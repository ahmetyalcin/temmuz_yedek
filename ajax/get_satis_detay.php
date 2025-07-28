<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID parametresi gerekli']);
    exit;
}

try {
    // Get sale details with sales type
    $sql = "SELECT s.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   st.ad as paket_adi,
                   st.evaluation_interval,
                   st.seans_adet,
                   CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                   satis_t.ad as satis_turu_adi,
                   odeme_t.ad as odeme_turu_adi,
                   (SELECT COALESCE(SUM(tutar), 0) FROM odemeler WHERE satis_id = s.id AND aktif = 1) as toplam_odenen
            FROM satislar s
            JOIN danisanlar d ON d.id = s.danisan_id
            JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            JOIN personel p ON p.id = s.personel_id
            LEFT JOIN satis_turleri satis_t ON satis_t.id = s.satis_turu_id
            LEFT JOIN odeme_turleri odeme_t ON odeme_t.id = s.odeme_turu_id
            WHERE s.id = :satis_id AND s.aktif = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $satis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$satis) {
        echo json_encode(['success' => false, 'message' => 'Satış bulunamadı']);
        exit;
    }
    
    // Get payments with payment type details
    $sql = "SELECT o.*, 
                   ot.ad as odeme_turu_adi,
                   ot.kod as odeme_turu_kodu,
                   ot.aciklama as odeme_turu_aciklama
            FROM odemeler o
            LEFT JOIN odeme_turleri ot ON o.odeme_turu_id = ot.id
            WHERE o.satis_id = :satis_id AND o.aktif = 1 
            ORDER BY o.odeme_tarihi DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['satis_id' => $_GET['id']]);
    $odemeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get installments with payment type details
    $sql = "SELECT t.*,
                   ot.ad as odeme_turu_adi,
                   ot.kod as odeme_turu_kodu,
                   ot.aciklama as odeme_turu_aciklama,
                   CASE 
                       WHEN t.odendi = 0 AND t.vade_tarihi < CURDATE() THEN 'gecikmiş'
                       WHEN t.odendi = 0 AND t.vade_tarihi >= CURDATE() THEN 'gelecek'
                       ELSE 'ödendi'
                   END as durum_tipi
            FROM taksitler t
            LEFT JOIN odeme_turleri ot ON t.odeme_turu_id = ot.id
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
                LEFT JOIN personel p ON p.id = r.personel_id
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
    
    // Get payment types for frontend
    $sql = "SELECT id, kod, ad, aciklama FROM odeme_turleri WHERE aktif = 1 ORDER BY ad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $odeme_turleri = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sales types for frontend
    $sql = "SELECT id, ad, aciklama FROM satis_turleri WHERE aktif = 1 ORDER BY ad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $satis_turleri = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'satis' => $satis,
        'odemeler' => $odemeler,
        'taksitler' => $taksitler,
        'randevular' => $randevular,
        'odeme_turleri' => $odeme_turleri,
        'satis_turleri' => $satis_turleri
    ]);
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>