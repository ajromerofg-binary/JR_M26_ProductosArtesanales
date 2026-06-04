<?php
require_once("funcionAuth.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosotros | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
     <?php include 'navbar.php'; ?>

    <main class="container mb-5">
        <div class="text-center cabecera-fija">
            <h1>Nuestra Historia</h1>
            <div class="mx-auto mb-4" style="width: 60px; height: 3px; background-color: var(--color-tierra-medio);"></div>
    </div>
   
    

        <div class="row align-items-center mb-5 pb-lg-5">
            <div class="col-md-6">
                <img src="img/ceramica.png" alt="Taller" class="img-hero-ajustada shadow-sm">
            </div>
            <div class="col-md-6 ps-lg-5 mt-4 mt-md-0">
                <h2 class="h1 mb-4">Manos que crean</h2>
                <p>Luz de Hogar nació en un pequeño rincón del Mediterráneo, buscando recuperar la calma que el mundo moderno nos quita. Cada pieza de cerámica es torneada individualmente, respetando los tiempos del barro.</p>
                <p>No buscamos la perfección industrial, sino la calidez de lo que ha sido tocado por manos humanas.</p>
            </div>
        </div>

        <div class="row align-items-center flex-column-reverse flex-md-row">
            <div class="col-md-6 pe-lg-5 mt-4 mt-md-0 text-md-end">
                <h2 class="h1 mb-4">Esencias con alma</h2>
                <p>Nuestras velas son el complemento perfecto para tus momentos de lectura o descanso. Utilizamos cera de soja pura y aceites esenciales que evocan la naturaleza y el hogar.</p>
                <p>Un proceso honesto, desde el vertido de la cera hasta el etiquetado final en nuestro taller.</p>
            </div>
            <div class="col-md-6">
                <img src="img/soja-vela.jpg" alt="Velas" class="img-hero-ajustada shadow-sm">
            </div>
        </div>
    </main>
    
 <?php include 'footer.php'; ?>

</body>
</html>
