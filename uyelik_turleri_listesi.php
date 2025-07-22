<?php
include 'con/db.php'; // Veritabanı bağlantısı
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// Hizmet paketlerini çekme
try {
    $sorgu = $pdo->query("
        SELECT * from uyelik_turleri");
    $uyelikTurleri = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sorgu hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>

<?php
    $title = "Üyelik Türleri ";
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
    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>

    <div class="page-content">
        <div class="page-container">
            <?php
            $subtitle = "Üyelik Türleri Listele";
            include "partials/title-meta.php";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="header-title mb-0">Üyelik Türleri Listele</h5>
                            <a href="uyelik_turu_ekle.php" class="btn btn-success btn-sm">Yeni Üyelik Türü Ekle</a>
                        </div>

                        <div class="card-body pt-2">
                            <div class="table-responsive">
                           
                            <table id="uyelikTable" class="table mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tür Adı</th>
                            <th>Varsayılan Fiyat</th>
                            <th>Varsayılan Hediye Seans</th>
                            <th>Açıklama</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($uyelikTurleri as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= $u['tur_adi'] ?></td>
                            <td><?= $u['varsayilan_fiyat'] ?></td>
                            <td><?= $u['varsayilan_hediye_seans'] ?></td>
                            <td><?= $u['aciklama'] ?></td>
                            <td>
                                <a href="uyelik_turu_duzenle.php?id=<?= $u['id'] ?>" class="btn btn-primary btn-sm">Düzenle</a>
                                <a href="uyelik_turu_sil.php?id=<?= $u['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
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

<?php include 'partials/customizer.php' ?>
<?php include 'partials/footer-scripts.php' ?>

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#uyelikTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
            }
        });
    });
</script>
</body>
</html>
