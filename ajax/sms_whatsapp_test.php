<?php
// ajax/sms_whatsapp_test.php - API bağlantısı test etme
session_start();
require_once '../con/db.php';
require_once '../sms_whatsapp_entegrasyon.php';
require_once '../config/sms_whatsapp_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tip = $input['tip'] ?? ''; // 'sms' veya 'whatsapp'

try {
    $config = getSMSWhatsAppConfig();
    $sms_whatsapp = new SMSWhatsAppEntegrasyon($pdo, $config);
    
    // Test numarası (kendi numaranız olmalı)
    $test_telefon = '5xxxxxxxxx'; // Gerçek test numaranızı girin
    $test_mesaj = "Bu bir API test mesajıdır. PhysioVita - " . date('Y-m-d H:i:s');
    
    if ($tip === 'sms') {
        // SMS API test
        if (empty($config['sms_username']) || empty($config['sms_password'])) {
            throw new Exception('SMS API bilgileri eksik');
        }
        
        $sonuc = $sms_whatsapp->mesajGonder($test_telefon, $test_mesaj, 'sms');
        echo json_encode([
            'success' => true,
            'message' => 'SMS API test başarılı',
            'detay' => $sonuc
        ]);
        
    } elseif ($tip === 'whatsapp') {
        // WhatsApp API test
        if (empty($config['whatsapp_account_sid']) || empty($config['whatsapp_auth_token'])) {
            throw new Exception('WhatsApp API bilgileri eksik');
        }
        
        $sonuc = $sms_whatsapp->mesajGonder($test_telefon, $test_mesaj, 'whatsapp');
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp API test başarılı',
            'detay' => $sonuc
        ]);
        
    } else {
        throw new Exception('Geçersiz test tipi');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Test başarısız: ' . $e->getMessage()
    ]);
}
?>
