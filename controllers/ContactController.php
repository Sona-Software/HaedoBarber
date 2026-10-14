<?php

require_once '../config/database.php';
require_once __DIR__ . '/../config/env.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/contacto.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$email = trim($_POST["email"] ?? "");
$asunto = trim($_POST["asunto"] ?? "");
$mensaje = trim($_POST["mensaje"] ?? "");

if (empty($nombre) || empty($email) || empty($mensaje)) {
    header("Location: ../public/contacto.php?error=campos_vacios");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../public/contacto.php?error=email_invalido");
    exit;
}

try {
    $sql = "INSERT INTO contactos (nombre, email, asunto, mensaje)
            VALUES (:nombre, :email, :asunto, :mensaje)";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":asunto", $asunto);
    $stmt->bindParam(":mensaje", $mensaje);

    $stmt->execute();

    // Simulación de envío de email 
    $destinatario = trim($_ENV['CONTACT_EMAIL'] ?? '');
    $remitente = trim($_ENV['SMTP_FROM'] ?? '');
    $titulo = "Nuevo mensaje de contacto - Haedo Barber";
    $contenido = "Nombre: $nombre\n";
    $contenido .= "Email: $email\n";
    $contenido .= "Asunto: $asunto\n\n";
    $contenido .= "Mensaje:\n$mensaje\n";

    if (filter_var($destinatario, FILTER_VALIDATE_EMAIL) && filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
        $headers = "From: $remitente\r\n";
        $headers .= "Reply-To: $email\r\n";
        @mail($destinatario, $titulo, $contenido, $headers);
    }

    header("Location: ../public/contacto.php?success=1");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/contacto.php?error=db_error");
    exit;
}
