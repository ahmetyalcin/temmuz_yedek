<?php
// personel-giris-cikis-basit.php - Basit Personel Giriş-Çıkış Sistemi
session_start();
require_once 'con/db.php';
require_once 'functions.php';

$title = 'Personel Giriş-Çıkış';

// İşlemler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if ($_POST['action'] == 'giris') {
        $personel_id = $_POST['personel_id'];
        $tarih = date('Y-m-d');
        $saat = date('H:i:s');
        
        // Bugün zaten giriş yapmış mı kontrol et
        $sql = "SELECT id FROM personel_giris_cikis WHERE personel_id = ? AND tarih = ? AND giris_saati IS NOT NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personel_id, $tarih]);
        
        if ($stmt->fetch()) {
            $response['message'] = 'Bugün zaten giriş yapmışsınız!';
        } else {
            // Giriş kaydı yap
            $sql = "INSERT INTO personel_giris_cikis (id, personel_id, tarih, giris_saati, kayit_tipi) 
                    VALUES (UUID(), ?, ?, ?, 'manuel')";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$personel_id, $tarih, $saat])) {
                $response = ['success' => true, 'message' => 'Giriş kaydınız yapıldı: ' . date('H:i')];
            }
        }
    }
    
    if ($_POST['action'] == 'cikis') {
        $personel_id = $_POST['personel_id'];
        $tarih = date('Y-m-d');
        $saat = date('H:i:s');
        
        // Bugün giriş yapmış mı kontrol et
        $sql = "SELECT id, giris_saati FROM personel_giris_cikis WHERE personel_id = ? AND tarih = ? AND giris_saati IS NOT NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personel_id, $tarih]);
        $kayit = $stmt->fetch();
        
        if (!$kayit) {
            $response['message'] = 'Önce giriş yapmalısınız!';
        } else if ($kayit && isset($kayit['cikis_saati']) && $kayit['cikis_saati']) {
            $response['message'] = 'Bugün zaten çıkış yapmışsınız!';
        } else {
            // Toplam saat hesapla
            $giris = new DateTime($kayit['giris_saati']);
            $cikis = new DateTime($saat);
            $toplam_saat = $cikis->diff($giris);
            $toplam_dakika = ($toplam_saat->h * 60) + $toplam_saat->i;
            $toplam_saat_decimal = $toplam_dakika / 60;
            
            // Çıkış kaydı yap
            $sql = "UPDATE personel_giris_cikis SET cikis_saati = ?, toplam_calisma_saati = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$saat, $toplam_saat_decimal, $kayit['id']])) {
                $response = ['success' => true, 'message' => 'Çıkış kaydınız yapıldı: ' . date('H:i') . ' (Toplam: ' . number_format($toplam_saat_decimal, 1) . ' saat)'];
            }
        }
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Bugünkü kayıtları getir
$bugun = date('Y-m-d');
$sql = "SELECT pgc.*, CONCAT(p.ad, ' ', p.soyad) as personel_adi, p.sicil_no
        FROM personel_giris_cikis pgc
        JOIN personel p ON p.id = pgc.personel_id
        WHERE pgc.tarih = ?
        ORDER BY pgc.giris_saati DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$bugun]);
$bugun_kayitlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktif personelleri getir
$sql = "SELECT id, CONCAT(ad, ' ', soyad) as ad_soyad, sicil_no FROM personel WHERE aktif = 1 ORDER BY ad, soyad";
$personeller = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include 'partials/header.php';
?>

<style>
.time-card {
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
    transition: all 0.3s ease;
}

.time-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.big-clock {
    font-family: 'Courier New', monospace;
    font-size: 3em;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 20px rgba(255,255,255,0.5);
}

.date-display {
    font-size: 1.2em;
    opacity: 0.9;
}

.entry-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    text-align: center;
}

.btn-giris {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 25px;
    padding: 15px 30px;
    font-size: 1.1em;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-giris:hover {
    transform: scale(1.05);
    color: white;
}

.btn-cikis {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    border: none;
    border-radius: 25px;
    padding: 15px 30px;
    font-size: 1.1em;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-cikis:hover {
    transform: scale(1.05);
    color: white;
}

.status-giris {
    background: #d4edda;
    color: #155724;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.status-cikis {
    background: #f8d7da;
    color: #721c24;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.status-tamamlandi {
    background: #cce5ff;
    color: #004085;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.record-item {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.record-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.time-display {
    font-family: 'Courier New', monospace;
    font-size: 1.1em;
    font-weight: bold;
}
</style>

<div class="page-content">
    <div class="container-fluid">
        
        <!-- Sayfa Başlığı -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">
                        <i class="fas fa-clock me-2"></i>Personel Giriş-Çıkış
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Giriş-Çıkış</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saat ve Giriş-Çıkış Formu -->
        <div class="row">
            <div class="col-12">
                <div class="entry-header">
                    <div class="big-clock" id="liveClock"></div>
                    <div class="date-display" id="liveDate"></div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-3">
                            <select id="personelSelect" class="form-select form-select-lg mb-3" style="background: rgba(255,255,255,0.9);">
                                <option value="">Personel Seçin</option>
                                <?php foreach ($personeller as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ad_soyad']) ?> (<?= $p['sicil_no'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="d-flex gap-3 justify-content-center">
                                <button class="btn btn-giris" onclick="girisYap()">
                                    <i class="fas fa-sign-in-alt me-2"></i>GİRİŞ
                                </button>
                                <button class="btn btn-cikis" onclick="cikisYap()">
                                    <i class="fas fa-sign-out-alt me-2"></i>ÇIKIŞ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bugünkü Kayıtlar -->
        <div class="row">
            <div class="col-12">
                <div class="card time-card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-day me-2"></i>Bugünkü Kayıtlar (<?= date('d.m.Y') ?>)
                                </h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="badge bg-primary">
                                    Toplam: <?= count($bugun_kayitlar) ?> kayıt
                                </span>
                                <a href="personel-rapor-basit.php" class="btn btn-success btn-sm ms-2">
                                    <i class="fas fa-chart-bar me-1"></i>Raporlar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bugun_kayitlar)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h5>Bugün henüz kayıt bulunmuyor</h5>
                                <p>Yukarıdaki formdan giriş-çıkış kaydı yapabilirsiniz</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($bugun_kayitlar as $kayit): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="record-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($kayit['personel_adi']) ?></h6>
                                                    <small class="text-muted">Sicil: <?= $kayit['sicil_no'] ?></small>
                                                </div>
                                                <div>
                                                    <?php if ($kayit['cikis_saati']): ?>
                                                        <span class="status-tamamlandi">Tamamlandı</span>
                                                    <?php else: ?>
                                                        <span class="status-giris">İçeride</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Giriş</small>
                                                    <div class="time-display text-success">
                                                        <?= $kayit['giris_saati'] ? date('H:i', strtotime($kayit['giris_saati'])) : '-' ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Çıkış</small>
                                                    <div class="time-display text-danger">
                                                        <?= $kayit['cikis_saati'] ? date('H:i', strtotime($kayit['cikis_saati'])) : '-' ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($kayit['toplam_calisma_saati']): ?>
                                                <div class="mt-2 pt-2 border-top">
                                                    <small class="text-muted">Toplam Çalışma: </small>
                                                    <strong><?= number_format($kayit['toplam_calisma_saati'], 1) ?> saat</strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı İstatistikler -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card time-card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h3><?= count($bugun_kayitlar) ?></h3>
                        <p class="mb-0 text-muted">Toplam Kayıt</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card time-card text-center">
                    <div class="card-body">
                        <i class="fas fa-sign-in-alt fa-2x text-success mb-2"></i>
                        <h3><?= count(array_filter($bugun_kayitlar, function($k) { return $k['giris_saati'] && !$k['cikis_saati']; })) ?></h3>
                        <p class="mb-0 text-muted">İçeride</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card time-card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                        <h3><?= count(array_filter($bugun_kayitlar, function($k) { return $k['cikis_saati']; })) ?></h3>
                        <p class="mb-0 text-muted">Tamamlanan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card time-card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h3><?= number_format(array_sum(array_column($bugun_kayitlar, 'toplam_calisma_saati')), 1) ?></h3>
                        <p class="mb-0 text-muted">Toplam Saat</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Canlı saat
function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString('tr-TR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const date = now.toLocaleDateString('tr-TR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    document.getElementById('liveClock').textContent = time;
    document.getElementById('liveDate').textContent = date;
}

// Saati başlat
setInterval(updateClock, 1000);
updateClock();

// Giriş yapma
function girisYap() {
    const personelId = document.getElementById('personelSelect').value;
    if (!personelId) {
        alert('Lütfen personel seçin!');
        return;
    }
    
    if (confirm('Giriş kaydı yapılacak. Onaylıyor musunuz?')) {
        $.post('personel-giris-cikis-basit.php', {
            action: 'giris',
            personel_id: personelId,
            ajax: 1
        }, function(response) {
            if (response.success) {
                showMessage(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(response.message, 'error');
            }
        }, 'json').fail(function() {
            showMessage('Bir hata oluştu!', 'error');
        });
    }
}

// Çıkış yapma
function cikisYap() {
    const personelId = document.getElementById('personelSelect').value;
    if (!personelId) {
        alert('Lütfen personel seçin!');
        return;
    }
    
    if (confirm('Çıkış kaydı yapılacak. Onaylıyor musunuz?')) {
        $.post('personel-giris-cikis-basit.php', {
            action: 'cikis',
            personel_id: personelId,
            ajax: 1
        }, function(response) {
            if (response.success) {
                showMessage(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(response.message, 'error');
            }
        }, 'json').fail(function() {
            showMessage('Bir hata oluştu!', 'error');
        });
    }
}

// Mesaj gösterme
function showMessage(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
    
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(alert);
    setTimeout(() => alert.remove(), 5000);
}

// Enter tuşu ile personel seçimi
$('#personelSelect').keypress(function(e) {
    if (e.which === 13) {
        girisYap();
    }
});
</script>

<?php include 'partials/footer.php'; ?>