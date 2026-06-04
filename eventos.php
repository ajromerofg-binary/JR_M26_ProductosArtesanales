<?php
ob_start();
session_start();
require_once('BD/conexion.php');

// ─── DATOS ───────────────────────────────────────────────────
$eventos = [];
try {
   
    $eventos = $pdo->query("SELECT * FROM EVENTO ORDER BY fecha_evento ASC")->fetchAll();
} catch (PDOException $e) {
    $eventos = [];
}

$ahora = new DateTime();

$activos  = array_values(array_filter($eventos, fn($e) =>
                (new DateTime($e['fecha_evento']))->format('Y-m-d') === $ahora->format('Y-m-d')));
$proximos = array_values(array_filter($eventos, fn($e) =>
                new DateTime($e['fecha_evento']) > $ahora &&
                (new DateTime($e['fecha_evento']))->format('Y-m-d') !== $ahora->format('Y-m-d')));
$pasados  = array_reverse(array_values(array_filter($eventos, fn($e) =>
                new DateTime($e['fecha_evento']) < $ahora &&
                (new DateTime($e['fecha_evento']))->format('Y-m-d') !== $ahora->format('Y-m-d'))));

$filtro_get = $_GET['filtro'] ?? 'todos';
if (!in_array($filtro_get, ['todos','activos','proximos','pasados'])) $filtro_get = 'todos';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="stylesEventos.css">

</head>
<body>
<?php include 'navbar.php'; ?>

<section class="eventos-hero">
    <div class="container-xl px-4">

        <!-- Cabecera hero -->
        <div class="hero-header">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="hero-eyebrow">Luz de Hogar</div>
                    <h1 class="hero-title">Nuestros<br><em>Eventos</em></h1>
                    <p class="hero-subtitle">Talleres, exposiciones y actividades pensadas para acercarte al mundo artesanal. Ven a conocernos.</p>

                </div>
            </div>
        </div>

        <!-- Eventos activos destacados -->
        <?php if (count($activos) > 0): ?>
        <div class="activo-destacado">
            <div class="activo-label">
                <span class="activo-dot"></span>
                En curso hoy
            </div>
            <?php foreach($activos as $ev):
                $fev = new DateTime($ev['fecha_evento']);
            ?>
            <div class="card-activo mb-3"
                 onclick="abrirModal(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
                <div class="card-activo-img">
                    <?php if($ev['url_imagen']): ?>
                        <img src="<?= htmlspecialchars($ev['url_imagen']) ?>" alt="">
                    <?php else: ?>
                        <div class="card-activo-img-ph"><i class="bi bi-calendar-heart"></i></div>
                    <?php endif; ?>
                    <div class="card-activo-overlay"></div>
                </div>
                <div class="card-activo-body">
                    <div class="card-activo-fecha">
                        <i class="bi bi-clock"></i> Hoy, <?= $fev->format('H:i') ?>
                    </div>
                    <div class="card-activo-titulo"><?= htmlspecialchars($ev['titulo']) ?></div>
                    <?php if(!empty($ev['ubicacion_mapa'])): ?>
                    <div class="card-activo-ubicacion">
                        <i class="bi bi-geo-alt"></i>
                        <?= htmlspecialchars($ev['ubicacion_mapa']) ?>
                    </div>
                    <?php endif; ?>
                    <p class="card-activo-desc">
                        <?= nl2br(htmlspecialchars(mb_strimwidth($ev['descripcion'] ?? '', 0, 200, '…'))) ?>
                    </p>
                    <span class="card-activo-cta">
                        Ver detalles <i class="bi bi-arrow-right"></i>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tabs + grid -->
        <div class="eventos-tabs-wrap">
            <div class="eventos-tabs">
                <a href="eventos.php"                      class="tab-btn <?= $filtro_get==='todos'    ? 'activo':'' ?>"><i class="bi bi-calendar3"></i> Todos (<?= count($eventos) ?>)</a>
                <a href="eventos.php?filtro=activos"       class="tab-btn <?= $filtro_get==='activos'  ? 'activo':'' ?>"><i class="bi bi-calendar-check"></i> Hoy (<?= count($activos) ?>)</a>
                <a href="eventos.php?filtro=proximos"      class="tab-btn <?= $filtro_get==='proximos' ? 'activo':'' ?>"><i class="bi bi-calendar-plus"></i> Próximos (<?= count($proximos) ?>)</a>
                <a href="eventos.php?filtro=pasados"       class="tab-btn <?= $filtro_get==='pasados'  ? 'activo':'' ?>"><i class="bi bi-calendar-x"></i> Pasados (<?= count($pasados) ?>)</a>
            </div>

            <?php
            $lista = match($filtro_get) {
                'activos'  => $activos,
                'proximos' => $proximos,
                'pasados'  => $pasados,
                default    => array_merge($activos, $proximos, $pasados),
            };
            ?>

            <?php if(count($lista) > 0): ?>
            <div class="row g-4">
                <?php foreach($lista as $ev):
                    $fev       = new DateTime($ev['fecha_evento']);
                    $es_hoy    = $fev->format('Y-m-d') === $ahora->format('Y-m-d');
                    $es_pasado = $fev < $ahora && !$es_hoy;
                    $bc = $es_hoy ? 'badge-hoy' : ($es_pasado ? 'badge-pasado' : 'badge-futuro');
                    $bi = $es_hoy ? 'bi-circle-fill' : ($es_pasado ? 'bi-clock-history' : 'bi-clock');
                ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="evento-card <?= $es_pasado ? 'opacity-75':'' ?>"
                         onclick="abrirModal(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
                        <div class="evento-card-img-wrap">
                            <?php if($ev['url_imagen']): ?>
                                <img src="<?= htmlspecialchars($ev['url_imagen']) ?>" alt="">
                            <?php else: ?>
                                <div class="evento-card-img-ph"><i class="bi bi-calendar-event"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="evento-card-body">
                            <span class="evento-card-badge <?= $bc ?>">
                                <i class="bi <?= $bi ?>"></i>
                                <?= $fev->format('d/m/Y') ?> · <?= $fev->format('H:i') ?>
                            </span>
                            <div class="evento-card-titulo"><?= htmlspecialchars($ev['titulo']) ?></div>
                            <?php if($ev['descripcion']): ?>
                            <p class="evento-card-desc"><?= htmlspecialchars(mb_strimwidth($ev['descripcion'], 0, 110, '…')) ?></p>
                            <?php endif; ?>
                            <?php if(!empty($ev['ubicacion_mapa'])): ?>
                            <div class="evento-card-meta">
                                <i class="bi bi-geo-alt"></i>
                                <?= htmlspecialchars(mb_strimwidth($ev['ubicacion_mapa'], 0, 50, '…')) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <div class="eventos-vacio">
                <i class="bi bi-calendar-x"></i>
                <?php if($filtro_get==='activos'): ?>
                    <p style="color:#888;">No hay eventos programados para hoy.</p>
                    <p><a href="eventos.php?filtro=proximos" style="color:var(--color-tierra-oscuro);">Ver próximos eventos</a></p>
                <?php elseif($filtro_get==='proximos'): ?>
                    <p style="color:#888;">No hay eventos próximos. ¡Vuelve pronto!</p>
                <?php elseif($filtro_get==='pasados'): ?>
                    <p style="color:#888;">El archivo de eventos pasados está vacío.</p>
                <?php else: ?>
                    <p style="color:#888;">Todavía no hay eventos publicados.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ════ MODAL DETALLE ════ -->
<div class="modal-evento-overlay" id="modalEvento" onclick="cerrarFuera(event)">
    <div class="modal-evento-box">
        <div class="modal-img-wrap" id="modalImgWrap"></div>
        <div class="modal-evento-body">
            <h2 class="modal-evento-titulo" id="modalTitulo"></h2>
            
            <div class="modal-desc" id="modalDesc" style="margin-bottom: 15px;"></div>
            
            <div id="modalInfo"></div>
            
            <div class="modal-footer-btns" id="modalFooter"></div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirModal(ev) {
    const overlay = document.getElementById('modalEvento');

    // Imagen
    const imgHTML = ev.url_imagen
        ? `<button class="modal-close-btn" onclick="cerrarModal()"><i class="bi bi-x-lg"></i></button>
           <img src="${ev.url_imagen}" class="modal-evento-img" alt="${ev.titulo}">`
        : `<button class="modal-close-btn" onclick="cerrarModal()"><i class="bi bi-x-lg"></i></button>
           <div class="modal-evento-img-ph"><i class="bi bi-calendar-heart"></i></div>`;
    document.getElementById('modalImgWrap').innerHTML = imgHTML;

    // Título
    document.getElementById('modalTitulo').textContent = ev.titulo;
    

        // Descripción
    const descEl = document.getElementById('modalDesc');
    if (ev.descripcion) {
        descEl.innerHTML = ev.descripcion.replace(/\n/g, '<br>');
        descEl.style.display = '';
    } else {
        descEl.style.display = 'none';
    }


   // Info: fecha, hora, ubicación
const fecha = new Date(ev.fecha_evento);
const fechaStr = fecha.toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
const horaStr  = fecha.toLocaleTimeString('es-ES', { hour:'2-digit', minute:'2-digit' });

let infoHTML = `
    <div class="modal-info-row"><i class="bi bi-calendar3"></i><span>${fechaStr.charAt(0).toUpperCase() + fechaStr.slice(1)}</span></div>
    <div class="modal-info-row"><i class="bi bi-clock"></i><span>${horaStr}</span></div>`;


if (ev.ubicacion_mapa) {
    infoHTML += `
        <div class="modal-info-row" style="align-items: flex-start;">
            <i class="bi bi-geo-alt-fill" style="margin-top: 3px;"></i>
            <div style="width: 100%;">
                <span style="font-weight: bold; display: block; margin-bottom: 8px;">Ubicación del evento:</span>
                <div class="mapa-modal-contenedor" style="width: 100%; overflow: hidden; border-radius: 8px; border: 1px solid #ddd;">
                    <iframe 
                        src="${ev.ubicacion_mapa}" 
                        width="100%" 
                        height="200" 
                        style="border:0; display:block;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>`;
}

document.getElementById('modalInfo').innerHTML = infoHTML;


// Footer 
    let footerHTML = ``; 
    document.getElementById('modalFooter').innerHTML = footerHTML;

    overlay.classList.add('abierto');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('modalEvento').classList.remove('abierto');
    document.body.style.overflow = '';
}

function cerrarFuera(e) {
    if (e.target === document.getElementById('modalEvento')) cerrarModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });
</script>
</body>
</html>
