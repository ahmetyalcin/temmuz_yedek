<?php
session_start();
include 'con/db.php'; // Veritabanı bağlantısı

$hata = "";
$basari = "";

// Profil fotoğraflarının yükleneceği dizin
$upload_dir = "uploads/avatars/";

// Düzenlenecek personelin ID'sini al
$personel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Mevcut personel bilgilerini al
if ($personel_id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM personel WHERE id = :id");
  $stmt->bindParam(":id", $personel_id);
  $stmt->execute();
  $personel = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$personel) {
    $hata = "Personel bulunamadı!";
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $ad = trim($_POST["ad"]);
  $soyad = trim($_POST["soyad"]);
  $tc_no = trim($_POST["tc_no"]);
  $cinsiyet = $_POST["cinsiyet"];
  $mezuniyet = $_POST["mezuniyet"];
  $sicil_no = trim($_POST["sicil_no"]);
  $kullanici_adi = trim($_POST["kullanici_adi"]);
  $sifre = trim($_POST["sifre"]);
  $rol = $_POST["rol"];

  // Dosya yükleme işlemi
  if (!empty($_FILES["avatar"]["name"])) {
    $avatar_name = time() . "_" . basename($_FILES["avatar"]["name"]);
    $avatar_path = $upload_dir . $avatar_name;

    // Dosya tipi kontrolü
    $allowed_types = ["image/jpeg", "image/png", "image/jpg"];
    if (!in_array($_FILES["avatar"]["type"], $allowed_types)) {
      $hata = "Sadece JPG, JPEG veya PNG dosyaları yükleyebilirsiniz!";
    } else {
      // Dosyayı sunucuya yükle
      if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $avatar_path)) {
        $avatar = $avatar_name;
      } else {
        $hata = "Fotoğraf yüklenirken hata oluştu!";
      }
    }
  } else {
    $avatar = $personel['avatar']; // Eğer fotoğraf yüklenmezse mevcut avatarı kullan
  }

  // Kullanıcı güncelleme
  if (empty($hata)) {
    if (!empty($ad) && !empty($soyad) && !empty($tc_no) && !empty($sicil_no) && !empty($kullanici_adi)) {
      $hashed_password = !empty($sifre) ? password_hash($sifre, PASSWORD_DEFAULT) : $personel['sifre'];

      $stmt = $pdo->prepare("UPDATE personel SET ad = :ad, soyad = :soyad, avatar = :avatar, tc_no = :tc_no, cinsiyet = :cinsiyet, mezuniyet = :mezuniyet, sicil_no = :sicil_no, kullanici_adi = :kullanici_adi, sifre = :sifre, rol = :rol WHERE id = :id");
      $stmt->bindParam(":ad", $ad);
      $stmt->bindParam(":soyad", $soyad);
      $stmt->bindParam(":avatar", $avatar);
      $stmt->bindParam(":tc_no", $tc_no);
      $stmt->bindParam(":cinsiyet", $cinsiyet);
      $stmt->bindParam(":mezuniyet", $mezuniyet);
      $stmt->bindParam(":sicil_no", $sicil_no);
      $stmt->bindParam(":kullanici_adi", $kullanici_adi);
      $stmt->bindParam(":sifre", $hashed_password);
      $stmt->bindParam(":rol", $rol);
      $stmt->bindParam(":id", $personel_id);

      if ($stmt->execute()) {
        $basari = "Personel başarıyla güncellendi!";
      } else {
        $hata = "Personel güncellenirken bir hata oluştu!";
      }
    } else {
      $hata = "Lütfen tüm alanları doldurun!";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
  <?php
  $title = "Personel Düzenle";
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
        $title = "Personel Düzenle";
        include "partials/page-title.php";
        ?>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5 class="header-title">Personel Düzenle</h5>
              </div>

              <div class="card-body pt-2">
                <div class="row">
                  <div class="col-xl-6">
                    <?php if (!empty($hata)) echo "<div class='alert alert-danger'>$hata</div>"; ?>
                    <?php if (!empty($basari)) echo "<div class='alert alert-success'>$basari</div>"; ?>

                    <form method="post" action="personel-duzenle.php?id=<?php echo $personel_id; ?>" enctype="multipart/form-data">
                      <div class="form-group">
                        <label>Profil Fotoğraf</label>
                        <input type="file" name="avatar" class="dropify" data-height="300" data-default-file="<?php echo $upload_dir . $personel['avatar']; ?>" />
                      </div>

                      <div class="form-group">
                        <label>Ad</label>
                        <input type="text" name="ad" class="form-control" value="<?php echo htmlspecialchars($personel['ad']); ?>" required>
                      </div>

                      <div class="form-group">
                        <label>Soyad</label>
                        <input type="text" name="soyad" class="form-control" value="<?php echo htmlspecialchars($personel['soyad']); ?>" required>
                      </div>

                      <div class="form-group">
                        <label>TC Kimlik No</label>
                        <input type="text" name="tc_no" class="form-control" value="<?php echo htmlspecialchars($personel['tc_no']); ?>" required maxlength="11">
                      </div>

                      <div class="form-group">
                        <label>Cinsiyet</label>
                        <select name="cinsiyet" class="form-control">
                          <option value="Erkek" <?php if ($personel['cinsiyet'] == 'Erkek') echo 'selected'; ?>>Erkek</option>
                          <option value="Kadın" <?php if ($personel['cinsiyet'] == 'Kadın') echo 'selected'; ?>>Kadın</option>
                          <option value="Diğer" <?php if ($personel['cinsiyet'] == 'Diğer') echo 'selected'; ?>>Diğer</option>
                        </select>
                      </div>

                      <div class="form-group">
                        <label>Mezuniyet</label>
                        <select name="mezuniyet" class="form-control">
                          <option value="Ön Lisans" <?php if ($personel['mezuniyet'] == 'Ön Lisans') echo 'selected'; ?>>Ön Lisans</option>
                          <option value="Lisans" <?php if ($personel['mezuniyet'] == 'Lisans') echo 'selected'; ?>>Lisans</option>
                          <option value="Yüksek Lisans" <?php if ($personel['mezuniyet'] == 'Yüksek Lisans') echo 'selected'; ?>>Yüksek Lisans</option>
                          <option value="Doktora" <?php if ($personel['mezuniyet'] == 'Doktora') echo 'selected'; ?>>Doktora</option>
                        </select>
                      </div>

                      <div class="form-group">
                        <label>Sicil No</label>
                        <input type="text" name="sicil_no" class="form-control" value="<?php echo htmlspecialchars($personel['sicil_no']); ?>" required>
                      </div>

                      <div class="form-group">
                        <label>Kullanıcı Adı</label>
                        <input type="text" name="kullanici_adi" class="form-control" value="<?php echo htmlspecialchars($personel['kullanici_adi']); ?>" required>
                      </div>

                      <div class="form-group">
                        <label>Şifre</label>
                        <input type="password" name="sifre" class="form-control" placeholder="Yeni şifreyi girin veya boş bırakın">
                      </div>

                      <div class="form-group">
                        <label>Rol</label>
                        <select name="rol" class="form-control">
                          <option value="personel" <?php if ($personel['rol'] == 'personel') echo 'selected'; ?>>Personel</option>
                          <option value="uzman" <?php if ($personel['rol'] == 'uzman') echo 'selected'; ?>>Uzman</option>
                          <option value="admin" <?php if ($personel['rol'] == 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                      </div>

                      <button type="submit" class="btn btn-primary">Güncelle</button>
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
