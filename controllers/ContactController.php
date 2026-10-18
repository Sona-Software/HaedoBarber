<?php

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/contacto.php");
    exit;
}

csrf_verify();

// Honeypot: el campo "sitio_web" está oculto para personas; los bots lo completan.
if (trim((string) ($_POST["sitio_web"] ?? "")) !== "") {
    header("Location: ../public/contacto.php?success=1");
    exit;
}

// Límite por IP: 5 mensajes por hora.
if (rl_bloqueado($conn, "contacto", "", 5, 5, PHP_INT_MAX, 3600)) {
    header("Location: ../public/contacto.php?error=demasiados_intentos");
    exit;
}
rl_registrar($conn, "contacto", "");

// Nombre, email y asunto son de una sola línea: se eliminan CR/LF y otros caracteres de control
// (evita inyección de cabeceras en el mail).
$nombre = limpiar_linea($_POST["nombre"] ?? "", 120);
$email = limpiar_linea($_POST["email"] ?? "", 120);
$asunto = limpiar_linea($_POST["asunto"] ?? "", 150);
$mensaje = limpiar_texto($_POST["mensaje"] ?? "", 500);

if ($nombre === "" || $email === "" || $mensaje === "") {
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

    $destinatario = limpiar_linea($_ENV['CONTACT_EMAIL'] ?? '', 120);
    $remitente = limpiar_linea($_ENV['SMTP_FROM'] ?? '', 120);
    $titulo = "Nuevo mensaje de contacto - Haedo Barber";
    $contenido = "Nombre: $nombre\n";
    $contenido .= "Email: $email\n";
    $contenido .= "Asunto: $asunto\n\n";
    $contenido .= "Mensaje:\n$mensaje\n";

    if (filter_var($destinatario, FILTER_VALIDATE_EMAIL) && filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
        // Valores ya sin saltos de línea; el Reply-To es un email validado.
        $headers = "From: $remitente\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($destinatario, $titulo, $contenido, $headers);
    }

    header("Location: ../public/contacto.php?success=1");
    exit;

} catch (PDOException $e) {
    error_log('Haedo Barber: error al guardar contacto: ' . $e->getMessage());
    header("Location: ../public/contacto.php?error=db_error");
    exit;
}
