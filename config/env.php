<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envFile) || !is_readable($envFile)) {
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
