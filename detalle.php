<?php
require_once("funcionAuth.php");
require_once("BD/conexion.php"); 
require_once("funcionEstrellas.php"); 


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ─── VERIFICAR SI EL USUARIO PUEDE RESEÑAR ──────────────────
$puedeResenar = false;
$cliente_id = null;
$yaTieneResena = false;

if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'cliente') {
    $cliente_id = $_SESSION['user']['id'];
    
    // 1. ¿Compró y recibió?
    $stmtCheck = $pdo->prepare("
        SELECT pit.ID 
        FROM PEDIDO pe
        JOIN PEDIDO_ITEM pit ON pit.pedido_id = pe.ID
        WHERE pe.cliente_id = ? 
          AND pit.producto_id = ? 
          AND pe.estado = 'Entregado'
        LIMIT 1
    ");
    $stmtCheck->execute([$cliente_id, $id]);
    $tienePedidoEntregado = $stmtCheck->fetch();
    
    // 2. ¿Ya valoró?
    $stmtYa = $pdo->prepare("SELECT ID FROM RESENA WHERE cliente_id = ? AND producto_id = ? LIMIT 1");
    $stmtYa->execute([$cliente_id, $id]);
    $yaTieneResena = $stmtYa->fetch();

    // AHORA: Puede reseñar si compró y NO ha valorado aún (sin depender de $_GET['opinar'])
    $puedeResenar = ($tienePedidoEntregado && !$yaTieneResena);
}

try {
    $query = "SELECT p.*, i.url_imagen, IFNULL(AVG(r.puntuacion), 0) as promedio 
              FROM producto p 
              LEFT JOIN producto_imagenes i ON p.ID = i.producto_id 
              LEFT JOIN RESENA r ON p.ID = r.producto_id
              WHERE p.ID = ? AND (i.orden = 1 OR i.orden IS NULL) 
              GROUP BY p.ID
              LIMIT 1";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        header("Location: tienda.php");
        exit();
    }

    $stmtReseñas = $pdo->prepare("
    SELECT r.*, c.Nombre as usuario_nombre 
    FROM RESENA r 
    LEFT JOIN CLIENTE c ON r.cliente_id = c.ID 
    WHERE r.producto_id = ? 
      AND r.visible = 1
    ORDER BY r.fecha DESC
    ");

    $stmtReseñas->execute([$id]);
    $listaReseñas = $stmtReseñas->fetchAll();

} catch (PDOException $e) {
    die("Error al cargar el detalle del producto: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre']); ?> | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="d-flex flex-column min-vh-100" style="background-color: var(--color-crema);">

    <?php include 'navbar.php'; ?>
    
    <main class="container mt-4 mb-5 flex-grow-1">
        <div class="mb-4">
            <a href="tienda.php" class="text-decoration-none text-muted small fw-bold">
                <i class="bi bi-chevron-left"></i> Volver a la colección
            </a>
        </div>

        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="contenedor-img-detalle">
                    <img src="img/<?= htmlspecialchars($producto['url_imagen']); ?> "class="img-fluid rounded-4 shadow-sm" 
                         alt="<?= htmlspecialchars($producto['nombre']); ?>" 
                         class="img-fluid w-100">
                </div>
            </div>

            <div class="col-lg-6">
                <div class="product-info ps-lg-4">
                    <h1 class="display-5 fw-bold mb-1" style="font-family: 'Playfair Display', serif;">
                        <?= htmlspecialchars($producto['nombre']); ?>
                    </h1>
                    
                    <div class="mb-3">
                        <?php if ($producto['promedio'] > 0): ?>
                            <!--Si hay reseñas, mostramos las estrellas y el promedio-->
                            <?= mostrarEstrellas($producto['promedio']); ?>
                            <span class="ms-2 text-muted small">(<?= number_format($producto['promedio'], 1) ?> / 5)</span>
                        <?php else: ?>
                            <!--Si no hay reseñas, mostramos un mensaje amable-->
                            <span class="text-muted small italic">
                                <i class="bi bi-chat-dots me-1"></i> ¡Sé el primero en compartir tu opinión!
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="h3 mb-4 text-tierra-oscuro">
                        <?= number_format($producto['precio'], 2, ',', '.'); ?> €
                    </p>

                    <div class="mb-4">
                        <h6 class="small fw-bold text-uppercase text-muted mb-2">Descripción</h6>
                        <p class="text-muted lh-lg">
                            <?= nl2br(htmlspecialchars($producto['descripcion'])); ?>
                        </p>
                    </div>

                    <div class="mb-4">
                        <p class="small">
                            <strong>Disponibilidad:</strong> 
                            <?php if ($producto['stock'] > 0): ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> En stock</span>
                            <?php else: ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> Agotado</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php
                        $categoriaRaw = strtolower($producto['categoria_nombre'] ?? 'sin-categoria');
                        $categoria = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', ' '], ['a', 'e', 'i', 'o', 'u', 'n', '-'], $categoriaRaw);
                        $precioFormateado = number_format($producto['precio'], 2, ',', '.');
                        $imagen = !empty($producto['url_imagen']) ? 'img/' . $producto['url_imagen'] : 'img/default.jpg';
                    ?>

                    <div class="mt-4">
                        <form action="add_carrito.php" method="POST" class="d-flex align-items-center gap-3">
                            <!-- Input de cantidad -->
                            <input type="number" name="cantidad" value="1" min="1" max="<?= $producto['stock']; ?>" 
                                class="form-control text-center" style="width: 80px; height: 45px;"
                                <?= ($producto['stock'] <= 0) ? 'disabled' : ''; ?>>
                            
                            <!-- Inputs ocultos necesarios -->
                            <input type="hidden" name="id" value="<?= $producto['ID']; ?>">
                            <input type="hidden" name="nombre" value="<?= htmlspecialchars($producto['nombre']); ?>">
                            <input type="hidden" name="precio" value="<?= $producto['precio']; ?>">
                            <input type="hidden" name="imagen" value="<?= htmlspecialchars($imagen); ?>">
                            <input type="hidden" name="seccion" value="prod-<?= $producto['ID']; ?>">
                            
                            <!-- Botón de añadir -->
                            <button type="submit" class="btn btn-tierra btn-lg flex-grow-1 d-flex align-items-center justify-content-center gap-2 <?= ($producto['stock'] <= 0) ? 'disabled' : ''; ?>" style="height: 45px;">
                                <i class="bi bi-bag-plus"></i> 
                                <span>Añadir al carrito</span>
                            </button>
                        </form>
                    </div>

                    <div class="mt-5 p-4 rounded-3" style="background-color: #fcf9f5; border: 1px dashed #d1c1b1;">
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="bi bi-box-seam d-block mb-1 fs-4 text-tierra"></i>
                                <span class="very-small d-block" style="font-size: 0.7rem;">Envío Cuidado</span>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-flower1 d-block mb-1 fs-4 text-tierra"></i>
                                <span class="very-small d-block" style="font-size: 0.7rem;">Artesanal</span>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-arrow-repeat d-block mb-1 fs-4 text-tierra"></i>
                                <span class="very-small d-block" style="font-size: 0.7rem;">Devolución 14 días</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="separador-resenas">

<div class="row justify-content-center" id="resenas">
    <div class="col-lg-8">

        <!-- Mensaje de éxito al publicar -->
        <?php if(isset($_GET['msj']) && $_GET['msj'] == 'ok'): ?>
            <div class="alert alert-resena alert-dismissible fade show" role="alert">
                ¡Gracias! Tu reseña se ha publicado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($puedeResenar): ?>
            <div class="resena-form-container mb-5">
                <h4 class="resena-titulo">Deja tu opinión</h4>
                <form action="guardar_reseña.php" method="POST">
                    <input type="hidden" name="producto_id" value="<?= $producto['ID'] ?>">
                    
                    <div class="mb-4 text-center">
                        <label class="label-puntuacion">¿Cómo puntuarías este producto?</label>
                        <div class="rating-input">
                            <input type="radio" name="estrellas" value="5" id="star5" required><label for="star5">★</label>
                            <input type="radio" name="estrellas" value="4" id="star4"><label for="star4">★</label>
                            <input type="radio" name="estrellas" value="3" id="star3"><label for="star3">★</label>
                            <input type="radio" name="estrellas" value="2" id="star2"><label for="star2">★</label>
                            <input type="radio" name="estrellas" value="1" id="star1"><label for="star1">★</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="label-comentario">Tu comentario</label>
                        <textarea name="comentario" class="form-control resena-input" rows="3" placeholder="Escribe aquí tu experiencia..." required></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-tierra px-5">Publicar reseña</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Lista de opiniones (Siempre visible) -->
        <div class="resenas-lista">
            <h4 class="resena-titulo">Opiniones de otros clientes</h4>
            <?php if ($listaReseñas): ?>
                <?php foreach ($listaReseñas as $res): ?>
                    <div class="resena-card shadow-sm mb-4 px-4" style="background-color: #fcf9f5; border: 1px dashed #d1c1b1;">
                        <div class="resena-header">
                            <div class="resena-info-usuario">
                                <span class="resena-nombre"><?= htmlspecialchars($res['usuario_nombre'] ?? 'Cliente') ?></span>
                                <small class="resena-fecha">Publicado el <?= date('d/m/Y', strtotime($res['fecha'])) ?></small>
                            </div>
                            <div class="resena-estrellas">
                                <?= mostrarEstrellas($res['puntuacion']) ?>
                            </div>
                        </div>
                        <p class="resena-texto">"<?= htmlspecialchars($res['comentario']) ?>"</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="resena-vacia">
                    <p>Aún no hay opiniones. ¡Sé el primero en compartir la tuya!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</main>

<?php include("footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>