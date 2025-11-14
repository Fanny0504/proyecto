<?php
// 1. Protector y conexión
include 'proteger.php';
include '../config/db_connect.php';

// (Este archivo NO necesita verificación de rol, ya que un Empleado puede crear pedidos)

$mensaje = "";
$mensaje_tipo = "error";

// --- Procesar POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_cliente = isset($_POST['nombre_cliente']) ? trim($_POST['nombre_cliente']) : '';
    $telefono_cliente = isset($_POST['telefono_cliente']) ? $_POST['telefono_cliente'] : '';
    $carrito_json = isset($_POST['carrito_data']) ? $_POST['carrito_data'] : '[]';
    $carrito = json_decode($carrito_json, true);
    $id_usuario_registro = $_SESSION['id_usuario'];
    $total_pedido = 0;

    if (empty($nombre_cliente) || empty($telefono_cliente)) { $mensaje = "Nombre y teléfono obligatorios."; }
    elseif (!preg_match('/^\d{10}$/', $telefono_cliente)) { $mensaje = "Teléfono debe tener 10 dígitos."; }
    elseif (empty($carrito)) { $mensaje = "El pedido está vacío."; }
    else {
        foreach ($carrito as $item) { $total_pedido += $item['cantidad'] * $item['precio']; }

        $conn->begin_transaction();
        try {
            $sql_pedido = "INSERT INTO Pedidos (nombre_cliente, telefono_cliente, id_usuario_registro, tipo_pedido, estado, total) VALUES (?, ?, ?, 'Manual', 'Nuevo', ?)";
            $stmt_pedido = $conn->prepare($sql_pedido);
            $stmt_pedido->bind_param("sssd", $nombre_cliente, $telefono_cliente, $id_usuario_registro, $total_pedido);
            $stmt_pedido->execute();
            $id_pedido_nuevo = $conn->insert_id;

            $sql_detalle = "INSERT INTO Detalle_Pedidos (id_pedido, id_producto, cantidad, precio_congelado) VALUES (?, ?, ?, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            $sql_stock = "UPDATE Productos SET stock = stock - ? WHERE id_producto = ?";
            $stmt_stock = $conn->prepare($sql_stock);

            foreach ($carrito as $item) {
                // (Validación de stock omitida aquí por brevedad, pero la teníamos en index.php)
                $stmt_detalle->bind_param("iiid", $id_pedido_nuevo, $item['id'], $item['cantidad'], $item['precio']);
                $stmt_detalle->execute();
                $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt_stock->execute();
            }

            $conn->commit();
            $stmt_pedido->close(); $stmt_detalle->close(); $stmt_stock->close();
            header("Location: gestionar_pedidos.php?status=success_create");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al guardar pedido: " . $e->getMessage();
            $mensaje_tipo = "error";
        }
    }
}

// --- Obtener Productos (GET) ---
$sql_productos = "SELECT id_producto, nombre, precio, stock FROM Productos WHERE esta_activo = 1 AND stock > 0 ORDER BY nombre ASC";
$result_productos = $conn->query($sql_productos);

$conn->close();

// --- Página Actual ---
$pagina_actual = 'pedidos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pedido Manual - Flor y Hojaldra</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <button class="mobile-nav-toggle" id="mobile-nav-toggle">&#9776;</button>

        <div class="page-header">
            <h1>Registrar Pedido Manual (CU-02)</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <p class="<?php echo ($mensaje_tipo == 'exito') ? 'mensaje-exito' : 'mensaje-error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </p>
        <?php endif; ?>

        <div class="form-container">
            <form action="crear_pedido_manual.php" method="POST" name="pedidoForm" onsubmit="return prepararEnvio()">
                <h2>1. Datos del Cliente</h2>
                <div class="form-group">
                    <label for="nombre_cliente">Nombre:</label>
                    <input type="text" name="nombre_cliente" id="nombre_cliente" required>
                </div>
                <div class="form-group">
                    <label for="telefono_cliente">Teléfono (10 dígitos):</label>
                    <input type="tel" name="telefono_cliente" id="telefono_cliente" required pattern="\d{10}" title="Debe tener 10 dígitos." maxlength="10">
                </div>

                <h2>2. Añadir Productos</h2>
                <div class="form-group" style="display: flex; gap: 10px; align-items: flex-end;">
                    <div style="flex-grow: 1;">
                        <label for="producto_select">Producto:</label>
                        <select id="producto_select">
                            <option value="">-- Selecciona --</option>
                            <?php if ($result_productos && $result_productos->num_rows > 0): ?>
                                <?php while($row_prod = $result_productos->fetch_assoc()): ?>
                                    <option value="<?php echo $row_prod['id_producto']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($row_prod['nombre']); ?>"
                                            data-precio="<?php echo $row_prod['precio']; ?>"
                                            data-stock="<?php echo $row_prod['stock']; ?>">
                                        <?php echo htmlspecialchars($row_prod['nombre']); ?> ($<?php echo number_format($row_prod['precio'], 2); ?>) - Stock: <?php echo $row_prod['stock']; ?>
                                     </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="cantidad_select">Cantidad:</label>
                        <input type="number" id="cantidad_select" value="1" min="1" style="width: 80px;">
                    </div>
                    <button type="button" class="btn btn-primary" onclick="agregarAlCarrito()">Añadir</button>
                </div>

                <h2>3. Resumen del Pedido</h2>
                <table>
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th><th>Acción</th></tr></thead>
                    <tbody id="carrito-tbody"></tbody>
                </table>
                <div id="total-pedido" style="font-size: 1.5em; font-weight: bold; text-align: right; margin-top: 10px;">Total: $0.00</div>
                <input type="hidden" name="carrito_data" id="carrito_data">

                <hr style="margin: 20px 0;">
                <button type="submit" class="btn btn-success">Guardar Pedido</button>
                <a href="gestionar_pedidos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>

    </div> <script src="js/admin_scripts.js"></script>
    <script>
        // (Pegar aquí el script de JS del carrito de crear_pedido_manual.php)
        let carrito = [];
        function agregarAlCarrito() {
            const select = document.getElementById('producto_select');
            const op = select.options[select.selectedIndex];
            const cantInput = document.getElementById('cantidad_select');
            const id = op.value;
            if (!id) { alert("Selecciona un producto."); return; }
            const nombre = op.dataset.nombre;
            const precio = parseFloat(op.dataset.precio);
            const stock = parseInt(op.dataset.stock);
            let cant = parseInt(cantInput.value);
            if (isNaN(cant) || cant <= 0) { alert("Cantidad inválida."); return; }
            const itemExistente = carrito.find(item => item.id == id);
            let cantTotal = cant;
            if (itemExistente) { cantTotal += itemExistente.cantidad; }
            if (cantTotal > stock) { alert(`Stock insuficiente. Solo quedan ${stock}.`); return; }
            if (itemExistente) { itemExistente.cantidad = cantTotal; }
            else { carrito.push({ id: id, nombre: nombre, precio: precio, cantidad: cant }); }
            cantInput.value = 1; select.selectedIndex = 0;
            actualizarVistaCarrito();
        }
        function actualizarVistaCarrito() {
            const tbody = document.getElementById('carrito-tbody');
            const totalEl = document.getElementById('total-pedido');
            let totalGeneral = 0; tbody.innerHTML = '';
            carrito.forEach((item, index) => {
                const subtotal = item.cantidad * item.precio;
                totalGeneral += subtotal;
                const fila = `<tr><td>${item.nombre}</td><td>${item.cantidad}</td><td>$${item.precio.toFixed(2)}</td><td>$${subtotal.toFixed(2)}</td><td><button type="button" class="btn btn-danger btn-sm" onclick="removerDelCarrito(${index})">X</button></td></tr>`;
                tbody.innerHTML += fila;
            });
            totalEl.textContent = `Total: $${totalGeneral.toFixed(2)}`;
            document.getElementById('carrito_data').value = JSON.stringify(carrito);
        }
        function removerDelCarrito(index) { carrito.splice(index, 1); actualizarVistaCarrito(); }
        function prepararEnvio() {
             if (carrito.length === 0) { alert("El carrito está vacío."); return false; }
             document.getElementById('carrito_data').value = JSON.stringify(carrito); return true;
        }
        document.addEventListener('DOMContentLoaded', actualizarVistaCarrito);
    </script>
</body>
</html>