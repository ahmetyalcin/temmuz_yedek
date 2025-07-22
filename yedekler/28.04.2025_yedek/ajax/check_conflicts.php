<?php
include_once '../con/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['room_id']) || !isset($data['datetime'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE room_id = ? 
            AND randevu_tarihi = ? 
            AND aktif = 1";
    
    $params = [$data['room_id'], $data['datetime']];
    
    // If updating existing appointment, exclude it from the check
    if (isset($data['appointment_id'])) {
        $sql .= " AND id != ?";
        $params[] = $data['appointment_id'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $count = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'hasConflict' => $count > 0
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>