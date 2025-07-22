<?php
// Start session at the very beginning
ob_start(); // sayfanın en üstüne
session_start();
if (
    isset($_GET['page']) &&
    $_GET['page'] === 'randevular' &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    include 'pages/randevular.php';
    exit;
}

include 'functions.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);




// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Session'dan kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$ad_soyad = $_SESSION['ad_soyad'];
$rol = $_SESSION['rol'];
$avatar = $_SESSION['avatar'] ?? 'assets/img/default-avatar.png';

// Aktif menü seçimi
$active_page = $_GET['page'] ?? 'dashboard';

// Verileri çek
$danisanlar = getDanisanlar();
$randevular = getRandevular();
$seansTurleri = getSeansTurleri();
$uyelikTurleri = getUyelikTurleri();
$hizmetPaketleri = getHizmetPaketleri();
$sponsorluklar = getSponsorluklar();
$terapistler = getTerapistler();
$aktif_terapistler = getTerapistler(true);
?>

<!DOCTYPE html>
<html lang="tr">
<?php include 'template/header.php'; ?>
<body>
    <?php include 'template/sidebar.php'; ?>
    <div class="top-bar">
        <button class="btn d-md-none" id="sidebar-toggle">
            <i class='bx bx-menu fs-4'></i>
        </button>
        <div class="user-info">
            <img src="<?php echo $avatar; ?>" alt="<?php echo $ad_soyad; ?>" class="user-avatar">
            <div>
                <div class="user-name"><?php echo $ad_soyad; ?></div>
                <div class="user-role"><?php echo ucfirst($rol); ?></div>
            </div>
        </div>
    </div>
    <main class="main-content">
        <?php
        switch($active_page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'danisanlar':
                include 'pages/danisanlar.php';
                break;
            case 'terapistler':
                include 'pages/terapistler.php';
                break;
            case 'terapist-detay':
                include 'pages/terapist-detay.php';
                break;
            case 'personel-kayit':
                include 'pages/personel-kayit.php';
                break;
            case 'randevular':
                include 'pages/randevular.php';
                break;
            case 'paketler':
                include 'pages/paketler.php';
                break;
            case 'uyelikler':
                include 'pages/uyelikler.php';
                break;
            case 'sponsorluklar':
                include 'pages/sponsorluklar.php';
                break;
            case 'satislar':
                include 'pages/satislar.php';
                break;
            case 'hediye_seanslar':
                include 'pages/hediye_seanslar.php';
                break;
            case 'seans_turleri':
                include 'pages/seans_turleri.php';
                break;
            case 'room_schedule':
                include 'pages/room_schedule.php';
                break;
            case 'weekly_room_schedule':
                include 'pages/weekly_room_schedule.php';
                break;
            case 'danisan-detay':
                include 'pages/danisan-detay.php';
                break;
            case 'satis_form':
                include 'pages/satis_form.php';
                break;
             case 'puantaj':
                include 'pages/puantaj.php';
                break;
                case 'ucret':
                include 'pages/ucret.php';
                break;
                case 'degerlendirme_form':
                include 'pages/degerlendirme_form.php';
                break;
                case 'degerlendirme_liste':
                include 'pages/degerlendirme_liste.php';
                break;
                   case 'kriterler':
                include 'pages/kriterler.php';
                break;
                case 'export_puantaj':
                include 'pages/export_puantaj.php';
                break;

                
                

            default:
                include 'pages/dashboard.php';
        }
        ?>
    </main>

    <?php include 'template/footer.php'; ?>
</body>
</html>

<?php
ob_end_flush();
?>