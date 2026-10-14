<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/user/panel_usuario.php");
    exit;
}

$accion = $_POST["accion"] ?? "";
$usuarioId = $_SESSION["usuario_id"];

if ($accion === "cancelar_usuario") {

    $id = $_POST["id"] ?? "";

    if (empty($id) || !is_numeric($id)) {
        header("Location: ../public/user/panel_usuario.php?error=datos_invalidos");
        exit;
    }

    try {
        $sql = "UPDATE turnos
                SET estado = 'cancelado'
                WHERE id = :id
                AND usuario_id = :usuario_id
                AND estado IN ('pendiente', 'confirmado')";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../public/user/panel_usuario.php?success=turno_cancelado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../public/user/panel_usuario.php?error=db_error");
        exit;
    }
}

if ($accion === "crear") {

    $sedeId = $_POST["sede_id"] ?? "";
    $barberoId = $_POST["barbero_id"] ?? "";
    $servicioId = $_POST["servicio_id"] ?? "";
    $fecha = $_POST["fecha"] ?? "";
    $hora = $_POST["hora"] ?? "";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if (
        empty($sedeId) ||
        empty($barberoId) ||
        empty($servicioId) ||
        empty($fecha) ||
        empty($hora)
    ) {
        header("Location: ../public/user/reservar_turno.php?error=campos_vacios");
        exit;
    }

    if (
        !is_numeric($sedeId) ||
        !is_numeric($barberoId) ||
        !is_numeric($servicioId)
    ) {
        header("Location: ../public/user/reservar_turno.php?error=datos_invalidos");
        exit;
    }

    if ($fecha < date("Y-m-d")) {
        header("Location: ../public/user/reservar_turno.php?error=fecha_pasada");
        exit;
    }

    $diaSemana = date("N", strtotime($fecha));

    if ($diaSemana == 1 || $diaSemana == 7) {
        header("Location: ../public/user/reservar_turno.php?error=dia_cerrado");
        exit;
    }

    try {
        $sqlValidar = "SELECT
                            b.id AS barbero_id,
                            b.sede_id,
                            sv.id AS servicio_id,
                            sv.duracion_min
                       FROM barberos b
                       INNER JOIN servicios sv ON sv.id = :servicio_id
                       WHERE b.id = :barbero_id
                       AND b.sede_id = :sede_id
                       AND b.activo = 1
                       AND sv.activo = 1";

        $stmtValidar = $conn->prepare($sqlValidar);
        $stmtValidar->bindParam(":servicio_id", $servicioId, PDO::PARAM_INT);
        $stmtValidar->bindParam(":barbero_id", $barberoId, PDO::PARAM_INT);
        $stmtValidar->bindParam(":sede_id", $sedeId, PDO::PARAM_INT);
        $stmtValidar->execute();

        $datosReserva = $stmtValidar->fetch(PDO::FETCH_ASSOC);

        if (!$datosReserva) {
            header("Location: ../public/user/reservar_turno.php?error=datos_invalidos");
            exit;
        }

        $duracionNueva = intval($datosReserva["duracion_min"]);

        $bloquesTrabajo = [
            ["10:30", "13:00"],
            ["14:30", "20:00"]
        ];

        $inicioNuevo = strtotime($fecha . " " . $hora);
        $finNuevo = $inicioNuevo + ($duracionNueva * 60);

        $dentroDeHorario = false;

        foreach ($bloquesTrabajo as $bloque) {
            $inicioBloque = strtotime($fecha . " " . $bloque[0]);
            $finBloque = strtotime($fecha . " " . $bloque[1]);

            if ($inicioNuevo >= $inicioBloque && $finNuevo <= $finBloque) {
                $dentroDeHorario = true;
                break;
            }
        }

        if (!$dentroDeHorario) {
            header("Location: ../public/user/reservar_turno.php?error=horario_invalido");
            exit;
        }

        $stmtTurnosUsuario = $conn->prepare("SELECT
                                                t.hora,
                                                sv.duracion_min
                                             FROM turnos t
                                             INNER JOIN servicios sv ON t.servicio_id = sv.id
                                             WHERE t.usuario_id = :usuario_id
                                             AND t.fecha = :fecha
                                             AND t.estado IN ('pendiente', 'confirmado')");

        $stmtTurnosUsuario->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
        $stmtTurnosUsuario->bindParam(":fecha", $fecha);
        $stmtTurnosUsuario->execute();

        $turnosUsuario = $stmtTurnosUsuario->fetchAll(PDO::FETCH_ASSOC);

        foreach ($turnosUsuario as $turnoUsuario) {
            $inicioExistenteUsuario = strtotime($fecha . " " . $turnoUsuario["hora"]);
            $finExistenteUsuario = $inicioExistenteUsuario + (intval($turnoUsuario["duracion_min"]) * 60);

            if ($inicioNuevo < $finExistenteUsuario && $finNuevo > $inicioExistenteUsuario) {
                header("Location: ../public/user/reservar_turno.php?error=usuario_turno_superpuesto");
                exit;
            }
        }

        $stmtTurnos = $conn->prepare("SELECT
                                        t.hora,
                                        sv.duracion_min
                                      FROM turnos t
                                      INNER JOIN servicios sv ON t.servicio_id = sv.id
                                      WHERE t.barbero_id = :barbero_id
                                      AND t.fecha = :fecha
                                      AND t.estado IN ('pendiente', 'confirmado')");

        $stmtTurnos->bindParam(":barbero_id", $barberoId, PDO::PARAM_INT);
        $stmtTurnos->bindParam(":fecha", $fecha);
        $stmtTurnos->execute();

        $turnosOcupados = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

        foreach ($turnosOcupados as $turnoOcupado) {
            $inicioExistente = strtotime($fecha . " " . $turnoOcupado["hora"]);
            $finExistente = $inicioExistente + (intval($turnoOcupado["duracion_min"]) * 60);

            if ($inicioNuevo < $finExistente && $finNuevo > $inicioExistente) {
                header("Location: ../public/user/reservar_turno.php?error=horario_ocupado");
                exit;
            }
        }

        $sqlCrear = "INSERT INTO turnos
                        (usuario_id, sede_id, barbero_id, servicio_id, fecha, hora, estado, observaciones)
                     VALUES
                        (:usuario_id, :sede_id, :barbero_id, :servicio_id, :fecha, :hora, 'pendiente', :observaciones)";

        $stmtCrear = $conn->prepare($sqlCrear);

        $stmtCrear->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
        $stmtCrear->bindParam(":sede_id", $sedeId, PDO::PARAM_INT);
        $stmtCrear->bindParam(":barbero_id", $barberoId, PDO::PARAM_INT);
        $stmtCrear->bindParam(":servicio_id", $servicioId, PDO::PARAM_INT);
        $stmtCrear->bindParam(":fecha", $fecha);
        $stmtCrear->bindParam(":hora", $hora);
        $stmtCrear->bindParam(":observaciones", $observaciones);

        $stmtCrear->execute();
        // ENVÍO DE CORREO DE CONFIRMACIÓN ---
        require_once '../services/Emailservice.php';

        //  Obtener el email del usuario usando su ID
        $stmtUser = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = :id"); //
        $stmtUser->execute([':id' => $usuarioId]); //
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        //  Obtener el nombre de la sede y el servicio para el correo
        $stmtSede = $conn->prepare("SELECT nombre FROM sedes WHERE id = :id"); //
        $stmtSede->execute([':id' => $sedeId]); //
        $nombreSede = $stmtSede->fetchColumn();

        $stmtServ = $conn->prepare("SELECT nombre FROM servicios WHERE id = :id"); //
        $stmtServ->execute([':id' => $servicioId]); //
        $nombreServicio = $stmtServ->fetchColumn();

        if ($userData && $userData['email']) {
            EmailService::enviarConfirmacion(
                $userData['email'], 
                $userData['nombre'], 
                $fecha, //
                $hora,  //
                $nombreSede, 
                $nombreServicio
            );
        }
        header("Location: ../public/user/panel_usuario.php?success=turno_creado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../public/user/reservar_turno.php?error=db_error");
        exit;
    }
}

header("Location: ../public/user/panel_usuario.php");
exit;
