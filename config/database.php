<?php

require_once __DIR__ . '/env.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'haedobarber_db';
$user = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Zona horaria única (PHP y MySQL) para que NOW()/CURDATE() coincidan con las horas de los turnos.
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    $conn->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log('Haedo Barber: no se pudo conectar a MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisá la configuración del servidor.');
}
