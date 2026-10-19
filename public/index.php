<?php
require_once '../config/session_check.php';
require_once '../config/site.php';
try {
    $sqlServicios = "SELECT *
                     FROM servicios
                     WHERE activo = 1
                     ORDER BY id ASC";

    $stmtServicios = $conn->prepare($sqlServicios);
    $stmtServicios->execute();

    $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);

    $sqlSedes = "SELECT *
                 FROM sedes
                 WHERE activo = 1
                 ORDER BY id ASC";

    $stmtSedes = $conn->prepare($sqlSedes);
    $stmtSedes->execute();

    $sedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $servicios = [];
    $sedes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Haedo Barber</title>

  <link rel="icon" type="image/png" href="assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png?v=2">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- ======================== NAVBAR ======================== -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <img src="assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" class="brand-logo" width="800" height="298">
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarMenu">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button"
               data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
              Sedes
            </a>

            <ul class="dropdown-menu">

              <?php if (empty($sedes)): ?>

                <li>
                  <span class="dropdown-item" style="color:var(--muted)!important">
                    <i class="bi bi-geo-alt-fill"></i>
                    Próximamente
                  </span>
                </li>

              <?php else: ?>

                <?php foreach ($sedes as $sede): ?>
                  <li>
                    <a class="dropdown-item" href="sede.php?id=<?php echo $sede["id"]; ?>">
                      <i class="bi bi-geo-alt-fill"></i>
                      <?php echo htmlspecialchars($sede["nombre"]); ?>
                    </a>
                  </li>
                <?php endforeach; ?>

              <?php endif; ?>

            </ul>
          </li>

          <li class="nav-item"><a class="nav-link" href="contacto.php">Contacto</a></li>

          <?php if (isset($_SESSION["usuario_id"])): ?>

        <li class="nav-item dropdown ms-lg-2">
          <a class="btn btn-outline-gold btn-sm dropdown-toggle"
            href="#"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i>
            <?php echo htmlspecialchars($_SESSION["usuario_nombre"]); ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item"
                href="<?php echo $_SESSION["usuario_rol"] === "admin" ? 'admin/panel_admin.php' : 'user/panel_usuario.php'; ?>">
                <i class="bi <?php echo $_SESSION["usuario_rol"] === "admin" ? 'bi-speedometer2' : 'bi-person'; ?>"></i>
                <?php echo $_SESSION["usuario_rol"] === "admin" ? 'Panel admin' : 'Mi cuenta'; ?>
              </a>
            </li>

            <li>
              <a class="dropdown-item" href="user/reservar_turno.php">
                <i class="bi bi-calendar-plus"></i>
                Reservar turno
              </a>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
              <form action="../controllers/AuthController.php" method="POST"><?= csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="dropdown-item">
                  <i class="bi bi-box-arrow-left"></i>
                  Cerrar sesión
                </button>
              </form>
            </li>
          </ul>
        </li>

      <?php else: ?>

  <li class="nav-item ms-lg-2">
    <a class="btn btn-outline-gold btn-sm" href="login.php">Ingresar</a>
  </li>

<?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ======================== HERO ======================== -->
  <section class="hero-section" id="inicio">
    <div class="hero-slash"></div>

    <div class="container h-100 position-relative">
      <div class="row h-100 align-items-center">

        <div class="col-lg-7">
          <p class="hero-eyebrow">Buenos Aires</p>

          <h1 class="hero-title">
            EL ARTE<br>
            <span class="title-gold">DE HAEDO.</span>
          </h1>

          <p class="hero-subtitle">
            Un espacio donde las risas, la buena música y los cortes impecables se encuentran.
          </p>

          <div class="d-flex gap-3 flex-wrap mt-4">
            <a href="<?php echo isset($_SESSION["usuario_id"]) ? 'user/reservar_turno.php' : 'login.php'; ?>"
            class="btn btn-gold btn-lg">
            Reservar turno
            </a>
            <a href="#servicios" class="btn btn-outline-gold btn-lg">Ver servicios</a>
          </div>
        </div>

        <div class="col-12 col-lg-5 d-flex justify-content-center justify-content-lg-end hero-media-col">
          <div class="hero-img-frame">
            <div class="hero-img-inner">
              <img src="assets/img/hero-principal.webp"
                   alt="Interior de Haedo Barber: sillón de barbero frente a los espejos"
                   class="hero-photo" width="1179" height="1475" fetchpriority="high" decoding="async">
            </div>

            <div class="hero-img-badge">
              <i class="bi bi-scissors"></i>
              <span>Premium<br>Barbershop</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ======================== FRANJA ANUNCIO ======================== -->
  <div class="announcement-bar">
    <div class="announcement-track">
      <div class="announcement-content">
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
      </div>

      <div class="announcement-content">
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
        <span>CORTE · BARBA · AFEITADO · CEJAS · KERATINA · TINTURA · </span>
      </div>
    </div>
  </div>

  <!-- ======================== SOBRE NOSOTROS ======================== -->
  <section class="about-section py-section" id="nosotros">
    <div class="container">
      <div class="row align-items-center g-5">

        <div class="col-lg-5">
          <div class="about-img-grid">
            <img src="assets/img/quienes-personal.webp"
                 alt="Barberos atendiendo clientes dentro de Haedo Barber"
                 class="about-img-main" width="1179" height="1342" loading="lazy" decoding="async">

            <img src="assets/img/quienes-equipo.webp"
                 alt="Máquinas, cepillos y tijeras sobre el puesto de trabajo"
                 class="about-img-side" width="1179" height="1472" loading="lazy" decoding="async">

            <div class="about-img-tag">
              <i class="bi bi-award-fill"></i>
              <span>Calidad garantizada</span>
            </div>
          </div>
        </div>

        <div class="col-lg-6 offset-lg-1">
          <p class="section-eyebrow">Quiénes somos</p>

          <h2 class="section-title">
            Más que una barbería.<br>
            <span class="text-gold">Un ritual.</span>
          </h2>

          <p class="section-body">
            En <strong>Haedo Barber</strong> entendemos que un buen corte no es solo estética:
            es confianza, es carácter. Desde el 2020 trabajamos con dedicación artesanal,
            combinando técnica clásica con tendencias actuales.
          </p>

          <p class="section-body">
            Cada silla en nuestro local es un espacio de respeto por el oficio.
            Venís, te sentás, y salís siendo vos — pero mejor.
          </p>

          <div class="about-features mt-4">
            <div class="about-feature-item">
              <i class="bi bi-check2-circle text-gold fs-5"></i>
              <span>Productos de primera línea</span>
            </div>

            <div class="about-feature-item">
              <i class="bi bi-check2-circle text-gold fs-5"></i>
              <span>Turnos con horario garantizado</span>
            </div>

            <div class="about-feature-item">
              <i class="bi bi-check2-circle text-gold fs-5"></i>
              <span>Ambiente cómodo y auténtico</span>
            </div>
          </div>

          <a href="#servicios" class="btn btn-gold mt-4">Ver nuestros servicios</a>
        </div>

      </div>
    </div>
  </section>

  <!-- ======================== SERVICIOS ======================== -->
  <section class="services-section py-section" id="servicios">
    <div class="container">
      <div class="text-center mb-5">
        <p class="section-eyebrow">Lo que hacemos</p>
        <h2 class="section-title">Servicios</h2>
      </div>

      <div class="row g-4 justify-content-center">

        <?php if (empty($servicios)): ?>

          <div class="col-lg-8">
            <div class="empty-state">
              <i class="bi bi-scissors"></i>
              <p>Próximamente vamos a publicar nuestros servicios disponibles.</p>
              <a href="contacto.php" class="btn btn-outline-gold btn-sm">Consultanos</a>
            </div>
          </div>

        <?php else: ?>

          <?php foreach ($servicios as $servicio): ?>

            <div class="col-md-6 col-lg-4">
              <div class="service-card h-100">

                <div class="service-icon">
                  <i class="bi bi-scissors"></i>
                </div>

                <h3 class="service-title">
                  <?php echo htmlspecialchars($servicio["nombre"]); ?>
                </h3>

                <p class="service-desc">
                  <?php echo htmlspecialchars($servicio["descripcion"] ?? ""); ?>
                </p>

                <div class="service-footer">
                  <span class="service-price">
                    <?php if ((float)$servicio["precio"] == 0): ?>
                      GRATIS
                    <?php else: ?>
                      $<?php echo number_format($servicio["precio"], 0, ",", "."); ?>
                    <?php endif; ?>
                  </span>

                  <a href="<?php echo isset($_SESSION["usuario_id"]) ? 'user/reservar_turno.php' : 'login.php'; ?>"
                    class="btn-service-link">
                    Reservar <i class="bi bi-arrow-right"></i>
                  </a>
                </div>

              </div>
            </div>

          <?php endforeach; ?>

        <?php endif; ?>

      </div>
    </div>
  </section>

  <!-- ======================== CTA SEDES ======================== -->
  <section class="cta-section">
    <div class="cta-bg"></div>

    <div class="container position-relative">
      <div class="row justify-content-center text-center">
        <div class="col-lg-7">
          <p class="section-eyebrow">
            <?php echo count($sedes) === 1 ? "Una ubicación" : "Nuestras ubicaciones"; ?>
          </p>

          <h2 class="cta-title">
            Encontranos cerca<br>
            <span class="text-gold">de vos.</span>
          </h2>

          <p class="cta-body">
            Elegí la sede que te quede más cómoda y reservá tu turno online.
          </p>

          <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">

            <?php if (empty($sedes)): ?>

              <a href="contacto.php" class="btn btn-gold btn-lg">
                Consultar ubicación
              </a>

            <?php else: ?>

              <?php foreach ($sedes as $index => $sede): ?>

                <a href="sede.php?id=<?php echo $sede["id"]; ?>"
                   class="btn <?php echo $index === 0 ? 'btn-gold' : 'btn-outline-gold'; ?> btn-lg">
                  <?php echo htmlspecialchars($sede["nombre"]); ?>
                </a>

              <?php endforeach; ?>

            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======================== FOOTER ======================== -->
<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
