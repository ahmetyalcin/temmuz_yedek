<?php
// ajax/mesaj_sablon_getir.php - Mesaj şablonunu getir
session_start();
require_once '../con/db.php';

header('Content-Type: application/json');

$kod = $_GET['kod'] ?? '';

if (!$kod) {
    echo json_encode(['success' => false, 'message' => 'Şablon kodu gerekli']);
    exit;
}

try {
    $sql = "SELECT * FROM mesaj_sablonlari WHERE kod = ? AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kod]);
    $sablon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sablon) {
        echo json_encode([
            'success' => true,
            'sablon' => $sablon
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Şablon bulunamadı'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>