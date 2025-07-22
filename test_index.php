<?php
include 'functions.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'paket_ekle':
                $sonuc = paketEkle(
                    $_POST['ad'],
                    $_POST['aciklama'],
                    $_POST['seans_turu_id'],
                    $_POST['seans_sayisi'],
                    $_POST['fiyat'],
                    $_POST['gecerlilik_gun']
                );
                if ($sonuc) {
                    $basari = "Paket başarıyla eklendi.";
                } else {
                    $hata = "Paket eklenirken bir hata oluştu.";
                }
                break;

            case 'paket_guncelle':
                $sonuc = paketGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['aciklama'],
                    $_POST['seans_turu_id'],
                    $_POST['seans_sayisi'],
                    $_POST['fiyat'],
                    $_POST['gecerlilik_gun']
                );
                if ($sonuc) {
                    $basari = "Paket başarıyla güncellendi.";
                } else {
                    $hata = "Paket güncellenirken bir hata oluştu.";
                }
                break;

            case 'paket_sil':
                $sonuc = paketSil($_POST['id']);
                if ($sonuc) {
                    $basari = "Paket başarıyla silindi.";
                } else {
                    $hata = "Paket silinirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Verileri çek
$uyelikTurleri = getUyelikTurleri();
$seansTurleri = getSeansTurleri();
$hizmetPaketleri = getHizmetPaketleri();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hizmet Paket Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">Hizmet Paket Yönetimi</h1>
        
        <?php if ($hata): ?>
            <div class="alert alert-danger"><?php echo $hata; ?></div>
        <?php endif; ?>
        
        <?php if ($basari): ?>
            <div class="alert alert-success"><?php echo $basari; ?></div>
        <?php endif; ?>

        <!-- Yeni Paket Ekleme Formu -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Yeni Paket Ekle</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" value="paket_ekle">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Paket Adı</label>
                            <input type="text" name="ad" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Türü</label>
                            <select name="seans_turu_id" class="form-select" required>
                                <?php foreach ($seansTurleri as $seansTuru): ?>
                                    <option value="<?php echo $seansTuru['id']; ?>">
                                        <?php echo $seansTuru['ad']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Seans Sayısı</label>
                            <input type="number" name="seans_sayisi" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fiyat</label>
                            <input type="number" step="0.01" name="fiyat" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Geçerlilik (Gün)</label>
                            <input type="number" name="gecerlilik_gun" class="form-control" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Paketi Ekle</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Paket Listesi -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Mevcut Paketler</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Paket Adı</th>
                                <th>Seans Türü</th>
                                <th>Seans Sayısı</th>
                                <th>Fiyat</th>
                                <th>Geçerlilik</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hizmetPaketleri as $paket): ?>
                                <tr>
                                    <td><?php echo $paket['ad']; ?></td>
                                    <td><?php echo $paket['seans_turu_adi']; ?></td>
                                    <td><?php echo $paket['seans_sayisi']; ?></td>
                                    <td><?php echo number_format($paket['fiyat'], 2); ?> TL</td>
                                    <td><?php echo $paket['gecerlilik_gun']; ?> gün</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="duzenle(<?php echo htmlspecialchars(json_encode($paket)); ?>)">
                                            Düzenle
                                        </button>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="islem" value="paket_sil">
                                            <input type="hidden" name="id" value="<?php echo $paket['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Bu paketi silmek istediğinizden emin misiniz?')">
                                                Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Düzenleme Modalı -->
    <div class="modal fade" id="duzenleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Paket Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="duzenleForm">
                        <input type="hidden" name="islem" value="paket_guncelle">
                        <input type="hidden" name="id" id="duzenle_id">
                        <div class="mb-3">
                            <label class="form-label">Paket Adı</label>
                            <input type="text" name="ad" id="duzenle_ad" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seans Türü</label>
                            <select name="seans_turu_id" id="duzenle_seans_turu_id" class="form-select" required>
                                <?php foreach ($seansTurleri as $seansTuru): ?>
                                    <option value="<?php echo $seansTuru['id']; ?>">
                                        <?php echo $seansTuru['ad']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seans Sayısı</label>
                            <input type="number" name="seans_sayisi" id="duzenle_seans_sayisi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fiyat</label>
                            <input type="number" step="0.01" name="fiyat" id="duzenle_fiyat" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Geçerlilik (Gün)</label>
                            <input type="number" name="gecerlilik_gun" id="duzenle_gecerlilik_gun" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" id="duzenle_aciklama" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('duzenleForm').submit()">
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function duzenle(paket) {
            document.getElementById('duzenle_id').value = paket.id;
            document.getElementById('duzenle_ad').value = paket.ad;
            document.getElementById('duzenle_seans_turu_id').value = paket.seans_turu_id;
            document.getElementById('duzenle_seans_sayisi').value = paket.seans_sayisi;
            document.getElementById('duzenle_fiyat').value = paket.fiyat;
            document.getElementById('duzenle_gecerlilik_gun').value = paket.gecerlilik_gun;
            document.getElementById('duzenle_aciklama').value = paket.aciklama;
            
            new bootstrap.Modal(document.getElementById('duzenleModal')).show();
        }
    </script>
</body>
</html>