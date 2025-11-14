<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// --- Verificación de Rol (Solo Dueño) ---
if ($_SESSION['rol'] != 'Dueño') {
    header("Location: gestionar_pedidos.php"); // Redirigir si no es Dueño
    exit;
}
// --- Fin Verificación de Rol ---

// 2. Consulta SQL
$sql = "SELECT
            id_usuario, nombre_completo, usuario, rol, esta_activo
        FROM Usuarios
        ORDER BY id_usuario ASC";
$result = $conn->query($sql);

// 3. Página Actual
$pagina_actual = 'empleados';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Empleados - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>
        
        <h1>Gestión de Empleados</h1>

        <?php
        if(isset($_GET['status'])){
            if($_GET['status'] == 'success_create') echo "<p class='mensaje-exito'>Empleado creado exitosamente.</p>";
            if($_GET['status'] == 'success_update') echo "<p class='mensaje-exito'>Empleado actualizado exitosamente.</p>";
            if($_GET['status'] == 'success_delete') echo "<p class='mensaje-exito'>Empleado eliminado exitosamente.</p>";
            if($_GET['status'] == 'error_delete') echo "<p class='mensaje-error'>Error al eliminar el empleado.</p>";
        }
        ?>

        <p><a href="crear_empleado.php" class="btn btn-success">+ Añadir Nuevo Empleado</a></p>

        <h2>Empleados Registrados</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Usuario (Login)</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_usuario'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nombre_completo']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
                        echo "<td>" . $row['rol'] . "</td>";

                        $estado = $row['esta_activo'] ? 'Activo' : 'Inactivo';
                        $color_estado = $row['esta_activo'] ? 'var(--color-exito)' : 'var(--color-error)';
                        echo "<td style='color:$color_estado; font-weight:bold;'>" . $estado . "</td>";

                        echo "<td>";
                        echo "<a href='editar_empleado.php?id=" . $row['id_usuario'] . "' class='btn btn-primary btn-sm'>Editar</a> ";
                        echo "<a href='eliminar_empleado.php?id=" . $row['id_usuario'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro?\");'>Eliminar</a>";
                        echo "</td>";

                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Aún no hay empleados registrados.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>