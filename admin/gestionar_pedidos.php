<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// 3. Consulta SQL para Pedidos PENDIENTES
$sql = "SELECT
            id_pedido, nombre_cliente, telefono_cliente, tipo_pedido, estado, total, fecha_creacion
        FROM Pedidos
        WHERE estado NOT IN ('Completado y Pagado', 'Cancelado')
        ORDER BY
            CASE estado
                WHEN 'Nuevo' THEN 1
                WHEN 'En Preparacion' THEN 2
                WHEN 'Listo para Recoger' THEN 3
                ELSE 4
            END,
            fecha_creacion ASC";
$result = $conn->query($sql);

// 4. Página Actual
$pagina_actual = 'pedidos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button> 
        
        <div class="page-header">
            <h1>Gestión de Pedidos</h1>
            <div class="page-actions">
                <a href="pos_venta.php" class="btn btn-primary">Registrar Venta (POS)</a>
                <a href="crear_pedido_manual.php" class="btn btn-success">+ Registrar Pedido Manual</a>
            </div>
        </div>
        
        <?php
        if(isset($_GET['status'])){
            if($_GET['status'] == 'success_create') echo "<p class='mensaje-exito'>Pedido creado exitosamente.</p>";
            if($_GET['status'] == 'success_update') echo "<p class='mensaje-exito'>Estado del pedido actualizado exitosamente.</p>";
            if($_GET['status'] == 'success_cancel') echo "<p class='mensaje-exito'>Pedido cancelado y stock restaurado.</p>";
        }
        ?>

        <h2>Pedidos Pendientes (Nuevos, En Preparación, Listos)</h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID Pedido</th><th>Cliente</th><th>Teléfono</th><th>Tipo</th>
                    <th>Estado</th><th>Total</th><th>Fecha Pedido</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>#" . $row['id_pedido'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['telefono_cliente']) . "</td>";
                        echo "<td>" . $row['tipo_pedido'] . "</td>";
                        $color_estado = 'var(--color-texto)';
                        if ($row['estado'] == 'Nuevo') $color_estado = 'var(--color-error)';
                        if ($row['estado'] == 'En Preparacion') $color_estado = 'var(--color-advertencia)';
                        if ($row['estado'] == 'Listo para Recoger') $color_estado = 'var(--color-exito)';
                        echo "<td style='font-weight:bold; color:$color_estado;'>" . $row['estado'] . "</td>";
                        echo "<td>$" . number_format($row['total'], 2) . "</td>";
                        echo "<td>" . date("d/m/Y h:i A", strtotime($row['fecha_creacion'])) . "</td>";
                        echo "<td><a href='ver_pedido.php?id=" . $row['id_pedido'] . "' class='btn-link'>Ver / Actualizar</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>¡Excelente! No hay pedidos pendientes.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
        
    </div> <script src="js/admin_scripts.js"></script>

</body>
</html>