<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['personel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum süresi dolmuş!']);
    exit;
}
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek!']);
    exit;
}
$not_id = (int)$_POST['id'];
$personel_id = (int)$_SESSION['personel_id'];

include_once __DIR__ . '/../con/db.php';

// Notun sahibini kontrol et
$stmt = $pdo->prepare("SELECT personel_id FROM personel_notlari WHERE id = ?");
$stmt->execute([$not_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Not bulunamadı!']);
    exit;
}
if ((int)$row['personel_id'] !== $personel_id) {
    echo json_encode(['success' => false, 'message' => 'Sadece kendi notunuzu silebilirsiniz!']);
    exit;
}

// Silme işlemi
$stmt = $pdo->prepare("DELETE FROM personel_notlari WHERE id = ?");
$stmt->execute([$not_id]);

echo json_encode(['success' => true]);
exit;
?>
