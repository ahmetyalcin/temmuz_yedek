<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

// DB bağlantısı
include_once __DIR__ . '/../con/db.php';

$personel_id = $_SESSION['personel_id'] ?? 0;
$danisan_id = $_POST['danisan_id'] ?? '';
$satis_id = $_POST['satis_id'] ?? '';
$seans_no = $_POST['seans_no'] ?? 0;
$icerik = $_POST['icerik'] ?? '';

if(!$personel_id || !$danisan_id || !$satis_id || !$seans_no || !$icerik){
  die(json_encode(['success'=>false, 'message'=>'Eksik alan!']));
}
$q = db()->prepare("INSERT INTO fonksiyonel_seans_notlari (danisan_id, satis_id, seans_no, personel_id, icerik, not_tarihi) VALUES (?,?,?,?,?,NOW())");
$res = $q->execute([$danisan_id, $satis_id, $seans_no, $personel_id, $icerik]);
echo json_encode(['success'=>$res]);
