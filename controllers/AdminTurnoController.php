<?php
require_once __DIR__ . '/../config/security.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/turnos_helper.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../public/login.php");
    exit;
}

if (($_SESSION["usuario_rol"] ?? "") !== "admin") {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/admin/turnos.php");
    exit;
}

csrf_verify();

$accion = $_POST["accion"] ?? "";
$id = turnos_id((string) ($_POST["id"] ?? ""));

if ($id === null) {
    header("Location: ../public/admin/turnos.php?error=datos_invalidos");
    exit;
}

// acción => [estado nuevo, estados desde los que se permite, requiere que el turno ya haya comenzado]
$transiciones = [
    "confirmar" => ["confirmado", ["pendiente"], false],
    "rechazar"  => ["cancelado", ["pendiente"], false], // rechazo de una reserva pendiente (canasta)
    "cancelar"  => ["cancelado", ["pendiente", "confirmado"], false],
    "completar" => ["completado", ["pendiente", "confirmado"], true],
    "ausente"   => ["ausente", ["pendiente", "confirmado"], true],
];

if (!is_string($accion) || !array_key_exists($accion, $transiciones)) {
    header("Location: ../public/admin/turnos.php?error=accion_invalida");
    exit;
}

[$nuevoEstado, $estadosOrigen, $requiereComenzado] = $transiciones[$accion];

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT estado, fecha, hora, requiere_confirmacion, usuario_id, sede_id, servicio_id FROM turnos WHERE id = :id FOR UPDATE");
    $stmt->execute([":id" => $id]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);

    $error = null;

    if (!$turno) {
        $error = "turno_inexistente";
    } elseif (!in_array($turno["estado"], $estadosOrigen, true)) {
        $error = "transicion_invalida";
    } elseif ($requiereComenzado && turnos_ts($turno["fecha"], substr($turno["hora"], 0, 5)) > time()) {
        $error = "turno_futuro";
    }

    if ($error !== null) {
        $conn->rollBack();
        header("Location: ../public/admin/turnos.php?error=$error");
        exit;
    }

    // Al resolver el turno sale de la canasta (requiere_confirmacion = 0).
    $upd = $conn->prepare("UPDATE turnos SET estado = :estado, requiere_confirmacion = 0 WHERE id = :id AND estado = :origen");
    foreach ($estadosOrigen as $origen) {
        if ($origen === $turno["estado"]) {
            $upd->execute([":estado" => $nuevoEstado, ":id" => $id, ":origen" => $origen]);
        }
    }
    $conn->commit();

    // Aviso por mail al cliente cuando se resuelve una reserva de la canasta (best-effort).
    if ((int) $turno["requiere_confirmacion"] === 1 && in_array($accion, ["confirmar", "rechazar", "cancelar"], true)) {
        try {
            require_once __DIR__ . '/../services/EmailService.php';
            $u = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
            $u->execute([":id" => $turno["usuario_id"]]);
            $usr = $u->fetch(PDO::FETCH_ASSOC);
            $sd = $conn->prepare("SELECT nombre FROM sedes WHERE id = :id");
            $sd->execute([":id" => $turno["sede_id"]]);
            $sv = $conn->prepare("SELECT nombre FROM servicios WHERE id = :id");
            $sv->execute([":id" => $turno["servicio_id"]]);
            if ($usr && $usr["email"]) {
                EmailService::enviarEstadoTurno(
                    $usr["email"], $usr["nombre"], $turno["fecha"], substr($turno["hora"], 0, 5),
                    $sd->fetchColumn(), $sv->fetchColumn(),
                    $accion === "confirmar" ? "confirmado" : "rechazado"
                );
            }
        } catch (\Throwable $e) {
            error_log("Haedo Barber: error enviando aviso de turno: " . $e->getMessage());
        }
    }

    $volver = (($_POST["vista"] ?? "") === "canasta") ? "&vista=canasta" : "";
    header("Location: ../public/admin/turnos.php?success=" . ($accion === "rechazar" ? "turno_rechazado" : "estado_actualizado") . $volver);
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Haedo Barber: error actualizando turno: " . $e->getMessage());
    header("Location: ../public/admin/turnos.php?error=db_error");
    exit;
}
