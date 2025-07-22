<?php
include 'con/db2.php'; // Veritabanı bağlantısı
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// Hizmet paketlerini çekme
try {
    $sorgu = $pdo->query("
        SELECT h.*, u.tur_adi, s.firma_adi 
        FROM hizmet_paketleri h 
        LEFT JOIN uyelik_turleri u ON h.uyelik_turu = u.id 
        LEFT JOIN sponsorluklar s ON h.sponsorluk_id = s.id
    ");
    $hizmetPaketleri = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sorgu hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
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
    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>

    <div class="page-content">
        <div class="page-container">
            <?php
            $subtitle = "Hizmet Yönetimi";
            $title = "Hizmet Paketleri Listele";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="header-title mb-0">Hizmet Paketleri</h5>
                            <a href="hizmet_paketi_ekle.php" class="btn btn-success btn-sm">Yeni Hizmet Paketi Ekle</a>
                        </div>

                        <div class="card-body pt-2">
                            <div class="table-responsive">
                                <table id="hizmetPaketleriTable" class="table mb-0">
                                    <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hizmet Adı</th>
                                        <th>Adet</th>
                                        <th>Fiyat</th>
                                        <th>Güncelleme Tarihi</th>
                                        <th>Üyelik Türü</th>
                                        <th>Hediye Seans</th>
                                        <th>Son Kullanma</th>
                                        <th>Sponsorluk</th>
                                        <th>İşlemler</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($hizmetPaketleri as $paket): ?>
                                        <tr>
                                            <td><?= $paket['id'] ?></td>
                                            <td><?= $paket['hizmet_adi'] ?></td>
                                            <td><?= $paket['adet'] ?></td>
                                            <td><?= $paket['fiyat'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($paket['guncelleme_tarih'])) ?></td>
                                            <td><?= $paket['tur_adi'] ?></td>
                                            <td><?= $paket['hediye_seans_sayisi'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($paket['son_kullanma_tarihi'])) ?></td>
                                            <td><?= $paket['firma_adi'] ? $paket['firma_adi'] : "Seçilmedi" ?></td>
                                            <td>
                                                <a href="hizmet_paketi_duzenle.php?id=<?= $paket['id'] ?>" class="btn btn-primary btn-sm">Düzenle</a>
                                                <a href="hizmet_paketi_sil.php?id=<?= $paket['id'] ?>"
                                                   onclick="return confirm('Silmek istediğinize emin misiniz?')"
                                                   class="btn btn-danger btn-sm">Sil</a>
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
        $('#hizmetPaketleriTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
            }
        });
    });
</script>
</body>
</html>
