<?php
// ajax/bordro_onayla.php
session_start();
require_once '../functions.php';
require_once '../con/db.php';

header('Content-Type: application/json');

// Yetki kontrolü - sadece muhasebe ve yönetici
if (!in_array($_SESSION['rol'], ['muhasebe', 'yonetici'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$bordro_id = $_POST['bordro_id'] ?? '';
$action = $_POST['action'] ?? '';

if (empty($bordro_id) || $action !== 'onayla') {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    // Bordro durumunu kontrol et
    $check_sql = "SELECT durum, personel_id FROM bordrolar WHERE id = ? AND aktif = 1";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$bordro_id]);
    $bordro = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bordro) {
        echo json_encode(['success' => false, 'message' => 'Bordro bulunamadı']);
        exit;
    }
    
    if ($bordro['durum'] !== 'taslak') {
        echo json_encode(['success' => false, 'message' => 'Bu bordro zaten onaylanmış']);
        exit;
    }
    
    // Bordroyu onayla
    $sql = "UPDATE bordrolar 
            SET durum = 'onaylandi', 
                onaylayan_id = ?, 
                onay_tarihi = NOW() 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$_SESSION['user_id'], $bordro_id]);
    
    if ($success && $stmt->rowCount() > 0) {
        // Loglama
        $log_sql = "INSERT INTO sistem_loglari (kullanici_id, islem, aciklama, ip_adresi) 
                    VALUES (?, 'bordro_onay', ?, ?)";
        try {
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $_SESSION['user_id'],
                "Bordro #{$bordro_id} onaylandı",
                $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor'
            ]);
        } catch(PDOException $e) {
            // Log hatası kritik değil
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Bordro başarıyla onaylandı'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bordro onaylanamadı']);
    }
    
} catch(PDOException $e) {
    error_log("Bordro onaylama hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu']);
}
?>