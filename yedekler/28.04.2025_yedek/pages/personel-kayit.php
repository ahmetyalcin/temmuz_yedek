<?php
include_once 'functions.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hata = "";
$basari = "";

// Profil fotoğraflarının yükleneceği dizin
$upload_dir = "uploads/avatars/";

// Dizin yoksa oluştur
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad = trim($_POST["ad"]);
    $soyad = trim($_POST["soyad"]);
    $email = trim($_POST["email"]);
    $tc_no = trim($_POST["tc_no"]);
    $cinsiyet = $_POST["cinsiyet"];
    $mezuniyet = $_POST["mezuniyet"];
    $sicil_no = trim($_POST["sicil_no"]);
    $kullanici_adi = trim($_POST["kullanici_adi"]);
    $sifre = trim($_POST["sifre"]);
    $rol = $_POST["rol"];
    $redirect = false;

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
        $avatar = "default_avatar.png";
    }

    // Personel ekleme
    if (empty($hata)) {
        try {
            $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO personel (
                ad, soyad, email, avatar, tc_no, cinsiyet, mezuniyet, 
                sicil_no, kullanici_adi, sifre, rol
            ) VALUES (
                :ad, :soyad, :email, :avatar, :tc_no, :cinsiyet, :mezuniyet,
                :sicil_no, :kullanici_adi, :sifre, :rol
            )");

            $stmt->execute([
                'ad' => $ad,
                'soyad' => $soyad,
                'email' => $email,
                'avatar' => $avatar,
                'tc_no' => $tc_no,
                'cinsiyet' => $cinsiyet,
                'mezuniyet' => $mezuniyet,
                'sicil_no' => $sicil_no,
                'kullanici_adi' => $kullanici_adi,
                'sifre' => $hashed_password,
                'rol' => $rol
            ]);

            $basari = "Personel başarıyla eklendi!";
            $redirect = true;

        } catch(PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'tc_no') !== false) {
                    $hata = "Bu TC kimlik numarası zaten kayıtlı!";
                } elseif (strpos($e->getMessage(), 'sicil_no') !== false) {
                    $hata = "Bu sicil numarası zaten kayıtlı!";
                } elseif (strpos($e->getMessage(), 'kullanici_adi') !== false) {
                    $hata = "Bu kullanıcı adı zaten kullanılıyor!";
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    $hata = "Bu e-posta adresi zaten kullanılıyor!";
                } else {
                    $hata = "Kayıt sırasında bir hata oluştu!";
                }
            } else {
                $hata = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
    
    // Başarılı kayıt sonrası yönlendirme
    if ($redirect && empty($hata)) {
        echo "<script>
            alert('Personel başarıyla eklendi!');
            window.location.href = '?page=terapistler';
        </script>";
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Personel Kayıt</h5>
                    <a href="?page=terapistler" class="btn btn-secondary btn-sm">
                        <i class='bx bx-arrow-back'></i> Geri Dön
                    </a>
                </div>

                <div class="card-body">
                    <?php if ($hata): ?>
                        <div class="alert alert-danger"><?php echo $hata; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($basari): ?>
                        <div class="alert alert-success"><?php echo $basari; ?></div>
                    <?php endif; ?>

                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Profil Fotoğrafı</label>
                                <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/jpg">
                                <small class="text-muted">Maksimum dosya boyutu: 2MB</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">TC Kimlik No</label>
                                <input type="text" name="tc_no" class="form-control" required maxlength="11" 
                                       pattern="[0-9]{11}" title="TC Kimlik No 11 haneli olmalıdır">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ad</label>
                                <input type="text" name="ad" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Soyad</label>
                                <input type="text" name="soyad" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cinsiyet</label>
                                <select name="cinsiyet" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <option value="Erkek">Erkek</option>
                                    <option value="Kadın">Kadın</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mezuniyet</label>
                                <select name="mezuniyet" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <option value="Ön Lisans">Ön Lisans</option>
                                    <option value="Lisans">Lisans</option>
                                    <option value="Yüksek Lisans">Yüksek Lisans</option>
                                    <option value="Doktora">Doktora</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sicil No</label>
                                <input type="text" name="sicil_no" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rol</label>
                                <select name="rol" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <option value="terapist">Terapist</option>
                                    <option value="yonetici">Yönetici</option>
                                    <option value="satis">Satış</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" name="kullanici_adi" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" name="sifre" class="form-control" required 
                                       minlength="6" title="Şifre en az 6 karakter olmalıdır">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Kaydet
                            </button>
                            <a href="?page=terapistler" class="btn btn-secondary">
                                <i class='bx bx-x'></i> İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
