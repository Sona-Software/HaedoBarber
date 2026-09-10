<?php
require_once '../config/session_check.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$sedes = [];
// Hola 
// completo
// Comentario
// Como estas
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
  <title>Contacto | Blest Barber Shop</title>
  <link rel="icon" type="image/jpg" href="../img/logo.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top scrolled" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span class="brand-el">BLEST</span><span class="brand-filo"> BARBER</span>
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
                <form action="../controllers/AuthController.php" method="POST">
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

<div style="background:var(--bg2);padding:130px 0 60px;border-bottom:1px solid var(--border)">
  <div class="container">
    <p class="section-eyebrow">Hablemos</p>
    <h1 class="section-title">Contacto</h1>
    <p style="color:var(--muted);font-size:14px">¿Tenés una pregunta? Mandanos un mensaje y te respondemos pronto.</p>
  </div>
</div>

<section class="py-section" style="background:var(--bg)">
  <div class="container">
    <div class="row g-5">

      <div class="col-lg-7">

        <?php if ($success === '1'): ?>
          <div class="alert fade show mb-4" role="alert"
            style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px;padding:16px 20px">
            <i class="bi bi-check2-circle me-2"></i>
            Mensaje enviado correctamente. Te contactaremos a la brevedad.
          </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="alert alert-dismissible fade show mb-4"
              role="alert"
              style="background:#2a1111;border:1px solid #8b1a1a;color:#f0ede8;border-radius:2px">

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?php
              if ($error === 'campos_vacios') {
                echo 'Completá todos los campos obligatorios.';
              } elseif ($error === 'email_invalido') {
                echo 'Ingresá un email válido.';
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

        <form action="../controllers/ContactController.php" method="POST" id="contactForm" novalidate>
          <div class="row g-3">

            <div class="col-sm-6">
              <label for="nombre" class="form-label">Nombre completo</label>
              <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Juan Pérez" required>
              <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá tu nombre.</div>
            </div>

            <div class="col-sm-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="juan@ejemplo.com" required>
              <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá un email válido.</div>
            </div>

            <div class="col-12">
              <label for="asunto" class="form-label">Asunto</label>
              <input type="text" class="form-control" id="asunto" name="asunto" placeholder="¿Sobre qué nos escribís?">
            </div>

            <div class="col-12">
              <label for="mensaje" class="form-label">Mensaje</label>
              <textarea class="form-control" id="mensaje" name="mensaje" rows="6"
                placeholder="Escribí tu consulta acá..." required></textarea>

              <div class="d-flex justify-content-between mt-1">
                <div class="invalid-feedback" style="font-size:11px;color:#e05555;display:block">Ingresá tu mensaje.</div>
                <span id="char-count" style="font-size:11px;color:var(--muted);text-align:right">0 / 500</span>
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
        <div style="background:var(--bg3);border:1px solid var(--border);padding:32px;margin-bottom:24px">
          <h3 style="font-family:var(--font-display);font-size:24px;color:var(--cream);letter-spacing:1px;margin-bottom:24px">ENCONTRANOS</h3>

          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:18px">
            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-geo-alt-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Sedes</p>
                <p style="color:var(--muted);font-size:13px;margin:0">
                  <?php if (empty($sedes)): ?>
                    Próximamente nuevas ubicaciones.
                  <?php else: ?>
                    <?php foreach ($sedes as $sede): ?>
                      <a href="sede.php?id=<?php echo $sede["id"]; ?>" style="color:var(--gold);text-decoration:none">
                        <?php echo htmlspecialchars($sede["nombre"]); ?>
                      </a>
                      — <?php echo htmlspecialchars($sede["direccion"]); ?><br>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </p>
              </div>
            </li>

            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-clock-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Horarios</p>
                <p style="color:var(--muted);font-size:13px;margin:0">Martes a Sábado: 10:30–13:00 / 14:30–20:00<br>Domingos y Lunes: cerrado</p>
              </div>
            </li>

            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-telephone-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Teléfono</p>
                <p style="color:var(--muted);font-size:13px;margin:0">+54 9 11 5126-7271</p>
              </div>
            </li>

            <li style="display:flex;gap:14px;align-items:flex-start">
              <i class="bi bi-envelope-fill" style="color:var(--gold);font-size:18px;margin-top:2px;flex-shrink:0"></i>
              <div>
                <p style="color:var(--cream);font-size:14px;margin:0;font-weight:600">Email</p>
                <p style="color:var(--muted);font-size:13px;margin:0">Juancruzargentina05@gmail.com</p>
              </div>
            </li>
          </ul>
        </div>

        <div style="border:1px solid var(--border);overflow:hidden">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6563.950157699876!2d-58.5205734!3d-34.6553323!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bcc9d73f385841%3A0x61822e73afb4fcd4!2sBlest%20Barber%20Shop%20Na%C3%B3n!5e0!3m2!1ses-419!2sar!4v1781283831807!5m2!1ses-419!2sar"
            width="100%" height="280"
            style="border:0;display:block;filter:grayscale(80%) contrast(1.1) brightness(0.6)"
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            title="Ubicación Blest Barber Shop">
          </iframe>
        </div>
      </div>

    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="container">
    <div class="row g-4">

      <div class="col-lg-4">
        <div class="footer-brand">
          <span class="brand-el">BLEST</span><span class="brand-filo"> BARBER</span>
        </div>

        <p class="footer-tagline">El arte del filo, la precisión del corte.</p>

        <div class="footer-social">
          <a href="https://www.instagram.com/blestbarbershop/" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="https://www.tiktok.com/@blestbarbershop00" target="_blank" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
          <a href="https://api.whatsapp.com/send/?phone=5491151267271" target="_blank" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>

      <div class="col-sm-6 col-lg-2 offset-lg-1">
        <h5 class="footer-heading">Sitio</h5>

        <ul class="footer-links">
          <li><a href="index.php">Inicio</a></li>
          <li><a href="index.php#servicios">Servicios</a></li>

          <?php foreach ($sedes as $sede): ?>
            <li>
              <a href="sede.php?id=<?php echo $sede["id"]; ?>">
                <?php echo htmlspecialchars($sede["nombre"]); ?>
              </a>
            </li>
          <?php endforeach; ?>

          <li><a href="contacto.php">Contacto</a></li>
        </ul>
      </div>

      <div class="col-sm-6 col-lg-2">
        <h5 class="footer-heading">Mi cuenta</h5>

        <ul class="footer-links">
          <?php if (isset($_SESSION["usuario_id"])): ?>
            <li>
              <a href="<?php echo $_SESSION["usuario_rol"] === "admin"
                  ? 'admin/panel_admin.php'
                  : 'user/panel_usuario.php'; ?>">

                <?php echo $_SESSION["usuario_rol"] === "admin"
                  ? 'Panel admin'
                  : 'Mi cuenta'; ?>

              </a>
            </li>
            <li><a href="user/reservar_turno.php">Reservar turno</a></li>
            <li>
              <form action="../controllers/AuthController.php" method="POST" style="margin:0">
                <input type="hidden" name="action" value="logout">
                <button type="submit" style="background:none;border:0;padding:0;color:inherit;font:inherit">
                  Cerrar sesión
                </button>
              </form>
            </li>
          <?php else: ?>
            <li><a href="registro.php">Registrarse</a></li>
            <li><a href="login.php">Ingresar</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="col-lg-3">
        <h5 class="footer-heading">Horarios</h5>

        <ul class="footer-contact-list">
          <?php if (empty($sedes)): ?>
            <li><i class="bi bi-geo-alt-fill"></i> Próximamente nuevas sedes</li>
          <?php else: ?>
            <?php foreach ($sedes as $sede): ?>
              <li>
                <i class="bi bi-geo-alt-fill"></i>
                <?php echo htmlspecialchars($sede["direccion"]); ?>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>

          <li><i class="bi bi-clock-fill"></i> Mar–Sab 10:30–13 / 14:30–20 hs</li>
          <li><i class="bi bi-x-circle-fill" style="color:#8b1a1a"></i> Dom-Lun cerrado</li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p>© 2026 Blest Barber Shop · Todos los derechos reservados</p>
    </div>
  </div>
</footer>

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

<script src="../js/main.js"></script>
</body>
</html>
