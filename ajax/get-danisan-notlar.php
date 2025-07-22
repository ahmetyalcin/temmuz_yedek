<?php
// ajax/get-danisan-notlar.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// DB bağlantısı
include_once __DIR__ . '/../con/db.php';

if (! isset($_GET['danisan_id']) || ! is_numeric($_GET['danisan_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Eksik veya geçersiz danisan_id'
    ]);
    exit;
}

$danisanId = (int) $_GET['danisan_id'];

try {
    // personel_notlari ile personel tablosunu join ediyoruz
    $stmt = $pdo->prepare("
        SELECT 
          pn.id, 
          pn.not_tarihi, 
          pn.icerik, 
          pn.personel_id,
          p.ad   AS personel_ad,
          p.soyad AS personel_soyad
        FROM personel_notlari pn
        LEFT JOIN personel p 
          ON p.id = pn.personel_id
        WHERE pn.danisan_id = ?
        ORDER BY pn.not_tarihi DESC
    ");
    $stmt->execute([$danisanId]);
    $notlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => $notlar
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
    exit;
}
