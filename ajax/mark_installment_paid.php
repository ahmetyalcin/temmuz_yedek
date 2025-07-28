<?php
include_once '../con/db.php';
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $taksit_id = $_POST['taksit_id'];
    $odeme_tarihi = $_POST['odeme_tarihi'];
    $odeme_turu_id = $_POST['odeme_turu_id']; // YENİ: odeme_tipi yerine odeme_turu_id
    
    // Validation
    if (!$taksit_id || !$odeme_tarihi || !$odeme_turu_id) {
        throw new Exception('Eksik parametreler');
    }
    
    // Get installment details
    $sql = "SELECT satis_id, tutar FROM taksitler WHERE id = ? AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taksit_id]);
    $taksit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$taksit) {
        throw new Exception('Taksit bulunamadı');
    }
    
    // Mark installment as paid - sadece odeme_turu_id kullan
    $sql = "UPDATE taksitler 
            SET odendi = 1,
                odeme_tarihi = ?,
                odeme_turu_id = ?,
                guncelleme_tarihi = CURRENT_TIMESTAMP
            WHERE id = ? AND aktif = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$odeme_tarihi, $odeme_turu_id, $taksit_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Taksit güncellenemedi');
    }
    
    // Add payment record - sadece odeme_turu_id kullan
    $odeme_id = generateUUID();
    $sql = "INSERT INTO odemeler (
                id, satis_id, tutar, odeme_turu_id, odeme_tarihi,
                notlar, aktif, olusturma_tarihi
            ) VALUES (
                ?, ?, ?, ?, ?,
                'Taksit ödemesi', 1, NOW()
            )";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $odeme_id,
        $taksit['satis_id'],
        $taksit['tutar'],
        $odeme_turu_id,
        $odeme_tarihi
    ]);
    
    // Update sale status
    $sql = "UPDATE satislar 
            SET durum = CASE 
                WHEN (
                    SELECT COUNT(*) 
                    FROM taksitler 
                    WHERE satis_id = ? 
                    AND aktif = 1 
                    AND odendi = 0
                ) = 0 THEN 'odendi'
                ELSE 'beklemede'
            END,
            guncelleme_tarihi = CURRENT_TIMESTAMP
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taksit['satis_id'], $taksit['satis_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ödeme başarıyla kaydedildi'
    ]);
    
} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Taksit ödeme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>