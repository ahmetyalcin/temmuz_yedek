<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['appointment_id'])) {
        throw new Exception('Appointment ID is required');
    }
    
    // Soft delete the appointment
    $sql = "UPDATE randevular 
            SET aktif = 0,
                guncelleme_tarihi = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$data['appointment_id']]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Randevu başarıyla silindi'
        ]);
    } else {
        throw new Exception('Randevu silinirken bir hata oluştu');
    }
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
