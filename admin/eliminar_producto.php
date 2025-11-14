<?php
// 1. Incluimos la conexión
include '../config/db_connect.php';
include 'proteger.php';
// 2. Verificamos que se haya recibido un ID por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $id_producto = $_GET['id'];

    // 3. Preparamos la consulta SQL (Usando Prepared Statements)
    // ¡NUNCA borres sin un WHERE!
    $sql = "DELETE FROM Productos WHERE id_producto = ?";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // 4. Vinculamos el ID
        $stmt->bind_param("i", $id_producto);
        
        // 5. Ejecutamos la consulta
        if ($stmt->execute()) {
            // Éxito: Redirigimos a la lista con un mensaje
            header("Location: gestionar_productos.php?status=success_delete");
            exit;
        } else {
            // Error: Redirigimos con un mensaje de error
            header("Location: gestionar_productos.php?status=error_delete");
            exit;
        }
        
        $stmt->close();
    } else {
        echo "Error al preparar la consulta: " . $conn->error;
    }

} else {
    // Si no se proporcionó un ID válido, simplemente redirigimos
    header("Location: gestionar_productos.php");
    exit;
}

$conn->close();

?>