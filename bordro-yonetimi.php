<?php
// bordro-yonetimi.php - Muhasebe için bordro kontrolü
session_start();
require_once 'functions.php';
/*
// Yetki kontrolü - sadece muhasebe ve yönetici
if (!in_array($_SESSION['rol'], ['muhasebe', 'yonetici'])) {
    header('Location: unauthorized.php');
    exit;
}
*/
// Ay ve yıl parametreleri
$ay = $_GET['ay'] ?? date('m');
$yil = $_GET['yil'] ?? date('Y');

// Bordro işlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'bordro_olustur') {
        $personel_id = $_POST['personel_id'];
        createBordro($personel_id, $ay, $yil);
        $success_msg = "Bordro başarıyla oluşturuldu.";
    } elseif ($_POST['action'] == 'bordro_onayla') {
        $bordro_id = $_POST['bordro_id'];
        approveBordro($bordro_id);
        $success_msg = "Bordro onaylandı.";
    } elseif ($_POST['action'] == 'toplu_bordro') {
        createAllBordrolar($ay, $yil);
        $success_msg = "Tüm bordrolar oluşturuldu.";
    }
}

// Verileri getir
$personeller = getPersonellerWithMaas();
$bordrolar = getBordrolar($ay, $yil);
$bordro_istatistik = getBordroIstatistik($ay, $yil);

$title = 'Bordro Yönetimi';
include __DIR__ . '/partials/header.php';
?>

<style>
.bordro-card {
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.bordro-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.bordro-taslak { border-left: 5px solid #ffc107; }
.bordro-onaylandi { border-left: 5px solid #28a745; }
.bordro-odendi { border-left: 5px solid #007bff; }

.salary-amount {
    font-size: 1.2em;
    font-weight: bold;
    color: #2c3e50;
}

.stat-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    text-align: center;
}

.month-selector {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}

.approval-panel {
    background: linear-gradient(135deg, #ff7b7b 0%, #667eea 100%);
    color: white;
    border-radius: 15px;
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

                <!-- Ay/Yıl Seçici -->
                <div class="month-selector">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ay</label>
                            <select name="ay" class="form-select">
                                <?php 
                                $aylar = [
                                    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                ];
                                foreach($aylar as $num => $isim): ?>
                                    <option value="<?= $num ?>" <?= $ay == $num ? 'selected' : '' ?>>
                                        <?= $isim ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Yıl</label>
                            <select name="yil" class="form-select">
                                <?php for($y = date('Y'); $y >= date('Y')-3; $y--): ?>
                                    <option value="<?= $y ?>" <?= $yil == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> Görüntüle
                            </button>
                        </div>
                        <div class="col-md-3 text-end">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toplu_bordro">
                                <input type="hidden" name="ay" value="<?= $ay ?>">
                                <input type="hidden" name="yil" value="<?= $yil ?>">
                                <button type="submit" class="btn btn-success" 
                                        onclick="return confirm('Tüm personel için bordro oluşturulacak. Emin misiniz?')">
                                    <i class="fas fa-plus-circle me-1"></i> Toplu Bordro Oluştur
                                </button>
                            </form>
                        </div>
                    </form>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-widget">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h3><?= $bordro_istatistik['toplam_personel'] ?></h3>
                            <p class="mb-0">Toplam Personel</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white bordro-card">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3><?= $bordro_istatistik['taslak_bordro'] ?></h3>
                                <p class="mb-0">Taslak Bordro</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white bordro-card">
                            <div class="card-body text-center">
                                <i class="fas fa-check fa-2x mb-2"></i>
                                <h3><?= $bordro_istatistik['onaylanan_bordro'] ?></h3>
                                <p class="mb-0">Onaylanan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white bordro-card">
                            <div class="card-body text-center">
                                <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                                <h3><?= number_format($bordro_istatistik['toplam_net'], 0, ',', '.') ?> ₺</h3>
                                <p class="mb-0">Toplam Net Ödeme</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Bordro Listesi -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                                    <?= $aylar[$ay] ?> <?= $yil ?> Bordroları
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="bordroTable">
                                        <thead>
                                            <tr>
                                                <th>Personel</th>
                                                <th>Brüt Maaş</th>
                                                <th>Kesintiler</th>
                                                <th>Net Ödeme</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($personeller as $personel): ?>
                                                <?php 
                                                $bordro = array_filter($bordrolar, function($b) use ($personel) {
                                                    return $b['personel_id'] == $personel['id'];
                                                });
                                                $bordro = reset($bordro); // İlk elemanı al
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="uploads/avatars/<?= $personel['avatar'] ?: 'default.png' ?>" 
                                                                 class="rounded-circle me-2" width="40" height="40" alt="">
                                                            <div>
                                                                <strong><?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($personel['sicil_no']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="salary-amount">
                                                            <?= number_format($personel['brut_maas'], 2, ',', '.') ?> ₺
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($bordro): ?>
                                                            <span class="text-danger">
                                                                <?= number_format($bordro['toplam_kesinti'], 2, ',', '.') ?> ₺
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($bordro): ?>
                                                            <span class="salary-amount text-success">
                                                                <?= number_format($bordro['net_odeme'], 2, ',', '.') ?> ₺
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Hesaplanmadı</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($bordro): ?>
                                                            <?php if ($bordro['durum'] == 'taslak'): ?>
                                                                <span class="badge bg-warning">📄 Taslak</span>
                                                            <?php elseif ($bordro['durum'] == 'onaylandi'): ?>
                                                                <span class="badge bg-success">✅ Onaylandı</span>
                                                            <?php elseif ($bordro['durum'] == 'odendi'): ?>
                                                                <span class="badge bg-info">💰 Ödendi</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">❌ Bordro Yok</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <?php if ($bordro): ?>
                                                                <?php if ($bordro['durum'] == 'taslak'): ?>
                                                                    <button class="btn btn-success btn-sm" 
                                                                            onclick="bordroOnayla('<?= $bordro['id'] ?>')">
                                                                        <i class="fas fa-check"></i> Onayla
                                                                    </button>
                                                                <?php endif; ?>
                                                                <button class="btn btn-info btn-sm" 
                                                                        onclick="bordroDetay('<?= $bordro['id'] ?>')">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-primary btn-sm" 
                                                                        onclick="bordroPrint('<?= $bordro['id'] ?>')">
                                                                    <i class="fas fa-print"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="bordro_olustur">
                                                                    <input type="hidden" name="personel_id" value="<?= $personel['id'] ?>">
                                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                        <i class="fas fa-plus"></i> Oluştur
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
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

                    <!-- Onay Paneli -->
                    <div class="col-lg-4">
                        <div class="card approval-panel">
                            <div class="card-header border-0">
                                <h5 class="text-white mb-0">
                                    <i class="fas fa-clipboard-check me-2"></i>
                                    Onay Bekleyen İşlemler
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $bekleyen_bordrolar = array_filter($bordrolar, function($b) {
                                    return $b['durum'] == 'taslak';
                                });
                                ?>
                                
                                <?php if (empty($bekleyen_bordrolar)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p class="mb-0">Tüm bordrolar kontrol edildi!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3">
                                        <h6 class="text-white">📋 Kontrol Listesi</h6>
                                        <div class="list-group">
                                            <?php foreach($bekleyen_bordrolar as $bordro): ?>
                                                <div class="list-group-item bg-transparent border-light">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong class="text-white"><?= htmlspecialchars($bordro['personel_adi']) ?></strong>
                                                            <br><small class="text-light">Net: <?= number_format($bordro['net_odeme'], 0, ',', '.') ?> ₺</small>
                                                        </div>
                                                        <button class="btn btn-sm btn-outline-light" 
                                                                onclick="bordroDetay('<?= $bordro['id'] ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-light">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong><?= count($bekleyen_bordrolar) ?></strong> bordro onay bekliyor.
                                        Maaşlar ödenmeden önce kontrol ediniz.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- İzin Durumu Özeti -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-times text-warning me-2"></i>
                                    Bu Ay İzin Durumu
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php $izin_ozet = getAylikIzinOzeti($ay, $yil); ?>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-success"><?= $izin_ozet['ucretli'] ?></h4>
                                        <small>Ücretli İzin</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-warning"><?= $izin_ozet['ucretsiz'] ?></h4>
                                        <small>Ücretsiz İzin</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <h5 class="text-primary"><?= $izin_ozet['toplam_gun'] ?></h5>
                                    <small>Toplam İzin Günü</small>
                                </div>
                            </div>
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-bolt text-info me-2"></i>
                                    Hızlı İşlemler
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="exportBordrolar()">
                                        <i class="fas fa-file-excel me-2"></i>Excel'e Aktar
                                    </button>
                                    <button class="btn btn-outline-success" onclick="emailBordrolar()">
                                        <i class="fas fa-envelope me-2"></i>E-posta Gönder
                                    </button>
                                    <button class="btn btn-outline-info" onclick="raporOlustur()">
                                        <i class="fas fa-chart-bar me-2"></i>Maaş Raporu
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

<!-- Bordro Detay Modal -->
<div class="modal fade" id="bordroDetayModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bordro Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bordroDetayIcerik">
                <!-- Bordro detayları AJAX ile yüklenecek -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" onclick="bordroPrintFromModal()">
                    <i class="fas fa-print me-1"></i>Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer-scripts.php'; ?>

<script>
let currentBordroId = null;

$(document).ready(function() {
    // DataTable
    $('#bordroTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});

// Bordro onaylama
function bordroOnayla(bordroId) {
    if (confirm('Bu bordroyu onaylamak istediğinize emin misiniz?\n\nOnaylandıktan sonra değişiklik yapamazsınız.')) {
        $.post('ajax/bordro_onayla.php', {
            bordro_id: bordroId,
            action: 'onayla'
        }, function(response) {
            if (response.success) {
                showToast('Bordro başarıyla onaylandı', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(response.message || 'Bordro onaylanırken hata oluştu', 'error');
            }
        }, 'json').fail(function() {
            showToast('Bir hata oluştu', 'error');
        });
    }
}

// Bordro detayları
function bordroDetay(bordroId) {
    currentBordroId = bordroId;
    $('#bordroDetayModal').modal('show');
    $('#bordroDetayIcerik').html('<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Bordro detayları yükleniyor...</p></div>');
    
    $.get('ajax/bordro_detay.php', {id: bordroId}, function(data) {
        $('#bordroDetayIcerik').html(data);
    }).fail(function() {
        $('#bordroDetayIcerik').html('<div class="alert alert-danger">Bordro detayları yüklenemedi</div>');
    });
}

// Bordro yazdırma
function bordroPrint(bordroId) {
    window.open(`bordro_print.php?id=${bordroId}`, '_blank');
}

function bordroPrintFromModal() {
    if (currentBordroId) {
        bordroPrint(currentBordroId);
    }
}

// Excel export
function exportBordrolar() {
    const ay = <?= $ay ?>;
    const yil = <?= $yil ?>;
    window.location.href = `bordro_excel_export.php?ay=${ay}&yil=${yil}`;
    showToast('Excel dosyası hazırlanıyor...', 'info');
}

// E-posta gönderme
function emailBordrolar() {
    if (confirm('Onaylanan bordroları personellere e-posta ile göndermek istediğinize emin misiniz?')) {
        $.post('ajax/bordro_email.php', {
            ay: <?= $ay ?>,
            yil: <?= $yil ?>,
            action: 'send_all'
        }, function(response) {
            if (response.success) {
                showToast(`${response.sent_count} bordro e-posta ile gönderildi`, 'success');
            } else {
                showToast(response.message || 'E-posta gönderilirken hata oluştu', 'error');
            }
        }, 'json');
    }
}

// Rapor oluşturma
function raporOlustur() {
    const ay = <?= $ay ?>;
    const yil = <?= $yil ?>;
    window.open(`bordro_rapor.php?ay=${ay}&yil=${yil}`, '_blank');
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

// Bordro durumu renklendirme
$(document).ready(function() {
    $('.bordro-card').each(function() {
        const durum = $(this).find('.badge').text().toLowerCase();
        if (durum.includes('taslak')) {
            $(this).addClass('bordro-taslak');
        } else if (durum.includes('onaylandı')) {
            $(this).addClass('bordro-onaylandi');
        } else if (durum.includes('ödendi')) {
            $(this).addClass('bordro-odendi');
        }
    });
});
</script>

<?php include 'partials/footer.php'; ?>