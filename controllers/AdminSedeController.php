<?php

session_start();
require_once '../config/database.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/admin/sedes.php");
    exit;
}

$accion = $_POST["accion"] ?? "";

function subirFotoSede($inputName = "foto") {
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

    $carpetaDestino = "../uploads/sedes/";

    if (!is_dir($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    $nombreArchivo = "sede_" . time() . "_" . rand(1000, 9999) . "." . $extension;
    $rutaDestino = $carpetaDestino . $nombreArchivo;

    if (!move_uploaded_file($_FILES[$inputName]["tmp_name"], $rutaDestino)) {
        return false;
    }

    return "uploads/sedes/" . $nombreArchivo;
}

try {

    if ($accion === "crear") {
        $nombre = trim($_POST["nombre"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");

        if ($nombre === "" || $direccion === "") {
            header("Location: ../public/admin/sedes.php?error=campos_vacios");
            exit;
        }

        $foto = subirFotoSede("foto");

        if ($foto === false) {
            header("Location: ../public/admin/sedes.php?error=foto_invalida");
            exit;
        }

        $sql = "INSERT INTO sedes (nombre, direccion, foto, activo)
                VALUES (:nombre, :direccion, :foto, 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":direccion", $direccion);
        $stmt->bindParam(":foto", $foto);
        $stmt->execute();

        header("Location: ../public/admin/sedes.php?success=creada");
        exit;
    }

    if ($accion === "editar") {
        $id = $_POST["id"] ?? "";
        $nombre = trim($_POST["nombre"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");
        $fotoActual = $_POST["foto_actual"] ?? "";

        if ($id === "" || $nombre === "" || $direccion === "") {
            header("Location: ../public/admin/sedes.php?error=campos_vacios");
            exit;
        }

        $fotoNueva = subirFotoSede("foto");

        if ($fotoNueva === false) {
            header("Location: ../public/admin/sedes.php?error=foto_invalida");
            exit;
        }

        $fotoFinal = $fotoNueva ?? $fotoActual;

        $sql = "UPDATE sedes
                SET nombre = :nombre,
                    direccion = :direccion,
                    foto = :foto
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":direccion", $direccion);
        $stmt->bindParam(":foto", $fotoFinal);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($fotoNueva !== null && str_starts_with($fotoActual, "uploads/sedes/")) {
            $rutaAnterior = "../" . $fotoActual;

            if (file_exists($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        header("Location: ../public/admin/sedes.php?success=editada");
        exit;
    }

    if ($accion === "desactivar") {
        $id = $_POST["id"] ?? "";

        $sql = "UPDATE sedes
                SET activo = 0
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        header("Location: ../public/admin/sedes.php?success=desactivada");
        exit;
    }

    if ($accion === "reactivar") {
        $id = $_POST["id"] ?? "";

        $sql = "UPDATE sedes
                SET activo = 1
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        header("Location: ../public/admin/sedes.php?success=reactivada");
        exit;
    }

    if ($accion === "agregar_foto") {
        $sede_id = $_POST["sede_id"] ?? "";

        if ($sede_id === "") {
            header("Location: ../public/admin/sedes.php?error=campos_vacios");
            exit;
        }

        $foto = subirFotoSede("foto");

        if ($foto === false || $foto === null) {
            header("Location: ../public/admin/sedes.php?error=foto_invalida");
            exit;
        }

        $sql = "INSERT INTO sede_galeria (sede_id, foto, activo)
                VALUES (:sede_id, :foto, 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":sede_id", $sede_id);
        $stmt->bindParam(":foto", $foto);
        $stmt->execute();

        header("Location: ../public/admin/sedes.php?success=foto_agregada");
        exit;
    }

    if ($accion === "eliminar_foto") {
        $foto_id = $_POST["foto_id"] ?? "";
        $foto_ruta = $_POST["foto_ruta"] ?? "";

        if ($foto_id === "") {
            header("Location: ../public/admin/sedes.php?error=campos_vacios");
            exit;
        }

        $sql = "UPDATE sede_galeria
                SET activo = 0
                WHERE id = :foto_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":foto_id", $foto_id);
        $stmt->execute();

        if (str_starts_with($foto_ruta, "uploads/sedes/")) {
            $rutaArchivo = "../" . $foto_ruta;

            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }

        header("Location: ../public/admin/sedes.php?success=foto_eliminada");
        exit;
    }

    header("Location: ../public/admin/sedes.php?error=accion_invalida");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/admin/sedes.php?error=db");
    exit;
}
