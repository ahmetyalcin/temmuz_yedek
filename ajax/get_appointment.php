<?php
// ajax/get_appointment.php
include_once '../con/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID parametresi gerekli']);
    exit;
}

try {
    $sql = "SELECT r.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   st.ad as paket_adi,
                   s.id as satis_id,
                   s.hizmet_paketi_id as seans_turu_id
            FROM randevular r
            LEFT JOIN danisanlar d ON d.id = r.danisan_id
            LEFT JOIN satislar s ON s.id = r.satis_id
            LEFT JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            WHERE r.id = ? AND r.aktif = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($appointment) {
        echo json_encode([
            'success' => true,
            'data' => $appointment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Randevu bulunamadı'
        ]);
    }
} catch(PDOException $e) {
    error_log("Get appointment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası'
    ]);
}
?>