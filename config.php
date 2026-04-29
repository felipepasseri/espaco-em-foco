<?php
// config.php - RAIZ do projeto
define('DB_HOST', 'localhost');
define('DB_NAME', 'guil4713_espacoemfoco');
define('DB_USER', 'guil4713_espaco');
define('DB_PASS', 'l!ajONJU7BGc');

function getDB()
{
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
