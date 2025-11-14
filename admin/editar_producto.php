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

$mensaje = "";
$producto = null;
$id_producto = 0;
$ruta_imagen_actual = null;

// --- OBTENER DATOS (GET) ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_producto = $_GET['id'];
    $sql_fetch = "SELECT * FROM Productos WHERE id_producto = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $id_producto); $stmt_fetch->execute(); $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows == 1) {
            $producto = $result_fetch->fetch_assoc();
            $ruta_imagen_actual = $producto['ruta_imagen'];
        } else { $mensaje = "Error: Producto no encontrado."; }
        $stmt_fetch->close();
    } else { $mensaje = "Error al preparar consulta."; }
} else {
    if ($_SERVER["REQUEST_METHOD"] != "POST") { $mensaje = "Error: ID no válido."; }
}

// --- ACTUALIZAR DATOS (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_producto = isset($_POST['id_producto']) ? filter_var($_POST['id_producto'], FILTER_VALIDATE_INT) : 0;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $precio = isset($_POST['precio']) ? filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT) : 0;
    $stock = isset($_POST['stock']) ? filter_var($_POST['stock'], FILTER_VALIDATE_INT) : 0;
    $id_categoria = isset($_POST['id_categoria']) ? filter_var($_POST['id_categoria'], FILTER_VALIDATE_INT) : 0;
    $esta_activo = isset($_POST['esta_activo']) ? filter_var($_POST['esta_activo'], FILTER_VALIDATE_INT) : 0;
    $ruta_imagen_guardar = isset($_POST['ruta_imagen_actual']) ? $_POST['ruta_imagen_actual'] : null;

    if (empty($nombre) || $precio === false || $precio <= 0 || $stock === false || $stock < 0 || empty($id_categoria) || $id_producto <= 0) {
        $mensaje = "Error: Verifica campos obligatorios.";
    } else {
        // --- Lógica de Subida de NUEVA Imagen ---
        if (isset($_FILES['imagen_producto_nueva']) && $_FILES['imagen_producto_nueva']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imagen_producto_nueva']['tmp_name'];
            $fileName = $_FILES['imagen_producto_nueva']['name'];
            $fileSize = $_FILES['imagen_producto_nueva']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif');

            if (in_array($fileExtension, $allowedfileExtensions)) {
                if ($fileSize < 5000000) { // 5MB
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = '../uploads/products/';
                    $dest_path = $uploadFileDir . $newFileName;
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Borrar la imagen antigua si existía
                        if ($ruta_imagen_guardar && file_exists('../' . $ruta_imagen_guardar)) {
                           unlink('../' . $ruta_imagen_guardar);
                        }
                        $ruta_imagen_guardar = 'uploads/products/' . $newFileName; // Nueva ruta
                    } else { $mensaje = 'Error al mover el nuevo archivo.'; }
                } else { $mensaje = 'Error: El nuevo archivo excede 5MB.'; }
            } else { $mensaje = 'Error: Tipo de archivo no permitido.'; }
        } elseif (isset($_FILES['imagen_producto_nueva']) && $_FILES['imagen_producto_nueva']['error'] != UPLOAD_ERR_NO_FILE) {
            $mensaje = 'Error al subir la nueva imagen. Código: ' . $_FILES['imagen_producto_nueva']['error'];
        }
        // --- Fin Lógica de Subida ---

        if (empty($mensaje)) {
            $sql_update = "UPDATE Productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, id_categoria = ?, ruta_imagen = ?, esta_activo = ? WHERE id_producto = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("ssdiissi", $nombre, $descripcion, $precio, $stock, $id_categoria, $ruta_imagen_guardar, $esta_activo, $id_producto);
                if ($stmt_update->execute()) {
                    header("Location: gestionar_productos.php?status=success_update");
                    exit;
                } else { $mensaje = "Error al actualizar BD: " . $stmt_update->error; }
                $stmt_update->close();
            } else { $mensaje = "Error al preparar actualización BD: " . $conn->error; }
        }
    }
    // Si hay error, recargar datos para mostrar en el form
    $producto = ['id_producto' => $id_producto, 'nombre' => $nombre, 'descripcion' => $descripcion, 'precio' => $precio, 'stock' => $stock, 'id_categoria' => $id_categoria, 'ruta_imagen' => $ruta_imagen_guardar, 'esta_activo' => $esta_activo];
    $ruta_imagen_actual = $ruta_imagen_guardar;
}

// --- OBTENER CATEGORÍAS (Para el Select) ---
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
    <title>Editar Producto - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>
        
        <div class="page-header">
            <h1>Editar Producto</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <?php if ($producto): ?>
        <div class="form-container">
            <form action="editar_producto.php?id=<?php echo $id_producto; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                <input type="hidden" name="ruta_imagen_actual" value="<?php echo htmlspecialchars($producto['ruta_imagen'] ?? ''); ?>">

                <div class="form-group">
                    <label for="id_categoria">Categoría:</label>
                    <select name="id_categoria" id="id_categoria" required>
                        <option value="">-- Selecciona --</option>
                        <?php if ($result_categorias && $result_categorias->num_rows > 0): ?>
                            <?php while($row_cat = $result_categorias->fetch_assoc()): ?>
                                <option value="<?php echo $row_cat['id_categoria']; ?>" <?php echo (isset($producto['id_categoria']) && $row_cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row_cat['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre del Producto:</label>
                    <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea name="descripcion" id="descripcion" rows="4"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="precio">Precio:</label>
                    <input type="number" name="precio" id="precio" value="<?php echo $producto['precio'] ?? '0.00'; ?>" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" name="stock" id="stock" value="<?php echo $producto['stock'] ?? '0'; ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label>Imagen Actual:</label>
                    <?php if (!empty($ruta_imagen_actual) && file_exists('../' . $ruta_imagen_actual)) : ?>
                        <img src="../<?php echo htmlspecialchars($ruta_imagen_actual); ?>?v=<?php echo time(); // Cache buster ?>" alt="Imagen actual" style="max-width: 150px; height: auto; display: block; margin-bottom: 10px; border-radius: 6px;">
                    <?php else: ?>
                        <p style="color: #888;">No hay imagen asignada.</p>
                    <?php endif; ?>
                    <label for="imagen_producto_nueva" style="margin-top:10px;">Subir Nueva Imagen (Opcional):</label>
                    <input type="file" name="imagen_producto_nueva" id="imagen_producto_nueva" accept="image/png, image/jpeg, image/gif">
                    <small>Dejar vacío para conservar la imagen actual.</small>
                </div>
                <div class="form-group">
                    <label for="esta_activo">Estado:</label>
                    <select name="esta_activo" id="esta_activo" required>
                        <option value="1" <?php echo (isset($producto['esta_activo']) && $producto['esta_activo'] == 1) ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo (isset($producto['esta_activo']) && $producto['esta_activo'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="gestionar_productos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        <?php elseif(empty($mensaje)): ?>
             <p class="mensaje-error">No se pudo cargar la información del producto.</p>
        <?php endif; ?>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>