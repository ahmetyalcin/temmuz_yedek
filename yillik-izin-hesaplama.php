<?php
// yillik-izin-hesaplama.php - Yıllık izin hakkı otomatik hesaplama ve takip sistemi



session_start();
require_once 'functions.php';



/*
// Yetki kontrolü
if (!in_array($_SESSION['rol'], ['yonetici', 'ik', 'sekreter'])) {
    header('Location: unauthorized.php');
    exit;
}
*/
// İşlemler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'hesapla_tum_izinler') {
        $yil = $_POST['yil'];
        $guncellenen = updateAllIzinHakki($yil);
        $success_msg = "{$guncellenen} personelin izin hakkı güncellendi.";
    } elseif ($_POST['action'] == 'manuel_guncelle') {
        $personel_id = $_POST['personel_id'];
        $yil = $_POST['yil'];
        $yeni_hak = $_POST['yeni_hak'];
        if (manuelIzinHakGuncelle($personel_id, $yil, $yeni_hak)) {
            $success_msg = "İzin hakkı manuel olarak güncellendi.";
        }
    }
}

$yil = $_GET['yil'] ?? date('Y');
$izin_durumlari = getPersonelIzinDurumlari($yil);
$izin_istatistikleri = getIzinIstatistikleri($yil);

$title = 'Yıllık İzin Hesaplama';
include __DIR__ . '/partials/header.php';
?>

<style>
.izin-card {
    border-radius: 12px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.izin-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.izin-bar {
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
    overflow: hidden;
    margin: 8px 0;
}

.izin-progress {
    height: 100%;
    transition: width 0.6s ease;
}

.status-normal { background: linear-gradient(90deg, #28a745, #20c997); }
.status-warning { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.status-danger { background: linear-gradient(90deg, #dc3545, #e83e8c); }

.personel-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.stat-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 25px;
    text-align: center;
}

.calculation-panel {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
    border-radius: 15px;
    color: #333;
}

.year-selector {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
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

                <!-- Yıl Seçici ve Toplu İşlemler -->
                <div class="year-selector">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <form method="GET" class="d-flex">
                                <select name="yil" class="form-select me-2">
                                    <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                        <option value="<?= $y ?>" <?= $yil == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">Göster</button>
                            </form>
                        </div>
                        <div class="col-md-6 text-center">
                            <h4 class="mb-0"><?= $yil ?> Yılı İzin Durumu</h4>
                        </div>
                        <div class="col-md-3 text-end">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="hesapla_tum_izinler">
                                <input type="hidden" name="yil" value="<?= $yil ?>">
                                <button type="submit" class="btn btn-success" 
                                        onclick="return confirm('Tüm personelin izin hakları yeniden hesaplanacak. Emin misiniz?')">
                                    <i class="fas fa-calculator me-1"></i> Toplu Hesapla
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-widget">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h3><?= $izin_istatistikleri['toplam_personel'] ?></h3>
                            <p class="mb-0">Toplam Personel</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white izin-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                <h3><?= $izin_istatistikleri['toplam_hak'] ?></h3>
                                <p class="mb-0">Toplam İzin Hakkı</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white izin-card">
                            <div class="card-body text-center">
                                <i class="fas fa-plane-departure fa-2x mb-2"></i>
                                <h3><?= $izin_istatistikleri['kullanilan'] ?></h3>
                                <p class="mb-0">Kullanılan İzin</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white izin-card">
                            <div class="card-body text-center">
                                <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                                <h3><?= $izin_istatistikleri['kalan'] ?></h3>
                                <p class="mb-0">Kalan İzin</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Personel İzin Durumları -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar text-primary me-2"></i>
                                    Personel İzin Durumları
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="izinDurumlariTable">
                                        <thead>
                                            <tr>
                                                <th>Personel</th>
                                                <th>Çalışma Yılı</th>
                                                <th>İzin Hakkı</th>
                                                <th>Kullanılan</th>
                                                <th>Kalan</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($izin_durumlari as $durum): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="uploads/avatars/<?= $durum['avatar'] ?: 'default.png' ?>" 
                                                                 class="personel-avatar me-3" alt="Avatar">
                                                            <div>
                                                                <strong><?= htmlspecialchars($durum['personel_adi']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($durum['sicil_no']) ?></small>
                                                                <br><small class="text-info"><?= htmlspecialchars($durum['departman']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= $durum['calisma_yili'] ?> yıl</span>
                                                        <br><small class="text-muted">
                                                            Başlama: <?= date('d.m.Y', strtotime($durum['ise_baslama'])) ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-primary"><?= $durum['yillik_hak'] ?> gün</span>
                                                        <?php if($durum['devredilen'] > 0): ?>
                                                            <br><small class="text-success">+<?= $durum['devredilen'] ?> devir</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning fw-bold"><?= $durum['kullanilan'] ?> gün</span>
                                                        <?php
                                                        $kullanim_orani = $durum['yillik_hak'] > 0 ? ($durum['kullanilan'] / $durum['yillik_hak']) * 100 : 0;
                                                        ?>
                                                        <div class="izin-bar">
                                                            <div class="izin-progress status-warning" 
                                                                 style="width: <?= min($kullanim_orani, 100) ?>%"></div>
                                                        </div>
                                                        <small class="text-muted"><?= round($kullanim_orani) ?>%</small>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success"><?= $durum['kalan'] ?> gün</span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $kalan_oran = $durum['yillik_hak'] > 0 ? ($durum['kalan'] / $durum['yillik_hak']) * 100 : 0;
                                                        if($kalan_oran > 50): ?>
                                                            <span class="badge bg-success">✅ Normal</span>
                                                        <?php elseif($kalan_oran > 20): ?>
                                                            <span class="badge bg-warning">⚠️ Dikkat</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">🚨 Kritik</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-info btn-sm" 
                                                                    onclick="izinDetayGoster('<?= $durum['personel_id'] ?>', <?= $yil ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-warning btn-sm" 
                                                                    onclick="manuelGuncelle('<?= $durum['personel_id'] ?>', '<?= $durum['personel_adi'] ?>', <?= $durum['yillik_hak'] ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-primary btn-sm" 
                                                                    onclick="izinPlanlama('<?= $durum['personel_id'] ?>')">
                                                                <i class="fas fa-calendar-plus"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hesaplama Paneli -->
                    <div class="col-lg-4">
                        <div class="card calculation-panel">
                            <div class="card-header border-0">
                                <h5 class="text-dark mb-0">
                                    <i class="fas fa-calculator me-2"></i>
                                    İzin Hakkı Hesaplama Kuralları
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6 class="fw-bold">📋 Türk İş Kanunu'na Göre:</h6>
                                    <ul class="mb-0">
                                        <li><strong>1-5 yıl:</strong> 14 gün</li>
                                        <li><strong>5-15 yıl:</strong> 20 gün</li>
                                        <li><strong>15+ yıl:</strong> 26 gün</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6 class="fw-bold">⚠️ Özel Durumlar:</h6>
                                    <ul class="mb-0">
                                        <li>18 yaş altı: +6 gün</li>
                                        <li>50+ yaş: +4 gün</li>
                                        <li>Engelli: +6 gün</li>
                                    </ul>
                                </div>

                                <div class="mt-3">
                                    <h6 class="fw-bold">📊 Bu Yıl Özeti:</h6>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="text-success"><?= round($izin_istatistikleri['ortalama_kullanim']) ?>%</h4>
                                            <small>Ortalama Kullanım</small>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-primary"><?= $izin_istatistikleri['kritik_personel'] ?></h4>
                                            <small>Kritik Durum</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kritik Durumlar -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">🚨 Dikkat Gereken Durumlar</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $kritik_durumlar = array_filter($izin_durumlari, function($d) {
                                    return ($d['kalan'] / max($d['yillik_hak'], 1)) < 0.2;
                                });
                                ?>
                                
                                <?php if(empty($kritik_durumlar)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                        <p class="mb-0">Tüm personel normale durumda!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($kritik_durumlar as $kritik): ?>
                                        <div class="alert alert-danger py-2 mb-2">
                                            <strong><?= htmlspecialchars($kritik['personel_adi']) ?></strong>
                                            <br><small>Sadece <?= $kritik['kalan'] ?> gün kaldı!</small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">⚡ Hızlı İşlemler</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="exportIzinRaporu()">
                                        <i class="fas fa-file-excel me-2"></i>Excel Rapor
                                    </button>
                                    <button class="btn btn-outline-success" onclick="izinUyariGonder()">
                                        <i class="fas fa-bell me-2"></i>Uyarı Gönder
                                    </button>
                                    <button class="btn btn-outline-info" onclick="yilSonuDevir()">
                                        <i class="fas fa-forward me-2"></i>Yıl Sonu Devir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manuel Güncelleme Modal -->
<div class="modal fade" id="manuelGuncellemeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manuel İzin Hakkı Güncelleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="manuelGuncellemeForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="manuel_guncelle">
                    <input type="hidden" name="personel_id" id="manuelPersonelId">
                    <input type="hidden" name="yil" value="<?= $yil ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Personel</label>
                        <input type="text" class="form-control" id="manuelPersonelAdi" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mevcut İzin Hakkı</label>
                        <input type="number" class="form-control" id="mevcutHak" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Yeni İzin Hakkı</label>
                        <input type="number" name="yeni_hak" class="form-control" id="yeniHak" min="0" max="50" required>
                        <div class="form-text">Özel durumlar için manuel ayarlama</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Güncelleme Nedeni</label>
                        <textarea name="guncelleme_nedeni" class="form-control" rows="3" 
                                  placeholder="Neden manuel güncelleme yapıldığını açıklayın..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- İzin Detay Modal -->
<div class="modal fade" id="izinDetayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İzin Kullanım Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="izinDetayIcerik">
                <!-- AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer-scripts.php'; ?>

<script>
$(document).ready(function() {
    $('#izinDurumlariTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[5, "asc"], [4, "asc"]], // Durum ve kalan güne göre sırala
        "pageLength": 25
    });
});

// Manuel güncelleme
function manuelGuncelle(personelId, personelAdi, mevcutHak) {
    $('#manuelPersonelId').val(personelId);
    $('#manuelPersonelAdi').val(personelAdi);
    $('#mevcutHak').val(mevcutHak);
    $('#yeniHak').val(mevcutHak);
    $('#manuelGuncellemeModal').modal('show');
}

// İzin detayları göster
function izinDetayGoster(personelId, yil) {
    $('#izinDetayModal').modal('show');
    $('#izinDetayIcerik').html('<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Detaylar yükleniyor...</p></div>');
    
    $.get('ajax/izin_detay_rapor.php', {
        personel_id: personelId,
        yil: yil
    }, function(data) {
        $('#izinDetayIcerik').html(data);
    }).fail(function() {
        $('#izinDetayIcerik').html('<div class="alert alert-danger">Detaylar yüklenemedi</div>');
    });
}

// İzin planlaması
function izinPlanlama(personelId) {
    window.location.href = `izin-planlama.php?personel_id=${personelId}`;
}

// Excel rapor export
function exportIzinRaporu() {
    const yil = <?= $yil ?>;
    window.location.href = `izin_excel_export.php?yil=${yil}`;
    showToast('Excel raporu hazırlanıyor...', 'info');
}

// İzin uyarıları gönder
function izinUyariGonder() {
    if (confirm('Kritik durumdaki personellere izin uyarısı gönderilecek. Onaylıyor musunuz?')) {
        $.post('ajax/izin_uyari_gonder.php', {
            yil: <?= $yil ?>,
            action: 'kritik_uyari'
        }, function(response) {
            if (response.success) {
                showToast(`${response.gonderilen} personele uyarı gönderildi`, 'success');
            } else {
                showToast(response.message || 'Uyarı gönderilirken hata oluştu', 'error');
            }
        }, 'json');
    }
}

// Yıl sonu devir işlemi
function yilSonuDevir() {
    if (confirm('Yıl sonu devir işlemi yapılacak. Bu işlem geri alınamaz. Emin misiniz?')) {
        $.post('ajax/yil_sonu_devir.php', {
            yil: <?= $yil ?>,
            action: 'devir'
        }, function(response) {
            if (response.success) {
                showToast('Yıl sonu devir işlemi tamamlandı', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast(response.message || 'Devir işlemi sırasında hata oluştu', 'error');
            }
        }, 'json');
    }
}

// Toast mesajları
function showToast(message, type = 'info') {
    const toastClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    
    const toastHTML = `
        <div class="toast align-items-center text-white ${toastClass} border-0" role="alert">
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
</script>

<?php include 'partials/footer.php'; ?>