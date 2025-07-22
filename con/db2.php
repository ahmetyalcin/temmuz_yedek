<?php


$host = "localhost";
$dbname = "u1989180_theravita";
$username = "u1989180_usertheravita";
$password = "=f?W-@^sc4iE";


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
