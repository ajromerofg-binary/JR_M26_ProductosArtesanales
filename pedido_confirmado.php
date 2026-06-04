<?php 
session_start();

require_once("modal_confirmacion.php"); // Incluye el archivo donde está definida la función mostrarModalConfirmacion().
$emailConfirmacion = $_SESSION['email_confirmacion'] ?? ''; // Recupera de la sesión el email al que se envió la confirmación.
unset($_SESSION['email_confirmacion']); // Elimina el email de la sesión para que el modal no aparezca otra vez al recargar la página.
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pedido confirmado</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100" style="background-color: var(--color-crema);">
<?php include 'navbar.php'; ?>
<main class="container mt-4 mb-5 flex-grow-1">

<div class="container text-center mt-5 mb-5">

    <h1 class="text-success mb-4">¡Pedido realizado correctamente!</h1>

    <p class="lead">Gracias por comprar en Luz de Hogar ❤️</p>

    <p>
        Número de pedido:
        <strong>#<?= htmlspecialchars($_GET['id'] ?? '') ?></strong>
    </p>

    <a href="tienda.php" class="btn btn-tierra mt-3">
        Volver a la tienda
    </a>

</div>
</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> <!-- Carga Bootstrap necesario para que funcione el modal. -->

<?php
if (!empty($emailConfirmacion)) { // Si existe un email de confirmación, se muestra la ventana emergente.
    mostrarModalConfirmacion($emailConfirmacion); // Si existe un email de confirmación, se muestra la ventana emergente.
}
?>

</body>
</html>

