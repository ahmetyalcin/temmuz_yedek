<?php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firma_adi           = $_POST['firma_adi'] ?? '';
    $indirim_orani       = $_POST['indirim_orani'] ?? 0;
    $son_kullanma_tarihi = $_POST['son_kullanma_tarihi'] ?? null;
    $aciklama            = $_POST['aciklama'] ?? '';

    if (empty($firma_adi)) {
        $hata = "Firma adı boş olamaz!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO sponsorluklar (firma_adi, indirim_orani, son_kullanma_tarihi, aciklama)
                                   VALUES (:firma_adi, :indirim_orani, :son_kullanma_tarihi, :aciklama)");
            $stmt->execute([
                ':firma_adi'           => $firma_adi,
                ':indirim_orani'       => $indirim_orani,
                ':son_kullanma_tarihi' => $son_kullanma_tarihi,
                ':aciklama'            => $aciklama
            ]);
            $basari = "Sponsorluk başarıyla eklendi!";
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
    $title = "Sponsorluk Ekle";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>

    <style>
        .form-control { width: 100%; padding: 8px; margin-bottom: 10px; }
        .btn { padding: 8px 16px; margin-right: 5px; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: #fff; border: none; }
        .btn-secondary { background-color: #6c757d; color: #fff; border: none; }
        .alert { padding: 10px; margin-bottom: 10px; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
    </style>

</head>

<body>
<div class="wrapper">
    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>

    <div class="page-content">
        <div class="page-container">
            <?php
            $subtitle = "Sponsorluk Ekle";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Yeni Sponsorluk Ekle</h5>
                        </div>
                   <div class="card-body">
                        <?php if($hata): ?>
                <div style="color:red;"><?= $hata ?></div>
            <?php endif; ?>
            <?php if($basari): ?>
                <div style="color:green;"><?= $basari ?></div>
            <?php endif; ?>
          
            <form method="POST" action="">
                <div>
                    <label>Firma Adı</label>
                    <input type="text" name="firma_adi" class="form-control" required>
                </div>
                <div>
                    <label>İndirim Oranı (%)</label>
                    <input type="number" step="0.01" name="indirim_orani" class="form-control" required>
                </div>
                <div>
                    <label>Son Kullanma Tarihi</label>
                    <input type="date" name="son_kullanma_tarihi" class="form-control">
                </div>
                <div>
                    <label>Açıklama</label>
                    <textarea name="aciklama" class="form-control"></textarea>
                </div>
                <br>
                <button type="submit" class="btn btn-primary">Kaydet</button>
                <a href="sponsorluklar_listesi.php" class="btn btn-secondary">Geri</a>
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
</body>
</html>
