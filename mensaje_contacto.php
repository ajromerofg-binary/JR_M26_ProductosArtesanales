<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso no permitido');
}

$email = trim($_POST['email'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if ($email === '' || $mensaje === '') {
    die('Todos los campos son obligatorios');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('El email no es válido');
}

if (mb_strlen($mensaje) < 5) {
    die('El mensaje es demasiado corto');
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'infoluzdehogar@gmail.com';
    $mail->Password = ''; /* entre las comillas hay que poner las contraseña que mande por whatsapp para que envie el mensaje al correo */
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->CharSet = 'UTF-8';

    $mail->setFrom('infoluzdehogar@gmail.com', 'Luz de Hogar');
    $mail->addAddress('infoluzdehogar@gmail.com');
    $mail->addReplyTo($email);

    $mail->Subject = 'Nuevo mensaje desde el formulario de contacto';
    $mail->Body =
        "Has recibido un nuevo mensaje desde la web.\n\n" .
        "Email del remitente: $email\n\n" .
        "Mensaje:\n$mensaje";

    $mail->send();

    
} catch (Exception $e) {
    echo "Error al enviar el mensaje: {$mail->ErrorInfo}";
}
?>

   <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
         <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
        <title>Mensaje enviado</title>
        <style>
            body {
                font-family: "Lato", sans-serif;
                text-align: center;
                padding: 50px;
                background: #FDFBD7;
            }
            .box {
                background: #E5D3B3;
                max-width: 500px;
                margin: auto;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .btn {
                
                display: inline-block;
                margin-top: 20px;
                padding: 12px 24px;
                background: #C19A6B;
                color: #333333;
                text-decoration: none;
                border-radius: 6px;
                font-family: "Lato", sans-serif;
            font-size: 14px;
}
            
            .btn:hover {
                background: #D4B896;
            }
        </style>
       
    </head>
    <body>
        <div class="box">
            <h2>Mensaje enviado correctamente</h2>
            <a class="btn" href="home.php">Volver al inicio</a>
        </div>
        
    </body>
    </html>
    
