<?php
// satis_ekle.php
include 'con/db2.php';  // Veritabanı bağlantısı
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// Hizmet paketleri, danışanlar ve personel verilerini çekiyoruz
try {
    // Hizmet paketleri
    $hizmetSorgu = $pdo->query("SELECT id, hizmet_adi FROM hizmet_paketleri");
    $hizmetler = $hizmetSorgu->fetchAll(PDO::FETCH_ASSOC);

    // Danışanlar: ad, soyad, telefon bilgileriyle
    $danisanSorgu = $pdo->query("SELECT id, ad, soyad, telefon FROM danisan");
    $danisanlar = $danisanSorgu->fetchAll(PDO::FETCH_ASSOC);

    // Personel: ad, soyad bilgileri
    $personelSorgu = $pdo->query("SELECT id, ad, soyad FROM personel");
    $personeller = $personelSorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hizmet_id          = $_POST['hizmet_id'] ?? null;
    $satan_personel_id  = $_POST['satan_personel_id'] ?? null;
    $danisan_id         = $_POST['danisan_id'] ?? null;
    $satis_tarihi       = $_POST['satis_tarihi'] ?? date('Y-m-d');
    $son_kullanma_tarihi= $_POST['son_kullanma_tarihi'] ?? null;
    $toplam_tutar       = $_POST['toplam_tutar'] ?? 0;
    $uyelik_indirimi    = $_POST['uyelik_indirimi'] ?? 0;
    $seans_sayisi       = $_POST['seans_sayisi'] ?? 0;

    // Hizmet, satan personel ve danışan seçimlerinin zorunlu olduğuna dikkat edelim
    if (!$hizmet_id || !$satan_personel_id || !$danisan_id) {
        $hata = "Lütfen hizmet, satan personel ve danışan seçiniz!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO satislar 
                (hizmet_id, satan_personel_id, danisan_id, satis_tarihi, son_kullanma_tarihi, toplam_tutar, uyelik_indirimi, seans_sayisi) 
                VALUES 
                (:hizmet_id, :satan_personel_id, :danisan_id, :satis_tarihi, :son_kullanma_tarihi, :toplam_tutar, :uyelik_indirimi, :seans_sayisi)");
            $stmt->execute([
                ':hizmet_id'          => $hizmet_id,
                ':satan_personel_id'  => $satan_personel_id,
                ':danisan_id'         => $danisan_id,
                ':satis_tarihi'       => $satis_tarihi,
                ':son_kullanma_tarihi'=> $son_kullanma_tarihi,
                ':toplam_tutar'       => $toplam_tutar,
                ':uyelik_indirimi'    => $uyelik_indirimi,
                ':seans_sayisi'       => $seans_sayisi
            ]);
            $basari = "Satış başarıyla eklendi!";
        } catch (PDOException $e) {
            $hata = "Ekleme hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Satışlar Listele";
    include "partials/title-meta.php";
    ?>

<link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <!-- Select2 CSS -->
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
            $subtitle = "Satış Yönetimi";
            $title = "Satışlar Listele";
            include "partials/page-title.php";
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="header-title mb-0">Yeni Satış Ekle</h5>
                            <a href="satis_ekle.php" class="btn btn-success btn-sm">Yeni Satış Ekle</a>
                        </div>
                        <div class="card-body pt-2">
                      
                   

<?php if($hata): ?>
    <div style="color:red; margin-bottom:10px;"><?= $hata ?></div>
<?php endif; ?>
<?php if($basari): ?>
    <div style="color:green; margin-bottom:10px;"><?= $basari ?></div>
<?php endif; ?>

<form method="POST" action="">
    <!-- Hizmet Paketi Seçimi -->
    <div class="mb-3">
        <label for="hizmet_id" class="form-label">Hizmet Paketi</label>
        <select name="hizmet_id" id="hizmet_id" class="form-control" required>
            <option value="">Seçiniz</option>
            <?php foreach($hizmetler as $h): ?>
                <option value="<?= $h['id'] ?>"><?= $h['hizmet_adi'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <!-- Satan Personel Seçimi (Select2 ile aramalı) -->
    <div class="mb-3">
        <label for="satan_personel_id" class="form-label">Satan Personel</label>
        <select name="satan_personel_id" id="satan_personel_id" class="form-control" required>
            <option value="">Seçiniz</option>
            <?php foreach($personeller as $p): ?>
                <option value="<?= $p['id'] ?>"><?= $p['ad'] . " " . $p['soyad'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <!-- Danışan Seçimi (Select2 ile aramalı, ad, soyad, telefon bilgisiyle) -->
    <div class="mb-3">
        <label for="danisan_id" class="form-label">Danışan</label>
        <select name="danisan_id" id="danisan_id" class="form-control" required>
            <option value="">Seçiniz</option>
            <?php foreach($danisanlar as $d): ?>
                <option value="<?= $d['id'] ?>">
                    <?= $d['ad'] . " " . $d['soyad'] . " (" . $d['telefon'] . ")" ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <!-- Satış Tarihi -->
    <div class="mb-3">
        <label for="satis_tarihi" class="form-label">Satış Tarihi</label>
        <input type="date" name="satis_tarihi" id="satis_tarihi" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>
    <!-- Son Kullanma Tarihi -->
    <div class="mb-3">
        <label for="son_kullanma_tarihi" class="form-label">Son Kullanma Tarihi</label>
        <input type="date" name="son_kullanma_tarihi" id="son_kullanma_tarihi" class="form-control">
    </div>
    <!-- Toplam Tutar -->
    <div class="mb-3">
        <label for="toplam_tutar" class="form-label">Toplam Tutar</label>
        <input type="number" step="0.01" name="toplam_tutar" id="toplam_tutar" class="form-control">
    </div>
    <!-- Üyelik İndirimi -->
    <div class="mb-3">
        <label for="uyelik_indirimi" class="form-label">Üyelik İndirimi (%)</label>
        <input type="number" step="0.01" name="uyelik_indirimi" id="uyelik_indirimi" class="form-control">
    </div>
    <!-- Seans Sayısı -->
    <div class="mb-3">
        <label for="seans_sayisi" class="form-label">Seans Sayısı</label>
        <input type="number" name="seans_sayisi" id="seans_sayisi" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="satislar_listesi.php" class="btn btn-secondary">İptal</a>
</form>


                        </div> 
                    </div> 
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'partials/customizer.php' ?>
<?php include 'partials/footer-scripts.php' ?>

<!-- jQuery (Zaten varsa projenizde mevcut olabilir) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Danışan ve satan personel alanlarına Select2 arama özelliği ekliyoruz
        $('#danisan_id').select2({
            placeholder: "Danışan seçiniz",
            allowClear: true,
            width: '100%'
        });
        $('#satan_personel_id').select2({
            placeholder: "Personel seçiniz",
            allowClear: true,
            width: '100%'
        });
    });
</script>


</body>
</html>
