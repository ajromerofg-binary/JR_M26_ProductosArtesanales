<?php
//http://localhost/practicas/WebTienda/luz_de_hogar/index.php 
require_once('funcionAuth.php');
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
                    <a href="home.php"> <img src="img/logoLuzDeHogar.png" alt="Luz de Hogar" class="img-fluid" style="max-height: 150px;"></a>
                    <h1 style="font-family: 'Playfair Display'; color: #333; margin-top: 15px;">Luz de Hogar</h1>
                    <p class="text-auxiliar">Inicia sesión para acceder a tu refugio</p>
                </div>

                <div class="card shadow-sm p-4">
                    <form action="login.php" method="POST">
                        <div class="mb-4">
                            <!--MENSAJES PROCEDENTE DE RegistrarBD y login dando el error o exito ocurrido-->
                            <div>
                                <?php 
                                //Mostrar mensaje de Usuario creado con exito
                                if (isset($_SESSION['mensaje_exito'])){
                                    ?>
                                    <div class="alert alert-success alert-dimissible fade show" role="alert">
                                        <?= $_SESSION['mensaje_exito'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                    <?php
                                    unset($_SESSION['mensaje_exito']);
                                }
                                //Mostrar mensaje de datos no validos
                                 if (isset($_SESSION['mensaje_error'])){
                                    ?>
                                    <div class="alert alert-danger alert-dimissible fade show" role="alert">
                                        <?= $_SESSION['mensaje_error'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                    <?php
                                    unset($_SESSION['mensaje_error']);
                                }
                                ?>
                            </div>
                            <label class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="nombre" class="form-control" placeholder="ejemplo@luz.com" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" for="password">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="contraseña" id="password" class="form-control" placeholder="••••••••" required>
                                <button class="btn-toggle-pass" type="button" id="togglePass" aria-label="Mostrar contraseña"><!--Boton de ojo para mostrar contraseña-->
                                <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-luz">Entrar</button>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4">
                    <small class="text-muted">¿No tienes cuenta? <a href="IndexRegistro.php" style="color: #C17A50; text-decoration: none;">Regístrate aquí</a></small>
                </div>

            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!--Funcionamiento del ojo para mostrar contraseña-->
    <script>
    document.querySelectorAll('.tipo-option').forEach(opt => {
        opt.addEventListener('click', () => {
            document.querySelectorAll('.tipo-option').forEach(o => o.classList.remove('activo'));
            opt.classList.add('activo');
            document.getElementById('tipoHidden').value = opt.querySelector('input').value;
        });
    });
    const togglePass = document.getElementById('togglePass');
    const passInput  = document.getElementById('password');
    const eyeIcon    = document.getElementById('eyeIcon');
    togglePass.addEventListener('click', () => {
        const visible = passInput.type === 'text';
        passInput.type = visible ? 'password' : 'text';
        eyeIcon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
    </script>

    <!--Duracion del mensaje mostrado en pantalla-->
    <script>
    setTimeout(() => {
        let alert = document.querySelector('.alert');
        if (alert) {
            let bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000); // 3 segundos
    </script>


</body>
</html>