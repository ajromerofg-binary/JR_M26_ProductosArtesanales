<?php
session_start();

if (!isset($_SESSION['carrito'])) {
    header("Location: carrito.php");
    exit();
}

$id = $_GET['id'];
$action = $_GET['action'];

// Verifica que existe el producto
if (isset($_SESSION['carrito'][$id])) {

    switch ($action) {

        case 'sumar':
            $_SESSION['carrito'][$id]['cantidad']++;
            break;

        case 'restar':
            $_SESSION['carrito'][$id]['cantidad']--;

            // Si llega a 0 → eliminar
            if ($_SESSION['carrito'][$id]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$id]);
                $_SESSION['carrito'] = array_values($_SESSION['carrito']); // reindexar
            }
            break;

        case 'eliminar':
            unset($_SESSION['carrito'][$id]);
            $_SESSION['carrito'] = array_values($_SESSION['carrito']); // reindexar
            break;
    }
}

header("Location: carrito.php");
exit();