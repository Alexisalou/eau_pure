<?php
$DATABASE_HOST = '10.0.14.4';
$DATABASE_NAME = 'eau_pure';
$DATABASE_USER = 'root';
$DATABASE_PASSWORD = 'ieufdl';
$DATABASE_PORT = '9999';

$conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME, $DATABASE_PORT);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

//ligne pour forcer l'encodage UTF-8
$conn->set_charset("utf8mb4");
?>
