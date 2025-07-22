<?php
include_once '../con/db.php';
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['satis_id'])) {
        throw new Exception('Missing required parameters');
    }
    
    // Get sale details and check remaining sessions
    $stmt = $pdo->prepare("
        SELECT s.*, st.seans_adet,
               (SELECT COUNT(*) FROM randevular 
                WHERE satis_id = s.id AND aktif = 1) as used_sessions
        FROM satislar s
        JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
        WHERE s.id = ?
    ");
    $stmt->execute([$data['satis_id']]);
    $satis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$satis) {
        throw new Exception('Sale not found');
    }
    
    $total_sessions = $satis['seans_adet'] + $satis['hediye_seans'];
    
    // Get existing appointments
    $stmt = $pdo->prepare("
        SELECT r.*, 
               CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
               rm.name as room_name
        FROM randevular r
        LEFT JOIN personel p ON p.id = r.personel_id
        LEFT JOIN rooms rm ON rm.id = r.room_id
        WHERE r.satis_id = ? AND r.aktif = 1
        ORDER BY r.randevu_tarihi ASC
    ");
    $stmt->execute([$data['satis_id']]);
    $existing_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no appointments provided, return existing appointments
    if (empty($data['appointments'])) {
        echo json_encode([
            'success' => true,
            'existing_appointments' => $existing_appointments
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Create a map of existing appointments by datetime for easy lookup
    $existing_map = [];
    foreach ($existing_appointments as $apt) {
        $existing_map[$apt['randevu_tarihi']] = $apt;
    }
    
    // Create a map of new appointments by datetime
    $new_map = [];
    foreach ($data['appointments'] as $apt) {
        $new_map[$apt['datetime']] = $apt;
    }
    
    // Find appointments to delete (exist in old but not in new)
    $to_delete = array_diff_key($existing_map, $new_map);
    
    // Find appointments to add (exist in new but not in old)
    $to_add = array_diff_key($new_map, $existing_map);
    
    // Find appointments to update (exist in both but might have changed)
    $to_update = array_intersect_key($new_map, $existing_map);
    
    // Delete removed appointments
    if (!empty($to_delete)) {
        $stmt = $pdo->prepare("
            UPDATE randevular 
            SET aktif = 0,
                guncelleme_tarihi = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        foreach ($to_delete as $apt) {
            $stmt->execute([$apt['id']]);
        }
    }
    
    // Add new appointments
    if (!empty($to_add)) {
        $stmt = $pdo->prepare("
            INSERT INTO randevular (
                id, danisan_id, personel_id, seans_turu_id, randevu_tarihi,
                satis_id, scheduled, color, durum, room_id, aktif, is_gift,
                evaluation_type
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 1, '#FFE4E1', 'beklemede', ?, 1, ?, ?
            )
        ");
        
        $regular_sessions = $satis['seans_adet'];
        $gift_sessions = $satis['hediye_seans'];
        $appointment_count = count($existing_appointments);
        
        foreach ($to_add as $datetime => $apt) {
            // Check for room conflicts if room is selected
            if (!empty($apt['room_id'])) {
                $conflict_check = $pdo->prepare("
                    SELECT COUNT(*) FROM randevular 
                    WHERE room_id = ? 
                    AND randevu_tarihi = ? 
                    AND aktif = 1
                ");
                $conflict_check->execute([$apt['room_id'], $datetime]);
                
                if ($conflict_check->fetchColumn() > 0) {
                    throw new Exception('Room is already booked for this time');
                }
            }
            
            $id = generateUUID();
            
            // Determine if this is a gift session and evaluation type
            $is_gift = $appointment_count >= $regular_sessions ? 1 : 0;
            $evaluation_type = null;
            
            if ($is_gift) {
                if ($appointment_count == $regular_sessions) {
                    $evaluation_type = 'initial';
                } elseif ($appointment_count == ($regular_sessions + $gift_sessions - 1)) {
                    $evaluation_type = 'final';
                }
            }
            
            $stmt->execute([
                $id,
                $satis['danisan_id'],
                $apt['personel_id'] ?? null,
                $satis['hizmet_paketi_id'],
                $datetime,
                $data['satis_id'],
                $apt['room_id'] ?? null,
                $is_gift,
                $evaluation_type
            ]);
            
            $appointment_count++;
        }
    }
    
    // Update existing appointments
    if (!empty($to_update)) {
        $stmt = $pdo->prepare("
            UPDATE randevular 
            SET personel_id = ?,
                room_id = ?,
                guncelleme_tarihi = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        foreach ($to_update as $datetime => $apt) {
            $existing = $existing_map[$datetime];
            
            // Only update if something changed
            if ($apt['personel_id'] !== $existing['personel_id'] || 
                $apt['room_id'] !== $existing['room_id']) {
                
                // Check for room conflicts if room is selected
                if (!empty($apt['room_id']) && $apt['room_id'] !== $existing['room_id']) {
                    $conflict_check = $pdo->prepare("
                        SELECT COUNT(*) FROM randevular 
                        WHERE room_id = ? 
                        AND randevu_tarihi = ? 
                        AND id != ?
                        AND aktif = 1
                    ");
                    $conflict_check->execute([$apt['room_id'], $datetime, $existing['id']]);
                    
                    if ($conflict_check->fetchColumn() > 0) {
                        throw new Exception('Room is already booked for this time');
                    }
                }
                
                $stmt->execute([
                    $apt['personel_id'] ?? null,
                    $apt['room_id'] ?? null,
                    $existing['id']
                ]);
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Randevular başarıyla güncellendi'
    ]);
    
} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>