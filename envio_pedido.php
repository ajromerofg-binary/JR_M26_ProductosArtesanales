<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);


/* vendor/autoload.php carga PHPMailer y otras librerías Composer */
require_once("BD/conexion.php");
require_once("vendor/autoload.php");


/* Permite usar PHPMailer y Exception */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/* Busca una carpeta temporal / local donde guardar los PDFs.
   Esto evita problemas de permisos en Mac, Windows o XAMPP. */
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


/* Si no recibe id, usa 1 por defecto */
$pedidoId = $_GET['id'] ?? 1;


/* Obtiene datos de pedido y cliente para enviar por correo */
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
    die("No se encontró el pedido.");
}


/* Busca el PDF generado */
$carpetaPDF = obtenerCarpetaPdf();

$rutaPDF = $carpetaPDF
    . DIRECTORY_SEPARATOR . "pedido_" . $pedido['ID'] . ".pdf";

if (!file_exists($rutaPDF)) {
    die("No existe el PDF del pedido. Genera primero el PDF.");
}


/* preparar y enviar el email */
$mail = new PHPMailer(true);

try {

    /* Configuracion SMTP, en nuestro caso configura Gmail como servidor de salida
     IMPORTANTE: usa contraseña de aplicación, no la normal */
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'infoluzdehogar@gmail.com';
    $mail->Password   = ''; /*la contrseña va entre las comillas */
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';


    /* remitente y destinatario */
    $mail->setFrom('infoluzdehogar@gmail.com', 'Luz De Hogar');

    $mail->addAddress(
        $pedido['email'],
        $pedido['Nombre'] . ' ' . $pedido['Apellido']
    );


    /* adjunta el pdf generado */
    $mail->addAttachment($rutaPDF, "pedido_" . $pedido['ID'] . ".pdf");


    /*  Define el asunto, cuerpo HTML y cuerpo alternativo en texto plano */
    $mail->isHTML(true);
    $mail->Subject = "Resumen de tu pedido #" . $pedido['ID'];

    $mail->Body = "
        <h2>Gracias por tu compra en Luz De Hogar</h2>
        <p>Hola " . htmlspecialchars($pedido['Nombre']) . ",</p>
        <p>Te adjuntamos el resumen en PDF de tu pedido <strong>#" . $pedido['ID'] . "</strong>.</p>
        <p>Total del pedido: <strong>" . number_format((float)$pedido['total'], 2, ',', '.') . " €</strong></p>
        <p>Gracias por confiar en nosotros.</p>
    ";

    $mail->AltBody = "Gracias por tu compra en Luz De Hogar. Adjuntamos el PDF del pedido #" . $pedido['ID'];

    $mail->send();

    echo "Correo enviado correctamente a " . htmlspecialchars($pedido['email']);

/* Si PHPMailer falla, muestra el error */
} catch (Exception $e) {
    echo "Error al enviar el correo: " . $mail->ErrorInfo;
}