<?php
// ajax/check_conflicts.php
include_once '../con/db.php';

header('Content-Type: application/json');

// JSON verisini al
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Parametreleri kontrol et
if (!isset($data['room_id']) || !isset($data['datetime'])) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    // 1. ODA ÇAKIŞMASI KONTROLÜ
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE room_id = ? 
            AND randevu_tarihi = ? 
            AND aktif = 1";
    
    $params = [$data['room_id'], $data['datetime']];
    
    // Eğer mevcut bir randevu güncelleniyorsa, kendisini hariç tut
    if (isset($data['appointment_id']) && !empty($data['appointment_id'])) {
        $sql .= " AND id != ?";
        $params[] = $data['appointment_id'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $roomConflict = $stmt->fetchColumn() > 0;
    
    // 2. DANIŞAN ÇAKIŞMASI KONTROLÜ
    $danisanConflict = false;
    $conflictMessage = '';
    
    if (isset($data['danisan_id']) && !empty($data['danisan_id'])) {
        // Aynı danışanın aynı saatte başka randevusu var mı?
        $sql = "SELECT r.*, rm.name as room_name 
                FROM randevular r
                LEFT JOIN rooms rm ON rm.id = r.room_id
                WHERE r.danisan_id = ? 
                AND r.randevu_tarihi = ? 
                AND r.aktif = 1";
        
        $params = [$data['danisan_id'], $data['datetime']];
        
        // Eğer mevcut bir randevu güncelleniyorsa, kendisini hariç tut
        if (isset($data['appointment_id']) && !empty($data['appointment_id'])) {
            $sql .= " AND r.id != ?";
            $params[] = $data['appointment_id'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $conflictingAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conflictingAppointment) {
            $danisanConflict = true;
            $conflictMessage = "Bu danışanın aynı saatte " . 
                              $conflictingAppointment['room_name'] . 
                              " odasında randevusu bulunmaktadır!";
        }
    }
    
    // Sonucu döndür
    echo json_encode([
        'success' => true,
        'hasConflict' => $roomConflict || $danisanConflict,
        'roomConflict' => $roomConflict,
        'danisanConflict' => $danisanConflict,
        'message' => $conflictMessage
    ]);
    
} catch(PDOException $e) {
    // Hata durumunda
    error_log("Check conflicts error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
}
?>