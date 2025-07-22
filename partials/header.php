<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    // sayfa başlığı yoksa “My App”
    $title = isset($title) ? $title : 'My App';
    // artık same-folder içinde oldukları için doğrudan __DIR__
    include __DIR__ . '/title-meta.php';
    include __DIR__ . '/head-css.php';
    ?>
</head>
<body>
    <?php
    include __DIR__ . '/session.php';
    include __DIR__ . '/sidenav.php';
    include __DIR__ . '/topbar.php';
    ?>
