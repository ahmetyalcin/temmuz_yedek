<?php
session_start();

include_once 'functions.php';
include 'con/db.php'; // Veritabanı bağlantısını içeri aktar

// Personel verilerini çek
try {
    $stmt = $pdo->prepare("SELECT * FROM personel ORDER BY ad ASC");
    $stmt->execute();
    $personeller = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Veritabanı hatası: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'sifre_degistir':
                if ($_POST['yeni_sifre'] !== $_POST['yeni_sifre_tekrar']) {
                    $hata = "Yeni şifreler eşleşmiyor!";
                    break;
                }
                
                $sonuc = sifreDegistir(
                    $_POST['id'],
                    $_POST['eski_sifre'],
                    $_POST['yeni_sifre']
                );
                if ($sonuc['success']) {
                    $basari = $sonuc['message'];
                } else {
                    $hata = $sonuc['message'];
                }
                break;

            case 'terapist_guncelle':
                $updateData = [
                    'ad' => $_POST['ad'] ?? '',
                    'soyad' => $_POST['soyad'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'cinsiyet' => $_POST['cinsiyet'] ?? null,
                    'mezuniyet' => $_POST['mezuniyet'] ?? null,
                    'rol' => $_POST['rol'] ?? 'terapist'
                ];

                // Avatar yükleme işlemi
                if (!empty($_FILES['avatar']['name'])) {
                    $avatar = handleAvatarUpload($_FILES['avatar']);
                    if ($avatar['success']) {
                        $updateData['avatar'] = $avatar['filename'];
                    } else {
                        $hata = $avatar['message'];
                        break;
                    }
                }

                $sonuc = personelGuncelle($_POST['id'], $updateData);
                if ($sonuc) {
                    $basari = "Personel başarıyla güncellendi.";
                } else {
                    $hata = "Personel güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'sifre_degistir':
                if ($_POST['yeni_sifre'] !== $_POST['yeni_sifre_tekrar']) {
                    $hata = "Yeni şifreler eşleşmiyor!";
                    break;
                }
                
                $sonuc = sifreDegistir(
                    $_POST['id'],
                    $_POST['eski_sifre'],
                    $_POST['yeni_sifre']
                );
                if ($sonuc['success']) {
                    $basari = $sonuc['message'];
                } else {
                    $hata = $sonuc['message'];
                }
                break;

            case 'terapist_guncelle':
                $updateData = [
                    'ad' => $_POST['ad'] ?? '',
                    'soyad' => $_POST['soyad'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'cinsiyet' => $_POST['cinsiyet'] ?? null,
                    'mezuniyet' => $_POST['mezuniyet'] ?? null,
                    'rol' => $_POST['rol'] ?? 'terapist'
                ];

                // Avatar yükleme işlemi
                if (!empty($_FILES['avatar']['name'])) {
                    $avatar = handleAvatarUpload($_FILES['avatar']);
                    if ($avatar['success']) {
                        $updateData['avatar'] = $avatar['filename'];
                    } else {
                        $hata = $avatar['message'];
                        break;
                    }
                }

                $sonuc = personelGuncelle($_POST['id'], $updateData);
                if ($sonuc) {
                    $basari = "Personel başarıyla güncellendi.";
                } else {
                    $hata = "Personel güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Get therapist details for editing
$duzenlenecek_terapist = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $terapistler = getTerapistler(false);
    foreach ($terapistler as $terapist) {
        if ($terapist['id'] == $_GET['id']) {
            $duzenlenecek_terapist = $terapist;
            break;
        }
    }
}

// Handle activation/deactivation
if ($action == 'activate' || $action == 'deactivate') {
    $id = $_GET['id'] ?? 0;
    $aktif = $action == 'activate' ? 1 : 0;
    $sonuc = personelDurumGuncelle($id, $aktif);
    if ($sonuc) {
        header("Location: ?page=terapistler");
        exit;
    }
}

// Handle deletion
if ($action == 'delete') {
    $id = $_GET['id'] ?? 0;
    $sonuc = personelSil($id);
    if ($sonuc) {
        header("Location: ?page=terapistler");
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Personel Listesi";
    include "partials/title-meta.php";
    include 'partials/session.php';
    ?>

    <link href="assets/vendor/custombox/custombox.min.css" rel="stylesheet" type="text/css">
    <?php include 'partials/head-css.php'; ?>
</head>

<body>

<div class="wrapper">

    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>

    <div class="page-content">
        <div class="page-container">

            <?php
            $subtitle = "Kullanıcı Yönetimi";
            $title = "Personel Listesi";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-sm-4">
                    <a href="personel-kayit.php" class="btn btn-primary waves-effect waves-light mb-3">
                        <i class="md md-add"></i> Yeni Personel Ekle
                    </a>
                </div>
            </div>

            <div class="row">
                <?php foreach ($personeller as $personel): ?>
                    <div class="col-lg-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="dropdown float-end text-end">
                                    <a href="#" class="dropdown-toggle card-drop drop-arrow-none" data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <div><i class="mdi mdi-dots-horizontal h3 m-0 text-muted"></i></div>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="personel-duzenle.php?id=<?= $personel['id'] ?>">Düzenle</a>
                                        <a class="dropdown-item text-danger" href="personel-sil.php?id=<?= $personel['id'] ?>" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                                        <a class="dropdown-item text-warning" href="personel-sertifika-kayit.php?id=<?= $personel['id'] ?>">Sertifika Ekle</a>
                                    </div>
                                </div>

                                <div class="clearfix"></div>

                                <div class="member-card">
                                    <div class="avatar-xl member-thumb mb-2 mx-auto">
                                        <img src="uploads/avatars/<?= $personel['avatar'] ?>" class="rounded-circle img-thumbnail"
                                             alt="profile-image">
                                        <i class="mdi mdi-star-circle member-star text-success" title="verified user"></i>
                                    </div>

                       

                                    <div class="">
                                        <h4 class="my-1 fw-semibold"><?= htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']) ?></h4>
                                        <p class="text-muted">@<?= htmlspecialchars($personel['kullanici_adi']) ?>
                                            <span> | </span> <span class="text-pink"><?= $personel['rol'] == 'admin' ? 'Admin' : 'Personel' ?></span>
                                        </p>
                                    </div>

                                    <p class="text-muted font-13">
                                        Sicil No: <?= htmlspecialchars($personel['sicil_no']) ?> <br>
                                        Mezuniyet: <?= htmlspecialchars($personel['mezuniyet']) ?>
                                    </p>

                    <a href="personel-detay.php?id=<?= htmlspecialchars($personel['id']) ?>" class="btn btn-warning waves-effect waves-light mb-3">
                        <i class="md md-add"></i>  Detay
                    </a>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end col -->
                <?php endforeach; ?>
            </div> <!-- end row -->

        </div> <!-- container -->

        <?php include 'partials/footer.php'; ?>

    </div>

</div>

<?php include 'partials/customizer.php'; ?>
<?php include 'partials/footer-scripts.php'; ?>
<script src="assets/vendor/custombox/custombox.min.js"></script>

</body>
</html>
