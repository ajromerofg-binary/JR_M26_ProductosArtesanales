<?php
session_start();

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$producto = [
    "id" => $_POST['id'],
    "nombre" => $_POST['nombre'],
    "precio" => (float)$_POST['precio'],
    "imagen" => $_POST['imagen'],
    "cantidad" => $_POST['cantidad'] ? (int)$_POST['cantidad'] : 1
];

$existe = false;

foreach ($_SESSION['carrito'] as &$item) {
    if ($item['nombre'] === $producto['nombre']) {
        $item['cantidad']++;
        $existe = true;
        break;
    }
}

if (!$existe) {
    $_SESSION['carrito'][] = $producto;
}

/* MENSAJE FLASH */
$_SESSION['mensaje'] = "Producto añadido al carrito";


// CAPTURAR POSICIÓN
$seccion = isset($_POST['seccion']) ? $_POST['seccion'] : "";

// REDIRIGIR CON ANCLA
if (!empty($seccion)) {
    header("Location: tienda.php#" . $seccion);
} else {
    header("Location: tienda.php");
}
exit();