<?php 
session_start(); 
require_once("BD/conexion.php");
require_once("funciones_pedido.php");

/* Si no hay carrito */
if (empty($_SESSION['carrito'])) {
    header("Location: carrito.php");
    exit();
}

/* Si no está logueado */
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$cliente_id = $_SESSION['user']['id'];

/* ─── OBTENER DIRECCIÓN DE ENVÍO ─────────────────────────── */
$direccion_id = $_POST['direccion_id'] ?? null;

if (empty($direccion_id)) {
    // Si no viene por POST, buscar la predeterminada
    $stmtDir = $pdo->prepare("SELECT * FROM DIRECCION WHERE cliente_id = ? AND predeterminada = 1 LIMIT 1");
    $stmtDir->execute([$cliente_id]);
    $direccion = $stmtDir->fetch();
} else {
    $stmtDir = $pdo->prepare("SELECT * FROM DIRECCION WHERE ID = ? AND cliente_id = ? LIMIT 1");
    $stmtDir->execute([$direccion_id, $cliente_id]);
    $direccion = $stmtDir->fetch();
}

if (!$direccion) {
    die("Error: No se encontró una dirección de envío válida. <a href='carrito.php'>Volver al carrito</a>");
}

/* Construir texto snapshot de la dirección */
/*$direccion_snapshot = trim($direccion['calle'] . ', ' . $direccion['codigo_postal'] . ' ' . $direccion['ciudad'] . ', ' . $direccion['pais'], ', '); */
$destinatario = trim(
    ($direccion['nombre_destinatario'] ?? '') . ' ' .
    ($direccion['apellidos_destinatario'] ?? '')
);

$direccion_snapshot = trim(
    ($destinatario ? $destinatario . "\n" : '') .
    $direccion['calle'] . "\n" .
    $direccion['codigo_postal'] . ' ' . $direccion['ciudad'] . ', ' . $direccion['pais'] .
    (!empty($direccion['telefono']) ? "\nTel: " . $direccion['telefono'] : '')
);

/* ─── OBTENER DATOS DEL CLIENTE PARA SNAPSHOT ────────────── */
$stmtCli = $pdo->prepare("SELECT Nombre, Apellido, email, telefono FROM CLIENTE WHERE ID = ?");
$stmtCli->execute([$cliente_id]);
$cliente = $stmtCli->fetch();

/* Calcular total */
$total = 0;
foreach ($_SESSION['carrito'] as $producto) {
    $total += $producto['precio'] * $producto['cantidad'];
}
//Tokens
$tokens_usados = isset($_POST['tokens_usados']) ? (int)$_POST['tokens_usados'] : 0;

//validacion 80%
$max_tokens_permitidos = (int)floor($total * 0.8);
if ($tokens_usados > $max_tokens_permitidos) {
    die("Error: Solo puedes usar un máximo de {$max_tokens_permitidos} tokens (80% del total del carrito). <a href='carrito.php'>Volver al carrito</a>");
}

//validacion de saldo
if ($tokens_usados > 0) {
    $stmtT = $pdo->prepare("SELECT tokens FROM CLIENTE WHERE ID = ?");
    $stmtT->execute([$cliente_id]);
    $tokens_actuales = (int)$stmtT->fetchColumn();

    if ($tokens_usados > $tokens_actuales) {
        die("Error: No tienes suficientes tokens. <a href='carrito.php'>Volver al carrito</a>");
    }
    if ($tokens_usados > $total) {
        $tokens_usados = (int)floor($total);
    }
}

$total_final = $total - $tokens_usados;
if ($total_final < 0) $total_final = 0;

$tokens_ganados = (int)floor($total_final / 10); // 1 token cada 10 € pagados
try {

    $pdo->beginTransaction();

    /* Crear pedido con snapshot */
    $sql = "INSERT INTO PEDIDO (
                fecha, Hora, estado, total, total_original, cliente_id,
                direccion_envio_id, direccion_envio_snapshot,
                cliente_nombre_snapshot, cliente_apellido_snapshot,
                cliente_email_snapshot, cliente_telefono_snapshot,
                tokens_usados, tokens_ganados
            ) VALUES (
                CURDATE(), CURTIME(), 'Pendiente', ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $total_final,
        $total,
        $cliente_id,
        $direccion['ID'],
        $direccion_snapshot,
        $cliente['Nombre'],
        $cliente['Apellido'],
        $cliente['email'],
        /*$cliente['telefono'], */
        $direccion['telefono'] ?? $cliente['telefono'],
        $tokens_usados,
        $tokens_ganados
    ]);

    $pedido_id = $pdo->lastInsertId();

    /* Insertar productos del pedido */
    $sqlItem = "INSERT INTO PEDIDO_ITEM
                (cantidad, precio_unitario, pedido_id, producto_id)
                VALUES (?, ?, ?, ?)";

    $stmtItem = $pdo->prepare($sqlItem);

    foreach ($_SESSION['carrito'] as $producto) {
        $stmtItem->execute([
            $producto['cantidad'],
            $producto['precio'],
            $pedido_id,
            $producto['id']
        ]);
    }

    /* Vaciar carrito de la BD (si existe) */
    $stmtCarrito = $pdo->prepare("SELECT ID FROM CARRITO WHERE cliente_id = ?");
    $stmtCarrito->execute([$cliente_id]);
    $carritoBD = $stmtCarrito->fetch();
    if ($carritoBD) {
        $pdo->prepare("DELETE FROM CARRITO_ITEM WHERE carrito_id = ?")->execute([$carritoBD['ID']]);
    }

    /* Actualizar saldo de tokens*/
    $pdo->prepare("UPDATE CLIENTE SET tokens = tokens - ? + ? WHERE ID = ?")
        ->execute([$tokens_usados, $tokens_ganados, $cliente_id]);
    
    $pdo->commit();
    /* Generar PDF y enviar email */
    try {
        $rutaPDF = generarPdfPedido($pdo, $pedido_id);
        enviarEmailPedido($pdo, $pedido_id, $rutaPDF);
    } catch (Throwable $e) {
        error_log("Error PDF/email pedido #" . $pedido_id . ": " . $e->getMessage());
    }

    /* Obtener email del cliente para mostrarlo en el modal */
    $_SESSION['email_confirmacion'] = $cliente['email'];

    /* Vaciar carrito de sesión */
    unset($_SESSION['carrito']);

    /* Mensaje */
    $_SESSION['mensaje_pago'] = "Pedido realizado correctamente";

    session_write_close();

    header("Location: pedido_confirmado.php?id=" . $pedido_id);
    exit();

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "Error al procesar pedido: " . $e->getMessage();
}
?>