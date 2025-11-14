<?php
// Iniciamos la sesión
session_start();

// Si el usuario YA tiene una sesión activa, lo redirigimos según su rol
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] == 'Dueño') {
        header("Location: index.php"); // Dueño al Dashboard
    } else {
        header("Location: gestionar_pedidos.php"); // Empleado a Pedidos
    }
    exit;
}

// Variable para mensajes de error
$error_login = "";

// Verificamos si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Incluimos la conexión a la BD (solo si se envía el form)
    include '../config/db_connect.php';

    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

    if (empty($usuario) || empty($contrasena)) {
        $error_login = "Por favor, ingresa usuario y contraseña.";
    } else {
        // 1. Preparamos la consulta para buscar al usuario
        $sql = "SELECT id_usuario, usuario, contrasena, rol, esta_activo FROM Usuarios WHERE usuario = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                // 2. Usuario encontrado, verificamos contraseña
                $user_data = $result->fetch_assoc();

                // 3. Verificamos el hash
                if (password_verify($contrasena, $user_data['contrasena'])) {

                    // 4. Verificamos si está activo
                    if ($user_data['esta_activo'] == 1) {

                        // 5. ¡ÉXITO! Creamos variables de sesión
                        session_regenerate_id(true); // Previene fijación de sesión
                        $_SESSION['id_usuario'] = $user_data['id_usuario'];
                        $_SESSION['usuario'] = $user_data['usuario'];
                        $_SESSION['rol'] = $user_data['rol'];

                        // 6. Redirigimos según el ROL
                        if ($_SESSION['rol'] == 'Dueño') {
                            header("Location: index.php"); // Dueño al Dashboard
                        } else {
                            header("Location: gestionar_pedidos.php"); // Empleado a Pedidos
                        }
                        exit; // Detenemos script

                    } else {
                        $error_login = "Tu cuenta está desactivada. Contacta al administrador.";
                    }
                } else {
                    // Contraseña incorrecta
                    $error_login = "Usuario o contraseña incorrectos.";
                }
            } else {
                // Usuario no encontrado
                $error_login = "Usuario o contraseña incorrectos.";
            }
            $stmt->close();
        } else {
            $error_login = "Error del sistema al preparar consulta. Intenta más tarde.";
        }
        // Cerramos conexión solo si se abrió
        if(isset($conn)) $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Flor y Hojaldra</title>
    <style>
        body { font-family: 'Lato', Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 320px; text-align: center; }
        .login-container h1 { margin: 0 0 10px 0; color: #4E342E; font-size: 1.8em; } /* Marrón Hojaldra */
        .login-container p { margin: 0 0 25px 0; color: #666; font-size: 1em; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9em;}
        .form-group input { width: 100%; padding: 12px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font-size: 1em; }
        .btn-login { background-color: #FF9800; color: #4E342E; width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em; font-weight: bold; transition: background-color 0.2s ease; } /* Naranja Cempasúchil */
        .btn-login:hover { background-color: #FB8C00; }
        .error-msg { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9em; }
        .back-link a { color: #6c757d; text-decoration: none; font-size: 0.9em; transition: color 0.2s ease;}
        .back-link a:hover { color: #333; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Flor y Hojaldra</h1>
        <p>Acceso al Panel</p>

        <?php
        // Mostramos el mensaje de error si existe
        if (!empty($error_login)) {
            echo "<div class='error-msg'>$error_login</div>";
        }
        // Mensaje si viene de logout
        if (isset($_GET['status']) && $_GET['status'] == 'logout_success') {
             echo "<div style='color: green; margin-bottom: 15px;'>Sesión cerrada correctamente.</div>";
        }
        ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" name="usuario" id="usuario" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" name="contrasena" id="contrasena" required>
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="back-link" style="margin-top: 25px;">
            <a href="../index.php">&larr; Regresar al Catálogo</a>
        </div>

    </div> </body>
</html>