<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
    exit;
}

try {
    // POST parametrelerini al
    $appointment_id = $_POST['appointment_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $time = $_POST['time'] ?? null;

    // Zorunlu alanları kontrol et
    if (!$appointment_id || !$room_id || !$date || !$time) {
        echo json_encode([
            'success' => false, 
            'message' => 'Gerekli alanlar eksik',
            'debug' => [
                'appointment_id' => $appointment_id,
                'room_id' => $room_id, 
                'date' => $date,
                'time' => $time
            ]
        ]);
        exit;
    }

    // Tarih ve saati birleştir
    $randevu_tarihi = $date . ' ' . $time . ':00';

    // Önce randevunun var olduğunu kontrol et
    $check_appointment_sql = "SELECT id FROM randevular WHERE id = ? AND aktif = 1";
    $check_appointment_stmt = $pdo->prepare($check_appointment_sql);
    $check_appointment_stmt->execute([$appointment_id]);
    
    if (!$check_appointment_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Randevu bulunamadı']);
        exit;
    }

    // Hedef odanın kilitli olup olmadığını kontrol et
    $lock_check_sql = "SELECT COUNT(*) FROM oda_kilitli_saatler 
                       WHERE room_id = ? 
                       AND tarih = ? 
                       AND saat = ? 
                       AND aktif = 1";
    
    $lock_check_stmt = $pdo->prepare($lock_check_sql);
    $lock_check_stmt->execute([$room_id, $date, $time . ':00']);
    
    if ($lock_check_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Hedef oda bu saatte kilitli']);
        exit;
    }

    // Çakışan randevu kontrolü (aynı oda, aynı zaman, farklı randevu)
    $conflict_check_sql = "SELECT COUNT(*) FROM randevular 
                          WHERE room_id = ? 
                          AND randevu_tarihi = ? 
                          AND aktif = 1 
                          AND id != ?";
    
    $conflict_check_stmt = $pdo->prepare($conflict_check_sql);
    $conflict_check_stmt->execute([$room_id, $randevu_tarihi, $appointment_id]);
    
    if ($conflict_check_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu oda ve saatte başka bir randevu bulunmakta']);
        exit;
    }

    // Randevuyu güncelle
    $update_sql = "UPDATE randevular 
                   SET room_id = ?, 
                       randevu_tarihi = ?,
                       guncelleme_tarihi = CURRENT_TIMESTAMP
                   WHERE id = ? AND aktif = 1";
    
    $update_stmt = $pdo->prepare($update_sql);
    $success = $update_stmt->execute([$room_id, $randevu_tarihi, $appointment_id]);

    if ($success && $update_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Randevu başarıyla taşındı',
            'data' => [
                'appointment_id' => $appointment_id,
                'new_room_id' => $room_id,
                'new_datetime' => $randevu_tarihi
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Randevu güncellenemedi']);
    }

} catch(PDOException $e) {
    error_log("DRAG_UPDATE ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("GENERAL DRAG_UPDATE ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Genel hata: ' . $e->getMessage()
    ]);
}
?>