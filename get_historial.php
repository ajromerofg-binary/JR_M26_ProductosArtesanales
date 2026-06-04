<?php
require_once('funcionAuth.php');
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

$pedido_id = intval($_GET['pedido_id'] ?? 0);
if (!$pedido_id) exit('<em style="color:#aaa;">ID de pedido inválido.</em>');

require_once('BD/conexion.php');

$stmt = $pdo->prepare("
    SELECT estado_anterior, estado_nuevo, fecha_cambio, comentario
    FROM PEDIDO_ESTADO_HISTORIAL
    WHERE pedido_id = ?
    ORDER BY fecha_cambio ASC
");
$stmt->execute([$pedido_id]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($historial)) {
    echo '<em style="color:#aaa;">Sin historial de cambios para este pedido.</em>';
    exit;
}

foreach ($historial as $h):
    $fecha      = date('d/m/Y H:i', strtotime($h['fecha_cambio']));
    $anterior   = htmlspecialchars($h['estado_anterior'] ?? 'Nuevo');
    $nuevo      = htmlspecialchars($h['estado_nuevo']);
    $comentario = htmlspecialchars($h['comentario'] ?? '');
?>
<div class="historial-item">
    <div class="historial-dot"></div>
    <div>
        <div>
            <strong><?= $anterior ?></strong>
            <span style="color:#aaa; margin:0 6px;">→</span>
            <strong style="color:var(--color-tierra-oscuro);"><?= $nuevo ?></strong>
        </div>
        <div style="color:#999; font-size:0.78rem; margin-top:2px;"><?= $fecha ?></div>
        <?php if ($comentario): ?>
        <div style="color:#555; font-size:0.82rem; margin-top:3px; font-style:italic;">"<?= $comentario ?>"</div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
