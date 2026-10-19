<?php
require_once '../config/session_check.php';
require_once '../config/site.php';
require_once '../config/turnos_helper.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
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

<div class="page-hero">
  <div class="container">
    <p class="section-eyebrow">Hablemos</p>
    <h1 class="section-title">Contacto</h1>
    <p>¿Tenés una pregunta? Mandanos un mensaje y te respondemos pronto.</p>
  </div>
</div>

<section class="py-section section-plain">
  <div class="container">
    <div class="row g-5">

      <div class="col-lg-7">

        <?php if ($success === '1'): ?>
          <div class="alert fade show mb-4 alert-hb alert-hb-success" role="alert"
            >
            <i class="bi bi-check2-circle me-2"></i>
            Mensaje enviado correctamente. Te contactaremos a la brevedad.
          </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-dismissible fade show mb-4 alert-hb alert-hb-error"
              role="alert"
              >

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?php
              if ($error === 'campos_vacios') {
                echo 'Completá todos los campos obligatorios.';
              } elseif ($error === 'email_invalido') {
                echo 'Ingresá un email válido.';
              } elseif ($error === 'demasiados_intentos') {
                echo 'Enviaste muchos mensajes seguidos. Probá de nuevo más tarde.';
              } elseif ($error === 'db_error') {
                echo 'Hubo un error al guardar el mensaje.';
              } else {
                echo 'Ocurrió un error al enviar el formulario.';
              }
            ?>

            <button type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="alert">
            </button>
          </div>
        <?php endif; ?>

        <form action="../controllers/ContactController.php" method="POST" id="contactForm" class="contact-form" novalidate><?= csrf_field() ?>
          <div class="row g-3">

            <div class="col-sm-6">
              <label for="nombre" class="form-label">Nombre completo</label>
              <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Juan Pérez" required maxlength="120">
              <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá tu nombre.</div>
            </div>

            <div class="col-sm-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="juan@ejemplo.com" required maxlength="120">
              <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá un email válido.</div>
            </div>

            <div class="col-12">
              <label for="asunto" class="form-label">Asunto</label>
              <input type="text" class="form-control" id="asunto" name="asunto" placeholder="¿Sobre qué nos escribís?" maxlength="150">
              <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label>No completar este campo <input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
            </div>

            <div class="col-12">
              <label for="mensaje" class="form-label">Mensaje</label>
              <textarea class="form-control" id="mensaje" name="mensaje" rows="6" maxlength="500"
                placeholder="Escribí tu consulta acá..." required></textarea>

              <div class="d-flex justify-content-between mt-1">
                <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá tu mensaje.</div>
                <span id="char-count" style="font-size:11px;color:var(--muted);text-align:right;white-space:nowrap;flex-shrink:0;margin-left:12px">0 / 500</span>
              </div>
            </div>

            <div class="col-12 mt-2">
              <button type="submit" class="btn btn-gold py-3 px-5">
                Enviar mensaje <i class="bi bi-send ms-2"></i>
              </button>
            </div>

          </div>
        </form>
      </div>

      <div class="col-lg-5">
        <div class="contact-card">
          <h3 style="font-family:var(--font-display);font-size:24px;color:var(--cream);letter-spacing:1px;margin-bottom:24px">ENCONTRANOS</h3>

          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:18px">
            <?php if (sitio_direccion() !== ''): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-geo-alt-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Dirección</p>
                <a class="hb-contact-link" href="<?php echo sitio_h(sitio_direccion_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h(sitio_direccion()); ?></a>
              </div>
            </li>
            <?php endif; ?>

            <?php if (!empty($sedes)): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-shop" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Sedes</p>
                <p style="color:var(--muted);font-size:13px;margin:0">
                  <?php foreach ($sedes as $sede): ?>
                    <a href="sede.php?id=<?php echo $sede["id"]; ?>" style="color:var(--gold);text-decoration:none">
                      <?php echo htmlspecialchars($sede["nombre"]); ?>
                    </a>
                    — <?php echo htmlspecialchars($sede["direccion"]); ?><br>
                  <?php endforeach; ?>
                </p>
              </div>
            </li>
            <?php endif; ?>

            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-clock-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Horarios</p>
                <p style="color:var(--muted);font-size:13px;margin:0"><?php echo htmlspecialchars(turnos_horario_texto()); ?><br>Domingos: cerrado</p>
              </div>
            </li>

            <?php if (sitio_whatsapp_url() !== ''): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-whatsapp" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">WhatsApp</p>
                <a class="hb-contact-link" href="<?php echo sitio_h(sitio_whatsapp_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h(sitio_telefono() ?: 'WhatsApp'); ?></a>
              </div>
            </li>
            <?php elseif (sitio_telefono() !== ''): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-telephone-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Teléfono</p>
                <a class="hb-contact-link" href="tel:<?php echo sitio_h(sitio_telefono_tel()); ?>"><?php echo sitio_h(sitio_telefono()); ?></a>
              </div>
            </li>
            <?php endif; ?>

            <?php if (sitio_instagram_url() !== ''): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-instagram" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Instagram</p>
                <a class="hb-contact-link" href="<?php echo sitio_h(sitio_instagram_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h(sitio_instagram_handle() ?: 'Instagram'); ?></a>
              </div>
            </li>
            <?php endif; ?>

            <?php if (sitio_tiktok_url() !== ''): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-tiktok" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">TikTok</p>
                <a class="hb-contact-link" href="<?php echo sitio_h(sitio_tiktok_url()); ?>" target="_blank" rel="noopener noreferrer">@haedobarber</a>
              </div>
            </li>
            <?php endif; ?>

            <?php if (sitio_email() !== ''): ?>
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-envelope-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0" aria-hidden="true"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Email</p>
                <a class="hb-contact-link" href="mailto:<?php echo sitio_h(sitio_email()); ?>"><?php echo sitio_h(sitio_email()); ?></a>
              </div>
            </li>
            <?php endif; ?>
          </ul>

          <?php if (sitio_whatsapp_url() !== ''): ?>
          <a class="hb-whatsapp-btn" href="<?php echo sitio_h(sitio_whatsapp_url()); ?>" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-whatsapp" aria-hidden="true"></i> Escribinos por WhatsApp
          </a>
          <?php endif; ?>
        </div>

        <?php if (sitio_mapa_url() !== ''): ?><div class="hb-map">
          <iframe
            src="<?php echo sitio_h(sitio_mapa_url()); ?>"
            width="600" height="320"
            style="border:0"
            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            title="Mapa con la ubicación de Haedo Barber en Las Bases 199, Haedo">
          </iframe>
        </div><?php endif; ?>
      </div>

    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const textarea = document.getElementById('mensaje');
  const counter = document.getElementById('char-count');

  if (textarea && counter) {
    textarea.addEventListener('input', () => {
      const len = textarea.value.length;
      counter.textContent = `${len} / 500`;
      counter.style.color = len > 450 ? 'var(--gold)' : 'var(--muted)';
      textarea.maxLength = 500;
    });
  }

  const form = document.getElementById('contactForm');

  if (form) {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }

      form.classList.add('was-validated');
    });
  }
</script>

<script src="assets/js/main.js"></script>
</body>
</html>
