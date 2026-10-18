<?php
/**
 * Crea (o promueve) un usuario administrador desde la línea de comandos.
 *
 * Uso:
 *   php scripts/crear_admin.php email@dominio.com "ClaveLarga123" [Nombre] [Apellido] [--forzar]
 *   php scripts/crear_admin.php email@dominio.com            (la clave se lee desde la entrada estándar)
 *
 * --forzar: si el email ya existe, le cambia la clave y lo convierte en admin.
 * Política de clave: 10 a 72 caracteres, con letras y números.
 * Sólo se ejecuta por consola (nunca desde el navegador).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';

$args = array_slice($argv, 1);
$forzar = in_array('--forzar', $args, true);
$args = array_values(array_filter($args, static fn($a) => $a !== '--forzar'));

if (count($args) < 1) {
    fwrite(STDERR, "Uso: php scripts/crear_admin.php email [clave] [Nombre] [Apellido] [--forzar]\n");
    exit(1);
}

$email = limpiar_linea($args[0], 120);
$password = $args[1] ?? null;
$nombre = limpiar_linea($args[2] ?? 'Admin', 80);
$apellido = limpiar_linea($args[3] ?? 'Sistema', 80);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email inválido.\n");
    exit(1);
}

if ($password === null) {
    fwrite(STDERR, "Contraseña (se mostrará al escribirla): ");
    $password = rtrim((string) fgets(STDIN), "\r\n");
}

$error = validar_password($password, $email);
if ($error !== null) {
    $mensajes = [
        'password_corta' => 'La contraseña debe tener al menos 10 caracteres.',
        'password_larga' => 'La contraseña no puede superar los 72 caracteres.',
        'password_debil' => 'La contraseña debe incluir letras y números y no ser una clave común.',
    ];
    fwrite(STDERR, ($mensajes[$error] ?? 'Contraseña no válida.') . "\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $existente = $stmt->fetchColumn();

    if ($existente) {
        if (!$forzar) {
            fwrite(STDERR, "Ya existe un usuario con ese email. Usá --forzar para cambiar su clave y hacerlo admin.\n");
            exit(1);
        }
        $upd = $conn->prepare("UPDATE usuarios SET password = :p, rol = 'admin' WHERE id = :id");
        $upd->execute([':p' => $hash, ':id' => $existente]);
        echo "Usuario existente actualizado: ahora es administrador.\n";
        exit(0);
    }

    $ins = $conn->prepare(
        "INSERT INTO usuarios (nombre, apellido, telefono, email, password, rol)
         VALUES (:nombre, :apellido, '', :email, :password, 'admin')"
    );
    $ins->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':email' => $email,
        ':password' => $hash,
    ]);
    echo "Administrador creado: $email\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Error de base de datos: " . $e->getMessage() . "\n");
    exit(1);
}
