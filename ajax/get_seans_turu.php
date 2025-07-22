<?php
require_once '../functions.php';

header('Content-Type: application/json');

$satis_id = $_GET['satis_id'] ?? null;

if (!$satis_id) {
    echo json_encode(['success' => false, 'message' => 'Satış ID gerekli']);
    exit;
}

try {
    $sql = "SELECT st.evaluation_interval 
            FROM satislar s
            JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            WHERE s.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$satis_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'evaluation_interval' => $result['evaluation_interval']
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
