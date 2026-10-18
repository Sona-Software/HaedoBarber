<?php
// Sólo por consola / cron: nunca accesible desde el navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailService.php';

try {
    // Buscar turnos pendientes o confirmados, que no tengan recordatorio enviado, 
    // y cuya fecha/hora esté entre AHORA y exactamente dentro de 2 horas.
    $sql = "SELECT 
                t.id AS turno_id, t.hora, 
                u.email, u.nombre AS usuario_nombre, 
                s.nombre AS sede_nombre
            FROM turnos t
            INNER JOIN usuarios u ON t.usuario_id = u.id
            INNER JOIN sedes s ON t.sede_id = s.id
            WHERE t.estado IN ('pendiente', 'confirmado') 
            AND t.recordatorio_enviado = 0
            AND CONCAT(t.fecha, ' ', t.hora) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparar el update para marcar el correo como enviado
    $stmtUpdate = $conn->prepare("UPDATE turnos SET recordatorio_enviado = 1 WHERE id = :id");

    foreach ($turnos as $turno) {
        if (!empty($turno['email'])) {
            // Enviar correo
            $enviado = EmailService::enviarRecordatorio(
                $turno['email'],
                $turno['usuario_nombre'],
                substr($turno['hora'], 0, 5),
                $turno['sede_nombre']
            );

            if (!$enviado) {
                echo "No se pudo enviar el recordatorio del turno ID: " . $turno['turno_id'] . "\n";
                continue;
            }

            // Marcar en la BD que ya se envió para no volver a mandarlo
            $stmtUpdate->execute([':id' => $turno['turno_id']]);

            echo "Recordatorio enviado al turno ID: " . $turno['turno_id'] . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Error procesando recordatorios: " . $e->getMessage();
}