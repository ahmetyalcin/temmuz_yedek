

<?php
session_start();

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kullanıcı bilgilerini global değişkenlere ata
$user_id = $_SESSION['user_id'];
$ad_soyad = $_SESSION['ad_soyad'];
$rol = $_SESSION['rol'];
$avatar = $_SESSION['avatar'] ?? 'assets/img/default-avatar.png';
?>
