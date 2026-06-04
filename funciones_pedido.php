<?php
// Carga automáticamente las librerías Dompdf y PHPMailer.
require_once __DIR__ . "/vendor/autoload.php";

// Importa Dompdf para generar PDFs.
use Dompdf\Dompdf;

// Importa las opciones de Dompdf.
use Dompdf\Options;

// Importa PHPMailer para enviar correos.
use PHPMailer\PHPMailer\PHPMailer;

// Importa la clase Exception de PHPMailer para controlar errores.
use PHPMailer\PHPMailer\Exception;

// Busca una carpeta válida donde guardar temporalmente los PDFs generados
function obtenerCarpetaPdf() {
    $posiblesCarpetas = [
        ini_get('upload_tmp_dir'),
        sys_get_temp_dir(),
        __DIR__ . DIRECTORY_SEPARATOR . 'pedidos_pdf'
    ];

    // Recorre las carpetas y si estan vacías pasan a la siguente.
    foreach ($posiblesCarpetas as $carpetaBase) {
        if (empty($carpetaBase)) {
            continue;
        }

        // Crea la ruta donde se guardarán los PDFs de pedidos.
        $carpetaPDF = $carpetaBase . DIRECTORY_SEPARATOR . "luz_de_hogar_pedidos";

        // Si la carpeta no existe, intenta crearla.
        if (!is_dir($carpetaPDF)) {
            @mkdir($carpetaPDF, 0775, true);
        }

        // Comprueba que la carpeta exista y que PHP tenga permisos de escritura.
        if (is_dir($carpetaPDF) && is_writable($carpetaPDF)) {
            return $carpetaPDF;
        }
    }

    // Si ninguna carpeta es válida, lanza un error.
    throw new Exception("No se encontró una carpeta con permisos para guardar el PDF.");
}

// moneda en euros
function euros($cantidad) {
    return number_format((float)$cantidad, 2, ',', '.') . ' €';
}

// Genera un PDF con el resumen de un pedido.
function generarPdfPedido(PDO $pdo, int $pedidoId) {
    // consulta datos del pedido y del cliente 
   $sqlPedido = "
    SELECT 
        p.ID,
        p.fecha,
        p.Hora,
        p.estado,
        p.total,
        p.direccion_envio_snapshot,
        p.cliente_nombre_snapshot,
        p.cliente_apellido_snapshot,
        p.cliente_email_snapshot,
        p.cliente_telefono_snapshot
    FROM PEDIDO p
    WHERE p.ID = :pedido_id
";
   
    $stmtPedido = $pdo->prepare($sqlPedido);
    $stmtPedido->execute(['pedido_id' => $pedidoId]);
    $pedido = $stmtPedido->fetch();
    if (!$pedido) {
        throw new Exception("No se encontró el pedido.");
    }

    $sqlProductos = "
        SELECT 
            pr.nombre,
            pi.cantidad,
            pi.precio_unitario
        FROM PEDIDO_ITEM pi
        INNER JOIN PRODUCTO pr ON pi.producto_id = pr.ID
        WHERE pi.pedido_id = :pedido_id
    ";

    // consulta de productos.
    $stmtProductos = $pdo->prepare($sqlProductos);

    // consulta el ID del pedido.
    $stmtProductos->execute(['pedido_id' => $pedidoId]);

    // Guarda todos los productos del pedido
    $productos = $stmtProductos->fetchAll();

    $logoPath = realpath(__DIR__ . "/img/logo.jpg");
    $logoSrc = "";

    // Si el logo existe, prepara su ruta con file:// para que Dompdf pueda leerlo.
    if ($logoPath && file_exists($logoPath)) {
        $logoSrc = "file://" . $logoPath;
    }

    ob_start();
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
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
            <div>
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
                                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($pedido['fecha'])) ?></p>
                                <p><strong>Hora:</strong> <?= htmlspecialchars($pedido['Hora']) ?></p>
                                <p><strong>Estado:</strong> <?= htmlspecialchars($pedido['estado']) ?></p>
                            </div>
                        </td>
                        <td style="width:48%; vertical-align:top; border:none; padding:0 0 0 10px;">
                            <h2 class="seccion-titulo">Datos del cliente</h2>
                            <div class="info-card">
                                <p><strong>Nombre:</strong> <?= htmlspecialchars($pedido['cliente_nombre_snapshot'] . ' ' . $pedido['cliente_apellido_snapshot']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($pedido['cliente_email_snapshot']) ?></p>
                                <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['cliente_telefono_snapshot'] ?? '') ?></p>
                                <p><strong>Dirección:</strong><br><?= nl2br(htmlspecialchars($pedido['direccion_envio_snapshot'] ?? '')) ?></p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

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

                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td><?= (int)$producto['cantidad'] ?></td>
                                <td class="text-end"><?= euros($producto['precio_unitario']) ?></td>
                                <td class="text-end"><?= euros($producto['cantidad'] * $producto['precio_unitario']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

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

    // Crea un objeto de opciones para Dompdf.
    $options = new Options();

    // Permite cargar recursos externos o rutas 
    $options->set('isRemoteEnabled', true);

    // Limita el acceso de Dompdf al directorio actual por seguridad.
    $options->set('chroot', __DIR__);

    // Crea el generador PDF con las opciones anteriores.
    $dompdf = new Dompdf($options);

    // Carga el HTML que se convertirá en PDF.
    $dompdf->loadHtml($html, 'UTF-8');

    // Define el tamaño y orientación del PDF.
    $dompdf->setPaper('A4', 'portrait');

    // Genera el PDF.
    $dompdf->render();
    $pdfGenerado = $dompdf->output();

    // donde guardar el PDF.
    $carpetaPDF = obtenerCarpetaPdf();

    // ruta completa del archivo PDF.
    $rutaPDF = $carpetaPDF
        . DIRECTORY_SEPARATOR . "pedido_" . $pedido['ID'] . ".pdf";

    // Guarda el PDF en servidor.
    if (file_put_contents($rutaPDF, $pdfGenerado) === false) {
        throw new Exception("No se pudo guardar el PDF.");
    }

    return $rutaPDF;
}

// Envía por email el PDF de un pedido concreto.
function enviarEmailPedido(PDO $pdo, int $pedidoId, string $rutaPDF) {
    // datos básicos del pedido y del cliente.
    $sql = "
        SELECT 
            p.ID,
            p.total,
            c.Nombre,
            c.Apellido,
            c.email
        FROM PEDIDO p
        INNER JOIN CLIENTE c ON p.cliente_id = c.ID
        WHERE p.ID = :pedido_id
    ";

    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pedido_id' => $pedidoId]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        throw new Exception("No se encontró el pedido para enviar el email.");
    }

    // Comprueba que el PDF exista antes de adjuntarlo.
    if (!file_exists($rutaPDF)) {
        throw new Exception("No existe el PDF para adjuntar.");
    }

    // Crea una instancia de PHPMailer.
    $mail = new PHPMailer(true);

    // Configura PHPMailer para enviar mediante SMTP.
    $mail->isSMTP();

    // Servidor SMTP de Gmail.
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'infoluzdehogar@gmail.com';
    $mail->Password   = 'buywfbglkzgychrl'; // Contraseña de aplicación de Gmail. IMPORTANTE: no conviene dejar contraseñas escritas directamente en el código.
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Define el remitente
    $mail->setFrom('infoluzdehogar@gmail.com', 'Luz De Hogar');

    // añade destinatario el email del cliente.
    $mail->addAddress(
        $pedido['email'],
        $pedido['Nombre'] . ' ' . $pedido['Apellido']
    );

    // Adjunta el PDF generado.
    $mail->addAttachment($rutaPDF, "pedido_" . $pedido['ID'] . ".pdf");

    // Indica que el cuerpo del correo está en formato HTML.
    $mail->isHTML(true);

    // Asunto del correo.
    $mail->Subject = "Resumen de tu pedido #" . $pedido['ID'];

    // Cuerpo HTML del correo.
    $mail->Body = "
        <h2>Gracias por tu compra en Luz De Hogar</h2>
        <p>Hola " . htmlspecialchars($pedido['Nombre']) . ",</p>
        <p>Te adjuntamos el resumen en PDF de tu pedido <strong>#" . $pedido['ID'] . "</strong>.</p>
        <p>Total del pedido: <strong>" . number_format((float)$pedido['total'], 2, ',', '.') . " €</strong></p>
        <p>Gracias por confiar en nosotros.</p>
    ";

    // Versión en texto plano del correo, por si el cliente no puede ver HTML.
    $mail->AltBody = "Gracias por tu compra en Luz De Hogar. Adjuntamos el PDF del pedido #" . $pedido['ID'];

    // Envía el correo.
    $mail->send();

    // Devuelve true si el envío se completó correctamente.
    return true;
}