<?php
require_once("funcionAuth.php");
require_once("BD/conexion.php"); 
try {
   $ids_favoritos = "1, 2, 3, 4"; 
    
    $query_favs = "SELECT p.ID, p.nombre, p.precio, i.url_imagen 
                   FROM producto p 
                   LEFT JOIN producto_imagenes i ON p.ID = i.producto_id 
                   WHERE (i.orden = 1 OR i.orden IS NULL)
                   AND p.ID IN ($ids_favoritos)
                   ORDER BY FIELD(p.ID, $ids_favoritos)"; 
    
    $stmt_favs = $pdo->query($query_favs);
    $favoritos = $stmt_favs->fetchAll();
} catch (PDOException $e) {
    die("Error en la consulta de favoritos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luz de Hogar</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <section class="hero-franja">
        <div class="container">
            <div class="row align-items-center">

            <!-- COLUMNA IZQUIERDA: Texto estatico-->
                <div class="col-lg-6 text-center text-lg-start">
                    <h1 class="display-5 mb-3">Crea un refugio de calma en tu propia casa</h1>
                    <p class="lead mb-5 text-muted" style="max-width: 450px;">
                        Cerámica de autor y velas vegetales vertidas a mano, diseñadas para los momentos que importan.
                    </p>
                    <a href="tienda.php" class="btn btn-tierra text-decoration-none">Ver Colección</a>
                </div>

                <!--COLUMNA DERECHA: Carrusel de imagenes-->
                <div class="col-lg-6 text-center mt-4 mt-lg-0">
                    <div class="position-relative">
                    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
                        <div class="carousel-indicators" style="bottom: -50px;">
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" style="background-color: var(--color-tierra-oscuro); width: 30px; height: 2px;"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" style="background-color: var(--color-tierra-oscuro); width: 30px; height: 2px;"></button>
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" style="background-color: var(--color-tierra-oscuro); width: 30px; height: 2px;"></button>
                        </div>
                        <div class="carousel-inner">
                            <!--Imagen 1: Ceramica -->
                            <div class="carousel-item active" data-bs-interval="4000">
                                <img src="img/taza-home1.jpg" class="img-hero-ajustada shadow-sm" alt="Ceramica artesanal">
                            </div>
                            <!--Imagen 2: Velas -->
                            <div class="carousel-item" data-bs-interval="4000">
                                <img src="img/fondo-vela1.jpg" class="img-hero-ajustada shadow-sm" alt="Velas vegetales">
                            </div>
                            <!--Imagen 3: Ambiente -->
                            <div class="carousel-item" data-bs-interval="4000">
                                <img src="img/fondo-manos3.jpg" class="img-hero-ajustada shadow-sm" alt="Ambiente Luz de Hogar">
                            </div>
                        </div>
                    </div>
                    <div class="badge-artesano shadow-sm">
                        <span>100%<br>Artesanal</span>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container my-5 py-5">
        <div class="row gx-5 gy-5"> 
            <div class="col-md-6">
                <div class="row gy-5"> 
                    <div class="col-12 text-center">
                        <img src="img/fondo-manos.jpg" class="img-mosaico-pequena shadow-sm" alt="Proceso">
                    </div>
                    <div class="col-12 text-center">
                        <img src="img/fondo-manos2.jpg" class="img-mosaico-pequena shadow-sm" alt="Resultado">
                    </div>
                </div>
                <div class="text-center mt-4">
                    <h4 class="fw-bold text-uppercase small">Cerámica</h4>
                    <p class="text-muted small">Piezas únicas de gres y barro vertidas a mano</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="text-center h-100 d-flex flex-column">
                    <img src="img/fondo-vela.jpg" class="img-mosaico-larga shadow-sm mb-3" alt="Velas">
                    <div class="mt-auto">
                        <h4 class="fw-bold text-uppercase small">Velas</h4>
                        <p class="text-muted small">Ceras vegetales y aromas naturales</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<section class="container mb-5 pb-5">
        <h2 class="display-5 fw-normal text-center mb-5">Favoritos de la temporada</h2>
        <div class="row g-4 text-center">
            
            <?php if (count($favoritos) > 0): ?>
                <?php foreach ($favoritos as $fav): ?>
                    <?php 
                        $imagen_final = !empty($fav['url_imagen']) ? 'img/' . $fav['url_imagen'] : 'img/default.jpg';
                    ?>
                    <div class="col-6 col-md-3">
                        
                        <a href="detalle.php?id=<?= $fav['ID']; ?>" class="text-decoration-none text-dark product-link">
                            <div class="contenedor-zoom shadow-sm">
                                <img src="<?= $imagen_final; ?>" alt="<?= htmlspecialchars($fav['nombre']); ?>">
                            </div>
                            <h6 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($fav['nombre']); ?></h6>
                            <p class="text-muted small"><?= number_format($fav['precio'], 2, ',', '.'); ?> €</p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted">Próximamente nuevos favoritos...</p>
                </div>
            <?php endif; ?>

        </div>
    </section>

    
    <?php include 'footer.php'; ?>

</body>
</html>
