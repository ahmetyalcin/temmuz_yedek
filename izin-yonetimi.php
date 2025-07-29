<?php
// izin-yonetimi.php - Sekreter için izin giriş sayfası
session_start();
require_once 'functions.php';
/*
// Yetki kontrolü - sekreter ve yönetici girebilir
if (!in_array($_SESSION['rol'], ['sekreter', 'yonetici', 'satis'])) {
    header('Location: unauthorized.php');
    exit;
}
*/

// İzin kaydetme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'izin_ekle') {
        $personel_id = $_POST['personel_id'];
        $izin_turu_id = $_POST['izin_turu_id'];
        $baslangic_tarihi = $_POST['baslangic_tarihi'];
        $bitis_tarihi = $_POST['bitis_tarihi'];
        $aciklama = $_POST['aciklama'] ?? '';
        
        // Gün sayısını hesapla
        $baslangic = new DateTime($baslangic_tarihi);
        $bitis = new DateTime($bitis_tarihi);
        $gun_sayisi = $bitis->diff($baslangic)->days + 1;
        
        try {
            $izin_id = generateUUID();
            $sql = "INSERT INTO personel_izinleri 
                    (id, personel_id, izin_turu_id, baslangic_tarihi, bitis_tarihi, 
                     gun_sayisi, aciklama, talep_eden_id, durum) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'beklemede')";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $izin_id, $personel_id, $izin_turu_id, $baslangic_tarihi, 
                $bitis_tarihi, $gun_sayisi, $aciklama, $_SESSION['user_id']
            ]);
            
            if ($success) {
                $basari_mesaji = "İzin başvurusu başarıyla kaydedildi.";
                
                // İzin türüne göre otomatik onay
                $izin_turu_sql = "SELECT onayli_kullanim FROM izin_turleri WHERE id = ?";
                $izin_stmt = $pdo->prepare($izin_turu_sql);
                $izin_stmt->execute([$izin_turu_id]);
                $izin_turu = $izin_stmt->fetch();
                
                if ($izin_turu && $izin_turu['onayli_kullanim'] == 0) {
                    // Otomatik onay
                    $onay_sql = "UPDATE personel_izinleri 
                                SET durum = 'onaylandi', onaylayan_id = ?, onay_tarihi = NOW() 
                                WHERE id = ?";
                    $onay_stmt = $pdo->prepare($onay_sql);
                    $onay_stmt->execute([$_SESSION['user_id'], $izin_id]);
                    $basari_mesaji .= " İzin otomatik olarak onaylandı.";
                }
            }
        } catch (PDOException $e) {
            $hata_mesaji = "İzin kaydedilirken hata: " . $e->getMessage();
        }
    }
}

// Verileri getir
$personeller = getPersoneller();
$izin_turleri = getIzinTurleri();
$bekleyen_izinler = getBekleyenIzinler();
$onaylanan_izinler = getOnaylananIzinler();

$title = 'İzin Yönetimi';
include __DIR__ . '/partials/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.izin-card {
    border-left: 4px solid;
    transition: all 0.3s ease;
}
.izin-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.izin-beklemede { border-left-color: #ffc107; }
.izin-onaylandi { border-left-color: #28a745; }
.izin-reddedildi { border-left-color: #dc3545; }

.personel-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.izin-takvim {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
}

.stat-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
    border: none;
}
</style>

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            <?php include __DIR__ . '/partials/page-title.php'; ?>

            <div class="container-fluid">
                
                <?php if (isset($basari_mesaji)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= $basari_mesaji ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($hata_mesaji)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $hata_mesaji ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3><?= count($bekleyen_izinler) ?></h3>
                                <p class="mb-0">Bekleyen İzin</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-check fa-2x mb-2"></i>
                                <h3><?= count($onaylanan_izinler) ?></h3>
                                <p class="mb-0">Bu Ay Onaylanan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h3><?= count($personeller) ?></h3>
                                <p class="mb-0">Toplam Personel</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card izin-takvim">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                <h3><?= date('d') ?></h3>
                                <p class="mb-0"><?= date('F Y') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- İzin Talep Formu -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-plus-circle text-primary me-2"></i>
                                    Yeni İzin Kaydı
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="izin_ekle">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Personel</label>
                                        <select name="personel_id" class="form-select" required>
                                            <option value="">Personel seçiniz</option>
                                            <?php foreach ($personeller as $personel): ?>
                                                <option value="<?= $personel['id'] ?>">
                                                    <?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?> 
                                                    (<?= htmlspecialchars($personel['sicil_no']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">İzin Türü</label>
                                        <select name="izin_turu_id" class="form-select" required>
                                            <option value="">İzin türü seçiniz</option>
                                            <?php foreach ($izin_turleri as $turu): ?>
                                                <option value="<?= $turu['id'] ?>" 
                                                        data-color="<?= $turu['renk_kodu'] ?>"
                                                        data-ucretli="<?= $turu['ucretli'] ?>"
                                                        data-onay="<?= $turu['onayli_kullanim'] ?>">
                                                    <?= htmlspecialchars($turu['ad']) ?>
                                                    <?php if ($turu['ucretli']): ?>
                                                        <span class="badge bg-success ms-1">Ücretli</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Ücretsiz</span>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Başlangıç Tarihi</label>
                                            <input type="date" name="baslangic_tarihi" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Bitiş Tarihi</label>
                                            <input type="date" name="bitis_tarihi" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Açıklama</label>
                                        <textarea name="aciklama" class="form-control" rows="3" 
                                                  placeholder="İzin açıklaması (opsiyonel)"></textarea>
                                    </div>

                                    <div class="alert alert-info">
                                        <small>
                                            <i class="fas fa-info-circle me-1"></i>
                                            İzin kaydı yapıldıktan sonra yöneticinin onayına gönderilecektir.
                                        </small>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-1"></i> İzin Kaydını Yap
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Hızlı İstatistikler -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">📊 Bu Ay İzin Durumu</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Bekleyen:</span>
                                    <span class="badge bg-warning"><?= count($bekleyen_izinler) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Onaylanan:</span>
                                    <span class="badge bg-success"><?= count($onaylanan_izinler) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>İzinli Personel:</span>
                                    <span class="badge bg-info"><?= getBugunkuIzinliSayisi() ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İzin Listesi -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list text-info me-2"></i>
                                        İzin Durumu
                                    </h5>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-outline-primary btn-sm active" data-filter="all">
                                            Tümü
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" data-filter="beklemede">
                                            Bekleyen
                                        </button>
                                        <button class="btn btn-outline-success btn-sm" data-filter="onaylandi">
                                            Onaylanan
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="izinlerTable">
                                        <thead>
                                            <tr>
                                                <th>Personel</th>
                                                <th>İzin Türü</th>
                                                <th>Tarih Aralığı</th>
                                                <th>Gün</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $tum_izinler = array_merge($bekleyen_izinler, $onaylanan_izinler);
                                            foreach ($tum_izinler as $izin): 
                                            ?>
                                                <tr data-status="<?= $izin['durum'] ?>">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="uploads/avatars/<?= $izin['avatar'] ?: 'default.png' ?>" 
                                                                 class="personel-avatar me-2" alt="Avatar">
                                                            <div>
                                                                <strong><?= htmlspecialchars($izin['personel_adi']) ?></strong>
                                                                <br><small class="text-muted"><?= htmlspecialchars($izin['sicil_no']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background-color: <?= $izin['renk_kodu'] ?>">
                                                            <?= htmlspecialchars($izin['izin_turu_adi']) ?>
                                                        </span>
                                                        <?php if ($izin['ucretli']): ?>
                                                            <br><small class="text-success">💰 Ücretli</small>
                                                        <?php else: ?>
                                                            <br><small class="text-warning">💸 Ücretsiz</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?= date('d.m.Y', strtotime($izin['baslangic_tarihi'])) ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?= date('d.m.Y', strtotime($izin['bitis_tarihi'])) ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= $izin['gun_sayisi'] ?> gün</span>
                                                    </td>
                                                    <td>
                                                        <?php if ($izin['durum'] == 'beklemede'): ?>
                                                            <span class="badge bg-warning">⏳ Beklemede</span>
                                                        <?php elseif ($izin['durum'] == 'onaylandi'): ?>
                                                            <span class="badge bg-success">✅ Onaylandı</span>
                                                            <br><small class="text-muted">
                                                                <?= date('d.m.Y', strtotime($izin['onay_tarihi'])) ?>
                                                            </small>
                                                        <?php elseif ($izin['durum'] == 'reddedildi'): ?>
                                                            <span class="badge bg-danger">❌ Reddedildi</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <?php if ($izin['durum'] == 'beklemede' && $_SESSION['rol'] == 'yonetici'): ?>
                                                                <button class="btn btn-success btn-sm" 
                                                                        onclick="izinOnayla('<?= $izin['id'] ?>')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button class="btn btn-danger btn-sm" 
                                                                        onclick="izinReddet('<?= $izin['id'] ?>')">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button class="btn btn-info btn-sm" 
                                                                    onclick="izinDetay('<?= $izin['id'] ?>')">
                                                                <i class="fas fa-eye"></i>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- İzin Detayı Modal -->
<div class="modal fade" id="izinDetayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İzin Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="izinDetayIcerik">
                <!-- İzin detayları AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<!-- İzin Red Nedeni Modal -->
<div class="modal fade" id="izinRedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İzin Reddi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="izinRedForm">
                    <input type="hidden" id="redIzinId">
                    <div class="mb-3">
                        <label class="form-label">Red Nedeni</label>
                        <textarea class="form-control" id="redNedeni" rows="4" 
                                  placeholder="İzin reddedilme nedenini açıklayınız..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" onclick="izinReddoConfirm()">
                    <i class="fas fa-times me-1"></i> İzni Reddet
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer-scripts.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

<script>
$(document).ready(function() {
    // DataTable
    $('#izinlerTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[2, "desc"]]
    });

    // Tarih picker
    flatpickr("input[type=date]", {
        locale: "tr",
        dateFormat: "Y-m-d",
        minDate: "today"
    });

    // Filtre butonları
    $('[data-filter]').click(function() {
        const filter = $(this).data('filter');
        $('[data-filter]').removeClass('active');
        $(this).addClass('active');
        
        if (filter === 'all') {
            $('#izinlerTable tbody tr').show();
        } else {
            $('#izinlerTable tbody tr').hide();
            $(`#izinlerTable tbody tr[data-status="${filter}"]`).show();
        }
    });

    // Gün sayısı otomatik hesaplama
    $('input[name="baslangic_tarihi"], input[name="bitis_tarihi"]').change(function() {
        const baslangic = $('input[name="baslangic_tarihi"]').val();
        const bitis = $('input[name="bitis_tarihi"]').val();
        
        if (baslangic && bitis) {
            const start = new Date(baslangic);
            const end = new Date(bitis);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            // Gün sayısını göster
            const gunSayisiInfo = `<small class="text-info">Toplam ${diffDays} gün</small>`;
            $('.form-control[name="bitis_tarihi"]').parent().find('small').remove();
            $('.form-control[name="bitis_tarihi"]').parent().append(gunSayisiInfo);
        }
    });
});

// İzin onaylama
function izinOnayla(izinId) {
    if (confirm('Bu izni onaylamak istediğinize emin misiniz?')) {
        $.post('ajax/izin_onayla.php', {
            izin_id: izinId,
            action: 'onayla'
        }, function(response) {
            if (response.success) {
                showToast('İzin başarıyla onaylandı', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(response.message || 'İzin onaylanırken hata oluştu', 'error');
            }
        }, 'json').fail(function() {
            showToast('Bir hata oluştu', 'error');
        });
    }
}

// İzin reddetme
function izinReddet(izinId) {
    $('#redIzinId').val(izinId);
    $('#izinRedModal').modal('show');
}

function izinReddoConfirm() {
    const izinId = $('#redIzinId').val();
    const nedeni = $('#redNedeni').val();
    
    if (!nedeni.trim()) {
        alert('Red nedeni girmelisiniz!');
        return;
    }
    
    $.post('ajax/izin_onayla.php', {
        izin_id: izinId,
        action: 'reddet',
        red_nedeni: nedeni
    }, function(response) {
        if (response.success) {
            $('#izinRedModal').modal('hide');
            showToast('İzin reddedildi', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(response.message || 'İzin reddedilirken hata oluştu', 'error');
        }
    }, 'json').fail(function() {
        showToast('Bir hata oluştu', 'error');
    });
}

// İzin detayları
function izinDetay(izinId) {
    $('#izinDetayModal').modal('show');
    $('#izinDetayIcerik').html('<div class="text-center"><div class="spinner-border"></div></div>');
    
    $.get('ajax/izin_detay.php', {id: izinId}, function(data) {
        $('#izinDetayIcerik').html(data);
    }).fail(function() {
        $('#izinDetayIcerik').html('<div class="alert alert-danger">Detaylar yüklenemedi</div>');
    });
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
        delay: 3000
    });
    toast.show();
    
    toastElement.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}
</script>

<?php include 'partials/footer.php'; ?>