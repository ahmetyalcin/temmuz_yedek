<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
    exit;
}

try {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $new_room_id = $_POST['room_id'] ?? null;
    $new_time = $_POST['time'] ?? null;
    $date = $_POST['date'] ?? null;

    if (!$appointment_id || !$new_room_id || !$new_time || !$date) {
        echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
        exit;
    }

    // Yeni randevu tarih ve saatini oluştur
    $new_datetime = date('Y-m-d H:i:s', strtotime($date . ' ' . $new_time));

    // Çakışma kontrolü
    $check_sql = "SELECT COUNT(*) FROM randevular 
                  WHERE room_id = ? 
                  AND randevu_tarihi = ? 
                  AND id != ? 
                  AND aktif = 1";
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$new_room_id, $new_datetime, $appointment_id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu oda ve saatte başka bir randevu bulunmakta']);
        exit;
    }

    // Randevuyu güncelle
    $sql = "UPDATE randevular 
            SET room_id = ?,
                randevu_tarihi = ?,
                guncelleme_tarihi = CURRENT_TIMESTAMP
            WHERE id = ? AND aktif = 1";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$new_room_id, $new_datetime, $appointment_id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Randevu başarıyla güncellendi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Randevu güncellenirken bir hata oluştu']);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>