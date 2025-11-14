<?php
// 1. Protector y conexión
include 'proteger.php'; // Inicia sesión y protege
include '../config/db_connect.php';

// --- Verificación de Rol (Solo Dueño puede crear empleados) ---
if ($_SESSION['rol'] != 'Dueño') {
    // Si no es Dueño, redirigir a la página principal del empleado
    header("Location: gestionar_pedidos.php");
    exit;
}
// --- Fin Verificación de Rol ---

$mensaje = "";

// --- Procesar POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $rol = isset($_POST['rol']) ? $_POST['rol'] : '';
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
    $confirmar_contrasena = isset($_POST['confirmar_contrasena']) ? $_POST['confirmar_contrasena'] : '';

    // Validaciones
    if (empty($nombre_completo) || empty($usuario) || empty($rol) || empty($contrasena)) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } elseif ($contrasena != $confirmar_contrasena) {
        $mensaje = "Error: Las contraseñas no coinciden.";
    } elseif ($rol != 'Dueño' && $rol != 'Empleado') {
         $mensaje = "Error: Rol no válido.";
    } else {
        // Hashear contraseña
        $contrasena_hasheada = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Preparar SQL
        $sql = "INSERT INTO Usuarios (nombre_completo, usuario, contrasena, rol, esta_activo) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssss", $nombre_completo, $usuario, $contrasena_hasheada, $rol);
            if ($stmt->execute()) {
                header("Location: gestionar_empleados.php?status=success_create");
                exit;
            } else {
                if ($conn->errno == 1062) { // Error de usuario duplicado
                    $mensaje = "Error: El nombre de usuario '$usuario' ya existe.";
                } else { 
                    $mensaje = "Error al guardar empleado: " . $stmt->error;
                }
            }
            $stmt->close();
        } else { 
            $mensaje = "Error al preparar la consulta: " . $conn->error;
        }
    }
}

$conn->close();

// --- Página Actual para el sidebar ---
$pagina_actual = 'empleados';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Empleado - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">

        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button> 

        <div class="page-header">
             <h1>Añadir Nuevo Empleado</h1>
             </div>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>

        <div class="form-container">
            <form action="crear_empleado.php" method="POST">
                
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo:</label>
                    <input type="text" name="nombre_completo" id="nombre_completo" required>
                </div>

                <div class="form-group">
                    <label for="usuario">Nombre de Usuario (Login):</label>
                    <input type="text" name="usuario" id="usuario" required>
                </div>

                <div class="form-group">
                    <label for="rol">Rol:</label>
                    <select name="rol" id="rol" required>
                        <option value="">-- Selecciona un rol --</option>
                        <option value="Dueño">Dueño</option>
                        <option value="Empleado">Empleado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contrasena">Contraseña:</label>
                    <input type="password" name="contrasena" id="contrasena" required>
                </div>

                <div class="form-group">
                    <label for="confirmar_contrasena">Confirmar Contraseña:</label>
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" required>
                </div>

                <button type="submit" class="btn btn-success">Guardar Empleado</button>
                <a href="gestionar_empleados.php" class="btn btn-secondary">Cancelar</a>
                
            </form>
        </div>

    </div> <script src="js/admin_scripts.js"></script>

</body>
</html>