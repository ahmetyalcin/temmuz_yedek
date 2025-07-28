<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// sms_whatsapp_yonetimi.php - SMS/WhatsApp Yönetim Arayüzü
session_start();
require_once 'functions.php';
require_once 'con/db.php';
require_once 'sms_whatsapp_entegrasyon.php';

// Yetki kontrolü (sadece admin)
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'yonetici') {
    header('Location: auth-login.php');
    exit;
}

$message = '';
$error = '';

// API Ayarlarını veritabanından al
function getAPIAyarlari() {
    global $pdo;
    $sql = "SELECT * FROM sistem_ayarlari WHERE kategori = 'sms_whatsapp'";
    $stmt = $pdo->query($sql);
    $ayarlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [];
    foreach ($ayarlar as $ayar) {
        $config[$ayar['anahtar']] = $ayar['deger'];
    }
    
    return $config;
}

// API Ayarlarını kaydet
function saveAPIAyarlari($ayarlar) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($ayarlar as $anahtar => $deger) {
            $sql = "INSERT INTO sistem_ayarlari (kategori, anahtar, deger, aciklama) 
                    VALUES ('sms_whatsapp', ?, ?, ?)
                    ON DUPLICATE KEY UPDATE deger = VALUES(deger)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$anahtar, $deger, getAyarAciklama($anahtar)]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// Ayar açıklamaları
function getAyarAciklama($anahtar) {
    $aciklamalar = [
        'sms_api_url' => 'SMS API URL adresi',
        'sms_username' => 'SMS API kullanıcı adı',
        'sms_password' => 'SMS API şifresi',
        'sms_sender' => 'SMS gönderen adı',
        'whatsapp_account_sid' => 'WhatsApp (Twilio) Account SID',
        'whatsapp_auth_token' => 'WhatsApp (Twilio) Auth Token',
        'whatsapp_from' => 'WhatsApp gönderen numarası',
        'default_country_code' => 'Varsayılan ülke kodu',
        'rate_limit_per_minute' => 'Dakika başına mesaj limiti'
    ];
    
    return $aciklamalar[$anahtar] ?? '';
}

// İşlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'api_ayarlari_kaydet':
                $ayarlar = [
                    'sms_api_url' => $_POST['sms_api_url'] ?? '',
                    'sms_username' => $_POST['sms_username'] ?? '',
                    'sms_password' => $_POST['sms_password'] ?? '',
                    'sms_sender' => $_POST['sms_sender'] ?? 'PhysioVita',
                    'whatsapp_account_sid' => $_POST['whatsapp_account_sid'] ?? '',
                    'whatsapp_auth_token' => $_POST['whatsapp_auth_token'] ?? '',
                    'whatsapp_from' => $_POST['whatsapp_from'] ?? '',
                    'default_country_code' => $_POST['default_country_code'] ?? '+90',
                    'rate_limit_per_minute' => $_POST['rate_limit_per_minute'] ?? '60'
                ];
                
                if (saveAPIAyarlari($ayarlar)) {
                    $message = "API ayarları başarıyla kaydedildi!";
                } else {
                    $error = "API ayarları kaydedilemedi!";
                }
                break;
                
            case 'mesaj_sablon_kaydet':
                $sql = "INSERT INTO mesaj_sablonlari (kod, baslik, icerik, aktif) 
                        VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE baslik = VALUES(baslik), icerik = VALUES(icerik)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['sablon_kodu'],
                    $_POST['sablon_baslik'],
                    $_POST['sablon_icerik']
                ]);
                $message = "Mesaj şablonu kaydedildi!";
                break;
                
            case 'test_mesaj_gonder':
                $config = getAPIAyarlari();
                $sms_whatsapp = new SMSWhatsAppEntegrasyon($pdo, $config);
                
                $telefon = $_POST['test_telefon'];
                $mesaj = $_POST['test_mesaj'];
                $tip = $_POST['test_tip'];
                
                $sonuc = $sms_whatsapp->mesajGonder($telefon, $mesaj, $tip);
                $message = "Test mesajı başarıyla gönderildi! ID: " . $sonuc['message_id'];
                break;
                
            case 'toplu_mesaj_gonder':
                $config = getAPIAyarlari();
                $sms_whatsapp = new SMSWhatsAppEntegrasyon($pdo, $config);
                
                $hedef_grup = $_POST['hedef_grup'];
                $mesaj = $_POST['toplu_mesaj'];
                $tip = $_POST['toplu_tip'];
                
                // Hedef kitleyi belirle
                $alicilar = getHedefKitle($hedef_grup);
                
                $sonuc = $sms_whatsapp->topluMesajGonder($alicilar, $mesaj, $tip);
                $message = "Toplu mesaj gönderildi! Başarılı: {$sonuc['basarili']}, Başarısız: {$sonuc['basarisiz']}";
                break;
                
            case 'sablon_sil':
                $sql = "DELETE FROM mesaj_sablonlari WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['sablon_id']]);
                $message = "Şablon silindi!";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Hedef kitle belirleme
function getHedefKitle($grup) {
    global $pdo;
    
    switch ($grup) {
        case 'tum_musteriler':
            $sql = "SELECT telefon FROM danisanlar WHERE aktif = 1 AND telefon IS NOT NULL";
            break;
        case 'bugun_randevu':
            $sql = "SELECT DISTINCT d.telefon FROM danisanlar d 
                    JOIN randevular r ON d.id = r.danisan_id 
                    WHERE DATE(r.randevu_tarihi) = CURDATE() AND r.aktif = 1";
            break;
        case 'yarin_randevu':
            $sql = "SELECT DISTINCT d.telefon FROM danisanlar d 
                    JOIN randevular r ON d.id = r.danisan_id 
                    WHERE DATE(r.randevu_tarihi) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND r.aktif = 1";
            break;
        case 'vip_musteriler':
            $sql = "SELECT telefon FROM danisanlar WHERE sadakat_seviyesi >= 4 AND aktif = 1 AND telefon IS NOT NULL";
            break;
        default:
            return [];
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Veriler
$api_ayarlari = getAPIAyarlari();

$sql = "SELECT * FROM mesaj_sablonlari WHERE aktif = 1 ORDER BY kod";
$stmt = $pdo->query($sql);
$mesaj_sablonlari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sayfa ayarları
$title = 'SMS/WhatsApp Yönetimi';
$subtitle = 'Mesaj gönderimi ve API ayarları';
include __DIR__ . '/partials/header.php';
?>

<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" />

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            
            <!-- Başlık -->
            <div class="page-title">
                <div class="row">
                    <div class="col-12">
                        <nav aria-label="breadcrumb" class="breadcrumb-header">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">SMS/WhatsApp Yönetimi</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3><?= $title ?></h3>
                        <p class="text-subtitle text-muted"><?= $subtitle ?></p>
                    </div>
                </div>
            </div>

            <div class="container-fluid">

                <!-- Mesajlar -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <?php
                    // İstatistikler
                    $sql = "SELECT COUNT(*) FROM mesaj_gonderim_log WHERE DATE(gonderim_tarihi) = CURDATE()";
                    $stmt = $pdo->query($sql);
                    $bugun_gonderilen = $stmt->fetchColumn();
                    
                    $sql = "SELECT COUNT(*) FROM mesaj_gonderim_log WHERE durum = 'basarili' AND DATE(gonderim_tarihi) = CURDATE()";
                    $stmt = $pdo->query($sql);
                    $bugun_basarili = $stmt->fetchColumn();
                    
                    $sql = "SELECT COUNT(*) FROM mesaj_gonderim_log WHERE WEEK(gonderim_tarihi) = WEEK(CURDATE())";
                    $stmt = $pdo->query($sql);
                    $haftalik_gonderilen = $stmt->fetchColumn();
                    
                    $sql = "SELECT COUNT(*) FROM mesaj_sablonlari WHERE aktif = 1";
                    $stmt = $pdo->query($sql);
                    $aktif_sablon = $stmt->fetchColumn();
                    ?>
                    
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bx bx-message-dots fs-1 text-primary mb-2"></i>
                                <h4 class="mb-1"><?= number_format($bugun_gonderilen) ?></h4>
                                <p class="text-muted mb-0">Bugün Gönderilen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bx bx-check-circle fs-1 text-success mb-2"></i>
                                <h4 class="mb-1"><?= number_format($bugun_basarili) ?></h4>
                                <p class="text-muted mb-0">Bugün Başarılı</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bx bx-trending-up fs-1 text-info mb-2"></i>
                                <h4 class="mb-1"><?= number_format($haftalik_gonderilen) ?></h4>
                                <p class="text-muted mb-0">Bu Hafta Toplam</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bx bx-file-blank fs-1 text-warning mb-2"></i>
                                <h4 class="mb-1"><?= number_format($aktif_sablon) ?></h4>
                                <p class="text-muted mb-0">Aktif Şablon</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ana Menü Tabs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" id="smsWhatsappTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-panel" type="button" role="tab">
                                            <i class="bx bx-cog me-2"></i>API Ayarları
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="sablonlar-tab" data-bs-toggle="tab" data-bs-target="#sablonlar-panel" type="button" role="tab">
                                            <i class="bx bx-file me-2"></i>Mesaj Şablonları
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="gonder-tab" data-bs-toggle="tab" data-bs-target="#gonder-panel" type="button" role="tab">
                                            <i class="bx bx-send me-2"></i>Mesaj Gönder
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="gecmis-tab" data-bs-toggle="tab" data-bs-target="#gecmis-panel" type="button" role="tab">
                                            <i class="bx bx-history me-2"></i>Gönderim Geçmişi
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="smsWhatsappTabContent">
                                    
                                    <!-- API Ayarları Tab -->
                                    <div class="tab-pane fade show active" id="api-panel" role="tabpanel">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="api_ayarlari_kaydet">
                                            
                                            <div class="row">
                                                <!-- SMS Ayarları -->
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-primary text-white">
                                                            <h5 class="mb-0"><i class="bx bx-message me-2"></i>SMS API Ayarları</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">SMS API URL</label>
                                                                <input type="url" name="sms_api_url" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['sms_api_url'] ?? 'https://api.iletimerkezi.com/v1/send-sms/get/') ?>"
                                                                       placeholder="https://api.iletimerkezi.com/v1/send-sms/get/">
                                                                <small class="text-muted">İletimerkezi, Netgsm, vs. API URL'i</small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Kullanıcı Adı</label>
                                                                <input type="text" name="sms_username" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['sms_username'] ?? '') ?>"
                                                                       placeholder="SMS API kullanıcı adınız">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Şifre/API Key</label>
                                                                <input type="password" name="sms_password" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['sms_password'] ?? '') ?>"
                                                                       placeholder="SMS API şifreniz">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Gönderen Adı</label>
                                                                <input type="text" name="sms_sender" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['sms_sender'] ?? 'PhysioVita') ?>"
                                                                       placeholder="PhysioVita" maxlength="11">
                                                                <small class="text-muted">Maksimum 11 karakter</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- WhatsApp Ayarları -->
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-success text-white">
                                                            <h5 class="mb-0"><i class="bx bxl-whatsapp me-2"></i>WhatsApp API Ayarları</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Twilio Account SID</label>
                                                                <input type="text" name="whatsapp_account_sid" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['whatsapp_account_sid'] ?? '') ?>"
                                                                       placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Auth Token</label>
                                                                <input type="password" name="whatsapp_auth_token" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['whatsapp_auth_token'] ?? '') ?>"
                                                                       placeholder="Twilio Auth Token">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">WhatsApp Numarası</label>
                                                                <input type="text" name="whatsapp_from" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['whatsapp_from'] ?? 'whatsapp:+14155238886') ?>"
                                                                       placeholder="whatsapp:+14155238886">
                                                                <small class="text-muted">Twilio sandbox veya onaylı numara</small>
                                                            </div>
                                                            
                                                            <div class="alert alert-info">
                                                                <small>
                                                                    <strong>Not:</strong> Twilio WhatsApp Business API için 
                                                                    <a href="https://www.twilio.com/whatsapp" target="_blank">buradan</a> 
                                                                    hesap açabilirsiniz.
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Genel Ayarlar -->
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h5 class="mb-0"><i class="bx bx-wrench me-2"></i>Genel Ayarlar</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Varsayılan Ülke Kodu</label>
                                                                <input type="text" name="default_country_code" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['default_country_code'] ?? '+90') ?>"
                                                                       placeholder="+90">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Dakika Başına Mesaj Limiti</label>
                                                                <input type="number" name="rate_limit_per_minute" class="form-control" 
                                                                       value="<?= htmlspecialchars($api_ayarlari['rate_limit_per_minute'] ?? '60') ?>"
                                                                       min="1" max="1000">
                                                                <small class="text-muted">Spam koruması için</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary btn-lg">
                                                        <i class="bx bx-save me-2"></i>API Ayarlarını Kaydet
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Mesaj Şablonları Tab -->
                                    <div class="tab-pane fade" id="sablonlar-panel" role="tabpanel">
                                        <div class="row">
                                            <!-- Yeni Şablon Ekleme -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-info text-white">
                                                        <h5 class="mb-0"><i class="bx bx-plus me-2"></i>Yeni Şablon Ekle</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="mesaj_sablon_kaydet">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Şablon Kodu</label>
                                                                <input type="text" name="sablon_kodu" class="form-control" required
                                                                       placeholder="randevu_hatirlatma">
                                                                <small class="text-muted">Benzersiz kod (ingilizce karakterler)</small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Şablon Başlığı</label>
                                                                <input type="text" name="sablon_baslik" class="form-control" required
                                                                       placeholder="Randevu Hatırlatması">
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Mesaj İçeriği</label>
                                                                <textarea name="sablon_icerik" class="form-control" rows="5" required
                                                                          placeholder="Sayın {MUSTERI_ADI}, {TARIH} {SAAT} randevunuz için hatırlatma..."></textarea>
                                                                <small class="text-muted">
                                                                    Kullanılabilir değişkenler: {MUSTERI_ADI}, {AD}, {SOYAD}, {TARIH}, {SAAT}, {TERAPIST}, {SEANS_TURU}
                                                                </small>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-info">
                                                                <i class="bx bx-plus me-2"></i>Şablon Ekle
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Mevcut Şablonlar -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5 class="mb-0"><i class="bx bx-file me-2"></i>Mevcut Şablonlar</h5>
                                                    </div>
                                                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                                        <?php if (empty($mesaj_sablonlari)): ?>
                                                            <p class="text-muted text-center">Henüz şablon eklenmemiş</p>
                                                        <?php else: ?>
                                                            <?php foreach ($mesaj_sablonlari as $sablon): ?>
                                                                <div class="card mb-3">
                                                                    <div class="card-body p-3">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div class="flex-grow-1">
                                                                                <h6 class="mb-1"><?= htmlspecialchars($sablon['baslik']) ?></h6>
                                                                                <code class="small"><?= htmlspecialchars($sablon['kod']) ?></code>
                                                                                <p class="mb-0 mt-2 small text-muted">
                                                                                    <?= nl2br(htmlspecialchars(substr($sablon['icerik'], 0, 100))) ?>
                                                                                    <?= strlen($sablon['icerik']) > 100 ? '...' : '' ?>
                                                                                </p>
                                                                            </div>
                                                                            <div class="ms-2">
                                                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                                        onclick="sablonuKullan('<?= htmlspecialchars($sablon['icerik']) ?>')">
                                                                                    <i class="bx bx-copy"></i>
                                                                                </button>
                                                                                <form method="POST" class="d-inline">
                                                                                    <input type="hidden" name="action" value="sablon_sil">
                                                                                    <input type="hidden" name="sablon_id" value="<?= $sablon['id'] ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                                            onclick="return confirm('Bu şablonu silmek istediğinizden emin misiniz?')">
                                                                                        <i class="bx bx-trash"></i>
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Mesaj Gönder Tab -->
                                    <div class="tab-pane fade" id="gonder-panel" role="tabpanel">
                                        <div class="row">
                                            <!-- Test Mesajı -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-warning text-dark">
                                                        <h5 class="mb-0"><i class="bx bx-test-tube me-2"></i>Test Mesajı Gönder</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="test_mesaj_gonder">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Test Telefon Numarası</label>
                                                                <input type="tel" name="test_telefon" class="form-control" required
                                                                       placeholder="5xxxxxxxxx" pattern="[0-9]{10}">
                                                                <small class="text-muted">Ülke kodu olmadan 10 haneli numara</small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Gönderim Yöntemi</label>
                                                                <select name="test_tip" class="form-select" required>
                                                                    <option value="auto">Otomatik (Tercih + WhatsApp/SMS)</option>
                                                                    <option value="sms">Sadece SMS</option>
                                                                    <option value="whatsapp">Sadece WhatsApp</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Test Mesajı</label>
                                                                <textarea name="test_mesaj" id="testMesajTextarea" class="form-control" rows="4" required
                                                                          placeholder="Bu bir test mesajıdır. PhysioVita"></textarea>
                                                                <div class="d-flex justify-content-between mt-1">
                                                                    <small class="text-muted">Karakter sayısı: <span id="testKarakterSayisi">0</span>/160</small>
                                                                    <small class="text-muted">SMS sayısı: <span id="testSmsSayisi">1</span></small>
                                                                </div>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="bx bx-send me-2"></i>Test Mesajı Gönder
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Toplu Mesaj -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-danger text-white">
                                                        <h5 class="mb-0"><i class="bx bx-broadcast me-2"></i>Toplu Mesaj Gönder</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST" onsubmit="return confirm('Toplu mesaj göndermek istediğinizden emin misiniz?')">
                                                            <input type="hidden" name="action" value="toplu_mesaj_gonder">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Hedef Kitle</label>
                                                                <select name="hedef_grup" class="form-select" required onchange="updateHedefSayisi(this.value)">
                                                                    <option value="">Hedef kitle seçiniz...</option>
                                                                    <option value="tum_musteriler">Tüm Aktif Müşteriler</option>
                                                                    <option value="bugun_randevu">Bugün Randevusu Olan Müşteriler</option>
                                                                    <option value="yarin_randevu">Yarın Randevusu Olan Müşteriler</option>
                                                                    <option value="vip_musteriler">VIP Müşteriler (Platin/Elmas)</option>
                                                                </select>
                                                                <small class="text-muted">Hedef kişi sayısı: <span id="hedefSayisi">-</span></small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Gönderim Yöntemi</label>
                                                                <select name="toplu_tip" class="form-select" required>
                                                                    <option value="auto">Otomatik (Müşteri tercihi)</option>
                                                                    <option value="sms">Sadece SMS</option>
                                                                    <option value="whatsapp">Sadece WhatsApp</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Toplu Mesaj İçeriği</label>
                                                                <textarea name="toplu_mesaj" id="topluMesajTextarea" class="form-control" rows="5" required
                                                                          placeholder="Değerli müşterilerimiz, size özel kampanya..."></textarea>
                                                                <div class="d-flex justify-content-between mt-1">
                                                                    <small class="text-muted">Karakter: <span id="topluKarakterSayisi">0</span>/160</small>
                                                                    <small class="text-muted">SMS: <span id="topluSmsSayisi">1</span></small>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="alert alert-warning">
                                                                <small>
                                                                    <i class="bx bx-info-circle me-1"></i>
                                                                    <strong>Dikkat:</strong> Toplu mesaj gönderimi geri alınamaz. 
                                                                    Lütfen mesajınızı kontrol edin.
                                                                </small>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="bx bx-broadcast me-2"></i>Toplu Mesaj Gönder
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Hızlı Şablonlar -->
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h5 class="mb-0"><i class="bx bx-zap me-2"></i>Hızlı Şablonlar</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-outline-primary w-100 mb-2" 
                                                                        onclick="sablonuKullan('🏥 PhysioVita Randevu Hatırlatması\n\nSayın Müşterimiz,\n\nYarın randevunuz bulunmaktadır. Lütfen zamanında gelerek tedavinizi aksatmayınız.\n\nPhysioVita')">
                                                                    <i class="bx bx-calendar me-2"></i>Randevu Hatırlatması
                                                                </button>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-outline-success w-100 mb-2" 
                                                                        onclick="sablonuKullan('🎉 Değerli Müşterimiz,\n\nSize özel %20 indirim fırsatı! Bu ayın sonuna kadar geçerli.\n\nDetaylar için bizi arayınız.\n\nPhysioVita')">
                                                                    <i class="bx bx-gift me-2"></i>Kampanya Bildirimi
                                                                </button>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" class="btn btn-outline-info w-100 mb-2" 
                                                                        onclick="sablonuKullan('📋 Değerli Müşterimiz,\n\nTedavinizi değerlendirmeniz için link:\n[DEĞERLENDIRME_LINKI]\n\nGörüşleriniz bizim için çok değerli.\n\nPhysioVita')">
                                                                    <i class="bx bx-star me-2"></i>Değerlendirme Daveti
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Gönderim Geçmişi Tab -->
                                    <div class="tab-pane fade" id="gecmis-panel" role="tabpanel">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                                                    <input type="text" id="searchGecmis" class="form-control" placeholder="Telefon numarası veya mesaj içeriği ara...">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <select id="filterYontem" class="form-select">
                                                    <option value="">Tüm Yöntemler</option>
                                                    <option value="sms">SMS</option>
                                                    <option value="whatsapp">WhatsApp</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select id="filterDurum" class="form-select">
                                                    <option value="">Tüm Durumlar</option>
                                                    <option value="basarili">Başarılı</option>
                                                    <option value="basarisiz">Başarısız</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table id="gecmisTable" class="table table-striped table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Tarih/Saat</th>
                                                        <th>Telefon</th>
                                                        <th>Yöntem</th>
                                                        <th>Durum</th>
                                                        <th>Mesaj</th>
                                                        <th>Detay</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $sql = "SELECT * FROM mesaj_gonderim_log 
                                                            ORDER BY gonderim_tarihi DESC 
                                                            LIMIT 200";
                                                    $stmt = $pdo->query($sql);
                                                    $gecmis = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <?php foreach ($gecmis as $log): ?>
                                                        <tr>
                                                            <td><?= date('d.m.Y H:i', strtotime($log['gonderim_tarihi'])) ?></td>
                                                            <td>
                                                                <span class="font-monospace"><?= htmlspecialchars($log['telefon']) ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($log['yontem'] === 'whatsapp'): ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="bx bxl-whatsapp me-1"></i>WhatsApp
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-primary">
                                                                        <i class="bx bx-message me-1"></i>SMS
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($log['durum'] === 'basarili'): ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="bx bx-check me-1"></i>Başarılı
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">
                                                                        <i class="bx bx-x me-1"></i>Başarısız
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                                      title="<?= htmlspecialchars($log['mesaj']) ?>">
                                                                    <?= htmlspecialchars(substr($log['mesaj'], 0, 50)) ?>...
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                                        onclick="showDetay('<?= htmlspecialchars(json_encode($log), ENT_QUOTES) ?>')">
                                                                    <i class="bx bx-info-circle"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mesaj Detay Modal -->
<div class="modal fade" id="detayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mesaj Gönderim Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detayContent">
                <!-- AJAX ile doldurulacak -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
// Karakter sayısı hesaplama
function updateKarakterSayisi(textareaId, sayiciId, smsSayiciId) {
    const textarea = document.getElementById(textareaId);
    const sayici = document.getElementById(sayiciId);
    const smsSayici = document.getElementById(smsSayiciId);
    
    const karakter = textarea.value.length;
    const smsSayisi = Math.ceil(karakter / 160);
    
    sayici.textContent = karakter;
    smsSayici.textContent = smsSayisi;
    
    // Renk kodlaması
    if (karakter > 160) {
        sayici.className = 'text-warning';
    } else if (karakter > 140) {
        sayici.className = 'text-info';
    } else {
        sayici.className = 'text-muted';
    }
}

// Event listener'lar
document.getElementById('testMesajTextarea').addEventListener('input', function() {
    updateKarakterSayisi('testMesajTextarea', 'testKarakterSayisi', 'testSmsSayisi');
});

document.getElementById('topluMesajTextarea').addEventListener('input', function() {
    updateKarakterSayisi('topluMesajTextarea', 'topluKarakterSayisi', 'topluSmsSayisi');
});

// Şablon kullanma
function sablonuKullan(icerik) {
    // Aktif tab'e göre uygun textarea'yı bul
    const activeTab = document.querySelector('.tab-pane.active');
    let textarea;
    
    if (activeTab.id === 'gonder-panel') {
        // Test mesajı veya toplu mesaj textarea'sını kullan
        textarea = document.getElementById('testMesajTextarea');
        if (!textarea.closest('.card').style.display !== 'none') {
            textarea = document.getElementById('topluMesajTextarea');
        }
    } else {
        // Şablon ekleme textarea'sını kullan
        textarea = document.querySelector('textarea[name="sablon_icerik"]');
    }
    
    if (textarea) {
        textarea.value = icerik;
        // Karakter sayısını güncelle
        const textareaId = textarea.id;
        if (textareaId === 'testMesajTextarea') {
            updateKarakterSayisi('testMesajTextarea', 'testKarakterSayisi', 'testSmsSayisi');
        } else if (textareaId === 'topluMesajTextarea') {
            updateKarakterSayisi('topluMesajTextarea', 'topluKarakterSayisi', 'topluSmsSayisi');
        }
    }
}

// Hedef kitle sayısını güncelle
function updateHedefSayisi(grup) {
    if (!grup) {
        document.getElementById('hedefSayisi').textContent = '-';
        return;
    }
    
    fetch(`ajax/get_hedef_kitle_sayisi.php?grup=${grup}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('hedefSayisi').textContent = data.sayi || '-';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('hedefSayisi').textContent = 'Hata';
        });
}

// Detay modal
function showDetay(logData) {
    const log = JSON.parse(logData);
    const modal = new bootstrap.Modal(document.getElementById('detayModal'));
    
    let detayHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Gönderim Bilgileri</h6>
                <table class="table table-sm">
                    <tr><td><strong>Tarih/Saat:</strong></td><td>${log.gonderim_tarihi}</td></tr>
                    <tr><td><strong>Telefon:</strong></td><td>${log.telefon}</td></tr>
                    <tr><td><strong>Yöntem:</strong></td><td>${log.yontem.toUpperCase()}</td></tr>
                    <tr><td><strong>Durum:</strong></td><td>${log.durum}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Mesaj İçeriği</h6>
                <div class="p-2 bg-light rounded">
                    <small>${log.mesaj.replace(/\n/g, '<br>')}</small>
                </div>
            </div>
        </div>
    `;
    
    if (log.detay) {
        try {
            const detay = JSON.parse(log.detay);
            detayHTML += `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>API Yanıt Detayı</h6>
                        <pre class="bg-dark text-light p-2 rounded small" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(detay, null, 2)}</pre>
                    </div>
                </div>
            `;
        } catch (e) {
            // JSON parse hatası
        }
    }
    
    document.getElementById('detayContent').innerHTML = detayHTML;
    modal.show();
}

// DataTable başlat
$(document).ready(function() {
    const table = $('#gecmisTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
        },
        order: [[0, 'desc']], // Tarihe göre sırala
        pageLength: 25,
        columnDefs: [
            { targets: [4], orderable: false } // Mesaj kolonunu sıralamaya dahil etme
        ]
    });
    
    // Filtreler
    $('#searchGecmis').on('keyup', function() {
        table.search(this.value).draw();
    });
    
    $('#filterYontem').on('change', function() {
        table.column(2).search(this.value).draw();
    });
    
    $('#filterDurum').on('change', function() {
        table.column(3).search(this.value).draw();
    });
});

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // İlk karakter sayımı
    updateKarakterSayisi('testMesajTextarea', 'testKarakterSayisi', 'testSmsSayisi');
    updateKarakterSayisi('topluMesajTextarea', 'topluKarakterSayisi', 'topluSmsSayisi');
});
</script>

<style>
.nav-tabs .nav-link {
    border-radius: 8px 8px 0 0;
    margin-right: 4px;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 12px 12px 0 0;
    font-weight: 600;
}

.btn {
    border-radius: 8px;
}

.badge {
    font-size: 0.75rem;
}

.table th {
    font-weight: 600;
    border-top: none;
}

.alert {
    border-radius: 8px;
    border: none;
}

.form-select, .form-control {
    border-radius: 6px;
}

.font-monospace {
    font-family: 'Courier New', monospace;
}

/* Responsive iyileştirmeler */
@media (max-width: 768px) {
    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<?php include __DIR__ . '/partials/footer-scripts.php'; ?>