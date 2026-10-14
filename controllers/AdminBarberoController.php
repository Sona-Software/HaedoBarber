<?php

session_start();
require_once '../config/database.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/admin/barberos.php");
    exit;
}

$accion = $_POST["accion"] ?? "";

function subirFotoBarbero($inputName = "foto") {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$inputName]["error"] !== UPLOAD_ERR_OK) {
        return false;
    }

    $permitidos = ["jpg", "jpeg", "png", "webp"];
    $nombreOriginal = $_FILES[$inputName]["name"];
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    if (!in_array($extension, $permitidos)) {
        return false;
    }

    $carpetaDestino = "../uploads/barberos/";

    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    $nombreArchivo = "barbero_" . time() . "_" . rand(1000, 9999) . "." . $extension;
    $rutaDestino = $carpetaDestino . $nombreArchivo;

    if (!move_uploaded_file($_FILES[$inputName]["tmp_name"], $rutaDestino)) {
        return false;
    }

    return "uploads/barberos/" . $nombreArchivo;
}

try {

    if ($accion === "crear") {

        $nombre = trim($_POST["nombre"] ?? "");
        $especialidad = trim($_POST["especialidad"] ?? "");
        $sede_id = $_POST["sede_id"] ?? "";

        if ($nombre === "" || $especialidad === "" || $sede_id === "") {
            header("Location: ../public/admin/barberos.php?error=campos_vacios");
            exit;
        }

        $foto = subirFotoBarbero("foto");

        if ($foto === false) {
            header("Location: ../public/admin/barberos.php?error=foto_invalida");
            exit;
        }

        $sql = "INSERT INTO barberos (nombre, especialidad, foto, sede_id, activo)
                VALUES (:nombre, :especialidad, :foto, :sede_id, 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":especialidad", $especialidad);
        $stmt->bindParam(":foto", $foto);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->execute();

        header("Location: ../public/admin/barberos.php?success=creado");
        exit;
    }

    if ($accion === "editar") {

        $id = $_POST["id"] ?? "";
        $nombre = trim($_POST["nombre"] ?? "");
        $especialidad = trim($_POST["especialidad"] ?? "");
        $sede_id = $_POST["sede_id"] ?? "";
        $fotoActual = $_POST["foto_actual"] ?? "";

        if ($id === "" || $nombre === "" || $especialidad === "" || $sede_id === "") {
            header("Location: ../public/admin/barberos.php?error=campos_vacios");
            exit;
        }

        $fotoNueva = subirFotoBarbero("foto");

        if ($fotoNueva === false) {
            header("Location: ../public/admin/barberos.php?error=foto_invalida");
            exit;
        }

        $fotoFinal = $fotoNueva ?? $fotoActual;

        $sql = "UPDATE barberos
                SET nombre = :nombre,
                    especialidad = :especialidad,
                    foto = :foto,
                    sede_id = :sede_id
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":especialidad", $especialidad);
        $stmt->bindParam(":foto", $fotoFinal);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($fotoNueva !== null && str_starts_with($fotoActual, "uploads/barberos/")) {
            $rutaAnterior = "../" . $fotoActual;
            if (file_exists($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        header("Location: ../public/admin/barberos.php?success=editado");
        exit;
    }

    if ($accion === "desactivar") {

        $id = $_POST["id"] ?? "";

        $sql = "UPDATE barberos
                SET activo = 0
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        header("Location: ../public/admin/barberos.php?success=desactivado");
        exit;
    }

    if ($accion === "reactivar") {

        $id = $_POST["id"] ?? "";

        $sql = "UPDATE barberos
                SET activo = 1
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        header("Location: ../public/admin/barberos.php?success=reactivado");
        exit;
    }

    if ($accion === "agregar_foto") {

    $barbero_id = $_POST["barbero_id"] ?? "";

    if ($barbero_id === "") {
        header("Location: ../public/admin/barberos.php?error=campos_vacios");
        exit;
    }

    $foto = subirFotoBarbero("foto");

    if ($foto === false || $foto === null) {
        header("Location: ../public/admin/barberos.php?error=foto_invalida");
        exit;
    }

    $sql = "INSERT INTO barbero_fotos (barbero_id, foto, activo)
            VALUES (:barbero_id, :foto, 1)";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":barbero_id", $barbero_id);
    $stmt->bindParam(":foto", $foto);
    $stmt->execute();

    header("Location: ../public/admin/barberos.php?success=foto_agregada");
    exit;
    }

    if ($accion === "eliminar_foto") {

    $foto_id = $_POST["foto_id"] ?? "";
    $foto_ruta = $_POST["foto_ruta"] ?? "";

    if ($foto_id === "") {
        header("Location: ../public/admin/barberos.php?error=campos_vacios");
        exit;
    }

    $sql = "UPDATE barbero_fotos
            SET activo = 0
            WHERE id = :foto_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":foto_id", $foto_id);
    $stmt->execute();

    if (str_starts_with($foto_ruta, "uploads/barberos/")) {
        $rutaArchivo = "../" . $foto_ruta;

        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    header("Location: ../public/admin/barberos.php?success=foto_eliminada");
    exit;
    }

    header("Location: ../public/admin/barberos.php?error=accion_invalida");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/admin/barberos.php?error=db");
    exit;
}