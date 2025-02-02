<?php
$host = 'localhost';
$dbname = 'u922896325_agrupa';
$username = 'u922896325_agrupa';
$password = '3Kw@&dj15P[cF4f3^Dk';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>