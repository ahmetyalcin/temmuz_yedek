<?php
session_start();
include 'con/db2.php'; // Veritabanı bağlantısı

$hata = "";
$basari = "";
$danisan = null;
$telefon = "";
$aramaYapildi = false; // Arama yapılıp yapılmadığını kontrol etmek için
$active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] : 'ilk_kayit_tespitleri';

// Silme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id']) && isset($_POST['delete_type'])) {
    $delete_id = intval($_POST['delete_id']);
    $delete_type = $_POST['delete_type'];
    $active_tab = $delete_type; // Set active tab to the current delete type
    
    $table_name = '';
    switch ($delete_type) {
        case 'ilk_kayit_tespitleri':
            $table_name = 'danisan_ilk_kayit_tespitleri';
            break;
        case 'olcum_degerleri':
            $table_name = 'danisan_olcum_degerleri';
            break;
        case 'doktor_raporlari':
            $table_name = 'danisan_doktor_raporlari';
            break;
        case 'beslenme_listeleri':
            $table_name = 'danisan_beslenme_listeleri';
            break;
        case 'talepler':
            $table_name = 'danisan_talepler';
            break;
        case 'aciklamalar':
            $table_name = 'danisan_aciklamalar';
            break;
    }
    
    if (!empty($table_name)) {
        $stmt = $pdo->prepare("DELETE FROM $table_name WHERE id = :id");
        $stmt->bindParam(":id", $delete_id);
        if ($stmt->execute()) {
            $basari = "Kayıt başarıyla silindi!";
            
            // Danışan bilgilerini tekrar yükle
            if (isset($_POST['danisan_id'])) {
                $danisan_id = intval($_POST['danisan_id']);
                $stmt = $pdo->prepare("SELECT * FROM danisan WHERE id = :id");
                $stmt->execute(['id' => $danisan_id]);
                $danisan = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $hata = "Kayıt silinirken bir hata oluştu!";
        }
    }
}

// Danışan arama işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['telefon']) && !isset($_POST['form_type']) && !isset($_POST['delete_id'])) {
    $telefon = trim($_POST['telefon']);
    $aramaYapildi = true; // Arama yapıldı
    $stmt = $pdo->prepare("SELECT * FROM danisan WHERE telefon = :telefon");
    $stmt->execute(['telefon' => $telefon]);
    $danisan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$danisan) {
        $hata = "Bu telefon numarası ile kayıtlı danışan bulunamadı. Yeni kayıt oluşturabilirsiniz.";
    }
}

// Yeni danışan kaydı
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ad'])) {
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $telefon = trim($_POST['telefon']); // Telefon numarasını al
    $adres = trim($_POST['adres']);
    $yas = intval($_POST['yas']);
    $cinsiyet = $_POST['cinsiyet'];
    $mezuniyet = $_POST['mezuniyet'];
    $meslek = trim($_POST['meslek']);

    $stmt = $pdo->prepare("INSERT INTO danisan (ad, soyad, telefon, adres, yas, cinsiyet, mezuniyet, meslek) 
                          VALUES (:ad, :soyad, :telefon, :adres, :yas, :cinsiyet, :mezuniyet, :meslek)");
    $stmt->bindParam(":ad", $ad);
    $stmt->bindParam(":soyad", $soyad);
    $stmt->bindParam(":telefon", $telefon);
    $stmt->bindParam(":adres", $adres);
    $stmt->bindParam(":yas", $yas);
    $stmt->bindParam(":cinsiyet", $cinsiyet);
    $stmt->bindParam(":mezuniyet", $mezuniyet);
    $stmt->bindParam(":meslek", $meslek);

    if ($stmt->execute()) {
        $basari = "Danışan başarıyla kaydedildi!";
        $danisan_id = $pdo->lastInsertId();
        $danisan = $pdo->query("SELECT * FROM danisan WHERE id = $danisan_id")->fetch(PDO::FETCH_ASSOC);
    } else {
        $hata = "Danışan kaydedilirken bir hata oluştu!";
    }
}

// Dinamik veri kaydetme işlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && isset($_POST['danisan_id'])) {
    $danisan_id = intval($_POST['danisan_id']);
    $form_type = $_POST['form_type'];
    $active_tab = $form_type; // Set active tab to the submitted form type
    
    // Fetch danisan data again to ensure it's available
    $stmt = $pdo->prepare("SELECT * FROM danisan WHERE id = :id");
    $stmt->execute(['id' => $danisan_id]);
    $danisan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($danisan) {
        switch ($form_type) {
            case 'ilk_kayit_tespitleri':
                if (isset($_POST['tespit'])) {
                    $tespit = trim($_POST['tespit']);
                    $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');
                    
                    $stmt = $pdo->prepare("INSERT INTO danisan_ilk_kayit_tespitleri (danisan_id, tespit, tarih) VALUES (:danisan_id, :tespit, :tarih)");
                    $stmt->bindParam(":danisan_id", $danisan_id);
                    $stmt->bindParam(":tespit", $tespit);
                    $stmt->bindParam(":tarih", $tarih);
                    if ($stmt->execute()) {
                        $basari = "İlk kayıt tespiti başarıyla kaydedildi!";
                    } else {
                        $hata = "İlk kayıt tespiti kaydedilirken bir hata oluştu!";
                    }
                }
                break;
                
            case 'olcum_degerleri':
                if (isset($_POST['yag'])) {
                    $yag = floatval($_POST['yag']);
                    $kas = floatval($_POST['kas']);
                    $kilo = floatval($_POST['kilo']);
                    $posturel_analiz = trim($_POST['posturel_analiz']);
                    $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');

                    $stmt = $pdo->prepare("INSERT INTO danisan_olcum_degerleri (danisan_id, yag, kas, kilo, posturel_analiz, tarih) VALUES (:danisan_id, :yag, :kas, :kilo, :posturel_analiz, :tarih)");
                    $stmt->bindParam(":danisan_id", $danisan_id);
                    $stmt->bindParam(":yag", $yag);
                    $stmt->bindParam(":kas", $kas);
                    $stmt->bindParam(":kilo", $kilo);
                    $stmt->bindParam(":posturel_analiz", $posturel_analiz);
                    $stmt->bindParam(":tarih", $tarih);

                    if ($stmt->execute()) {
                        $basari = "Ölçüm değerleri başarıyla kaydedildi!";
                    } else {
                        $hata = "Ölçüm değerleri kaydedilirken bir hata oluştu!";
                    }
                }
                break;
                
            case 'doktor_raporlari':
                if (isset($_POST['rapor'])) {
                    $rapor = trim($_POST['rapor']);
                    $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');
                    
                    $stmt = $pdo->prepare("INSERT INTO danisan_doktor_raporlari (danisan_id, rapor, tarih) VALUES (:danisan_id, :rapor, :tarih)");
                    $stmt->bindParam(":danisan_id", $danisan_id);
                    $stmt->bindParam(":rapor", $rapor);
                    $stmt->bindParam(":tarih", $tarih);
                    if ($stmt->execute()) {
                        $basari = "Doktor raporu başarıyla kaydedildi!";
                    } else {
                        $hata = "Doktor raporu kaydedilirken bir hata oluştu!";
                    }
                }
                break;
                
            case 'beslenme_listeleri':
                if (isset($_POST['liste'])) {
                    $liste = trim($_POST['liste']);
                    $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');
                    
                    $stmt = $pdo->prepare("INSERT INTO danisan_beslenme_listeleri (danisan_id, liste, tarih) VALUES (:danisan_id, :liste, :tarih)");
                    $stmt->bindParam(":danisan_id", $danisan_id);
                    $stmt->bindParam(":liste", $liste);
                    $stmt->bindParam(":tarih", $tarih);
                    if ($stmt->execute()) {
                        $basari = "Beslenme listesi başarıyla kaydedildi!";
                    } else {
                        $hata = "Beslenme listesi kaydedilirken bir hata oluştu!";
                    }
                }
                break;
                
            case 'talepler':
                if (isset($_POST['talep'])) {
                    $talep = trim($_POST['talep']);
                    $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');
                    
                    $stmt = $pdo->prepare("INSERT INTO danisan_talepler (danisan_id, talep, tarih) VALUES (:danisan_id, :talep, :tarih)");
                    $stmt->bindParam(":danisan_id", $danisan_id);
                    $stmt->bindParam(":talep", $talep);
                    $stmt->bindParam(":tarih", $tarih);
                    if ($stmt->execute()) {
                        $basari = "Talep başarıyla kaydedildi!";
                    } else {
                        $hata = "Talep kaydedilirken bir hata oluştu!";
                    }
                }
                break;
                
            case 'aciklamalar':
                if (isset($_POST['aciklama'])) {
                    $aciklama = trim($_POST['aciklama']);
                    $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');
                    
                    $stmt = $pdo->prepare("INSERT INTO danisan_aciklamalar (danisan_id, aciklama, tarih) VALUES (:danisan_id, :aciklama, :tarih)");
                    $stmt->bindParam(":danisan_id", $danisan_id);
                    $stmt->bindParam(":aciklama", $aciklama);
                    $stmt->bindParam(":tarih", $tarih);
                    if ($stmt->execute()) {
                        $basari = "Açıklama başarıyla kaydedildi!";
                    } else {
                        $hata = "Açıklama kaydedilirken bir hata oluştu!";
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Danışan Yönetimi";
    include "partials/title-meta.php";
    include 'partials/head-css.php';
    ?>
    <link href="assets/vendor/custombox/custombox.min.css" rel="stylesheet" type="text/css">
    <style>
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
        }
        .delete-btn:hover {
            color: #bd2130;
        }
    </style>
</head>

<body>
<div class="wrapper">
    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>

    <div class="page-content">
        <div class="page-container">
            <?php
            $subtitle = "Danışan";
            $title = "Yönetim";
            include "partials/page-title.php";
            ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($hata)) echo "<div class='alert alert-danger'>$hata</div>"; ?>
                            <?php if (!empty($basari)) echo "<div class='alert alert-success'>$basari</div>"; ?>

                            <!-- Danışan Arama Formu -->
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>Telefon Numarası ile Danışan Ara</label>
                                    <input type="text" name="telefon" class="form-control" value="<?php echo htmlspecialchars($telefon); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Ara</button>
                            </form>

                            <!-- Yeni Danışan Kayıt Formu -->
                            <?php if ($aramaYapildi && !$danisan) { ?>
                                <h5 class="mt-4">Yeni Danışan Kaydı</h5>
                                <form method="post" action="">
                                    <input type="hidden" name="telefon" value="<?php echo htmlspecialchars($telefon); ?>">
                                    <div class="form-group">
                                        <label>Ad</label>
                                        <input type="text" name="ad" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Soyad</label>
                                        <input type="text" name="soyad" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Telefon</label>
                                        <input type="text" name="telefon" class="form-control" value="<?php echo htmlspecialchars($telefon); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Adres</label>
                                        <textarea name="adres" class="form-control"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Yaş</label>
                                        <input type="number" name="yas" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Cinsiyet</label>
                                        <select name="cinsiyet" class="form-control">
                                            <option value="Erkek">Erkek</option>
                                            <option value="Kadın">Kadın</option>
                                            <option value="Diğer">Diğer</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Mezuniyet</label>
                                        <input type="text" name="mezuniyet" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Meslek</label>
                                        <input type="text" name="meslek" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-success">Kaydet</button>
                                </form>
                            <?php } ?>

                            <!-- Danışan Bilgileri ve Dinamik Veriler -->
                            <?php if ($danisan) { ?>
                                <div class="card mt-4">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">Danışan Bilgileri</h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Ad:</strong> <?php echo htmlspecialchars($danisan['ad']); ?></p>
                                            <p><strong>Soyad:</strong> <?php echo htmlspecialchars($danisan['soyad']); ?></p>
                                            <p><strong>Telefon:</strong> <?php echo htmlspecialchars($danisan['telefon']); ?></p>
                                            <p><strong>Adres:</strong> <?php echo htmlspecialchars($danisan['adres']); ?></p>
                                            <p><strong>Yaş:</strong> <?php echo htmlspecialchars($danisan['yas']); ?></p>
                                            <p><strong>Cinsiyet:</strong> <?php echo htmlspecialchars($danisan['cinsiyet']); ?></p>
                                            <p><strong>Mezuniyet:</strong> <?php echo htmlspecialchars($danisan['mezuniyet']); ?></p>
                                            <p><strong>Meslek:</strong> <?php echo htmlspecialchars($danisan['meslek']); ?></p>
                                        </div>
                                        </div>

                                <!-- Dinamik Veriler -->
                                <h5 class="mt-4">Danışan Veriler</h5>

                                <div class="card-body pt-2">
                                    <ul class="nav nav-tabs mb-3">
                                        <li class="nav-item">
                                            <a href="#ilk_kayit_tespitleri" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $active_tab == 'ilk_kayit_tespitleri' ? 'active' : ''; ?>">
                                                İlk Kayıt Tespitleri
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#olcum_degerleri" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $active_tab == 'olcum_degerleri' ? 'active' : ''; ?>">
                                                Ölçüm Değerleri
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#doktor_raporlari" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $active_tab == 'doktor_raporlari' ? 'active' : ''; ?>">
                                                Doktor Raporları
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#beslenme_listeleri" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $active_tab == 'beslenme_listeleri' ? 'active' : ''; ?>">
                                                Beslenme Listeleri
                                            </a>
                                        </li>
                                         <li class="nav-item">
                                            <a href="#talepler" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $active_tab == 'talepler' ? 'active' : ''; ?>">
                                                Talepler
                                            </a>
                                        </li>
                                         <li class="nav-item">
                                            <a href="#aciklamalar" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $active_tab == 'aciklamalar' ? 'active' : ''; ?>">
                                                Açıklamalar
                                            </a>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- İlk Kayıt Tespitleri -->
                                        <div class="tab-pane <?php echo $active_tab == 'ilk_kayit_tespitleri' ? 'show active' : ''; ?>" id="ilk_kayit_tespitleri">
                                            <a href="#ilk_kayit_tespitleri_modal" class="btn btn-primary waves-effect waves-light mb-3"
                                               data-animation="fadein" data-plugin="custommodal" data-overlaySpeed="200"
                                               data-overlayColor="#36404a">Yeni Kayıt Gir</a>

                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Tespit</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $tespitler_query = $pdo->prepare("SELECT * FROM danisan_ilk_kayit_tespitleri WHERE danisan_id = :danisan_id ORDER BY tarih DESC");
                                                    $tespitler_query->execute(['danisan_id' => $danisan['id']]);
                                                    $tespitler = $tespitler_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($tespitler as $tespit):
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?php echo htmlspecialchars($tespit['id']); ?></th>
                                                            <td><?php echo htmlspecialchars($tespit['tespit']); ?></td>
                                                            <td><?php echo htmlspecialchars($tespit['tarih']); ?></td>
                                                            <td>
                                                                <form method="post" action="" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $tespit['id']; ?>">
                                                                    <input type="hidden" name="delete_type" value="ilk_kayit_tespitleri">
                                                                    <input type="hidden" name="danisan_id" value="<?php echo $danisan['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i> Sil</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Ölçüm Değerleri -->
                                        <div class="tab-pane <?php echo $active_tab == 'olcum_degerleri' ? 'show active' : ''; ?>" id="olcum_degerleri">
                                            <a href="#olcum_degerleri_modal" class="btn btn-primary waves-effect waves-light mb-3"
                                               data-animation="fadein" data-plugin="custommodal" data-overlaySpeed="200"
                                               data-overlayColor="#36404a">Yeni Kayıt Gir</a>

                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Yağ</th>
                                                        <th>Kas</th>
                                                        <th>Kilo</th>
                                                        <th>Postürel Analiz</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $olcumler_query = $pdo->prepare("SELECT * FROM danisan_olcum_degerleri WHERE danisan_id = :danisan_id ORDER BY tarih DESC");
                                                    $olcumler_query->execute(['danisan_id' => $danisan['id']]);
                                                    $olcumler = $olcumler_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($olcumler as $olcum):
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?php echo htmlspecialchars($olcum['id']); ?></th>
                                                            <td><?php echo htmlspecialchars($olcum['yag']); ?></td>
                                                            <td><?php echo htmlspecialchars($olcum['kas']); ?></td>
                                                            <td><?php echo htmlspecialchars($olcum['kilo']); ?></td>
                                                            <td><?php echo htmlspecialchars($olcum['posturel_analiz']); ?></td>
                                                            <td><?php echo htmlspecialchars($olcum['tarih']); ?></td>
                                                            <td>
                                                                <form method="post" action="" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $olcum['id']; ?>">
                                                                    <input type="hidden" name="delete_type" value="olcum_degerleri">
                                                                    <input type="hidden" name="danisan_id" value="<?php echo $danisan['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i> Sil</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                         <!-- Doktor Raporları -->
                                        <div class="tab-pane <?php echo $active_tab == 'doktor_raporlari' ? 'show active' : ''; ?>" id="doktor_raporlari">
                                            <a href="#doktor_raporlari_modal" class="btn btn-primary waves-effect waves-light mb-3"
                                               data-animation="fadein" data-plugin="custommodal" data-overlaySpeed="200"
                                               data-overlayColor="#36404a">Yeni Kayıt Gir</a>

                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Rapor</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $raporlar_query = $pdo->prepare("SELECT * FROM danisan_doktor_raporlari WHERE danisan_id = :danisan_id ORDER BY tarih DESC");
                                                    $raporlar_query->execute(['danisan_id' => $danisan['id']]);
                                                    $raporlar = $raporlar_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($raporlar as $rapor):
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?php echo htmlspecialchars($rapor['id']); ?></th>
                                                            <td><?php echo htmlspecialchars($rapor['rapor']); ?></td>
                                                            <td><?php echo htmlspecialchars($rapor['tarih']); ?></td>
                                                            <td>
                                                                <form method="post" action="" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $rapor['id']; ?>">
                                                                    <input type="hidden" name="delete_type" value="doktor_raporlari">
                                                                    <input type="hidden" name="danisan_id" value="<?php echo $danisan['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i> Sil</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                         <!-- Beslenme Listeleri -->
                                        <div class="tab-pane <?php echo $active_tab == 'beslenme_listeleri' ? 'show active' : ''; ?>" id="beslenme_listeleri">
                                            <a href="#beslenme_listeleri_modal" class="btn btn-primary waves-effect waves-light mb-3"
                                               data-animation="fadein" data-plugin="custommodal" data-overlaySpeed="200"
                                               data-overlayColor="#36404a">Yeni Kayıt Gir</a>

                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Liste</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $listeler_query = $pdo->prepare("SELECT * FROM danisan_beslenme_listeleri WHERE danisan_id = :danisan_id ORDER BY tarih DESC");
                                                    $listeler_query->execute(['danisan_id' => $danisan['id']]);
                                                    $listeler = $listeler_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($listeler as $liste):
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?php echo htmlspecialchars($liste['id']); ?></th>
                                                            <td><?php echo htmlspecialchars($liste['liste']); ?></td>
                                                            <td><?php echo htmlspecialchars($liste['tarih']); ?></td>
                                                            <td>
                                                                <form method="post" action="" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $liste['id']; ?>">
                                                                    <input type="hidden" name="delete_type" value="beslenme_listeleri">
                                                                    <input type="hidden" name="danisan_id" value="<?php echo $danisan['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i> Sil</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                         <!-- Talepler -->
                                        <div class="tab-pane <?php echo $active_tab == 'talepler' ? 'show active' : ''; ?>" id="talepler">
                                            <a href="#talepler_modal" class="btn btn-primary waves-effect waves-light mb-3"
                                               data-animation="fadein" data-plugin="custommodal" data-overlaySpeed="200"
                                               data-overlayColor="#36404a">Yeni Kayıt Gir</a>

                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Talep</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $talepler_query = $pdo->prepare("SELECT * FROM danisan_talepler WHERE danisan_id = :danisan_id ORDER BY tarih DESC");
                                                    $talepler_query->execute(['danisan_id' => $danisan['id']]);
                                                    $talepler = $talepler_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($talepler as $talep):
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?php echo htmlspecialchars($talep['id']); ?></th>
                                                            <td><?php echo htmlspecialchars($talep['talep']); ?></td>
                                                            <td><?php echo htmlspecialchars($talep['tarih']); ?></td>
                                                            <td>
                                                                <form method="post" action="" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $talep['id']; ?>">
                                                                    <input type="hidden" name="delete_type" value="talepler">
                                                                    <input type="hidden" name="danisan_id" value="<?php echo $danisan['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i> Sil</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                         <!-- Açıklamalar -->
                                        <div class="tab-pane <?php echo $active_tab == 'aciklamalar' ? 'show active' : ''; ?>" id="aciklamalar">
                                            <a href="#aciklamalar_modal" class="btn btn-primary waves-effect waves-light mb-3"
                                               data-animation="fadein" data-plugin="custommodal" data-overlaySpeed="200"
                                               data-overlayColor="#36404a">Yeni Kayıt Gir</a>

                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Açıklama</th>
                                                        <th>Tarih</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $aciklamalar_query = $pdo->prepare("SELECT * FROM danisan_aciklamalar WHERE danisan_id = :danisan_id ORDER BY tarih DESC");
                                                    $aciklamalar_query->execute(['danisan_id' => $danisan['id']]);
                                                    $aciklamalar = $aciklamalar_query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($aciklamalar as $aciklama):
                                                        ?>
                                                        <tr>
                                                            <th scope="row"><?php echo htmlspecialchars($aciklama['id']); ?></th>
                                                            <td><?php echo htmlspecialchars($aciklama['aciklama']); ?></td>
                                                            <td><?php echo htmlspecialchars($aciklama['tarih']); ?></td>
                                                            <td>
                                                                <form method="post" action="" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                                                    <input type="hidden" name="delete_id" value="<?php echo $aciklama['id']; ?>">
                                                                    <input type="hidden" name="delete_type" value="aciklamalar">
                                                                    <input type="hidden" name="danisan_id" value="<?php echo $danisan['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="mdi mdi-delete"></i> Sil</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<?php include 'partials/customizer.php'; ?>
<?php include 'partials/footer-scripts.php'; ?>

<script src="assets/vendor/custombox/custombox.min.js"></script>

<!-- Modallar -->
<!-- İlk Kayıt Tespitleri Modal -->
<div id="ilk_kayit_tespitleri_modal" class="modal-demo">
    <div class="d-flex w-100 p-3 bg-primary align-items-center justify-content-between">
        <h4 class="custom-modal-title">İlk Kayıt Tespiti Ekle</h4>
        <button type="button" class="btn-close btn-close-white" onclick="Custombox.modal.close();">
            <span class="sr-only">Close</span>
        </button>
    </div>
    <div class="custom-modal-text text-muted">
        <form method="post" action="">
            <input type="hidden" name="form_type" value="ilk_kayit_tespitleri">
            <input type="hidden" name="danisan_id" value="<?php echo $danisan ? $danisan['id'] : ''; ?>">
            <input type="hidden" name="active_tab" value="ilk_kayit_tespitleri">
            <div class="form-group">
                <label>Tespit</label>
                <textarea name="tespit" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="datetime-local" name="tarih" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

<!-- Ölçüm Değerleri Modal -->
<div id="olcum_degerleri_modal" class="modal-demo">
    <div class="d-flex w-100 p-3 bg-primary align-items-center justify-content-between">
        <h4 class="custom-modal-title">Ölçüm Değerleri Ekle</h4>
        <button type="button" class="btn-close btn-close-white" onclick="Custombox.modal.close();">
            <span class="sr-only">Close</span>
        </button>
    </div>
    <div class="custom-modal-text text-muted">
        <form method="post" action="">
            <input type="hidden" name="form_type" value="olcum_degerleri">
            <input type="hidden" name="danisan_id" value="<?php echo $danisan ? $danisan['id'] : ''; ?>">
            <input type="hidden" name="active_tab" value="olcum_degerleri">
            <div class="form-group">
                <label>Yağ</label>
                <input type="number" step="0.01" name="yag" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Kas</label>
                <input type="number" step="0.01" name="kas" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Kilo</label>
                <input type="number" step="0.01" name="kilo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Postürel Analiz</label>
                <textarea name="posturel_analiz" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="datetime-local" name="tarih" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">

            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

 <!-- Doktor Raporları Modal -->
<div id="doktor_raporlari_modal" class="modal-demo">
    <div class="d-flex w-100 p-3 bg-primary align-items-center justify-content-between">
        <h4 class="custom-modal-title">Doktor Raporları Ekle</h4>
        <button type="button" class="btn-close btn-close-white" onclick="Custombox.modal.close();">
            <span class="sr-only">Close</span>
        </button>
    </div>
    <div class="custom-modal-text text-muted">
        <form method="post" action="">
            <input type="hidden" name="form_type" value="doktor_raporlari">
            <input type="hidden" name="danisan_id" value="<?php echo $danisan ? $danisan['id'] : ''; ?>">
            <input type="hidden" name="active_tab" value="doktor_raporlari">
            <div class="form-group">
                <label>Rapor</label>
                <textarea name="rapor" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="datetime-local" name="tarih" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

 <!-- Beslenme Listeleri Modal -->
<div id="beslenme_listeleri_modal" class="modal-demo">
    <div class="d-flex w-100 p-3 bg-primary align-items-center justify-content-between">
        <h4 class="custom-modal-title">Beslenme Listeleri Ekle</h4>
        <button type="button" class="btn-close btn-close-white" onclick="Custombox.modal.close();">
            <span class="sr-only">Close</span>
        </button>
    </div>
    <div class="custom-modal-text text-muted">
        <form method="post" action="">
            <input type="hidden" name="form_type" value="beslenme_listeleri">
            <input type="hidden" name="danisan_id" value="<?php echo $danisan ? $danisan['id'] : ''; ?>">
            <input type="hidden" name="active_tab" value="beslenme_listeleri">
            <div class="form-group">
                <label>Liste</label>
                <textarea name="liste" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="datetime-local" name="tarih" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

 <!-- Talepler Modal -->
<div id="talepler_modal" class="modal-demo">
    <div class="d-flex w-100 p-3 bg-primary align-items-center justify-content-between">
        <h4 class="custom-modal-title">Talepler Ekle</h4>
        <button type="button" class="btn-close btn-close-white" onclick="Custombox.modal.close();">
            <span class="sr-only">Close</span>
        </button>
    </div>
    <div class="custom-modal-text text-muted">
        <form method="post" action="">
            <input type="hidden" name="form_type" value="talepler">
            <input type="hidden" name="danisan_id" value="<?php echo $danisan ? $danisan['id'] : ''; ?>">
            <input type="hidden" name="active_tab" value="talepler">
            <div class="form-group">
                <label>Talep</label>
                <textarea name="talep" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="datetime-local" name="tarih" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

 <!-- Açıklamalar Modal -->
<div id="aciklamalar_modal" class="modal-demo">
    <div class="d-flex w-100 p-3 bg-primary align-items-center justify-content-between">
        <h4 class="custom-modal-title">Açıklamalar Ekle</h4>
        <button type="button" class="btn-close btn-close-white" onclick="Custombox.modal.close();">
            <span class="sr-only">Close</span>
        </button>
    </div>
    <div class="custom-modal-text text-muted">
        <form method="post" action="">
            <input type="hidden" name="form_type" value="aciklamalar">
            <input type="hidden" name="danisan_id" value="<?php echo $danisan ? $danisan['id'] : ''; ?>">
            <input type="hidden" name="active_tab" value="aciklamalar">
            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="aciklama" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="datetime-local" name="tarih" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

<script>
// Automatically activate the correct tab based on PHP variable
document.addEventListener('DOMContentLoaded', function() {
    // This is a fallback in case the PHP tab activation doesn't work
    var activeTab = '<?php echo $active_tab; ?>';
    var tabElement = document.querySelector('a[href="#' + activeTab + '"]');
    if (tabElement) {
        var tab = new bootstrap.Tab(tabElement);
        tab.show();
    }
    
    // Confirm delete operations
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>

</html>
