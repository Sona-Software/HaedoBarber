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

    $stmt = $conn->prepare("SELECT estado, fecha, hora FROM turnos WHERE id = :id FOR UPDATE");
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

    $upd = $conn->prepare("UPDATE turnos SET estado = :estado WHERE id = :id AND estado = :origen");
    foreach ($estadosOrigen as $origen) {
        if ($origen === $turno["estado"]) {
            $upd->execute([":estado" => $nuevoEstado, ":id" => $id, ":origen" => $origen]);
        }
    }
    $conn->commit();

    header("Location: ../public/admin/turnos.php?success=estado_actualizado");
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Haedo Barber: error actualizando turno: " . $e->getMessage());
    header("Location: ../public/admin/turnos.php?error=db_error");
    exit;
}
