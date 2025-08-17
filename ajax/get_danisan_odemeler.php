<?php
include_once '../con/db.php';

header('Content-Type: application/json');

$danisan_id = $_GET['danisan_id'] ?? 0;

if (!$danisan_id) {
    echo json_encode(['success' => false, 'message' => 'Danışan ID gerekli']);
    exit;
}

try {
    $sql = "SELECT o.*, 
                   o.tutar,
                   o.odeme_tarihi as vade_tarihi,
                   ot.ad as odeme_tipi,
                   CONCAT(p.ad, ' ', p.soyad) as personel_adi
            FROM odemeler o
            LEFT JOIN odeme_turleri ot ON ot.id = o.odeme_turu_id
            LEFT JOIN satislar s ON s.id = o.satis_id
            LEFT JOIN personel p ON p.id = s.personel_id
            WHERE s.danisan_id = ? AND o.aktif = 1
            ORDER BY o.odeme_tarihi DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$danisan_id]);
    $odemeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $odemeler
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>