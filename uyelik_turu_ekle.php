<?php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tur_adi             = $_POST['tur_adi'] ?? '';
    $varsayilan_fiyat    = $_POST['varsayilan_fiyat'] ?? 0;
    $varsayilan_hediye   = $_POST['varsayilan_hediye_seans'] ?? 0;
    $aciklama            = $_POST['aciklama'] ?? '';

    if (empty($tur_adi)) {
        $hata = "Üyelik tür adı boş olamaz!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO uyelik_turleri (tur_adi, varsayilan_fiyat, varsayilan_hediye_seans, aciklama)
                                   VALUES (:tur_adi, :varsayilan_fiyat, :varsayilan_hediye_seans, :aciklama)");
            $stmt->execute([
                ':tur_adi'               => $tur_adi,
                ':varsayilan_fiyat'      => $varsayilan_fiyat,
                ':varsayilan_hediye_seans' => $varsayilan_hediye,
                ':aciklama'              => $aciklama
            ]);
            $basari = "Üyelik türü başarıyla eklendi!";
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
    $title = "Üyelik Türleri Ekle";
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
            $subtitle = "Üyelik Türleri";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Yeni Hizmet Paketi Ekle</h5>
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
                    <label>Tür Adı</label>
                    <input type="text" name="tur_adi" class="form-control" required>
                </div>
                <div>
                    <label>Varsayılan Fiyat</label>
                    <input type="number" step="0.01" name="varsayilan_fiyat" class="form-control" required>
                </div>
                <div>
                    <label>Varsayılan Hediye Seans</label>
                    <input type="number" name="varsayilan_hediye_seans" class="form-control" required>
                </div>
                <div>
                    <label>Açıklama</label>
                    <textarea name="aciklama" class="form-control"></textarea>
                </div>
                <br>
                <button type="submit" class="btn btn-primary">Kaydet</button>
                <a href="uyelik_turleri_listesi.php" class="btn btn-secondary">Geri</a>
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
