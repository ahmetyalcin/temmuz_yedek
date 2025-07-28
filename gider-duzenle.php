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

// Dropdown verileri
$gider_kategorileri = getGiderKategorileri();
$harcama_turleri = getHarcamaTurleri();

// Form işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $eski_tutar = $gider['tutar'];
        $yeni_tutar = floatval($_POST['tutar']);
        
        $sonuc = giderGuncelle(
            $gider_id,
            $_POST['tarih'],
            $_POST['kategori_id'],
            $_POST['aciklama'],
            $yeni_tutar,
            $_POST['harcama_turu_id'],
            $_POST['fatura_no'] ?? null,
            $_POST['tedarikci'] ?? null,
            $_POST['notlar'] ?? null
        );
        
        if ($sonuc) {
            $basari = "Gider başarıyla güncellendi.";
            
            // Tutar değiştiyse ödeme durumunu yeniden hesapla
            if ($eski_tutar != $yeni_tutar) {
                giderOdemeDurumunuGuncelle($gider_id);
                $basari .= " Ödeme durumu yeniden hesaplandı.";
            }
            
            // Güncellenmiş veriyi tekrar yükle
            $gider_detay = getGiderDetay($gider_id);
            $gider = $gider_detay['gider'];
        } else {
            $hata = "Gider güncellenirken bir hata oluştu.";
        }
        
    } catch (Exception $e) {
        $hata = "Gider güncellenirken hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Gider Düzenle";
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
                $title = "Gider Düzenle";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit text-warning me-2"></i>
                                    Gider Düzenle
                                </h5>
                                <div>
                                    <a href="gider-detay.php?id=<?= $gider_id ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye me-2"></i>Detay
                                    </a>
                                    <a href="gider-listesi.php" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left me-2"></i>Geri
                                    </a>
                                </div>
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
                                                   value="<?= $_POST['tarih'] ?? $gider['tarih'] ?>" required>
                                        </div>

                                        <!-- Kategori -->
                                        <div class="col-md-6">
                                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                            <select name="kategori_id" class="form-select searchable-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($gider_kategorileri as $kategori): ?>
                                                    <option value="<?= $kategori['id'] ?>" 
                                                            <?= ($_POST['kategori_id'] ?? $gider['kategori_id']) == $kategori['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($kategori['ad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Açıklama -->
                                        <div class="col-12">
                                            <label class="form-label">Açıklama <span class="text-danger">*</span></label>
                                            <textarea name="aciklama" class="form-control" rows="3" 
                                                      placeholder="Gider açıklaması..." required><?= $_POST['aciklama'] ?? $gider['aciklama'] ?></textarea>
                                        </div>

                                        <!-- Tutar -->
                                        <div class="col-md-6">
                                            <label class="form-label">Tutar (₺) <span class="text-danger">*</span></label>
                                            <input type="number" name="tutar" class="form-control" 
                                                   step="0.01" min="0.01" 
                                                   value="<?= $_POST['tutar'] ?? $gider['tutar'] ?>" required>
                                            <div class="form-text text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Tutar değiştirildiğinde ödeme durumu tekrar hesaplanacaktır.
                                            </div>
                                        </div>

                                        <!-- Harcama Türü -->
                                        <div class="col-md-6">
                                            <label class="form-label">Harcama Türü <span class="text-danger">*</span></label>
                                            <select name="harcama_turu_id" class="form-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($harcama_turleri as $tur): ?>
                                                    <option value="<?= $tur['id'] ?>" 
                                                            <?= ($_POST['harcama_turu_id'] ?? $gider['harcama_turu_id']) == $tur['id'] ? 'selected' : '' ?>>
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
                                                   value="<?= $_POST['fatura_no'] ?? $gider['fatura_no'] ?>">
                                        </div>

                                        <!-- Tedarikçi (Opsiyonel) -->
                                        <div class="col-md-6">
                                            <label class="form-label">Tedarikçi</label>
                                            <input type="text" name="tedarikci" class="form-control" 
                                                   placeholder="Tedarikçi firma adı..." 
                                                   value="<?= $_POST['tedarikci'] ?? $gider['tedarikci'] ?>">
                                        </div>

                                        <!-- Notlar (Opsiyonel) -->
                                        <div class="col-12">
                                            <label class="form-label">Notlar</label>
                                            <textarea name="notlar" class="form-control" rows="2" 
                                                      placeholder="Ek notlar..."><?= $_POST['notlar'] ?? $gider['notlar'] ?></textarea>
                                        </div>

                                        <!-- Mevcut Durum Bilgisi -->
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6 class="mb-3">
                                                        <i class="fas fa-info-circle text-info me-2"></i>
                                                        Mevcut Ödeme Durumu
                                                    </h6>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <small class="text-muted">Toplam Tutar:</small>
                                                            <div class="fw-bold text-danger">
                                                                <?= number_format($gider['tutar'], 2) ?> ₺
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-muted">Ödenen:</small>
                                                            <div class="fw-bold text-success">
                                                                <?php
                                                                $toplam_odenen = $gider['tutar'] - $gider['odenmemis_kalan'];
                                                                echo number_format($toplam_odenen, 2);
                                                                ?> ₺
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-muted">Kalan:</small>
                                                            <div class="fw-bold text-warning">
                                                                <?= number_format($gider['odenmemis_kalan'], 2) ?> ₺
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-muted">Durum:</small>
                                                            <div>
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
                                                                <span class="badge bg-<?= $badge_class ?>"><?= $durum_text ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                        </button>
                                        <a href="gider-detay.php?id=<?= $gider_id ?>" class="btn btn-info">
                                            <i class="fas fa-eye me-2"></i>Detay Görüntüle
                                        </a>
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

                // Form validation
                $('#gider_form').on('submit', function(e) {
                    const tutar = parseFloat($('input[name="tutar"]').val());
                    if (tutar <= 0) {
                        e.preventDefault();
                        alert('Tutar 0\'dan büyük olmalıdır!');
                        return false;
                    }

                    // Tutar değişip değişmediğini kontrol et
                    const eskiTutar = <?= $gider['tutar'] ?>;
                    if (tutar !== eskiTutar) {
                        const onay = confirm('Tutar değiştirildiğinde mevcut ödeme durumu etkilenecektir. Devam etmek istiyor musunuz?');
                        if (!onay) {
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            });
        </script>
    </div>
</body>
</html>