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

$mensaje = "";

// --- Procesar POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $precio = isset($_POST['precio']) ? filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT) : 0;
    $stock = isset($_POST['stock']) ? filter_var($_POST['stock'], FILTER_VALIDATE_INT) : 0;
    $id_categoria = isset($_POST['id_categoria']) ? filter_var($_POST['id_categoria'], FILTER_VALIDATE_INT) : 0;
    $ruta_imagen_guardar = null;

    if (empty($nombre) || $precio === false || $precio <= 0 || $stock === false || $stock < 0 || empty($id_categoria)) {
        $mensaje = "Error: Verifica campos obligatorios (Nombre, Precio > 0, Stock >= 0, Categoría).";
    } else {
        // --- Lógica de Subida de Imagen ---
        if (isset($_FILES['imagen_producto']) && $_FILES['imagen_producto']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen_producto']['tmp_name'];
            $fileName = $_FILES['imagen_producto']['name'];
            $fileSize = $_FILES['imagen_producto']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif');

            if (in_array($fileExtension, $allowedfileExtensions)) {
                if ($fileSize < 5000000) { // 5MB
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = '../uploads/products/';
                    $dest_path = $uploadFileDir . $newFileName;
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $ruta_imagen_guardar = 'uploads/products/' . $newFileName;
                    } else { $mensaje = 'Error al mover el archivo subido.'; }
                } else { $mensaje = 'Error: Archivo excede 5MB.'; }
            } else { $mensaje = 'Error: Tipo de archivo no permitido.'; }
        }

        // --- Fin Lógica de Subida ---
        if (empty($mensaje)) {
            $sql = "INSERT INTO Productos (nombre, descripcion, precio, stock, id_categoria, ruta_imagen, esta_activo) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssdiis", $nombre, $descripcion, $precio, $stock, $id_categoria, $ruta_imagen_guardar);
                if ($stmt->execute()) {
                    header("Location: gestionar_productos.php?status=success_create");
                    exit;
                } else { $mensaje = "Error al guardar en BD: " . $stmt->error; }
                $stmt->close();
            } else { $mensaje = "Error al preparar BD: " . $conn->error; }
        }
    }
}
// --- Obtener Categorías (GET) ---
$sql_categorias = "SELECT id_categoria, nombre FROM Categorias ORDER BY nombre ASC";
$result_categorias = $conn->query($sql_categorias);
$conn->close();
// --- Página Actual ---
$pagina_actual = 'productos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Producto - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>
        
        <div class="page-header">
            <h1>Añadir Nuevo Producto</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <div class="form-container">
            <form action="crear_producto.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="id_categoria">Categoría:</label>
                    <select name="id_categoria" id="id_categoria" required>
                        <option value="">-- Selecciona --</option>
                        <?php if ($result_categorias && $result_categorias->num_rows > 0): ?>
                            <?php while($row_cat = $result_categorias->fetch_assoc()): ?>
                                <option value="<?php echo $row_cat['id_categoria']; ?>"><?php echo htmlspecialchars($row_cat['nombre']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea name="descripcion" id="descripcion" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="precio">Precio:</label>
                    <input type="number" name="precio" id="precio" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock (Cantidad inicial):</label>
                    <input type="number" name="stock" id="stock" min="0" required>
                </div>
                <div class="form-group">
                    <label for="imagen_producto">Imagen del Producto (Opcional):</label>
                    <input type="file" name="imagen_producto" id="imagen_producto" accept="image/png, image/jpeg, image/gif">
                    <small>Formatos permitidos: JPG, PNG, GIF. Tamaño máx: 5MB.</small>
                </div>
                <button type="submit" class="btn btn-success">Guardar Producto</button>
                <a href="gestionar_productos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>