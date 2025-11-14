<?php
// 1. Incluimos la conexión
include './config/db_connect.php'; // Usa './' o simplemente 'config/...'

$mensaje_error = "";
$mensaje_exito = "";

// --- PARTE 1: PROCESAR EL PEDIDO (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_cliente = isset($_POST['nombre_cliente']) ? trim($_POST['nombre_cliente']) : '';
    $telefono_cliente = isset($_POST['telefono_cliente']) ? $_POST['telefono_cliente'] : '';
    $carrito_json = isset($_POST['carrito_data']) ? $_POST['carrito_data'] : '[]';
    $carrito = json_decode($carrito_json, true);
    $total_pedido = 0;

    // Validaciones
    if (empty($nombre_cliente) || empty($telefono_cliente)) { $mensaje_error = "Error: Nombre y teléfono obligatorios."; }
    elseif (!preg_match('/^\d{10}$/', $telefono_cliente)) { $mensaje_error = "Error: Teléfono debe tener 10 dígitos."; }
    elseif (empty($carrito)) { $mensaje_error = "Error: Tu carrito está vacío."; }
    else {
        // Calcular total en servidor
        foreach ($carrito as $item) { $total_pedido += $item['cantidad'] * $item['precio']; }

        // --- INICIO TRANSACCIÓN ---
        $conn->begin_transaction();
        try {
            // Insertar Pedido
            $sql_pedido = "INSERT INTO Pedidos (nombre_cliente, telefono_cliente, id_usuario_registro, tipo_pedido, estado, total) VALUES (?, ?, NULL, 'Online', 'Nuevo', ?)";
            $stmt_pedido = $conn->prepare($sql_pedido);
            $stmt_pedido->bind_param("ssd", $nombre_cliente, $telefono_cliente, $total_pedido);
            $stmt_pedido->execute();
            $id_pedido_nuevo = $conn->insert_id;

            // Insertar Detalles y Actualizar Stock
            $sql_detalle = "INSERT INTO Detalle_Pedidos (id_pedido, id_producto, cantidad, precio_congelado) VALUES (?, ?, ?, ?)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            $sql_stock = "UPDATE Productos SET stock = stock - ? WHERE id_producto = ?";
            $stmt_stock = $conn->prepare($sql_stock);

            foreach ($carrito as $item) {
                // Validar stock ANTES de descontar (importante para evitar negativos si hay concurrencia)
                $sql_check_stock = "SELECT stock FROM Productos WHERE id_producto = ? FOR UPDATE"; // Bloquea la fila
                $stmt_check = $conn->prepare($sql_check_stock);
                $stmt_check->bind_param("i", $item['id']);
                $stmt_check->execute();
                $stock_actual = $stmt_check->get_result()->fetch_assoc()['stock'];
                $stmt_check->close();

                if ($stock_actual < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para el producto ID: " . $item['id']); // Lanza excepción para rollback
                }

                // Si hay stock, proceder
                $stmt_detalle->bind_param("iiid", $id_pedido_nuevo, $item['id'], $item['cantidad'], $item['precio']);
                $stmt_detalle->execute();
                $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt_stock->execute();
            }

            // Commit si todo OK
            $conn->commit();
            $stmt_pedido->close(); $stmt_detalle->close(); $stmt_stock->close();
            header("Location: pedido_exitoso.php?id=" . $id_pedido_nuevo);
            exit;

        } catch (Exception $e) {
            $conn->rollback(); // Revertir si hay error (ej. stock insuficiente)
            $mensaje_error = "Error al guardar el pedido: " . $e->getMessage();
        }
    }
}

// --- PARTE 2: OBTENER PRODUCTOS PARA CATÁLOGO (GET) ---
$productos_por_categoria = [];
$sql_productos = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.ruta_imagen, c.nombre AS categoria_nombre
                  FROM Productos p
                  JOIN Categorias c ON p.id_categoria = c.id_categoria
                  WHERE p.esta_activo = 1 AND p.stock > 0
                  ORDER BY c.nombre, p.nombre";
$result_productos = $conn->query($sql_productos);
if ($result_productos && $result_productos->num_rows > 0) {
    while($row = $result_productos->fetch_assoc()) {
        $productos_por_categoria[$row['categoria_nombre']][] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flor y Hojaldra - Tienda</title>
    <link rel="stylesheet" href="css/store_styles.css">
</head>
<body>

    <header class="store-header">
        <div class="header-content">
            <h1>Flor y Hojaldra</h1>
            <p class="tagline">Tradición y sabor para tu ofrenda</p>
        </div>
    </header>

    <div class="container">

        <div class="productos-col">
            <?php
            if (empty($productos_por_categoria)) {
                echo "<h2>¡Lo sentimos!</h2><p>No hay productos disponibles en este momento.</p>";
            } else {
                foreach ($productos_por_categoria as $categoria_nombre => $productos) {
                    echo "<div class='categoria-grupo'>";
                    echo "<h2>" . htmlspecialchars($categoria_nombre) . "</h2>";

                    foreach ($productos as $producto) {
                        ?>
                        <div class="product-card">
                            <?php
                            $rutaImagen = !empty($producto['ruta_imagen']) && file_exists($producto['ruta_imagen'])
                                          ? htmlspecialchars($producto['ruta_imagen'])
                                          : 'img/placeholder.png'; // Ruta a una imagen genérica si no hay
                            ?>
                            <img src="<?php echo $rutaImagen; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="product-image">

                            <div class="info">
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p class="descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <div class="precio-stock">
                                    <span class="precio">$<?php echo number_format($producto['precio'], 2); ?></span>
                                    <span class="stock">Disponibles: <?php echo $producto['stock']; ?></span>
                                </div>
                            </div>
                            <div class="acciones">
                                <input type="number" id="cantidad_<?php echo $producto['id_producto']; ?>" value="1" min="1" max="<?php echo $producto['stock']; ?>" aria-label="Cantidad">
                                <button class="btn-add"
                                        data-id="<?php echo $producto['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo $producto['precio']; ?>"
                                        data-stock="<?php echo $producto['stock']; ?>"
                                        onclick="agregarAlCarrito(this)">
                                    Añadir al Pedido
                                </button>
                            </div>
                        </div>
                        <?php
                    } // Fin foreach producto
                    echo "</div>"; // Fin .categoria-grupo
                } // Fin foreach categoria
            } // Fin else empty
            ?>
        </div>

        <div class="carrito-col">
            <form action="index.php" method="POST" onsubmit="return prepararEnvio()">
                <h2>Tu Pedido</h2>
                <p>Arma tu pedido aquí y recógelo en tienda. El pago es en efectivo al momento de la entrega.</p>

                <?php if (!empty($mensaje_error)): ?>
                    <p class="mensaje-error"><?php echo htmlspecialchars($mensaje_error); ?></p>
                <?php endif; ?>

                <ul id="carrito-items" style="padding-left: 0;">
                    <li id="carrito-vacio" style="text-align: center; color: #888;">Tu carrito está vacío</li>
                </ul>
                <div id="carrito-total">Total: $0.00</div>

                <hr style="margin: 20px 0;">

                <h3>Datos de Contacto</h3>
                <div class="form-group">
                    <label for="nombre_cliente">Tu Nombre:</label>
                    <input type="text" name="nombre_cliente" id="nombre_cliente" required>
                </div>
                <div class="form-group">
                    <label for="telefono_cliente">Tu Teléfono (10 dígitos):</label>
                    <input type="tel" name="telefono_cliente" id="telefono_cliente" required
                           pattern="\d{10}"
                           title="El número de teléfono debe tener 10 dígitos."
                           maxlength="10">
                </div>

                <input type="hidden" name="carrito_data" id="carrito_data">

                <button type="submit" class="btn-submit">Confirmar Pedido</button>
            </form>

            <div class="admin-link">
                <a href="./admin/login.php">Acceso Administración</a>
            </div>
        </div>
    </div>

    <script>
        let carrito = [];

        function agregarAlCarrito(boton) {
            const id = boton.dataset.id;
            const nombre = boton.dataset.nombre;
            const precio = parseFloat(boton.dataset.precio);
            const stock = parseInt(boton.dataset.stock);
            const cantidadInput = document.getElementById(`cantidad_${id}`);
            let cantidad = parseInt(cantidadInput.value);

            if (isNaN(cantidad) || cantidad <= 0) { alert("Cantidad inválida."); return; }

            const itemExistente = carrito.find(item => item.id == id);
            let cantidadTotal = cantidad;
            if (itemExistente) { cantidadTotal += itemExistente.cantidad; }

            if (cantidadTotal > stock) { alert(`Stock insuficiente. Solo quedan ${stock}.`); return; }

            if (itemExistente) { itemExistente.cantidad = cantidadTotal; }
            else { carrito.push({ id: id, nombre: nombre, precio: precio, cantidad: cantidad }); }

            cantidadInput.value = 1;
            actualizarVistaCarrito();
        }

        function actualizarVistaCarrito() {
            const ul = document.getElementById('carrito-items');
            const totalEl = document.getElementById('carrito-total');
            const vacioEl = document.getElementById('carrito-vacio');
            let totalGeneral = 0;
            ul.innerHTML = ''; // Limpiar lista, incluyendo el mensaje de vacío

            if (carrito.length === 0) {
                ul.innerHTML = '<li id="carrito-vacio" style="text-align: center; color: #888; border: none;">Tu carrito está vacío</li>';
            } else {
                 carrito.forEach((item, index) => {
                    const subtotal = item.cantidad * item.precio;
                    totalGeneral += subtotal;
                    const li = `<li>
                                    <span class="item-nombre">(${item.cantidad}x) ${item.nombre}</span>
                                    <span>$${subtotal.toFixed(2)}</span>
                                    <button type="button" class="btn-remove" onclick="removerDelCarrito(${index})">&times;</button>
                                </li>`;
                    ul.innerHTML += li;
                });
            }

            totalEl.textContent = `Total: $${totalGeneral.toFixed(2)}`;
            document.getElementById('carrito_data').value = JSON.stringify(carrito);
        }

        function removerDelCarrito(index) {
            carrito.splice(index, 1);
            actualizarVistaCarrito();
        }

        function prepararEnvio() {
            if (carrito.length === 0) {
                alert("Tu carrito está vacío. Añade al menos un producto.");
                return false; // Detiene el envío
            }
            document.getElementById('carrito_data').value = JSON.stringify(carrito);
            return true;
        }
         // Llamar al cargar para mostrar el estado inicial (vacío)
        document.addEventListener('DOMContentLoaded', actualizarVistaCarrito);
    </script>
    </body>
</html>