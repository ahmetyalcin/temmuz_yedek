<?php
// add-danisan-not.php
header('Content-Type: application/json');
include_once __DIR__ . '/../con/db.php';


// Giriş kontrolü gerekirse ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $danisan_id = (int)($_POST['danisan_id'] ?? 0);
    $not_tarihi = trim($_POST['not_tarihi'] ?? '');
    $icerik = trim($_POST['icerik'] ?? '');

    // Oturumdan personel_id al (giriş sistemine göre değiştir)
    session_start();
    $personel_id = $_SESSION['personel_id'] ?? 1; // Varsayılan: 1

    if ($danisan_id && $not_tarihi && $icerik) {
        global $pdo; // functions.php'den gelmeli
        $stmt = $pdo->prepare("INSERT INTO personel_notlari (personel_id, not_tarihi, icerik, danisan_id) VALUES (?, ?, ?, ?)");
        $ok = $stmt->execute([$personel_id, $not_tarihi, $icerik, $danisan_id]);
        if ($ok) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Alanlar eksik!']);
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
