<?php
require_once __DIR__ . '/../config/security.php';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="format-detection" content="telephone=no">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrarse | Haedo Barber</title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png?v=2">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

  <nav class="navbar navbar-dark fixed-top py-3">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <img src="assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" class="brand-logo" width="800" height="298">
      </a>
      <a href="index.php" class="btn btn-outline-gold btn-sm">← Volver al inicio</a>
    </div>
  </nav>

  <div class="container">
    <div class="auth-card" style="max-width:560px">

      <div class="mb-4">
        <p class="section-eyebrow mb-1">Únete al equipo</p>
        <h1 class="auth-title">CREAR CUENTA</h1>
        <p class="auth-subtitle">¿Ya tenés cuenta? <a href="login.php" class="text-gold text-decoration-none fw-semibold">Ingresá acá</a></p>
      </div>

      <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-hb alert-hb-error"
            role="alert"
            >

          <i class="bi bi-exclamation-triangle-fill me-2"></i>

          <?php
            if ($error === 'terminos_no_aceptados') {
              echo 'Tenés que aceptar los términos y condiciones.';
            } elseif ($error === 'email_existente' || $error === 'registro_fallido') {
              echo 'No pudimos crear la cuenta con esos datos. Si ya te registraste antes, ingresá desde la página de inicio de sesión.';
            } elseif ($error === 'demasiados_intentos') {
              echo 'Demasiados intentos. Esperá un rato antes de volver a intentar.';
            } elseif ($error === 'telefono_invalido') {
              echo 'Ingresá un teléfono válido.';
            } elseif ($error === 'password_larga') {
              echo 'La contraseña no puede superar los 72 caracteres.';
            } elseif ($error === 'password_debil') {
              echo 'La contraseña debe incluir letras y números, y no ser una clave común.';
            } elseif ($error === 'email_invalido') {
              echo 'Ingresá un email válido.';
            } elseif ($error === 'password_no_coincide') {
              echo 'Las contraseñas no coinciden.';
            } elseif ($error === 'password_corta') {
              echo 'La contraseña debe tener al menos 10 caracteres.';
            } elseif ($error === 'campos_vacios') {
              echo 'Completá todos los campos obligatorios.';
            } else {
              echo 'Ocurrió un error al registrar la cuenta.';
            }
      ?>

  <button type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="alert">
  </button>
</div>
<?php endif; ?>

      <form action="../controllers/AuthController.php" method="POST" id="registerForm" novalidate><?= csrf_field() ?>
        <input type="hidden" name="action" value="register">

        <div class="row g-3">

          <div class="col-sm-6">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre"
              placeholder="Tu nombre" required minlength="2" maxlength="80">
            <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá tu nombre.</div>
          </div>

          <div class="col-sm-6">
            <label for="apellido" class="form-label">Apellido</label>
            <input type="text" class="form-control" id="apellido" name="apellido"
              placeholder="Tu apellido" required minlength="2" maxlength="80">
            <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá tu apellido.</div>
          </div>

          <div class="col-sm-6">
            <label for="telefono" class="form-label">Teléfono</label>
            <div class="input-group">
              <span class="input-group-text">+54</span>
              <input type="tel" class="form-control" id="telefono" name="telefono"
                placeholder="11 1234-5678" required
                pattern="[0-9\s\-]{8,15}"
                style="border-left:none!important">
            </div>
            <div class="form-text" style="font-size:11px;color:var(--muted)">Para recordatorios de turno</div>
          </div>

          <div class="col-sm-6">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
              placeholder="tu@email.com" required autocomplete="email" maxlength="120">
            <div class="invalid-feedback" style="font-size:11px;color:#e05555">Ingresá un email válido.</div>
          </div>

          <div class="col-12">
            <hr style="border-color:var(--border);margin:8px 0">
            <p style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:12px">Seguridad</p>
          </div>

          <div class="col-sm-6">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password"
                placeholder="Mínimo 10, con letras y números" required minlength="10" maxlength="72" autocomplete="new-password"
                style="border-right:none!important">
              <button class="btn btn-toggle-pass" type="button"
                onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="col-sm-6">
            <label for="password_confirm" class="form-label">Repetir contraseña</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                placeholder="Repetí la contraseña" required
                style="border-right:none!important">
              <button class="btn btn-toggle-pass" type="button"
                onclick="togglePassword('password_confirm', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="invalid-feedback" style="font-size:11px;color:#e05555">Las contraseñas no coinciden.</div>
          </div>

         <div class="col-12">
          <div class="form-check terms-check">
            <input
              class="form-check-input"
              type="checkbox"
              id="terminos"
              name="terminos"
              required
              
            >

            <label class="form-check-label" for="terminos" style="font-size:12px;color:var(--muted)">
              Acepto los
              <a href="terminos.html" class="text-gold text-decoration-none" target="_blank">
                términos y condiciones
              </a>
              del servicio
            </label>

            <div class="invalid-feedback terms-error">
              Tenés que aceptar los términos y condiciones.
            </div>
          </div>
        </div>

          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-gold w-100 py-3">
              Crear cuenta <i class="bi bi-arrow-right ms-2"></i>
            </button>
          </div>

        </div>
      </form>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword(id, btn) {
      const input = document.getElementById(id);
      const icon = btn.querySelector('i');
      input.type = input.type === 'password' ? 'text' : 'password';
      icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }

    // Medidor de fuerza de contraseña
    const passInput = document.getElementById('password');
    const bar = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    const wrap = document.getElementById('password-strength-wrap');

    if (passInput && bar && label && wrap) passInput.addEventListener('input', () => {
      const val = passInput.value;
      if (!val) { wrap.style.display = 'none'; return; }
      wrap.style.display = 'block';

      let score = 0;
      if (val.length >= 10) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      const levels = [
        { w: '20%', color: '#e05555', text: 'Muy débil' },
        { w: '40%', color: '#e08855', text: 'Débil' },
        { w: '65%', color: '#c9a84c', text: 'Aceptable' },
        { w: '85%', color: '#5bb888', text: 'Fuerte' },
        { w: '100%', color: '#2aa86c', text: 'Muy fuerte' },
      ];
      const l = levels[Math.min(score, 4)];
      bar.style.width = l.w;
      bar.style.background = l.color;
      label.textContent = l.text;
    });
  </script>
  <script src="assets/js/main.js"></script>
</body>
</html>
