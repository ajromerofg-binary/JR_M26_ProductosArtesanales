<?php
//http://localhost/practicas/WebTienda/luz_de_hogar/BD/conexion.php

$host = 'localhost';
$db = 'luz_de_hogar';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
//Configuracion extra de PDO
$options = [
    PDO::ATTR_ERRMODE                => PDO::ERRMODE_EXCEPTION, //Activa errores detallados
    PDO::ATTR_DEFAULT_FETCH_MODE     => PDO::FETCH_ASSOC, //Devuelve los datos como array
    PDO::ATTR_EMULATE_PREPARES       => false, //Usa sentencias preparadas reales
];

try {
    //Creamos la variable $pdo que usaremos en el login
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión con la base de datos: ". $e->getMessage());
};

//echo "Conexion exitosa";

?>