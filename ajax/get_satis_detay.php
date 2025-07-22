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
                   st.evaluation_interval,
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
    
    // Get installments
    $sql = "SELECT t.*,
                   CASE 
                       WHEN odendi = 0 AND vade_tarihi < CURDATE() THEN 'gecikmiş'
                       WHEN odendi = 0 AND vade_tarihi >= CURDATE() THEN 'gelecek'
                       ELSE 'ödendi'
                   END as durum_tipi
            FROM taksitler t
            WHERE t.satis_id = :satis_id 
            AND t.aktif = 1 
            ORDER BY t.vade_tarihi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $taksitler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get appointments with evaluation based on session type interval
    $sql = "WITH RankedAppointments AS (
                SELECT r.*, 
                       CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                       rm.name as room_name,
                       st.evaluation_interval,
                       ROW_NUMBER() OVER (
                           PARTITION BY r.satis_id 
                           ORDER BY r.randevu_tarihi ASC
                       ) as session_number
                FROM randevular r
                JOIN personel p ON p.id = r.personel_id
                LEFT JOIN rooms rm ON rm.id = r.room_id
                JOIN satislar s ON s.id = r.satis_id
                JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
                WHERE r.satis_id = :satis_id AND r.aktif = 1
            )
            SELECT *,
                   CASE 
                       WHEN evaluation_interval IS NULL OR evaluation_interval = 0 THEN NULL
                       WHEN session_number = 1 THEN 'initial'
                       WHEN session_number = (
                           SELECT COUNT(*) 
                           FROM randevular 
                           WHERE satis_id = :satis_id AND aktif = 1
                       ) THEN 'final'
                       WHEN evaluation_interval > 0 AND 
                            session_number % evaluation_interval = 0 THEN 'progress'
                       ELSE NULL 
                   END as evaluation_type,
                   CASE 
                       WHEN evaluation_interval IS NULL OR evaluation_interval = 0 THEN NULL
                       WHEN session_number = 1 THEN 1
                       WHEN evaluation_interval > 0 AND 
                            session_number % evaluation_interval = 0 
                            THEN session_number / evaluation_interval
                       ELSE NULL 
                   END as evaluation_number
            FROM RankedAppointments
            ORDER BY randevu_tarihi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'satis' => $satis,
        'odemeler' => $odemeler,
        'taksitler' => $taksitler,
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
