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

$nombreAdmin = $_SESSION["usuario_nombre"];
$emailAdmin = $_SESSION["usuario_email"];
$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

try {
    $sqlSedes = "SELECT * FROM sedes ORDER BY nombre ASC";
    $stmtSedes = $conn->prepare($sqlSedes);
    $stmtSedes->execute();
    $sedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);

    $sqlActivos = "SELECT barberos.*, sedes.nombre AS sede_nombre
                   FROM barberos
                   INNER JOIN sedes ON barberos.sede_id = sedes.id
                   WHERE barberos.activo = 1
                   ORDER BY sedes.nombre ASC, barberos.nombre ASC";

    $stmtActivos = $conn->prepare($sqlActivos);
    $stmtActivos->execute();
    $barberosActivos = $stmtActivos->fetchAll(PDO::FETCH_ASSOC);

    $sqlInactivos = "SELECT barberos.*, sedes.nombre AS sede_nombre
                     FROM barberos
                     INNER JOIN sedes ON barberos.sede_id = sedes.id
                     WHERE barberos.activo = 0
                     ORDER BY sedes.nombre ASC, barberos.nombre ASC";

    $stmtInactivos = $conn->prepare($sqlInactivos);
    $stmtInactivos->execute();
    $barberosInactivos = $stmtInactivos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $sedes = [];
    $barberosActivos = [];
    $barberosInactivos = [];
    $errorDB = true;
}

function obtenerRutaFoto($foto) {
    if (empty($foto)) {
        return "../assets/img/apple-touch-icon.png";
    }

    if (str_starts_with($foto, "img/")) return "../assets/" . substr($foto, 4);
    if (str_starts_with($foto, "assets/")) return "../" . $foto;
    return "../../" . $foto;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

  <title>Barberos | Panel Admin</title>

  <link rel="icon" type="image/png" href="../assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png?v=2">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet"
        href="../assets/css/style.css?v=4">
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
           class="admin-menu-link active">
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
        <p class="section-eyebrow mb-1">
          Panel administrativo
        </p>

        <h1 class="admin-title">
          Barberos
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
          <h3>Equipo de barberos</h3>

          <p>
            Gestioná fotos, nombres, especialidades y sedes asignadas.
          </p>
        </div>

        <button type="button"
                class="btn btn-gold"
                style="margin-top: 12px;"
                data-bs-toggle="modal"
                data-bs-target="#crearBarberoModal">
          Nuevo barbero
        </button>

      </div>

      <?php if ($success === "creado"): ?>
        <div class="alert mb-4"
             style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
          <i class="bi bi-check2-circle me-2"></i>
          Barbero creado correctamente.
        </div>
      <?php endif; ?>

      <?php if ($success === "editado"): ?>
        <div class="alert mb-4"
             style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
          <i class="bi bi-check2-circle me-2"></i>
          Barbero editado correctamente.
        </div>
      <?php endif; ?>

      <?php if ($success === "desactivado"): ?>
        <div class="alert mb-4"
             style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
          <i class="bi bi-check2-circle me-2"></i>
          Barbero desactivado correctamente.
        </div>
      <?php endif; ?>

      <?php if ($success === "reactivado"): ?>
        <div class="alert mb-4"
             style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
          <i class="bi bi-check2-circle me-2"></i>
          Barbero reactivado correctamente.
        </div>
      <?php endif; ?>

      <?php if ($error === "foto_invalida"): ?>
        <div class="alert mb-4"
             style="background:#2a1111;border:1px solid #8b1a1a;color:#ff8b8b;border-radius:2px">
          <i class="bi bi-exclamation-triangle me-2"></i>
          La imagen no es válida. Usá JPG, JPEG, PNG o WEBP.
        </div>
      <?php endif; ?>

      <?php if (!empty($errorDB)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-exclamation-triangle"></i>
          <p>Error al cargar barberos.</p>
        </div>

      <?php elseif (empty($barberosActivos)): ?>

        <div class="admin-empty-state">
          <i class="bi bi-person-badge"></i>
          <p>No hay barberos activos cargados.</p>
          <small>Usá el botón «Nuevo barbero» para sumar al primero.</small>
        </div>

      <?php else: ?>

        <div class="admin-barbers-grid">

          <?php foreach ($barberosActivos as $barbero): ?>

            <article class="admin-barber-card">

              <div class="admin-barber-image">
                <img src="<?php echo htmlspecialchars(obtenerRutaFoto($barbero["foto"])); ?>"
                     alt="<?php echo htmlspecialchars($barbero["nombre"]); ?>">
              </div>

              <div class="admin-barber-content">

                <span class="admin-badge-active">
                  Activo
                </span>

                <h4>
                  <?php echo htmlspecialchars($barbero["nombre"]); ?>
                </h4>

                <p class="admin-barber-specialty">
                  <?php echo htmlspecialchars($barbero["especialidad"] ?? ""); ?>
                </p>

                <p class="admin-barber-branch">
                  <i class="bi bi-geo-alt-fill"></i>
                  <?php echo htmlspecialchars($barbero["sede_nombre"]); ?>
                </p>

                <?php

                $sqlFotos = "SELECT *
                            FROM barbero_fotos
                            WHERE barbero_id = :barbero_id
                            AND activo = 1
                            ORDER BY id DESC";

                $stmtFotos = $conn->prepare($sqlFotos);
                $stmtFotos->bindParam(":barbero_id", $barbero["id"]);
                $stmtFotos->execute();

                $fotosBarbero = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

                ?>

                <div class="admin-barber-gallery">

                <div class="admin-barber-gallery-header">
                    <span>Trabajos del barbero</span>
                </div>

                <?php if (!empty($fotosBarbero)): ?>

                    <div class="admin-barber-gallery-grid">

                    <?php foreach ($fotosBarbero as $foto): ?>

                        <div class="admin-barber-mini-photo">

                        <img src="<?php echo htmlspecialchars(obtenerRutaFoto($foto["foto"])); ?>"
                            alt="Trabajo barbero">

                        <form action="../../controllers/AdminBarberoController.php"
                                method="POST"><?= csrf_field() ?>

                            <input type="hidden"
                                name="accion"
                                value="eliminar_foto">

                            <input type="hidden"
                                name="foto_id"
                                value="<?php echo $foto["id"]; ?>">

                            <input type="hidden"
                                name="foto_ruta"
                                value="<?php echo htmlspecialchars($foto["foto"]); ?>">

                            <button type="submit"
                                    class="admin-mini-delete">
                            <i class="bi bi-trash"></i>
                            </button>

                        </form>

                        </div>

                    <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <form action="../../controllers/AdminBarberoController.php"
                        method="POST"
                        enctype="multipart/form-data"
                        class="admin-upload-mini-form"><?= csrf_field() ?>

                    <input type="hidden"
                        name="accion"
                        value="agregar_foto">

                    <input type="hidden"
                        name="barbero_id"
                        value="<?php echo $barbero["id"]; ?>">

                    <input type="file"
                        name="foto"
                        class="form-control form-control-sm"
                        accept=".jpg,.jpeg,.png,.webp"
                        required>

                    <button type="submit"
                            class="btn btn-outline-gold btn-sm w-100 mt-2">
                    Agregar mini foto
                    </button>

                </form>

                </div>

                <div class="admin-barber-actions">

                  <button type="button"
                          class="btn btn-outline-gold btn-sm"
                          data-bs-toggle="modal"
                          data-bs-target="#editarBarberoModal<?php echo $barbero["id"]; ?>">
                    Editar
                  </button>

                  <button type="button"
                          class="btn admin-btn-danger"
                          data-bs-toggle="modal"
                          data-bs-target="#desactivarBarberoModal<?php echo $barbero["id"]; ?>">
                    Eliminar
                  </button>

                </div>

              </div>

            </article>

            <!-- MODAL EDITAR BARBERO -->
            <div class="modal fade"
                 id="editarBarberoModal<?php echo $barbero["id"]; ?>"
                 tabindex="-1"
                 aria-hidden="true">

              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content admin-message-modal">

                  <form action="../../controllers/AdminBarberoController.php"
                        method="POST"
                        enctype="multipart/form-data"><?= csrf_field() ?>

                    <input type="hidden"
                           name="accion"
                           value="editar">

                    <input type="hidden"
                           name="id"
                           value="<?php echo $barbero["id"]; ?>">

                    <input type="hidden"
                           name="foto_actual"
                           value="<?php echo htmlspecialchars($barbero["foto"] ?? ""); ?>">

                    <div class="modal-header">
                      <h5 class="modal-title">
                        Editar barbero
                      </h5>

                      <button type="button"
                              class="btn-close btn-close-white"
                              data-bs-dismiss="modal">
                      </button>
                    </div>

                    <div class="modal-body">

                      <div class="admin-current-photo mb-4">
                        <img src="<?php echo htmlspecialchars(obtenerRutaFoto($barbero["foto"])); ?>"
                             alt="<?php echo htmlspecialchars($barbero["nombre"]); ?>">

                        <div>
                          <strong>Foto actual</strong>                        
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">
                          Nombre
                        </label>

                        <input type="text"
                               name="nombre"
                               class="form-control"
                               value="<?php echo htmlspecialchars($barbero["nombre"]); ?>"
                               required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">
                          Especialidad
                        </label>

                        <input type="text"
                               name="especialidad"
                               class="form-control"
                               value="<?php echo htmlspecialchars($barbero["especialidad"] ?? ""); ?>"
                               required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">
                          Sede
                        </label>

                        <select name="sede_id"
                                class="form-select"
                                required>

                          <?php foreach ($sedes as $sede): ?>
                            <option value="<?php echo $sede["id"]; ?>"
                              <?php echo ($sede["id"] == $barbero["sede_id"]) ? "selected" : ""; ?>>
                              <?php echo htmlspecialchars($sede["nombre"]); ?>
                            </option>
                          <?php endforeach; ?>

                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">
                          Cambiar foto
                        </label>

                        <input type="file"
                               name="foto"
                               class="form-control"
                               accept=".jpg,.jpeg,.png,.webp">

                        <small style="color:var(--muted);font-size:12px">
                          Formatos permitidos: JPG, JPEG, PNG y WEBP.
                        </small>
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
                        Guardar cambios
                      </button>
                    </div>

                  </form>

                </div>
              </div>
            </div>

            <!-- MODAL DESACTIVAR BARBERO -->
            <div class="modal fade"
                 id="desactivarBarberoModal<?php echo $barbero["id"]; ?>"
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
                      ¿Deseás eliminar este barbero?
                    </h4>

                    <p class="delete-text">
                      No se borrará de la base de datos, solo quedará inactivo.
                    </p>
                  </div>

                  <div class="modal-footer justify-content-center">

                    <button type="button"
                            class="btn btn-outline-gold"
                            data-bs-dismiss="modal">
                      Cancelar
                    </button>

                    <form action="../../controllers/AdminBarberoController.php"
                          method="POST"><?= csrf_field() ?>

                      <input type="hidden"
                             name="accion"
                             value="desactivar">

                      <input type="hidden"
                             name="id"
                             value="<?php echo $barbero["id"]; ?>">

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

        </div>

      <?php endif; ?>

      <?php if (!empty($barberosInactivos)): ?>

        <div class="mt-5">

          <button class="btn btn-outline-gold"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#barberosInactivos">
            Ver inactivos
          </button>

          <div class="collapse mt-4"
               id="barberosInactivos">

            <h3 class="mb-3">Barberos inactivos</h3>

            <div class="admin-table-wrap">

              <table class="admin-table">

                <thead>
                  <tr>
                    <th>Barbero</th>
                    <th>Especialidad</th>
                    <th>Sede</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>

                <tbody>

                  <?php foreach ($barberosInactivos as $barbero): ?>

                    <tr>
                      <td><?php echo htmlspecialchars($barbero["nombre"]); ?></td>
                      <td><?php echo htmlspecialchars($barbero["especialidad"] ?? ""); ?></td>
                      <td><?php echo htmlspecialchars($barbero["sede_nombre"]); ?></td>
                      <td>
                        <span class="admin-badge-inactive">
                          Inactivo
                        </span>
                      </td>
                      <td>
                        <div class="admin-actions">

                          <form action="../../controllers/AdminBarberoController.php"
                                method="POST"><?= csrf_field() ?>

                            <input type="hidden"
                                   name="accion"
                                   value="reactivar">

                            <input type="hidden"
                                   name="id"
                                   value="<?php echo $barbero["id"]; ?>">

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

  </section>

</main>

<!-- MODAL NUEVO BARBERO -->
<div class="modal fade"
     id="crearBarberoModal"
     tabindex="-1"
     aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content admin-message-modal">

      <form action="../../controllers/AdminBarberoController.php"
            method="POST"
            enctype="multipart/form-data"><?= csrf_field() ?>

        <input type="hidden"
               name="accion"
               value="crear">

        <div class="modal-header">
          <h5 class="modal-title">
            Nuevo barbero
          </h5>

          <button type="button"
                  class="btn-close btn-close-white"
                  data-bs-dismiss="modal">
          </button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">
              Nombre
            </label>

            <input type="text"
                   name="nombre"
                   class="form-control"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Especialidad
            </label>

            <input type="text"
                   name="especialidad"
                   class="form-control"
                   placeholder="Ej: Fades & Texturas"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Sede
            </label>

            <select name="sede_id"
                    class="form-select"
                    required>

              <option value="">
                Seleccionar sede
              </option>

              <?php foreach ($sedes as $sede): ?>
                <option value="<?php echo $sede["id"]; ?>">
                  <?php echo htmlspecialchars($sede["nombre"]); ?>
                </option>
              <?php endforeach; ?>

            </select>

            <?php if (empty($sedes)): ?>
              <small style="color:var(--muted);font-size:12px">
                Primero tenés que crear una sede en la sección Sedes.
              </small>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">
              Foto
            </label>

            <input type="file"
                   name="foto"
                   class="form-control"
                   accept=".jpg,.jpeg,.png,.webp">

            <small style="color:var(--muted);font-size:12px">
              Formatos permitidos: JPG, JPEG, PNG y WEBP.
            </small>
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
            Crear barbero
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/panel.js"></script>

</body>
</html>
