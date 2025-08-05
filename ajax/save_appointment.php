<?php
/**
 * RANDEVU KAYDETME - GELİŞTİRİLMİŞ KONTROLLER İLE
 * ajax/save_appointment.php
 */

session_start();
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenir']);
    exit;
}

try {
    $action = $_POST['ajax_action'] ?? '';
    $appointment_id = $_POST['id'] ?? null;
    $danisan_id = $_POST['danisan_id'] ?? null;
    $personel_id = $_POST['personel_id'] ?? null;
    $seans_turu_id = $_POST['seans_turu_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null;
    $randevu_tarihi = $_POST['randevu_tarihi'] ?? null;
    $notlar = $_POST['notlar'] ?? '';
    $evaluation_notes = $_POST['evaluation_notes'] ?? '';

    // Zorunlu alanları kontrol et
    if (empty($danisan_id) || empty($personel_id) || empty($seans_turu_id) || 
        empty($room_id) || empty($randevu_tarihi)) {
        throw new Exception('Tüm zorunlu alanları doldurunuz');
    }

    if ($action === 'randevu_ekle') {
        // Geliştirilmiş randevu ekleme fonksiyonunu kullan
        $result = randevuEkleGelismis(
            $danisan_id, $personel_id, $seans_turu_id, 
            $room_id, $randevu_tarihi, $notlar
        );
        
        echo json_encode($result);
        
    } elseif ($action === 'randevu_guncelle') {
        
        if (empty($appointment_id)) {
            throw new Exception('Randevu ID gerekli');
        }

        // Geliştirilmiş randevu güncelleme fonksiyonunu kullan
        $result = randevuGuncelleGelismis(
            $appointment_id, $danisan_id, $personel_id, $seans_turu_id,
            $room_id, $randevu_tarihi, $notlar, $evaluation_notes
        );
        
        echo json_encode($result);
        
    } else {
        throw new Exception('Geçersiz işlem');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Randevu kaydetme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
}
?>