<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

$mensaje = "";
$gasto = null;
$id_gasto = 0;

// --- OBTENER DATOS (GET) ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_gasto = $_GET['id'];
    $sql_fetch = "SELECT * FROM Gastos WHERE id_gasto = ?";
    if ($_SESSION['rol'] != 'Dueño') { $sql_fetch .= " AND id_usuario = ?"; } // Seguridad Rol
    $stmt_fetch = $conn->prepare($sql_fetch);
    if ($stmt_fetch) {
        if ($_SESSION['rol'] != 'Dueño') { $stmt_fetch->bind_param("ii", $id_gasto, $_SESSION['id_usuario']); }
        else { $stmt_fetch->bind_param("i", $id_gasto); }
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows == 1) { $gasto = $result_fetch->fetch_assoc(); }
        else { $mensaje = "Error: Gasto no encontrado o sin permisos."; }
        $stmt_fetch->close();
    } else { $mensaje = "Error al preparar consulta."; }
} else {
    if ($_SERVER["REQUEST_METHOD"] != "POST") { $mensaje = "Error: ID no válido."; }
}

// --- ACTUALIZAR DATOS (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_gasto = isset($_POST['id_gasto']) ? filter_var($_POST['id_gasto'], FILTER_VALIDATE_INT) : 0;
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $monto = isset($_POST['monto']) ? filter_var($_POST['monto'], FILTER_VALIDATE_FLOAT) : 0;
    $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
    $id_usuario_actual = $_SESSION['id_usuario'];

    if (empty($fecha) || $monto === false || $monto <= 0 || empty($concepto) || $id_gasto <= 0) {
        $mensaje = "Error: Verifica los campos obligatorios (Fecha, Monto > 0, Concepto).";
    } else {
        $sql_update = "UPDATE Gastos SET fecha = ?, monto = ?, concepto = ? WHERE id_gasto = ?";
        if ($_SESSION['rol'] != 'Dueño') { $sql_update .= " AND id_usuario = ?"; } // Seguridad Rol
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            if ($_SESSION['rol'] != 'Dueño') { $stmt_update->bind_param("sdsii", $fecha, $monto, $concepto, $id_gasto, $id_usuario_actual); }
            else { $stmt_update->bind_param("sdsi", $fecha, $monto, $concepto, $id_gasto); }
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    header("Location: gestionar_gastos.php?status=success_update");
                    exit;
                } else { $mensaje = "No se realizaron cambios o no tienes permiso."; }
            } else { $mensaje = "Error al actualizar: " . $stmt_update->error; }
            $stmt_update->close();
        } else { $mensaje = "Error al preparar actualización: " . $conn->error; }
    }
    // Si hay error, recargar datos para mostrar en el form
    $gasto = ['id_gasto' => $id_gasto, 'fecha' => $fecha, 'monto' => $monto, 'concepto' => $concepto];
}

$conn->close();

// --- Página Actual ---
$pagina_actual = 'gastos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Gasto - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>

        <div class="page-header">
             <h1>Editar Gasto</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <?php if ($gasto): ?>
        <div class="form-container">
            <form action="editar_gasto.php?id=<?php echo $id_gasto; ?>" method="POST">
                <input type="hidden" name="id_gasto" value="<?php echo $gasto['id_gasto']; ?>">
                <div class="form-group">
                    <label for="fecha">Fecha del Gasto:</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($gasto['fecha']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="concepto">Concepto:</label>
                    <input type="text" name="concepto" id="concepto" value="<?php echo htmlspecialchars($gasto['concepto']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="monto">Monto:</label>
                    <input type="number" name="monto" id="monto" value="<?php echo $gasto['monto']; ?>" step="0.01" min="0.01" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="gestionar_gastos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        <?php elseif(empty($mensaje)): ?>
             <p class="mensaje-error">No se pudo cargar la información del gasto.</p>
        <?php endif; ?>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>