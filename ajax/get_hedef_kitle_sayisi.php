<?php
// ajax/get_hedef_kitle_sayisi.php - Hedef kitle sayısını getir
session_start();
require_once '../con/db.php';

header('Content-Type: application/json');

$grup = $_GET['grup'] ?? '';

try {
    switch ($grup) {
        case 'tum_musteriler':
            $sql = "SELECT COUNT(*) FROM danisanlar WHERE aktif = 1 AND telefon IS NOT NULL AND telefon != ''";
            break;
        case 'bugun_randevu':
            $sql = "SELECT COUNT(DISTINCT d.id) FROM danisanlar d 
                    JOIN randevular r ON d.id = r.danisan_id 
                    WHERE DATE(r.randevu_tarihi) = CURDATE() 
                    AND r.aktif = 1 AND d.telefon IS NOT NULL";
            break;
        case 'yarin_randevu':
            $sql = "SELECT COUNT(DISTINCT d.id) FROM danisanlar d 
                    JOIN randevular r ON d.id = r.danisan_id 
                    WHERE DATE(r.randevu_tarihi) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) 
                    AND r.aktif = 1 AND d.telefon IS NOT NULL";
            break;
        case 'vip_musteriler':
            $sql = "SELECT COUNT(*) FROM danisanlar 
                    WHERE sadakat_seviyesi >= 4 AND aktif = 1 
                    AND telefon IS NOT NULL AND telefon != ''";
            break;
        default:
            echo json_encode(['sayi' => 0]);
            exit;
    }
    
    $stmt = $pdo->query($sql);
    $sayi = $stmt->fetchColumn();
    
    echo json_encode(['sayi' => (int)$sayi]);
    
} catch (Exception $e) {
    echo json_encode(['sayi' => 0, 'error' => $e->getMessage()]);
}
?>

<?php
// config/sms_whatsapp_config.php - Konfigürasyon dosyası
require_once __DIR__ . '/../con/db.php';

function getSMSWhatsAppConfig() {
    global $pdo;
    
    $sql = "SELECT anahtar, deger FROM sistem_ayarlari WHERE kategori = 'sms_whatsapp'";
    $stmt = $pdo->query($sql);
    $ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return [
        'sms_api_url' => $ayarlar['sms_api_url'] ?? 'https://api.iletimerkezi.com/v1/send-sms/get/',
        'sms_username' => $ayarlar['sms_username'] ?? '',
        'sms_password' => $ayarlar['sms_password'] ?? '',
        'sms_sender' => $ayarlar['sms_sender'] ?? 'PhysioVita',
        'whatsapp_api_url' => 'https://api.twilio.com/2010-04-01/Accounts/{account_sid}/Messages.json',
        'whatsapp_account_sid' => $ayarlar['whatsapp_account_sid'] ?? '',
        'whatsapp_auth_token' => $ayarlar['whatsapp_auth_token'] ?? '',
        'whatsapp_from' => $ayarlar['whatsapp_from'] ?? 'whatsapp:+14155238886',
        'default_country_code' => $ayarlar['default_country_code'] ?? '+90',
        'max_retry_count' => 3,
        'rate_limit_per_minute' => (int)($ayarlar['rate_limit_per_minute'] ?? 60)
    ];
}
?>