<?php
session_start();
header('Content-Type: application/json');

// Veritabanı bağlantısını direkt dahil et
include_once __DIR__ . '/../con/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

try {
    $gider_id = trim($_POST['gider_id'] ?? '');
    $odeme_tarihi = trim($_POST['odeme_tarihi'] ?? '');
    $tutar = floatval($_POST['tutar'] ?? 0);
    $odeme_yontemi = trim($_POST['odeme_yontemi'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    
    // Validasyon
    if (empty($gider_id) || empty($odeme_tarihi) || $tutar <= 0 || empty($odeme_yontemi)) {
        echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurunuz']);
        exit;
    }
    
    // Giderin mevcut durumunu kontrol et
    $stmt = $pdo->prepare("SELECT * FROM giderler WHERE id = ? AND aktif = 1");
    $stmt->execute([$gider_id]);
    $gider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gider) {
        echo json_encode(['success' => false, 'message' => 'Gider bulunamadı']);
        exit;
    }
    
    // Ödeme tutarı kalan tutardan fazla olamaz
    if ($tutar > $gider['odenmemis_kalan']) {
        echo json_encode(['success' => false, 'message' => 'Ödeme tutarı kalan tutardan fazla olamaz']);
        exit;
    }
    
    // User ID'yi al
    $user_id = $_SESSION['user_id'] ?? $_SESSION['personel_id'] ?? $_SESSION['id'] ?? 1;
    
    // UUID oluştur
    $odeme_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Transaction başlat
    $pdo->beginTransaction();
    
    // Ödeme kaydını ekle
    $stmt = $pdo->prepare("
        INSERT INTO gider_odemeleri (
            id, gider_id, odeme_tarihi, tutar, odeme_yontemi, aciklama, kayit_yapan_id
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $odeme_eklendi = $stmt->execute([
        $odeme_id,
        $gider_id,
        $odeme_tarihi,
        $tutar,
        $odeme_yontemi,
        $aciklama,
        $user_id
    ]);
    
    if (!$odeme_eklendi) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ödeme kaydedilemedi']);
        exit;
    }
    
    // Toplam ödenen tutarı hesapla
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(tutar), 0) as toplam_odenen 
        FROM gider_odemeleri 
        WHERE gider_id = ? AND aktif = 1
    ");
    $stmt->execute([$gider_id]);
    $toplam_odenen = $stmt->fetchColumn();
    
    // Kalan tutarı ve durumu güncelle
    $odenmemis_kalan = $gider['tutar'] - $toplam_odenen;
    
    $durum = 'beklemede';
    if ($toplam_odenen >= $gider['tutar']) {
        $durum = 'odendi';
    } elseif ($toplam_odenen > 0) {
        $durum = 'kismi_odendi';
    }
    
    // Gider tablosunu güncelle
    $stmt = $pdo->prepare("
        UPDATE giderler 
        SET odenmemis_kalan = ?, durum = ?, guncelleme_tarihi = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $gider_guncellendi = $stmt->execute([$odenmemis_kalan, $durum, $gider_id]);
    
    if (!$gider_guncellendi) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Gider durumu güncellenemedi']);
        exit;
    }
    
    // Transaction commit
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ödeme başarıyla kaydedildi',
        'odeme_id' => $odeme_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Gider ödeme ekleme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()]);
}
?>