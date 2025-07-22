<?php
require_once 'db.php'; // yukarıda verdiğin dosya adı buysa

header('Content-Type: application/json');

try {
    // Gelen JSON verisini al
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['randevu_id']) || empty($data['randevu_id'])) {
        echo json_encode(['success' => false, 'message' => 'Randevu ID eksik']);
        exit;
    }

    $randevuId = $data['randevu_id'];

    // Veritabanı bağlantısı: $pdo
    $stmt = $pdo->prepare("UPDATE randevular SET aktif = 0 WHERE id = ?");
    $success = $stmt->execute([$randevuId]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
}
