<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID parametresi gerekli']);
    exit;
}

try {
    $sql = "SELECT r.*, 
                   d.ad as danisan_ad, d.soyad as danisan_soyad,
                   p.ad as personel_ad, p.soyad as personel_soyad,
                   st.ad as seans_turu_ad
            FROM randevular r
            JOIN danisanlar d ON d.id = r.danisan_id
            JOIN personel p ON p.id = r.personel_id
            JOIN seans_turleri st ON st.id = r.seans_turu_id
            WHERE r.id = ? AND r.aktif = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $randevu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($randevu) {
        echo json_encode([
            'success' => true,
            'data' => $randevu
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Randevu bulunamadı'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>