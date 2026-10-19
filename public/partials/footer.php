<?php
/**
 * Footer público reutilizable. Requiere config/site.php (getters sitio_*) y session_check.php.
 * Se incluye con: require __DIR__ . '/partials/footer.php';
 */
$hbLogueado = isset($_SESSION['usuario_id']);
$hbEsAdmin = $hbLogueado && ($_SESSION['usuario_rol'] ?? '') === 'admin';
$hbDireccion = sitio_direccion();
$hbWhatsUrl = sitio_whatsapp_url();
$hbTel = sitio_telefono();
$hbHorario = sitio_horario();
$hbRedes = [
    ['Instagram', 'instagram', sitio_instagram_url()],
    ['TikTok', 'tiktok', sitio_tiktok_url()],
    ['WhatsApp', 'whatsapp', $hbWhatsUrl],
];
?>
<footer class="site-footer hb-footer">
  <div class="container">
    <div class="hb-footer-grid">

      <div class="hb-footer-brand">
        <a href="index.php" class="hb-footer-logo" aria-label="Haedo Barber, inicio">
          <img src="assets/img/logo-haedobarber.png?v=2" alt="Haedo Barber" width="800" height="298">
        </a>
        <ul class="hb-footer-social" aria-label="Redes sociales">
          <?php foreach ($hbRedes as [$nombre, $icono, $href]): if ($href === '') { continue; } ?>
            <li><a href="<?php echo sitio_h($href); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo sitio_h($nombre); ?>"><i class="bi bi-<?php echo $icono; ?>" aria-hidden="true"></i></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <nav class="hb-footer-col" aria-label="Navegación del pie">
        <h2 class="hb-footer-title">Navegación</h2>
        <ul class="hb-footer-list">
          <li><a href="index.php">Inicio</a></li>
          <li><a href="index.php#servicios">Servicios</a></li>
          <li><a href="contacto.php">Contacto</a></li>
          <li><a href="user/reservar_turno.php">Reservar turno</a></li>
          <?php if ($hbLogueado): ?>
            <li><a href="<?php echo $hbEsAdmin ? 'admin/panel_admin.php' : 'user/panel_usuario.php'; ?>"><?php echo $hbEsAdmin ? 'Panel admin' : 'Mi cuenta'; ?></a></li>
          <?php else: ?>
            <li><a href="login.php">Ingresar</a></li>
          <?php endif; ?>
          <li><a href="terminos.html">Términos y condiciones</a></li>
          <?php if ($hbLogueado && function_exists('csrf_field')): ?>
            <li>
              <form action="../controllers/AuthController.php" method="POST"><?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit">Cerrar sesión</button>
              </form>
            </li>
          <?php endif; ?>
        </ul>
      </nav>

      <?php if ($hbDireccion !== '' || $hbWhatsUrl !== '' || $hbTel !== '' || $hbHorario !== ''): ?>
      <div class="hb-footer-col">
        <h2 class="hb-footer-title">Visitanos</h2>
        <ul class="hb-footer-info">
          <?php if ($hbDireccion !== ''): ?>
            <li><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><a href="<?php echo sitio_h(sitio_direccion_url()); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h($hbDireccion); ?></a></li>
          <?php endif; ?>
          <?php if ($hbWhatsUrl !== ''): ?>
            <li><i class="bi bi-whatsapp" aria-hidden="true"></i><a href="<?php echo sitio_h($hbWhatsUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo sitio_h($hbTel !== '' ? $hbTel : 'WhatsApp'); ?></a></li>
          <?php elseif ($hbTel !== ''): ?>
            <li><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:<?php echo sitio_h(sitio_telefono_tel()); ?>"><?php echo sitio_h($hbTel); ?></a></li>
          <?php endif; ?>
          <?php if ($hbHorario !== ''): ?>
            <li><i class="bi bi-clock-fill" aria-hidden="true"></i><span><?php echo sitio_h($hbHorario); ?></span></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>

    </div>

    <div class="hb-footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Haedo Barber</p>
    </div>
  </div>
</footer>
