<?php
//http://localhost/practicas/WebTienda/luz_de_hogar/login.php
require_once("BD/conexion.php");
require_once("funcionAuth.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['nombre']) && !empty($_POST['contraseña'])) {

        $email    = trim($_POST['nombre']);
        $password = $_POST['contraseña'];

        // ─────────────────────────────────────────────────────
        // 🔴 ADMINISTRADOR
        // Contraseña guardada con SHA2 (MySQL) = hash('sha256') en PHP
        // ─────────────────────────────────────────────────────
        $stmtAdmin = $pdo->prepare("SELECT ID, nombre, contraseña FROM ADMINISTRADOR WHERE email = :email");
        $stmtAdmin->execute(['email' => $email]);
        $admin = $stmtAdmin->fetch();

        if ($admin && hash('sha256', $password) === $admin['contraseña']) {

        session_unset();
            $_SESSION['user'] = [
                'id'     => $admin['ID'],
                'nombre' => $admin['nombre'],
                'rol'    => 'admin'
            ];
            header("Location: home.php");
            exit();
        }

        // ─────────────────────────────────────────────────────
        // 🔵 CLIENTE
        // Puede tener contraseña en dos formatos según cómo fue creado:
        //   - bcrypt  → registrado desde el formulario (password_hash)
        //   - SHA2    → insertado directamente en BD con SHA2() de MySQL
        // ─────────────────────────────────────────────────────
        $stmtCliente = $pdo->prepare("SELECT ID, Nombre, contraseña FROM CLIENTE WHERE email = :email");
        $stmtCliente->execute(['email' => $email]);
        $cliente = $stmtCliente->fetch();

        if ($cliente) {
            $hash       = $cliente['contraseña'];
            $bcrypt_ok  = password_verify($password, $hash);
            $sha256_ok  = (hash('sha256', $password) === $hash);

            if ($bcrypt_ok || $sha256_ok) {

            session_unset();

                // Si entró con SHA2, migrar la contraseña a bcrypt aprovechando el login
                if ($sha256_ok && !$bcrypt_ok) {
                    $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE CLIENTE SET contraseña = ? WHERE ID = ?")
                        ->execute([$nuevo_hash, $cliente['ID']]);
                }

                $_SESSION['user'] = [
                    'id'     => $cliente['ID'],
                    'nombre' => $cliente['Nombre'],
                    'rol'    => 'cliente'
                ];
                header("Location: home.php");
                exit();
            }
        }

        // ─── Credenciales incorrectas ─────────────────────────
        $_SESSION['mensaje_error'] = "Datos introducidos no válidos.";
        header('Location: index.php');
        exit();
    }
}
?>
