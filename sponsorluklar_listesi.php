<?php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Tüm sponsorlukları çek
    $stmt = $pdo->query("SELECT * FROM sponsorluklar");
    $sponsorluklar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veri çekme hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>

<?php
    $title = "Sponsorluklar Listele";
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
            $subtitle = "Sponsorluk  Listele";
            include "partials/title-meta.php";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="header-title mb-0">Sponsorluk Listele</h5>
                            <a href="sponsorluk_ekle.php" class="btn btn-success btn-sm">Yeni Sponsorluk Ekle</a>
                        </div>

                        <div class="card-body pt-2">
                            <div class="table-responsive">
                           
                            <table id="sponsorlukTable" class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Firma Adı</th>
                        <th>İndirim Oranı (%)</th>
                        <th>Son Kullanma Tarihi</th>
                        <th>Açıklama</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($sponsorluklar as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><?= $s['firma_adi'] ?></td>
                        <td><?= $s['indirim_orani'] ?></td>
                        <td><?= $s['son_kullanma_tarihi'] ?></td>
                        <td><?= $s['aciklama'] ?></td>
                        <td>
                            <a href="sponsorluk_duzenle.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">Düzenle</a>
                            <a href="sponsorluk_sil.php?id=<?= $s['id'] ?>"
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
        $('#sponsorlukTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
            }
        });
    });
</script>
</body>
</html>
