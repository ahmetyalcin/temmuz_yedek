<?php
include_once __DIR__ . '/../con/db.php';

$danisan_id = $_GET['danisan_id'] ?? 0;
$notlar = [];

if ($danisan_id) {
    global $pdo;
    $q = $pdo->prepare("SELECT r.notlar AS icerik, r.notu_giren_personel_id, r.randevu_tarihi AS not_tarihi, p.ad AS personel_ad, p.soyad AS personel_soyad 
                        FROM randevular r 
                        LEFT JOIN personel p ON r.personel_id = p.id
                        WHERE r.danisan_id = ? AND r.notlar IS NOT NULL AND TRIM(r.notlar) != ''
                        ORDER BY r.randevu_tarihi DESC");
    $q->execute([$danisan_id]);
    while($row = $q->fetch(PDO::FETCH_ASSOC)){
        // Ekleyen bulunamadıysa 'Sistem' olarak göster
        if (empty($row['personel_ad']) && empty($row['personel_soyad'])) {
            $row['personel_ad'] = 'Sistem';
            $row['personel_soyad'] = '';
        }
        $notlar[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $notlar
], JSON_UNESCAPED_UNICODE);
