<?php
// Asegurarnos de que $pagina_actual esté definida
if (!isset($pagina_actual)) {
    $pagina_actual = '';
}
?>

<aside class="sidebar" id="sidebar">
    
    <button class="close-btn" id="sidebar-close-btn">&times;</button>
    
    <div class="logo">
        <img src="img/logoFlorPan.png" alt="Logo Flor y Hojaldra" style="max-width: 180px; height: auto;">
    </div>
    <nav>
        <ul>
            <li><a href="gestionar_pedidos.php" class="<?php echo ($pagina_actual == 'pedidos') ? 'active' : ''; ?>">Gestionar Pedidos</a></li>
            <li><a href="gestionar_productos.php" class="<?php echo ($pagina_actual == 'productos') ? 'active' : ''; ?>">Gestionar Productos</a></li>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Dueño'): ?>
                <li><a href="index.php" class="<?php echo ($pagina_actual == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="reporte_ventas.php" class="<?php echo ($pagina_actual == 'ventas') ? 'active' : ''; ?>">Reporte de Ventas</a></li>
                <li><a href="gestionar_empleados.php" class="<?php echo ($pagina_actual == 'empleados') ? 'active' : ''; ?>">Gestionar Empleados</a></li>
                <li><a href="gestionar_gastos.php" class="<?php echo ($pagina_actual == 'gastos') ? 'active' : ''; ?>">Gestionar Gastos</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="logout-link">
        <a href="logout.php"> Cerrar Sesión</a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay"></div>