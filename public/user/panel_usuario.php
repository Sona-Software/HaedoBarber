<?php
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../config/turnos_helper.php';

$usuarioId = $_SESSION["usuario_id"];
$nombreUsuario = $_SESSION["usuario_nombre"] ?? "Usuario";
$emailUsuario = $_SESSION["usuario_email"] ?? "";

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";
$errorDB = false;
$restriccionUsuario = null;

try {
    $sqlProximos = "SELECT
                        t.id,
                        t.fecha,
                        t.hora,
                        t.estado,
                        t.observaciones,
                        s.nombre AS sede_nombre,
                        s.direccion AS sede_direccion,
                        b.nombre AS barbero_nombre,
                        sv.nombre AS servicio_nombre,
                        sv.precio AS servicio_precio,
                        sv.duracion_min AS servicio_duracion
                    FROM turnos t
                    INNER JOIN sedes s ON t.sede_id = s.id
                    INNER JOIN barberos b ON t.barbero_id = b.id
                    INNER JOIN servicios sv ON t.servicio_id = sv.id
                    WHERE t.usuario_id = :usuario_id
                    AND t.estado IN ('pendiente', 'confirmado')
                    AND TIMESTAMP(t.fecha, t.hora) >= NOW()
                    ORDER BY t.fecha ASC, t.hora ASC";

    $stmtProximos = $conn->prepare($sqlProximos);
    $stmtProximos->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmtProximos->execute();
    $proximosTurnos = $stmtProximos->fetchAll(PDO::FETCH_ASSOC);

    $sqlHistorial = "SELECT
                        t.id,
                        t.fecha,
                        t.hora,
                        t.estado,
                        s.nombre AS sede_nombre,
                        b.nombre AS barbero_nombre,
                        sv.nombre AS servicio_nombre,
                        sv.precio AS servicio_precio
                    FROM turnos t
                    INNER JOIN sedes s ON t.sede_id = s.id
                    INNER JOIN barberos b ON t.barbero_id = b.id
                    INNER JOIN servicios sv ON t.servicio_id = sv.id
                    WHERE t.usuario_id = :usuario_id
                    AND (
                        t.estado IN ('cancelado', 'completado', 'ausente')
                        OR TIMESTAMP(t.fecha, t.hora) < NOW()
                    )
                    ORDER BY t.fecha DESC, t.hora DESC
                    LIMIT 20";

    $stmtHistorial = $conn->prepare($sqlHistorial);
    $stmtHistorial->bindParam(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmtHistorial->execute();
    $historialTurnos = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

    $restriccionUsuario = turnos_restriccion_usuario($conn, (int) $usuarioId);

} catch (PDOException $e) {
    $proximosTurnos = [];
    $historialTurnos = [];
    $errorDB = true;
}

function badgeEstadoUsuario($estado) {
    if ($estado === "pendiente") {
        return "admin-badge-pending";
    }

    if ($estado === "confirmado") {
        return "admin-badge-active";
    }

    if ($estado === "cancelado") {
        return "admin-badge-inactive";
    }

    if ($estado === "completado") {
        return "admin-badge-completed";
    }

    if ($estado === "ausente") {
        return "admin-badge-absent";
    }

    return "admin-badge-inactive";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Mi cuenta | Haedo Barber</title>

    <link rel="icon" type="image/png" href="../assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png?v=2">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet"
          href="../assets/css/style.css">
</head>

<body>

<main class="admin-layout">

    <aside class="admin-sidebar">
        <div>

            <div class="admin-brand">
                <img src="../assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" class="brand-logo brand-logo-admin" width="800" height="298">
            </div>

            <nav class="admin-menu">

                <a href="../index.php"
                   class="admin-menu-link">
                    <i class="bi bi-house"></i>
                    Inicio
                </a>

                <a href="panel_usuario.php"
                   class="admin-menu-link active">
                    <i class="bi bi-person-circle"></i>
                    Mi cuenta
                </a>

                <a href="reservar_turno.php"
                   class="admin-menu-link">
                    <i class="bi bi-calendar-plus"></i>
                    Reservar turno
                </a>

                <a href="../contacto.php"
                   class="admin-menu-link">
                    <i class="bi bi-envelope"></i>
                    Contacto
                </a>

            </nav>
        </div>

        <a href="../../controllers/AuthController.php?logout=1"
           class="admin-logout">
            <i class="bi bi-box-arrow-left"></i>
            Cerrar sesión
        </a>
    </aside>

    <section class="admin-main">

        <header class="admin-topbar">

            <div>
                <p class="section-eyebrow mb-1">
                    Área de cliente
                </p>

                <h1 class="admin-title">
                    Mi cuenta
                </h1>
            </div>

            <div class="admin-user-box">

                <div class="admin-user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>

                <div>
                    <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong>
                    <span><?php echo htmlspecialchars($emailUsuario); ?></span>
                </div>

            </div>

        </header>

        <?php if ($success === "turno_creado"): ?>
            <div class="alert mb-4"
                style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
                <i class="bi bi-check2-circle me-2"></i>
                Turno reservado correctamente. Cualquier consulta contactar con la barbería.
            </div>
        <?php endif; ?>

        <?php if ($success === "turno_cancelado"): ?>
            <div class="alert mb-4"
                 style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
                <i class="bi bi-check2-circle me-2"></i>
                Turno cancelado correctamente.
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="alert mb-4"
                 style="background:#2a1111;border:1px solid #6b2c2c;color:#e8a0a0;border-radius:2px">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars(turnos_mensaje_error((string) $error) ?? "No se pudo realizar la operación."); ?>
            </div>
        <?php endif; ?>

        <?php if ($restriccionUsuario === "usuario_bloqueado_ausencias"): ?>
            <div class="alert mb-4"
                 style="background:#2a1f0d;border:1px solid #8b6a1a;color:#f0ede8;border-radius:2px">
                <i class="bi bi-info-circle-fill me-2"></i>
                <?php echo htmlspecialchars(turnos_mensaje_error($restriccionUsuario)); ?>
            </div>
        <?php endif; ?>

        <section class="admin-stats-grid mb-4">

            <div class="admin-stat-card">
                <div>
                    <span>Próximos turnos</span>
                    <strong><?php echo count($proximosTurnos); ?></strong>
                </div>
                <i class="bi bi-calendar-check"></i>
            </div>

            <div class="admin-stat-card">
                <div>
                    <span>Historial</span>
                    <strong><?php echo count($historialTurnos); ?></strong>
                </div>
                <i class="bi bi-clock-history"></i>
            </div>

            <div class="admin-stat-card">
                <div>
                    <span>Estado</span>
                    <strong>Activo</strong>
                </div>
                <i class="bi bi-person-check"></i>
            </div>

        </section>

        <section class="admin-section-card mb-4">

            <div class="admin-section-header">

                <div>
                    <h3>Reservá tu próximo turno</h3>
                    <p>Elegí sede, barbero, servicio, fecha y horario disponible.</p>
                </div>

                <a href="reservar_turno.php"
                   class="btn btn-gold"
                   style="margin-top: 12px;">
                    Reservar turno
                </a>

            </div>

        </section>

        <section class="admin-section-card mb-4">

            <div class="admin-section-header">

                <div>
                    <h3>Próximos turnos</h3>
                    <p>Acá podés ver tus reservas pendientes o confirmadas.</p>
                </div>

            </div>

            <?php if ($errorDB): ?>

                <div class="admin-empty-state">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Error al cargar tus turnos.</p>
                </div>

            <?php elseif (empty($proximosTurnos)): ?>

                <div class="admin-empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No tenés próximos turnos.</p>
                </div>

            <?php else: ?>

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Sede</th>
                                <th>Barbero</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($proximosTurnos as $turno): ?>

                                <tr>
                                    <td>
                                        <?php echo date("d/m/Y", strtotime($turno["fecha"])); ?>
                                    </td>

                                    <td>
                                        <?php echo substr($turno["hora"], 0, 5); ?>
                                    </td>

                                    <td>
                                        <strong><?php echo htmlspecialchars($turno["sede_nombre"]); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($turno["sede_direccion"]); ?></small>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($turno["barbero_nombre"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($turno["servicio_nombre"]); ?>
                                        <br>
                                        <small>
                                            $<?php echo number_format($turno["servicio_precio"], 0, ",", "."); ?>
                                            ·
                                            <?php echo intval($turno["servicio_duracion"]); ?> min
                                        </small>
                                    </td>

                                    <td>
                                        <span class="<?php echo badgeEstadoUsuario($turno["estado"]); ?>">
                                            <?php echo ucfirst($turno["estado"]); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (turnos_ts($turno["fecha"], substr($turno["hora"], 0, 5)) - time() >= TURNOS_CANCEL_MIN_HORAS * 3600): ?>
                                            <button type="button"
                                                    class="btn admin-btn-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelarTurnoModal<?php echo $turno["id"]; ?>">
                                                Cancelar
                                            </button>
                                        <?php else: ?>
                                            <small>No se puede cancelar online con menos de <?php echo (int) TURNOS_CANCEL_MIN_HORAS; ?> h. Contactá a la barbería.</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <div class="modal fade"
                                    id="cancelarTurnoModal<?php echo $turno["id"]; ?>"
                                    tabindex="-1"
                                    aria-hidden="true">

                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content admin-message-modal">

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Cancelar turno
                                                </h5>

                                                <button type="button"
                                                        class="btn-close btn-close-white"
                                                        data-bs-dismiss="modal">
                                                </button>
                                            </div>

                                            <div class="modal-body text-center">
                                                <i class="bi bi-calendar-x delete-icon"></i>

                                                <h4 class="delete-title">
                                                    ¿Querés cancelar este turno?
                                                </h4>

                                                <p class="delete-text">
                                                    Esta acción va a cancelar tu reserva del
                                                    <strong><?php echo date("d/m/Y", strtotime($turno["fecha"])); ?></strong>
                                                    a las
                                                    <strong><?php echo substr($turno["hora"], 0, 5); ?></strong>.
                                                </p>
                                            </div>

                                            <div class="modal-footer justify-content-center">

                                                <button type="button"
                                                        class="btn btn-outline-gold"
                                                        data-bs-dismiss="modal">
                                                    Volver
                                                </button>

                                                <form action="../../controllers/TurnoController.php"
                                                    method="POST"><?= csrf_field() ?>

                                                    <input type="hidden"
                                                        name="accion"
                                                        value="cancelar_usuario">

                                                    <input type="hidden"
                                                        name="id"
                                                        value="<?php echo $turno["id"]; ?>">

                                                    <button type="submit"
                                                            class="btn admin-btn-danger">
                                                        Sí, cancelar
                                                    </button>

                                                </form>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                                
                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

        <section class="admin-section-card">

            <div class="admin-section-header">

                <div>
                    <h3>Historial</h3>
                    <p>Últimos turnos cancelados, completados o pasados.</p>
                </div>

            </div>

            <?php if (empty($historialTurnos)): ?>

                <div class="admin-empty-state">
                    <i class="bi bi-clock-history"></i>
                    <p>Todavía no hay historial de turnos.</p>
                </div>

            <?php else: ?>

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Sede</th>
                                <th>Barbero</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($historialTurnos as $turno): ?>

                                <tr>
                                    <td>
                                        <?php echo date("d/m/Y", strtotime($turno["fecha"])); ?>
                                    </td>

                                    <td>
                                        <?php echo substr($turno["hora"], 0, 5); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($turno["sede_nombre"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($turno["barbero_nombre"]); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($turno["servicio_nombre"]); ?>
                                        <br>
                                        <small>
                                            $<?php echo number_format($turno["servicio_precio"], 0, ",", "."); ?>
                                        </small>
                                    </td>

                                    <td>
                                        <span class="<?php echo badgeEstadoUsuario($turno["estado"]); ?>">
                                            <?php echo ucfirst($turno["estado"]); ?>
                                        </span>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/panel.js"></script>

</body>
</html>