<?php
// 1. Incluimos el protector y la conexión
include 'proteger.php';
include '../config/db_connect.php';

// 2. Verificamos que se haya recibido un ID por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $id_gasto = $_GET['id'];
    $id_usuario_actual = $_SESSION['id_usuario'];

    // 3. Preparamos la consulta SQL DELETE con seguridad
    $sql = "DELETE FROM Gastos WHERE id_gasto = ?";
    
    // Si el rol NO es 'Dueño', añadimos la condición
    if ($_SESSION['rol'] != 'Dueño') {
        $sql .= " AND id_usuario = ?";
    }
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // 4. Vinculamos los parámetros
        if ($_SESSION['rol'] != 'Dueño') {
            $stmt->bind_param("ii", $id_gasto, $id_usuario_actual);
        } else {
            $stmt->bind_param("i", $id_gasto);
        }
        
        // 5. Ejecutamos
        if ($stmt->execute()) {
            // Verificamos si realmente se borró algo
            if ($stmt->affected_rows > 0) {
                // Éxito
                header("Location: gestionar_gastos.php?status=success_delete");
            } else {
                // No se borró nada (probablemente por falta de permisos)
                header("Location: gestionar_gastos.php?status=error_permission");
            }
            exit;
        } else {
            // Error de ejecución
            header("Location: gestionar_gastos.php?status=error_delete");
            exit;
        }
        
        $stmt->close();
    } else {
        echo "Error al preparar la consulta: " . $conn->error;
    }

} else {
    // Si no hay ID válido, redirigimos
    header("Location: gestionar_gastos.php");
    exit;
}

$conn->close();
?>