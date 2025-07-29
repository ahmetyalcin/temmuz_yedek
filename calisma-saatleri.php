<?php
// calisma-saatleri.php - Kapsamlı çalışma saatleri takip sistemi
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!in_array($_SESSION['rol'], ['yonetici', 'ik', 'sekreter'])) {
    header('Location: unauthorized.php');
    exit;
}

// İşlemler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'giris_cikis_kaydet') {
        $personel_id = $_POST['personel_id'];
        $tarih = $_POST['tarih'];
        $giris_saati = $_POST['giris_saati'];
        $cikis_saati = $_POST['cikis_saati'];
        $aciklama = $_POST['aciklama'] ?? '';
        
        if (kaydetGirisCikis($personel_id, $tarih, $giris_saati, $cikis_saati, $aciklama)) {
            $success_msg = "Çalışma saatleri kaydedildi.";
        } else {
            $error_msg = "Kaydetme sırasında hata oluştu.";
        }
    } elseif ($_POST['action'] == 'toplu_kayit') {
        $tarih = $_POST['toplu_tarih'];
        $giris_saati = $_POST['toplu_giris'];
        $cikis_saati = $_POST['toplu_cikis'];
        $personel_listesi = $_POST['personel_listesi'] ?? [];
        
        $basarili = 0;
        foreach ($personel_listesi as $personel_id) {
            if (kaydetGirisCikis($personel_id, $tarih, $giris_saati, $cikis_saati, 'Toplu kayıt')) {
                $basarili++;
            }
        }
        $success_msg = "{$basarili} personelin çalışma saatleri toplu olarak kaydedildi.";
    }
}

// Filtreleme parametreleri
$tarih = $_GET['tarih'] ?? date('Y-m-d');
$personel_id = $_GET['personel_id'] ?? '';
$ay = $_GET['ay'] ?? date('m');
$yil = $_GET['yil'] ?? date('Y');

// Verileri getir
$personeller = getPersoneller();
$gunluk_kayitlar = getGunlukCalismaKayitlari($tarih);
$aylik_ozet = getAylikCalismaOzeti($ay, $yil);
$calisma_istatistikleri = getCalismaIstatistikleri($ay, $yil);

$title = 'Çalışma Saatleri Takibi';
include __DIR__ . '/partials/header.php';
?>

<style>
.time-card {
    border-radius: 15px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.time-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.status-normal { border-left: 5px solid #28a745; }
.status-gecikme { border-left: 5px solid #ffc107; }
.status-erken_cikis { border-left: 5px solid #fd7e14; }
.status-devamsizlik { border-left: 5px solid #dc3545; }
.status-izin { border-left: 5px solid #6f42c1; }

.time-input {
    font-family: 'Courier New', monospace;
    font-size: 1.1em;
    text-align: center;
}

.shift-selector {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
}

.daily-summary {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    border-radius: 15px;
    padding: 20px;
}

.attendance-widget {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    border-radius: 15px;
    color: #333;
    text-align: center;
    padding: 25px;
}

.overtime-indicator {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.overtime-normal { background: #d1ecf1; color: #0c5460; }
.overtime-warning { background: #fff3cd; color: #856404; }
.overtime-danger { background: #f8d7da; color: #721c24; }

.time-display {
    font-family: 'Courier New', monospace;
    font-size: 1.2em;
    font-weight: bold;
}

.personel-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.digital-clock {
    font-family: 'Courier New', monospace;
    font-size: 1.5em;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 10px rgba(255,255,255,0.3);
}

.quick-action-btn {
    border-radius: 25px;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    transform: scale(1.05);
}
</style>

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            <?php include __DIR__ . '/partials/page-title.php'; ?>

            <div class="container-fluid">
                
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $error_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tarih ve Filtreleme -->
                <div class="shift-selector">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <h5 class="text-white mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Çalışma Saatleri Takibi
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="digital-clock" id="digitalClock"></div>
                        </div>
                    </div>
                    
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label text-white fw-bold">📅 Tarih</label>
                            <input type="date" name="tarih" class="form-control" value="<?= $tarih ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white fw-bold">👤 Personel</label>
                            <select name="personel_id" class="form-select">
                                <option value="">Tüm Personel</option>
                                <?php foreach ($personeller as $personel): ?>
                                    <option value="<?= $personel['id'] ?>" <?= $personel_id == $personel['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-white fw-bold">📊 Ay/Yıl</label>
                            <div class="row">
                                <div class="col-6">
                                    <select name="ay" class="form-select">
                                        <?php for($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= sprintf('%02d', $m) ?>" <?= $ay == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                                <?= $m ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="yil" class="form-select">
                                        <?php for($y = date('Y'); $y >= date('Y')-2; $y--): ?>
                                            <option value="<?= $y ?>" <?= $yil == $y ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-light w-100 quick-action-btn">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success w-100 quick-action-btn" data-bs-toggle="modal" data-bs-target="#topluKayitModal">
                                <i class="fas fa-users me-1"></i>Toplu Kayıt
                            </button>
                        </div>
                    </form>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="attendance-widget">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <h3><?= number_format($calisma_istatistikleri['ortalama_saat'], 1) ?></h3>
                            <p class="mb-0">Ortalama Çalışma Saati</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white time-card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x mb-2"></i>
                                <h3><?= $calisma_istatistikleri['zamaninda_gelen'] ?></h3>
                                <p class="mb-0">Zamanında Gelen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white time-card">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3><?= $calisma_istatistikleri['geciken'] ?></h3>
                                <p class="mb-0">Geciken</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white time-card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-times fa-2x mb-2"></i>
                                <h3><?= $calisma_istatistikleri['devamsiz'] ?></h3>
                                <p class="mb-0">Devamsızlık</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Günlük Kayıtlar -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-day text-primary me-2"></i>
                                    <?= date('d.m.Y', strtotime($tarih)) ?> Çalışma Kayıtları
                                </h5>
                                <div class="btn-group">
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#yeniKayitModal">
                                        <i class="fas fa-plus me-1"></i>Yeni Kayıt
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="exportGunlukRapor()">
                                        <i class="fas fa-file-excel me-1"></i>Excel
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="calismaKayitlariTable">
                                        <thead>
                                            <tr>
                                                <th>Personel</th>
                                                <th>Giriş</th>
                                                <th>Çıkış</th>
                                                <th>Toplam Saat</th>
                                                <th>Mesai</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($gunluk_kayitlar)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">Bu tarih için kayıt bulunamadı.</p>
                                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#topluKayitModal">
                                                            <i class="fas fa-plus me-1"></i>Toplu Kayıt Oluştur
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach($gunluk_kayitlar as $kayit): ?>
                                                    <tr class="status-<?= $kayit['durum'] ?>">
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="uploads/avatars/<?= $kayit['avatar'] ?: 'default.png' ?>" 
                                                                     class="personel-avatar me-2" alt="">
                                                                <div>
                                                                    <strong><?= htmlspecialchars($kayit['personel_adi']) ?></strong>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($kayit['sicil_no']) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($kayit['giris_saati']): ?>
                                                                <span class="time-display text-success">
                                                                    <?= date('H:i', strtotime($kayit['giris_saati'])) ?>
                                                                </span>
                                                                <?php if ($kayit['gecikme_dakika'] > 0): ?>
                                                                    <br><small class="text-warning">
                                                                        <i class="fas fa-clock"></i> +<?= $kayit['gecikme_dakika'] ?> dk gecikme
                                                                    </small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Giriş yok</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($kayit['cikis_saati']): ?>
                                                                <span class="time-display text-danger">
                                                                    <?= date('H:i', strtotime($kayit['cikis_saati'])) ?>
                                                                </span>
                                                                <?php if ($kayit['erken_cikis_dakika'] > 0): ?>
                                                                    <br><small class="text-warning">
                                                                        <i class="fas fa-door-open"></i> -<?= $kayit['erken_cikis_dakika'] ?> dk erken
                                                                    </small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Çıkış yok</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold">
                                                                <?= number_format($kayit['toplam_calisma_saati'], 1) ?> saat
                                                            </span>
                                                            <?php 
                                                            $saat_durumu = '';
                                                            if ($kayit['toplam_calisma_saati'] >= 9) {
                                                                $saat_durumu = 'overtime-normal';
                                                                $icon = '✅';
                                                                $text = 'Normal';
                                                            } elseif ($kayit['toplam_calisma_saati'] >= 7) {
                                                                $saat_durumu = 'overtime-warning';
                                                                $icon = '⚠️';
                                                                $text = 'Eksik';
                                                            } else {
                                                                $saat_durumu = 'overtime-danger';
                                                                $icon = '❌';
                                                                $text = 'Kritik';
                                                            }
                                                            ?>
                                                            <br><div class="overtime-indicator <?= $saat_durumu ?>">
                                                                <?= $icon ?> <?= $text ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($kayit['mesai_saati'] > 0): ?>
                                                                <span class="badge bg-info">
                                                                    <i class="fas fa-plus"></i> <?= number_format($kayit['mesai_saati'], 1) ?> saat
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $durum_icons = [
                                                                'normal' => ['✅', 'success', 'Normal'],
                                                                'gecikme' => ['⏰', 'warning', 'Gecikme'],
                                                                'erken_cikis' => ['🚪', 'info', 'Erken Çıkış'],
                                                                'tam_gun_izin' => ['🏖️', 'secondary', 'İzinli'],
                                                                'devamsizlik' => ['❌', 'danger', 'Devamsız']
                                                            ];
                                                            $durum_info = $durum_icons[$kayit['durum']] ?? ['❓', 'dark', 'Bilinmiyor'];
                                                            ?>
                                                            <span class="badge bg-<?= $durum_info[1] ?>">
                                                                <?= $durum_info[0] ?> <?= $durum_info[2] ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button class="btn btn-warning btn-sm" 
                                                                        onclick="duzenleKayit('<?= $kayit['id'] ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-info btn-sm" 
                                                                        onclick="detayGoster('<?= $kayit['id'] ?>')">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-danger btn-sm" 
                                                                        onclick="silKayit('<?= $kayit['id'] ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Günlük Özet ve Hızlı İşlemler -->
                    <div class="col-lg-4">
                        <div class="daily-summary">
                            <h5 class="mb-3">
                                <i class="fas fa-chart-pie me-2"></i>
                                Günlük Özet
                            </h5>
                            
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-success"><?= count($gunluk_kayitlar) ?></h4>
                                    <small>Toplam Kayıt</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-primary">
                                        <?= number_format(array_sum(array_column($gunluk_kayitlar, 'toplam_calisma_saati')), 1) ?>
                                    </h4>
                                    <small>Toplam Saat</small>
                                </div>
                            </div>

                            <div class="progress mb-3" style="height: 20px;">
                                <?php
                                $normal_oran = count(array_filter($gunluk_kayitlar, fn($k) => $k['durum'] == 'normal'));
                                $gecikme_oran = count(array_filter($gunluk_kayitlar, fn($k) => $k['durum'] == 'gecikme'));
                                $toplam = count($gunluk_kayitlar);
                                
                                $normal_yuzde = $toplam > 0 ? ($normal_oran / $toplam) * 100 : 0;
                                $gecikme_yuzde = $toplam > 0 ? ($gecikme_oran / $toplam) * 100 : 0;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?= $normal_yuzde ?>%">
                                    Normal (<?= $normal_oran ?>)
                                </div>
                                <div class="progress-bar bg-warning" style="width: <?= $gecikme_yuzde ?>%">
                                    Gecikme (<?= $gecikme_oran ?>)
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="fw-bold">📊 Bu Ay Genel Durum:</h6>
                                <ul class="mb-0">
                                    <li>Ortalama çalışma: <?= number_format($calisma_istatistikleri['ortalama_saat'], 1) ?> saat</li>
                                    <li>En çok gecikme: <?= $calisma_istatistikleri['max_gecikme'] ?> dakika</li>
                                    <li>Toplam mesai: <?= number_format($calisma_istatistikleri['toplam_mesai'], 1) ?> saat</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">⚡ Hızlı İşlemler</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="exportCalismaRaporu()">
                                        <i class="fas fa-file-excel me-2"></i>Excel Rapor
                                    </button>
                                    <button class="btn btn-outline-success" onclick="mesaiHesapla()">
                                        <i class="fas fa-calculator me-2"></i>Mesai Hesapla
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="gecikmeUyarisi()">
                                        <i class="fas fa-bell me-2"></i>Gecikme Uyarısı
                                    </button>
                                    <button class="btn btn-outline-info" onclick="pdksEntegrasyon()">
                                        <i class="fas fa-sync me-2"></i>PDKS Senkronizasyon
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Aylık Özet Tablosu -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">📈 Bu Ay Performans</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Personel</th>
                                                <th>Ort. Saat</th>
                                                <th>Gecikme</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $top_performers = array_slice($aylik_ozet, 0, 5);
                                            foreach($top_performers as $performer): 
                                            ?>
                                                <tr>
                                                    <td>
                                                        <small><?= htmlspecialchars($performer['personel_adi']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $performer['ortalama_saat'] >= 8 ? 'success' : 'warning' ?>">
                                                            <?= number_format($performer['ortalama_saat'], 1) ?>h
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-<?= $performer['gecikme_sayisi'] == 0 ? 'success' : 'warning' ?>">
                                                            <?= $performer['gecikme_sayisi'] ?>
                                                        </small>
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

<!-- Yeni Kayıt Modal -->
<div class="modal fade" id="yeniKayitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Çalışma Kaydı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="giris_cikis_kaydet">
                    
                    <div class="mb-3">
                        <label class="form-label">Personel</label>
                        <select name="personel_id" class="form-select" required>
                            <option value="">Personel seçiniz</option>
                            <?php foreach ($personeller as $personel): ?>
                                <option value="<?= $personel['id'] ?>">
                                    <?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" name="tarih" class="form-control" value="<?= $tarih ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giriş Saati</label>
                            <input type="time" name="giris_saati" class="form-control time-input" value="09:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Çıkış Saati</label>
                            <input type="time" name="cikis_saati" class="form-control time-input" value="18:00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="3" 
                                  placeholder="Ek açıklama (opsiyonel)"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Mesai Kuralları:</strong><br>
                            • Normal çalışma: 09:00 - 18:00 (8 saat)<br>
                            • 15 dakika sonra gecikme sayılır<br>
                            • 8 saatten fazla mesai sayılır
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toplu Kayıt Modal -->
<div class="modal fade" id="topluKayitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>
                    Toplu Çalışma Kaydı
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="toplu_kayit">
                    
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tarih</label>
                            <input type="date" name="toplu_tarih" class="form-control" value="<?= $tarih ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ortak Giriş Saati</label>
                            <input type="time" name="toplu_giris" class="form-control time-input" value="09:00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ortak Çıkış Saati</label>
                            <input type="time" name="toplu_cikis" class="form-control time-input" value="18:00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Personel Seçimi</label>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">
                                    <i class="fas fa-check-double me-1"></i>Tümünü Seç
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectNone()">
                                    <i class="fas fa-times me-1"></i>Seçimi Temizle
                                </button>
                            </div>
                        </div>
                        
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <div class="row">
                                <?php foreach ($personeller as $personel): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input personel-checkbox" type="checkbox" 
                                                   name="personel_listesi[]" value="<?= $personel['id'] ?>"
                                                   id="personel_<?= $personel['id'] ?>">
                                            <label class="form-check-label d-flex align-items-center" for="personel_<?= $personel['id'] ?>">
                                                <img src="uploads/avatars/<?= $personel['avatar'] ?: 'default.png' ?>" 
                                                     class="rounded-circle me-2" width="25" height="25" alt="">
                                                <span><?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <small class="text-muted">
                                <span id="selectedCount">0</span> personel seçildi
                            </small>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Uyarı:</strong> Bu işlem seçilen tüm personel için aynı giriş/çıkış saatlerini kaydedecektir.
                        Mevcut kayıtlar varsa güncellenecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-users me-1"></i>Toplu Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kayıt Detay Modal -->
<div class="modal fade" id="detayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Çalışma Kaydı Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detayModalContent">
                <!-- AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<!-- Düzenleme Modal -->
<div class="modal fade" id="duzenleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Çalışma Kaydını Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="duzenleModalContent">
                <!-- AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer-scripts.php'; ?>

<script>
$(document).ready(function() {
    // DataTable başlatma
    $('#calismaKayitlariTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[0, "asc"]],
        "pageLength": 25,
        "responsive": true
    });
    
    // Personel seçim sayacı
    $('.personel-checkbox').change(function() {
        updateSelectedCount();
    });
    
    // Dijital saat başlatma
    updateDigitalClock();
    setInterval(updateDigitalClock, 1000);
});

// Toplu seçim fonksiyonları
function selectAll() {
    $('.personel-checkbox').prop('checked', true);
    updateSelectedCount();
}

function selectNone() {
    $('.personel-checkbox').prop('checked', false);
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = $('.personel-checkbox:checked').length;
    $('#selectedCount').text(count);
}

// Dijital saat güncellemesi
function updateDigitalClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('tr-TR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const dateString = now.toLocaleDateString('tr-TR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    $('#digitalClock').html(`
        <div>${timeString}</div>
        <small style="font-size: 0.6em; opacity: 0.8;">${dateString}</small>
    `);
}

// Kayıt düzenleme
function duzenleKayit(kayitId) {
    $('#duzenleModal').modal('show');
    $('#duzenleModalContent').html('<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Yükleniyor...</p></div>');
    
    $.get('ajax/calisma_kayit_duzenle.php', {id: kayitId}, function(data) {
        $('#duzenleModalContent').html(data);
    }).fail(function() {
        $('#duzenleModalContent').html('<div class="alert alert-danger">Kayıt yüklenemedi</div>');
    });
}

// Detay gösterme
function detayGoster(kayitId) {
    $('#detayModal').modal('show');
    $('#detayModalContent').html('<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Detaylar yükleniyor...</p></div>');
    
    $.get('ajax/calisma_kayit_detay.php', {id: kayitId}, function(data) {
        $('#detayModalContent').html(data);
    }).fail(function() {
        $('#detayModalContent').html('<div class="alert alert-danger">Detaylar yüklenemedi</div>');
    });
}

// Kayıt silme
function silKayit(kayitId) {
    if (confirm('Bu kaydı silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz.')) {
        $.post('ajax/calisma_kayit_sil.php', {
            id: kayitId
        }, function(response) {
            if (response.success) {
                showToast('Kayıt başarıyla silindi', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(response.message || 'Silme işleminde hata oluştu', 'error');
            }
        }, 'json').fail(function() {
            showToast('Bir hata oluştu', 'error');
        });
    }
}

// Excel export fonksiyonları
function exportGunlukRapor() {
    const tarih = '<?= $tarih ?>';
    window.location.href = `calisma_gunluk_excel.php?tarih=${tarih}`;
    showToast('Günlük rapor hazırlanıyor...', 'info');
}

function exportCalismaRaporu() {
    const ay = <?= $ay ?>;
    const yil = <?= $yil ?>;
    window.location.href = `calisma_excel_export.php?ay=${ay}&yil=${yil}`;
    showToast('Aylık Excel raporu hazırlanıyor...', 'info');
}

// Mesai hesaplama
function mesaiHesapla() {
    if (confirm('Bu ay için tüm personelin mesai saatleri yeniden hesaplanacak. Devam edilsin mi?')) {
        $.post('ajax/mesai_hesapla.php', {
            ay: <?= $ay ?>,
            yil: <?= $yil ?>
        }, function(response) {
            if (response.success) {
                showToast(`Mesai hesaplaması tamamlandı. ${response.personel_sayisi} personel güncellendi.`, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast(response.message || 'Mesai hesaplanırken hata oluştu', 'error');
            }
        }, 'json').fail(function() {
            showToast('Mesai hesaplama işleminde hata oluştu', 'error');
        });
    }
}

// Gecikme uyarısı
function gecikmeUyarisi() {
    if (confirm('Geciken personellere SMS/WhatsApp uyarısı gönderilecek. Onaylıyor musunuz?')) {
        $.post('ajax/gecikme_uyarisi.php', {
            tarih: '<?= $tarih ?>'
        }, function(response) {
            if (response.success) {
                showToast(`${response.gonderilen} personele uyarı gönderildi`, 'success');
            } else {
                showToast(response.message || 'Uyarı gönderilirken hata oluştu', 'error');
            }
        }, 'json').fail(function() {
            showToast('Uyarı gönderme işleminde hata oluştu', 'error');
        });
    }
}

// PDKS entegrasyonu
function pdksEntegrasyon() {
    showToast('PDKS senkronizasyonu başlatılıyor...', 'info');
    
    $.post('ajax/pdks_senkronizasyon.php', {
        tarih: '<?= $tarih ?>'
    }, function(response) {
        if (response.success) {
            showToast(`${response.senkronize_edilen} kayıt PDKS'den senkronize edildi`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast(response.message || 'PDKS senkronizasyonu başarısız', 'error');
        }
    }, 'json').fail(function() {
        showToast('PDKS bağlantı hatası', 'error');
    });
}

// Hızlı kayıt ekleme (Enter tuşu ile)
$(document).on('keypress', '.time-input', function(e) {
    if (e.which === 13) { // Enter tuşu
        $(this).closest('form').find('button[type="submit"]').click();
    }
});

// Otomatik form tamamlama
$('input[name="giris_saati"]').change(function() {
    const girisValue = $(this).val();
    const cikisInput = $('input[name="cikis_saati"]');
    
    if (girisValue && !cikisInput.val()) {
        // Giriş saatinden 8 saat sonrasını hesapla
        const giris = new Date(`2000-01-01 ${girisValue}`);
        giris.setHours(giris.getHours() + 8);
        const cikis = giris.toTimeString().slice(0, 5);
        cikisInput.val(cikis);
    }
});

// Toast mesajları
function showToast(message, type = 'info') {
    const toastClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    
    const toastHTML = `
        <div class="toast align-items-center text-white ${toastClass} border-0" role="alert" style="min-width: 300px;">
            <div class="d-flex">
                <div class="toast-body">
                    ${icon} ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    if (!$('#toast-container').length) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>');
    }
    
    const toastElement = $(toastHTML).appendTo('#toast-container');
    const toast = new bootstrap.Toast(toastElement[0], {
        autohide: true,
        delay: 4000
    });
    toast.show();
    
    toastElement.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Performans animasyonları
$(document).ready(function() {
    // Kartları sırayla animasyonla göster
    $('.time-card').each(function(index) {
        $(this).delay(index * 100).fadeIn(500);
    });
    
    // Progress barlarını animasyonla doldur
    setTimeout(() => {
        $('.progress-bar').each(function() {
            const width = $(this).css('width');
            $(this).css('width', '0').animate({
                width: width
            }, 1000);
        });
    }, 500);
});

// Keyboard shortcuts
$(document).keydown(function(e) {
    // Ctrl + N: Yeni kayıt
    if (e.ctrlKey && e.which === 78) {
        e.preventDefault();
        $('#yeniKayitModal').modal('show');
    }
    
    // Ctrl + T: Toplu kayıt
    if (e.ctrlKey && e.which === 84) {
        e.preventDefault();
        $('#topluKayitModal').modal('show');
    }
    
    // Ctrl + E: Excel export
    if (e.ctrlKey && e.which === 69) {
        e.preventDefault();
        exportCalismaRaporu();
    }
});

// Sayfa yüklendiğinde bilgilendirme
$(document).ready(function() {
    if (<?= count($gunluk_kayitlar) ?> === 0) {
        setTimeout(() => {
            showToast('Bu tarih için henüz çalışma kaydı bulunmuyor. Toplu kayıt ile hızlıca oluşturabilirsiniz.', 'info');
        }, 1000);
    }
});

// Modal açılma olayları
$('#yeniKayitModal').on('shown.bs.modal', function() {
    $(this).find('select[name="personel_id"]').focus();
});

$('#topluKayitModal').on('shown.bs.modal', function() {
    updateSelectedCount();
});

// Form validasyonu
$('form').submit(function(e) {
    const giris = $(this).find('input[name="giris_saati"]').val();
    const cikis = $(this).find('input[name="cikis_saati"]').val();
    
    if (giris && cikis && giris >= cikis) {
        e.preventDefault();
        showToast('Çıkış saati giriş saatinden sonra olmalıdır!', 'error');
        return false;
    }
});
</script>

<style>
/* Responsive iyileştirmeler */
@media (max-width: 768px) {
    .shift-selector .row {
        gap: 10px;
    }
    
    .time-display {
        font-size: 1em;
    }
    
    .digital-clock {
        font-size: 1.2em;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
    
    .attendance-widget {
        margin-bottom: 15px;
    }
}

/* Animasyonlar */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.time-card {
    animation: fadeInUp 0.5s ease-out;
}

/* Print stilleri */
@media print {
    .btn, .modal, .shift-selector .btn, .card-header .btn {
        display: none !important;
    }
    
    .time-card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    body {
        background: white !important;
    }
    
    .table {
        font-size: 12px;
    }
}

/* Loading animasyonu */
.spinner-border {
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}
</style>

<?php include 'partials/footer.php'; ?>