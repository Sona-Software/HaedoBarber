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

// Los IDs se normalizan a enteros positivos (cualquier otro valor se descarta).
$sedeSeleccionada = (string) (turnos_id((string) ($_GET["sede_id"] ?? "")) ?? "");
$barberoSeleccionado = (string) (turnos_id((string) ($_GET["barbero_id"] ?? "")) ?? "");
$servicioSeleccionado = (string) (turnos_id((string) ($_GET["servicio_id"] ?? "")) ?? "");
$fechaSeleccionada = (string) ($_GET["fecha"] ?? "");
if (!turnos_fecha_valida($fechaSeleccionada)) {
    $fechaSeleccionada = "";
}

$horariosDisponibles = [];
$errorDia = null;
$restriccionUsuario = null;
$errorDB = false;
$ahora = time();

try {
    $stmtSedes = $conn->prepare("SELECT id, nombre, direccion FROM sedes WHERE activo = 1 ORDER BY nombre ASC");
    $stmtSedes->execute();
    $sedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);

    $stmtServicios = $conn->prepare("SELECT id, nombre, precio, duracion_min FROM servicios WHERE activo = 1 ORDER BY nombre ASC");
    $stmtServicios->execute();
    $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);

    $barberos = [];

    if ($sedeSeleccionada !== "") {
        $stmtBarberos = $conn->prepare("SELECT id, nombre, especialidad FROM barberos WHERE activo = 1 AND sede_id = :sede_id ORDER BY nombre ASC");
        $stmtBarberos->bindParam(":sede_id", $sedeSeleccionada, PDO::PARAM_INT);
        $stmtBarberos->execute();
        $barberos = $stmtBarberos->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($sedeSeleccionada !== "" && $barberoSeleccionado !== "" && $servicioSeleccionado !== "" && $fechaSeleccionada !== "") {
        $errorDia = turnos_validar_dia($fechaSeleccionada, $ahora);

        if ($errorDia === null) {
            // El barbero debe pertenecer a la sede elegida y estar activo.
            $stmtBarberoOk = $conn->prepare("SELECT COUNT(*) FROM barberos WHERE id = :b AND sede_id = :s AND activo = 1");
            $stmtBarberoOk->execute([":b" => $barberoSeleccionado, ":s" => $sedeSeleccionada]);

            $stmtServicio = $conn->prepare("SELECT duracion_min FROM servicios WHERE id = :id AND activo = 1");
            $stmtServicio->bindParam(":id", $servicioSeleccionado, PDO::PARAM_INT);
            $stmtServicio->execute();
            $servicioActual = $stmtServicio->fetch(PDO::FETCH_ASSOC);

            if ($servicioActual && (int) $stmtBarberoOk->fetchColumn() === 1) {
                // Misma lógica (turnos_validar_horario) que usa el controller al crear el turno.
                $horariosDisponibles = turnos_slots_disponibles(
                    $fechaSeleccionada,
                    intval($servicioActual["duracion_min"]),
                    turnos_ocupados_barbero($conn, (int) $barberoSeleccionado, $fechaSeleccionada),
                    $ahora
                );
            }
        }
    }

    $restriccionUsuario = turnos_restriccion_usuario($conn, (int) $usuarioId);

} catch (PDOException $e) {
    $sedes = [];
    $servicios = [];
    $barberos = [];
    $errorDB = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Reservar turno | Haedo Barber</title>

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
                <a href="../index.php" class="admin-menu-link">
                    <i class="bi bi-house"></i>
                    Inicio
                </a>

                <a href="panel_usuario.php" class="admin-menu-link">
                    <i class="bi bi-person-circle"></i>
                    Mi cuenta
                </a>

                <a href="reservar_turno.php" class="admin-menu-link active">
                    <i class="bi bi-calendar-plus"></i>
                    Reservar turno
                </a>

                <a href="../contacto.php" class="admin-menu-link">
                    <i class="bi bi-envelope"></i>
                    Contacto
                </a>
            </nav>
        </div>

        <form action="../../controllers/AuthController.php" method="POST" style="display:contents"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button type="submit" class="admin-logout">
            <i class="bi bi-box-arrow-left"></i>
            Cerrar sesión
        </button></form>
    </aside>

    <section class="admin-main">

        <header class="admin-topbar">
            <div>
                <p class="section-eyebrow mb-1">Área de cliente</p>
                <h1 class="admin-title">Reservar turno</h1>
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

        <section class="admin-section-card mb-4">

            <div class="admin-section-header">
                <div>
                    <h3>Elegí tu turno</h3>
                    <p>Seleccioná sede, barbero, servicio y fecha para ver horarios disponibles.</p>
                </div>
            </div>

            <?php if ($errorDB): ?>

                <div class="admin-empty-state">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Error al cargar la reserva.</p>
                </div>

            <?php else: ?>

                <?php
                // Solo se muestran mensajes de una lista conocida (nunca texto arbitrario de la URL).
                $mensajeError = turnos_mensaje_error((string) ($_GET["error"] ?? ""));
                ?>

                <?php if ($mensajeError !== null): ?>
                    <div class="alert mb-4"
                        style="background:#2a1111;border:1px solid #8b1a1a;color:#f0ede8;border-radius:2px">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($mensajeError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($restriccionUsuario !== null && $mensajeError === null): ?>
                    <div class="alert mb-4"
                        style="background:#2a1f0d;border:1px solid #8b6a1a;color:#f0ede8;border-radius:2px">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <?php echo htmlspecialchars(turnos_mensaje_error($restriccionUsuario) ?? ""); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($sedes) || empty($servicios)): ?>
                    <div class="alert alert-info">Todavía no hay sedes o servicios cargados. Volvé a intentar más tarde.</div>
                <?php endif; ?>

                <form method="GET" class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Sede</label>
                        <select name="sede_id"
                                class="form-control"
                                required
                                onchange="this.form.submit()">
                            <option value="">Seleccionar sede</option>

                            <?php foreach ($sedes as $sede): ?>
                                <option value="<?php echo $sede["id"]; ?>"
                                    <?php echo $sedeSeleccionada == $sede["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($sede["nombre"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Barbero</label>
                        <select name="barbero_id"
                                class="form-control"
                                required
                                <?php echo $sedeSeleccionada === "" ? "disabled" : ""; ?>>
                            <option value="">
                                <?php echo $sedeSeleccionada === "" ? "Primero elegí una sede" : "Seleccionar barbero"; ?>
                            </option>

                            <?php foreach ($barberos as $barbero): ?>
                                <option value="<?php echo $barbero["id"]; ?>"
                                    <?php echo $barberoSeleccionado == $barbero["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($barbero["nombre"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Servicio</label>
                        <select name="servicio_id"
                                class="form-control"
                                required>
                            <option value="">Seleccionar servicio</option>

                            <?php foreach ($servicios as $servicio): ?>
                                <option value="<?php echo $servicio["id"]; ?>"
                                    <?php echo $servicioSeleccionado == $servicio["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($servicio["nombre"]); ?>
                                    - $<?php echo number_format($servicio["precio"], 0, ",", "."); ?>
                                    - <?php echo intval($servicio["duracion_min"]); ?> min
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="date"
                            name="fecha"
                            class="form-control"
                            min="<?php echo date("Y-m-d"); ?>"
                            max="<?php echo date("Y-m-d", $ahora + TURNOS_ANTICIPACION_MAX_DIAS * 86400); ?>"
                            value="<?php echo htmlspecialchars($fechaSeleccionada); ?>"
                            required>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-gold">
                            Ver horarios disponibles
                        </button>

                        <a href="reservar_turno.php" class="btn btn-outline-gold">
                            Limpiar
                        </a>
                    </div>

                </form>

            <?php endif; ?>

        </section>

        <?php if ($sedeSeleccionada !== "" && $barberoSeleccionado !== "" && $servicioSeleccionado !== "" && $fechaSeleccionada !== ""): ?>

            <section class="admin-section-card">

                <div class="admin-section-header">
                    <div>
                        <h3>Horarios disponibles</h3>
                        <p>Solo se muestran horarios libres para el barbero y servicio seleccionado.</p>
                    </div>
                </div>

                <?php if ($restriccionUsuario !== null): ?>

                    <div class="admin-empty-state">
                        <i class="bi bi-hourglass-split"></i>
                        <p><?php echo htmlspecialchars(turnos_mensaje_error($restriccionUsuario) ?? ""); ?></p>
                    </div>

                <?php elseif ($errorDia !== null): ?>

                    <div class="admin-empty-state">
                        <i class="bi <?php echo $errorDia === "dia_cerrado" ? "bi-door-closed" : "bi-calendar-x"; ?>"></i>
                        <p><?php echo htmlspecialchars(turnos_mensaje_error($errorDia) ?? "La fecha elegida no es válida."); ?></p>
                    </div>

                <?php elseif (empty($horariosDisponibles)): ?>

                    <div class="admin-empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <p>No hay horarios disponibles para esa fecha.</p>
                    </div>

                <?php else: ?>

                    <form action="../../controllers/TurnoController.php"
                          method="POST"><?= csrf_field() ?>

                        <input type="hidden" name="accion" value="crear">
                        <input type="hidden" name="sede_id" value="<?php echo htmlspecialchars($sedeSeleccionada); ?>">
                        <input type="hidden" name="barbero_id" value="<?php echo htmlspecialchars($barberoSeleccionado); ?>">
                        <input type="hidden" name="servicio_id" value="<?php echo htmlspecialchars($servicioSeleccionado); ?>">
                        <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fechaSeleccionada); ?>">

                        <div class="row g-3 mb-4">

                            <?php foreach ($horariosDisponibles as $hora): ?>

                                <div class="col-6 col-md-3 col-lg-2">
                                    <input type="radio"
                                           class="btn-check"
                                           name="hora"
                                           id="hora<?php echo str_replace(":", "", $hora); ?>"
                                           value="<?php echo $hora; ?>"
                                           required>

                                    <label class="btn btn-outline-gold w-100"
                                           for="hora<?php echo str_replace(":", "", $hora); ?>">
                                        <?php echo $hora; ?>
                                    </label>
                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="mb-4">
                            <label class="form-label">Observaciones</label>

                            <textarea name="observaciones"
                                      class="form-control"
                                      maxlength="<?php echo (int) TURNOS_OBS_MAX; ?>"
                                      rows="3"
                                      placeholder="Ej: prefiero corte bajo, llegaré 5 minutos antes, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-gold">
                            Confirmar reserva
                        </button>

                    </form>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/panel.js"></script>

</body>
</html>
