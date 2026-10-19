/* ============================================================
   HAEDO BARBER | JS de paneles (admin / cliente)
   - Etiquetas data-label en tablas para verlas como tarjetas en móvil.
   - Centra el enlace activo del menú horizontal en móvil.
   - Mueve los modales a <body> para que queden sobre el backdrop.
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

  // Los modales viven dentro de .admin-layout (isolation: isolate) y quedaban
  // debajo del backdrop de Bootstrap (no se podían tocar). Se mueven a <body>.
  document.querySelectorAll('.admin-layout .modal').forEach(modal => {
    document.body.appendChild(modal);
  });

  const menu = document.querySelector('.admin-menu');
  const active = menu && menu.querySelector('.admin-menu-link.active');
  if (menu && active && menu.scrollWidth > menu.clientWidth) {
    menu.scrollLeft = active.offsetLeft - (menu.clientWidth - active.offsetWidth) / 2;
  }
});
