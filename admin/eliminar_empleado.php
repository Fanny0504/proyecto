<?php
// 1. Incluimos la conexión
include '../config/db_connect.php';
include 'proteger.php';
// (Seguridad simple: En un futuro, deberíamos verificar que el 'Dueño'
// no pueda borrarse a sí mismo, o que sea el único Dueño)

// 2. Verificamos que se haya recibido un ID por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $id_usuario = $_GET['id'];

    // 3. Preparamos la consulta SQL DELETE
    $sql = "DELETE FROM Usuarios WHERE id_usuario = ?";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // 4. Vinculamos el ID
        $stmt->bind_param("i", $id_usuario);
        
        // 5. Ejecutamos
        if ($stmt->execute()) {
            // Éxito: Redirigimos a la lista
            header("Location: gestionar_empleados.php?status=success_delete");
            exit;
        } else {
            // Error
            header("Location: gestionar_empleados.php?status=error_delete");
            exit;
        }
        
        $stmt->close();
    } else {
        echo "Error al preparar la consulta: " . $conn->error;
    }

} else {
    // Si no hay ID válido, redirigimos
    header("Location: gestionar_empleados.php");
    exit;
}

$conn->close();

?>