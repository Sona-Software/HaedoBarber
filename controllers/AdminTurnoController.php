<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../public/login.php");
    exit;
}

if ($_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/admin/turnos.php");
    exit;
}

$accion = $_POST["accion"] ?? "";
$id = $_POST["id"] ?? "";

if (empty($id) || !is_numeric($id)) {
    header("Location: ../public/admin/turnos.php?error=datos_invalidos");
    exit;
}

$estadosPermitidos = [
    "confirmar" => "confirmado",
    "cancelar" => "cancelado",
    "completar" => "completado",
    "ausente" => "ausente"
];

if (!array_key_exists($accion, $estadosPermitidos)) {
    header("Location: ../public/admin/turnos.php?error=accion_invalida");
    exit;
}

$nuevoEstado = $estadosPermitidos[$accion];

try {
    $sql = "UPDATE turnos
            SET estado = :estado
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":estado", $nuevoEstado);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../public/admin/turnos.php?success=estado_actualizado");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/admin/turnos.php?error=db_error");
    exit;
}