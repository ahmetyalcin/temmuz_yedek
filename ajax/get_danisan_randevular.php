<?php
// ajax/get_danisan_randevular.php
include_once '../con/db.php';

header('Content-Type: application/json');

$danisan_id = $_GET['danisan_id'] ?? 0;

if (!$danisan_id) {
    echo json_encode(['success' => false, 'message' => 'Danışan ID gerekli']);
    exit;
}

try {
    $sql = "SELECT r.*, 
                   r.randevu_tarihi,
                   r.durum,
                   CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                   st.ad as seans_turu,
                   rm.name as room_name
            FROM randevular r
            LEFT JOIN personel p ON p.id = r.personel_id
            LEFT JOIN seans_turleri st ON st.id = r.seans_turu_id
            LEFT JOIN rooms rm ON rm.id = r.room_id
            WHERE r.danisan_id = ? AND r.aktif = 1
            ORDER BY r.randevu_tarihi DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$danisan_id]);
    $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $randevular
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>