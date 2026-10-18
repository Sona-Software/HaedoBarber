/* ============================================================
   HAEDO BARBER | JS de paneles (admin / cliente)
   - Etiquetas data-label en tablas para verlas como tarjetas en móvil.
   - Centra el enlace activo del menú horizontal en móvil.
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

  document.querySelectorAll('.admin-table').forEach(table => {
    const headers = Array.from(table.querySelectorAll('thead th'))
      .map(th => th.textContent.trim());

    table.querySelectorAll('tbody tr').forEach(row => {
      Array.from(row.children).forEach((cell, index) => {
        if (cell.tagName === 'TD' && headers[index] && !cell.hasAttribute('data-label')) {
          cell.setAttribute('data-label', headers[index]);
        }
      });
    });
  });

  const menu = document.querySelector('.admin-menu');
  const active = menu && menu.querySelector('.admin-menu-link.active');
  if (menu && active && menu.scrollWidth > menu.clientWidth) {
    menu.scrollLeft = active.offsetLeft - (menu.clientWidth - active.offsetWidth) / 2;
  }
});
