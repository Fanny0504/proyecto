<?php
// Obtenemos el ID del pedido desde la URL
$id_pedido = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : 'un error';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pedido Exitoso! - Flor y Hojaldra</title>
    <style>
        :root {
            --color-cempasuchil: #FF9800;
            --color-hojaldra: #4E342E;
            --color-verde: #2E7D32;
        }
        body { font-family: Arial, sans-serif; background-color: #FFF8E1; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; }
        .container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { color: var(--color-verde); font-size: 2.5em; margin-top: 0; }
        p { font-size: 1.2em; color: #333; line-height: 1.6; }
        .pedido-id { font-size: 1.8em; font-weight: bold; color: var(--color-hojaldra); margin: 20px 0; }
        .btn-volver {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background-color: var(--color-cempasuchil);
            color: var(--color-hojaldra);
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Gracias por tu pedido!</h1>
        <p>Hemos recibido tu pedido y lo estaremos preparando.</p>
        <p>Tu número de pedido es:</p>
        <div class="pedido-id">#<?php echo $id_pedido; ?></div>
        <p>Recuerda que el pago es en efectivo al momento de recoger en la tienda.</p>
        <a href="index.php" class="btn-volver">&larr; Volver al Catálogo</a>
    </div>
</body>
</html>