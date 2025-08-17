<?php
// ajax/save_general_note.php
include_once '../con/db.php';
include_once '../partials/session.php';

header('Content-Type: application/json');

$danisan_id = $_POST['danisan_id'] ?? 0;
$icerik = $_POST['icerik'] ?? '';

if (!$danisan_id || !$icerik) {
    echo json_encode(['success' => false, 'message' => 'Danışan ID ve not içeriği gerekli']);
    exit;
}

// Get current user from session
$personel_id = $_SESSION['user_id'] ?? null;

if (!$personel_id) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit;
}

try {
    $sql = "INSERT INTO personel_notlari (danisan_id, personel_id, icerik, not_tarihi) 
            VALUES (?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$danisan_id, $personel_id, $icerik]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Not başarıyla kaydedildi'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Not kaydedilemedi'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>