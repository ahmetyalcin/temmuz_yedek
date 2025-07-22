<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID parametresi gerekli']);
    exit;
}

try {
    // Get sale details
    $sql = "SELECT s.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   st.ad as paket_adi,
                   CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                   (SELECT COALESCE(SUM(tutar), 0) FROM odemeler WHERE satis_id = s.id AND aktif = 1) as toplam_odenen
            FROM satislar s
            JOIN danisanlar d ON d.id = s.danisan_id
            JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            JOIN personel p ON p.id = s.personel_id
            WHERE s.id = :satis_id AND s.aktif = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $satis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$satis) {
        echo json_encode(['success' => false, 'message' => 'Satış bulunamadı']);
        exit;
    }
    
    // Get payments
    $sql = "SELECT * FROM odemeler 
            WHERE satis_id = :satis_id AND aktif = 1 
            ORDER BY odeme_tarihi DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $odemeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get appointments with room information and evaluation types
    $sql = "SELECT r.*, 
                   CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                   rm.name as room_name,
                   CASE 
                       WHEN r.evaluation_number = 1 THEN 'initial'
                       WHEN r.evaluation_number = (
                           SELECT COUNT(*) 
                           FROM randevular 
                           WHERE satis_id = r.satis_id 
                           AND aktif = 1 
                           AND evaluation_number IS NOT NULL
                       ) THEN 'final'
                       WHEN r.evaluation_number > 0 THEN 'progress'
                       ELSE NULL 
                   END as evaluation_type
            FROM randevular r
            JOIN personel p ON p.id = r.personel_id
            LEFT JOIN rooms rm ON rm.id = r.room_id
            WHERE r.satis_id = :satis_id AND r.aktif = 1
            ORDER BY r.randevu_tarihi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'satis' => $satis,
        'odemeler' => $odemeler,
        'randevular' => $randevular
    ]);
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>
