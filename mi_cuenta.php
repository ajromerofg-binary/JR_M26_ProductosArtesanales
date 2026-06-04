<?php
require_once("funcionAuth.php");
require_once("BD/conexion.php");

if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'cliente') {
    header("Location: index.php");
    exit();
}

$cliente_id = $_SESSION['user']['id'];
$mensaje      = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ── ACTUALIZAR DATOS PERSONALES ──────────────────────────
    if ($accion === 'actualizar_perfil') {
        $nombre   = trim($_POST['nombre']   ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (empty($nombre) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = 'Por favor revisa los campos nombre y email.';
            $tipo_mensaje = 'error';
        } else {
            $check = $pdo->prepare("SELECT ID FROM CLIENTE WHERE email = ? AND ID != ?");
            $check->execute([$email, $cliente_id]);
            if ($check->fetch()) {
                $mensaje = 'Ese email ya está en uso por otra cuenta.';
                $tipo_mensaje = 'error';
            } else {
                $pdo->prepare("UPDATE CLIENTE SET Nombre=?, Apellido=?, email=?, telefono=? WHERE ID=?")
                    ->execute([$nombre, $apellido, $email, $telefono, $cliente_id]);
                $_SESSION['user']['nombre'] = $nombre;
                $mensaje = 'Datos personales actualizados correctamente.';
                $tipo_mensaje = 'ok';
            }
        }
    }

    // ── AÑADIR DIRECCIÓN ─────────────────────────────────────
    if ($accion === 'añadir_direccion') {
         $alias          = trim($_POST['alias']          ?? '');
        $nombre_dir     = trim($_POST['nombre_dir']     ?? '');
        $apellidos_dir  = trim($_POST['apellidos_dir']  ?? '');
        $calle          = trim($_POST['calle']          ?? '');
        $ciudad         = trim($_POST['ciudad']         ?? '');
        $codigo_postal  = trim($_POST['codigo_postal']  ?? '');
        $pais           = trim($_POST['pais']           ?? 'España');
        $telefono_dir   = trim($_POST['telefono_dir']   ?? '');
        $predeterminada = isset($_POST['predeterminada']) ? 1 : 0;

        if (empty($calle) || empty($ciudad) || empty($codigo_postal)) {
            $mensaje = 'Calle, ciudad y código postal son obligatorios.';
            $tipo_mensaje = 'error';
        } else {
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM DIRECCION WHERE cliente_id=?");
            $cnt->execute([$cliente_id]);
            if ($cnt->fetchColumn() == 0) $predeterminada = 1;

            if ($predeterminada) {
                $pdo->prepare("UPDATE DIRECCION SET predeterminada=0 WHERE cliente_id=?")
                    ->execute([$cliente_id]);
            }
            $pdo->prepare("INSERT INTO DIRECCION (cliente_id, alias, calle, ciudad, codigo_postal, pais, telefono, predeterminada)
                           VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$cliente_id, $alias ?: null, $calle, $ciudad, $codigo_postal, $pais, $telefono_dir ?: null, $predeterminada]);
            $mensaje = 'Dirección añadida correctamente.';
            $tipo_mensaje = 'ok';
        }
    }

    // ── MARCAR COMO PREDETERMINADA ────────────────────────────
    if ($accion === 'marcar_predeterminada') {
        $dir_id = (int)($_POST['dir_id'] ?? 0);

        $chk = $pdo->prepare("SELECT ID FROM DIRECCION WHERE ID=? AND cliente_id=?");
        $chk->execute([$dir_id, $cliente_id]);
        $fila = $chk->fetch();

        if ($fila) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE DIRECCION SET predeterminada=0 WHERE cliente_id=?")->execute([$cliente_id]);
            $pdo->prepare("UPDATE DIRECCION SET predeterminada=1 WHERE ID=? AND cliente_id=?")->execute([$dir_id, $cliente_id]);
            $pdo->commit();

            $_SESSION['toast_direccion'] = 'Dirección predeterminada establecida correctamente';
        }

        header("Location: mi_cuenta.php#direcciones");
        exit();
    }

    // ── ELIMINAR DIRECCIÓN ────────────────────────────────────
     if ($accion === 'eliminar_direccion') {
        $dir_id = (int)($_POST['dir_id'] ?? 0);

        $chk = $pdo->prepare("SELECT ID, predeterminada FROM DIRECCION WHERE ID = ? AND cliente_id = ?");
        $chk->execute([$dir_id, $cliente_id]);
        $dir_row = $chk->fetch();

        if ($dir_row) {
            try {
                $pdo->beginTransaction();

                // Desvincular de pedidos para evitar error de clave foránea
                $pdo->prepare("UPDATE PEDIDO SET direccion_envio_id = NULL WHERE direccion_envio_id = ? AND cliente_id = ?")
                    ->execute([$dir_id, $cliente_id]);

                // Eliminar la dirección
                $pdo->prepare("DELETE FROM DIRECCION WHERE ID = ?")->execute([$dir_id]);

                // Si era la predeterminada, marcar otra como predeterminada (si queda alguna)
                if ($dir_row['predeterminada']) {
                    $primera = $pdo->prepare("SELECT ID FROM DIRECCION WHERE cliente_id = ? ORDER BY ID ASC LIMIT 1");
                    $primera->execute([$cliente_id]);
                    $p = $primera->fetch();
                    if ($p) {
                        $pdo->prepare("UPDATE DIRECCION SET predeterminada = 1 WHERE ID = ?")->execute([$p['ID']]);
                    }
                }

                $pdo->commit();
                $_SESSION['toast_direccion'] = 'Dirección eliminada correctamente';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['toast_direccion'] = 'No se pudo eliminar la dirección';
            }
        }

        header("Location: mi_cuenta.php#direcciones");
        exit();
    }

    // ── CAMBIAR CONTRASEÑA ───────────────────────────────────
    if ($accion === 'cambiar_password') {
        $pass_actual = $_POST['pass_actual'] ?? '';
        $pass_nueva  = $_POST['pass_nueva']  ?? '';
        $pass_repite = $_POST['pass_repite'] ?? '';

        $row = $pdo->prepare("SELECT contraseña FROM CLIENTE WHERE ID=?");
        $row->execute([$cliente_id]);
        $hash_actual = $row->fetchColumn();

        $ok_bcrypt = password_verify($pass_actual, $hash_actual);
        $ok_sha256 = (hash('sha256', $pass_actual) === $hash_actual);

        if (!$ok_bcrypt && !$ok_sha256) {
            $mensaje = 'La contraseña actual no es correcta.';
            $tipo_mensaje = 'error';
        } elseif (strlen($pass_nueva) < 8) {
            $mensaje = 'La nueva contraseña debe tener al menos 8 caracteres.';
            $tipo_mensaje = 'error';
        } elseif ($pass_nueva !== $pass_repite) {
            $mensaje = 'Las contraseñas nuevas no coinciden.';
            $tipo_mensaje = 'error';
        } else {
            $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE CLIENTE SET contraseña=? WHERE ID=?")->execute([$nuevo_hash, $cliente_id]);
            $mensaje = 'Contraseña actualizada correctamente.';
            $tipo_mensaje = 'ok';
        }
    }
}

// ─── CARGAR DATOS ────────────────────────────────────────────
$cliente_stmt = $pdo->prepare("SELECT * FROM CLIENTE WHERE ID=?");
$cliente_stmt->execute([$cliente_id]);
$cliente = $cliente_stmt->fetch();

// Pedidos
$pedidos_stmt = $pdo->prepare("
    SELECT pe.*, COUNT(pit.ID) AS num_productos
    FROM PEDIDO pe
    LEFT JOIN PEDIDO_ITEM pit ON pit.pedido_id = pe.ID
    WHERE pe.cliente_id = ?
    GROUP BY pe.ID
    ORDER BY pe.ID DESC
");
$pedidos_stmt->execute([$cliente_id]);
$pedidos = $pedidos_stmt->fetchAll();

// Items de pedidos
$pedidos_items = [];
if (count($pedidos) > 0) {
    $ids = implode(',', array_map('intval', array_column($pedidos, 'ID')));
    $items_stmt = $pdo->query("
        SELECT pit.pedido_id, pit.cantidad, pit.precio_unitario,
               p.ID AS producto_id, p.nombre AS producto_nombre, pi2.url_imagen
        FROM PEDIDO_ITEM pit
        JOIN PRODUCTO p ON p.ID = pit.producto_id
        LEFT JOIN PRODUCTO_IMAGENES pi2 ON pi2.producto_id = p.ID AND pi2.orden = 1
        WHERE pit.pedido_id IN ($ids)
    ");
    foreach ($items_stmt->fetchAll() as $item) {
        $pedidos_items[$item['pedido_id']][] = $item;
    }
}

// Favoritos: productos comprados con reseña propia >= 4 estrellas, sin tabla extra
$favoritos_stmt = $pdo->prepare("
    SELECT DISTINCT p.ID AS producto_id, p.nombre, p.precio, pi2.url_imagen,
           r.puntuacion, MAX(pe.fecha) AS ultima_compra
    FROM PEDIDO pe
    JOIN PEDIDO_ITEM pit ON pit.pedido_id = pe.ID
    JOIN PRODUCTO p ON p.ID = pit.producto_id
    JOIN RESENA r ON r.producto_id = p.ID AND r.cliente_id = ? AND r.puntuacion >= 4 AND r.visible = 1
    LEFT JOIN PRODUCTO_IMAGENES pi2 ON pi2.producto_id = p.ID AND pi2.orden = 1
    WHERE pe.cliente_id = ?
    GROUP BY p.ID, p.nombre, p.precio, pi2.url_imagen, r.puntuacion
    ORDER BY ultima_compra DESC
    LIMIT 6
");
$favoritos_stmt->execute([$cliente_id, $cliente_id]);
$favoritos = $favoritos_stmt->fetchAll();

// Productos entregados sin reseña
$resenables_stmt = $pdo->prepare("
    SELECT DISTINCT p.ID AS producto_id, p.nombre AS producto_nombre, pi2.url_imagen, pe.ID AS pedido_id
    FROM PEDIDO pe
    JOIN PEDIDO_ITEM pit ON pit.pedido_id = pe.ID
    JOIN PRODUCTO p ON p.ID = pit.producto_id
    LEFT JOIN PRODUCTO_IMAGENES pi2 ON pi2.producto_id = p.ID AND pi2.orden = 1
    LEFT JOIN RESENA r ON r.producto_id = p.ID AND r.cliente_id = :cid
    WHERE pe.cliente_id = :cid2 AND pe.estado = 'Entregado' AND r.ID IS NULL
");
$resenables_stmt->execute([':cid' => $cliente_id, ':cid2' => $cliente_id]);
$productos_resenables = $resenables_stmt->fetchAll();

// Cesta
$cesta = [];
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cesta[] = [
            'item_id'     => $item['id'] ?? 0,
            'producto_id' => $item['id'] ?? 0,
            'nombre'      => $item['nombre'],
            'precio'      => $item['precio'],
            'cantidad'    => $item['cantidad'],
            'url_imagen'  => $item['imagen'] ?? '',
            'subtotal'    => $item['precio'] * $item['cantidad']
        ];
    }
}
$total_cesta = array_sum(array_column($cesta, 'subtotal'));

// Direcciones
$dir_stmt = $pdo->prepare("SELECT * FROM DIRECCION WHERE cliente_id=? ORDER BY predeterminada DESC, ID ASC");
$dir_stmt->execute([$cliente_id]);
$direcciones = $dir_stmt->fetchAll();

function estadoBadge(string $estado): string {
    return match(strtolower(trim($estado))) {
        'confirmado','en preparación','en preparacion' => 'enviado',
        'enviado','entregado' => 'activo',
        'cancelado'           => 'cancelado',
        default               => 'pendiente',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="mi_cuenta.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="cuenta-layout">

    <!-- ════════════════ SIDEBAR ════════════════ -->
    <aside class="cuenta-sidebar">

        <div class="cuenta-perfil-card">
            <div class="cuenta-avatar">
                <?= mb_strtoupper(mb_substr($cliente['Nombre'] ?? '?', 0, 1) . mb_substr($cliente['Apellido'] ?? '', 0, 1)) ?>
            </div>
            <div class="cuenta-perfil-info">
                <div class="cuenta-perfil-nombre">
                    <?= htmlspecialchars(trim(($cliente['Nombre'] ?? '') . ' ' . ($cliente['Apellido'] ?? ''))) ?>
                </div>
                <div class="cuenta-perfil-email"><?= htmlspecialchars($cliente['email'] ?? '') ?></div>
            </div>
        </div>

        <div class="cuenta-resumen">
            <div class="cuenta-resumen-item">
                <span class="cuenta-resumen-valor"><?= count($pedidos) ?></span>
                <span class="cuenta-resumen-label">Pedidos</span>
            </div>
            <div class="cuenta-resumen-sep"></div>
            <div class="cuenta-resumen-item">
                <span class="cuenta-resumen-valor"><?= count($cesta) ?></span>
                <span class="cuenta-resumen-label">En cesta</span>
            </div>
            <div class="cuenta-resumen-sep"></div>
            <div class="cuenta-resumen-item">
                <span class="cuenta-resumen-valor"><?= count($direcciones) ?></span>
                <span class="cuenta-resumen-label">Direcciones</span>
            </div>
            <div class="cuenta-resumen-sep"></div>
            <div class="cuenta-resumen-item">
                <span class="cuenta-resumen-valor"><?= (int)($cliente['tokens'] ?? 0) ?></span>
                <span class="cuenta-resumen-label">Tokens</span>
            </div>
        </div>

        <div class="cuenta-sidebar-label">Mi cuenta</div>
        <ul class="cuenta-nav">
            <li><a href="#" class="active" onclick="showSeccion('resumen',this)">
                <i class="bi bi-grid-1x2"></i> Resumen
            </a></li>
            <li><a href="#" onclick="showSeccion('perfil',this)">
                <i class="bi bi-person"></i> Datos personales
            </a></li>
            <li><a href="#" onclick="showSeccion('direcciones',this)">
                <i class="bi bi-geo-alt"></i> Mis direcciones
                <?php if (count($direcciones) > 0): ?>
                <span class="cuenta-nav-badge"><?= count($direcciones) ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="#" onclick="showSeccion('pedidos',this)">
                <i class="bi bi-bag-check"></i> Mis pedidos
                <?php if (count($pedidos) > 0): ?>
                <span class="cuenta-nav-badge"><?= count($pedidos) ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="#" onclick="showSeccion('cesta',this)">
                <i class="bi bi-cart3"></i> Mi cesta
                <?php if (count($cesta) > 0): ?>
                <span class="cuenta-nav-badge"><?= count($cesta) ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="#" onclick="showSeccion('pago',this)">
                <i class="bi bi-credit-card"></i> Formas de pago
            </a></li>
            <li><a href="#" onclick="showSeccion('tokens',this)">
                <i class="bi bi-coin"></i> Mis tokens
                <?php if (($cliente['tokens'] ?? 0) > 0): ?>
                <span class="cuenta-nav-badge"><?= (int)$cliente['tokens'] ?></span>
                <?php endif; ?>
            </a></li>
        </ul>



    </aside>

    <!-- ════════════════ MAIN ════════════════ -->
    <main class="cuenta-main">

        <?php if ($mensaje): ?>
        <div class="cuenta-flash cuenta-flash-<?= $tipo_mensaje ?>">
            <i class="bi bi-<?= $tipo_mensaje === 'ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════ -->
        <!-- RESUMEN                            -->
        <!-- ══════════════════════════════════ -->
        <div id="sec-resumen" class="cuenta-seccion active">
            <?php
    $nombre_raw = $cliente['Nombre'] ?? '';
    $ultima_letra = mb_strtolower(mb_substr(trim($nombre_raw), -1));
    $saludo = in_array($ultima_letra, ['a','e']) ? 'Bienvenida' : 'Bienvenido';
?>
<h2 class="cuenta-titulo"><?= $saludo ?>, <?= htmlspecialchars($nombre_raw) ?> 👋</h2>

            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="cuenta-kpi">
                        <div class="cuenta-kpi-icon"><i class="bi bi-bag-check-fill"></i></div>
                        <div class="cuenta-kpi-valor"><?= count($pedidos) ?></div>
                        <div class="cuenta-kpi-label">Pedidos</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="cuenta-kpi">
                        <div class="cuenta-kpi-icon"><i class="bi bi-cart3"></i></div>
                        <div class="cuenta-kpi-valor"><?= count($cesta) ?></div>
                        <div class="cuenta-kpi-label">En cesta</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="cuenta-kpi">
                        <div class="cuenta-kpi-icon"><i class="bi bi-heart-fill"></i></div>
                        <div class="cuenta-kpi-valor"><?= count($favoritos) ?></div>
                        <div class="cuenta-kpi-label">Favoritos</div>
                    </div>
                </div>
            </div>

            <!-- Último pedido -->
            <?php if (count($pedidos) > 0): $ultimo = $pedidos[0]; ?>
            <div class="cuenta-card mb-3">
                <div class="cuenta-card-header">
                    <h5>Último pedido</h5>
                    <a href="#" onclick="showSeccion('pedidos',null)" class="cuenta-link">Ver todos</a>
                </div>
                <div class="cuenta-card-body">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div>
                            <div style="font-weight:700;">Pedido #<?= $ultimo['ID'] ?></div>
                            <div style="color:#999;font-size:0.85rem;">
                                <?= $ultimo['fecha'] ? date('d/m/Y', strtotime($ultimo['fecha'])) : 'Sin fecha' ?>
                                · <?= $ultimo['num_productos'] ?> producto(s)
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;">
                                <?= number_format($ultimo['total'], 2, ',', '.') ?>€
                            </div>
                            <?php if (($ultimo['tokens_usados'] ?? 0) > 0): ?>
                            <div style="font-size:0.75rem; color:#2a6840; font-weight:700;">
                                <i class="bi bi-coin"></i> -<?= $ultimo['tokens_usados'] ?> tokens usados
                            </div>
                            <?php endif; ?>
                            <?php if (($ultimo['tokens_ganados'] ?? 0) > 0): ?>
                            <div style="font-size:0.75rem; color:var(--color-tierra-oscuro); font-weight:700;">
                                <i class="bi bi-gift"></i> +<?= $ultimo['tokens_ganados'] ?> tokens ganados
                            </div>
                            <?php endif; ?>
                            <span class="cuenta-badge cuenta-badge-<?= estadoBadge($ultimo['estado']) ?>">
                                <?= htmlspecialchars($ultimo['estado']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Productos favoritos (comprados + reseña >= 4 estrellas) -->
            <?php if (count($favoritos) > 0): ?>
            <div class="cuenta-card mb-3">
                <div class="cuenta-card-header">
                    <h5><i class="bi bi-heart-fill me-2" style="color:var(--color-tierra-oscuro);font-size:0.9rem;"></i>Mis favoritos</h5>
                    <a href="#" onclick="showSeccion('pedidos',null)" class="cuenta-link">Ver pedidos</a>
                </div>
                <div class="cuenta-card-body">
                    <div class="cuenta-fav-resumen-grid">
                        <?php foreach ($favoritos as $fav): ?>
                        <a href="detalle.php?id=<?= $fav['producto_id'] ?>" class="cuenta-fav-resumen-item">
                            <div class="cuenta-fav-resumen-img">
                                <?php if ($fav['url_imagen']): ?>
                                <img src="img/<?= htmlspecialchars($fav['url_imagen']) ?>"
                                     alt="<?= htmlspecialchars($fav['nombre']) ?>"
                                     onerror="this.parentElement.innerHTML='<i class=\'bi bi-image\'></i>'">
                                <?php else: ?>
                                <i class="bi bi-image"></i>
                                <?php endif; ?>
                                <span class="cuenta-fav-resumen-stars">
                                    <?php for($s=1;$s<=5;$s++): ?>
                                    <i class="bi bi-star<?= $s <= $fav['puntuacion'] ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <div class="cuenta-fav-resumen-nombre"><?= htmlspecialchars($fav['nombre']) ?></div>
                            <div class="cuenta-fav-resumen-precio"><?= number_format($fav['precio'], 2, ',', '.') ?>€</div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php elseif (count($pedidos) > 0): ?>
            <div class="cuenta-card mb-3">
                <div class="cuenta-card-header">
                    <h5><i class="bi bi-heart me-2" style="color:var(--color-tierra-oscuro);font-size:0.9rem;"></i>Mis favoritos</h5>
                </div>
                <div class="cuenta-card-body" style="text-align:center;padding:1.5rem;color:#aaa;">
                    <i class="bi bi-star" style="font-size:2rem;color:var(--color-tierra-claro);"></i>
                    <p style="margin-top:0.75rem;font-size:0.88rem;">
                        Tus productos favoritos aparecerán aquí cuando dejes una reseña de 4 o 5 estrellas en alguno de tus pedidos.
                    </p>
                    <a href="#" onclick="showSeccion('pedidos',null)" class="cuenta-link">Ver mis pedidos para valorar</a>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ══════════════════════════════════ -->
        <!-- DATOS PERSONALES                   -->
        <!-- ══════════════════════════════════ -->
        <div id="sec-perfil" class="cuenta-seccion">
            <h2 class="cuenta-titulo">Datos personales</h2>

            <div class="cuenta-card mb-4">
                <div class="cuenta-card-header"><h5>Información de la cuenta</h5></div>
                <div class="cuenta-card-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="actualizar_perfil">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="cuenta-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control cuenta-input"
                                       value="<?= htmlspecialchars($cliente['Nombre'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="cuenta-label">Apellido</label>
                                <input type="text" name="apellido" class="form-control cuenta-input"
                                       value="<?= htmlspecialchars($cliente['Apellido'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="cuenta-label">Email</label>
                                <input type="email" name="email" class="form-control cuenta-input"
                                       value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="cuenta-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control cuenta-input"
                                       value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>"
                                       placeholder="612 345 678">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-tierra">Guardar cambios</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cuenta-card">
                <div class="cuenta-card-header"><h5>Cambiar contraseña</h5></div>
                <div class="cuenta-card-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="cambiar_password">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="cuenta-label">Contraseña actual</label>
                                <input type="password" name="pass_actual" class="form-control cuenta-input"
                                       placeholder="••••••••" required>
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">Nueva contraseña</label>
                                <input type="password" name="pass_nueva" class="form-control cuenta-input"
                                       placeholder="Mínimo 8 caracteres" required>
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">Repetir contraseña</label>
                                <input type="password" name="pass_repite" class="form-control cuenta-input"
                                       placeholder="••••••••" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-tierra">Actualizar contraseña</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════ -->
        <!-- DIRECCIONES                        -->
        <!-- ══════════════════════════════════ -->
        <div id="sec-direcciones" class="cuenta-seccion">
            <h2 class="cuenta-titulo">Mis direcciones de envío</h2>

            <?php if (isset($_SESSION['toast_direccion'])): ?>
            <div id="toast-direccion" style="position:fixed;top:20px;right:20px;z-index:9999;background:#d4edda;color:#2a6840;border:1px solid #b8dfc5;border-radius:6px;padding:14px 20px;font-size:0.9rem;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;opacity:0;transform:translateY(-20px);transition:all 0.4s ease;">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['toast_direccion']) ?>
            </div>
            <?php unset($_SESSION['toast_direccion']); ?>
            <script>
                (function(){
                    var t = document.getElementById('toast-direccion');
                    if(t){ setTimeout(function(){ t.style.opacity='1'; t.style.transform='translateY(0)'; }, 100); setTimeout(function(){ t.style.opacity='0'; t.style.transform='translateY(-20px)'; }, 3500); }
                })();
            </script>
            <?php endif; ?>

            <?php if (count($direcciones) > 0): ?>
            <div class="row g-3 mb-4">
                <?php foreach ($direcciones as $dir): ?>
                <div class="col-md-6">
                    <div class="cuenta-dir-card<?= $dir['predeterminada'] ? ' predeterminada' : '' ?>">
                        <div class="cuenta-dir-card-top">
                            <div class="cuenta-dir-alias">
                                <i class="bi bi-<?= $dir['predeterminada'] ? 'house-fill' : 'geo-alt' ?>"></i>
                                <?= htmlspecialchars($dir['alias'] ?: 'Sin alias') ?>
                                <?php if ($dir['predeterminada']): ?>
                                <span class="cuenta-badge cuenta-badge-activo ms-1">Predeterminada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="cuenta-dir-card-body">
                            <?php if (!empty($dir['nombre_destinatario']) || !empty($dir['apellidos_destinatario'])): ?>
                            <div class="cuenta-dir-linea" style="font-weight:700; color:var(--color-tierra-oscuro);">
                                <?= htmlspecialchars(trim(($dir['nombre_destinatario'] ?? '') . ' ' . ($dir['apellidos_destinatario'] ?? ''))) ?>
                            </div>
                            <?php endif; ?>
                            <div class="cuenta-dir-linea"><?= htmlspecialchars($dir['calle']) ?></div>
                            <div class="cuenta-dir-linea">
                                <?= htmlspecialchars($dir['codigo_postal']) ?> <?= htmlspecialchars($dir['ciudad']) ?>
                            </div>
                            <div class="cuenta-dir-linea" style="color:#999;"><?= htmlspecialchars($dir['pais'] ?? 'España') ?></div>
                            <?php if ($dir['telefono']): ?>
                            <div class="cuenta-dir-linea" style="color:#888;font-size:0.85rem;">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($dir['telefono']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="cuenta-dir-card-footer">
                            <?php if (!$dir['predeterminada']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="marcar_predeterminada">
                                <input type="hidden" name="dir_id" value="<?= $dir['ID'] ?>">
                                <button type="submit" class="btn-dir-accion">
                                    <i class="bi bi-star"></i> Usar como predeterminada
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('¿Eliminar esta dirección?');">
                                <input type="hidden" name="accion" value="eliminar_direccion">
                                <input type="hidden" name="dir_id" value="<?= $dir['ID'] ?>">
                                <button type="submit" class="btn-dir-accion btn-dir-eliminar">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="cuenta-card mb-4">
                <div class="cuenta-card-body" style="text-align:center;padding:2rem;color:#aaa;">
                    <i class="bi bi-geo-alt" style="font-size:2.5rem;color:var(--color-tierra-claro);"></i>
                    <p style="margin-top:0.75rem;">Aún no tienes ninguna dirección guardada.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Formulario añadir dirección -->
            <div class="cuenta-card">
                <div class="cuenta-card-header">
                    <h5><i class="bi bi-plus-circle me-2" style="color:var(--color-tierra-oscuro);"></i>Añadir nueva dirección</h5>
                </div>
                <div class="cuenta-card-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="añadir_direccion">
                        <div class="row g-3">
                           <div class="col-md-4">
                                <label class="cuenta-label">Alias</label>
                                <input type="text" name="alias" class="form-control cuenta-input" placeholder="Casa, Trabajo…">
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">Nombre</label>
                                <input type="text" name="nombre_dir" class="form-control cuenta-input" placeholder="Nombre del destinatario">
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">Apellidos</label>
                                <input type="text" name="apellidos_dir" class="form-control cuenta-input" placeholder="Apellidos del destinatario">
                            </div>
                            <div class="col-md-8">
                                <label class="cuenta-label">Calle y número <span style="color:#c00;">*</span></label>
                                <input type="text" name="calle" class="form-control cuenta-input"
                                       placeholder="Calle Mayor 12, 3º B" required>
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">Código postal <span style="color:#c00;">*</span></label>
                                <input type="text" name="codigo_postal" class="form-control cuenta-input"
                                       placeholder="50001" maxlength="10" required>
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">Ciudad <span style="color:#c00;">*</span></label>
                                <input type="text" name="ciudad" class="form-control cuenta-input"
                                       placeholder="Zaragoza" required>
                            </div>
                            <div class="col-md-4">
                                <label class="cuenta-label">País</label>
                                <input type="text" name="pais" class="form-control cuenta-input" value="España">
                            </div>
                            <div class="col-md-6">
                                <label class="cuenta-label">Teléfono</label>
                                <input type="text" name="telefono_dir" class="form-control cuenta-input" placeholder="612 345 678">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="cuenta-check-wrap">
                                    <input type="checkbox" name="predeterminada" id="chk-pred" value="1"
                                           <?= count($direcciones) === 0 ? 'checked disabled' : '' ?>>
                                    <label for="chk-pred">Establecer como predeterminada</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-tierra">
                                    <i class="bi bi-plus-lg me-1"></i> Guardar dirección
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════ -->
        <!-- PEDIDOS                            -->
        <!-- ══════════════════════════════════ -->
        <div id="sec-pedidos" class="cuenta-seccion">
            <h2 class="cuenta-titulo">Mis pedidos</h2>

            <?php if (count($pedidos) > 0): ?>
                <?php foreach ($pedidos as $p):
                    $seguimiento = 'LDH-' . str_pad($p['ID'], 6, '0', STR_PAD_LEFT);
                    $pasos       = ['Pendiente','Confirmado','En preparación','Enviado','Entregado'];
                    $idx_actual  = array_search($p['estado'], $pasos);
                    if ($idx_actual === false) $idx_actual = 0;
                ?>
                <div class="cuenta-card mb-3">
                    <div class="cuenta-card-header">
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <strong>Pedido #<?= $p['ID'] ?></strong>
                            <span style="color:#999;font-size:0.85rem;">
                                <?= $p['fecha'] ? date('d/m/Y', strtotime($p['fecha'])) : '' ?>
                            </span>
                            <span class="cuenta-badge cuenta-badge-<?= estadoBadge($p['estado']) ?>">
                                <?= htmlspecialchars($p['estado']) ?>
                            </span>
                            <?php if (in_array($p['estado'], ['Enviado','Entregado'])): ?>
                            <span class="cuenta-tracking"><?= $seguimiento ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:right;">
                            <strong style="font-family:'Playfair Display',serif;">
                                <?= number_format($p['total'], 2, ',', '.') ?>€
                            </strong>
                            <?php if (($p['tokens_usados'] ?? 0) > 0): ?>
                            <div style="font-size:0.75rem; color:#2a6840; font-weight:700;">
                                <i class="bi bi-coin"></i> -<?= $p['tokens_usados'] ?> tokens
                            </div>
                            <?php endif; ?>
                            <?php if (($p['tokens_ganados'] ?? 0) > 0): ?>
                            <div style="font-size:0.75rem; color:var(--color-tierra-oscuro); font-weight:700;">
                                <i class="bi bi-gift"></i> +<?= $p['tokens_ganados'] ?> tokens
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="cuenta-card-body">
                        <?php if ($p['estado'] !== 'Cancelado'): ?>
                        <div class="cuenta-timeline">
                            <?php foreach ($pasos as $i => $paso):
                                $done = ($i <= $idx_actual); $current = ($i === $idx_actual); ?>
                            <div class="cuenta-timeline-paso<?= $done?' completado':'' ?><?= $current?' actual':'' ?>">
                                <div class="cuenta-timeline-circulo">
                                    <i class="bi bi-<?= $done?'check-lg':'circle' ?>"></i>
                                </div>
                                <div class="cuenta-timeline-label"><?= $paso ?></div>
                            </div>
                            <?php if ($i < count($pasos)-1): ?>
                            <div class="cuenta-timeline-linea<?= ($i<$idx_actual)?' completada':'' ?>"></div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div style="text-align:center;color:#dc3545;padding:0.5rem;font-size:0.9rem;">
                            <i class="bi bi-x-circle"></i> Pedido cancelado.
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pedidos_items[$p['ID']])): ?>
                        <div style="margin-top:1rem;">
                            <?php foreach ($pedidos_items[$p['ID']] as $pi): ?>
                            <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid rgba(212,184,150,0.2);">
                                <?php if ($pi['url_imagen']): ?>
                                <img src="img/<?= htmlspecialchars($pi['url_imagen']) ?>"
                                     style="width:36px;height:36px;border-radius:4px;object-fit:cover;border:1px solid var(--color-borde);"
                                     onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div style="flex:1;font-size:0.88rem;"><?= htmlspecialchars($pi['producto_nombre']) ?></div>
                                <div style="font-size:0.85rem;color:#777;white-space:nowrap;">
                                    <?= $pi['cantidad'] ?> × <?= number_format($pi['precio_unitario'], 2, ',', '.') ?>€
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($p['estado'] === 'Entregado' && !empty($pedidos_items[$p['ID']])): ?>
                        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--color-borde);">
                            <?php foreach ($pedidos_items[$p['ID']] as $pi_r):
                                $ya = $pdo->prepare("SELECT ID FROM RESENA WHERE cliente_id=? AND producto_id=?");
                                $ya->execute([$cliente_id, $pi_r['producto_id']]);
                                if (!$ya->fetch()): ?>
                            <a href="detalle.php?id=<?= $pi_r['producto_id'] ?>#resenas"
                               class="btn btn-tierra btn-sm me-2 mb-1">
                                <i class="bi bi-star me-1"></i> Valorar "<?= htmlspecialchars($pi_r['producto_nombre']) ?>"
                            </a>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Pendientes de reseña -->
                <?php if (count($productos_resenables) > 0): ?>
                <div class="cuenta-card mt-3">
                    <div class="cuenta-card-header">
                        <h5>⭐ Pendientes de reseña</h5>
                        <span style="font-size:0.8rem;color:#999;"><?= count($productos_resenables) ?> producto(s)</span>
                    </div>
                    <div class="cuenta-card-body">
                        <div class="row g-3">
                        <?php foreach ($productos_resenables as $pr): ?>
                        <div class="col-md-6">
                            <div style="display:flex;align-items:center;gap:12px;background:var(--color-crema);border:1px solid var(--color-borde);border-radius:4px;padding:12px;">
                                <?php if ($pr['url_imagen']): ?>
                                <img src="img/<?= htmlspecialchars($pr['url_imagen']) ?>"
                                     style="width:50px;height:50px;border-radius:4px;object-fit:cover;"
                                     onerror="this.style.display='none'">
                                <?php endif; ?>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= htmlspecialchars($pr['producto_nombre']) ?>
                                    </div>
                                </div>
                                <a href="detalle.php?id=<?= $pr['producto_id'] ?>#resenas" class="btn btn-tierra btn-sm">
                                    <i class="bi bi-star"></i> Valorar
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
            <div class="cuenta-card">
                <div class="cuenta-card-body" style="text-align:center;padding:3rem;">
                    <i class="bi bi-bag-x" style="font-size:3rem;color:var(--color-tierra-claro);"></i>
                    <p style="margin-top:1rem;color:#aaa;">Todavía no has realizado ningún pedido.</p>
                    <a href="tienda.php" class="btn btn-tierra mt-2">Ir a la tienda</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════════════════════════ -->
        <!-- CESTA                              -->
        <!-- ══════════════════════════════════ -->
        <div id="sec-cesta" class="cuenta-seccion">
            <h2 class="cuenta-titulo">Mi cesta</h2>
            <?php if (count($cesta) > 0): ?>
            <div class="cuenta-card">
                <div style="overflow-x:auto;">
                    <table class="cuenta-tabla">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio ud.</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cesta as $item): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php if ($item['url_imagen']): ?>
                                    <img src="img/<?= htmlspecialchars($item['url_imagen']) ?>"
                                         style="width:42px;height:42px;border-radius:4px;object-fit:cover;border:1px solid var(--color-borde);"
                                         onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <span style="font-weight:700;"><?= htmlspecialchars($item['nombre']) ?></span>
                                </div>
                            </td>
                            <td><?= number_format($item['precio'], 2, ',', '.') ?>€</td>
                            <td><?= $item['cantidad'] ?></td>
                            <td><strong><?= number_format($item['subtotal'], 2, ',', '.') ?>€</strong></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cuenta-cesta-total">
                    <span>Total cesta:</span>
                    <strong style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--color-tierra-oscuro);">
                        <?= number_format($total_cesta, 2, ',', '.') ?>€
                    </strong>
                </div>
            </div>
            <div style="margin-top:1rem;text-align:right;">
                <a href="tienda.php" class="btn btn-tierra">Seguir comprando</a>
            </div>
            <?php else: ?>
            <div class="cuenta-card">
                <div class="cuenta-card-body" style="text-align:center;padding:3rem;">
                    <i class="bi bi-cart-x" style="font-size:3rem;color:var(--color-tierra-claro);"></i>
                    <p style="margin-top:1rem;color:#aaa;">Tu cesta está vacía.</p>
                    <a href="tienda.php" class="btn btn-tierra mt-2">Ir a la tienda</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════════════════════════ -->
        <!-- FORMAS DE PAGO                     -->
        <!-- ══════════════════════════════════ -->
        <div id="sec-pago" class="cuenta-seccion">
            <h2 class="cuenta-titulo">Formas de pago</h2>
            <p style="color:#777;margin-bottom:1.5rem;">Métodos de pago aceptados en Luz de Hogar.</p>

            <div class="row g-3">
                <!-- Tarjeta -->
                <div class="col-12">
                    <div class="cuenta-pago-card cuenta-pago-card-tarjeta">
                        <div class="cuenta-pago-icon"><i class="bi bi-credit-card-2-front"></i></div>
                        <div class="flex-grow-1">
                            <div class="cuenta-pago-titulo">Tarjeta de crédito / débito</div>
                            <div class="cuenta-pago-desc">Introduce los datos de tu tarjeta al finalizar la compra.</div>
                            <div class="cuenta-pago-campos">
                                <div class="cuenta-pago-campo-full">
                                    <div class="cuenta-pago-campo-mock">Número de tarjeta</div>
                                </div>
                                <div class="cuenta-pago-campo-half">
                                    <div class="cuenta-pago-campo-mock">MM/AA</div>
                                </div>
                                <div class="cuenta-pago-campo-half">
                                    <div class="cuenta-pago-campo-mock">CVV</div>
                                </div>
                            </div>
                        </div>
                        <span class="cuenta-badge cuenta-badge-activo">Disponible</span>
                    </div>
                </div>
                <!-- PayPal -->
                <div class="col-md-6">
                    <div class="cuenta-pago-card">
                        <div class="cuenta-pago-icon cuenta-pago-icon-paypal">
                            <i class="bi bi-paypal"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="cuenta-pago-titulo">PayPal</div>
                            <div class="cuenta-pago-desc">Paga con tu cuenta PayPal de forma rápida y segura.</div>
                        </div>
                        <span class="cuenta-badge cuenta-badge-activo">Disponible</span>
                    </div>
                </div>
                <!-- Google Pay -->
                <div class="col-md-6">
                    <div class="cuenta-pago-card">
                        <div class="cuenta-pago-icon cuenta-pago-icon-gpay">
                            <i class="bi bi-google"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="cuenta-pago-titulo">Google Pay</div>
                            <div class="cuenta-pago-desc">Paga en segundos con tu cuenta de Google.</div>
                        </div>
                        <span class="cuenta-badge cuenta-badge-activo">Disponible</span>
                    </div>
                </div>
            </div>

            <div class="cuenta-card mt-4">
                <div class="cuenta-card-body" style="display:flex;align-items:center;gap:12px;">
                    <i class="bi bi-shield-lock-fill" style="font-size:1.5rem;color:var(--color-tierra-oscuro);"></i>
                    <div>
                        <div style="font-weight:700;">Pago 100% seguro</div>
                        <div style="font-size:0.85rem;color:#777;">
                            Todos los pagos están protegidos con cifrado SSL de 256 bits. No almacenamos datos de tarjetas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- TOKENS -->
        <div id="sec-tokens" class="cuenta-seccion">
            <h2 class="cuenta-titulo">Mis tokens de fidelidad</h2>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="cuenta-kpi" style="border-left:4px solid #f4c430;">
                        <div class="cuenta-kpi-icon"><i class="bi bi-coin" style="color:#f4c430;"></i></div>
                        <div class="cuenta-kpi-valor"><?= (int)($cliente['tokens'] ?? 0) ?></div>
                        <div class="cuenta-kpi-label">Tokens disponibles</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="cuenta-kpi" style="border-left:4px solid var(--color-tierra-oscuro);">
                        <div class="cuenta-kpi-icon"><i class="bi bi-gift"></i></div>
                        <div class="cuenta-kpi-valor">1 €</div>
                        <div class="cuenta-kpi-label">Valor por token</div>
                    </div>
                </div>
            </div>

            <div class="cuenta-card mb-4">
                <div class="cuenta-card-header">
                    <h5><i class="bi bi-info-circle me-2" style="color:var(--color-tierra-oscuro);"></i>¿Cómo funciona?</h5>
                </div>
                <div class="cuenta-card-body" style="font-size:0.9rem; color:#555; line-height:1.6;">
                    <p><strong>1. Acumula tokens:</strong> por cada <strong>10 €</strong> que gastes en una compra, recibirás <strong>1 token</strong> automáticamente.</p>
                    <p><strong>2. Usa tus tokens:</strong> en el carrito podrás decidir cuántos tokens aplicar como descuento. Cada token resta <strong>1 €</strong> del total.</p>
                    <p><strong>3. Sin límite:</strong> puedes acumular tantos tokens como quieras y usarlos cuando prefieras.</p>
                </div>
            </div>

            <?php 
            $tok_stmt = $pdo->prepare("
                SELECT ID, fecha, total, tokens_usados, tokens_ganados, estado 
                FROM PEDIDO 
                WHERE cliente_id = ? AND (tokens_usados > 0 OR tokens_ganados > 0)
                ORDER BY ID DESC
            ");
            $tok_stmt->execute([$cliente_id]);
            $tok_historial = $tok_stmt->fetchAll();
            ?>

            <?php if (count($tok_historial) > 0): ?>
            <div class="cuenta-card">
                <div class="cuenta-card-header">
                    <h5>Historial de tokens</h5>
                </div>
                <div class="cuenta-card-body" style="padding:0;">
                    <table class="cuenta-tabla">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Fecha</th>
                                <th>Usados</th>
                                <th>Ganados</th>
                                <th>Saldo movimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tok_historial as $th): 
                            $saldo = $th['tokens_ganados'] - $th['tokens_usados'];
                            $color = $saldo >= 0 ? '#2a6840' : '#842029';
                            $icono = $saldo >= 0 ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle';
                        ?>
                            <tr>
                                <td><a href="pedido_resumen.php?id=<?= $th['ID'] ?>" class="cuenta-link">#<?= $th['ID'] ?></a></td>
                                <td><?= $th['fecha'] ? date('d/m/Y', strtotime($th['fecha'])) : '-' ?></td>
                                <td style="color:#842029; font-weight:700;"><?= $th['tokens_usados'] > 0 ? '-'.$th['tokens_usados'] : '-' ?></td>
                                <td style="color:#2a6840; font-weight:700;"><?= $th['tokens_ganados'] > 0 ? '+'.$th['tokens_ganados'] : '-' ?></td>
                                <td style="color:<?= $color ?>; font-weight:700;">
                                    <i class="bi <?= $icono ?>"></i> <?= ($saldo > 0 ? '+' : '').$saldo ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="cuenta-card">
                <div class="cuenta-card-body" style="text-align:center; padding:2rem; color:#aaa;">
                    <i class="bi bi-coin" style="font-size:2.5rem; color:var(--color-tierra-claro);"></i>
                    <p style="margin-top:0.75rem;">Aún no tienes movimientos de tokens.<br>¡Haz tu primera compra para empezar a acumular!</p>
                    <a href="tienda.php" class="btn btn-tierra mt-2">Ir a la tienda</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>

<script>
function showSeccion(id, linkEl) {
    document.querySelectorAll('.cuenta-seccion').forEach(s => s.classList.remove('active'));
    const t = document.getElementById('sec-' + id);
    if (t) t.classList.add('active');
    document.querySelectorAll('.cuenta-nav a').forEach(a => a.classList.remove('active'));
    if (linkEl) linkEl.classList.add('active');
}

const flash = document.querySelector('.cuenta-flash');
if (flash) setTimeout(() => { flash.style.transition='opacity 0.5s'; flash.style.opacity='0'; }, 3500);

// Activar sección según hash de la URL
(function(){
    const hash = window.location.hash.replace('#','');
    if (!hash) return;
    
    const links = document.querySelectorAll('.cuenta-nav a');
    let linkActivo = null;
    links.forEach(a => {
        const onclick = a.getAttribute('onclick');
        if (onclick && onclick.includes("'"+hash+"'")) {
            linkActivo = a;
        }
    });
    
    document.querySelectorAll('.cuenta-seccion').forEach(s => s.classList.remove('active'));
    const seccion = document.getElementById('sec-' + hash);
    if (seccion) {
        seccion.classList.add('active');
        links.forEach(a => a.classList.remove('active'));
        if (linkActivo) linkActivo.classList.add('active');
    }
})();
</script>
</body>
</html>
