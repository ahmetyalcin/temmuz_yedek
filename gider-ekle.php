<?php
session_start();
require_once 'functions.php';

$hata = '';
$basari = '';

// Dropdown verileri
$gider_kategorileri = getGiderKategorileri();
$harcama_turleri = getHarcamaTurleri();

// Form işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();
        
        // Gider ekle
        $gider_id = giderEkle(
            $_POST['tarih'],
            $_POST['kategori_id'],
            $_POST['aciklama'],
            floatval($_POST['tutar']),
            $_POST['harcama_turu_id'],
            $_POST['fatura_no'] ?? null,
            $_POST['tedarikci'] ?? null,
            $_POST['notlar'] ?? null,
            $_SESSION['user_id'] ?? null
        );
        
        // Eğer hemen ödeme yapılacaksa ödeme kaydı ekle
        if (isset($_POST['hemen_ode']) && $_POST['hemen_ode'] == '1') {
            giderOdemeEkle(
                $gider_id,
                $_POST['odeme_tarihi'] ?? $_POST['tarih'],
                floatval($_POST['tutar']),
                $_POST['odeme_yontemi'],
                'İlk ödeme',
                $_SESSION['user_id'] ?? null
            );
        }
        
        $pdo->commit();
        $basari = "Gider başarıyla kaydedildi.";
        
        // Formu temizle
        $_POST = [];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $hata = "Gider kaydedilirken hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Gider Ekle";
    include "partials/title-meta.php";
    ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                $title = "Gider Ekle";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-minus-circle text-danger me-2"></i>
                                    Yeni Gider Kaydı
                                </h5>
                            </div>
                            <div class="card-body">
                                
                                <?php if ($hata): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($hata) ?></div>
                                <?php endif; ?>
                                
                                <?php if ($basari): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($basari) ?></div>
                                <?php endif; ?>

                                <form method="POST" id="gider_form">
                                    <div class="row g-3">
                                        
                                        <!-- Tarih -->
                                        <div class="col-md-6">
                                            <label class="form-label">Tarih <span class="text-danger">*</span></label>
                                            <input type="date" name="tarih" class="form-control" 
                                                   value="<?= $_POST['tarih'] ?? date('Y-m-d') ?>" required>
                                        </div>

                                        <!-- Kategori -->
                                        <div class="col-md-6">
                                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                            <select name="kategori_id" class="form-select searchable-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($gider_kategorileri as $kategori): ?>
                                                    <option value="<?= $kategori['id'] ?>" 
                                                            <?= ($_POST['kategori_id'] ?? '') == $kategori['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($kategori['ad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Açıklama -->
                                        <div class="col-12">
                                            <label class="form-label">Açıklama <span class="text-danger">*</span></label>
                                            <textarea name="aciklama" class="form-control" rows="3" 
                                                      placeholder="Gider açıklaması..." required><?= $_POST['aciklama'] ?? '' ?></textarea>
                                        </div>

                                        <!-- Tutar -->
                                        <div class="col-md-6">
                                            <label class="form-label">Tutar (₺) <span class="text-danger">*</span></label>
                                            <input type="number" name="tutar" class="form-control" 
                                                   step="0.01" min="0.01" 
                                                   value="<?= $_POST['tutar'] ?? '' ?>" required>
                                        </div>

                                        <!-- Harcama Türü -->
                                        <div class="col-md-6">
                                            <label class="form-label">Harcama Türü <span class="text-danger">*</span></label>
                                            <select name="harcama_turu_id" class="form-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($harcama_turleri as $tur): ?>
                                                    <option value="<?= $tur['id'] ?>" 
                                                            <?= ($_POST['harcama_turu_id'] ?? '') == $tur['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($tur['ad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Fatura No (Opsiyonel) -->
                                        <div class="col-md-6">
                                            <label class="form-label">Fatura No</label>
                                            <input type="text" name="fatura_no" class="form-control" 
                                                   placeholder="Fatura numarası..." 
                                                   value="<?= $_POST['fatura_no'] ?? '' ?>">
                                        </div>

                                        <!-- Tedarikçi (Opsiyonel) -->
                                        <div class="col-md-6">
                                            <label class="form-label">Tedarikçi</label>
                                            <input type="text" name="tedarikci" class="form-control" 
                                                   placeholder="Tedarikçi firma adı..." 
                                                   value="<?= $_POST['tedarikci'] ?? '' ?>">
                                        </div>

                                        <!-- Notlar (Opsiyonel) -->
                                        <div class="col-12">
                                            <label class="form-label">Notlar</label>
                                            <textarea name="notlar" class="form-control" rows="2" 
                                                      placeholder="Ek notlar..."><?= $_POST['notlar'] ?? '' ?></textarea>
                                        </div>

                                        <!-- Hemen Ödeme -->
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="hemen_ode" name="hemen_ode" value="1"
                                                               <?= ($_POST['hemen_ode'] ?? '') == '1' ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-bold" for="hemen_ode">
                                                            Bu giderin ödemesini hemen yap
                                                        </label>
                                                    </div>

                                                    <div id="odeme_detaylari" class="mt-3" style="display: none;">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Ödeme Tarihi</label>
                                                                <input type="date" name="odeme_tarihi" class="form-control" 
                                                                       value="<?= $_POST['odeme_tarihi'] ?? date('Y-m-d') ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Ödeme Yöntemi</label>
                                                                <select name="odeme_yontemi" class="form-select">
                                                                    <option value="nakit" <?= ($_POST['odeme_yontemi'] ?? '') == 'nakit' ? 'selected' : '' ?>>Nakit</option>
                                                                    <option value="havale" <?= ($_POST['odeme_yontemi'] ?? '') == 'havale' ? 'selected' : '' ?>>Havale</option>
                                                                    <option value="kredi_karti" <?= ($_POST['odeme_yontemi'] ?? '') == 'kredi_karti' ? 'selected' : '' ?>>Kredi Kartı</option>
                                                                    <option value="cek" <?= ($_POST['odeme_yontemi'] ?? '') == 'cek' ? 'selected' : '' ?>>Çek</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-save me-2"></i>Gideri Kaydet
                                        </button>
                                        <a href="gider-listesi.php" class="btn btn-secondary">
                                            <i class="fas fa-list me-2"></i>Gider Listesi
                                        </a>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Select2 başlat
                $('.searchable-select').select2({
                    width: '100%',
                    placeholder: 'Seçiniz...',
                    allowClear: true
                });

                // Hemen ödeme checkbox kontrolü
                $('#hemen_ode').change(function() {
                    if ($(this).is(':checked')) {
                        $('#odeme_detaylari').slideDown();
                    } else {
                        $('#odeme_detaylari').slideUp();
                    }
                });

                // Sayfa yüklendiğinde checkbox durumunu kontrol et
                if ($('#hemen_ode').is(':checked')) {
                    $('#odeme_detaylari').show();
                }

                // Form validation
                $('#gider_form').on('submit', function(e) {
                    const tutar = parseFloat($('input[name="tutar"]').val());
                    if (tutar <= 0) {
                        e.preventDefault();
                        alert('Tutar 0\'dan büyük olmalıdır!');
                        return false;
                    }
                });
            });
        </script>
    </div>
</body>
</html>