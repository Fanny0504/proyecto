<?php
// 1. Protector y conexión
include 'proteger.php';

// --- Verificación de Rol (Solo Dueño) ---
if ($_SESSION['rol'] != 'Dueño') {
    header("Location: gestionar_pedidos.php");
    exit;
}
// --- Fin Verificación de Rol ---

include '../config/db_connect.php';

// --- 3. DEFINIR EL RANGO DE FECHAS ---
$fecha_inicio = date('Y-m-01');
$fecha_fin = date('Y-m-t');
if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) { $fecha_inicio = $_GET['fecha_inicio']; }
if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) { $fecha_fin = $_GET['fecha_fin']; }
$fecha_fin_sql = date('Y-m-d', strtotime($fecha_fin . ' +1 day'));

// --- 4. CONSULTAS A LA BASE DE DATOS ---
// Q1: Ingresos
$sql_ingresos = "SELECT SUM(total) AS total_ingresos FROM Pedidos WHERE estado = 'Completado y Pagado' AND fecha_completado >= ? AND fecha_completado < ?";
$stmt_ingresos = $conn->prepare($sql_ingresos);
$stmt_ingresos->bind_param("ss", $fecha_inicio, $fecha_fin_sql);
$stmt_ingresos->execute();
$total_ingresos = $stmt_ingresos->get_result()->fetch_assoc()['total_ingresos'] ?? 0;
$stmt_ingresos->close();

// Q2: Gastos
$sql_gastos = "SELECT SUM(monto) AS total_gastos FROM Gastos WHERE fecha >= ? AND fecha < ?";
$stmt_gastos = $conn->prepare($sql_gastos);
$stmt_gastos->bind_param("ss", $fecha_inicio, $fecha_fin_sql);
$stmt_gastos->execute();
$total_gastos = $stmt_gastos->get_result()->fetch_assoc()['total_gastos'] ?? 0;
$stmt_gastos->close();

// Q3: Ganancia
$ganancia_neta = $total_ingresos - $total_gastos;

// Q4: Pedidos Pendientes
$sql_pendientes = "SELECT COUNT(id_pedido) AS pedidos_pendientes FROM Pedidos WHERE estado NOT IN ('Completado y Pagado', 'Cancelado')";
$pedidos_pendientes = $conn->query($sql_pendientes)->fetch_assoc()['pedidos_pendientes'] ?? 0;

$conn->close();

// --- 5. Página Actual ---
$pagina_actual = 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button> 
        
        <div class="page-header">
            <h1>Dashboard y Reporte Financiero</h1>
            </div>

        <p>Bienvenido(a), <?php echo htmlspecialchars($_SESSION['usuario']); ?>.</p>

        <div class="form-container" style="max-width: none; margin-bottom: 20px;">
             <form action="index.php" method="GET" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <strong>Reporte de:</strong>
                <label for="fecha_inicio" style="margin-left: 10px;">Desde:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                <label for="fecha_fin">Hasta:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
        </div>

        <h2>Resumen Financiero (<?php echo date("d/m/Y", strtotime($fecha_inicio)) . " - " . date("d/m/Y", strtotime($fecha_fin)); ?>)</h2>
        <div class="kpi-container">
            <div class="kpi-card ingresos">
                <h2>Total Ingresos</h2>
                <span>$<?php echo number_format($total_ingresos, 2); ?></span>
            </div>
            <div class="kpi-card gastos">
                <h2>Total Gastos</h2>
                <span>$<?php echo number_format($total_gastos, 2); ?></span>
            </div>
            <div class="kpi-card ganancia">
                <h2>Ganancia Neta</h2>
                <span>$<?php echo number_format($ganancia_neta, 2); ?></span>
            </div>
        </div>

        <h2>Alertas Operativas</h2>
        <div class="kpi-container">
            <div class="kpi-card alerta">
                <h2>Pedidos Pendientes</h2>
                <span><?php echo $pedidos_pendientes; ?></span>
                <a href="gestionar_pedidos.php" class="btn-link">Gestionar ahora &rarr;</a>
            </div>
        </div>

    </div> <script src="js/admin_scripts.js"></script>

</body>
</html>