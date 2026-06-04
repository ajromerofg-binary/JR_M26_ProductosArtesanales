<?php
require_once("funcionAuth.php");
require_once("BD/conexion.php");

// Solo clientes logueados pueden reseñar
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tienda.php");
    exit();
}

$producto_id = intval($_POST['producto_id'] ?? 0);
$estrellas   = intval($_POST['estrellas'] ?? 0);
$comentario  = trim($_POST['comentario'] ?? '');
$cliente_id  = $_SESSION['user']['id'];

// Validaciones básicas
if ($producto_id <= 0 || $estrellas < 1 || $estrellas > 5 || empty($comentario)) {
    header("Location: detalle.php?id=$producto_id&msj=error");
    exit();
}

try {
    // 1. Verificar que el cliente tiene un pedido ENTREGADO con este producto
    $stmtCheck = $pdo->prepare("
        SELECT pit.ID 
        FROM PEDIDO pe
        JOIN PEDIDO_ITEM pit ON pit.pedido_id = pe.ID
        WHERE pe.cliente_id = ? 
          AND pit.producto_id = ? 
          AND pe.estado = 'Entregado'
        LIMIT 1
    ");
    $stmtCheck->execute([$cliente_id, $producto_id]);
    if (!$stmtCheck->fetch()) {
        header("Location: detalle.php?id=$producto_id&msj=no_comprado");
        exit();
    }

    // 2. Verificar que NO tiene reseña previa para este producto
    $stmtYa = $pdo->prepare("SELECT ID FROM RESENA WHERE cliente_id = ? AND producto_id = ? LIMIT 1");
    $stmtYa->execute([$cliente_id, $producto_id]);
    if ($stmtYa->fetch()) {
        header("Location: detalle.php?id=$producto_id&msj=ya_valorado");
        exit();
    }

    // 3. Insertar la reseña (visible = 1 por defecto)
    $stmt = $pdo->prepare("
        INSERT INTO RESENA (producto_id, cliente_id, puntuacion, comentario, visible) 
        VALUES (?, ?, ?, ?, 1)
    ");
    $stmt->execute([$producto_id, $cliente_id, $estrellas, $comentario]);
    
    header("Location: detalle.php?id=$producto_id&msj=ok#resenas");
    exit();

} catch (PDOException $e) {
    header("Location: detalle.php?id=$producto_id&msj=error");
    exit();
}
?>