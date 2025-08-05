<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $danisan_id = $_POST['danisan_id'] ?? null;
    $personel_id = $_POST['personel_id'] ?? null;
    $randevu_tarihi = $_POST['randevu_tarihi'] ?? null;
    $room_id = $_POST['room_id'] ?? null;
    $notlar = $_POST['notlar'] ?? null;
    $satis_id = $_POST['satis_id'] ?? null;
    $evaluation_notes = $_POST['evaluation_notes'] ?? null;

    // Zorunlu alanları kontrol et
    if (!$danisan_id || !$personel_id || !$randevu_tarihi || !$room_id) {
        echo json_encode(['success' => false, 'message' => 'Tüm zorunlu alanları doldurun']);
        exit;
    }

    // Get sale details if satis_id exists
    $is_gift = 0;
    $evaluation_type = null;
    
    if ($satis_id) {
        $stmt = $pdo->prepare("
            SELECT s.*, st.seans_adet,
                   (SELECT COUNT(*) FROM randevular 
                    WHERE satis_id = s.id AND aktif = 1) as used_sessions
            FROM satislar s
            JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            WHERE s.id = ?
        ");
        $stmt->execute([$satis_id]);
        $satis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($satis) {
            $regular_sessions = $satis['seans_adet'];
            $total_sessions = $regular_sessions + $satis['hediye_seans'];
            $used_sessions = $satis['used_sessions'];
            
            $is_gift = $used_sessions >= $regular_sessions ? 1 : 0;
        }
    }

    // Çakışan randevu kontrolü
    $check_sql = "SELECT COUNT(*) FROM randevular 
                  WHERE room_id = ? 
                  AND randevu_tarihi = ? 
                  AND aktif = 1";
    if ($id) {
        $check_sql .= " AND id != ?";
    }
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_params = [$room_id, $randevu_tarihi];
    if ($id) {
        $check_params[] = $id;
    }
    $check_stmt->execute($check_params);
    
    if ($check_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu oda ve saatte başka bir randevu bulunmakta']);
        exit;
    }

    if ($id) {
        // Güncelleme - seans_turu_id'yi değiştirmiyoruz
        $sql = "UPDATE randevular 
                SET danisan_id = ?,
                    personel_id = ?,
                    randevu_tarihi = ?,
                    room_id = ?,
                    notlar = ?,
                    satis_id = ?,
                    is_gift = ?,
                    evaluation_notes = ?,
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = ? AND aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $danisan_id, $personel_id,
            $randevu_tarihi, $room_id, $notlar,
            $satis_id, $is_gift, $evaluation_notes,
            $id
        ]);
    } else {
        // Yeni kayıt için seans_turu_id'yi satıştan al
        $seans_turu_id = null;
        if ($satis_id) {
            $stmt = $pdo->prepare("SELECT hizmet_paketi_id FROM satislar WHERE id = ?");
            $stmt->execute([$satis_id]);
            $seans_turu_id = $stmt->fetchColumn();
        }

        if (!$seans_turu_id) {
            echo json_encode(['success' => false, 'message' => 'Seans türü bulunamadı']);
            exit;
        }

        // Generate UUID for new appointment
        $new_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Yeni kayıt
        $sql = "INSERT INTO randevular (
                    id, danisan_id, personel_id, seans_turu_id,
                    randevu_tarihi, room_id, notlar,
                    satis_id, is_gift, evaluation_notes,
                    durum, aktif
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    'beklemede', 1
                )";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $new_id, $danisan_id, $personel_id, $seans_turu_id,
            $randevu_tarihi, $room_id, $notlar,
            $satis_id, $is_gift, $evaluation_notes
        ]);
    }

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Randevu başarıyla kaydedildi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Randevu kaydedilirken bir hata oluştu']);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>
