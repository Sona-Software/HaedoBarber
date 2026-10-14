<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ingresar | Haedo Barber</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/haedobarber-mark.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

  <!-- Navbar mínima -->
  <nav class="navbar navbar-dark fixed-top py-3" style="background:rgba(10,10,10,0.95);border-bottom:1px solid var(--border)">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <span class="brand-el">HAEDO</span><span class="brand-filo"> BARBER</span>
      </a>
      <a href="index.php" class="btn btn-outline-gold btn-sm">← Volver al inicio</a>
    </div>
  </nav>

  <div class="container">
    <div class="auth-card">

      <!-- Encabezado -->
      <!-- Encabezado -->
      <!-- Encabezado -->
      <!-- Encabezado -->
      <!-- Encabezado -->
      <!-- Encabezado -->
      <!-- Encabezado -->
      <div class="mb-4">
        <p class="section-eyebrow mb-1">Bienvenido de vuelta</p>
        <h1 class="auth-title">INGRESAR</h1>
      </div>

      <!-- Mensaje de error PHP (placeholder) -->
      <!-- Mensaje de error PHP (placeholder) -->
      <!-- Mensaje de error PHP (placeholder) -->
      <!-- Mensaje de error PHP (placeholder) -->
      <?php
          $error = $_GET['error'] ?? '';
        ?>
        <?php if ($error === 'credenciales_invalidas'): ?>
          <div class="alert alert-danger alert-dismissible fade show"
              role="alert"
              style="background:#2a1111;border:1px solid #8b1a1a;color:#f0ede8;border-radius:2px">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Email o contraseña incorrectos
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; 
      ?>

      <!-- Formulario -->
        <form action="../controllers/AuthController.php" method="POST" id="loginForm" novalidate>
          <input type="hidden" name="action" value="login">

          <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--bg4);border:1px solid var(--border);border-right:none;color:var(--muted);border-radius:2px 0 0 2px">
                <i class="bi bi-envelope"></i>
              </span>
              <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                placeholder="tu@email.com"
                required
                autocomplete="email"
                style="border-left:none!important"
              >
            </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--bg4);border:1px solid var(--border);border-right:none;color:var(--muted);border-radius:2px 0 0 2px">
                <i class="bi bi-lock"></i>
              </span>
              <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
                style="border-left:none!important;border-right:none!important"
              >
              <button class="btn" type="button" id="togglePass"
                style="background:var(--bg4);border:1px solid var(--border);border-left:none;color:var(--muted);border-radius:0 2px 2px 0;padding:0 14px"
                onclick="togglePassword('password', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="recordar" id="recordar"
                style="background-color:var(--bg4);border-color:var(--border)">
              <label class="form-check-label" for="recordar" style="font-size:12px;color:var(--muted)">Recordarme</label>
            </div>
            <a href="recuperar.html" class="text-gold text-decoration-none" style="font-size:12px">¿Olvidaste tu contraseña?</a>
          </div>

          <button type="submit" class="btn btn-gold w-100 py-3">
            Ingresar <i class="bi bi-arrow-right ms-2"></i>
          </button>
        </form>

      <div class="mt-4 pt-4" style="border-top:1px solid var(--border);text-align:center">
        <p style="font-size:12px;color:var(--muted)">
          ¿Primera vez en Haedo Barber?
          <a href="registro.php" class="text-gold text-decoration-none fw-semibold ms-1">Creá tu cuenta</a>
        </p>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword(id, btn) {
      const input = document.getElementById(id);
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
      } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
      }
    }
  </script>
  <script src="assets/js/main.js"></script>
</body>
</html>
