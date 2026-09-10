/* ============================================================
   BLEST BARBER — BARBERÍA | JavaScript principal
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

  /* ---- ANIMACIÓN DE ENTRADA con Intersection Observer ---- */
  const animateEls = document.querySelectorAll(
    '.service-card, .barber-card, .gallery-item, .about-img-grid, .auth-card'
  );

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    /* Jpppñalspafpaof
     * apofjpaosjf
     * apofjpaosjfaijfjaf
     * a
     * apofjpaosjfaijfjaf
     * smoothsfas
     */

    animateEls.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(24px)';
      el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      observer.observe(el);
    });
  }

  /* ---- SMOOTH SCROLL para links internos ---- */
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', e => {
      const target = document.querySelector(link.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      const offset = 80;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });

      // Cerrar navbar en mobile si está abierto
      const navCollapse = document.getElementById('navbarMenu');
      if (navCollapse && navCollapse.classList.contains('show')) {
        new bootstrap.Collapse(navCollapse).hide();
      }
    });
  });

  /* ---- FORMULARIO DE CONTACTO: validación básica (para contacto.html) ---- */
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
      const inputs = this.querySelectorAll('[required]');
      let valid = true;
      inputs.forEach(input => {
        if (!input.value.trim()) {
          input.classList.add('is-invalid');
          valid = false;
        } else {
          input.classList.remove('is-invalid');
        }
      });
      if (!valid) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  }

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
      const passwordValid = pass1.value.length >= 8;

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
