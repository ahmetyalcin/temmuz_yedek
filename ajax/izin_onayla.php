<?php
// ajax/izin_onayla.php
session_start();
require_once '../functions.php';
require_once '../con/db.php';

header('Content-Type: application/json');

// Yetki kontrolü
if (!in_array($_SESSION['rol'], ['yonetici', 'muhasebe'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$izin_id = $_POST['izin_id'] ?? '';
$action = $_POST['action'] ?? '';

if (empty($izin_id) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler']);
    exit;
}

try {
    if ($action == 'onayla') {
        $sql = "UPDATE personel_izinleri 
                SET durum = 'onaylandi', 
                    onaylayan_id = ?, 
                    onay_tarihi = NOW() 
                WHERE id = ? AND durum = 'beklemede'";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$_SESSION['user_id'], $izin_id]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'İzin başarıyla onaylandı']);
        } else {
            echo json_encode(['success' => false, 'message' => 'İzin onaylanamadı']);
        }
        
    } elseif ($action == 'reddet') {
        $red_nedeni = $_POST['red_nedeni'] ?? '';
        
        if (empty($red_nedeni)) {
            echo json_encode(['success' => false, 'message' => 'Red nedeni gerekli']);
            exit;
        }
        
        $sql = "UPDATE personel_izinleri 
                SET durum = 'reddedildi', 
                    onaylayan_id = ?, 
                    onay_tarihi = NOW(),
                    red_nedeni = ?
                WHERE id = ? AND durum = 'beklemede'";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$_SESSION['user_id'], $red_nedeni, $izin_id]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'İzin reddedildi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'İzin reddedilemedi']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    }
    
} catch(PDOException $e) {
    error_log("İzin onaylama hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
?>