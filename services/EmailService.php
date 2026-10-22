<?php

require_once __DIR__ . '/../config/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

class EmailService {

    private static function configurarMailer() {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $puerto = (int) ($_ENV['SMTP_PORT'] ?? 465);
        $mail->SMTPSecure = $puerto === 587 ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $puerto;
        $mail->SMTPDebug  = 0;

        $mail->setFrom($_ENV['SMTP_FROM'] ?? '', $_ENV['SMTP_FROM_NAME'] ?? 'Haedo Barber');
        
        return $mail;
    }

    private static function esc($valor) {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }

    public static function enviarConfirmacion($emailDestino, $nombreUsuario, $fecha, $hora, $sede, $servicio) {
        try {
            $nombrePlano = trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $nombreUsuario)); // sin CR/LF (header injection)
            $nombreUsuario = self::esc($nombreUsuario);
            $fecha = self::esc($fecha);
            $hora = self::esc($hora);
            $sede = self::esc($sede);
            $servicio = self::esc($servicio);
            $mail = self::configurarMailer();
            $mail->addAddress($emailDestino, $nombrePlano);
            $mail->isHTML(true);
            $mail->Subject = 'Confirmacion de Turno - HAEDO BARBER';
            $mail->Body    = "<h3>¡Hola $nombreUsuario!</h3><p>Tu turno ha sido reservado exitosamente.</p><ul><li><strong>Fecha:</strong> $fecha</li><li><strong>Hora:</strong> $hora</li><li><strong>Sede:</strong> $sede</li><li><strong>Servicio:</strong> $servicio</li></ul><p>¡Te esperamos!</p>";
            return $mail->send();
        } catch (\Throwable $e) {
            error_log('Haedo Barber: error enviando confirmacion: ' . $e->getMessage());
            return false;
        }
    }

    /** Reserva que quedó en la canasta de confirmación manual (pendiente hasta que la barbería la revise). */
    public static function enviarPendiente($emailDestino, $nombreUsuario, $fecha, $hora, $sede, $servicio) {
        return self::enviarEstadoTurno($emailDestino, $nombreUsuario, $fecha, $hora, $sede, $servicio, 'pendiente');
    }

    /** Aviso al cliente cuando el admin resuelve una reserva de la canasta: $tipo = 'confirmado' | 'rechazado'. */
    public static function enviarEstadoTurno($emailDestino, $nombreUsuario, $fecha, $hora, $sede, $servicio, $tipo) {
        try {
            $nombrePlano = trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $nombreUsuario)); // sin CR/LF (header injection)
            $nombreUsuario = self::esc($nombreUsuario);
            $fecha = self::esc($fecha);
            $hora = self::esc($hora);
            $sede = self::esc($sede);
            $servicio = self::esc($servicio);
            if ($tipo === 'confirmado') {
                $asunto = 'Turno confirmado - HAEDO BARBER';
                $intro = 'Tu turno quedó <strong>confirmado</strong>.';
                $cierre = '¡Te esperamos!';
            } elseif ($tipo === 'rechazado') {
                $asunto = 'Turno no confirmado - HAEDO BARBER';
                $intro = 'Lamentablemente no pudimos confirmar tu turno y fue cancelado.';
                $cierre = 'Podés elegir otro horario o contactarnos por WhatsApp.';
            } else {
                $asunto = 'Turno pendiente de confirmación - HAEDO BARBER';
                $intro = 'Recibimos tu reserva y quedó <strong>pendiente de confirmación</strong>. El horario queda reservado mientras la revisamos y te avisaremos por WhatsApp.';
                $cierre = 'Gracias por tu paciencia.';
            }
            $mail = self::configurarMailer();
            $mail->addAddress($emailDestino, $nombrePlano);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = "<h3>¡Hola $nombreUsuario!</h3><p>$intro</p><ul><li><strong>Fecha:</strong> $fecha</li><li><strong>Hora:</strong> $hora</li><li><strong>Sede:</strong> $sede</li><li><strong>Servicio:</strong> $servicio</li></ul><p>$cierre</p>";
            return $mail->send();
        } catch (\Throwable $e) {
            error_log('Haedo Barber: error enviando aviso de turno: ' . $e->getMessage());
            return false;
        }
    }

    public static function enviarRecordatorio($emailDestino, $nombreUsuario, $hora, $sede) {
        try {
            $nombrePlano = trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $nombreUsuario)); // sin CR/LF (header injection)
            $nombreUsuario = self::esc($nombreUsuario);
            $hora = self::esc($hora);
            $sede = self::esc($sede);
            $mail = self::configurarMailer();
            $mail->addAddress($emailDestino, $nombrePlano);
            $mail->isHTML(true);
            $mail->Subject = 'Recordatorio de Turno - HAEDO BARBER';
            $mail->Body    = "<h3>¡Hola $nombreUsuario!</h3><p>Te recordamos que tienes un turno con nosotros <strong>hoy a las $hora</strong> en nuestra sucursal de <strong>$sede</strong>.</p><p>Por favor, intenta llegar 5 minutos antes.</p><p>¡Nos vemos pronto!</p>";
            return $mail->send();
        } catch (\Throwable $e) {
            error_log('Haedo Barber: error enviando recordatorio: ' . $e->getMessage());
            return false;
        }
    }
}
