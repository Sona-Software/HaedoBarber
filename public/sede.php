<?php
require_once '../config/session_check.php';

$sedeId = $_GET["id"] ?? "";

if ($sedeId === "" || !is_numeric($sedeId)) {
    header("Location: index.php");
    exit;
}

$sede = null;
$sedes = [];
$barberos = [];
$galeriaSede = [];
$galeriasJS = [];

function rutaPublicaFoto(string $foto): string {
    if (str_starts_with($foto, 'img/')) return 'assets/' . substr($foto, 4);
    if (str_starts_with($foto, 'assets/')) return $foto;
    return '../' . ltrim($foto, '/');
}

$linkReserva = isset($_SESSION["usuario_id"]) ? "user/reservar_turno.php" : "login.php";

try {
    $sqlSedes = "SELECT *
                 FROM sedes
                 WHERE activo = 1
                 ORDER BY id ASC";

    $stmtSedes = $conn->prepare($sqlSedes);
    $stmtSedes->execute();
    $sedes = $stmtSedes->fetchAll(PDO::FETCH_ASSOC);

    $sqlSede = "SELECT *
                FROM sedes
                WHERE id = :id
                AND activo = 1
                LIMIT 1";

    $stmtSede = $conn->prepare($sqlSede);
    $stmtSede->bindParam(":id", $sedeId);
    $stmtSede->execute();
    $sede = $stmtSede->fetch(PDO::FETCH_ASSOC);

    if (!$sede) {
        header("Location: index.php");
        exit;
    }

    $sqlGaleriaSede = "SELECT *
                       FROM sede_galeria
                       WHERE sede_id = :sede_id
                       AND activo = 1
                       ORDER BY id ASC";

    $stmtGaleriaSede = $conn->prepare($sqlGaleriaSede);
    $stmtGaleriaSede->bindParam(":sede_id", $sedeId);
    $stmtGaleriaSede->execute();
    $galeriaSede = $stmtGaleriaSede->fetchAll(PDO::FETCH_ASSOC);

    $galeriasJS["sede_" . $sedeId] = [];

    foreach ($galeriaSede as $foto) {
        $galeriasJS["sede_" . $sedeId][] = rutaPublicaFoto($foto["foto"]);
    }

    $sqlBarberos = "SELECT *
                    FROM barberos
                    WHERE sede_id = :sede_id
                    AND activo = 1
                    ORDER BY nombre ASC";

    $stmtBarberos = $conn->prepare($sqlBarberos);
    $stmtBarberos->bindParam(":sede_id", $sedeId);
    $stmtBarberos->execute();
    $barberos = $stmtBarberos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($barberos as $index => $barbero) {
        $sqlFotos = "SELECT *
                     FROM barbero_fotos
                     WHERE barbero_id = :barbero_id
                     AND activo = 1
                     ORDER BY id ASC";

        $stmtFotos = $conn->prepare($sqlFotos);
        $stmtFotos->bindParam(":barbero_id", $barbero["id"]);
        $stmtFotos->execute();

        $fotos = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);
        $barberos[$index]["fotos"] = $fotos;

        $galeriaBarbero = "barbero_" . $barbero["id"];
        $galeriasJS[$galeriaBarbero] = [];

        foreach ($fotos as $foto) {
            $galeriasJS[$galeriaBarbero][] = rutaPublicaFoto($foto["foto"]);
        }
    }

} catch (PDOException $e) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

  <title><?php echo htmlspecialchars($sede["nombre"]); ?> | Haedo Barber</title>

  <link rel="icon" type="image/svg+xml" href="assets/img/haedobarber-mark.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet"
        href="assets/css/style.css">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top scrolled" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span class="brand-el">HAEDO</span><span class="brand-filo"> BARBER</span>
    </a>

    <button class="navbar-toggler border-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu">
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
          <a class="nav-link dropdown-toggle"
             href="#"
             role="button"
             data-bs-toggle="dropdown"
             data-bs-auto-close="true"
             aria-expanded="false"
             style="color:var(--gold)!important">
            Sedes
          </a>

          <ul class="dropdown-menu">
            <?php foreach ($sedes as $itemSede): ?>
              <li>
                <a class="dropdown-item"
                   href="sede.php?id=<?php echo $itemSede["id"]; ?>"
                   <?php if ($itemSede["id"] == $sedeId): ?>
                     style="color:var(--gold)!important"
                   <?php endif; ?>>
                  <i class="bi bi-geo-alt-fill"></i>
                  <?php echo htmlspecialchars($itemSede["nombre"]); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="contacto.php">Contacto</a>
        </li>

        <?php if (isset($_SESSION["usuario_id"])): ?>

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

<div class="sede-header">
  <div class="container">
    <div class="sede-tag">
      <i class="bi bi-geo-alt-fill"></i>
      Sede <?php echo str_pad($sede["id"], 2, "0", STR_PAD_LEFT); ?>
    </div>

    <p class="section-eyebrow mb-2">
      Haedo Barber
    </p>

    <h1 class="section-title mb-0">
      <?php echo htmlspecialchars($sede["nombre"]); ?>
    </h1>

    <div class="sede-info-strip">
      <span class="sede-info-item">
        <i class="bi bi-geo-alt-fill"></i>
        <?php echo htmlspecialchars($sede["direccion"]); ?>
      </span>

      <span class="sede-info-item">
        <i class="bi bi-clock-fill"></i>
        Mar–Sáb 10:30–13:00 / 14:30–20:00
      </span>

      <span class="sede-info-item">
        <i class="bi bi-telephone-fill"></i>
        +54 9 11 5126-7271
      </span>
    </div>
  </div>
</div>

<section class="barbers-section py-section">
  <div class="container">

    <div class="text-center mb-5">
      <p class="section-eyebrow">
        El equipo de <?php echo htmlspecialchars($sede["nombre"]); ?>
      </p>

      <h2 class="section-title">
        Nuestros <span class="text-gold">Barberos</span>
      </h2>

      <p style="color:var(--muted);font-size:14px;max-width:500px;margin:0 auto">
        Cada barbero tiene su estilo. Elegí el tuyo y reservá tu turno directo con él.
      </p>
    </div>

    <div class="row g-5 justify-content-center">

      <?php if (empty($barberos)): ?>

        <div class="col-lg-8">
          <div class="admin-empty-state">
            <i class="bi bi-person-badge"></i>
            <p>Próximamente vas a poder conocer al equipo de esta sede.</p>
          </div>
        </div>

      <?php else: ?>

        <?php foreach ($barberos as $barbero): ?>

          <?php
            $fotos = $barbero["fotos"] ?? [];
            $totalFotos = count($fotos);
            $galeriaNombre = "barbero_" . $barbero["id"];
          ?>

          <div class="col-lg-6">
            <div class="barber-card">
              <div class="row g-0">

                <div class="col-5">
                  <div class="barber-img-wrap" style="height:100%">
                    <?php
                    $fotoBarbero = $barbero["foto"] ?? '';
                    $rutaFoto = str_starts_with($fotoBarbero, 'img/') ? 'assets/' . substr($fotoBarbero, 4) : (str_starts_with($fotoBarbero, 'assets/') ? $fotoBarbero : (!empty($fotoBarbero) ? '../' . $fotoBarbero : 'assets/img/haedobarber-mark.svg'));
                    ?>
                    <img src="../<?php echo $rutaFoto; ?>"
                        alt="<?php echo htmlspecialchars($barbero["nombre"]); ?>"
                        class="barber-img"
                        style="height:100%;min-height:260px">

                    <div class="barber-overlay">
                      <a href="<?php echo $linkReserva; ?>" class="btn btn-gold btn-sm">Reservar</a>
                    </div>
                  </div>
                </div>

                <div class="col-7 ps-4 py-3 d-flex flex-column">
                  <div class="barber-info mb-3">
                    <h4 class="barber-name">
                      <?php echo htmlspecialchars($barbero["nombre"]); ?>
                    </h4>

                    <span class="barber-spec">
                      <?php echo htmlspecialchars($barbero["especialidad"]); ?>
                    </span>
                  </div>

                  <p style="font-size:12px;color:var(--muted);line-height:1.6;flex:1">
                    Especialista en cortes modernos, detalles personalizados y atención profesional.
                  </p>

                  <?php if (!empty($fotos)): ?>

                    <div class="barber-cuts-grid mt-2">

                      <?php foreach (array_slice($fotos, 0, 3) as $index => $foto): ?>

                        <?php $esVerMas = ($index === 2 && $totalFotos > 3); ?>

                        <div class="barber-cut-thumb <?php echo $esVerMas ? 'barber-cut-more' : ''; ?>"
                             data-gallery="<?php echo $galeriaNombre; ?>"
                             data-index="<?php echo $index; ?>">

                          <img src="<?php echo htmlspecialchars(rutaPublicaFoto($foto["foto"])); ?>"
                               alt="Trabajo <?php echo $index + 1; ?>">

                          <?php if ($esVerMas): ?>

                            <div class="barber-cut-more-overlay">
                              <i class="bi bi-images"></i>
                              <span>Ver más</span>
                            </div>

                          <?php else: ?>

                            <div class="barber-cut-overlay">
                              <i class="bi bi-zoom-in"></i>
                            </div>

                          <?php endif; ?>

                        </div>

                      <?php endforeach; ?>

                    </div>

                  <?php endif; ?>

                  <a href="<?php echo $linkReserva; ?>"
                     class="btn btn-outline-gold btn-sm mt-3"
                     style="align-self:flex-start">
                    Reservar turno <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>

              </div>
            </div>
          </div>

        <?php endforeach; ?>

      <?php endif; ?>

    </div>
  </div>
</section>

<section class="gallery-section py-section">
  <div class="container">

    <div class="text-center mb-5">
      <p class="section-eyebrow">
        Trabajos de la sede
      </p>

      <h2 class="section-title">
        Galería <span class="text-gold"><?php echo htmlspecialchars($sede["nombre"]); ?></span>
      </h2>
    </div>

    <?php if (empty($galeriaSede)): ?>

      <div class="admin-empty-state">
        <i class="bi bi-images"></i>
        <p>Esta sede todavía no tiene fotos cargadas en la galería.</p>
      </div>

    <?php else: ?>

      <div class="gallery-grid">

        <?php foreach (array_slice($galeriaSede, 0, 6) as $index => $foto): ?>

          <?php $esVerMasGaleria = ($index === 5 && count($galeriaSede) > 6); ?>

          <div class="gallery-item <?php echo $esVerMasGaleria ? 'gallery-more' : ''; ?>"
               data-gallery="sede_<?php echo $sedeId; ?>"
               data-index="<?php echo $index; ?>">

            <img src="<?php echo htmlspecialchars(rutaPublicaFoto($foto["foto"])); ?>"
                 alt="Trabajo sede <?php echo $index + 1; ?>">

            <?php if ($esVerMasGaleria): ?>

              <div class="gallery-more-overlay">
                <i class="bi bi-images gallery-more-icon"></i>
                <span class="gallery-more-text">Ver más</span>
                <small class="gallery-more-subtext">Explorar galería</small>
              </div>

            <?php else: ?>

              <div class="gallery-hover">
                <i class="bi bi-zoom-in"></i>
              </div>

            <?php endif; ?>

          </div>

        <?php endforeach; ?>

      </div>

    <?php endif; ?>

  </div>
</section>

<section style="background:var(--bg2);padding:60px 0">
  <div class="container">
    <div class="row align-items-center g-5">

      <div class="col-lg-5">
        <p class="section-eyebrow">Cómo llegar</p>

        <h2 class="section-title">
          <?php echo htmlspecialchars($sede["nombre"]); ?>
        </h2>

        <ul class="footer-contact-list mt-3">
          <li>
            <i class="bi bi-geo-alt-fill"></i>
            <?php echo htmlspecialchars($sede["direccion"]); ?>
          </li>

          <li>
            <i class="bi bi-clock-fill"></i>
            Mar–Sáb 10:30–13:00 / 14:30–20:00
          </li>

          <li>
            <i class="bi bi-telephone-fill"></i>
            +54 9 11 5126-7271
          </li>
        </ul>

        <a href="<?php echo $linkReserva; ?>" class="btn btn-gold mt-4">
          Reservar en esta sede
        </a>
      </div>

      <div class="col-lg-7">

        <div class="sede-map-wrap">

          <?php if (!empty($sede["mapa_embed"])): ?>

            <?php echo $sede["mapa_embed"]; ?>

          <?php else: ?>

            <div class="sede-map-empty">
              <div>
                <i class="bi bi-geo-alt-fill"></i>

                <p>
                  Mapa pendiente de configuración para esta sede.
                </p>
              </div>
            </div>

          <?php endif; ?>

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
          <span class="brand-el">HAEDO</span><span class="brand-filo"> BARBER</span>
        </div>

        <p class="footer-tagline">El arte del filo, la precisión del corte.</p>

        <div class="footer-social">
          <?php if (!empty($_ENV['INSTAGRAM_URL'])): ?><a href="<?php echo htmlspecialchars($_ENV['INSTAGRAM_URL'], ENT_QUOTES, 'UTF-8'); ?>"
             target="_blank"
             aria-label="Instagram">
            <i class="bi bi-instagram"></i>
          </a><?php endif; ?>

          <?php if (!empty($_ENV['TIKTOK_URL'])): ?><a href="<?php echo htmlspecialchars($_ENV['TIKTOK_URL'], ENT_QUOTES, 'UTF-8'); ?>"
             target="_blank"
             aria-label="TikTok">
            <i class="bi bi-tiktok"></i>
          </a><?php endif; ?>

          <?php if (!empty($_ENV['WHATSAPP_URL'])): ?><a href="<?php echo htmlspecialchars($_ENV['WHATSAPP_URL'], ENT_QUOTES, 'UTF-8'); ?>"
             target="_blank"
             aria-label="WhatsApp">
            <i class="bi bi-whatsapp"></i>
          </a><?php endif; ?>
        </div>
      </div>

      <div class="col-sm-6 col-lg-2 offset-lg-1">
        <h5 class="footer-heading">Sitio</h5>

        <ul class="footer-links">
          <li><a href="index.php">Inicio</a></li>
          <li><a href="index.php#servicios">Servicios</a></li>

          <?php foreach ($sedes as $itemSede): ?>
            <li>
              <a href="sede.php?id=<?php echo $itemSede["id"]; ?>">
                <?php echo htmlspecialchars($itemSede["nombre"]); ?>
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
            <li><a href="terminos.html">Términos y condiciones</a></li>
          <?php else: ?>
            <li><a href="registro.php">Registrarse</a></li>
            <li><a href="login.php">Ingresar</a></li>
            <li><a href="terminos.html">Términos y condiciones</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="col-lg-3">
        <h5 class="footer-heading">Contacto</h5>

        <ul class="footer-contact-list">
          <?php foreach ($sedes as $itemSede): ?>
            <li>
              <i class="bi bi-geo-alt-fill"></i>
              <?php echo htmlspecialchars($itemSede["direccion"]); ?>
            </li>
          <?php endforeach; ?>

          <li>
            <i class="bi bi-clock-fill"></i>
            Mar–Sáb 10:30–13:00 / 14:30–20:00
          </li>

          <li>
            <i class="bi bi-telephone-fill"></i>
            <?php if (!empty($_ENV['WHATSAPP_URL'])): ?><a href="<?php echo htmlspecialchars($_ENV['WHATSAPP_URL'], ENT_QUOTES, 'UTF-8'); ?>">Contactanos por WhatsApp</a><?php endif; ?>
          </li>

          <li>
            <i class="bi bi-envelope-fill"></i>
            <?php echo !empty($_ENV['CONTACT_EMAIL']) ? htmlspecialchars($_ENV['CONTACT_EMAIL']) : 'Contacto mediante formulario'; ?>
          </li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p>© 2026 Haedo Barber · Todos los derechos reservados</p>
    </div>
  </div>
</footer>

<div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content gallery-modal-content">

      <button type="button"
              class="btn-close btn-close-white gallery-modal-close"
              data-bs-dismiss="modal"
              aria-label="Cerrar">
      </button>

      <div class="modal-body p-0">

        <div id="imageGalleryCarousel" class="carousel slide">
          <div class="carousel-inner" id="galleryCarouselInner"></div>

          <button class="carousel-control-prev"
                  type="button"
                  data-bs-target="#imageGalleryCarousel"
                  data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>

          <button class="carousel-control-next"
                  type="button"
                  data-bs-target="#imageGalleryCarousel"
                  data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>

        <div class="gallery-counter" id="galleryCounter"></div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const galleries = <?php echo json_encode($galeriasJS); ?>;

const modalElement = document.getElementById("imageGalleryModal");
const carouselElement = document.getElementById("imageGalleryCarousel");
const carouselInner = document.getElementById("galleryCarouselInner");
const galleryCounter = document.getElementById("galleryCounter");

let activeGallery = [];

function openGallery(galleryName, startIndex) {
  activeGallery = galleries[galleryName] || [];

  if (activeGallery.length === 0) {
    return;
  }

  carouselInner.innerHTML = "";

  activeGallery.forEach((imageSrc, index) => {
    const item = document.createElement("div");

    item.classList.add("carousel-item");

    if (index === startIndex) {
      item.classList.add("active");
    }

    item.innerHTML = `
      <img src="${imageSrc}"
           class="d-block w-100 gallery-modal-img"
           alt="Foto ${index + 1}">
    `;

    carouselInner.appendChild(item);
  });

  updateCounter(startIndex);

  const modal = new bootstrap.Modal(modalElement);
  modal.show();

  const carousel = bootstrap.Carousel.getOrCreateInstance(
    carouselElement,
    {
      interval: false,
      ride: false
    }
  );

  carousel.to(startIndex);
}

function updateCounter(index) {
  galleryCounter.textContent =
    `${index + 1} / ${activeGallery.length}`;
}

document
.querySelectorAll("[data-gallery]")
.forEach(item => {
  item.addEventListener("click", () => {
    const galleryName = item.dataset.gallery;
    const startIndex = Number(item.dataset.index);

    openGallery(galleryName, startIndex);
  });
});

carouselElement.addEventListener(
  "slid.bs.carousel",
  event => {
    updateCounter(event.to);
  }
);
</script>

<script src="assets/js/main.js"></script>

</body>
</html>
