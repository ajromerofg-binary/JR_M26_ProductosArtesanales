<?php
require_once("../funcionAuth.php");
require_once("conexion.php");
require_once("../vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoRegistro($emailCliente, $nombreCliente) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'infoluzdehogar@gmail.com';
        $mail->Password   = 'buywfbglkzgychrl'; // Contraseña de aplicación de Gmail. IMPORTANTE: NO dejar contraseñas escritas directamente en el código.
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('infoluzdehogar@gmail.com', 'Luz De Hogar');
        $mail->addAddress($emailCliente, $nombreCliente);

        $mail->isHTML(true);
        $mail->Subject = "Bienvenido/a a Luz de Hogar";

       $mail->Body = "
            <div style='background-color:#FDFBD7; padding:25px; font-family:Arial, sans-serif; color:#333333;'>

                 <div style='background-color:#FFFDFB; border:1px solid #D4B896; border-radius:12px; padding:20px;'>

                    <h2 style='color:#A66641;'>Bienvenido/a a Luz de Hogar</h2>

                        <p>Hola <strong>" . htmlspecialchars($nombreCliente) . "</strong>,</p>

                        <p>Te has registrado correctamente en nuestra tienda.</p>

                        <p>Como nuevo cliente, te regalamos un cupón de descuento del 
                            <strong>5%</strong> para tus próximas compras.
                         </p>

                        <p style='background-color:#EEDCBE; padding:12px; border-radius:8px; text-align:center;'>
                            <strong>Cupón: BIENVENIDA5</strong>
                        </p>

                    <p>Gracias por confiar en Luz de Hogar.</p>

                </div>

            </div>
        ";

        $mail->AltBody = "Te has registrado correctamente en Luz de Hogar. Cupón de bienvenida: BIENVENIDA5 con un 5% de descuento.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error al enviar correo de registro: " . $mail->ErrorInfo);
        return false;
    }
}

try{
    //1. Conexion con PDO
    //$pdo = new PDO($dsn, $user, $pass, $options);

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $email = $_POST['email'] ?? '';
        $passRaw = $_POST['password'] ?? '';
        $confirmPass = $_POST['confirmPassword'] ?? '';

        // VALIDAR CAMPOS VACÍOS
        if (empty($nombre) || empty($apellido) || empty($email) || empty($passRaw)) {
            $_SESSION['mensaje_error'] = "Todos los campos son obligatorios"; //Estos mensajes van para IndexRegistro
            $_SESSION['old'] = $_POST;
            header("Location: ../IndexRegistro.php");
            exit;
        }

        // VALIDAR CLAVE
        if ($passRaw !== $confirmPass){
            $_SESSION['mensaje_error'] = "Las contraseñas no coinciden";
            $_SESSION['old'] = $_POST;
            header("Location: ../IndexRegistro.php");
            exit;
        }

        // VALIDAR FORMATO EMAIL
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['mensaje_error'] = "El correo no tiene un formato válido"; //Estos mensajes van para IndexRegistro
            $_SESSION['old'] = $_POST;
            header("Location: ../IndexRegistro.php");
            exit;
        }

        // VALIDACION DE DUPLICADOS
        $checkEmail = $pdo->prepare("SELECT ID FROM cliente WHERE email = ?");
        $checkEmail->execute([$email]);

        if ($checkEmail->rowCount() > 0){
            //❌ ERROR: email ya existe
            $_SESSION['mensaje_error'] = "El correo ya está registrado"; //Estos mensajes van para IndexRegistro
            $_SESSION['old'] = $_POST;
            header("Location: ../IndexRegistro.php");
            exit;
        }else{

            //2. Encriptar la contraseña
            $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);

            //3. Preparamos consulta BD
            $sql = "INSERT INTO cliente (Nombre, Apellido, email, contraseña) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            //4. Ejecutar, enviar correo y guardar
            if ($stmt->execute([$nombre, $apellido, $email, $passwordHash])) {

            enviarCorreoRegistro($email, $nombre);

                //✅ ÉXITO
                $_SESSION['mensaje_exito'] = "Usuario creado con éxito"; //Este mensaje va para index
                header('Location: ../index.php');
                exit;
            }
        }
    }
}catch(\PDOException $e){
    $_SESSION['mensaje_error'] = "Error en el registro";
    header("Location: ../IndexRegistro.php");
}

?>
