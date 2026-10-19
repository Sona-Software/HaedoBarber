<?php
require_once __DIR__ . '/../../config/security.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../config/turnos_helper.php';

$nombreAdmin = $_SESSION["usuario_nombre"];
$emailAdmin = $_SESSION["usuario_email"];

$buscar = trim($_GET["buscar"] ?? "");
$estadoFiltro = $_GET["estado"] ?? "";
$fechaFiltro = $_GET["fecha"] ?? "";
$sedeFiltro = (string) (turnos_id((string) ($_GET["sede_id"] ?? "")) ?? "");
$barberoFiltro = (string) (turnos_id((string) ($_GET["barbero_id"] ?? "")) ?? "");
if (!in_array($estadoFiltro, ["pendiente", "confirmado", "cancelado", "completado", "ausente"], true)) {
    $estadoFiltro = "";
}
if (!turnos_fecha_valida((string) $fechaFiltro)) {
    $fechaFiltro = "";
}
$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

$errorDB = false;

try {
    $sqlSedes = "SELECT id, nombre FROM sedes WHERE activo = 1 ORDER BY nombre ASC";
    $stmtSedes = $conn->prepare($sqlSedes);
    $stmtSedes->execute();
    $sedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);

    $sqlBarberos = "SELECT id, nombre FROM barberos WHERE activo = 1 ORDER BY nombre ASC";
    $stmtBarberos = $conn->prepare($sqlBarberos);
    $stmtBarberos->execute();
    $barberos = $stmtBarberos->fetchAll(PDO::FETCH_ASSOC);

    $sqlStats = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN fecha = CURDATE() THEN 1 ELSE 0 END) AS hoy,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'confirmado' THEN 1 ELSE 0 END) AS confirmados,
                    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) AS cancelados
                 FROM turnos";

    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT
                t.id,
                t.fecha,
                t.hora,
                t.estado,
                t.observaciones,
                t.creado_en,
                u.nombre AS usuario_nombre,
                u.apellido AS usuario_apellido,
                u.email AS usuario_email,
                u.telefono AS usuario_telefono,
                s.nombre AS sede_nombre,
                b.nombre AS barbero_nombre,
                sv.nombre AS servicio_nombre,
                sv.precio AS servicio_precio,
                sv.duracion_min AS servicio_duracion
            FROM turnos t
            INNER JOIN usuarios u ON t.usuario_id = u.id
            INNER JOIN sedes s ON t.sede_id = s.id
            INNER JOIN barberos b ON t.barbero_id = b.id
            INNER JOIN servicios sv ON t.servicio_id = sv.id
            WHERE 1 = 1";

    $params = [];

    if ($buscar !== "") {
        $sql .= " AND (
                    u.nombre LIKE :buscar
                    OR u.apellido LIKE :buscar
                    OR u.email LIKE :buscar
                    OR u.telefono LIKE :buscar
                    OR b.nombre LIKE :buscar
                    OR sv.nombre LIKE :buscar
                    OR s.nombre LIKE :buscar
                  )";
        $params[":buscar"] = "%" . $buscar . "%";
    }

    if ($estadoFiltro !== "") {
        $sql .= " AND t.estado = :estado";
        $params[":estado"] = $estadoFiltro;
    }

    if ($fechaFiltro !== "") {
        $sql .= " AND t.fecha = :fecha";
        $params[":fecha"] = $fechaFiltro;
    }

    if ($sedeFiltro !== "") {
        $sql .= " AND t.sede_id = :sede_id";
        $params[":sede_id"] = $sedeFiltro;
    }

    if ($barberoFiltro !== "") {
        $sql .= " AND t.barbero_id = :barbero_id";
        $params[":barbero_id"] = $barberoFiltro;
    }

    $sql .= " ORDER BY t.fecha DESC, t.hora DESC";

    $stmt = $conn->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $turnos = [];
    $sedes = [];
    $barberos = [];
    $stats = [
        "total" => 0,
        "hoy" => 0,
        "pendientes" => 0,
        "confirmados" => 0,
        "cancelados" => 0
    ];
    $errorDB = true;
}

function badgeEstado($estado) {
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

  <title>Turnos | Panel Admin</title>

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

        <a href="panel_admin.php"
           class="admin-menu-link">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </a>

        <a href="turnos.php"
           class="admin-menu-link active">
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

        <a href="sedes.php"
           class="admin-menu-link">
          <i class="bi bi-geo-alt"></i>
          Sedes
        </a>

        <a href="usuarios.php"
           class="admin-menu-link">
          <i class="bi bi-people"></i>
          Usuarios
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
        <p class="section-eyebrow mb-1">
          Panel administrativo
        </p>

        <h1 class="admin-title">
          Turnos
        </h1>
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

    <section class="admin-stats-grid mb-4">

      <div class="admin-stat-card">
        <div>
          <span>Turnos hoy</span>
          <strong><?php echo intval($stats["hoy"] ?? 0); ?></strong>
        </div>
        <i class="bi bi-calendar-day"></i>
      </div>

      <div class="admin-stat-card">
        <div>
          <span>Pendientes</span>
          <strong><?php echo intval($stats["pendientes"] ?? 0); ?></strong>
        </div>
        <i class="bi bi-hourglass-split"></i>
      </div>

      <div class="admin-stat-card">
        <div>
          <span>Confirmados</span>
          <strong><?php echo intval($stats["confirmados"] ?? 0); ?></strong>
        </div>
        <i class="bi bi-check-circle"></i>
      </div>

      <div class="admin-stat-card">
        <div>
          <span>Cancelados</span>
          <strong><?php echo intval($stats["cancelados"] ?? 0); ?></strong>
        </div>
        <i class="bi bi-x-circle"></i>
      </div>

    </section>

    <section class="admin-section-card">

      <div class="admin-section-header">

        <div>
          <h3>Gestión de turnos</h3>
          <p>Consultá reservas, clientes, sedes, barberos, servicios y estados.</p>
        </div>

      </div>

      <?php if ($success === "estado_actualizado"): ?>
        <div class="alert mb-4"
             style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
          <i class="bi bi-check2-circle me-2"></i>
          Estado del turno actualizado correctamente.
        </div>
      <?php endif; ?>

      <?php
        $mensajesError = [
          "datos_invalidos" => "No se pudo actualizar el turno: datos inválidos.",
          "accion_invalida" => "No se pudo actualizar el turno: acción inválida.",
          "db_error" => "No se pudo actualizar el turno por un error de base de datos.",
          "turno_inexistente" => "El turno ya no existe.",
          "transicion_invalida" => "Ese cambio de estado no es válido para el estado actual del turno.",
          "turno_futuro" => "No se puede marcar como completado o ausente un turno que todavía no comenzó.",
        ];
      ?>
      <?php if (isset($mensajesError[$error])): ?>
        <div class="alert mb-4"
             style="background:#2a1111;border:1px solid #6b2c2c;color:#e8a0a0;border-radius:2px">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <?php echo htmlspecialchars($mensajesError[$error]); ?>
        </div>
      <?php endif; ?>

      <form method="GET"
            class="row g-3 mb-4">

        <div class="col-md-4">
          <label class="form-label">Buscar</label>
          <input type="text"
                 name="buscar"
                 class="form-control"
                 placeholder="Cliente, email, teléfono, barbero..."
                 value="<?php echo htmlspecialchars($buscar); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label">Fecha</label>
          <input type="date"
                 name="fecha"
                 class="form-control"
                 value="<?php echo htmlspecialchars($fechaFiltro); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label">Estado</label>
          <select name="estado"
                  class="form-control">
            <option value="">Todos</option>
            <option value="pendiente" <?php echo $estadoFiltro === "pendiente" ? "selected" : ""; ?>>Pendiente</option>
            <option value="confirmado" <?php echo $estadoFiltro === "confirmado" ? "selected" : ""; ?>>Confirmado</option>
            <option value="cancelado" <?php echo $estadoFiltro === "cancelado" ? "selected" : ""; ?>>Cancelado</option>
            <option value="completado" <?php echo $estadoFiltro === "completado" ? "selected" : ""; ?>>Completado</option>
            <option value="ausente" <?php echo $estadoFiltro === "ausente" ? "selected" : ""; ?>>Ausente</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Sede</label>
          <select name="sede_id"
                  class="form-control">
            <option value="">Todas</option>

            <?php foreach ($sedes as $sede): ?>
              <option value="<?php echo $sede["id"]; ?>"
                <?php echo $sedeFiltro == $sede["id"] ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($sede["nombre"]); ?>
              </option>
            <?php endforeach; ?>

          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Barbero</label>
          <select name="barbero_id"
                  class="form-control">
            <option value="">Todos</option>

            <?php foreach ($barberos as $barbero): ?>
              <option value="<?php echo $barbero["id"]; ?>"
                <?php echo $barberoFiltro == $barbero["id"] ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($barbero["nombre"]); ?>
              </option>
            <?php endforeach; ?>

          </select>
        </div>

        <div class="col-12 d-flex gap-2">
          <button type="submit"
                  class="btn btn-gold">
            Filtrar
          </button>

          <a href="turnos.php"
             class="btn btn-outline-gold">
            Limpiar
          </a>
        </div>

      </form>

      <?php if ($errorDB): ?>

        <div class="admin-empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Error al cargar turnos.</p>
        </div>

      <?php elseif (empty($turnos)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-calendar-x"></i>
          <p>No hay turnos para mostrar.</p>
        </div>

      <?php else: ?>

        <div class="admin-table-wrap">

          <table class="admin-table">

            <thead>
              <tr>
                <th>Cliente</th>
                <th>Sede</th>
                <th>Barbero</th>
                <th>Servicio</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Estado</th>
                <th>Detalle</th>
              </tr>
            </thead>

            <tbody>

              <?php foreach ($turnos as $turno): ?>

                <tr>
                  <td>
                    <strong>
                      <?php echo htmlspecialchars($turno["usuario_nombre"] . " " . $turno["usuario_apellido"]); ?>
                    </strong>
                    <br>
                    <small><?php echo htmlspecialchars($turno["usuario_email"]); ?></small>
                    <br>
                    <small><?php echo htmlspecialchars($turno["usuario_telefono"]); ?></small>
                  </td>

                  <td><?php echo htmlspecialchars($turno["sede_nombre"]); ?></td>

                  <td><?php echo htmlspecialchars($turno["barbero_nombre"]); ?></td>

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
                    <?php echo date("d/m/Y", strtotime($turno["fecha"])); ?>
                  </td>

                  <td>
                    <?php echo substr($turno["hora"], 0, 5); ?>
                  </td>

                  <td>
                    <span class="<?php echo badgeEstado($turno["estado"]); ?>">
                      <?php echo ucfirst($turno["estado"]); ?>
                    </span>
                  </td>

                  <td>
                    <button type="button"
                            class="btn btn-outline-gold btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#detalleTurnoModal<?php echo $turno["id"]; ?>">
                      Ver
                    </button>
                  </td>
                </tr>

                <div class="modal fade"
                     id="detalleTurnoModal<?php echo $turno["id"]; ?>"
                     tabindex="-1"
                     aria-hidden="true">

                  <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content admin-message-modal">

                      <div class="modal-header">
                        <h5 class="modal-title">
                          Detalle del turno
                        </h5>

                        <button type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal">
                        </button>
                      </div>

                      <div class="modal-body">

                        <p><strong>Cliente:</strong>
                          <?php echo htmlspecialchars($turno["usuario_nombre"] . " " . $turno["usuario_apellido"]); ?>
                        </p>

                        <p><strong>Email:</strong>
                          <?php echo htmlspecialchars($turno["usuario_email"]); ?>
                        </p>

                        <p><strong>Teléfono:</strong>
                          <?php echo htmlspecialchars($turno["usuario_telefono"]); ?>
                        </p>

                        <hr>

                        <p><strong>Sede:</strong>
                          <?php echo htmlspecialchars($turno["sede_nombre"]); ?>
                        </p>

                        <p><strong>Barbero:</strong>
                          <?php echo htmlspecialchars($turno["barbero_nombre"]); ?>
                        </p>

                        <p><strong>Servicio:</strong>
                          <?php echo htmlspecialchars($turno["servicio_nombre"]); ?>
                        </p>

                        <p><strong>Precio:</strong>
                          $<?php echo number_format($turno["servicio_precio"], 0, ",", "."); ?>
                        </p>

                        <p><strong>Duración:</strong>
                          <?php echo intval($turno["servicio_duracion"]); ?> minutos
                        </p>

                        <hr>

                        <p><strong>Fecha:</strong>
                          <?php echo date("d/m/Y", strtotime($turno["fecha"])); ?>
                        </p>

                        <p><strong>Hora:</strong>
                          <?php echo substr($turno["hora"], 0, 5); ?>
                        </p>

                        <p><strong>Estado:</strong>
                          <?php echo ucfirst($turno["estado"]); ?>
                        </p>

                        <p><strong>Observaciones:</strong></p>

                        <p>
                          <?php echo !empty($turno["observaciones"])
                              ? nl2br(htmlspecialchars($turno["observaciones"]))
                              : "Sin observaciones."; ?>
                        </p>

                      </div>

                      <div class="modal-footer d-flex flex-wrap justify-content-between gap-2">

                        <div class="d-flex flex-wrap gap-2">

                          <?php if ($turno["estado"] === "pendiente"): ?>
                            <form action="../../controllers/AdminTurnoController.php"
                                  method="POST"><?= csrf_field() ?>

                              <input type="hidden"
                                     name="accion"
                                     value="confirmar">

                              <input type="hidden"
                                     name="id"
                                     value="<?php echo $turno["id"]; ?>">

                              <button type="submit"
                                      class="btn btn-gold">
                                Confirmar
                              </button>

                            </form>
                          <?php endif; ?>

                          <?php if ($turno["estado"] === "pendiente" || $turno["estado"] === "confirmado"): ?>
                            <form action="../../controllers/AdminTurnoController.php"
                                  method="POST"><?= csrf_field() ?>

                              <input type="hidden"
                                     name="accion"
                                     value="cancelar">

                              <input type="hidden"
                                     name="id"
                                     value="<?php echo $turno["id"]; ?>">

                              <button type="submit"
                                      class="btn admin-btn-danger">
                                Cancelar
                              </button>

                            </form>
                          <?php endif; ?>

                          <?php if (($turno["estado"] === "confirmado" || $turno["estado"] === "pendiente")
                                    && turnos_ts($turno["fecha"], substr($turno["hora"], 0, 5)) <= time()): ?>
                            <form action="../../controllers/AdminTurnoController.php"
                                  method="POST"><?= csrf_field() ?>

                              <input type="hidden"
                                     name="accion"
                                     value="completar">

                              <input type="hidden"
                                     name="id"
                                     value="<?php echo $turno["id"]; ?>">

                              <button type="submit"
                                      class="btn btn-outline-gold">
                                Completar
                              </button>

                            </form>

                            <form action="../../controllers/AdminTurnoController.php"
                                  method="POST"><?= csrf_field() ?>

                              <input type="hidden"
                                     name="accion"
                                     value="ausente">

                              <input type="hidden"
                                     name="id"
                                     value="<?php echo $turno["id"]; ?>">

                              <button type="submit"
                                      class="btn btn-outline-gold">
                                Ausente
                              </button>

                            </form>
                          <?php endif; ?>

                        </div>

                        <button type="button"
                                class="btn btn-outline-gold"
                                data-bs-dismiss="modal">
                          Cerrar
                        </button>

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

  </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/panel.js"></script>

</body>
</html>