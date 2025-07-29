<?php
// ajax/update_fatura_status.php
session_start();
require_once '../con/db.php';
// JSON response header
header('Content-Type: application/json');

// Güvenlik kontrolü - sadece giriş yapmış kullanıcılar
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek yöntemi']);
    exit;
}

// Gerekli parametrelerin kontrolü
if (!isset($_POST['satis_id']) || !isset($_POST['faturalandi'])) {
    echo json_encode(['success' => false, 'error' => 'Eksik parametreler']);
    exit;
}

$satis_id = trim($_POST['satis_id']);
$faturalandi = (int) $_POST['faturalandi'];

// Parametre validasyonu
if (empty($satis_id)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz satış ID']);
    exit;
}

if (!in_array($faturalandi, [0, 1])) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz faturalama durumu']);
    exit;
}

try {
    // Önce satışın var olup olmadığını kontrol et
    $check_sql = "SELECT id FROM satislar WHERE id = :satis_id AND aktif = 1";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute(['satis_id' => $satis_id]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Satış bulunamadı']);
        exit;
    }
    
    // Faturalama durumunu güncelle
    $sql = "UPDATE satislar 
            SET faturalandi = :faturalandi, 
                guncelleme_tarihi = CURRENT_TIMESTAMP 
            WHERE id = :satis_id";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        'faturalandi' => $faturalandi,
        'satis_id' => $satis_id
    ]);
    
    if ($success && $stmt->rowCount() > 0) {
        // Loglama (opsiyonel)
        $log_sql = "INSERT INTO sistem_loglari (kullanici_id, islem, aciklama, ip_adresi, olusturma_tarihi) 
                    VALUES (:kullanici_id, 'fatura_guncelle', :aciklama, :ip, NOW())";
        
        try {
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                'kullanici_id' => $_SESSION['user_id'],
                'aciklama' => "Satış #{$satis_id} faturalama durumu " . ($faturalandi ? 'faturalı' : 'faturasız') . " olarak güncellendi",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor'
            ]);
        } catch(PDOException $e) {
            // Log hatası kritik değil, ana işlemi etkilemesin
            error_log("Fatura güncelleme log hatası: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $faturalandi ? 'Satış faturalı olarak işaretlendi' : 'Satış faturasız olarak işaretlendi'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Güncelleme yapılamadı']);
    }
    
} catch(PDOException $e) {
    error_log("Fatura durumu güncelleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası oluştu']);
} catch(Exception $e) {
    error_log("Genel hata: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Beklenmeyen bir hata oluştu']);
}
?>