<?php
require_once __DIR__ . '/../config/security.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/turnos_helper.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/user/panel_usuario.php");
    exit;
}

csrf_verify();

$accion = $_POST["accion"] ?? "";
$usuarioId = turnos_id((string) $_SESSION["usuario_id"]);

if ($usuarioId === null) {
    header("Location: ../public/login.php");
    exit;
}

$ip = turnos_ip_cliente();

/* ------------------------------ Cancelar (usuario) ------------------------------ */
if ($accion === "cancelar_usuario") {

    $panel = "../public/user/panel_usuario.php";
    $id = turnos_id($_POST["id"] ?? "");

    if ($id === null) {
        header("Location: $panel?error=datos_invalidos");
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT id, fecha, hora, estado
                                FROM turnos
                                WHERE id = :id AND usuario_id = :usuario_id
                                FOR UPDATE");
        $stmt->execute([":id" => $id, ":usuario_id" => $usuarioId]);
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);

        $error = null;

        if (!$turno) {
            $error = "turno_inexistente";
        } elseif (!in_array($turno["estado"], ["pendiente", "confirmado"], true)) {
            $error = "turno_no_cancelable";
        } else {
            $inicio = turnos_ts($turno["fecha"], substr($turno["hora"], 0, 5));
            if ($inicio - time() < TURNOS_CANCEL_MIN_HORAS * 3600) {
                $error = "cancelacion_tardia";
            } elseif (turnos_cancelaciones_recientes($conn, $usuarioId) >= TURNOS_CANCEL_MAX) {
                $error = "limite_cancelaciones";
            }
        }

        if ($error !== null) {
            $conn->rollBack();
            header("Location: $panel?error=$error");
            exit;
        }

        $upd = $conn->prepare("UPDATE turnos SET estado = 'cancelado'
                               WHERE id = :id AND usuario_id = :usuario_id
                               AND estado IN ('pendiente', 'confirmado')");
        $upd->execute([":id" => $id, ":usuario_id" => $usuarioId]);
        $conn->commit();

        turnos_log_intento($conn, $ip, $usuarioId, "cancelar", true);

        header("Location: $panel?success=turno_cancelado");
        exit;

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Haedo Barber: error cancelando turno: " . $e->getMessage());
        header("Location: $panel?error=db_error");
        exit;
    }
}

/* ------------------------------ Crear ------------------------------ */
if ($accion === "crear") {

    $sel = [
        "sede_id" => (string) ($_POST["sede_id"] ?? ""),
        "barbero_id" => (string) ($_POST["barbero_id"] ?? ""),
        "servicio_id" => (string) ($_POST["servicio_id"] ?? ""),
        "fecha" => (string) ($_POST["fecha"] ?? ""),
    ];
    $hora = trim((string) ($_POST["hora"] ?? ""));
    $observaciones = trim((string) ($_POST["observaciones"] ?? ""));

    $volver = function (string $error) use ($sel) {
        header("Location: " . turnos_url_reserva("../public/user/", $error, $sel));
        exit;
    };

    try {
        // 1) Rate limiting por IP y por cuenta (antes de hacer cualquier otro trabajo).
        $limite = turnos_rate_limit($conn, $ip, $usuarioId, "crear");
        if ($limite !== null) {
            $volver($limite);
        }
        $intentoId = turnos_log_intento($conn, $ip, $usuarioId, "crear", false);

        // 2) Datos básicos
        if ($sel["sede_id"] === "" || $sel["barbero_id"] === "" || $sel["servicio_id"] === "" || $sel["fecha"] === "" || $hora === "") {
            $volver("campos_vacios");
        }

        $sedeId = turnos_id($sel["sede_id"]);
        $barberoId = turnos_id($sel["barbero_id"]);
        $servicioId = turnos_id($sel["servicio_id"]);

        if ($sedeId === null || $barberoId === null || $servicioId === null) {
            $volver("datos_invalidos");
        }

        if (mb_strlen($observaciones, "UTF-8") > TURNOS_OBS_MAX) {
            $volver("observaciones_largas");
        }
        // Sin caracteres de control (salvo saltos de línea)
        $observaciones = preg_replace('/[^\P{C}\n]/u', "", $observaciones) ?? "";

        // 3) Restricciones de la cuenta (cuenta nueva, ausencias)
        $restriccion = turnos_restriccion_usuario($conn, $usuarioId);
        if ($restriccion !== null) {
            $volver($restriccion);
        }

        // 4) Sede / barbero / servicio válidos y activos
        $stmtValidar = $conn->prepare("SELECT sv.duracion_min
                                       FROM barberos b
                                       INNER JOIN sedes s ON s.id = b.sede_id AND s.activo = 1
                                       INNER JOIN servicios sv ON sv.id = :servicio_id AND sv.activo = 1
                                       WHERE b.id = :barbero_id
                                       AND b.sede_id = :sede_id
                                       AND b.activo = 1");
        $stmtValidar->execute([
            ":servicio_id" => $servicioId,
            ":barbero_id" => $barberoId,
            ":sede_id" => $sedeId,
        ]);
        $datosReserva = $stmtValidar->fetch(PDO::FETCH_ASSOC);

        if (!$datosReserva) {
            $volver("datos_invalidos");
        }

        $duracionNueva = intval($datosReserva["duracion_min"]);
        $fecha = $sel["fecha"];

        // 5) Fecha/hora: misma validación que usa la grilla de disponibilidad
        $errHorario = turnos_validar_horario($fecha, $hora, $duracionNueva, time());
        if ($errHorario !== null) {
            $volver($errHorario);
        }

        $inicioNuevo = turnos_ts($fecha, $hora);
        $finNuevo = $inicioNuevo + ($duracionNueva * 60);

        // 6) Transacción: se bloquea la cuenta y el barbero para serializar reservas concurrentes
        $conn->beginTransaction();

        $stmtLockU = $conn->prepare("SELECT id FROM usuarios WHERE id = :id FOR UPDATE");
        $stmtLockU->execute([":id" => $usuarioId]);
        if (!$stmtLockU->fetchColumn()) {
            $conn->rollBack();
            header("Location: ../public/login.php");
            exit;
        }

        $stmtLock = $conn->prepare("SELECT id FROM barberos WHERE id = :id FOR UPDATE");
        $stmtLock->execute([":id" => $barberoId]);

        $errUsuario = turnos_validar_limites_usuario($conn, $usuarioId, $fecha, $inicioNuevo, $finNuevo);
        if ($errUsuario !== null) {
            $conn->rollBack();
            $volver($errUsuario);
        }

        foreach (turnos_ocupados_barbero($conn, $barberoId, $fecha) as $ocupado) {
            $iniEx = turnos_ts($fecha, substr($ocupado["hora"], 0, 5));
            $finEx = $iniEx + (intval($ocupado["duracion_min"]) * 60);

            if (turnos_solapan($inicioNuevo, $finNuevo, $iniEx, $finEx)) {
                $conn->rollBack();
                $volver("horario_ocupado");
            }
        }

        $stmtCrear = $conn->prepare("INSERT INTO turnos
                        (usuario_id, sede_id, barbero_id, servicio_id, fecha, hora, estado, observaciones)
                     VALUES
                        (:usuario_id, :sede_id, :barbero_id, :servicio_id, :fecha, :hora, 'pendiente', :observaciones)");
        $stmtCrear->execute([
            ":usuario_id" => $usuarioId,
            ":sede_id" => $sedeId,
            ":barbero_id" => $barberoId,
            ":servicio_id" => $servicioId,
            ":fecha" => $fecha,
            ":hora" => $hora,
            ":observaciones" => $observaciones,
        ]);
        $conn->commit();

        turnos_marcar_exito($conn, $intentoId);

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // 1062 = clave duplicada: otro usuario tomó el horario justo antes.
        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
            $volver("horario_ocupado");
        }
        error_log("Haedo Barber: error creando turno: " . $e->getMessage());
        $volver("db_error");
    }

    // Correo de confirmación: el turno ya está creado, un fallo de mail no debe afectar el resultado.
    try {
        require_once __DIR__ . '/../services/EmailService.php';

        $stmtUser = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
        $stmtUser->execute([":id" => $usuarioId]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $stmtSede = $conn->prepare("SELECT nombre FROM sedes WHERE id = :id");
        $stmtSede->execute([":id" => $sedeId]);
        $nombreSede = $stmtSede->fetchColumn();

        $stmtServ = $conn->prepare("SELECT nombre FROM servicios WHERE id = :id");
        $stmtServ->execute([":id" => $servicioId]);
        $nombreServicio = $stmtServ->fetchColumn();

        if ($userData && $userData["email"]) {
            EmailService::enviarConfirmacion(
                $userData["email"],
                $userData["nombre"],
                $fecha,
                $hora,
                $nombreSede,
                $nombreServicio
            );
        }
    } catch (\Throwable $e) {
        error_log("Haedo Barber: error enviando confirmación: " . $e->getMessage());
    }

    header("Location: ../public/user/panel_usuario.php?success=turno_creado");
    exit;
}

header("Location: ../public/user/panel_usuario.php");
exit;
