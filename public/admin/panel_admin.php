<?php
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION["usuario_rol"] ?? "") !== "admin") {
    header("Location: ../login.php");
    exit;
}

$nombreAdmin = $_SESSION["usuario_nombre"] ?? "";
$emailAdmin = $_SESSION["usuario_email"] ?? "";

require_once '../../config/database.php';

$totalMensajes = 0;
$totalServicios = 0;
$totalUsuarios = 0;
$totalTurnos = 0;

try {
    $sqlMensajes = "SELECT COUNT(*) AS total FROM contactos";
    $stmtMensajes = $conn->prepare($sqlMensajes);
    $stmtMensajes->execute();
    $resultadoMensajes = $stmtMensajes->fetch(PDO::FETCH_ASSOC);
    $totalMensajes = $resultadoMensajes["total"] ?? 0;

    $sqlServicios = "SELECT COUNT(*) AS total FROM servicios WHERE activo = 1";
    $stmtServicios = $conn->prepare($sqlServicios);
    $stmtServicios->execute();
    $resultadoServicios = $stmtServicios->fetch(PDO::FETCH_ASSOC);
    $totalServicios = $resultadoServicios["total"] ?? 0;

    $sqlUsuarios = "SELECT COUNT(*) AS total FROM usuarios";
    $stmtUsuarios = $conn->prepare($sqlUsuarios);
    $stmtUsuarios->execute();
    $resultadoUsuarios = $stmtUsuarios->fetch(PDO::FETCH_ASSOC);
    $totalUsuarios = $resultadoUsuarios["total"] ?? 0;

    $sqlTurnos = "SELECT COUNT(*) AS total FROM turnos";
    $stmtTurnos = $conn->prepare($sqlTurnos);
    $stmtTurnos->execute();
    $resultadoTurnos = $stmtTurnos->fetch(PDO::FETCH_ASSOC);
    $totalTurnos = $resultadoTurnos["total"] ?? 0;

} catch (PDOException $e) {
    $totalMensajes = 0;
    $totalServicios = 0;
    $totalUsuarios = 0;
    $totalTurnos = 0;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin | Haedo Barber</title>

  <link rel="icon" type="image/png" href="../assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png?v=2">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style.css?v=3">
</head>

<body>

  <main class="admin-layout">

    <aside class="admin-sidebar">
      <div>
        <div class="admin-brand">
          <img src="../assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" class="brand-logo brand-logo-admin" width="800" height="298">
        </div>

    <nav class="admin-menu">

        <a href="panel_admin.php" class="admin-menu-link active">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>

        <a href="turnos.php" class="admin-menu-link">
          <i class="bi bi-calendar-check"></i>
          Turnos
        </a>

        <a href="mensajes.php" class="admin-menu-link">
          <i class="bi bi-envelope"></i>
          Mensajes
        </a>

        <a href="servicios.php" class="admin-menu-link">
          <i class="bi bi-scissors"></i>
          Servicios
        </a>

        <a href="barberos.php" class="admin-menu-link">
          <i class="bi bi-person-badge"></i>
          Barberos
        </a>

        <a href="sedes.php" class="admin-menu-link">
          <i class="bi bi-geo-alt"></i>
          Sedes
        </a>

        <a href="usuarios.php" class="admin-menu-link">
          <i class="bi bi-people"></i>
          Usuarios
        </a>
      </nav>
  </div>

      <form action="../../controllers/AuthController.php"
      method="POST"><?= csrf_field() ?>

        <input type="hidden"
              name="action"
              value="logout">

        <button type="submit"
                class="admin-logout">
          <i class="bi bi-box-arrow-left"></i>
          Cerrar sesión
        </button>

      </form>
    </aside>

    <section class="admin-main">

      <header class="admin-topbar">
        <div>
          <p class="section-eyebrow mb-1">Panel administrativo</p>
          <h1 class="admin-title">Dashboard</h1>
        </div>

        <div class="admin-user-box">
          <div class="admin-user-avatar">
            <i class="bi bi-person-fill"></i>
          </div>
          <div>
            <strong><?php echo htmlspecialchars($nombreAdmin); ?></strong>
            <span><?php echo htmlspecialchars($emailAdmin); ?></span>
          </div>
        </div>
      </header>

      <section class="admin-welcome-card admin-welcome-card-visual">
        <div class="admin-welcome-content">
          <h2>Bienvenido, <?php echo htmlspecialchars($nombreAdmin); ?>.</h2>
          <p>
            Desde este panel vas a poder administrar turnos, mensajes,
            servicios, barberos y usuarios del sistema.
          </p>

          <a href="../index.php" class="btn btn-outline-gold btn-sm">
            Ver sitio
          </a>
        </div>

        <div class="admin-welcome-image admin-welcome-logo" aria-hidden="true">
          <img src="../assets/img/logo-haedobarber.png?v=2" alt="" class="brand-logo" width="800" height="298">
        </div>
      </section>

      <section class="admin-stats-grid">

        <article class="admin-stat-card">
          <i class="bi bi-calendar-check"></i>
          <span>Turnos</span>
          <strong><?php echo intval($totalTurnos); ?></strong>
          <small>Turnos registrados</small>
        </article>

        <article class="admin-stat-card">
          <i class="bi bi-envelope"></i>
          <span>Mensajes</span>
          <strong><?php echo $totalMensajes; ?></strong>
          <small>Consultas recibidas</small>
        </article>

        <article class="admin-stat-card">
          <i class="bi bi-people"></i>
          <span>Usuarios</span>
          <strong><?php echo $totalUsuarios; ?></strong>
          <small>Clientes registrados</small>
        </article>

        <article class="admin-stat-card">
          <i class="bi bi-scissors"></i>
          <span>Servicios</span>
          <strong><?php echo $totalServicios; ?></strong>
          <small>Servicios activos</small>
        </article>

      </section>

      <section class="admin-section-card">
        <div class="admin-section-header">
            <div>
            <h3>Accesos rápidos</h3>
            <p>Gestioná las áreas principales del sistema administrativo.</p>
            </div>
        </div>

        <div class="admin-feature-list">

            <a href="mensajes.php" class="admin-feature-item">
            <i class="bi bi-envelope"></i>
            <div>
                <strong>Mensajes de contacto</strong>
                <span>Revisar consultas enviadas desde el formulario web.</span>
            </div>
            </a>

            <a href="turnos.php" class="admin-feature-item">
            <i class="bi bi-calendar-check"></i>
            <div>
                <strong>Gestión de turnos</strong>
                <span>Ver, confirmar, modificar o cancelar reservas.</span>
            </div>
            </a>

            <a href="servicios.php" class="admin-feature-item">
            <i class="bi bi-scissors"></i>
            <div>
                <strong>Servicios y precios</strong>
                <span>Administrar cortes, barba, duración y valores.</span>
            </div>
            </a>

            <a href="barberos.php" class="admin-feature-item">
            <i class="bi bi-person-badge"></i>
            <div>
                <strong>Barberos</strong>
                <span>Gestionar barberos, especialidades y sedes asignadas.</span>
            </div>
            </a>

        </div>
        </section>
    </section>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/panel.js"></script>
</body>
</html>