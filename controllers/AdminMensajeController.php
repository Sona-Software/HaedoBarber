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
    header("Location: ../public/admin/mensajes.php");
    exit;
}

$id = $_POST["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: ../public/admin/mensajes.php?error=id_invalido");
    exit;
}

try {
    $sql = "DELETE FROM contactos WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../public/admin/mensajes.php?success=eliminado");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/admin/mensajes.php?error=db_error");
    exit;
}