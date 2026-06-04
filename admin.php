<?php
require_once('funcionAuth.php');
requireAdmin();

// ─── CONEXIÓN ───────────────────────────────────────────────
require_once('BD/conexion.php');

// ─── ACCIONES POST ──────────────────────────────────────────
$mensaje      = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ── CREAR PRODUCTO ──────────────────────────────────────
    if ($accion === 'crear_producto') {
        $nombre      = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio      = floatval($_POST['precio']);
        $stock       = intval($_POST['stock']);
        $categoria   = intval($_POST['categoria_id']) ?: null;

        $stmt = $pdo->prepare("INSERT INTO PRODUCTO (nombre, descripcion, precio, stock, categoria_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoria]);
        $nuevo_id = $pdo->lastInsertId();

        if (!empty($_FILES['imagen']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $filename = 'img/prod_' . $nuevo_id . '.' . $ext;
            move_uploaded_file($_FILES['imagen']['tmp_name'], $filename);
            $pdo->prepare("INSERT INTO PRODUCTO_IMAGENES (producto_id, url_imagen, orden) VALUES (?, ?, 0)")
                ->execute([$nuevo_id, $filename]);
        }

        $mensaje = 'Producto creado correctamente.';
        $tipo_mensaje = 'ok';
    }

    // ── EDITAR PRODUCTO ─────────────────────────────────────
    if ($accion === 'editar_producto') {
        $id          = intval($_POST['id']);
        $nombre      = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio      = floatval($_POST['precio']);
        $stock       = intval($_POST['stock']);
        $categoria   = intval($_POST['categoria_id']) ?: null;

        $pdo->prepare("UPDATE PRODUCTO SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=? WHERE ID=?")
            ->execute([$nombre, $descripcion, $precio, $stock, $categoria, $id]);

        if (!empty($_FILES['imagen']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $filename = 'img/prod_' . $id . '.' . $ext;
            move_uploaded_file($_FILES['imagen']['tmp_name'], $filename);
            $check = $pdo->prepare("SELECT ID FROM PRODUCTO_IMAGENES WHERE producto_id=? ORDER BY orden LIMIT 1");
            $check->execute([$id]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE PRODUCTO_IMAGENES SET url_imagen=? WHERE producto_id=? ORDER BY orden LIMIT 1")
                    ->execute([$filename, $id]);
            } else {
                $pdo->prepare("INSERT INTO PRODUCTO_IMAGENES (producto_id, url_imagen, orden) VALUES (?, ?, 0)")
                    ->execute([$id, $filename]);
            }
        }

        $mensaje = 'Producto actualizado correctamente.';
        $tipo_mensaje = 'ok';
    }

    // ── ELIMINAR PRODUCTO ───────────────────────────────────
    if ($accion === 'eliminar_producto') {
        $id = intval($_POST['id']);
        $pdo->prepare("DELETE FROM PRODUCTO_IMAGENES WHERE producto_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM PRODUCTO WHERE ID=?")->execute([$id]);
        $mensaje = 'Producto eliminado.';
        $tipo_mensaje = 'ok';
    }

    // ── CAMBIAR ESTADO PEDIDO ───────────────────────────────
    if ($accion === 'cambiar_estado') {
        $pedido_id    = intval($_POST['pedido_id']);
        $estado_nuevo = trim($_POST['estado_nuevo']);
        $comentario   = trim($_POST['comentario'] ?? '');

        $row = $pdo->prepare("SELECT estado FROM PEDIDO WHERE ID=?");
        $row->execute([$pedido_id]);
        $estado_anterior = $row->fetchColumn();

        $pdo->prepare("UPDATE PEDIDO SET estado=? WHERE ID=?")
            ->execute([$estado_nuevo, $pedido_id]);

        $pdo->prepare("INSERT INTO PEDIDO_ESTADO_HISTORIAL (pedido_id, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, ?, ?)")
            ->execute([$pedido_id, $estado_anterior, $estado_nuevo, $comentario]);

        $mensaje = 'Estado del pedido actualizado.';
        $tipo_mensaje = 'ok';
    }

    // ── CREAR CATEGORÍA ─────────────────────────────────────
    if ($accion === 'crear_categoria') {
        $nombre      = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $pdo->prepare("INSERT INTO CATEGORIA (nombre, descripcion) VALUES (?, ?)")
            ->execute([$nombre, $descripcion]);
        $mensaje = 'Categoría creada correctamente.';
        $tipo_mensaje = 'ok';
    }

    // ── ELIMINAR CATEGORÍA ──────────────────────────────────
    if ($accion === 'eliminar_categoria') {
        $id = intval($_POST['id']);
        $check = $pdo->prepare("SELECT COUNT(*) FROM PRODUCTO WHERE categoria_id=?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $mensaje = 'No se puede eliminar: la categoría tiene productos asociados.';
            $tipo_mensaje = 'error';
        } else {
            $pdo->prepare("DELETE FROM CATEGORIA WHERE ID=?")->execute([$id]);
            $mensaje = 'Categoría eliminada.';
            $tipo_mensaje = 'ok';
        }
    }

    // ── MODERAR RESEÑA ──────────────────────────────────────
    if ($accion === 'publicar_resena') {
        $id = intval($_POST['id']);
        $pdo->prepare("UPDATE RESENA SET visible=1 WHERE ID=?")->execute([$id]);
        $mensaje = 'Reseña publicada correctamente.';
        $tipo_mensaje = 'ok';
    }

    if ($accion === 'ocultar_resena') {
        $id = intval($_POST['id']);
        $pdo->prepare("UPDATE RESENA SET visible=0 WHERE ID=?")->execute([$id]);
        $mensaje = 'Reseña ocultada.';
        $tipo_mensaje = 'ok';
    }

    if ($accion === 'eliminar_resena') {
        $id = intval($_POST['id']);
        $pdo->prepare("DELETE FROM RESENA WHERE ID=?")->execute([$id]);
        $mensaje = 'Reseña eliminada.';
        $tipo_mensaje = 'ok';
    }

    // ── ELIMINAR CLIENTE ────────────────────────────────────
    if ($accion === 'eliminar_cliente') {
        $id = intval($_POST['id']);
        $pdo->prepare("DELETE FROM CLIENTE WHERE ID=?")->execute([$id]);
        $mensaje = 'Cliente eliminado.';
        $tipo_mensaje = 'ok';
    }

    // ── CREAR EVENTO ────────────────────────────────────────
    if ($accion === 'crear_evento') {
        $titulo         = trim($_POST['titulo']);
        $descripcion    = trim($_POST['descripcion']);
        $fecha          = $_POST['fecha_evento'];
        $ubicacion_mapa = trim($_POST['ubicacion_mapa'] ?? '');
        $imagen = null;

            // Subir imagen si se ha seleccionado
            if (!empty($_FILES['imagen']['name'])) {
                $directorio = __DIR__ . '/img/eventos/';

                if (!is_dir($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $nombreArchivo = time() . '_' . basename($_FILES['imagen']['name']);
                $rutaFisica = $directorio . $nombreArchivo;
                $rutaBD = 'img/eventos/' . $nombreArchivo;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFisica)) {
                    $imagen = $rutaBD;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO EVENTO
                (titulo, descripcion, fecha_evento, ubicacion_mapa, url_imagen)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $titulo,
                $descripcion,
                $fecha,
                $ubicacion_mapa ?: null,
                $imagen
            ]);

            $mensaje = 'Evento creado.';
            $tipo_mensaje = 'ok';
        }

    // ── EDITAR EVENTO ────────────────────────────────────────
    if ($accion === 'editar_evento') {
    $id             = intval($_POST['id']);
    $titulo         = trim($_POST['titulo']);
    $descripcion    = trim($_POST['descripcion']);
    $fecha          = $_POST['fecha_evento'];
    $ubicacion_mapa = trim($_POST['ubicacion_mapa'] ?? '');

    // Mantener la imagen actual si no se sube una nueva
    $imagen = $_POST['imagen_actual'] ?? null;

    // Si se ha subido una nueva imagen
    if (!empty($_FILES['imagen']['name'])) {
        $directorio = __DIR__ . '/img/eventos/';

        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombreArchivo = time() . '_' . basename($_FILES['imagen']['name']);
        $rutaFisica = $directorio . $nombreArchivo;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFisica)) {
            // Guardar la ruta relativa completa en la base de datos
            $imagen = 'img/eventos/' . $nombreArchivo;
        }
    }

    $pdo->prepare("
        UPDATE EVENTO
        SET titulo = ?,
            descripcion = ?,
            fecha_evento = ?,
            ubicacion_mapa = ?,
            url_imagen = ?
        WHERE ID = ?
    ")->execute([
        $titulo,
        $descripcion,
        $fecha,
        $ubicacion_mapa ?: null,
        $imagen,
        $id
    ]);

    $mensaje = 'Evento actualizado.';
    $tipo_mensaje = 'ok';
}

    // ── ELIMINAR EVENTO ──────────────────────────────────────
    if ($accion === 'eliminar_evento') {
        $id = intval($_POST['id']);
        $pdo->prepare("DELETE FROM EVENTO WHERE ID=?")->execute([$id]);
        $mensaje = 'Evento eliminado.';
        $tipo_mensaje = 'ok';
    }
}



// ─── DATOS DASHBOARD ────────────────────────────────────────
$total_pedidos   = $pdo->query("SELECT COUNT(*) FROM PEDIDO")->fetchColumn();
$total_ingresos  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM PEDIDO WHERE estado != 'Cancelado'")->fetchColumn();
$total_clientes  = $pdo->query("SELECT COUNT(*) FROM CLIENTE")->fetchColumn();
$total_productos = $pdo->query("SELECT COUNT(*) FROM PRODUCTO")->fetchColumn();
$sin_stock       = $pdo->query("SELECT COUNT(*) FROM PRODUCTO WHERE stock = 0")->fetchColumn();

$ventas_mes = $pdo->query("
    SELECT DATE_FORMAT(fecha,'%b') AS mes, COALESCE(SUM(total),0) AS total
    FROM PEDIDO
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
      AND estado != 'Cancelado'
    GROUP BY YEAR(fecha), MONTH(fecha)
    ORDER BY YEAR(fecha), MONTH(fecha)
")->fetchAll();

// ─── DATOS SECCIONES ────────────────────────────────────────
$productos = $pdo->query("
    SELECT p.*, c.nombre AS categoria_nombre, pi.url_imagen
    FROM PRODUCTO p
    LEFT JOIN CATEGORIA c ON p.categoria_id = c.ID
    LEFT JOIN PRODUCTO_IMAGENES pi ON p.ID = pi.producto_id AND pi.orden = 0
    GROUP BY p.ID
    ORDER BY p.ID DESC
")->fetchAll();

$categorias = $pdo->query("
    SELECT c.*, COUNT(p.ID) AS num_productos
    FROM CATEGORIA c
    LEFT JOIN PRODUCTO p ON p.categoria_id = c.ID
    GROUP BY c.ID
    ORDER BY c.nombre
")->fetchAll();

$pedidos = $pdo->query("
    SELECT pe.*,
           CONCAT(cl.Nombre,' ',COALESCE(cl.Apellido,'')) AS cliente_nombre,
           cl.email AS cliente_email,
           cl.telefono AS cliente_telefono,
           cl.direccion AS cliente_direccion,
           COUNT(pit.ID) AS num_productos
    FROM PEDIDO pe
    LEFT JOIN CLIENTE cl ON pe.cliente_id = cl.ID
    LEFT JOIN PEDIDO_ITEM pit ON pit.pedido_id = pe.ID
    GROUP BY pe.ID
    ORDER BY pe.ID DESC
")->fetchAll();

// Productos de cada pedido
$pedidos_items = [];
if (count($pedidos) > 0) {
    $ids = implode(',', array_column($pedidos, 'ID'));
    $pi_stmt = $pdo->query("
        SELECT pit.pedido_id, pit.cantidad, pit.precio_unitario,
               p.ID AS producto_id, p.nombre AS producto_nombre,
               pi2.url_imagen
        FROM PEDIDO_ITEM pit
        JOIN PRODUCTO p ON p.ID = pit.producto_id
        LEFT JOIN PRODUCTO_IMAGENES pi2 ON pi2.producto_id = p.ID AND pi2.orden = 1
        WHERE pit.pedido_id IN ($ids)
        ORDER BY pit.pedido_id, p.nombre
    ");
    foreach ($pi_stmt->fetchAll() as $pi) {
        $pedidos_items[$pi['pedido_id']][] = $pi;
    }
}

// Pedidos pendientes (para alerta en sidebar)
$pedidos_pendientes = $pdo->query("
    SELECT COUNT(*) FROM PEDIDO WHERE estado = 'Pendiente'
")->fetchColumn();

$clientes = $pdo->query("
    SELECT cl.*, COUNT(pe.ID) AS num_pedidos,
           COALESCE(SUM(pe.total),0) AS total_gastado
    FROM CLIENTE cl
    LEFT JOIN PEDIDO pe ON pe.cliente_id = cl.ID
    GROUP BY cl.ID
    ORDER BY cl.ID DESC
")->fetchAll();

$pedidos_recientes = $pdo->query("
    SELECT pe.ID, pe.estado, pe.total, pe.fecha,
           CONCAT(cl.Nombre,' ',COALESCE(cl.Apellido,'')) AS cliente_nombre
    FROM PEDIDO pe
    LEFT JOIN CLIENTE cl ON pe.cliente_id = cl.ID
    ORDER BY pe.ID DESC LIMIT 5
")->fetchAll();

$top_productos = $pdo->query("
    SELECT p.nombre, p.precio, p.stock, pi.url_imagen,
           COALESCE(SUM(pit.cantidad),0) AS vendidos,
           c.nombre AS categoria_nombre
    FROM PRODUCTO p
    LEFT JOIN CATEGORIA c ON p.categoria_id = c.ID
    LEFT JOIN PRODUCTO_IMAGENES pi ON p.ID = pi.producto_id AND pi.orden = 0
    LEFT JOIN PEDIDO_ITEM pit ON p.ID = pit.producto_id
    GROUP BY p.ID
    ORDER BY vendidos DESC
    LIMIT 5
")->fetchAll();

$estados_pedido = ['Pendiente','Confirmado','En preparación','Enviado','Entregado','Cancelado'];

// ─── DATOS DE REGISTROS (CLIENTES) ──────────────────────────
// Los 5 clientes con ID más alto = los últimos registrados
$clientes_recientes = $pdo->query("
    SELECT ID, Nombre, Apellido, email
    FROM CLIENTE
    ORDER BY ID DESC
    LIMIT 5
")->fetchAll();

// Registros por mes:
// ⚠ La tabla CLIENTE del SQL no tiene campo fecha.
// Cuando añadas columna 'fecha_registro DATE' a CLIENTE,
// sustituye esta query por la comentada debajo.

// ── Versión SIN fecha — ya no necesaria ──
/*
$registros_por_mes = [];
if ($total_clientes > 0) {
    $registros_por_mes = [[
        'mes'   => date('M Y'),
        'total' => $total_clientes,
    ]];
}
*/

// ── Versión CON fecha (con fallback si no existe la columna) ──
try {
    $registros_por_mes = $pdo->query("
        SELECT DATE_FORMAT(fecha_registro, '%b %Y') AS mes,
               DATE_FORMAT(fecha_registro, '%Y-%m')  AS mes_orden,
               COUNT(*) AS total
        FROM CLIENTE
        WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY mes_orden
        ORDER BY mes_orden ASC
    ")->fetchAll();
} catch (PDOException $e) {
    // Si no existe la columna, mostrar un solo punto con el total
    $registros_por_mes = [[
        'mes'   => 'Total',
        'total' => $total_clientes,
    ]];
}

// ─── DATOS EVENTOS ───────────────────────────────────────────
$eventos = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS EVENTO (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT,
        fecha_evento DATETIME NOT NULL,
        url_imagen VARCHAR(255),
        ubicacion_mapa TEXT,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $eventos = $pdo->query("
        SELECT * 
        FROM EVENTO 
        ORDER BY fecha_evento ASC
    ")->fetchAll();

} catch (PDOException $e) {
    $eventos = [];
}

$ahora = new DateTime();

$eventos_activos = array_filter(
    $eventos,
    fn($e) =>
        (new DateTime($e['fecha_evento']))->format('Y-m-d') === $ahora->format('Y-m-d')
);

$eventos_proximos = array_filter(
    $eventos,
    fn($e) =>
        new DateTime($e['fecha_evento']) > $ahora &&
        (new DateTime($e['fecha_evento']))->format('Y-m-d') !== $ahora->format('Y-m-d')
);

$eventos_pasados = array_filter(
    $eventos,
    fn($e) =>
        new DateTime($e['fecha_evento']) < $ahora &&
        (new DateTime($e['fecha_evento']))->format('Y-m-d') !== $ahora->format('Y-m-d')
);

$eventos_proximos = array_values($eventos_proximos);
$eventos_pasados  = array_reverse(array_values($eventos_pasados));

// ─── FUNCIONES AUXILIARES ────────────────────────────────────
function estadoClase(string $estado): string {
    return match(strtolower(trim($estado))) {
        'pendiente'                       => 'pendiente',
        'confirmado', 'en preparación',
        'en preparacion'                  => 'enviado',
        'enviado', 'entregado'            => 'activo',
        'cancelado'                       => 'cancelado',
        default                           => 'pendiente',
    };
}

function formProducto(array $categorias, array $prod = []): string {
    $nombre      = htmlspecialchars($prod['nombre']       ?? '');
    $descripcion = htmlspecialchars($prod['descripcion']  ?? '');
    $precio      = $prod['precio']       ?? '';
    $stock       = $prod['stock']        ?? '';
    $cat_id      = $prod['categoria_id'] ?? '';

    $opts = '<option value="">Sin categoría</option>';
    foreach ($categorias as $c) {
        $sel   = ($c['ID'] == $cat_id) ? 'selected' : '';
        $label = htmlspecialchars($c['nombre']);
        $opts .= "<option value=\"{$c['ID']}\" $sel>$label</option>";
    }

    return <<<HTML
    <div class="row g-3">
        <div class="col-md-6">
            <label class="admin-label">Nombre</label>
            <input type="text" name="nombre" class="admin-input w-100" value="$nombre" required>
        </div>
        <div class="col-md-3">
            <label class="admin-label">Precio (€)</label>
            <input type="number" name="precio" step="0.01" min="0" class="admin-input w-100" value="$precio" required>
        </div>
        <div class="col-md-3">
            <label class="admin-label">Stock</label>
            <input type="number" name="stock" min="0" class="admin-input w-100" value="$stock" required>
        </div>
        <div class="col-md-6">
            <label class="admin-label">Categoría</label>
            <select name="categoria_id" class="admin-input w-100">$opts</select>
        </div>
        <div class="col-md-6">
            <label class="admin-label">Imagen</label>
            <input type="file" name="imagen" class="admin-input w-100" accept="image/*">
        </div>
        <div class="col-12">
            <label class="admin-label">Descripción</label>
            <textarea name="descripcion" class="admin-input w-100" rows="2">$descripcion</textarea>
        </div>
    </div>
    HTML;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="admin-layout">
<!-- ════════ OVERLAY SIDEBAR (mobile) ════════ -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- ════════ SIDEBAR ════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div style="display:none; font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; color:var(--color-tierra-oscuro);">Luz de Hogar</div>
        <div style="font-size:0.7rem; text-transform:uppercase; letter-spacing:2px; color:#999; margin-top:4px;">Panel de Admin</div>
    </div>

    <div class="sidebar-label">Principal</div>
    <ul class="sidebar-nav">
        <li><a href="#" class="active" onclick="showPage('dashboard',this)">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a></li>
        <li><a href="#" onclick="showPage('pedidos',this)">
            <i class="bi bi-bag-check"></i> Pedidos
            <?php if($pedidos_pendientes > 0): ?>
            <span class="sidebar-badge"><?= $pedidos_pendientes ?> nuevos</span>
            <?php endif; ?>
        </a></li>
        <li><a href="#" onclick="showPage('productos',this)">
            <i class="bi bi-box-seam"></i> Productos
        </a></li>
        <li><a href="#" onclick="showPage('clientes',this)">
            <i class="bi bi-people"></i> Clientes
        </a></li>
        <li><a href="#" onclick="showPage('registros',this)">
            <i class="bi bi-person-plus"></i> Registros
            <?php if(count($clientes_recientes) > 0): ?>
            <span class="sidebar-badge"><?= count($clientes_recientes) ?></span>
            <?php endif; ?>
        </a></li>
    </ul>

    <div class="sidebar-label">Contenido</div>
    <ul class="sidebar-nav">
        <li><a href="#" onclick="showPage('categorias',this)">
            <i class="bi bi-tags"></i> Categorías
        </a></li>
        <li><a href="#" onclick="showPage('eventos',this)">
            <i class="bi bi-calendar-event"></i> Eventos
            <?php if(count($eventos_proximos) > 0): ?>
            <span class="sidebar-badge"><?= count($eventos_proximos) ?></span>
            <?php endif; ?>
        </a></li>
    </ul>

</aside>

<!-- ════════ MAIN ════════ -->
<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn-hamburger" id="btn-hamburger" onclick="toggleSidebar()" aria-label="Menú">
                <i class="bi bi-list" style="font-size:1.3rem;"></i>
            </button>
            <span class="topbar-title" id="page-title">Dashboard</span>
        </div>
        <div class="topbar-right">
            <?php if($sin_stock > 0): ?>
            <span class="sin-stock-alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $sin_stock ?> sin stock
            </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="flash-msg flash-<?= $tipo_mensaje ?>">
        <i class="bi bi-<?= $tipo_mensaje === 'ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
    <?php endif; ?>

    <div class="page-content">

    <!-- ════════ DASHBOARD ════════ -->
    <div id="page-dashboard" class="page-section active">
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-bag-check-fill"></i></div>
                    <div>
                        <div class="kpi-label">Pedidos</div>
                        <div class="kpi-value"><?= $total_pedidos ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-currency-euro"></i></div>
                    <div>
                        <div class="kpi-label">Ingresos</div>
                        <div class="kpi-value"><?= number_format($total_ingresos,2,',','.') ?>€</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="kpi-label">Clientes</div>
                        <div class="kpi-value"><?= $total_clientes ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-box-seam-fill"></i></div>
                    <div>
                        <div class="kpi-label">Productos</div>
                        <div class="kpi-value"><?= $total_productos ?></div>
                        <?php if($sin_stock > 0): ?>
                        <div class="kpi-sub"><?= $sin_stock ?> sin stock</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Registros recientes -->
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="section-card">
                    <div class="section-card-header">
                        <h5>Últimos registros</h5>
                        <a href="#" onclick="showPage('registros',null)" style="font-size:0.78rem;color:var(--color-tierra-oscuro);text-decoration:none;">Ver todos</a>
                    </div>
                    <div class="section-card-body" style="padding:0;">
                        <?php if(count($clientes_recientes) > 0): ?>
                        <table class="admin-table">
                            <thead><tr><th>#</th><th>Nombre</th><th>Email</th></tr></thead>
                            <tbody>
                            <?php foreach($clientes_recientes as $cr): ?>
                            <tr>
                                <td style="color:#aaa;font-size:0.8rem;">#<?= $cr['ID'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="order-avatar" style="width:28px;height:28px;font-size:0.7rem;">
                                            <?= mb_strtoupper(mb_substr($cr['Nombre']??'?',0,1).mb_substr($cr['Apellido']??'',0,1)) ?>
                                        </div>
                                        <?= htmlspecialchars(trim(($cr['Nombre']??'').' '.($cr['Apellido']??''))) ?>
                                    </div>
                                </td>
                                <td style="color:#999;font-size:0.85rem;"><?= htmlspecialchars($cr['email']??'—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p style="color:#aaa;text-align:center;padding:1.5rem 0;margin:0;">Sin registros todavía.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-card-header">
                        <h5>Ventas por mes</h5>
                        <span style="font-size:0.8rem;color:#999;">Últimos 12 meses</span>
                    </div>
                    <div class="section-card-body">
                        <?php if (count($ventas_mes) > 0):
                            $max_v = max(array_column($ventas_mes,'total')) ?: 1; ?>
                        <div class="bar-chart">
                            <?php foreach ($ventas_mes as $v): ?>
                            <div class="bar" style="height:<?= round(($v['total']/$max_v)*100) ?>%"
                                 title="<?= $v['mes'] ?>: <?= number_format($v['total'],2,',','.') ?>€"></div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;gap:6px;margin-top:4px;">
                            <?php foreach ($ventas_mes as $v): ?>
                            <div class="bar-label" style="flex:1;"><?= $v['mes'] ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color:#aaa;text-align:center;padding:2rem 0;">Sin datos de ventas todavía.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-card h-100">
                    <div class="section-card-header">
                        <h5>Pedidos recientes</h5>
                        <a href="#" onclick="showPage('pedidos',null)" style="font-size:0.78rem;color:var(--color-tierra-oscuro);text-decoration:none;">Ver todos</a>
                    </div>
                    <div class="section-card-body">
                        <?php if (count($pedidos_recientes) > 0):
                            foreach ($pedidos_recientes as $p): ?>
                        <div class="order-row">
                            <div class="order-avatar"><?= mb_strtoupper(mb_substr($p['cliente_nombre']??'?',0,2)) ?></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:700;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($p['cliente_nombre'] ?: 'Sin cliente') ?>
                                </div>
                                <div style="font-size:0.76rem;color:#999;">#<?= $p['ID'] ?> · <?= number_format($p['total'],2,',','.') ?>€</div>
                            </div>
                            <span class="badge-estado badge-<?= estadoClase($p['estado']) ?>"><?= htmlspecialchars($p['estado']) ?></span>
                        </div>
                        <?php endforeach; else: ?>
                        <p style="color:#aaa;text-align:center;padding:2rem 0;">Sin pedidos todavía.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="section-card">
                    <div class="section-card-header"><h5>Productos más vendidos</h5></div>
                    <div style="overflow-x:auto;">
                        <table class="admin-table">
                            <thead><tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Vendidos</th><th>Stock</th></tr></thead>
                            <tbody>
                            <?php if (count($top_productos) > 0):
                                foreach ($top_productos as $p): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <?php if ($p['url_imagen']): ?>
                                        <img src="<?= htmlspecialchars($p['url_imagen']) ?>" class="product-thumb" onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></td>
                                <td><?= number_format($p['precio'],2,',','.') ?>€</td>
                                <td><?= $p['vendidos'] ?></td>
                                <td>
                                    <?php if ($p['stock'] == 0): ?>
                                    <span class="badge-estado badge-cancelado">Sin stock</span>
                                    <?php elseif ($p['stock'] <= 3): ?>
                                    <span class="badge-estado badge-pendiente"><?= $p['stock'] ?> uds</span>
                                    <?php else: ?>
                                    <span class="badge-estado badge-activo"><?= $p['stock'] ?> uds</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align:center;color:#aaa;padding:2rem;">Sin productos todavía.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════ PEDIDOS ════════ -->
    <div id="page-pedidos" class="page-section">

        <!-- CABECERA -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 style="margin:0;">Gestión de Pedidos</h4>
                <p style="color:#999;font-size:0.85rem;margin:0;">
                    <?= count($pedidos) ?> pedidos en total
                    <?php if($pedidos_pendientes > 0): ?>
                    · <span style="color:var(--color-tierra-oscuro);font-weight:700;"><?= $pedidos_pendientes ?> pendientes</span>
                    <?php endif; ?>
                </p>
            </div>
        <!-- Filtros rápidos por estado -->
            <div class="pedidos-filtros">
                <button class="btn-admin btn-sm" onclick="filtrarPedidos('todos')" id="filtro-todos" style="opacity:1;">Todos</button>
                <button class="btn-admin btn-sm btn-cancelar" onclick="filtrarPedidos('Pendiente')" id="filtro-Pendiente">Pendientes</button>
                <button class="btn-admin btn-sm btn-cancelar" onclick="filtrarPedidos('Enviado')" id="filtro-Enviado">Enviados</button>
                <button class="btn-admin btn-sm btn-cancelar" onclick="filtrarPedidos('Entregado')" id="filtro-Entregado">Entregados</button>
            </div>
        </div>

        <!-- BUSCADOR AVANZADO -->
        <div class="section-card mb-4">
            <div class="section-card-body" style="padding:1rem 1.5rem;">
                <div class="row g-2 align-items-end">

                    <!-- Búsqueda libre -->
                    <div class="col-12 col-md-5">
                        <label class="admin-label">Nombre / apellidos del cliente</label>
                        <div style="position:relative;">
                            <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:0.9rem;"></i>
                            <input type="text" id="buscar-cliente" class="admin-input"
                                   style="width:100%;padding-left:32px;"
                                   placeholder="Ej: María García..."
                                   oninput="buscarPedidos()">
                        </div>
                    </div>

                    <!-- ID Pedido -->
                    <div class="col-6 col-md-2">
                        <label class="admin-label">Nº Pedido</label>
                        <input type="number" id="buscar-id" class="admin-input"
                               style="width:100%;"
                               placeholder="Ej: 12"
                               oninput="buscarPedidos()">
                    </div>

                    <!-- Número seguimiento -->
                    <div class="col-6 col-md-2">
                        <label class="admin-label">Nº Seguimiento</label>
                        <input type="text" id="buscar-seguimiento" class="admin-input"
                               style="width:100%;"
                               placeholder="LDH-000001"
                               oninput="buscarPedidos()">
                    </div>

                    <!-- ID Producto -->
                    <div class="col-6 col-md-1">
                        <label class="admin-label">ID Producto</label>
                        <input type="number" id="buscar-producto" class="admin-input"
                               style="width:100%;"
                               placeholder="Ej: 3"
                               oninput="buscarPedidos()">
                    </div>

                    <!-- Fecha -->
                    <div class="col-6 col-md-2">
                        <label class="admin-label">Fecha</label>
                        <input type="date" id="buscar-fecha" class="admin-input"
                               style="width:100%;"
                               oninput="buscarPedidos()">
                    </div>

                </div>

                <!-- Resultados y limpiar -->
                <div class="buscar-resultado-row">
                    <span id="buscar-resultado" style="font-size:0.82rem;color:#999;"></span>
                    <button onclick="limpiarBusqueda()" class="btn-limpiar">
                        <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                    </button>
                </div>
            </div>
        </div>

        <?php if (count($pedidos) > 0):
            foreach ($pedidos as $p): ?>

        <?php
            $seguimiento_num = 'LDH-' . str_pad($p['ID'], 6, '0', STR_PAD_LEFT);
            // Productos del pedido para búsqueda por ID producto
            $prod_ids_str = '';
            if (!empty($pedidos_items[$p['ID']])) {
                $prod_ids_str = implode(' ', array_column($pedidos_items[$p['ID']], 'producto_id'));
            }
        ?>
        <div class="section-card mb-3 pedido-card"
             data-estado="<?= htmlspecialchars($p['estado']) ?>"
             data-id="<?= $p['ID'] ?>"
             data-cliente="<?= htmlspecialchars(strtolower($p['cliente_nombre'] ?? '')) ?>"
             data-seguimiento="<?= strtolower($seguimiento_num) ?>"
             data-fecha="<?= $p['fecha'] ?? '' ?>"
             data-productos="<?= htmlspecialchars($prod_ids_str) ?>">
            <!-- Cabecera del pedido -->
            <div class="section-card-header pedido-header" onclick="toggleDetallePedido(<?= $p['ID'] ?>)">
                <div class="pedido-header-info">
                    <strong>#<?= $p['ID'] ?></strong>
                    <div>
                        <div style="font-weight:700; font-size:0.9rem;">
                            <?= htmlspecialchars($p['cliente_nombre'] ?: 'Sin cliente') ?>
                        </div>
                        <div style="font-size:0.75rem; color:#999;">
                            <?= htmlspecialchars($p['cliente_email'] ?? '') ?>
                            <?php if($p['cliente_telefono']): ?>
                            · <?= htmlspecialchars($p['cliente_telefono']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span style="color:#aaa; font-size:0.82rem;">
                        <?= $p['fecha'] ? date('d/m/Y', strtotime($p['fecha'])) : '—' ?>
                    </span>
                    <span class="badge-estado badge-<?= estadoClase($p['estado']) ?>">
                        <?= htmlspecialchars($p['estado']) ?>
                    </span>
                    <?php if (in_array($p['estado'], ['Enviado','Entregado'])): ?>
                    <span class="seguimiento-badge">
                        LDH-<?= str_pad($p['ID'], 6, '0', STR_PAD_LEFT) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="pedido-header-total">
                    <strong style="font-family:'Playfair Display',serif; font-size:1.1rem;">
                        <?= number_format($p['total'],2,',','.') ?>€
                    </strong>
                    <i class="bi bi-chevron-down" id="chevron-<?= $p['ID'] ?>" style="color:#aaa; transition:transform 0.2s;"></i>
                </div>
            </div>

            <!-- Detalle expandible -->
            <div id="detalle-pedido-<?= $p['ID'] ?>" style="display:none;">
                <div style="padding:1.25rem 1.5rem; border-top:1px solid var(--color-borde);">
                    <div class="row g-3">

                        <!-- Productos del pedido -->
                        <div class="col-lg-7">
                            <div class="pedido-detail-label">Productos</div>
                            <?php if (!empty($pedidos_items[$p['ID']])): ?>
                            <?php foreach ($pedidos_items[$p['ID']] as $pi): ?>
                            <div class="pedido-producto-row">
                                <?php if ($pi['url_imagen']): ?>
                                <img src="img/<?= htmlspecialchars($pi['url_imagen']) ?>"
                                     class="product-thumb" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div style="flex:1;">
                                    <div class="pedido-producto-nombre"><?= htmlspecialchars($pi['producto_nombre']) ?></div>
                                </div>
                                <div class="pedido-producto-qty">
                                    <?= $pi['cantidad'] ?> × <?= number_format($pi['precio_unitario'],2,',','.') ?>€
                                </div>
                                <div class="pedido-producto-total">
                                    <?= number_format($pi['cantidad'] * $pi['precio_unitario'],2,',','.') ?>€
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="pedido-subtotal">
                                <span style="color:#777;">Total: </span>
                                <strong><?= number_format($p['total'],2,',','.') ?>€</strong>
                            </div>
                            <?php else: ?>
                            <p style="color:#aaa; font-size:0.85rem;">Sin productos registrados en este pedido.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Panel derecho: dirección + acciones -->
                        <div class="col-lg-5">

                            <!-- Dirección de envío -->
                            <?php if ($p['cliente_direccion']): ?>
                            <div style="margin-bottom:1rem;">
                                <div class="pedido-detail-label">Dirección de envío</div>
                                <div class="direccion-box">
                                    <i class="bi bi-geo-alt" style="color:var(--color-tierra-oscuro);"></i>
                                    <?= htmlspecialchars($p['cliente_direccion']) ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Cambiar estado -->
                            <div style="margin-bottom:1rem;">
                                <div class="pedido-detail-label">Estado actual</div>
                                <span class="badge-estado badge-<?= estadoClase($p['estado']) ?>" style="font-size:0.8rem; padding:5px 12px;">
                                    <?= htmlspecialchars($p['estado']) ?>
                                </span>
                            </div>

                            <button class="btn-admin w-100 mb-2"
                                    onclick="abrirModalEstado(<?= $p['ID'] ?>, '<?= htmlspecialchars($p['estado'],ENT_QUOTES) ?>')">
                                <i class="bi bi-arrow-repeat me-1"></i> Cambiar estado
                            </button>

                            <button class="btn-admin btn-cancelar w-100"
                                    onclick="toggleHistorial(<?= $p['ID'] ?>)">
                                <i class="bi bi-clock-history me-1"></i> Ver historial
                            </button>

                            <!-- Historial inline -->
                            <div id="historial-<?= $p['ID'] ?>" style="display:none; margin-top:10px;">
                                <div class="historial-box" id="historial-content-<?= $p['ID'] ?>">
                                    <em style="color:#aaa;font-size:0.82rem;">Cargando...</em>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php endforeach; ?>

        <?php else: ?>
        <div class="section-card">
            <div style="text-align:center; padding:3rem; color:#aaa;">
                <i class="bi bi-bag-x" style="font-size:3rem; color:var(--color-tierra-claro);"></i>
                <p style="margin-top:1rem;">Sin pedidos todavía.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ════════ PRODUCTOS ════════ -->
    <div id="page-productos" class="page-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="margin:0;">Catálogo de Productos</h4>
                <p style="color:#999;font-size:0.85rem;margin:0;"><?= count($productos) ?> productos</p>
            </div>
            <button class="btn-admin" onclick="toggleForm('form-nuevo-producto')">
                <i class="bi bi-plus-lg me-1"></i>Nuevo Producto
            </button>
        </div>

        <div id="form-nuevo-producto" class="section-card mb-4" style="display:none;">
            <div class="section-card-header">
                <h5>Nuevo Producto</h5>
                <button class="btn-icon" onclick="toggleForm('form-nuevo-producto')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="section-card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="crear_producto">
                    <?= formProducto($categorias) ?>
                    <button type="submit" class="btn-admin mt-2">Guardar Producto</button>
                </form>
            </div>
        </div>

        <div class="section-card">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php if (count($productos) > 0):
                        foreach ($productos as $prod): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php if ($prod['url_imagen']): ?>
                                <img src="<?= htmlspecialchars($prod['url_imagen']) ?>" class="product-thumb" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:700;"><?= htmlspecialchars($prod['nombre']) ?></div>
                                    <div style="font-size:0.75rem;color:#999;">ID-<?= $prod['ID'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($prod['categoria_nombre'] ?? '—') ?></td>
                        <td><?= number_format($prod['precio'],2,',','.') ?>€</td>
                        <td>
                            <?php if ($prod['stock'] == 0): ?>
                            <span class="badge-estado badge-cancelado">Sin stock</span>
                            <?php elseif ($prod['stock'] <= 3): ?>
                            <span class="badge-estado badge-pendiente"><?= $prod['stock'] ?> uds</span>
                            <?php else: ?>
                            <span class="badge-estado badge-activo"><?= $prod['stock'] ?> uds</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon" onclick="toggleForm('edit-<?= $prod['ID'] ?>')" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('¿Eliminar este producto?')">
                                <input type="hidden" name="accion" value="eliminar_producto">
                                <input type="hidden" name="id" value="<?= $prod['ID'] ?>">
                                <button type="submit" class="btn-icon" style="color:#dc3545;" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr id="edit-<?= $prod['ID'] ?>" style="display:none;">
                        <td colspan="5" style="padding:0;">
                            <div class="edit-inline-box">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="accion" value="editar_producto">
                                    <input type="hidden" name="id" value="<?= $prod['ID'] ?>">
                                    <?= formProducto($categorias, $prod) ?>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="submit" class="btn-admin">Guardar cambios</button>
                                        <button type="button" class="btn-admin btn-cancelar"
                                                onclick="toggleForm('edit-<?= $prod['ID'] ?>')">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;padding:2rem;">Sin productos. ¡Crea el primero!</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════ CLIENTES ════════ -->
    <div id="page-clientes" class="page-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="margin:0;">Clientes</h4>
                <p style="color:#999;font-size:0.85rem;margin:0;"><?= count($clientes) ?> clientes registrados</p>
            </div>
        </div>
        <div class="section-card">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr><th>Cliente</th><th>Email</th><th>Teléfono</th><th>Pedidos</th><th>Total gastado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php if (count($clientes) > 0):
                        foreach ($clientes as $cl): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="order-avatar">
                                    <?= mb_strtoupper(mb_substr($cl['Nombre']??'?',0,1).mb_substr($cl['Apellido']??'',0,1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;"><?= htmlspecialchars(trim(($cl['Nombre']??'').' '.($cl['Apellido']??''))) ?></div>
                                    <?php if ($cl['direccion']): ?>
                                    <div style="font-size:0.75rem;color:#999;"><?= htmlspecialchars($cl['direccion']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($cl['email'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($cl['telefono'] ?? '—') ?></td>
                        <td><?= $cl['num_pedidos'] ?></td>
                        <td><?= number_format($cl['total_gastado'],2,',','.') ?>€</td>
                        <td>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('¿Eliminar este cliente?')">
                                <input type="hidden" name="accion" value="eliminar_cliente">
                                <input type="hidden" name="id" value="<?= $cl['ID'] ?>">
                                <button type="submit" class="btn-icon" style="color:#dc3545;" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;padding:2rem;">Sin clientes todavía.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════ CATEGORÍAS ════════ -->
    <div id="page-categorias" class="page-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="margin:0;">Categorías</h4>
                <p style="color:#999;font-size:0.85rem;margin:0;"><?= count($categorias) ?> categorías</p>
            </div>
            <button class="btn-admin" onclick="toggleForm('form-nueva-categoria')">
                <i class="bi bi-plus-lg me-1"></i>Nueva Categoría
            </button>
        </div>

        <div id="form-nueva-categoria" class="section-card mb-4" style="display:none;">
            <div class="section-card-header">
                <h5>Nueva Categoría</h5>
                <button class="btn-icon" onclick="toggleForm('form-nueva-categoria')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="section-card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_categoria">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="admin-label">Nombre</label>
                            <input type="text" name="nombre" class="admin-input w-100" required placeholder="Ej: Cerámica">
                        </div>
                        <div class="col-md-7">
                            <label class="admin-label">Descripción</label>
                            <input type="text" name="descripcion" class="admin-input w-100" placeholder="Descripción breve">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-admin">Guardar Categoría</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="section-card">
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Productos</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php if (count($categorias) > 0):
                        foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?= $cat['ID'] ?></td>
                        <td><strong><?= htmlspecialchars($cat['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($cat['descripcion'] ?? '—') ?></td>
                        <td><span class="badge-estado badge-activo"><?= $cat['num_productos'] ?> productos</span></td>
                        <td>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('¿Eliminar esta categoría?')">
                                <input type="hidden" name="accion" value="eliminar_categoria">
                                <input type="hidden" name="id" value="<?= $cat['ID'] ?>">
                                <button type="submit" class="btn-icon" style="color:#dc3545;" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;padding:2rem;">Sin categorías todavía.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ════════ REGISTROS ════════ -->
    <div id="page-registros" class="page-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="margin:0;">Registros de Clientes</h4>
                <p style="color:#999;font-size:0.85rem;margin:0;"><?= $total_clientes ?> clientes registrados en total</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- KPI total -->
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="kpi-label">Total clientes</div>
                        <div class="kpi-value"><?= $total_clientes ?></div>
                    </div>
                </div>
            </div>
            <!-- KPI este mes -->
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="kpi-label">Últimos 5</div>
                        <div class="kpi-value"><?= count($clientes_recientes) ?></div>
                        <div class="kpi-sub">registros recientes</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- TABLA TODOS LOS REGISTROS -->
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-card-header">
                        <h5>Todos los clientes registrados</h5>
                        <span style="font-size:0.8rem;color:#999;"><?= $total_clientes ?> en total</span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="admin-table">
                            <thead>
                                <tr><th>#</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Pedidos</th><th>Gastado</th></tr>
                            </thead>
                            <tbody>
                            <?php if(count($clientes) > 0):
                                foreach($clientes as $cl): ?>
                            <tr>
                                <td style="color:#aaa;font-size:0.8rem;">#<?= $cl['ID'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="order-avatar" style="width:30px;height:30px;font-size:0.72rem;">
                                            <?= mb_strtoupper(mb_substr($cl['Nombre']??'?',0,1).mb_substr($cl['Apellido']??'',0,1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars(trim(($cl['Nombre']??'').' '.($cl['Apellido']??''))) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:0.85rem;"><?= htmlspecialchars($cl['email']??'—') ?></td>
                                <td style="font-size:0.85rem;color:#999;"><?= htmlspecialchars($cl['telefono']??'—') ?></td>
                                <td><span class="badge-estado badge-<?= $cl['num_pedidos']>0?'activo':'pendiente' ?>"><?= $cl['num_pedidos'] ?></span></td>
                                <td style="font-weight:700;"><?= number_format($cl['total_gastado'],2,',','.') ?>€</td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center;color:#aaa;padding:2rem;">Sin clientes todavía.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL LATERAL: registros recientes + gráfico -->
            <div class="col-lg-4">
                <!-- Últimos registrados -->
                <div class="section-card mb-3">
                    <div class="section-card-header">
                        <h5>Últimos registrados</h5>
                    </div>
                    <div class="section-card-body">
                        <?php if(count($clientes_recientes) > 0):
                            foreach($clientes_recientes as $cr): ?>
                        <div class="order-row">
                            <div class="order-avatar">
                                <?= mb_strtoupper(mb_substr($cr['Nombre']??'?',0,1).mb_substr($cr['Apellido']??'',0,1)) ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:700;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars(trim(($cr['Nombre']??'').' '.($cr['Apellido']??''))) ?>
                                </div>
                                <div style="font-size:0.76rem;color:#999;"><?= htmlspecialchars($cr['email']??'') ?></div>
                            </div>
                            <span class="badge-estado badge-enviado" style="white-space:nowrap;">#<?= $cr['ID'] ?></span>
                        </div>
                        <?php endforeach; else: ?>
                        <p style="color:#aaa;text-align:center;padding:1rem 0;margin:0;">Sin registros.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registros por mes -->
                <div class="section-card">
                    <div class="section-card-header">
                        <h5>Registros por mes</h5>
                        <span style="font-size:0.8rem;color:#999;">Distribución</span>
                    </div>
                    <div class="section-card-body">
                        <?php if(count($registros_por_mes) > 0):
                            $max_r = max(array_column($registros_por_mes,'total')) ?: 1; ?>
                        <div class="bar-chart">
                            <?php foreach($registros_por_mes as $rm): ?>
                            <div class="bar"
                                 style="height:<?= round(($rm['total']/$max_r)*100) ?>%"
                                 title="<?= htmlspecialchars($rm['mes']) ?>: <?= $rm['total'] ?> registros">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;gap:6px;margin-top:4px;">
                            <?php foreach($registros_por_mes as $rm): ?>
                            <div class="bar-label" style="flex:1;"><?= htmlspecialchars($rm['mes']) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Tabla resumen -->
                        <table class="tabla-registros-mes">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Registros</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($registros_por_mes as $rm): ?>
                            <tr>
                                <td><?= htmlspecialchars($rm['mes']) ?></td>
                                <td><?= $rm['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p style="color:#aaa;text-align:center;padding:1rem 0;margin:0;">Sin datos todavía.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         SECCIÓN EVENTOS
    ═════════════════════════════════════════════════════════ -->
    <div id="page-eventos" class="page-section">

        <!-- KPIs -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="kpi-label">Hoy</div>
                        <div class="kpi-value"><?= count($eventos_activos) ?></div>
                        <div class="kpi-sub">en activo</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-calendar-plus"></i></div>
                    <div>
                        <div class="kpi-label">Próximos</div>
                        <div class="kpi-value"><?= count($eventos_proximos) ?></div>
                        <div class="kpi-sub">programados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="kpi-card">
                    <div class="kpi-icon"><i class="bi bi-calendar-x"></i></div>
                    <div>
                        <div class="kpi-label">Pasados</div>
                        <div class="kpi-value"><?= count($eventos_pasados) ?></div>
                        <div class="kpi-sub">en archivo</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- COLUMNA IZQUIERDA: formulario + lista -->
            <div class="col-lg-8">

                <!-- ── Formulario nuevo evento ── -->
                <div class="section-card mb-4">
                    <div class="section-card-header">
                        <h5><i class="bi bi-plus-circle me-2" style="color:var(--color-tierra-oscuro);"></i>Publicar nuevo evento</h5>
                    </div>
                    <div class="section-card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="crear_evento">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="admin-label">Título del evento</label>
                                    <input type="text" name="titulo" class="admin-input w-100"
                                           placeholder="Ej: Taller de decoración navideña" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="admin-label">Fecha y hora</label>
                                    <input type="datetime-local" name="fecha_evento" class="admin-input w-100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="admin-label">Imagen del evento</label>
                                    <input type="file" name="imagen" class="admin-input w-100" accept="image/*">
                                </div>
                                <div class="col-12">
                                    <label class="admin-label">Descripción</label>
                                    <textarea name="descripcion" class="admin-input w-100" rows="3"
                                              placeholder="Describe el evento, lugar, detalles..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="admin-label">Ubicación (Google Maps)</label>
                                    <input type="url"
                                        name="ubicacion_mapa"
                                        class="admin-input w-100"
                                        placeholder="https://www.google.com/maps/embed?pb=...">
                                    <small class="text-muted">
                                        Copia la URL del atributo src desde Google Maps → Compartir → Insertar un mapa.
                                    </small>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn-admin">
                                        <i class="bi bi-calendar-plus me-1"></i> Publicar evento
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ── Tabs activos / próximos / pasados ── -->
                <div class="section-card">
                    <div class="section-card-header" style="flex-wrap:wrap;gap:8px;">
                        <h5>Todos los eventos</h5>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button class="btn-admin" id="tab-activos"
                                    onclick="switchTabEventos('activos')"
                                    style="font-size:0.75rem;padding:5px 12px;">
                                Hoy (<?= count($eventos_activos) ?>)
                            </button>
                            <button class="btn-admin btn-cancelar" id="tab-proximos"
                                    onclick="switchTabEventos('proximos')"
                                    style="font-size:0.75rem;padding:5px 12px;">
                                Próximos (<?= count($eventos_proximos) ?>)
                            </button>
                            <button class="btn-admin btn-cancelar" id="tab-pasados"
                                    onclick="switchTabEventos('pasados')"
                                    style="font-size:0.75rem;padding:5px 12px;">
                                Pasados (<?= count($eventos_pasados) ?>)
                            </button>
                        </div>
                    </div>

                    <!-- Tab: Activos (hoy) -->
                    <div id="eventos-activos">
                        <table class="admin-table">
                            <thead><tr><th>Imagen</th><th>Evento</th><th>Fecha</th><th>Acciones</th></tr></thead>
                            <tbody>
                            <?php if(count($eventos_activos) > 0):
                                foreach($eventos_activos as $ev): ?>
                            <tr>
                                <td>
                                    <?php if($ev['url_imagen']): ?>
                                    <img src="<?= htmlspecialchars($ev['url_imagen']) ?>" class="product-thumb" alt="">
                                    <?php else: ?>
                                    <div class="product-thumb d-flex align-items-center justify-content-center"
                                         style="background:var(--color-tierra-claro);color:var(--color-tierra-oscuro);">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($ev['titulo']) ?></div>
                                    <div style="font-size:0.78rem;color:#999;margin-top:2px;"><?= mb_strimwidth(htmlspecialchars($ev['descripcion']??''), 0, 80, '…') ?></div>
                                </td>
                                <td>
                                    <span class="badge-estado badge-activo">
                                        <?= (new DateTime($ev['fecha_evento']))->format('d/m/Y H:i') ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-icon" title="Editar"
                                            onclick="toggleForm('edit-evento-<?= $ev['ID'] ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('¿Eliminar este evento?')">
                                        <input type="hidden" name="accion" value="eliminar_evento">
                                        <input type="hidden" name="id" value="<?= $ev['ID'] ?>">
                                        <button type="submit" class="btn-icon" title="Eliminar">
                                            <i class="bi bi-trash" style="color:#dc3545;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="edit-evento-<?= $ev['ID'] ?>" style="display:none;">
                                <td colspan="4" class="edit-inline-box">
                                    
                                    <!--Formulario de edicion del evento-->
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="accion" value="editar_evento">
                                        <input type="hidden" name="id" value="<?= $ev['ID'] ?>">

                                        <!-- IMPORTANTE: conserva la imagen actual si no se sube una nueva -->
                                        <input type="hidden" name="imagen_actual"
                                            value="<?= htmlspecialchars($ev['url_imagen'] ?? '') ?>">

                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label class="admin-label">Título</label>
                                                <input type="text" name="titulo" class="admin-input w-100"
                                                    value="<?= htmlspecialchars($ev['titulo']) ?>" required>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="admin-label">Fecha y hora</label>
                                                <input type="datetime-local" name="fecha_evento" class="admin-input w-100"
                                                    value="<?= (new DateTime($ev['fecha_evento']))->format('Y-m-d\TH:i') ?>" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="admin-label">Nueva imagen (opcional)</label>
                                                <input type="file" name="imagen" class="admin-input w-100" accept="image/*">
                                            </div>

                                            <div class="col-12">
                                                <label class="admin-label">Descripción</label>
                                                <textarea name="descripcion" class="admin-input w-100" rows="2"><?= htmlspecialchars($ev['descripcion'] ?? '') ?></textarea>
                                            </div>

                                            <div class="col-12">
                                                <label class="admin-label">Ubicación (Google Maps)</label>
                                                <input type="url"
                                                    name="ubicacion_mapa"
                                                    class="admin-input w-100"
                                                    value="<?= htmlspecialchars($ev['ubicacion_mapa'] ?? '') ?>"
                                                    placeholder="https://www.google.com/maps/embed?pb=...">
                                            </div>

                                            <div class="col-12 d-flex gap-2 justify-content-end">
                                                <button type="button"
                                                        class="btn-admin btn-cancelar"
                                                        onclick="toggleForm('edit-evento-<?= $ev['ID'] ?>')">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="btn-admin">
                                                    Guardar cambios
                                                </button>
                                            </div>
                                        </div>
                                </form>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" style="text-align:center;color:#aaa;padding:2rem;">
                                No hay eventos activos hoy.
                            </td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Próximos -->
                    <div id="eventos-proximos" style="display:none;">
                        <table class="admin-table">
                            <thead><tr><th>Imagen</th><th>Evento</th><th>Fecha</th><th>Acciones</th></tr></thead>
                            <tbody>
                            <?php if(count($eventos_proximos) > 0):
                                foreach($eventos_proximos as $ev): ?>
                            <tr>
                                <td>
                                    <?php if($ev['url_imagen']): ?>
                                    <img src="<?= htmlspecialchars($ev['url_imagen']) ?>" class="product-thumb" alt="">
                                    <?php else: ?>
                                    <div class="product-thumb d-flex align-items-center justify-content-center"
                                         style="background:var(--color-tierra-claro);color:var(--color-tierra-oscuro);">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($ev['titulo']) ?></div>
                                    <div style="font-size:0.78rem;color:#999;margin-top:2px;"><?= mb_strimwidth(htmlspecialchars($ev['descripcion']??''), 0, 80, '…') ?></div>
                                </td>
                                <td>
                                    <span class="badge-estado badge-enviado">
                                        <?= (new DateTime($ev['fecha_evento']))->format('d/m/Y H:i') ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-icon" title="Editar"
                                            onclick="toggleForm('edit-evento-<?= $ev['ID'] ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('¿Eliminar este evento?')">
                                        <input type="hidden" name="accion" value="eliminar_evento">
                                        <input type="hidden" name="id" value="<?= $ev['ID'] ?>">
                                        <button type="submit" class="btn-icon" title="Eliminar">
                                            <i class="bi bi-trash" style="color:#dc3545;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="edit-evento-<?= $ev['ID'] ?>" style="display:none;">
                                <td colspan="4" class="edit-inline-box">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="accion" value="editar_evento">
                                        <input type="hidden" name="id" value="<?= $ev['ID'] ?>">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label class="admin-label">Título</label>
                                                <input type="text" name="titulo" class="admin-input w-100"
                                                       value="<?= htmlspecialchars($ev['titulo']) ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="admin-label">Fecha y hora</label>
                                                <input type="datetime-local" name="fecha_evento" class="admin-input w-100"
                                                       value="<?= (new DateTime($ev['fecha_evento']))->format('Y-m-d\TH:i') ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="admin-label">Nueva imagen (opcional)</label>
                                                <input type="file" name="imagen" class="admin-input w-100" accept="image/*">
                                            </div>
                                            <div class="col-12">
                                                <label class="admin-label">Descripción</label>
                                                <textarea name="descripcion" class="admin-input w-100" rows="2"><?= htmlspecialchars($ev['descripcion']??'') ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="admin-label">Ubicación (Google Maps)</label>
                                                <input type="url"
                                                    name="ubicacion_mapa"
                                                    class="admin-input w-100"
                                                    value="<?= htmlspecialchars($ev['ubicacion_mapa'] ?? '') ?>"
                                                    placeholder="https://www.google.com/maps/embed?pb=...">
                                            </div>
                                            <div class="col-12 d-flex gap-2 justify-content-end">
                                                <button type="button" class="btn-admin btn-cancelar"
                                                        onclick="toggleForm('edit-evento-<?= $ev['ID'] ?>')">Cancelar</button>
                                                <button type="submit" class="btn-admin">Guardar cambios</button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" style="text-align:center;color:#aaa;padding:2rem;">
                                No hay eventos próximos programados.
                            </td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Pasados -->
                    <div id="eventos-pasados" style="display:none;">
                        <table class="admin-table">
                            <thead><tr><th>Imagen</th><th>Evento</th><th>Fecha</th><th>Acciones</th></tr></thead>
                            <tbody>
                            <?php if(count($eventos_pasados) > 0):
                                foreach($eventos_pasados as $ev): ?>
                            <tr style="opacity:0.7;">
                                <td>
                                    <?php if($ev['url_imagen']): ?>
                                    <img src="<?= htmlspecialchars($ev['url_imagen']) ?>" class="product-thumb" alt="">
                                    <?php else: ?>
                                    <div class="product-thumb d-flex align-items-center justify-content-center"
                                         style="background:var(--color-tierra-claro);color:var(--color-tierra-oscuro);">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($ev['titulo']) ?></div>
                                    <div style="font-size:0.78rem;color:#999;margin-top:2px;"><?= mb_strimwidth(htmlspecialchars($ev['descripcion']??''), 0, 80, '…') ?></div>
                                </td>
                                <td>
                                    <span class="badge-estado badge-cancelado">
                                        <?= (new DateTime($ev['fecha_evento']))->format('d/m/Y H:i') ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('¿Eliminar este evento del archivo?')">
                                        <input type="hidden" name="accion" value="eliminar_evento">
                                        <input type="hidden" name="id" value="<?= $ev['ID'] ?>">
                                        <button type="submit" class="btn-icon" title="Eliminar del archivo">
                                            <i class="bi bi-trash" style="color:#dc3545;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" style="text-align:center;color:#aaa;padding:2rem;">
                                El archivo de eventos pasados está vacío.
                            </td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div><!-- /section-card tabs -->
            </div><!-- /col-lg-8 -->

            <!-- COLUMNA DERECHA: próximo evento destacado -->
            <div class="col-lg-4">
                <div class="section-card">
                    <div class="section-card-header">
                        <h5>Próximo evento</h5>
                    </div>
                    <div class="section-card-body">
                        <?php
                        $siguiente = count($eventos_proximos) > 0 ? $eventos_proximos[0]
                                   : (count($eventos_activos) > 0 ? array_values($eventos_activos)[0] : null);
                        if($siguiente): ?>
                        <?php if($siguiente['url_imagen']): ?>
                        <img src="<?= htmlspecialchars($siguiente['url_imagen']) ?>"
                             class="evento-preview-img" alt="">
                        <?php else: ?>
                        <div class="evento-preview-placeholder">
                            <i class="bi bi-calendar-heart"></i>
                        </div>
                        <?php endif; ?>
                        <div class="evento-titulo-card">
                            <?= htmlspecialchars($siguiente['titulo']) ?>
                        </div>
                        <div class="evento-fecha-card">
                            <i class="bi bi-clock me-1"></i>
                            <?= (new DateTime($siguiente['fecha_evento']))->format('d/m/Y — H:i') ?>
                        </div>
                        <?php if($siguiente['descripcion']): ?>
                        <p class="evento-desc-card">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($siguiente['descripcion'], 0, 200, '…'))) ?>
                        </p>
                        <?php endif; ?>
                        <?php else: ?>
                        <div style="text-align:center;padding:2rem 0;color:#aaa;">
                            <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                            No hay eventos próximos.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(count($eventos) > 0): ?>
                <!-- Mini resumen total -->
                <div class="section-card mt-3">
                    <div class="section-card-header"><h5>Resumen</h5></div>
                    <div class="section-card-body" style="padding:0.75rem 1.5rem;">
                        <div class="order-row">
                            <div class="kpi-icon-sm">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div style="flex:1;">Hoy</div>
                            <strong><?= count($eventos_activos) ?></strong>
                        </div>
                        <div class="order-row">
                            <div class="kpi-icon-sm">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                            <div style="flex:1;">Próximos</div>
                            <strong><?= count($eventos_proximos) ?></strong>
                        </div>
                        <div class="order-row">
                            <div class="kpi-icon-sm">
                                <i class="bi bi-archive"></i>
                            </div>
                            <div style="flex:1;">Archivo</div>
                            <strong><?= count($eventos_pasados) ?></strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div><!-- /col-lg-4 -->

        </div><!-- /row -->
    </div><!-- /page-eventos -->

    </div><!-- /page-content -->
</main><!-- /main-content -->
</div> <!-- Cierra admin-layout -->

                        <!-- ════════ MODAL CAMBIO DE ESTADO ════════ -->
<div id="modal-estado" class="modal-overlay" onclick="cerrarModal(event)">
    <div class="modal-box">
        <div class="modal-header">
            <h5>Cambiar estado — Pedido <span id="modal-pedido-num"></span></h5>
            <button class="btn-icon" onclick="document.getElementById('modal-estado').style.display='none'">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="cambiar_estado">
            <input type="hidden" name="pedido_id" id="modal-pedido-id-input">
            <div class="modal-body">
                <div class="mb-3">
                    <label class="admin-label">Estado actual</label>
                    <div id="modal-estado-actual" style="font-weight:700;margin-top:4px;"></div>
                </div>
                <div class="mb-3">
                    <label class="admin-label">Nuevo estado</label>
                    <select name="estado_nuevo" class="admin-input w-100" required>
                        <?php foreach ($estados_pedido as $e): ?>
                        <option value="<?= $e ?>"><?= $e ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="admin-label">Comentario (opcional)</label>
                    <textarea name="comentario" class="admin-input w-100" rows="2"
                              placeholder="Ej: Enviado por Correos, nº seguimiento 12345"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-admin btn-cancelar"
                        onclick="document.getElementById('modal-estado').style.display='none'">Cancelar</button>
                <button type="submit" class="btn-admin">Guardar cambio</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const pageTitles = {
    dashboard:'Dashboard', pedidos:'Pedidos', productos:'Productos',
    clientes:'Clientes', categorias:'Categorías', registros:'Registros de Clientes',
    resenas:'Reseñas de Productos', contacto:'Mensajes de Contacto',
    eventos:'Eventos'
};

function showPage(page, linkEl) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const t = document.getElementById('page-' + page);
    if (t) t.classList.add('active');
    document.getElementById('page-title').textContent = pageTitles[page] || page;
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    if (linkEl) linkEl.classList.add('active');
    // Cerrar sidebar en móvil al navegar
    if (window.innerWidth < 992) closeSidebar();
}

// ── SIDEBAR MÓVIL ─────────────────────────────────────────────
function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const isOpen   = sidebar.classList.contains('open');
    sidebar.classList.toggle('open', !isOpen);
    overlay.classList.toggle('active', !isOpen);
    document.body.style.overflow = isOpen ? '' : 'hidden';
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('active');
    document.body.style.overflow = '';
}

// Cerrar sidebar con tecla ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
});

function toggleForm(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? '' : 'none';
}

function abrirModalEstado(pedidoId, estadoActual) {
    document.getElementById('modal-pedido-num').textContent = '#' + pedidoId;
    document.getElementById('modal-pedido-id-input').value = pedidoId;
    document.getElementById('modal-estado-actual').textContent = estadoActual;
    document.getElementById('modal-estado').style.display = 'flex';
}

function cerrarModal(e) {
    if (e.target.id === 'modal-estado')
        document.getElementById('modal-estado').style.display = 'none';
}

function toggleHistorial(pedidoId) {
    const fila    = document.getElementById('historial-' + pedidoId);
    const content = document.getElementById('historial-content-' + pedidoId);
    if (fila.style.display === 'none') {
        fila.style.display = '';
        fetch('get_historial.php?pedido_id=' + pedidoId)
            .then(r => r.text())
            .then(html => { content.innerHTML = html; });
    } else {
        fila.style.display = 'none';
    }
}

function toggleDetallePedido(pedidoId) {
    const detalle = document.getElementById('detalle-pedido-' + pedidoId);
    const chevron = document.getElementById('chevron-' + pedidoId);
    const abierto = detalle.style.display !== 'none';
    detalle.style.display = abierto ? 'none' : '';
    chevron.style.transform = abierto ? '' : 'rotate(180deg)';
}

// Estado de filtros activos
let filtroEstadoActivo = 'todos';

function filtrarPedidos(estado) {
    filtroEstadoActivo = estado;
    buscarPedidos(); // unifica con la búsqueda

    // Actualizar botones activos
    document.querySelectorAll('[id^="filtro-"]').forEach(btn => {
        btn.style.opacity = '0.5';
        btn.classList.add('btn-cancelar');
    });
    const active = document.getElementById('filtro-' + estado);
    if (active) { active.style.opacity = '1'; active.classList.remove('btn-cancelar'); }
}

function buscarPedidos() {
    const termCliente    = (document.getElementById('buscar-cliente')?.value     || '').toLowerCase().trim();
    const termId         = (document.getElementById('buscar-id')?.value          || '').trim();
    const termSeguim     = (document.getElementById('buscar-seguimiento')?.value || '').toLowerCase().trim();
    const termProducto   = (document.getElementById('buscar-producto')?.value    || '').trim();
    const termFecha      = (document.getElementById('buscar-fecha')?.value       || '').trim();

    let visibles = 0;
    let total    = 0;

    document.querySelectorAll('.pedido-card').forEach(card => {
        total++;

        const estado     = card.dataset.estado    || '';
        const cliente    = card.dataset.cliente   || '';
        const idPedido   = card.dataset.id        || '';
        const seguim     = card.dataset.seguimiento || '';
        const fecha      = card.dataset.fecha     || '';
        const productos  = card.dataset.productos || '';

        // Filtro estado
        const okEstado = (filtroEstadoActivo === 'todos' || estado === filtroEstadoActivo);

        // Filtros de búsqueda — solo aplica si tiene valor
        const okCliente   = !termCliente  || cliente.includes(termCliente);
        const okId        = !termId       || idPedido === termId;
        const okSeguim    = !termSeguim   || seguim.includes(termSeguim.replace('ldh-', 'ldh-'));
        const okProducto  = !termProducto || productos.split(' ').includes(termProducto);
        const okFecha     = !termFecha    || fecha === termFecha;

        const mostrar = okEstado && okCliente && okId && okSeguim && okProducto && okFecha;

        card.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });

    // Actualizar contador de resultados
    const res = document.getElementById('buscar-resultado');
    if (res) {
        const hayFiltro = termCliente || termId || termSeguim || termProducto || termFecha;
        if (hayFiltro) {
            res.textContent = visibles === 0
                ? 'Sin resultados para esta búsqueda.'
                : `${visibles} pedido${visibles !== 1 ? 's' : ''} encontrado${visibles !== 1 ? 's' : ''}.`;
            res.style.color = visibles === 0 ? '#dc3545' : 'var(--color-tierra-oscuro)';
        } else {
            res.textContent = '';
        }
    }
}

function limpiarBusqueda() {
    ['buscar-cliente','buscar-id','buscar-seguimiento','buscar-producto','buscar-fecha']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    filtroEstadoActivo = 'todos';
    // Resetear botones
    document.querySelectorAll('[id^="filtro-"]').forEach(btn => {
        btn.style.opacity = '0.5';
        btn.classList.add('btn-cancelar');
    });
    const btnTodos = document.getElementById('filtro-todos');
    if (btnTodos) { btnTodos.style.opacity = '1'; btnTodos.classList.remove('btn-cancelar'); }
    buscarPedidos();
}

const flash = document.querySelector('.flash-msg');
if (flash) setTimeout(() => { flash.style.transition='opacity 0.5s'; flash.style.opacity='0'; }, 3500);

function switchTabEventos(tab) {
    ['activos','proximos','pasados'].forEach(t => {
        document.getElementById('eventos-' + t).style.display = (t === tab) ? '' : 'none';
        const btn = document.getElementById('tab-' + t);
        if (btn) {
            btn.classList.toggle('btn-cancelar', t !== tab);
        }
    });
}
</script>
 
    <?php include 'footer.php'; ?>
</body>
</html>
