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
    $sql = "SELECT id, nombre, email, asunto, mensaje, creado_en
            FROM contactos
            ORDER BY creado_en DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensajes = [];
    $errorDB = true;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mensajes | Panel Admin</title>

  <link rel="icon" type="image/png" href="../assets/img/favicon.png?v=2">
  <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png?v=2">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

  <main class="admin-layout">

    <aside class="admin-sidebar">
      <div>
        <div class="admin-brand">
          <img src="../assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" class="brand-logo brand-logo-admin" width="800" height="298">
        </div>

        <nav class="admin-menu">
          <a href="panel_admin.php" class="admin-menu-link">
            <i class="bi bi-speedometer2"></i>
            Dashboard
          </a>

          <a href="turnos.php" class="admin-menu-link">
            <i class="bi bi-calendar-check"></i>
            Turnos
          </a>

          <a href="mensajes.php" class="admin-menu-link active">
            <i class="bi bi-envelope"></i>
            Mensajes
          </a>

          <a href="servicios.php" class="admin-menu-link">
            <i class="bi bi-scissors"></i>
            Servicios
          </a>

          <a href="barberos.php" class="admin-menu-link">
            <i class="bi bi-person-badge"></i>
            Barberos
          </a>

          <a href="sedes.php" class="admin-menu-link">
          <i class="bi bi-geo-alt"></i>
          Sedes
        </a>

          <a href="usuarios.php" class="admin-menu-link">
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
          <p class="section-eyebrow mb-1">Panel administrativo</p>
          <h1 class="admin-title">Mensajes</h1>
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

      <section class="admin-section-card">

    <div class="admin-section-header">
        <div>
            <h3>Mensajes de contacto</h3>
            <p>Historial de consultas recibidas por el antiguo formulario. Ahora la atención es por WhatsApp.</p>
        </div>
    </div>

    <?php if ($success === "eliminado"): ?>
        <div class="alert mb-4"
             style="background:#0d2418;border:1px solid #2a6644;color:#7ecba1;border-radius:2px">
            <i class="bi bi-check2-circle me-2"></i>
            Mensaje eliminado correctamente.
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert mb-4"
             style="background:#2a1111;border:1px solid #8b1a1a;color:#f0ede8;border-radius:2px">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No se pudo eliminar el mensaje.
        </div>
    <?php endif; ?>

    <?php if (!empty($errorDB)): ?>

        <div class="admin-empty-state">
            <i class="bi bi-exclamation-triangle"></i>
            <p>Hubo un error al cargar los mensajes.</p>
        </div>

    <?php elseif (empty($mensajes)): ?>

        <div class="admin-empty-state">
            <i class="bi bi-inbox"></i>
            <p>Todavía no hay mensajes recibidos.</p>
        </div>

    <?php else: ?>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Asunto</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

              <tbody>
                <?php foreach ($mensajes as $mensaje): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($mensaje["nombre"]); ?></td>
                    <td><?php echo htmlspecialchars($mensaje["email"]); ?></td>
                    <td>
                      <?php
                        echo !empty($mensaje["asunto"])
                          ? htmlspecialchars($mensaje["asunto"])
                          : "Sin asunto";
                      ?>
                    </td>
                    <td class="admin-message-preview">
                      <?php echo htmlspecialchars($mensaje["mensaje"]); ?>
                    </td>
                    <td>
<?php echo htmlspecialchars($mensaje["creado_en"]); ?>
                    </td>
                    <td>
                        <div class="admin-actions">
                            <button
                            type="button"
                            class="btn btn-outline-gold btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#mensajeModal<?php echo $mensaje["id"]; ?>">
                            Ver
                            </button>

                            <a
                            href="mailto:<?php echo htmlspecialchars($mensaje["email"]); ?>?subject=Respuesta%20Haedo%20Barber"
                            class="btn btn-gold btn-sm">
                            Responder
                            </a>

                            <button
                            type="button"
                            class="btn btn-sm admin-btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal<?php echo $mensaje["id"]; ?>">
                            Eliminar
                            </button>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <?php foreach ($mensajes as $mensaje): ?>

            <div class="modal fade" id="mensajeModal<?php echo $mensaje["id"]; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content admin-message-modal">

                    <div class="modal-header">
                        <h5 class="modal-title">
                        Mensaje de <?php echo htmlspecialchars($mensaje["nombre"]); ?>
                        </h5>

                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($mensaje["email"]); ?></p>

                        <p>
                        <strong>Asunto:</strong>
                        <?php echo !empty($mensaje["asunto"]) ? htmlspecialchars($mensaje["asunto"]) : "Sin asunto"; ?>
                        </p>

                        <p><strong>Fecha:</strong> <?php echo htmlspecialchars($mensaje["creado_en"]); ?></p>

                        <hr>

                        <p class="admin-message-full">
                        <?php echo nl2br(htmlspecialchars($mensaje["mensaje"])); ?>
                        </p>
                    </div>

                    <div class="modal-footer">
                        <a 
                        href="mailto:<?php echo htmlspecialchars($mensaje["email"]); ?>?subject=Respuesta%20Haedo%20Barber"
                        class="btn btn-gold">
                        Responder por email
                        </a>

                        <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                        Cerrar
                        </button>
                    </div>

                    </div>
                </div>
            </div>

            <div class="modal fade"
                id="deleteModal<?php echo $mensaje["id"]; ?>"
                tabindex="-1"
                aria-hidden="true">

                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content admin-message-modal">

                    <div class="modal-header">
                        <h5 class="modal-title">
                        Confirmar eliminación
                        </h5>

                        <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                        </button>
                    </div>

                    <div class="modal-body text-center">
                        <i class="bi bi-trash3-fill delete-icon"></i>

                        <h4 class="delete-title">
                        ¿Deseás eliminar este mensaje?
                        </h4>

                        <p class="delete-text">
                        Esta acción no se puede deshacer.
                        </p>
                    </div>

                    <div class="modal-footer justify-content-center">

                        <button
                        type="button"
                        class="btn btn-outline-gold"
                        data-bs-dismiss="modal">
                        Cancelar
                        </button>

                        <form
                        action="../../controllers/AdminMensajeController.php"
                        method="POST"><?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo $mensaje["id"]; ?>">

                        <button
                            type="submit"
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

      </section>

    </section>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/panel.js"></script>

</body>
</html>
