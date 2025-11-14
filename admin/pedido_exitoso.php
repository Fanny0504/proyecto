<?php
// Obtenemos el ID del pedido desde la URL
$id_pedido = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 'un error';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pedido Exitoso! - Flor y Hojaldra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-cempasuchil: #FF9800;
            --color-hojaldra: #4E342E;
            --color-verde: #2E7D32;
            --color-fondo: #FFF8E1;
            --color-blanco: #fff;
        }
        body {
            font-family: 'Lato', Arial, sans-serif;
            background-color: var(--color-fondo);
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            box-sizing: border-box;
            align-items: center; /* Centrar contenido */
        }
        .store-header-simple {
             background-color: var(--color-hojaldra);
             color: var(--color-blanco);
             padding: 15px 20px;
             text-align: center;
             margin-bottom: 30px;
             border-radius: 8px;
             box-shadow: 0 2px 4px rgba(0,0,0,0.1);
             width: 100%;
             max-width: 680px; /* Ancho consistente */
        }
         .store-header-simple h1 {
             margin: 0;
             font-size: 1.8em;
             color: var(--color-cempasuchil);
         }
        .container {
            background-color: var(--color-blanco);
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
            max-width: 600px;
            width: 100%;
            margin: 0; /* Ya centrado por el body */
        }
        .container h2 {
            color: var(--color-verde);
            font-size: 2em;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .container p {
            font-size: 1.1em;
            line-height: 1.7;
            margin-bottom: 15px;
        }
        .pedido-id {
            font-size: 1.6em;
            font-weight: bold;
            color: var(--color-hojaldra);
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            display: inline-block;
            margin: 10px 0 20px 0;
            border: 1px solid #dee2e6;
        }
        .btn-volver {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 30px;
            background-color: var(--color-cempasuchil);
            color: var(--color-hojaldra);
            text-decoration: none;
            font-weight: bold;
            border-radius: 6px;
            font-size: 1.1em;
            transition: background-color 0.2s ease;
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-volver:hover { background-color: #FB8C00; }
         footer {
             margin-top: auto; /* Empuja al fondo */
             padding: 30px 15px 15px 15px;
             text-align: center;
             font-size: 0.9em;
             color: #777;
         }
    </style>
</head>
<body>
    <header class="store-header-simple">
        <h1>Flor y Hojaldra</h1>
    </header>

    <div class="container">
        <h2>¡Gracias por tu pedido!</h2>
        <p>Hemos recibido tu solicitud y estaremos preparando tus productos. 🌼</p>
        <p>Tu número de pedido es:</p>
        <div class="pedido-id">#<?php echo $id_pedido; ?></div>
        <p>Recuerda que el pago es en **efectivo** al momento de recoger en nuestra tienda.</p>
        <p>¡Te esperamos!</p>
        <a href="index.php" class="btn-volver">&larr; Volver al Catálogo</a>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Flor y Hojaldra. Todos los derechos reservados.
    </footer>
</body>
</html>