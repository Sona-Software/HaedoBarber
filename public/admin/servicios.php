<?php
session_start();

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
$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

try {

    $sqlActivos = "SELECT *
               FROM servicios
               WHERE activo = 1
               ORDER BY nombre ASC";

    $stmtActivos = $conn->prepare($sqlActivos);
    $stmtActivos->execute();
    $serviciosActivos = $stmtActivos->fetchAll(PDO::FETCH_ASSOC);

    $sqlInactivos = "SELECT *
                    FROM servicios
                    WHERE activo = 0
                    ORDER BY nombre ASC";

    $stmtInactivos = $conn->prepare($sqlInactivos);
    $stmtInactivos->execute();
    $serviciosInactivos = $stmtInactivos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $servicios = [];
    $errorDB = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

  <title>Servicios | Panel Admin</title>

  <link rel="icon" type="image/svg+xml" href="../assets/img/haedobarber-mark.svg?v=1.0">
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
           class="admin-menu-link active">
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
           class="admin-menu-link">
          <i class="bi bi-people"></i>
          Usuarios
        </a>

      </nav>
    </div>

    <a href="../../controllers/AuthController.php?logout=1"
       class="admin-logout">
      <i class="bi bi-box-arrow-left"></i>
      Cerrar sesión
    </a>
  </aside>

  <section class="admin-main">

    <header class="admin-topbar">

      <div>
        <p class="section-eyebrow mb-1">
          Panel administrativo
        </p>

        <h1 class="admin-title">
          Servicios
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

    <section class="admin-section-card">

      <div class="admin-section-header">

        <div>
          <h3>Servicios de barbería</h3>

          <p>
            Gestioná cortes, barba y precios.
          </p>
        </div>

        <button
            type="button"
            class="btn btn-gold"
            style="margin-top: 12px;"
            data-bs-toggle="modal"
            data-bs-target="#crearServicioModal">
            Nuevo servicio
        </button>

      </div>

        <?php if ($success === "editado"): ?>
            <div class="alert mb-4"
                style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
                <i class="bi bi-check2-circle me-2"></i>
                Servicio editado correctamente.
            </div>
        <?php endif; ?>

        <?php if ($success === "creado"): ?>
            <div class="alert mb-4"
                style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
                <i class="bi bi-check2-circle me-2"></i>
                Servicio creado correctamente.
            </div>
        <?php endif; ?>

        <?php if ($success === "desactivado"): ?>
            <div class="alert mb-4"
                style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
                <i class="bi bi-check2-circle me-2"></i>
                Servicio desactivado correctamente.
            </div>
        <?php endif; ?>

        <?php if ($success === "reactivado"): ?>
            <div class="alert mb-4"
                style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
                <i class="bi bi-check2-circle me-2"></i>
                Servicio reactivado correctamente.
            </div>
        <?php endif; ?>

      <?php if (!empty($errorDB)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Error al cargar servicios.</p>
        </div>

      <?php elseif (empty($serviciosActivos)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-scissors"></i>
          <p>No hay servicios cargados.</p>
        </div>

      <?php else: ?>

        <div class="admin-table-wrap">

          <table class="admin-table">

            <thead>
              <tr>
                <th>Servicio</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Duración</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>

            <tbody>

              <?php foreach ($serviciosActivos as $servicio): ?>

              <tr>

                <td>
                  <?php echo htmlspecialchars($servicio["nombre"]); ?>
                </td>

                <td>
                  <?php echo htmlspecialchars($servicio["descripcion"]); ?>
                </td>

                <td>
                  $<?php echo number_format($servicio["precio"], 0, ",", "."); ?>
                </td>

                <td>
                  <?php echo htmlspecialchars($servicio["duracion_min"]); ?> min
                </td>

                <td>

                  <?php if ($servicio["activo"]): ?>

                    <span class="admin-badge-active">
                      Activo
                    </span>

                  <?php else: ?>

                    <span class="admin-badge-inactive">
                      Inactivo
                    </span>

                  <?php endif; ?>

                </td>
                <td>
                    <div class="admin-actions">

                        <button
                            type="button"
                            class="btn btn-outline-gold btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#editarServicioModal<?php echo $servicio["id"]; ?>">
                            Editar
                        </button>

                    </div>
                </td>

              </tr>

<!-- MODAL EDITAR SERVICIO -->
 
              <div class="modal fade"
                id="editarServicioModal<?php echo $servicio["id"]; ?>"
                tabindex="-1"
                aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content admin-message-modal">

                <form action="../../controllers/AdminServicioController.php"
                        method="POST">

                    <input type="hidden"
                        name="accion"
                        value="editar">

                    <input type="hidden"
                        name="id"
                        value="<?php echo $servicio["id"]; ?>">

                    <div class="modal-header">
                    <h5 class="modal-title">
                        Editar servicio
                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>
                    </div>

                    <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">
                        Nombre del servicio
                        </label>

                        <input type="text"
                            name="nombre"
                            class="form-control"
                            value="<?php echo htmlspecialchars($servicio["nombre"]); ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                        Descripción
                        </label>

                        <textarea name="descripcion"
                                class="form-control"
                                rows="4"><?php echo htmlspecialchars($servicio["descripcion"]); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                        Precio
                        </label>

                        <input type="number"
                            name="precio"
                            class="form-control"
                            value="<?php echo htmlspecialchars($servicio["precio"]); ?>"
                            min="0"
                            step="100"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                        Duración (minutos)
                        </label>

                        <input type="number"
                            name="duracion_min"
                            class="form-control"
                            value="<?php echo htmlspecialchars($servicio["duracion_min"]); ?>"
                            min="15"
                            step="15"
                            required>
                    </div>
              </div>

                    <div class="modal-footer d-flex justify-content-between">
                    <button
                        type="button"
                        class="btn admin-btn-danger"
                        data-bs-dismiss="modal"
                        data-bs-toggle="modal"
                        data-bs-target="#desactivarServicioModal<?php echo $servicio["id"]; ?>">
                        Eliminar
                    </button>

                    <div>
                        <button type="button"
                            class="btn btn-outline-gold"
                            data-bs-dismiss="modal">
                        Cancelar
                        </button>

                        <button type="submit"
                                class="btn btn-gold">
                        Guardar cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DESACTIVAR SERVICIO -->

<div class="modal fade"
     id="desactivarServicioModal<?php echo $servicio["id"]; ?>"
     tabindex="-1"
     aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content admin-message-modal">

      <div class="modal-header">
        <h5 class="modal-title">
          Confirmar eliminación
        </h5>

        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal">
        </button>
      </div>

      <div class="modal-body text-center">
        <i class="bi bi-trash3-fill delete-icon"></i>

        <h4 class="delete-title">
          ¿Deseás eliminar este servicio?
        </h4>

        <p class="delete-text">
          El servicio no se borrará de la base de datos, solo quedará inactivo.
        </p>
      </div>

      <div class="modal-footer justify-content-center">

        <button type="button"
                class="btn btn-outline-gold"
                data-bs-dismiss="modal">
          Cancelar
        </button>

        <form action="../../controllers/AdminServicioController.php"
              method="POST">

          <input type="hidden"
                 name="accion"
                 value="desactivar">

          <input type="hidden"
                 name="id"
                 value="<?php echo $servicio["id"]; ?>">

          <button type="submit"
                  class="btn admin-btn-danger">
            Eliminar
          </button>

        </form>

      </div>

    </div>
  </div>
</div>

              <?php endforeach; ?>

            </tbody>

          </table>

        </div>

      <?php endif; ?>

      <?php if (!empty($serviciosInactivos)): ?>

  <div class="admin-inactive-services mt-5">

    <button class="btn btn-outline-gold"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#serviciosInactivos">
      Ver inactivos
    </button>

    <div class="collapse mt-4" id="serviciosInactivos">

      <h3 class="mb-3">Servicios inactivos</h3>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Servicio</th>
              <th>Descripción</th>
              <th>Precio</th>
              <th>Duración</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($serviciosInactivos as $servicio): ?>
              <tr>
                <td><?php echo htmlspecialchars($servicio["nombre"]); ?></td>
                <td><?php echo htmlspecialchars($servicio["descripcion"]); ?></td>
                <td>$<?php echo number_format($servicio["precio"], 0, ",", "."); ?></td>
                <td><?php echo htmlspecialchars($servicio["duracion_min"]); ?> min</td>
                <td>
                  <span class="admin-badge-inactive">Inactivo</span>
                </td>
                <td>
                  <div class="admin-actions">
                    <form action="../../controllers/AdminServicioController.php"
                          method="POST">

                      <input type="hidden"
                             name="accion"
                             value="reactivar">

                      <input type="hidden"
                             name="id"
                             value="<?php echo $servicio["id"]; ?>">

                      <button type="submit"
                              class="btn btn-outline-gold btn-sm">
                        Reactivar
                      </button>

                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>

        </table>
      </div>

    </div>

  </div>

<?php endif; ?>

    </section>

</main>

<!-- MODAL NUEVO SERVICIO -->

<div class="modal fade"
     id="crearServicioModal"
     tabindex="-1"
     aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content admin-message-modal">

      <form action="../../controllers/AdminServicioController.php"
            method="POST">

        <input type="hidden"
               name="accion"
               value="crear">

        <div class="modal-header">
          <h5 class="modal-title">
            Nuevo servicio
          </h5>

          <button type="button"
                  class="btn-close btn-close-white"
                  data-bs-dismiss="modal">
          </button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">
              Nombre del servicio
            </label>

            <input type="text"
                   name="nombre"
                   class="form-control"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Descripción
            </label>

            <textarea name="descripcion"
                      class="form-control"
                      rows="4"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Precio
            </label>

            <input type="number"
                   name="precio"
                   class="form-control"
                   min="0"
                   step="100"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Duración (minutos)
            </label>

            <input type="number"
                  name="duracion_min"
                  class="form-control"
                  min="15"
                  step="15"
                  value="30"
                  required>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input"
                   type="checkbox"
                   name="activo"
                   id="crearServicioActivo"
                   checked>

            <label class="form-check-label"
                   for="crearServicioActivo">
              Servicio activo
            </label>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-gold"
                  data-bs-dismiss="modal">
            Cancelar
          </button>

          <button type="submit"
                  class="btn btn-gold">
            Crear servicio
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>