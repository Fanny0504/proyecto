<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// (Este archivo NO necesita verificación de rol, ya que un Empleado puede registrar gastos)

$mensaje = "";

// --- Procesar POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $monto = isset($_POST['monto']) ? filter_var($_POST['monto'], FILTER_VALIDATE_FLOAT) : 0;
    $concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
    $id_usuario = $_SESSION['id_usuario']; // El usuario que está logueado

    if (empty($fecha) || $monto === false || $monto <= 0 || empty($concepto)) {
        $mensaje = "Error: Verifica campos obligatorios (Fecha, Monto > 0, Concepto).";
    } else {
        $sql = "INSERT INTO Gastos (id_usuario, fecha, monto, concepto) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isds", $id_usuario, $fecha, $monto, $concepto);
            if ($stmt->execute()) {
                header("Location: gestionar_gastos.php?status=success_create");
                exit;
            } else { $mensaje = "Error al guardar gasto: " . $stmt->error; }
            $stmt->close();
        } else { $mensaje = "Error al preparar: " . $conn->error; }
    }
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
    <title>Registrar Gasto - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>

        <div class="page-header">
             <h1>Registrar Nuevo Gasto</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <div class="form-container">
            <form action="crear_gasto.php" method="POST">
                <div class="form-group">
                    <label for="fecha">Fecha del Gasto:</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="concepto">Concepto:</label>
                    <input type="text" name="concepto" id="concepto" required>
                </div>

                <div class="form-group">
                    <label for="monto">Monto:</label>
                    <input type="number" name="monto" id="monto" step="0.01" min="0.01" required>
                </div>

                <button type="submit" class="btn btn-success">Guardar Gasto</button>
                <a href="gestionar_gastos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>

    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>