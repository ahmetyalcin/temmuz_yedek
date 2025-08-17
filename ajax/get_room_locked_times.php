<?php
include_once '../con/db.php';

header('Content-Type: application/json');

if (!isset($_GET['room_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Oda ID ve tarih gerekli']);
    exit;
}

$room_id = $_GET['room_id'];
$date = $_GET['date'];

try {
    // Get locked times for specific room and date
    $sql = "SELECT saat, kilit_turu, aciklama 
            FROM room_time_locks 
            WHERE room_id = ? AND tarih = ? AND aktif = 1
            ORDER BY saat";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $date]);
    $locked_times = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format times to HH:MM format
    $formatted_times = [];
    foreach ($locked_times as $lock) {
        $formatted_times[] = [
            'time' => date('H:i', strtotime($lock['saat'])),
            'type' => $lock['kilit_turu'],
            'description' => $lock['aciklama']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'locked_times' => $formatted_times
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>