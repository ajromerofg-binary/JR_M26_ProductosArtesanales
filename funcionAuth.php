<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header("Location: index.php");
        exit();
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
}
?>
