<?php
// hizmet_paketi_ekle.php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// 1) Üyelik türleri ve sponsorluklar tablolarından verileri çekelim
try {
    // Üyelik türleri
    $uyelikTurleriStmt = $pdo->query("SELECT id, tur_adi, varsayilan_fiyat, varsayilan_hediye_seans FROM uyelik_turleri");
    $uyelikTurleri = $uyelikTurleriStmt->fetchAll(PDO::FETCH_ASSOC);

    // Sponsorluklar
    $sponsorluklarStmt = $pdo->query("SELECT id, firma_adi, indirim_orani, son_kullanma_tarihi FROM sponsorluklar");
    $sponsorluklar = $sponsorluklarStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}

// 2) Form post edildiğinde INSERT işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hizmet_adi          = $_POST['hizmet_adi'] ?? '';
    $adet                = $_POST['adet'] ?? 0;
    $fiyat               = $_POST['fiyat'] ?? 0;  // JS ile hesaplanmış son fiyat
    $uyelik_turu         = $_POST['uyelik_turu'] ?? '';
    $hediye_seans_sayisi = $_POST['hediye_seans_sayisi'] ?? 0;
    $son_kullanma_tarihi = $_POST['son_kullanma_tarihi'] ?? null;
    $sponsorluk_id       = $_POST['sponsorluk_id'] ?? null;

    // Gerekli alanlar kontrolü
    if (empty($hizmet_adi)) {
        $hata = "Hizmet adı boş olamaz!";
    } elseif (empty($uyelik_turu)) {
        $hata = "Lütfen bir üyelik türü seçiniz!";
    } else {
        try {
            // Hizmet paketini ekleyelim
            $stmt = $pdo->prepare("
                INSERT INTO hizmet_paketleri 
                (hizmet_adi, adet, fiyat, guncelleme_tarih, uyelik_turu, hediye_seans_sayisi, son_kullanma_tarihi, sponsorluk_id)
                VALUES
                (:hizmet_adi, :adet, :fiyat, NOW(), :uyelik_turu, :hediye_seans_sayisi, :son_kullanma_tarihi, :sponsorluk_id)
            ");
            $stmt->execute([
                ':hizmet_adi'          => $hizmet_adi,
                ':adet'                => $adet,
                ':fiyat'               => $fiyat,  // Sponsor + üyelik türü analizi sonrası
                ':uyelik_turu'         => $uyelik_turu,
                ':hediye_seans_sayisi' => $hediye_seans_sayisi,
                ':son_kullanma_tarihi' => $son_kullanma_tarihi,
                ':sponsorluk_id'       => $sponsorluk_id
            ]);

            $basari = "Hizmet paketi başarıyla eklendi!";
        } catch (PDOException $e) {
            $hata = "Ekleme hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hizmet Paketi Ekle</title>
    <?php
    // Projende partial dosyaların varsa ekle
    // $title = "Hizmet Paketi Ekle";
    // include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <!-- Örnek CSS (kendi stil dosyalarını ekleyebilirsin) -->
    <style>
        .form-control { width: 100%; padding: 8px; margin-bottom: 10px; }
        .btn { padding: 8px 16px; margin-right: 5px; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: #fff; border: none; }
        .btn-secondary { background-color: #6c757d; color: #fff; border: none; }
        .alert { padding: 10px; margin-bottom: 10px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
    </style>

<?php
    $title = "Hizmet Paketleri Listele";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>

</head>
<body>
<div class="wrapper">
    <!-- Örnek: menü ve topbar partial'lar -->
    <!-- <?php include 'partials/sidenav.php'; ?> -->
    <!-- <?php include 'partials/topbar.php'; ?> -->

    <div class="page-content">
        <div class="page-container">
            <h1>Hizmet Paketi Ekle</h1>

            <?php if($hata): ?>
                <div class="alert alert-danger"><?= $hata ?></div>
            <?php endif; ?>
            <?php if($basari): ?>
                <div class="alert alert-success"><?= $basari ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Hizmet Adı -->
                <label for="hizmet_adi" class="form-label">Hizmet Adı</label>
                <input type="text" name="hizmet_adi" id="hizmet_adi" class="form-control" required>

                <!-- Adet -->
                <label for="adet" class="form-label">Adet</label>
                <input type="number" name="adet" id="adet" class="form-control" min="0" value="1">

                <!-- Üyelik Türü (dinamik) -->
                <label for="uyelik_turu" class="form-label">Üyelik Türü</label>
                <select name="uyelik_turu" id="uyelik_turu" class="form-control" required>
                    <option value="">Seçiniz</option>
                    <?php foreach($uyelikTurleri as $tur): ?>
                        <option value="<?= $tur['tur_adi'] ?>"
                                data-fiyat="<?= $tur['varsayilan_fiyat'] ?>"
                                data-seans="<?= $tur['varsayilan_hediye_seans'] ?>">
                            <?= $tur['tur_adi'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Sponsorluk (dinamik) -->
                <label for="sponsorluk_id" class="form-label">Sponsorluk (Opsiyonel)</label>
                <select name="sponsorluk_id" id="sponsorluk_id" class="form-control">
                    <option value="">Seçiniz</option>
                    <?php foreach($sponsorluklar as $s): ?>
                        <option value="<?= $s['id'] ?>"
                                data-indirim="<?= $s['indirim_orani'] ?>"
                                data-sonkullanma="<?= $s['son_kullanma_tarihi'] ?>">
                            <?= $s['firma_adi'] ?> (İnd. %<?= $s['indirim_orani'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Fiyat (dinamik hesaplanacak) -->
                <label for="fiyat" class="form-label">Fiyat</label>
                <input type="number" step="0.01" name="fiyat" id="fiyat" class="form-control" value="0" required>

                <!-- Hediye Seans Sayısı (dinamik) -->
                <label for="hediye_seans_sayisi" class="form-label">Hediye Seans Sayısı</label>
                <input type="number" name="hediye_seans_sayisi" id="hediye_seans_sayisi" class="form-control" min="0" value="0">

                <!-- Son Kullanma Tarihi -->
                <label for="son_kullanma_tarihi" class="form-label">Son Kullanma Tarihi</label>
                <input type="date" name="son_kullanma_tarihi" id="son_kullanma_tarihi" class="form-control">

                <button type="submit" class="btn btn-primary">Kaydet</button>
                <a href="hizmet_paketleri_listesi.php" class="btn btn-secondary">İptal</a>
            </form>
        </div>
    </div>
</div>
<?php include 'partials/customizer.php' ?>
<?php include 'partials/footer-scripts.php' ?>
<!-- Basit JS kodu: Üyelik Türü ve Sponsorluk seçimine göre fiyat & hediye seans güncelleme -->
<script>
    const selectUyelik = document.getElementById('uyelik_turu');
    const selectSponsor = document.getElementById('sponsorluk_id');
    const inputFiyat = document.getElementById('fiyat');
    const inputSeans = document.getElementById('hediye_seans_sayisi');

    // Üyelik türü değiştiğinde varsayılan fiyat ve seans set edelim
    selectUyelik.addEventListener('change', function() {
        // Seçili option'un data-fiyat ve data-seans değerlerini al
        let seciliOption = selectUyelik.options[selectUyelik.selectedIndex];
        let varsayilanFiyat = parseFloat(seciliOption.getAttribute('data-fiyat')) || 0;
        let varsayilanSeans = parseInt(seciliOption.getAttribute('data-seans')) || 0;

        // Sponsorluk seçili ise indirim uygulayalım
        let sponsorOption = selectSponsor.options[selectSponsor.selectedIndex];
        let indirimOrani = parseFloat(sponsorOption?.getAttribute('data-indirim')) || 0;

        // Fiyat = üyelik_türü_fiyat - (üyelik_türü_fiyat * indirimOrani / 100)
        let sonFiyat = varsayilanFiyat;
        if (indirimOrani > 0) {
            sonFiyat = sonFiyat - (sonFiyat * (indirimOrani / 100));
        }

        // Değerleri ilgili inputlara aktar
        inputFiyat.value = sonFiyat.toFixed(2);
        inputSeans.value = varsayilanSeans;
    });

    // Sponsorluk değiştiğinde, mevcut üyelik türü fiyatından indirim uygulayalım
    selectSponsor.addEventListener('change', function() {
        let seciliUyelikOption = selectUyelik.options[selectUyelik.selectedIndex];
        let varsayilanFiyat = parseFloat(seciliUyelikOption.getAttribute('data-fiyat')) || 0;

        let sponsorOption = selectSponsor.options[selectSponsor.selectedIndex];
        let indirimOrani = parseFloat(sponsorOption?.getAttribute('data-indirim')) || 0;

        // Hesaplama: varsayılan üyelik fiyatı - (indirimOrani %)
        let sonFiyat = varsayilanFiyat;
        if (indirimOrani > 0) {
            sonFiyat = sonFiyat - (sonFiyat * (indirimOrani / 100));
        }

        // Güncel fiyatı input'a aktar
        inputFiyat.value = sonFiyat.toFixed(2);
    });
</script>
</body>
</html>
