<?php
 //http://localhost/practicas/WebTienda/JR_M26_ProductosArtesanales-main/IndexRegistro.php
 require_once("funcionAuth.php");
 ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Luz de Hogar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100"> 

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                
                <div class="text-center mb-5">
                    <img src="img/logoLuzDeHogar.png" alt="Luz de Hogar" class="img-fluid" style="max-height: 150px;">
                    <h1 style="font-family: 'Playfair Display'; color: #333; margin-top: 15px;">Luz de Hogar</h1>
                    <p class="text-auxiliar">Registrate para acceder a tu refugio</p>
                </div>


                <!--MENSAJES PROCEDENTE DE RegistrarBD dando el error ocurrido-->
                <div class="card shadow-sm p-4">
                    <form action="BD/RegistrarBD.php" method="POST" novalidate>
                    <div class="mb-3">

                    <?php
                    if (isset($_SESSION['mensaje_error'])) {
                    ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['mensaje_error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php
                        unset($_SESSION['mensaje_error']);
                    }
                    ?>

                        <label class="form-label" for="nombre">Nombre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   placeholder="Nombre" autocomplete="nombre" value="<?= $_SESSION['old']['nombre'] ?? '' ?>" required><!--Al recargar formulario se queda guardado los ultimos datros introducidos-->
                        </div>
                        <label class="form-label" for="apellido">Apellido</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-people-fill"></i></span>
                            <input type="text" class="form-control" id="apellido" name="apellido"
                                   placeholder="Apellido" autocomplete="apellido" value="<?= $_SESSION['old']['apellido'] ?? '' ?>" required><!--Al recargar formulario se queda guardado los ultimos datros introducidos-->
                        </div>
                        <label class="form-label" for="email">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="tucorreo@ejemplo.com" autocomplete="email" value="<?= $_SESSION['old']['email'] ?? '' ?>" required><!--Al recargar formulario se queda guardado los ultimos datros introducidos-->
                        </div>
                    </div>
                    <!--Contraseña-->
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                    autocomplete="current-password" required>
                            <button class="btn-toggle-pass" type="button" id="togglePass" aria-label="Mostrar contraseña"><!--Boton de ojo para mostrar contraseña-->
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Barra de contraseña debil/fuerte-->
                     <div class="progress mt-2" style="height: 6px;">
                        <div id="strengthBar" class="progress-bar" style="width: 0%;"></div>
                     </div>
                     <small id="strengthText"></small>


                    <!--Confirmar contraseña-->
                    <div class="mb-3">
                        <label class="form-label" for="confirmPassword">Confirmar contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                    autocomplete="current-password" required>
                            <button class="btn-toggle-pass" type="button" id="togglePass" aria-label="Mostrar contraseña"><!--Boton de ojo para mostrar contraseña-->
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <!--MENSAJE-->
                    <small id="matchText"></small>
                    </div>
                   

                    <div class="d-flex justify-content-between align-items-center mb-4"></div>

                    <div class="ldh-divider mb-4"><span></span></div>

                    <button type="submit" class="btn btn-luz w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Registrarse
                    </button>
                </form>
                <?php unset($_SESSION['old']); ?>

               
                </div>

                <div class="text-center mt-4">
                    <small class="text-muted">¿Tienes cuenta? <a href="index.php" style="color: #C17A50; text-decoration: none;">Inicia sesion aquí</a></small>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scriptRegistro.js"></script>

</body>
</html>