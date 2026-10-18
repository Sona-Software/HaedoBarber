<?php

require_once __DIR__ . '/../config/security.php';
require_once '../config/database.php';

if (!isset($_SESSION["usuario_id"]) || $_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../public/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/admin/sedes.php");
    exit;
}

csrf_verify();

// Validación común de entradas: ids numéricos y longitudes máximas.
foreach (["id", "sede_id", "barbero_id", "foto_id"] as $campoId) {
    if (isset($_POST[$campoId]) && (!is_string($_POST[$campoId]) || ($_POST[$campoId] !== "" && !ctype_digit($_POST[$campoId])))) {
        header("Location: ../public/admin/sedes.php?error=datos_invalidos");
        exit;
    }
}
foreach (["nombre" => 80, "direccion" => 150] as $campoTexto => $maxLargo) {
    if (isset($_POST[$campoTexto]) && (!is_string($_POST[$campoTexto]) || mb_strlen(trim($_POST[$campoTexto]), "UTF-8") > $maxLargo)) {
        header("Location: ../public/admin/sedes.php?error=datos_invalidos");
        exit;
    }
}

$accion = $_POST["accion"] ?? "";

// Subida segura (MIME real con finfo, getimagesize, 5 MB, nombre aleatorio): ver config/security.php
function subirFotoSede($inputName = "foto") {
    return subir_imagen($inputName, "sedes", "sede");
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

        if ($id === "" || !ctype_digit((string) $id) || $nombre === "" || $direccion === "") {
            header("Location: ../public/admin/sedes.php?error=campos_vacios");
            exit;
        }

        // La foto actual se toma de la base de datos, nunca del formulario.
        $stmtFotoActual = $conn->prepare("SELECT foto FROM sedes WHERE id = :id");
        $stmtFotoActual->execute([":id" => $id]);
        $fotoActual = (string) ($stmtFotoActual->fetchColumn() ?: "");

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
            $rutaAnterior = __DIR__ . "/../uploads/sedes/" . basename($fotoActual);

            if (is_file($rutaAnterior)) {
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

        if ($foto_id === "" || !ctype_digit((string) $foto_id)) {
            header("Location: ../public/admin/sedes.php?error=campos_vacios");
            exit;
        }

        // La ruta se toma de la base de datos, nunca del formulario.
        $stmtRuta = $conn->prepare("SELECT foto FROM sede_galeria WHERE id = :foto_id");
        $stmtRuta->execute([":foto_id" => $foto_id]);
        $foto_ruta = (string) ($stmtRuta->fetchColumn() ?: "");

        $sql = "UPDATE sede_galeria
                SET activo = 0
                WHERE id = :foto_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":foto_id", $foto_id);
        $stmt->execute();

        if (str_starts_with($foto_ruta, "uploads/sedes/")) {
            $rutaArchivo = __DIR__ . "/../uploads/sedes/" . basename($foto_ruta);

            if (is_file($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }

        header("Location: ../public/admin/sedes.php?success=foto_eliminada");
        exit;
    }

    header("Location: ../public/admin/sedes.php?error=accion_invalida");
    exit;

} catch (PDOException $e) {
    error_log('Haedo Barber (AdminSede): ' . $e->getMessage());
    header("Location: ../public/admin/sedes.php?error=db");
    exit;
}
