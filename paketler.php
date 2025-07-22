
<?php
session_start();

include_once 'functions.php';

$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';


$hizmetPaketleri = getHizmetPaketleri();
$seansTurleri = getSeansTurleri();

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'paket_ekle':
                $sonuc = paketEkle(
                    $_POST['ad'],
                    $_POST['seans_turu_id'],
                    $_POST['seans_sayisi'],
                    $_POST['fiyat'],
                    $_POST['gecerlilik_gun'],
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Hizmet paketi başarıyla eklendi.";
                    header("Location: ?page=paketler");
                    exit;
                } else {
                    $hata = "Hizmet paketi eklenirken bir hata oluştu.";
                }
                break;

            case 'paket_guncelle':
                $sonuc = paketGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['seans_turu_id'],
                    $_POST['seans_sayisi'],
                    $_POST['fiyat'],
                    $_POST['gecerlilik_gun'],
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Hizmet paketi başarıyla güncellendi.";
                    header("Location: ?page=paketler");
                    exit;
                } else {
                    $hata = "Hizmet paketi güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için paket bilgilerini al
$duzenlenecek_paket = null;
if ($action == 'edit' && isset($_GET['id'])) {
    foreach ($hizmetPaketleri as $paket) {
        if ($paket['id'] == $_GET['id']) {
            $duzenlenecek_paket = $paket;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Danışan Listele";
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
                $title = "Hizmet Paketleri";
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
        <!-- Hizmet Paketleri Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"></h5>
                <a href="?page=paketler&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Paket
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Paket Adı</th>
                                <th>Seans Türü</th>
                                <th>Seans Sayısı</th>
                                <th>Fiyat</th>
                                <th>Geçerlilik</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hizmetPaketleri as $paket): ?>
                            <tr>
                                <td><?php echo $paket['ad']; ?></td>
                                <td><?php echo $paket['seans_turu_adi']; ?></td>
                                <td><?php echo $paket['seans_sayisi']; ?></td>
                                <td><?php echo formatPrice($paket['fiyat']); ?></td>
                                <td><?php echo $paket['gecerlilik_gun']; ?> gün</td>
                                <td>
                                    <span class="badge bg-<?php echo $paket['aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $paket['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                    <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?action=edit&id=<?= $paket['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($paket['aktif']): ?>
                                        <button class="btn btn-warning"
                                                onclick="if(confirm('Bu paketi pasife almak istediğinizden emin misiniz?'))
                                                        window.location.href='?action=deactivate&id=<?= $paket['id'] ?>'">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-success"
                                                onclick="if(confirm('Bu paketi aktife almak istediğinizden emin misiniz?'))
                                                        window.location.href='?action=activate&id=<?= $paket['id'] ?>'">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger"
                                                onclick="if(confirm('Bu paketi silmek istediğinizden emin misiniz?'))
                                                        window.location.href='?action=delete&id=<?= $paket['id'] ?>'">
                                        <i class="fas fa-trash"></i>
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
        <!-- Hizmet Paketi Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Hizmet Paketi Düzenle' : 'Yeni Hizmet Paketi'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'paket_guncelle' : 'paket_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_paket['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Paket Adı</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_paket['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Türü</label>
                            <select name="seans_turu_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($seansTurleri as $seans): ?>
                                    <option value="<?php echo $seans['id']; ?>"
                                            <?php echo ($duzenlenecek_paket && $duzenlenecek_paket['seans_turu_id'] == $seans['id']) ? 'selected' : ''; ?>>
                                        <?php echo $seans['ad'] . ' (' . $seans['sure'] . ' dk)'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Seans Sayısı</label>
                            <input type="number" name="seans_sayisi" class="form-control" required min="1"
                                   value="<?php echo $duzenlenecek_paket['seans_sayisi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fiyat (₺)</label>
                            <input type="number" name="fiyat" class="form-control" required min="0" step="0.01"
                                   value="<?php echo $duzenlenecek_paket['fiyat'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Geçerlilik (Gün)</label>
                            <input type="number" name="gecerlilik_gun" class="form-control" required min="1"
                                   value="<?php echo $duzenlenecek_paket['gecerlilik_gun'] ?? '90'; ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"><?php echo $duzenlenecek_paket['aciklama'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=paketler" class="btn btn-secondary">İptal</a>
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

    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#danisanTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json" // Türkçe dil desteği
                }
            });
        });
    </script>
</body>

</html>