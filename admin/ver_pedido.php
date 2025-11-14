<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// (Este archivo NO necesita verificación de rol, ya que un Empleado puede verlo)

$mensaje = "";
$mensaje_tipo = "error";
$pedido = null;
$detalles_pedido = [];
$id_pedido = 0;

// --- PARTE 1: PROCESAR ACCIONES (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pedido_post = isset($_POST['id_pedido']) ? filter_var($_POST['id_pedido'], FILTER_VALIDATE_INT) : 0;
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    if ($id_pedido_post <= 0) { $mensaje = "ID de pedido inválido."; }
    else {
        $id_pedido = $id_pedido_post; // Guardamos ID para recargar
        // --- LÓGICA PARA ACTUALIZAR ESTADO ---
        if ($accion == 'actualizar_estado') {
            $nuevo_estado = isset($_POST['nuevo_estado']) ? $_POST['nuevo_estado'] : '';
            $estados_permitidos = ['Nuevo', 'En Preparacion', 'Listo para Recoger', 'Completado y Pagado'];
            if (!empty($nuevo_estado) && in_array($nuevo_estado, $estados_permitidos)) {
                $conn->begin_transaction();
                try {
                    if ($nuevo_estado == 'Completado y Pagado') {
                        $sql_update = "UPDATE Pedidos SET estado = ?, fecha_completado = NOW() WHERE id_pedido = ? AND estado != 'Cancelado'";
                    } else {
                        $sql_update = "UPDATE Pedidos SET estado = ?, fecha_completado = NULL WHERE id_pedido = ? AND estado != 'Cancelado'";
                    }
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("si", $nuevo_estado, $id_pedido);
                    $stmt_update->execute();
                    $stmt_update->close();
                    $conn->commit();
                    header("Location: gestionar_pedidos.php?status=success_update");
                    exit;
                } catch (Exception $e) { $conn->rollback(); $mensaje = "Error al actualizar estado: " . $e->getMessage(); }
            } else { $mensaje = "Estado nuevo no válido."; }
        }
        // --- LÓGICA PARA CANCELAR PEDIDO ---
        elseif ($accion == 'cancelar') {
            $conn->begin_transaction();
            try {
                $sql_get_details = "SELECT id_producto, cantidad FROM Detalle_Pedidos WHERE id_pedido = ?";
                $stmt_get = $conn->prepare($sql_get_details);
                $stmt_get->bind_param("i", $id_pedido); $stmt_get->execute(); $result_details = $stmt_get->get_result();
                $items_a_restaurar = [];
                while ($row = $result_details->fetch_assoc()) { $items_a_restaurar[] = $row; }
                $stmt_get->close();
                if (empty($items_a_restaurar)) { throw new Exception("No se encontraron detalles para este pedido."); }

                $sql_cancel = "UPDATE Pedidos SET estado = 'Cancelado', fecha_completado = NULL WHERE id_pedido = ? AND estado NOT IN ('Completado y Pagado', 'Cancelado')";
                $stmt_cancel = $conn->prepare($sql_cancel);
                $stmt_cancel->bind_param("i", $id_pedido); $stmt_cancel->execute();

                 if ($stmt_cancel->affected_rows > 0) {
                     $sql_restore_stock = "UPDATE Productos SET stock = stock + ? WHERE id_producto = ?";
                     $stmt_restore = $conn->prepare($sql_restore_stock);
                     foreach ($items_a_restaurar as $item) {
                         $stmt_restore->bind_param("ii", $item['cantidad'], $item['id_producto']);
                         $stmt_restore->execute();
                     }
                     $stmt_restore->close(); $conn->commit(); $stmt_cancel->close();
                     header("Location: gestionar_pedidos.php?status=success_cancel");
                     exit;
                 } else {
                     $conn->rollback();
                     $mensaje = "Error: El pedido ya está completado o cancelado y no se puede modificar.";
                 }
                 $stmt_cancel->close();
            } catch (Exception $e) { $conn->rollback(); $mensaje = "Error al cancelar el pedido: " . $e->getMessage(); }
        } else { $mensaje = "Acción no reconocida."; }
    }
}

// --- PARTE 2: OBTENER DATOS DEL PEDIDO (GET) ---
if ($id_pedido == 0) {
    if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        $id_pedido = $_GET['id'];
    } else { $mensaje = "Error: ID de pedido no válido."; }
}
if ($id_pedido > 0 && $pedido == null) {
    $sql_pedido = "SELECT * FROM Pedidos WHERE id_pedido = ?";
    $stmt_pedido = $conn->prepare($sql_pedido);
    if ($stmt_pedido) {
        $stmt_pedido->bind_param("i", $id_pedido); $stmt_pedido->execute(); $result_pedido = $stmt_pedido->get_result();
        if ($result_pedido->num_rows == 1) {
            $pedido = $result_pedido->fetch_assoc();
            $sql_detalles = "SELECT dp.cantidad, dp.precio_congelado, p.nombre AS nombre_producto FROM Detalle_Pedidos AS dp JOIN Productos AS p ON dp.id_producto = p.id_producto WHERE dp.id_pedido = ?";
            $stmt_detalles = $conn->prepare($sql_detalles);
            if ($stmt_detalles) {
                $stmt_detalles->bind_param("i", $id_pedido); $stmt_detalles->execute(); $result_detalles = $stmt_detalles->get_result();
                while($row = $result_detalles->fetch_assoc()) { $detalles_pedido[] = $row; }
                $stmt_detalles->close();
            } else { $mensaje .= " Error al cargar detalles: " . $conn->error; }
        } else { $mensaje = "Error: Pedido con ID $id_pedido no encontrado."; $pedido = null; }
        $stmt_pedido->close();
    } else { $mensaje = "Error al preparar la consulta del pedido."; }
}
$conn->close();
$pagina_actual = 'pedidos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido #<?php echo $id_pedido; ?> - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>

        <?php if ($pedido) : ?>
            <div class="page-header">
                <h1>Detalle del Pedido #<?php echo $pedido['id_pedido']; ?></h1>
            </div>

            <?php if (!empty($mensaje)): ?>
                <p class="<?php echo ($mensaje_tipo == 'exito') ? 'mensaje-exito' : 'mensaje-error'; ?>"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php endif; ?>

            <div class="form-container">
                 <h2>Datos del Cliente</h2>
                 <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--color-gris-borde); line-height: 1.7;">
                     <strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?><br>
                     <strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['telefono_cliente']); ?><br>
                     <strong>Tipo:</strong> <?php echo $pedido['tipo_pedido']; ?><br>
                     <strong>Fecha Pedido:</strong> <?php echo date("d/m/Y h:i A", strtotime($pedido['fecha_creacion'])); ?><br>
                     <?php if ($pedido['fecha_completado']) : ?><strong>Fecha Completado:</strong> <?php echo date("d/m/Y h:i A", strtotime($pedido['fecha_completado'])); ?><br><?php endif; ?>
                     <strong>Estado:</strong> <strong style="font-size: 1.2em;"><?php echo $pedido['estado']; ?></strong>
                 </div>
                 <h2>Productos del Pedido</h2>
                 <table>
                     <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead>
                     <tbody>
                     <?php foreach ($detalles_pedido as $detalle): $subtotal = $detalle['cantidad'] * $detalle['precio_congelado']; ?>
                         <tr><td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td><td><?php echo $detalle['cantidad']; ?></td><td>$<?php echo number_format($detalle['precio_congelado'], 2); ?></td><td>$<?php echo number_format($subtotal, 2); ?></td></tr>
                     <?php endforeach; ?>
                     <tr style="background-color: #f8f9fa; font-weight: bold;"><td colspan="3" style="text-align: right;">TOTAL:</td><td>$<?php echo number_format($pedido['total'], 2); ?></td></tr>
                     </tbody>
                 </table>

                <?php if ($pedido['estado'] != 'Completado y Pagado' && $pedido['estado'] != 'Cancelado') : ?>
                    <div style="margin-top: 30px; border-top: 1px solid var(--color-gris-borde); padding-top: 20px;">
                        <form action="ver_pedido.php?id=<?php echo $id_pedido; ?>" method="POST" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                            <input type="hidden" name="accion" value="actualizar_estado">
                            <div class="form-group" style="display: inline-block; margin-bottom: 0; vertical-align: middle;">
                                <label for="nuevo_estado" style="margin-right: 5px;">Actualizar Estado:</label>
                                <select name="nuevo_estado" id="nuevo_estado" required>
                                    <?php $estado_actual = $pedido['estado']; $opciones = [];
                                    if ($estado_actual == 'Nuevo') $opciones = ['Nuevo', 'En Preparacion', 'Completado y Pagado'];
                                    elseif ($estado_actual == 'En Preparacion') $opciones = ['En Preparacion', 'Listo para Recoger', 'Completado y Pagado'];
                                    elseif ($estado_actual == 'Listo para Recoger') $opciones = ['Listo para Recoger', 'Completado y Pagado'];
                                    foreach ($opciones as $opcion) { $selected = ($opcion == $estado_actual) ? 'selected' : ''; $texto = ($opcion == $estado_actual) ? $opcion : "Marcar como: $opcion"; echo "<option value='$opcion' $selected>$texto</option>"; } ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                        </form>
                        <form action="ver_pedido.php?id=<?php echo $id_pedido; ?>" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás MUY seguro de CANCELAR este pedido? Esta acción restaurará el stock y no se puede deshacer.');">
                            <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                            <input type="hidden" name="accion" value="cancelar">
                            <button type="submit" class="btn btn-danger">Cancelar Pedido</GIST></button>
                        </form>
                    </div>
                <?php else: ?>
                    <p style="margin-top: 30px; font-weight: bold; font-size: 1.1em; color: <?php echo ($pedido['estado'] == 'Cancelado' ? 'var(--color-error)' : 'var(--color-exito)'); ?>;">
                        Este pedido ya está <?php echo ($pedido['estado'] == 'Cancelado' ? 'CANCELADO' : 'COMPLETADO'); ?>.
                    </p>
                <?php endif; ?>
            </div>

        <?php else : // Si NO encontramos el pedido ?>
            <div class="page-header"><h1>Error</h1></div>
            <p class="mensaje-error"><?php echo htmlspecialchars($mensaje ?: "No se pudo cargar el pedido."); ?></p>
        <?php endif; ?>

        <a href="gestionar_pedidos.php" class="btn btn-link" style="margin-top: 20px;">&larr; Volver a la lista de Pedidos</a>
    </div> <script src="js/admin_scripts.js"></script>
</body>
</html>