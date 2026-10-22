<?php
require_once '../config/session_check.php';
require_once '../config/site.php';
require_once '../config/turnos_helper.php';

// Contacto por WhatsApp: helpers locales (sin formulario ni email).
function contacto_wa(string $texto): string
{
    $base = sitio_whatsapp_url();
    if ($base === '') {
        return '';
    }
    $base = preg_replace('/[?#].*$/', '', $base);
    return $base . '?text=' . rawurlencode($texto);
}

$direccion = trim((string) preg_replace('/,?\s*Provincia de Buenos Aires\s*$/iu', '', sitio_direccion()));
$direccionUrl = $direccion === '' ? '' : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($direccion);
$waPrincipal = contacto_wa('Hola Haedo Barber, quería consultar por un turno');
$waChips = [
    ['Consultar por un turno', 'bi-calendar-check', 'Hola Haedo Barber, quería consultar por un turno'],
    ['Consultar por un servicio', 'bi-scissors', 'Hola Haedo Barber, quería consultar por un servicio'],
    ['Consultar precios', 'bi-tag', 'Hola Haedo Barber, quería consultar los precios'],
];
$sedes = [];
try {
    $sqlSedes = "SELECT *
                 FROM sedes
                 WHERE activo = 1
                 ORDER BY id ASC";

    $stmtSedes = $conn->prepare($sqlSedes);
    $stmtSedes->execute();
    $sedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $sedes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <title>Contacto | Haedo Barber</title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png?v=2">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top scrolled" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" class="brand-logo" width="800" height="298">
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

        <li class="nav-item">
          <a class="nav-link" href="index.php">Inicio</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="index.php#servicios">Servicios</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button"
             data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
            Sedes
          </a>

          <ul class="dropdown-menu">
            <?php if (empty($sedes)) : ?>
              <li>
                <span class="dropdown-item" style="color:var(--muted)!important">
                  <i class="bi bi-geo-alt-fill"></i>
                  Próximamente
                </span>
              </li>
            <?php else : ?>
                <?php foreach ($sedes as $sede) : ?>
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

        <li class="nav-item">
          <a class="nav-link active" href="contacto.php" style="color:var(--gold)!important">Contacto</a>
        </li>

        <?php if (isset($_SESSION["usuario_id"])) : ?>

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
                  href="<?php echo $_SESSION["usuario_rol"] === "admin"
                      ? 'admin/panel_admin.php'
                      : 'user/panel_usuario.php'; ?>">

                  <i class="bi <?php echo $_SESSION["usuario_rol"] === "admin"
                      ? 'bi-speedometer2'
                      : 'bi-person'; ?>"></i>

                  <?php echo $_SESSION["usuario_rol"] === "admin"
                      ? 'Panel admin'
                      : 'Mi cuenta'; ?>

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

          <li class="nav-item ms-lg-1">
            <a class="btn btn-gold btn-sm" href="login.php">Reservar turno</a>
          </li>

        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<header class="hb-c-hero">
  <div class="container">
    <p class="section-eyebrow">Hablemos</p>
    <h1 class="section-title">Contacto</h1>
    <p>Escribinos por WhatsApp y te respondemos a la brevedad.</p>
  </div>
</header>

<main class="hb-c-main">
  <div class="container hb-c-wrap">

    <?php if ($waPrincipal !== ''): ?>
    <section class="hb-c-cta" aria-label="Contacto por WhatsApp">
      <a class="btn btn-gold btn-lg" href="<?php echo sitio_h($waPrincipal); ?>" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-whatsapp" aria-hidden="true"></i> Escribinos por WhatsApp
      </a>
      <?php if (sitio_telefono() !== ''): ?>
        <p class="hb-c-note"><?php echo sitio_h(sitio_telefono()); ?></p>
      <?php endif; ?>
      <div class="hb-c-chips">
        <?php foreach ($waChips as $chip): ?>
          <a class="hb-c-chip" href="<?php echo sitio_h(contacto_wa($chip[2])); ?>" target="_blank" rel="noopener noreferrer">
            <i class="bi <?php echo sitio_h($chip[1]); ?> me-2" aria-hidden="true"></i><?php echo sitio_h($chip[0]); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <section aria-labelledby="encontranos">
      <h2 class="hb-c-title" id="encontranos">ENCONTRANOS</h2>
      <div class="hb-c-grid">

        <ul class="hb-c-list">
          <?php if ($direccion !== ''): ?>
          <li>
            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
            <div>
              <span class="hb-c-label">Dirección</span>
              <a class="hb-c-val" href="<?php echo sitio_h($direccionUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h($direccion); ?></a>
            </div>
          </li>
          <?php endif; ?>

          <li>
            <i class="bi bi-clock-fill" aria-hidden="true"></i>
            <div>
              <span class="hb-c-label">Horario</span>
              <span class="hb-c-val"><?php echo sitio_h(turnos_horario_texto()); ?></span>
            </div>
          </li>

          <?php if (sitio_instagram_url() !== ''): ?>
          <li>
            <i class="bi bi-instagram" aria-hidden="true"></i>
            <div>
              <span class="hb-c-label">Instagram</span>
              <a class="hb-c-val" href="<?php echo sitio_h(sitio_instagram_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h(sitio_instagram_handle() ?: 'Instagram'); ?></a>
            </div>
          </li>
          <?php endif; ?>

          <?php if (sitio_tiktok_url() !== ''): ?>
          <li>
            <i class="bi bi-tiktok" aria-hidden="true"></i>
            <div>
              <span class="hb-c-label">TikTok</span>
              <a class="hb-c-val" href="<?php echo sitio_h(sitio_tiktok_url()); ?>" target="_blank" rel="noopener noreferrer">@haedobarber</a>
            </div>
          </li>
          <?php endif; ?>
        </ul>

        <?php if (sitio_mapa_url() !== ''): ?>
        <div class="hb-c-map">
          <iframe
            src="<?php echo sitio_h(sitio_mapa_url()); ?>"
            width="600" height="450"
            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            title="Mapa con la ubicación de Haedo Barber en Las Bases 199, Haedo"></iframe>
        </div>
        <?php endif; ?>

      </div>
    </section>

  </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/main.js"></script>
</body>
</html>
