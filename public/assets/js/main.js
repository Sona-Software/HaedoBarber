/* ============================================================
   HAEDO BARBER — BARBERÍA | JavaScript principal
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ---- NAVBAR: agregar clase al hacer scroll ---- */
  const nav = document.getElementById('mainNav');
  if (nav) {
    const toggleNav = () => {
      nav.classList.toggle('scrolled', window.scrollY > 60);
    };
    window.addEventListener('scroll', toggleNav, { passive: true });
    toggleNav();
  }

  /* ---- ANIMACIÓN DE ENTRADA con Intersection Observer ----
     Los elementos solo se ocultan (.reveal) cuando JS corre, hay soporte de
     IntersectionObserver y no se pidió reducir movimiento. Un temporizador de
     seguridad los muestra igual si el observador nunca se dispara. */
  const reduceMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const animateEls = document.querySelectorAll(
    '.service-card, .barber-card, .gallery-item, .about-img-grid, .auth-card'
  );

  if ('IntersectionObserver' in window && !reduceMotion && animateEls.length) {
    const show = (el) => {
      el.classList.add('is-visible');
      // Al terminar la transición se quita .reveal para que los :hover propios funcionen.
      setTimeout(() => el.classList.remove('reveal', 'is-visible'), 700);
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          show(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    animateEls.forEach(el => {
      el.classList.add('reveal');
      observer.observe(el);
    });

    // Respaldo: nada debe quedar invisible si el observador falla.
    setTimeout(() => {
      animateEls.forEach(el => {
        if (el.classList.contains('reveal') && !el.classList.contains('is-visible')) {
          show(el);
        }
      });
      observer.disconnect();
    }, 4000);
  }

  /* ---- SMOOTH SCROLL para links internos ---- */
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const hash = link.getAttribute('href');
      if (!hash || hash === '#') return; // toggles de dropdown
      const target = document.querySelector(hash);
      if (!target) return;
      e.preventDefault();
      const navHeight = nav ? nav.offsetHeight : 72;
      const top = target.getBoundingClientRect().top + window.scrollY - navHeight + 1;
      window.scrollTo({ top, behavior: reduceMotion ? 'auto' : 'smooth' });

      // Cerrar navbar en mobile si está abierto
      const navCollapse = document.getElementById('navbarMenu');
      if (navCollapse && navCollapse.classList.contains('show') && window.bootstrap) {
        bootstrap.Collapse.getOrCreateInstance(navCollapse, { toggle: false }).hide();
      }
    });
  });

  /* ---- GALERÍAS: miniaturas accesibles con teclado ---- */
  document.querySelectorAll('[data-gallery]').forEach(item => {
    if (!item.hasAttribute('tabindex')) item.setAttribute('tabindex', '0');
    if (!item.hasAttribute('role')) item.setAttribute('role', 'button');
    if (!item.hasAttribute('aria-label')) item.setAttribute('aria-label', 'Ver foto ampliada');
    item.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        item.click();
      }
    });
  });


  /* ---- FORMULARIO DE REGISTRO: validación de contraseñas ---- */
 const registerForm = document.getElementById('registerForm');

if (registerForm) {
  const pass1 = registerForm.querySelector('#password');
  const pass2 = registerForm.querySelector('#password_confirm');

  registerForm.addEventListener('submit', function(e) {

    // Validación Bootstrap (required, checkbox, etc.)
    if (!registerForm.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }

    // Validación de contraseñas
    if (pass1 && pass2 && pass1.value !== pass2.value) {
      e.preventDefault();

      pass2.classList.add('is-invalid');

      const fb = pass2.nextElementSibling;
      if (fb && fb.classList.contains('invalid-feedback')) {
        fb.textContent = 'Las contraseñas no coinciden.';
      }
    }

    registerForm.classList.add('was-validated');
  });

  // Validación en tiempo real password confirm
  if (pass2) {
    pass2.addEventListener('input', () => {

      const passwordsMatch = pass1.value === pass2.value;
      const passwordValid = pass1.value.length >= 10;

      pass2.classList.toggle(
        'is-invalid',
        !passwordsMatch || !passwordValid
      );

      pass2.classList.toggle(
        'is-valid',
        passwordsMatch && passwordValid
      );
    });
  }
}

  /* ---- SELECTOR DE TURNO: deshabilitar fechas pasadas ---- */
  const dateInput = document.getElementById('fecha_turno');
  if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
  }

  /* ---- ALERTS: auto-cerrar después de 5s ---- */
  document.querySelectorAll('.alert-dismissible').forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      if (bsAlert) bsAlert.close();
    }, 5000);
  });

});
