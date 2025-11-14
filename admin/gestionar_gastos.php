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
            G.id_gasto, G.fecha, G.monto, G.concepto,
            U.nombre_completo AS nombre_usuario
        FROM Gastos AS G
        JOIN Usuarios AS U ON G.id_usuario = U.id_usuario
        ORDER BY G.fecha DESC, G.id_gasto DESC";
$result = $conn->query($sql);

// 3. Página Actual
$pagina_actual = 'gastos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Gastos - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>
        
        <h1>Gestión de Gastos Operativos</h1>

        <?php
        if(isset($_GET['status'])){
            if($_GET['status'] == 'success_create') echo "<p class='mensaje-exito'>Gasto registrado exitosamente.</p>";
            if($_GET['status'] == 'success_update') echo "<p class='mensaje-exito'>Gasto actualizado exitosamente.</p>";
            if($_GET['status'] == 'success_delete') echo "<p class='mensaje-exito'>Gasto eliminado exitosamente.</p>";
            if($_GET['status'] == 'error_delete') echo "<p class='mensaje-error'>Error al eliminar el gasto.</p>";
            if($_GET['status'] == 'error_permission') echo "<p class='mensaje-error'>No tienes permiso para eliminar este gasto.</p>";
        }
        ?>

        <p><a href="crear_gasto.php" class="btn btn-success">+ Registrar Nuevo Gasto</a></p>

        <h2>Gastos Registrados</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Registrado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_gasto'] . "</td>";
                        echo "<td>" . date("d/m/Y", strtotime($row['fecha'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['concepto']) . "</td>";
                        echo "<td>$" . number_format($row['monto'], 2) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nombre_usuario']) . "</td>";
                        echo "<td>";
                        echo "<a href='editar_gasto.php?id=" . $row['id_gasto'] . "' class='btn btn-primary btn-sm'>Editar</a> ";
                        echo "<a href='eliminar_gasto.php?id=" . $row['id_gasto'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Estás seguro?\");'>Eliminar</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Aún no hay gastos registrados.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>