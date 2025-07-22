<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if (!isset($_GET['danisan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Danışan ID gerekli']);
    exit;
}

try {
    // Get the latest active sale for this client
    $sql = "SELECT s.*, st.seans_adet,
                   (SELECT COUNT(*) FROM randevular 
                    WHERE satis_id = s.id AND aktif = 1) as used_sessions
            FROM satislar s
            JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            WHERE s.danisan_id = ? 
            AND s.aktif = 1
            ORDER BY s.olusturma_tarihi DESC
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['danisan_id']]);
    $satis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($satis) {
        echo json_encode([
            'success' => true,
            'satis_id' => $satis['id'],
            'seans_turu_id' => $satis['hizmet_paketi_id'],
            'seans_adet' => $satis['seans_adet'],
            'hediye_seans' => $satis['hediye_seans'],
            'kullanilan_seans' => $satis['used_sessions']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aktif satış bulunamadı'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>
