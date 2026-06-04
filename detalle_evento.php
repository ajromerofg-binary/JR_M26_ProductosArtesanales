<?php
ob_start();
require_once('BD/conexion.php');
require_once('funcionAuth.php');

// Validar ID recibido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Evento no válido.');
}

$id = (int) $_GET['id'];

// Buscar evento por su ID
$sql = "SELECT * FROM EVENTO WHERE ID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$evento = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe
if (!$evento) {
    die('Evento no encontrado.');
}

$fecha_evento = new DateTime($evento['fecha_evento']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($evento['titulo']) ?> | Eventos</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="stylesEventos.css">
</head>
<body class="d-flex flex-column min-vh-100" style="background-color: var(--color-crema);">

<?php include 'navbar.php'; ?>

<main class="container my-5 flex-grow-1">

    <a href="eventos.php" class="btn btn-outline-secondary mb-4">
        ← Volver a eventos
    </a>

    <div class="card shadow-sm border-0">

        <?php if (!empty($evento['url_imagen'])): ?>
            <img src="<?= htmlspecialchars($evento['url_imagen']) ?>"
                 class="card-img-top"
                 alt="<?= htmlspecialchars($evento['titulo']) ?>">
        <?php endif; ?>

        <div class="card-body p-4">
            <h1 class="mb-3">
                <?= htmlspecialchars($evento['titulo']) ?>
            </h1>

            <p class="text-muted mb-3">
                <strong>Fecha:</strong>
                <?= $fecha_evento->format('d/m/Y H:i') ?>
            </p>

            <?php if (!empty($evento['descripcion'])): ?>
                <div class="mt-4">
                    <?= nl2br(htmlspecialchars($evento['descripcion'])) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($evento['ubicacion_mapa'])): ?>
            <div class="mt-5">
                <h3 class="mb-3">Ubicación</h3>

                <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
                    <iframe
                        src="<?= htmlspecialchars($evento['ubicacion_mapa']) ?>"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>