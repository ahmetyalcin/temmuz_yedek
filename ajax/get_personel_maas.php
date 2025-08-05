<?php
session_start();
require_once '../con/db.php';

header('Content-Type: application/json');
/*
// Giriş kontrolü
if (!isset($_SESSION['personel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim!']);
    exit;
}
*/
$personel_id = $_GET['personel_id'] ?? '';

if (empty($personel_id)) {
    echo json_encode(['success' => false, 'message' => 'Personel ID bulunamadı!']);
    exit;
}

try {
    // Personelin aktif maaş bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT 
            brut_maas, prim_yuzdesi, sabit_prim, yemek_yardimi,
            banka_adi, iban, baslangic_tarihi
        FROM personel_maas_bilgileri 
        WHERE personel_id = ? AND aktif = 1
        ORDER BY baslangic_tarihi DESC
        LIMIT 1
    ");
    
    $stmt->execute([$personel_id]);
    $maas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($maas) {
        echo json_encode([
            'success' => true,
            'maas' => $maas
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Maaş bilgisi bulunamadı'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>