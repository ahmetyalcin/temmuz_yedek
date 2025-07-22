
<?php
session_start();

include_once 'functions.php';


$uyelikTurleri = getUyelikTurleri();





$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'uyelik_ekle':
                $sonuc = uyelikEkle(
                    $_POST['ad'],
                    $_POST['seviye'],
                    $_POST['min_seans_sayisi'],
                    $_POST['indirim_yuzdesi'],
                    $_POST['hediye_seans_sayisi'],
                    $_POST['hediye_seans_gecerlilik_gun'],
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Üyelik türü başarıyla eklendi.";
                    header("Location: ?page=uyelikler");
                    exit;
                } else {
                    $hata = "Üyelik türü eklenirken bir hata oluştu.";
                }
                break;

            case 'uyelik_guncelle':
                $sonuc = uyelikGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['seviye'],
                    $_POST['min_seans_sayisi'],
                    $_POST['indirim_yuzdesi'],
                    $_POST['hediye_seans_sayisi'],
                    $_POST['hediye_seans_gecerlilik_gun'],
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Üyelik türü başarıyla güncellendi.";
                    header("Location: ?page=uyelikler");
                    exit;
                } else {
                    $hata = "Üyelik türü güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için üyelik bilgilerini al
$duzenlenecek_uyelik = null;
if ($action == 'edit' && isset($_GET['id'])) {
    foreach ($uyelikTurleri as $uyelik) {
        if ($uyelik['id'] == $_GET['id']) {
            $duzenlenecek_uyelik = $uyelik;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Hizmet Paketleri";
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
                $subtitle = "Hizmet Paketleri";
                $title = "Üyelik Türleri";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">


<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <!-- Üyelik Türleri Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"></h5>
                <a href="?page=uyelikler&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Üyelik Türü
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad</th>
                                <th>Seviye</th>
                                <th>Min. Seans</th>
                                <th>İndirim %</th>
                                <th>Hediye Seans</th>
                                <th>Geçerlilik (Gün)</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uyelikTurleri as $uyelik): ?>
                                <tr>
                                    <td><?php echo $uyelik['ad']; ?></td>
                                    <td><?php echo $uyelik['seviye']; ?></td>
                                    <td><?php echo $uyelik['min_seans_sayisi']; ?></td>
                                    <td><?php echo $uyelik['indirim_yuzdesi']; ?>%</td>
                                    <td><?php echo $uyelik['hediye_seans_sayisi']; ?></td>
                                    <td><?php echo $uyelik['hediye_seans_gecerlilik_gun']; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=uyelikler&action=edit&id=<?php echo $uyelik['id']; ?>" 
                                            class="btn btn-primary">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="if(confirm('Bu üyelik türünü silmek istediğinizden emin misiniz?'))
                                                            window.location.href='?page=uyelikler&action=delete&id=<?php echo $uyelik['id']; ?>'">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Üyelik Türü Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Üyelik Türü Düzenle' : 'Yeni Üyelik Türü'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'uyelik_guncelle' : 'uyelik_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_uyelik['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_uyelik['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seviye</label>
                            <input type="number" name="seviye" class="form-control" required min="1"
                                   value="<?php echo $duzenlenecek_uyelik['seviye'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Seans Sayısı</label>
                            <input type="number" name="min_seans_sayisi" class="form-control" required min="0"
                                   value="<?php echo $duzenlenecek_uyelik['min_seans_sayisi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İndirim Yüzdesi (%)</label>
                            <input type="number" name="indirim_yuzdesi" class="form-control" required min="0" max="100"
                                   value="<?php echo $duzenlenecek_uyelik['indirim_yuzdesi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hediye Seans Sayısı</label>
                            <input type="number" name="hediye_seans_sayisi" class="form-control" required min="0"
                                   value="<?php echo $duzenlenecek_uyelik['hediye_seans_sayisi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hediye Seans Geçerlilik (Gün)</label>
                            <input type="number" name="hediye_seans_gecerlilik_gun" class="form-control" required min="1"
                                   value="<?php echo $duzenlenecek_uyelik['hediye_seans_gecerlilik_gun'] ?? '90'; ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"><?php echo $duzenlenecek_uyelik['aciklama'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=uyelikler" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
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