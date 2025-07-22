<?php
// satislar_listesi.php
include 'con/db2.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Satışları JOIN sorgusu ile çekiyoruz
try {
    $sql = "SELECT s.*, 
                   h.hizmet_adi, 
                   CONCAT(p.ad, ' ', p.soyad) AS personel_ad, 
                   CONCAT(d.ad, ' ', d.soyad) AS danisan_ad, 
                   d.telefon AS danisan_telefon 
            FROM satislar s
            LEFT JOIN hizmet_paketleri h ON s.hizmet_id = h.id
            LEFT JOIN personel p ON s.satan_personel_id = p.id
            LEFT JOIN danisan d ON s.danisan_id = d.id";
    $stmt = $pdo->query($sql);
    $satislar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sorgu hatası: " . $e->getMessage());
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
            $subtitle = "Satış Yönetimi";
            $title = "Satışlar Listele";
            include "partials/page-title.php";
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="header-title mb-0">Satışlar</h5>
                            <a href="satis_ekle.php" class="btn btn-success btn-sm">Yeni Satış Ekle</a>
                        </div>
                        <div class="card-body pt-2">
                            <div class="table-responsive">
                                <table id="satislarTable" class="table mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Hizmet</th>
                                            <th>Satan Personel</th>
                                            <th>Danışan</th>
                                            <th>Satış Tarihi</th>
                                            <th>Son Kullanma</th>
                                            <th>Toplam Tutar</th>
                                            <th>Üyelik İndirimi</th>
                                            <th>Seans Sayısı</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($satislar as $satis): ?>
                                            <tr>
                                                <td><?= $satis['id'] ?></td>
                                                <td><?= $satis['hizmet_adi'] ?></td>
                                                <td><?= $satis['personel_ad'] ?></td>
                                                <td>
                                                    <?= $satis['danisan_ad'] ?>
                                                    (<?= $satis['danisan_telefon'] ?>)
                                                </td>
                                                <td><?= $satis['satis_tarihi'] ?></td>
                                                <td><?= $satis['son_kullanma_tarihi'] ?></td>
                                                <td><?= $satis['toplam_tutar'] ?></td>
                                                <td><?= $satis['uyelik_indirimi'] ?></td>
                                                <td><?= $satis['seans_sayisi'] ?></td>
                                                <td>
                                                    <a href="satis_duzenle.php?id=<?= $satis['id'] ?>" class="btn btn-primary btn-sm">Düzenle</a>
                                                    <a href="satis_sil.php?id=<?= $satis['id'] ?>"
                                                       onclick="return confirm('Satışı silmek istediğinize emin misiniz?')"
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
        $('#satislarTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
            }
        });
    });
</script>
</body>
</html>
