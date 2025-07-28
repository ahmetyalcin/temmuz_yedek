<?php
session_start();
require_once 'functions.php';

$gider_id = $_GET['id'] ?? '';
$hata = '';
$basari = '';

if (empty($gider_id)) {
    header("Location: gider-listesi.php");
    exit;
}

// Gider detayını getir
$gider_detay = getGiderDetay($gider_id);
if (!$gider_detay) {
    header("Location: gider-listesi.php");
    exit;
}

$gider = $gider_detay['gider'];
$odemeler = $gider_detay['odemeler'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Gider Detayı";
    include "partials/title-meta.php";
    ?>
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
</head>
<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Muhasebe";
                $title = "Gider Detayı";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Gider Bilgileri -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Gider Bilgileri
                                </h5>
                                <div>
                                    <a href="gider-duzenle.php?id=<?= $gider['id'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-2"></i>Düzenle
                                    </a>
                                    <a href="gider-listesi.php" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left me-2"></i>Geri
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tarih:</label>
                                        <p class="mb-0"><?= date('d.m.Y', strtotime($gider['tarih'])) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Kategori:</label>
                                        <p class="mb-0"><?= htmlspecialchars($gider['kategori_adi']) ?></p>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-bold">Açıklama:</label>
                                        <p class="mb-0"><?= htmlspecialchars($gider['aciklama']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tutar:</label>
                                        <p class="mb-0 fs-5 text-danger fw-bold">
                                            <?= number_format($gider['tutar'], 2) ?> ₺
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Harcama Türü:</label>
                                        <p class="mb-0">
                                            <span class="badge bg-<?= $gider['harcama_turu_adi'] == 'İşletme' ? 'primary' : 'info' ?> fs-6">
                                                <?= htmlspecialchars($gider['harcama_turu_adi']) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <?php if($gider['fatura_no']): ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Fatura No:</label>
                                        <p class="mb-0"><?= htmlspecialchars($gider['fatura_no']) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($gider['tedarikci']): ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tedarikçi:</label>
                                        <p class="mb-0"><?= htmlspecialchars($gider['tedarikci']) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Durum:</label>
                                        <p class="mb-0">
                                            <?php
                                            $badge_class = '';
                                            $durum_text = '';
                                            switch($gider['durum']) {
                                                case 'odendi':
                                                    $badge_class = 'success';
                                                    $durum_text = 'Ödendi';
                                                    break;
                                                case 'kismi_odendi':
                                                    $badge_class = 'warning';
                                                    $durum_text = 'Kısmi Ödendi';
                                                    break;
                                                default:
                                                    $badge_class = 'danger';
                                                    $durum_text = 'Beklemede';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?> fs-6"><?= $durum_text ?></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Kayıt Yapan:</label>
                                        <p class="mb-0"><?= htmlspecialchars($gider['kayit_yapan_adi'] ?? 'Sistem') ?></p>
                                    </div>
                                    <?php if($gider['notlar']): ?>
                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-bold">Notlar:</label>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($gider['notlar'])) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Ödeme Geçmişi -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-history text-success me-2"></i>
                                    Ödeme Geçmişi
                                </h5>
                                <?php if($gider['odenmemis_kalan'] > 0): ?>
                                <button class="btn btn-success btn-sm odeme-ekle-btn" 
                                        data-gider-id="<?= $gider['id'] ?>"
                                        data-kalan="<?= $gider['odenmemis_kalan'] ?>">
                                    <i class="fas fa-plus me-2"></i>Ödeme Ekle
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if(empty($odemeler)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-credit-card fs-1 text-muted"></i>
                                        <p class="text-muted mt-2">Henüz ödeme yapılmamış.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Tutar</th>
                                                    <th>Yöntem</th>
                                                    <th>Açıklama</th>
                                                    <th>Kayıt Yapan</th>
                                                    <th>Kayıt Tarihi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($odemeler as $odeme): ?>
                                                <tr>
                                                    <td><?= date('d.m.Y', strtotime($odeme['odeme_tarihi'])) ?></td>
                                                    <td class="text-success fw-bold">
                                                        <?= number_format($odeme['tutar'], 2) ?> ₺
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= ucfirst(str_replace('_', ' ', $odeme['odeme_yontemi'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($odeme['aciklama'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($odeme['kayit_yapan_adi'] ?? 'Sistem') ?></td>
                                                    <td><?= date('d.m.Y H:i', strtotime($odeme['olusturma_tarihi'])) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Özet Kartı -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie text-info me-2"></i>
                                    Ödeme Özeti
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Toplam Tutar:</span>
                                        <span class="text-danger fw-bold">
                                            <?= number_format($gider['tutar'], 2) ?> ₺
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Ödenen:</span>
                                        <span class="text-success fw-bold">
                                            <?php
                                            $toplam_odenen = array_sum(array_column($odemeler, 'tutar'));
                                            echo number_format($toplam_odenen, 2);
                                            ?> ₺
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Kalan:</span>
                                        <span class="text-warning fw-bold">
                                            <?= number_format($gider['odenmemis_kalan'], 2) ?> ₺
                                        </span>
                                    </div>
                                </div>
                                <hr>
                                <div class="progress mb-2">
                                    <?php
                                    $ode_orani = $gider['tutar'] > 0 ? ($toplam_odenen / $gider['tutar']) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $ode_orani ?>%" 
                                         aria-valuenow="<?= $ode_orani ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Ödeme Oranı: %<?= number_format($ode_orani, 1) ?>
                                </small>
                            </div>
                        </div>

                        <!-- Kayıt Bilgileri -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info text-secondary me-2"></i>
                                    Kayıt Bilgileri
                                </h5>
                            </div>
                            <div class="card-body">
                                <small class="text-muted">
                                    <strong>Oluşturma:</strong><br>
                                    <?= date('d.m.Y H:i:s', strtotime($gider['olusturma_tarihi'])) ?>
                                </small>
                                <br><br>
                                <small class="text-muted">
                                    <strong>Son Güncelleme:</strong><br>
                                    <?= date('d.m.Y H:i:s', strtotime($gider['guncelleme_tarihi'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ödeme Ekleme Modal -->
        <div class="modal fade" id="odemeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="odemeForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Ödeme Ekle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="gider_id" name="gider_id" value="<?= $gider['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Ödeme Tarihi</label>
                                <input type="date" name="odeme_tarihi" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tutar (₺)</label>
                                <input type="number" name="tutar" class="form-control" 
                                       step="0.01" min="0.01" max="<?= $gider['odenmemis_kalan'] ?>"
                                       value="<?= $gider['odenmemis_kalan'] ?>" required>
                                <div class="form-text">Kalan tutar: <?= number_format($gider['odenmemis_kalan'], 2) ?> ₺</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ödeme Yöntemi</label>
                                <select name="odeme_yontemi" class="form-select" required>
                                    <option value="nakit">Nakit</option>
                                    <option value="havale">Havale</option>
                                    <option value="kredi_karti">Kredi Kartı</option>
                                    <option value="cek">Çek</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="2" 
                                          placeholder="Ödeme açıklaması..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-success">Ödeme Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script>
            $(document).ready(function() {
                // Ödeme ekleme modal
                $('.odeme-ekle-btn').click(function() {
                    $('#odemeModal').modal('show');
                });

                // Ödeme formu gönderme
                $('#odemeForm').submit(function(e) {
                    e.preventDefault();
                    
                    const formData = $(this).serialize();
                    
                    $.ajax({
                        url: 'ajax/gider-odeme-ekle.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert('Ödeme başarıyla kaydedildi!');
                                location.reload();
                            } else {
                                alert('Hata: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Bir hata oluştu!');
                        }
                    });
                });
            });
        </script>
    </div>
</body>
</html>