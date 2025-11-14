<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// (Este archivo NO necesita verificación de rol, ya que un Empleado puede verlo)

// 2. Consulta SQL
$sql = "SELECT P.id_producto, P.nombre, P.precio, P.stock, P.esta_activo, P.ruta_imagen, C.nombre AS nombre_categoria
        FROM Productos P
        JOIN Categorias C ON P.id_categoria = C.id_categoria
        ORDER BY P.id_producto DESC";
$result = $conn->query($sql);

// 3. Página Actual
$pagina_actual = 'productos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Productos - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>

        <h1>Gestión de Inventario (Productos)</h1>

        <?php
        if(isset($_GET['status'])){
            if($_GET['status'] == 'success_create') echo "<p class='mensaje-exito'>Producto creado exitosamente.</p>";
            if($_GET['status'] == 'success_update') echo "<p class='mensaje-exito'>Producto actualizado exitosamente.</p>";
            if($_GET['status'] == 'success_delete') echo "<p class='mensaje-exito'>Producto eliminado exitosamente.</p>";
            if($_GET['status'] == 'error_delete') echo "<p class='mensaje-error'>Error al eliminar el producto.</p>";
        }
        ?>

        <?php if ($_SESSION['rol'] == 'Dueño'): ?>
            <p><a href="crear_producto.php" class="btn btn-success">+ Añadir Nuevo Producto</a></p>
        <?php endif; ?>

        <h2>Productos Actuales</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre del Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_producto'] . "</td>";
                        // Columna de Imagen
                        echo "<td>";
                        if (!empty($row['ruta_imagen']) && file_exists('../' . $row['ruta_imagen'])) {
                            echo "<img src='../" . htmlspecialchars($row['ruta_imagen']) . "?v=" . time() . "' alt='Imagen' style='width: 50px; height: 50px; object-fit: cover; border-radius: 4px;'>";
                        } else {
                            if ($_SESSION['rol'] == 'Dueño') {
                                echo "<a href='editar_producto.php?id=" . $row['id_producto'] . "' class='btn btn-secondary btn-sm'>Añadir Imagen</a>";
                            } else {
                                echo "<span style='color: #aaa; font-size: 0.8em;'>Sin img</span>";
                            }
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nombre_categoria']) . "</td>";
                        echo "<td>$" . number_format($row['precio'], 2) . "</td>";
                        echo "<td>" . $row['stock'] . "</td>";
                        $estado = $row['esta_activo'] ? 'Activo' : 'Inactivo';
                        $color_estado = $row['esta_activo'] ? 'var(--color-exito)' : 'var(--color-error)';
                        echo "<td style='color:$color_estado; font-weight:bold;'>" . $estado . "</td>";
                        echo "<td>";
                        // Acciones (solo Dueño)
                        if ($_SESSION['rol'] == 'Dueño') {
                            echo "<a href='editar_producto.php?id=" . $row['id_producto'] . "' class='btn btn-primary btn-sm'>Editar</a> ";
                            echo "<a href='eliminar_producto.php?id=" . $row['id_producto'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro?\");'>Eliminar</a>";
                        } else {
                            echo "<span style='color: #aaa; font-size: 0.8em;'>N/A</span>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>Aún no hay productos registrados.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>