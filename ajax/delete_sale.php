<?php
session_start();
require_once '../functions.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz ID']);
    exit;
}

try {
    global $pdo;
    $pdo->beginTransaction();

    // İlgili taksit, ödeme ve satış kayıtlarını silin:
    // (Implementasyonunuza göre fonksiyon veya SQL kullanın)
    deleteInstallmentsBySale($id);
    deletePaymentsBySale($id);
    deleteSaleById($id);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
