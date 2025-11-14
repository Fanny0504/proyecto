<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// --- Verificación de Rol (Solo Dueño) ---
if ($_SESSION['rol'] != 'Dueño') {
    header("Location: gestionar_pedidos.php");
    exit;
}
// --- Fin Verificación de Rol ---

// --- 2. Rango de Fechas ---
$fecha_inicio = date('Y-m-01');
$fecha_fin = date('Y-m-t');
if (isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) { $fecha_inicio = $_GET['fecha_inicio']; }
if (isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) { $fecha_fin = $_GET['fecha_fin']; }
$fecha_fin_sql = date('Y-m-d', strtotime($fecha_fin . ' +1 day'));

// --- 3. Consulta ---
$sql_ventas = "SELECT
                    p.id_pedido, p.nombre_cliente, p.tipo_pedido, p.total,
                    p.fecha_completado, u.nombre_completo AS nombre_empleado
                FROM Pedidos AS p
                LEFT JOIN Usuarios AS u ON p.id_usuario_registro = u.id_usuario
                WHERE
                    p.estado = 'Completado y Pagado'
                    AND p.fecha_completado >= ?
                    AND p.fecha_completado < ?
                ORDER BY p.fecha_completado DESC";
$stmt_ventas = $conn->prepare($sql_ventas);
$stmt_ventas->bind_param("ss", $fecha_inicio, $fecha_fin_sql);
$stmt_ventas->execute();
$result_ventas = $stmt_ventas->get_result();
$conn->close();

// --- 4. Página Actual ---
$pagina_actual = 'ventas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>

        <div class="page-header">
            <h1>Reporte de Ventas Completadas</h1>
        </div>

        <div class="form-container" style="max-width: none; margin-bottom: 20px;">
            <form action="reporte_ventas.php" method="GET" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <strong>Mostrar ventas de:</strong>
                <label for="fecha_inicio" style="margin-left: 10px;">Desde:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                <label for="fecha_fin">Hasta:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
        </div>

        <h2>Ventas en el período seleccionado</h2>

        <table>
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Fecha Completado</th>
                    <th>Tipo</th>
                    <th>Cliente / Empleado (POS)</th>
                    <th>Total</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_ventas && $result_ventas->num_rows > 0) {
                    while($row = $result_ventas->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>#" . $row['id_pedido'] . "</td>";
                        echo "<td>" . ($row['fecha_completado'] ? date("d/m/Y h:i A", strtotime($row['fecha_completado'])) : 'N/A') . "</td>";
                        echo "<td>" . $row['tipo_pedido'] . "</td>";

                        if ($row['tipo_pedido'] == 'POS') {
                            $quien_registro = 'Venta POS por ' . ($row['nombre_empleado'] ? htmlspecialchars($row['nombre_empleado']) : 'Desconocido');
                        } else {
                            $quien_registro = htmlspecialchars($row['nombre_cliente']);
                        }
                        echo "<td>" . $quien_registro . "</td>";
                        echo "<td>$" . number_format($row['total'], 2) . "</td>";
                        echo "<td><a href='ver_pedido.php?id=" . $row['id_pedido'] . "' class='btn-link'>Ver Detalle</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No se encontraron ventas completadas en este período.</td></tr>";
                }
                if ($stmt_ventas) $stmt_ventas->close();
                ?>
            </tbody>
        </table>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>