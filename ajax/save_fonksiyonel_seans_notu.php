<?php
include_once '../con/db.php';
session_start();
header('Content-Type: application/json');

$danisan_id = $_POST['danisan_id'] ?? '';
$olcum_no   = $_POST['olcum_no'] ?? '';
$icerik     = $_POST['icerik'] ?? '';
$personel_id = $_SESSION['personel_id'] ?? 0;

if (!$danisan_id || !$olcum_no) {
    echo json_encode(['success'=>false, 'message'=>'Eksik parametre']);
    exit;
}

try {
    $sql = "INSERT INTO fonksiyonel_seans_notlari 
        (danisan_id, olcum_no, icerik, personel_id, not_tarihi)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
          icerik = VALUES(icerik), 
          personel_id = VALUES(personel_id), 
          not_tarihi = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$danisan_id, $olcum_no, $icerik, $personel_id]);
    echo json_encode(['success'=>true]);
} catch(PDOException $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
