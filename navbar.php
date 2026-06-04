<?php 
require_once("funcionAuth.php");

$user = $_SESSION['user'] ?? null;

// Página actual — variable limpia (de navbar.php)
$current_page = basename($_SERVER['PHP_SELF']);

// Contador carrito
$cantidad_carrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad_carrito += $item['cantidad'];
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-luz sticky-top shadow-sm">
    <div class="container">

        <a class="navbar-brand" href="home.php">
            <img src="img/logoLuzDeHogar.png" alt="Luz de Hogar" style="width: 110px;">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">

            <!-- Saludo — solo si hay usuario logueado -->
            <?php if ($user): ?>
            <div class="me-5">
                <span class="lead text-muted">
                    Bienvenid@ <?= htmlspecialchars($user['nombre']) ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Links principales -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="home.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="tienda.php">Tienda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold" href="nosotros.php">Nosotros</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link fw-bold" href="eventos.php">Eventos</a>
                    <a class="nav-link fw-bold dropdown-toggle dropdown-toggle-split px-1"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false"
                       style="padding-left:2px !important;">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-luz">
                        <li>
                            <a class="dropdown-item" href="eventos.php">
                                <i class="bi bi-calendar3 me-2"></i>Todos los eventos
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="eventos.php?filtro=activos">
                                <i class="bi bi-calendar-check me-2" style="color:var(--color-tierra-oscuro);"></i>Hoy
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="eventos.php?filtro=proximos">
                                <i class="bi bi-calendar-plus me-2" style="color:var(--color-tierra-oscuro);"></i>Próximos
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="eventos.php?filtro=pasados">
                                <i class="bi bi-calendar-x me-2" style="color:#999;"></i>Pasados
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <!-- NO active porque abre modal -->
                    <a class="nav-link fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#modalContacto">
                        Contacto
                    </a>
                </li>
            </ul>

            <!-- Iconos derecha -->
            <div class="d-flex align-items-center">

                <!-- Carrito con contador -->
                <a href="carrito.php" class="nav-icon position-relative">
                    <i class="bi bi-bag"></i>
                    <?php if ($cantidad_carrito > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size:0.6rem;">
                        <?= $cantidad_carrito ?>
                    </span>
                    <?php endif; ?>
                </a>

                <?php if (!$user): ?>
                    <!-- 🔓 NO LOGUEADO — va al login -->
                    <a href="index.php" class="nav-icon" title="Iniciar sesión">
                        <i class="bi bi-person"></i>
                    </a>

                <?php else: ?>
                    <!-- 🔐 LOGUEADO -->
                    <?php if ($user['rol'] === 'admin'): ?>
                        <!-- Admin → panel de administración -->
                        <a href="admin.php" class="nav-icon" title="Panel de administración">
                            <i class="bi bi-shield-lock"></i>
                        </a>
                    <?php else: ?>
                        <!-- Cliente → área de cliente -->
                        <a href="mi_cuenta.php" class="nav-icon" title="Mi cuenta">
                            <i class="bi bi-person-fill"></i>
                        </a>
                    <?php endif; ?>

                    <a href="logout.php" class="nav-icon" title="Cerrar sesión">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>

                <?php endif; ?>

            </div>

        </div>
    </div>
</nav>

<script>
document.addEventListener('click', function (event) {
    // Seleccionamos el contenedor del menú desplegable y el botón de la hamburguesa
    const navbarCollapse = document.getElementById('navbarNav');
    const navbarToggler = document.querySelector('.navbar-toggler');
    
    // Verificamos si el menú móvil está actualmente expandido/visible
    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
        
        // Si el clic NO se ha hecho ni dentro del menú ni dentro del botón que lo abre
        if (!navbarCollapse.contains(event.target) && !navbarToggler.contains(event.target)) {
            
            // Usamos la API nativa de Bootstrap para cerrarlo limpiamente con animación
            const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse) 
                               || new bootstrap.Collapse(navbarCollapse, { toggle: false });
            bsCollapse.hide();
        }
    }
});
</script>
