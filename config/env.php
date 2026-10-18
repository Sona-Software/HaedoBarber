<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envFile) || !is_readable($envFile)) {
    // Sin .env: se aceptan variables de entorno reales del servidor.
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_PORT', 'SMTP_FROM', 'SMTP_FROM_NAME', 'CONTACT_EMAIL', 'TRUST_PROXY'] as $clave) {
        $valorEntorno = getenv($clave);
        if ($valorEntorno !== false) {
            $_ENV[$clave] = $valorEntorno;
        }
    }
    return;
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//') || !str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim(trim($value), "\"'");
    if ($key === '') {
        continue;
    }

    $existingValue = getenv($key);
    if ($existingValue !== false) {
        $_ENV[$key] = $existingValue;
        continue;
    }

    $_ENV[$key] = $value;
    putenv($key . '=' . $value);
}
