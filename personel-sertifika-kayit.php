<?php
include 'con/db.php'; // Veritabanı bağlantısı
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// Sertifikaların yükleneceği dizin
$upload_dir = "uploads/certificates/";
$personel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Default sertifika dosyası
$default_sertifika = "default-certificate.pdf"; // Varsayılan sertifika dosyası adı

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $sertifika_adi = trim($_POST["sertifika_adi"]);
  $sertifika = $default_sertifika; // Varsayılan olarak default sertifika kullan

  // Dosya yükleme işlemi (opsiyonel)
  if (!empty($_FILES["sertifika"]["name"])) {
    $sertifika_name = time() . "_" . basename($_FILES["sertifika"]["name"]);
    $sertifika_path = $upload_dir . $sertifika_name;

    // Dosya tipi kontrolü
    $allowed_types = ["image/jpeg", "image/png", "image/jpg", "application/pdf"];
    if (!in_array($_FILES["sertifika"]["type"], $allowed_types)) {
      $hata = "Sadece JPG, JPEG, PNG veya PDF dosyaları yükleyebilirsiniz!";
    } else {
      // Dosyayı sunucuya yükle
      if (move_uploaded_file($_FILES["sertifika"]["tmp_name"], $sertifika_path)) {
        $sertifika = $sertifika_name;
      } else {
        $hata = "Sertifika yüklenirken hata oluştu!";
      }
    }
  }

  // Sertifika ekleme
  if (empty($hata)) {
    if (!empty($sertifika_adi) && $personel_id > 0) {
      $stmt = $pdo->prepare("INSERT INTO personel_sertifikalar (personel_id, sertifika_adi, sertifika) 
                            VALUES (:personel_id, :sertifika_adi, :sertifika)");
      $stmt->bindParam(":personel_id", $personel_id);
      $stmt->bindParam(":sertifika_adi", $sertifika_adi);
      $stmt->bindParam(":sertifika", $sertifika);

      if ($stmt->execute()) {
        $basari = "Sertifika başarıyla eklendi!";
      } else {
        $hata = "Sertifika eklenirken bir hata oluştu!";
      }
    } else {
      $hata = "Lütfen sertifika adını girin!";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
  <?php
  $title = "Sertifika Ekle";
  include "partials/title-meta.php";
  ?>
  <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
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
        $subtitle = "Kullanıcı Yönetimi";
        $title = "Sertifika Ekle";
        include "partials/page-title.php";
        ?>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5 class="header-title">Sertifika Ekle</h5>
              </div>

              <div class="card-body pt-2">
                <div class="row">
                  <div class="col-xl-6">
                    <?php if (!empty($hata)) echo "<div class='alert alert-danger'>$hata</div>"; ?>
                    <?php if (!empty($basari)) echo "<div class='alert alert-success'>$basari</div>"; ?>
   
                    <form method="post" action="personel-sertifika-kayit.php?id=<?php echo $personel_id; ?>" enctype="multipart/form-data">
                      <div class="form-group">
                        <label>Sertifika Adı</label>
                        <input type="text" name="sertifika_adi" class="form-control" required>
                      </div>

                      <div class="form-group">
                        <label>Sertifika Dosyası (Opsiyonel)</label>
                        <input type="file" name="sertifika" class="dropify" data-height="300" />
                        <small class="text-muted">Dosya yüklemezseniz varsayılan sertifika kullanılacaktır.</small>
                      </div>

                      <button type="submit" class="btn btn-primary">Kaydet</button>
                    </form>
                  </div>
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

  <!-- dropify File Upload js -->
  <script src="assets/vendor/dropify/js/dropify.min.js"></script>

  <!-- File Upload Demo js -->
  <script src="assets/js/pages/form-fileupload.js"></script>
</body>

</html>
