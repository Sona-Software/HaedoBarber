<?php
require_once __DIR__ . '/../config/security.php';

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
    header("Location: ../public/admin/servicios.php");
    exit;
}

csrf_verify();

// Límites de entrada: longitudes máximas y rangos numéricos razonables.
foreach (["nombre" => 100, "descripcion" => 1000, "id" => 10, "precio" => 12, "duracion_min" => 4] as $campoTexto => $maxLargo) {
    if (isset($_POST[$campoTexto]) && (!is_string($_POST[$campoTexto]) || mb_strlen(trim($_POST[$campoTexto]), "UTF-8") > $maxLargo)) {
        header("Location: ../public/admin/servicios.php?error=datos_invalidos");
        exit;
    }
}
if (
    (isset($_POST["precio"]) && is_numeric($_POST["precio"]) && (float) $_POST["precio"] > 99999999) ||
    (isset($_POST["duracion_min"]) && is_numeric($_POST["duracion_min"]) && (float) $_POST["duracion_min"] > 600)
) {
    header("Location: ../public/admin/servicios.php?error=datos_invalidos");
    exit;
}

$accion = $_POST["accion"] ?? "";

if ($accion === "crear") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $duracion_min = $_POST["duracion_min"] ?? "";
    $activo = 1;

    if (empty($nombre) || $precio === "" || $duracion_min === "") {
        header("Location: ../public/admin/servicios.php?error=campos_vacios");
        exit;
    }

    if (!is_numeric($precio) || $precio < 0 || !is_numeric($duracion_min) || $duracion_min <= 0) {
        header("Location: ../public/admin/servicios.php?error=datos_invalidos");
        exit;
    }

    try {
        $sql = "INSERT INTO servicios (nombre, descripcion, precio, duracion_min, activo)
                VALUES (:nombre, :descripcion, :precio, :duracion_min, :activo)";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":descripcion", $descripcion);
        $stmt->bindParam(":precio", $precio);
        $stmt->bindParam(":duracion_min", $duracion_min, PDO::PARAM_INT);
        $stmt->bindParam(":activo", $activo, PDO::PARAM_INT);

        $stmt->execute();

        header("Location: ../public/admin/servicios.php?success=creado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../public/admin/servicios.php?error=db_error");
        exit;
    }
}

if ($accion === "editar") {

    $id = $_POST["id"] ?? "";
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $precio = $_POST["precio"] ?? "";
    $duracion_min = $_POST["duracion_min"] ?? "";

    if (empty($id) || empty($nombre) || $precio === "" || $duracion_min === "") {
        header("Location: ../public/admin/servicios.php?error=campos_vacios");
        exit;
    }

    if (!is_numeric($id) || !is_numeric($precio) || $precio < 0 || !is_numeric($duracion_min) || $duracion_min <= 0) {
        header("Location: ../public/admin/servicios.php?error=datos_invalidos");
        exit;
    }

    try {
        $sql = "UPDATE servicios
                SET nombre = :nombre,
                    descripcion = :descripcion,
                    precio = :precio,
                    duracion_min = :duracion_min
                WHERE id = :id";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":descripcion", $descripcion);
        $stmt->bindParam(":precio", $precio);
        $stmt->bindParam(":duracion_min", $duracion_min, PDO::PARAM_INT);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);

        $stmt->execute();

        header("Location: ../public/admin/servicios.php?success=editado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../public/admin/servicios.php?error=db_error");
        exit;
    }
}

if ($accion === "desactivar") {

    $id = $_POST["id"] ?? "";

    if (empty($id) || !is_numeric($id)) {
        header("Location: ../public/admin/servicios.php?error=datos_invalidos");
        exit;
    }

    try {
        $sql = "UPDATE servicios
                SET activo = 0
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../public/admin/servicios.php?success=desactivado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../public/admin/servicios.php?error=db_error");
        exit;
    }
}

if ($accion === "reactivar") {

    $id = $_POST["id"] ?? "";

    if (empty($id) || !is_numeric($id)) {
        header("Location: ../public/admin/servicios.php?error=datos_invalidos");
        exit;
    }

    try {
        $sql = "UPDATE servicios
                SET activo = 1
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../public/admin/servicios.php?success=reactivado");
        exit;

    } catch (PDOException $e) {
        header("Location: ../public/admin/servicios.php?error=db_error");
        exit;
    }
}

header("Location: ../public/admin/servicios.php");
exit;