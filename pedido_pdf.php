<?php
/* Muestra todos los errores durante desarrollo */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once("BD/conexion.php");
require_once("vendor/autoload.php");


/* Dompdf genera el PDF.
   Options permite configurar Dompdf */

use Dompdf\Dompdf;
use Dompdf\Options;

/* Busca una carpeta donde PHP pueda guardar archivos PDF.
Prueba varias rutas para evitar problemas de permisos */

function obtenerCarpetaPdf() {
    $posiblesCarpetas = [
        ini_get('upload_tmp_dir'),
        sys_get_temp_dir(),
        __DIR__ . DIRECTORY_SEPARATOR . 'pedidos_pdf'
    ];

    foreach ($posiblesCarpetas as $carpetaBase) {
        if (empty($carpetaBase)) {
            continue;
        }

        $carpetaPDF = $carpetaBase . DIRECTORY_SEPARATOR . "luz_de_hogar_pedidos";

        if (!is_dir($carpetaPDF)) {
            @mkdir($carpetaPDF, 0775, true);
        }

        if (is_dir($carpetaPDF) && is_writable($carpetaPDF)) {
            return $carpetaPDF;
        }
    }

    die("No se encontró una carpeta con permisos para guardar el PDF.");
}


   /* Si no recibe ID, usa 1 por defecto */

$pedidoId = $_GET['id'] ?? 1;

/* Obtiene los datos generales del pedido y del cliente. */
$sqlPedido = "
    SELECT 
        p.ID,
        p.fecha,
        p.Hora,
        p.estado,
        p.total,
        c.Nombre,
        c.Apellido,
        c.email,
        c.telefono,
        c.direccion
    FROM PEDIDO p
    INNER JOIN CLIENTE c ON p.cliente_id = c.ID
    WHERE p.ID = :pedido_id
";

$stmtPedido = $pdo->prepare($sqlPedido);
$stmtPedido->execute(['pedido_id' => $pedidoId]);
$pedido = $stmtPedido->fetch();

if (!$pedido) {
    die("No se encontró el pedido.");
}


/* Obtiene los productos, cantidades y precios del pedido */

$sqlProductos = "
    SELECT 
        pr.nombre,
        pi.cantidad,
        pi.precio_unitario
    FROM PEDIDO_ITEM pi
    INNER JOIN PRODUCTO pr ON pi.producto_id = pr.ID
    WHERE pi.pedido_id = :pedido_id
";

$stmtProductos = $pdo->prepare($sqlProductos);
$stmtProductos->execute(['pedido_id' => $pedidoId]);
$productos = $stmtProductos->fetchAll();


/* Convierte números a formato euro español */
function euros($cantidad) {
    return number_format((float)$cantidad, 2, ',', '.') . ' €';
}


/* Usa logo.jpg porque Dompdf lo interpreta mejor que algunos PNG */
$logoPath = realpath(__DIR__ . "/img/logo.jpg");

$logoSrc = "";

if ($logoPath && file_exists($logoPath)) {
    $logoSrc = "file://" . $logoPath;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #<?= htmlspecialchars($pedido['ID']) ?></title>

    <style>
        :root {
            --color-crema: #FDFBD7;
            --color-tierra-claro: #D4B896;
            --color-tierra-medio: #C19A6B;
            --color-tierra-oscuro: #A66641;
            --color-texto: #333333;
            --color-borde: #D4B896;
            --color-blanco-roto: #FFFDFB;
            --color-franja-hero: #EEDCBE;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            background-color: #ffffff;
            color: var(--color-texto);
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .pedido-wrapper {
            width: 100%;
            border: 1px solid var(--color-borde);
            background-color: var(--color-blanco-roto);
        }

        .pedido-header {
            position: relative;
            background-color: var(--color-tierra-claro);
            padding: 25px 30px;
            text-align: center;
            min-height: 90px;
        }

        .logo-pedido {
            position: absolute;
            left: 40px;
            top: 40px;
            width: 70px;
            height: auto;
        }

        .texto-header {
            text-align: center;
        }

        .pedido-header h1 {
            margin: 0 0 5px 0;
            font-family: Georgia, serif;
            font-size: 28px;
        }

        .pedido-header p {
            margin: 0;
            font-size: 14px;
        }

        .pedido-body {
            padding: 25px 30px;
        }

        .seccion-titulo {
            color: var(--color-tierra-oscuro);
            border-bottom: 2px solid var(--color-tierra-claro);
            padding-bottom: 6px;
            margin-bottom: 12px;
            font-family: Georgia, serif;
            font-size: 20px;
        }

        .bloque {
            margin-bottom: 25px;
        }

        .info-card {
            background-color: #fffdfb;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            padding: 12px 15px;
        }

        .info-card p {
            margin: 0 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead th {
            background-color: var(--color-franja-hero);
            border: 1px solid var(--color-borde);
            padding: 10px;
            text-align: left;
        }

        tbody td {
            border: 1px solid var(--color-borde);
            padding: 10px;
        }

        .text-end {
            text-align: right;
        }

        .resumen-total {
            margin-top: 20px;
            margin-left: auto;
            width: 280px;
            background-color: var(--color-crema);
            border: 1px solid var(--color-borde);
            padding: 12px 15px;
        }

        .resumen-total .fila.total {
            border-top: 2px solid var(--color-tierra-medio);
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 16px;
            color: var(--color-tierra-oscuro);
        }

        .mensaje-final {
            background-color: var(--color-crema);
            border-left: 5px solid var(--color-tierra-medio);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            margin-top: 25px;
        }

        .pedido-footer {
            background-color: var(--color-tierra-claro);
            text-align: center;
            padding: 15px;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="pedido-wrapper">

    <div class="pedido-header">
        <?php if (!empty($logoSrc)): ?>
            <img src="<?= $logoSrc ?>" class="logo-pedido">
        <?php endif; ?>

        <div class="texto-header">
            <h1>Luz De Hogar</h1>
            <p>Resumen de compra</p>
        </div>
    </div>

    <div class="pedido-body">
        <div class="bloque">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="width:48%; vertical-align:top; border:none; padding:0 10px 0 0;">
                        <h2 class="seccion-titulo">Datos del pedido</h2>
                        <div class="info-card">
                            <p><strong>Número de pedido:</strong> #<?= htmlspecialchars($pedido['ID']) ?></p>
                            <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['fecha']) ?></p>
                            <p><strong>Hora:</strong> <?= htmlspecialchars($pedido['Hora']) ?></p>
                            <p><strong>Estado:</strong> <?= htmlspecialchars($pedido['estado']) ?></p>
                        </div>
                    </td>

                    <td style="width:48%; vertical-align:top; border:none; padding:0 0 0 10px;">
                        <h2 class="seccion-titulo">Datos del cliente</h2>
                        <div class="info-card">
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($pedido['Nombre'] . ' ' . $pedido['Apellido']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
                            <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono'] ?? '') ?></p>
                            <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion'] ?? '') ?></p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Recorre los productos del pedido y calcula subtotal -->
        <div class="bloque">
            <h2 class="seccion-titulo">Productos</h2>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th class="text-end">Precio unitario</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td><?= (int)$producto['cantidad'] ?></td>
                                <td class="text-end"><?= euros($producto['precio_unitario']) ?></td>
                                <td class="text-end"><?= euros($producto['cantidad'] * $producto['precio_unitario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">
                                Este pedido no tiene productos asociados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- total del pedido -->
            <div class="resumen-total">
                <div class="fila total">
                    <span>Total: <?= euros($pedido['total']) ?></span>
                </div>
            </div>
        </div>

        <div class="mensaje-final">
            Gracias por tu compra. Hemos recibido tu pedido correctamente y comenzaremos a prepararlo lo antes posible.
        </div>

    </div>

    <div class="pedido-footer">
        <strong>Luz De Hogar</strong><br>
        © 2026 Luz de Hogar - Hecho a mano en España - infoluzdehogar@gmail.com
    </div>

</div>

</body>
</html>
<?php

$html = ob_get_clean();

/* Configuracion de Dompdf
   isRemoteEnabled permite cargar recursos externos/locales.
   chroot limita el acceso de Dompdf a la carpeta del proyecto */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('chroot', __DIR__);


/* Carga el HTML, define tamaño A4 vertical y genera el PDF */
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();


/* Obtiene contenido del PDF
   output() devuelve el PDF como cadena binaria */
$pdfGenerado = $dompdf->output();


/* Guarda pdf en carpeta
   Obtiene una carpeta válida y guarda el PDF como pedido_ID.pdf */
$carpetaPDF = obtenerCarpetaPdf();

$rutaPDF = $carpetaPDF
    . DIRECTORY_SEPARATOR . "pedido_" . $pedido['ID'] . ".pdf";

if (file_put_contents($rutaPDF, $pdfGenerado) === false) {
    die("No se pudo guardar el PDF.");
}


/* Evita que warnings, espacios o HTML anterior corrompan el PDF */
while (ob_get_level() > 0) {
    ob_end_clean();
}


/* Muestra el PDF inline en vez de forzar descarga */
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="pedido_' . $pedido['ID'] . '.pdf"');
header('Content-Length: ' . strlen($pdfGenerado));

echo $pdfGenerado;
exit;