<?php

require_once("funcionAuth.php");

require_once("BD/conexion.php"); 
require_once("funcionEstrellas.php"); 


try {
    $query = "SELECT p.ID, p.nombre, p.precio, p.categoria_id, c.nombre as categoria_nombre, i.url_imagen,
          IFNULL(AVG(r.puntuacion), 0) as promedio,
          COUNT(r.ID) as num_resenas
          FROM producto p 
          LEFT JOIN producto_imagenes i ON p.ID = i.producto_id 
          LEFT JOIN CATEGORIA c ON p.categoria_id = c.ID
          LEFT JOIN RESENA r ON p.ID = r.producto_id AND r.visible = 1
          WHERE i.orden = 1 OR i.orden IS NULL
          GROUP BY p.ID";
    $stmt = $pdo->query($query);
    $productos = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css?v=3">
</head>
<body >
    <?php include 'navbar.php'; ?>
    <?php include 'modal_contacto.php'; ?>

    <div class="container mt-3">
        <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="toastCarrito" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['mensaje']; 
                    unset($_SESSION['mensaje']);
                ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<?php endif; ?>
    </div>
    <?php unset($_SESSION['mensaje']); ?>


    <main class="container mb-5">
        <div class="text-center cabecera-fija">
    <h1>Nuestra Colección</h1>
    <div class="mx-auto" style="width: 60px; height: 3px; background-color: var(--color-tierra-medio);"></div>
    <p class="text-muted mt-2 mb-0 small">Objetos creados para durar y acompañarte.</p>
</div>
        <div class="row">
            <aside class="col-lg-3 mb-4">
                <div class="p-4 rounded-4 shadow-sm" style="background-color: white; border: 1px solid #eee;">
                    <h5 class="fw-bold mb-3">Categorías</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-decoration-none text-dark fw-bold filtro-categoria active" data-filtro="todos">Todo</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-muted filtro-categoria" data-filtro="ceramica">Cerámica artesanal</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-muted filtro-categoria" data-filtro="velas">Velas vegetales</a></li>
                    </ul>
                    <hr>
                    <h5 class="fw-bold mb-3">Precio</h5>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="precioMin" class="form-label small text-muted mb-1">Min (€)</label>
                            <input type="number" class="form-control form-control-sm" id="precioMin" placeholder="0" min="0">
                        </div>
                        <div class="col-6">
                            <label for="precioMax" class="form-label small text-muted mb-1">Max (€)</label>
                            <input type="number" class="form-control form-control-sm" id="precioMax" placeholder="100" min="0">
                        </div>
                    </div>
                    <button class="btn btn-tierra btn-sm w-100 mt-3" id="btnFiltrarPrecio">Aplicar Filtro</button>
                    <button class="btn btn-outline-secondary btn-sm w-100 mt-2" id="btnResetPrecio" style="display: none;">resetear</button>
                </div>
            </aside>
            <div class="col-lg-9">
                <div class="row g-4 justify-content-center">
                    <?php foreach ($productos as $producto): ?>
    <?php
    $categoriaRaw = strtolower($producto['categoria_nombre'] ?? 'sin-categoria');
    $categoria = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', ' '], ['a', 'e', 'i', 'o', 'u', 'n', '-'], $categoriaRaw);
    $precioFormateado = number_format($producto['precio'], 2, ',', '.');
    $imagen = !empty($producto['url_imagen']) ? 'img/' . $producto['url_imagen'] : 'img/default.jpg';
    ?>
    
    <div class="col-md-4 col-6 producto-item" data-categoria="<?= htmlspecialchars($categoria); ?>" id="prod-<?= $producto['ID']; ?>">
        <div class="product-card text-center h-100 d-flex flex-column">
            <a href="detalle.php?id=<?= intval($producto['ID']); ?>" class="text-decoration-none text-dark flex-grow-1">
                <div class="contenedor-zoom shadow-sm">
                    <img src="<?= htmlspecialchars($imagen); ?>" alt="<?= htmlspecialchars($producto['nombre']); ?>" class="img-fluid">
                </div>
                <div class="product-info p-2">
                    <h6 class="product-title fw-bold text-truncate"><?= htmlspecialchars($producto['nombre']); ?></h6>
                    <p class="product-price text-muted mb-1"><?= $precioFormateado; ?> €</p>
                    <div style="font-size:0.78rem;">
                        <?= mostrarEstrellas($producto['promedio']); ?>
                    </div>
                </div>
            </a>
            
            <form action="add_carrito.php" method="POST">
                <input type="hidden" name="id" value="<?= $producto['ID']; ?>">
                <input type="hidden" name="nombre" value="<?= htmlspecialchars($producto['nombre']); ?>">
                <input type="hidden" name="precio" value="<?= $producto['precio']; ?>">
                <input type="hidden" name="imagen" value="<?= htmlspecialchars($imagen); ?>">
                <input type="hidden" name="seccion" value="prod-<?= $producto['ID']; ?>">
                <button class="btn btn-tierra btn-sm w-100 mt-2">
                    Añadir
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($productos)): ?>
                        <div class="col-12 text-center py-5">
                            <p class="text-muted fs-5">No hay productos disponibles en este momento.</p>
                            <a href="home.php" class="btn btn-tierra mt-3">Volver al inicio</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include("footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const filtros = document.querySelectorAll('.filtro-categoria');
            const productos = document.querySelectorAll('.producto-item');
            const inputMin = document.getElementById('precioMin');
            const inputMax = document.getElementById('precioMax');
            const btnFiltrar = document.getElementById('btnFiltrarPrecio');
            const btnReset = document.getElementById('btnResetPrecio');

            let categoriaActual = 'todos';
            
            function extraerPrecio(textoPrecio){
                return parseFloat(textoPrecio.replace('€','').trim().replace(',','.'));
            }
            
            function filtrarProductos(){
                const min = inputMin.value ? parseFloat(inputMin.value) : 0;
                const max = inputMax.value ? parseFloat(inputMax.value) : Infinity;

                productos.forEach(producto => {
                    const categoriaProducto = producto.getAttribute('data-categoria');
                    const precioTexto = producto.querySelector('.product-price').textContent;
                    const precio = extraerPrecio(precioTexto);
                    
                    const cumpleCategoria = categoriaActual === 'todos' || categoriaProducto === categoriaActual;
                    const cumplePrecio = precio >= min && precio <= max;
                    
                    if(cumpleCategoria && cumplePrecio){
                        producto.style.display = 'block';
                        producto.classList.remove('oculto');
                        producto.classList.add('visible');
                    } else {
                        producto.style.display = 'none';
                        producto.classList.add('oculto');
                        producto.classList.remove('visible');
                    }
                });

                btnReset.style.display = (inputMin.value || inputMax.value) ? 'block' : 'none';
            }   
            
            filtros.forEach(filtro => {
                filtro.addEventListener('click', function(e){
                    e.preventDefault();
                    categoriaActual = this.getAttribute('data-filtro');                   
                    
                    filtros.forEach(f => {
                        f.classList.remove('active', 'fw-bold', 'text-dark');
                        f.classList.add('text-muted');
                    });
                    this.classList.add('active', 'fw-bold', 'text-dark');
                    this.classList.remove('text-muted');

                    filtrarProductos();
                });
            });
        
            btnFiltrar.addEventListener('click', filtrarProductos);
            inputMin.addEventListener('keypress', (e) => { if(e.key === 'Enter') filtrarProductos(); });
            inputMax.addEventListener('keypress', (e) => { if(e.key === 'Enter') filtrarProductos(); });

            btnReset.addEventListener('click', function(){
                inputMin.value = '';
                inputMax.value = '';
                filtrarProductos();
            });
        });
    </script>
    

    <script>
document.addEventListener("DOMContentLoaded", function() {
    let toastEl = document.getElementById("toastCarrito");
    if (toastEl) {
        let toast = new bootstrap.Toast(toastEl, {
            delay: 3000 // ⏱️ dura 3 segundos
        });
        toast.show();
    }
});
</script>
</body>
</html>