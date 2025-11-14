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
$usuario_data = null;
$id_usuario = 0;

// --- OBTENER DATOS (GET) ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id_usuario = $_GET['id'];
    $sql_fetch = "SELECT id_usuario, nombre_completo, usuario, rol, esta_activo FROM Usuarios WHERE id_usuario = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $id_usuario); $stmt_fetch->execute(); $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows == 1) { $usuario_data = $result_fetch->fetch_assoc(); }
        else { $mensaje = "Error: Empleado no encontrado."; }
        $stmt_fetch->close();
    } else { $mensaje = "Error al preparar consulta."; }
} else {
    if ($_SERVER["REQUEST_METHOD"] != "POST") { $mensaje = "Error: ID no válido."; }
}

// --- ACTUALIZAR DATOS (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = isset($_POST['id_usuario']) ? filter_var($_POST['id_usuario'], FILTER_VALIDATE_INT) : 0;
    $nombre_completo = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
    $usuario_login = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $rol = isset($_POST['rol']) ? $_POST['rol'] : '';
    $esta_activo = isset($_POST['esta_activo']) ? filter_var($_POST['esta_activo'], FILTER_VALIDATE_INT) : 0;
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
    $confirmar_contrasena = isset($_POST['confirmar_contrasena']) ? $_POST['confirmar_contrasena'] : '';

    if (empty($nombre_completo) || empty($usuario_login) || empty($rol) || $id_usuario <= 0) {
        $mensaje = "Error: Nombre, Usuario y Rol son obligatorios.";
    } elseif (!empty($contrasena) && $contrasena != $confirmar_contrasena) {
        $mensaje = "Error: Las nuevas contraseñas no coinciden.";
    } else {
        if (!empty($contrasena)) {
            $contrasena_hasheada = password_hash($contrasena, PASSWORD_DEFAULT);
            $sql_update = "UPDATE Usuarios SET nombre_completo = ?, usuario = ?, rol = ?, esta_activo = ?, contrasena = ? WHERE id_usuario = ?";
            $params_types = "sssisi";
            $params_values = [$nombre_completo, $usuario_login, $rol, $esta_activo, $contrasena_hasheada, $id_usuario];
        } else {
            $sql_update = "UPDATE Usuarios SET nombre_completo = ?, usuario = ?, rol = ?, esta_activo = ? WHERE id_usuario = ?";
            $params_types = "sssii";
            $params_values = [$nombre_completo, $usuario_login, $rol, $esta_activo, $id_usuario];
        }
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param($params_types, ...$params_values);
            if ($stmt_update->execute()) {
                header("Location: gestionar_empleados.php?status=success_update");
                exit;
            } else {
                if ($conn->errno == 1062) { $mensaje = "Error: El usuario '$usuario_login' ya existe."; }
                else { $mensaje = "Error al actualizar: " . $stmt_update->error; }
            }
            $stmt_update->close();
        } else { $mensaje = "Error al preparar actualización: " . $conn->error; }
    }
    // Si hay error, recargar datos para mostrar en el form
    $usuario_data = ['id_usuario' => $id_usuario, 'nombre_completo' => $nombre_completo, 'usuario' => $usuario_login, 'rol' => $rol, 'esta_activo' => $esta_activo];
}

$conn->close();

// --- Página Actual ---
$pagina_actual = 'empleados';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>
        
        <div class="page-header">
            <h1>Editar Empleado</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <?php if ($usuario_data): ?>
        <div class="form-container">
            <form action="editar_empleado.php?id=<?php echo $id_usuario; ?>" method="POST">
                <input type="hidden" name="id_usuario" value="<?php echo $usuario_data['id_usuario']; ?>">
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo:</label>
                    <input type="text" name="nombre_completo" id="nombre_completo" value="<?php echo htmlspecialchars($usuario_data['nombre_completo']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="usuario">Nombre de Usuario (Login):</label>
                    <input type="text" name="usuario" id="usuario" value="<?php echo htmlspecialchars($usuario_data['usuario']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="rol">Rol:</label>
                    <select name="rol" id="rol" required>
                        <option value="Dueño" <?php echo ($usuario_data['rol'] == 'Dueño') ? 'selected' : ''; ?>>Dueño</option>
                        <option value="Empleado" <?php echo ($usuario_data['rol'] == 'Empleado') ? 'selected' : ''; ?>>Empleado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="esta_activo">Estado:</label>
                    <select name="esta_activo" id="esta_activo" required>
                        <option value="1" <?php echo ($usuario_data['esta_activo'] == 1) ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo ($usuario_data['esta_activo'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <hr style="margin: 25px 0;">
                <div class="form-group">
                    <label for="contrasena">Nueva Contraseña:</label>
                    <input type="password" name="contrasena" id="contrasena">
                    <small>Dejar en blanco para no cambiar.</small>
                </div>
                <div class="form-group">
                    <label for="confirmar_contrasena">Confirmar Nueva Contraseña:</label>
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena">
                </div>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="gestionar_empleados.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
         <?php elseif(empty($mensaje)): ?>
             <p class="mensaje-error">No se pudo cargar la información del empleado.</p>
        <?php endif; ?>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>