<?php
session_start();

date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once '../../config/database.php';

$nombreAdmin = $_SESSION["usuario_nombre"];
$emailAdmin = $_SESSION["usuario_email"];

$busqueda = trim($_GET["buscar"] ?? "");
$filtroRol = trim($_GET["rol"] ?? "");

$usuarios = [];
$totalUsuarios = 0;
$totalGenerales = 0;
$totalAdmins = 0;
$totalSesionesActivas = 0;

function formatearUltimoAcceso($fecha) {
    if (empty($fecha)) {
        return "Nunca ingresó";
    }

    $ultimoAcceso = new DateTime($fecha);
    $ahora = new DateTime();

    if ($ultimoAcceso > $ahora) {
        return "Hace instantes";
    }

    $diferencia = $ultimoAcceso->diff($ahora);

    if ($diferencia->y > 0) {
        return "Hace " . $diferencia->y . " año/s";
    }

    if ($diferencia->m > 0) {
        return "Hace " . $diferencia->m . " mes/es";
    }

    if ($diferencia->d > 0) {
        return "Hace " . $diferencia->d . " día/s";
    }

    if ($diferencia->h > 0) {
        return "Hace " . $diferencia->h . " hora/s";
    }

    if ($diferencia->i > 0) {
        return "Hace " . $diferencia->i . " minuto/s";
    }

    return "Hace instantes";
}

function obtenerEstadoSesion($usuario, $adminActualId) {
    if ($usuario["rol"] === "admin" && $usuario["id"] == $adminActualId) {
        return "Activa";
    }

    if (
        !empty($usuario["remember_token_hash"]) &&
        !empty($usuario["remember_token_expira"]) &&
        strtotime($usuario["remember_token_expira"]) > time()
    ) {
        return "Activa";
    }

    return "Inactiva";
}

try {
    $sqlStats = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN rol = 'general' THEN 1 ELSE 0 END) AS generales,
                    SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) AS admins,
                    SUM(CASE
                        WHEN remember_token_hash IS NOT NULL
                        AND remember_token_expira > NOW()
                        THEN 1 ELSE 0
                    END) AS sesiones_activas
                 FROM usuarios";

    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $totalUsuarios = $stats["total"] ?? 0;
    $totalGenerales = $stats["generales"] ?? 0;
    $totalAdmins = $stats["admins"] ?? 0;
    $totalSesionesActivas = $stats["sesiones_activas"] ?? 0;

    $sql = "SELECT
                id,
                nombre,
                apellido,
                telefono,
                email,
                rol,
                creado_en,
                ultimo_acceso,
                remember_token_hash,
                remember_token_expira
            FROM usuarios
            WHERE 1 = 1";

    $params = [];

    if ($busqueda !== "") {
        $sql .= " AND (
                    nombre LIKE :busqueda
                    OR apellido LIKE :busqueda
                    OR email LIKE :busqueda
                    OR telefono LIKE :busqueda
                  )";

        $params[":busqueda"] = "%" . $busqueda . "%";
    }

    if ($filtroRol === "admin" || $filtroRol === "general") {
        $sql .= " AND rol = :rol";
        $params[":rol"] = $filtroRol;
    }

    $sql .= " ORDER BY creado_en DESC";

    $stmt = $conn->prepare($sql);

    foreach ($params as $param => $valor) {
        $stmt->bindValue($param, $valor);
    }

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $usuarios = [];
    $errorDB = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

  <title>Usuarios | Panel Admin</title>

  <link rel="icon" type="image/svg+xml" href="../assets/img/haedobarber-mark.svg?v=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet"
        href="../assets/css/style.css?v=7">
</head>

<body>

<main class="admin-layout">

  <aside class="admin-sidebar">
    <div>

      <div class="admin-brand">
        <span class="brand-el">HAEDO</span>
        <span class="brand-filo"> BARBER</span>
      </div>

      <nav class="admin-menu">

        <a href="panel_admin.php"
           class="admin-menu-link">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>

        <a href="turnos.php"
           class="admin-menu-link">
          <i class="bi bi-calendar-check"></i>
          Turnos
        </a>

        <a href="mensajes.php"
           class="admin-menu-link">
          <i class="bi bi-envelope"></i>
          Mensajes
        </a>

        <a href="servicios.php"
           class="admin-menu-link">
          <i class="bi bi-scissors"></i>
          Servicios
        </a>

        <a href="barberos.php"
           class="admin-menu-link">
          <i class="bi bi-person-badge"></i>
          Barberos
        </a>

        <a href="sedes.php" class="admin-menu-link">
          <i class="bi bi-geo-alt"></i>
          Sedes
        </a>

        <a href="usuarios.php"
           class="admin-menu-link active">
          <i class="bi bi-people"></i>
          Usuarios
        </a>

      </nav>
    </div>

    <form action="../../controllers/AuthController.php"
          method="POST">

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
        <p class="section-eyebrow mb-1">
          Panel administrativo
        </p>

        <h1 class="admin-title">
          Usuarios
        </h1>
      </div>

      <div class="admin-user-box">

        <div class="admin-user-avatar">
          <i class="bi bi-person-fill"></i>
        </div>

        <div>
          <strong>
            <?php echo htmlspecialchars($nombreAdmin); ?>
          </strong>

          <span>
            <?php echo htmlspecialchars($emailAdmin); ?>
          </span>
        </div>

      </div>

    </header>

    <section class="admin-stats-grid">

      <article class="admin-stat-card">
        <i class="bi bi-people"></i>
        <span>Total usuarios</span>
        <strong><?php echo $totalUsuarios; ?></strong>
        <small>Cuentas registradas</small>
      </article>

      <article class="admin-stat-card">
        <i class="bi bi-person"></i>
        <span>Usuarios generales</span>
        <strong><?php echo $totalGenerales; ?></strong>
        <small>Clientes registrados</small>
      </article>

      <article class="admin-stat-card">
        <i class="bi bi-shield-lock"></i>
        <span>Administradores</span>
        <strong><?php echo $totalAdmins; ?></strong>
        <small>Cuentas internas</small>
      </article>

      <article class="admin-stat-card">
        <i class="bi bi-wifi"></i>
        <span>Sesiones activas</span>
        <strong><?php echo $totalSesionesActivas; ?></strong>
        <small>Usuarios con sesión vigente</small>
      </article>

    </section>

    <section class="admin-section-card">

      <div class="admin-section-header">
        <div>
          <h3>Clientes registrados</h3>

          <p>
            Consultá usuarios, roles, último ingreso y estado de sesión sin exponer contraseñas.
          </p>
        </div>
      </div>

      <form method="GET"
            class="row g-3 mb-4">

        <div class="col-lg-7">
          <label class="form-label">
            Buscar usuario
          </label>

          <input type="text"
                 name="buscar"
                 class="form-control"
                 placeholder="Nombre, apellido, email o teléfono"
                 value="<?php echo htmlspecialchars($busqueda); ?>">
        </div>

        <div class="col-lg-3">
          <label class="form-label">
            Rol
          </label>

          <select name="rol"
                  class="form-select admin-filter-select"
                  onchange="this.form.submit()">

            <option value=""
              <?php echo ($filtroRol === "") ? "selected" : ""; ?>>
              Todos
            </option>

            <option value="general"
              <?php echo ($filtroRol === "general") ? "selected" : ""; ?>>
              Usuario general
            </option>

            <option value="admin"
              <?php echo ($filtroRol === "admin") ? "selected" : ""; ?>>
              Administrador
            </option>

          </select>
        </div>

        <div class="col-lg-2 d-flex align-items-end">
          <button type="submit"
                  class="btn btn-gold w-100">
            Buscar
          </button>
        </div>

      </form>

      <?php if ($busqueda !== "" || $filtroRol !== ""): ?>

        <a href="usuarios.php"
           class="btn btn-outline-gold btn-sm mb-4">
          Limpiar filtros
        </a>

      <?php endif; ?>

      <?php if (!empty($errorDB)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Error al cargar usuarios.</p>
        </div>

      <?php elseif (empty($usuarios)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-people"></i>
          <p>No se encontraron usuarios con esos criterios.</p>
        </div>

      <?php else: ?>

        <div class="admin-table-wrap">

          <table class="admin-table">

            <thead>
              <tr>
                <th>Usuario</th>
                <th>Contacto</th>
                <th>Rol</th>
                <th>Registro</th>
                <th>Último ingreso</th>
                <th>Sesión</th>
              </tr>
            </thead>

            <tbody>

              <?php foreach ($usuarios as $usuario): ?>

                <?php
                  $nombreCompleto = trim($usuario["nombre"] . " " . $usuario["apellido"]);

                  $estadoSesion = obtenerEstadoSesion(
                      $usuario,
                      $_SESSION["usuario_id"]
                  );
                ?>

                <tr>

                  <td>
                    <strong>
                      <?php echo htmlspecialchars($nombreCompleto); ?>
                    </strong>

                    <br>

                    <small style="color:var(--muted)">
                      ID #<?php echo $usuario["id"]; ?>
                    </small>
                  </td>

                  <td>
                    <span>
                      <?php echo htmlspecialchars($usuario["email"]); ?>
                    </span>

                    <br>

                    <small style="color:var(--muted)">
                      <?php echo !empty($usuario["telefono"])
                        ? htmlspecialchars($usuario["telefono"])
                        : "Sin teléfono"; ?>
                    </small>
                  </td>

                  <td>
                    <?php if ($usuario["rol"] === "admin"): ?>

                      <span class="admin-badge-active">
                        Admin
                      </span>

                    <?php else: ?>

                      <span class="admin-badge-inactive">
                        General
                      </span>

                    <?php endif; ?>
                  </td>

                  <td>
                    <?php echo date("d/m/Y", strtotime($usuario["creado_en"])); ?>

                    <br>

                    <small style="color:var(--muted)">
                      <?php echo date("H:i", strtotime($usuario["creado_en"])); ?> hs
                    </small>
                  </td>

                  <td>
                    <?php echo formatearUltimoAcceso($usuario["ultimo_acceso"]); ?>

                    <?php if (!empty($usuario["ultimo_acceso"])): ?>
                      <br>

                      <small style="color:var(--muted)">
                        <?php echo date("d/m/Y H:i", strtotime($usuario["ultimo_acceso"])); ?> hs
                      </small>
                    <?php endif; ?>
                  </td>

                  <td>

                    <?php if ($estadoSesion === "Activa"): ?>

                      <span class="admin-badge-active">
                        Activa
                      </span>

                    <?php else: ?>

                      <span class="admin-badge-inactive">
                        Inactiva
                      </span>

                    <?php endif; ?>

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

</body>
</html>