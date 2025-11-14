// Espera a que el documento HTML esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {

    // 1. Busca los elementos por su ID
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('mobile-nav-toggle'); // Botón hamburguesa
    const closeBtn = document.getElementById('sidebar-close-btn'); // Botón 'X'
    const overlay = document.getElementById('sidebar-overlay');    // Fondo oscuro

    // Función para ABRIR el menú
    function abrirMenu() {
        if (sidebar) sidebar.classList.add('active');
        if (overlay) overlay.classList.add('active');
    }

    // Función para CERRAR el menú
    function cerrarMenu() {
        if (sidebar) sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
    }

    // 2. Asigna los eventos de clic
    if (toggleBtn) {
        toggleBtn.addEventListener('click', abrirMenu);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', cerrarMenu);
    }
    
    // Cierra el menú si se hace clic en el fondo oscuro
    if (overlay) {
        overlay.addEventListener('click', cerrarMenu);
    }

});