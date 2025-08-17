<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// belgeler.php dosyanızın en üstüne ekleyin:
echo "<!-- Debug Info: -->";
echo "<!-- POST Max Size: " . ini_get('post_max_size') . " -->";
echo "<!-- Upload Max Filesize: " . ini_get('upload_max_filesize') . " -->";
echo "<!-- Max File Uploads: " . ini_get('max_file_uploads') . " -->";

session_start();
include 'con/db.php'; // Veritabanı bağlantısı
include 'functions.php'; // Belge fonksiyonları

$hata = "";
$basari = "";

// Kullanıcı giriş kontrolü


$kullanici_id = $_SESSION['user_id'];

// Sayfa parametrelerini al
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$kategori_id = $_GET['kategori'] ?? null;
$arama = $_GET['arama'] ?? '';
$sayfa = intval($_GET['sayfa'] ?? 1);

// Belge kategorilerini getir
$kategoriler = getBelgeKategorileri();

// Belge istatistiklerini getir
$istatistikler = getBelgeIstatistikleri($kullanici_id);

// Debug için
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST Request Debug:");
    error_log("POST: " . print_r($_POST, true));
    error_log("FILES: " . print_r($_FILES, true));
    error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
}



// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
case 'upload':
    error_log("Upload işlemi başladı");
    error_log("FILES: " . print_r($_FILES, true));
    error_log("POST: " . print_r($_POST, true));
    
    if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
        $baslik = trim($_POST['baslik'] ?? '');
        $kategori_id_post = intval($_POST['kategori_id'] ?? 0);
        $aciklama = trim($_POST['aciklama'] ?? '');
        $belge_tarihi = $_POST['belge_tarihi'] ?? date('Y-m-d');
        $gizlilik_seviyesi = $_POST['gizlilik_seviyesi'] ?? 'genel';
        $etiketler = $_POST['etiketler'] ?? [];
        
        error_log("Veriler - Başlık: $baslik, Kategori: $kategori_id_post");
        
        if (empty($baslik)) {
            $hata = "Belge başlığı gereklidir!";
        } elseif ($kategori_id_post <= 0) {
            $hata = "Geçerli bir kategori seçiniz!";
        } else {
            $result = belgeYukle(
                $_FILES['dosya'],
                $kategori_id_post,
                $baslik,
                $kullanici_id,
                $aciklama,
                $belge_tarihi,
                $etiketler,
                $gizlilik_seviyesi
            );
            
            error_log("belgeYukle sonucu: " . print_r($result, true));
            
            if ($result['success']) {
             $basari = $result['message'];
                // Başarılı olursa sayfayı yenile
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $hata = $result['message'];
            }
        }
    } else {
        $upload_error_codes = [
            UPLOAD_ERR_INI_SIZE => 'Dosya php.ini upload_max_filesize ayarından büyük',
            UPLOAD_ERR_FORM_SIZE => 'Dosya HTML form MAX_FILE_SIZE ayarından büyük', 
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
            UPLOAD_ERR_NO_FILE => 'Hiç dosya yüklenmedi',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör eksik',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı',
            UPLOAD_ERR_EXTENSION => 'PHP uzantısı dosya yüklemeyi durdurdu'
        ];
        
        $error_code = $_FILES['dosya']['error'] ?? 'UNKNOWN';
        $error_message = $upload_error_codes[$error_code] ?? "Bilinmeyen hata: $error_code";
        
        $hata = "Dosya yükleme hatası: $error_message";
        error_log("Upload error: " . $hata);
    }
    break;
            
        case 'add_category':
            $ad = trim($_POST['kategori_ad'] ?? '');
            $aciklama = trim($_POST['kategori_aciklama'] ?? '');
            $icon = $_POST['kategori_icon'] ?? 'fas fa-file-alt';
            $renk = $_POST['kategori_renk'] ?? '#007bff';
            
            if (!empty($ad)) {
                if (belgeKategorisiEkle($ad, $aciklama, $icon, $renk)) {
                    $basari = "Kategori başarıyla eklendi!";
                    // Kategorileri yeniden yükle
                    $kategoriler = getBelgeKategorileri();
                } else {
                    $hata = "Kategori eklenirken hata oluştu!";
                }
            } else {
                $harta = "Kategori adı gereklidir!";
            }
            break;
    }
}

// Belgeleri getir
if ($kategori_id === 'all') {
    $kategori_id = null;
}

$belgeler_result = getBelgeler($kategori_id, $arama, $sayfa, 12, $kullanici_id);
$belgeler = $belgeler_result['belgeler'];
$toplam_sayfa = $belgeler_result['toplam_sayfa'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Belge ve Evrak Takibi";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
        .belge-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e6ed;
            border-radius: 10px;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .belge-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .kategori-badge {
            border-radius: 20px;
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
        }
        
        .dosya-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        
        .belge-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .belge-card:hover .belge-actions {
            opacity: 1;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 20px;
        }
        
        .kategori-item {
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 5px;
        }
        
        .kategori-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .kategori-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        
        .upload-area.dragover {
            border-color: #28a745;
            background-color: #d4edda;
        }
        
        .search-filters {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .favori-icon {
            color: #ffc107;
            cursor: pointer;
        }
        
        .favori-icon:hover {
            color: #ffb300;
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
                $subtitle = "Döküman Yönetimi";
                $title = "Belge ve Evrak Takibi";
                include "partials/page-title.php";
                ?>

                <?php if ($hata): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $hata; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($basari): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $basari; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Sol Sidebar -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-folder-open me-2"></i>
                                    Belge Kategorileri
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <a href="?page=belgeler" class="list-group-item list-group-item-action kategori-item <?= !$kategori_id ? 'active' : '' ?>">
                                        <i class="fas fa-th-large me-2"></i>
                                        Tüm Belgeler
                                        <span class="badge bg-secondary float-end"><?= $istatistikler['toplam_belge'] ?></span>
                                    </a>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <?php
                                        $kategori_sayi = 0;
                                        foreach ($istatistikler['kategoriler'] as $stat) {
                                            if ($stat['ad'] === $kategori['ad']) {
                                                $kategori_sayi = $stat['sayi'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <a href="?page=belgeler&kategori=<?= $kategori['id'] ?>" 
                                           class="list-group-item list-group-item-action kategori-item <?= ($kategori_id == $kategori['id']) ? 'active' : '' ?>">
                                            <i class="<?= $kategori['icon'] ?> me-2" style="color: <?= $kategori['renk'] ?>;"></i>
                                            <?= htmlspecialchars($kategori['ad']) ?>
                                            <span class="badge float-end" style="background-color: <?= $kategori['renk'] ?>;"><?= $kategori_sayi ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hızlı İstatistikler -->
                        <div class="stats-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">Toplam Belge</h6>
                                    <h3 class="mb-0"><?= $istatistikler['toplam_belge'] ?></h3>
                                </div>
                                <div>
                                    <i class="fas fa-file-alt fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-pie fa-2x text-info mb-2"></i>
                                <h6>Toplam Boyut</h6>
                                <h5 class="text-info"><?= formatFileSize($istatistikler['toplam_boyut']) ?></h5>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Son Yüklenen Belgeler</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($istatistikler['son_belgeler'])): ?>
                                    <?php foreach ($istatistikler['son_belgeler'] as $son_belge): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file-alt text-muted me-2"></i>
                                            <div class="flex-grow-1">
                                                <div class="small"><?= htmlspecialchars($son_belge['baslik']) ?></div>
                                                <small class="text-muted"><?= date('d.m.Y', strtotime($son_belge['olusturma_tarihi'])) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted small">Henüz belge bulunmuyor.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ana İçerik -->
                    <div class="col-md-9">
                        <!-- Üst Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>
                                <i class="fas fa-archive me-2 text-primary"></i>
                                Belgeler
                            </h4>
                            <div>
                                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#belgeYukleModal">
                                    <i class="fas fa-plus me-1"></i>
                                    Yeni Belge Yükle
                                </button>
                                <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#gelismisAramaModal">
                                    <i class="fas fa-search me-1"></i>
                                    Gelişmiş Arama
                                </button>
                                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#kategoriEkleModal">
                                    <i class="fas fa-folder-plus me-1"></i>
                                    Kategori Ekle
                                </button>
                            </div>
                        </div>
                        
                        <!-- Arama ve Filtreler -->
                        <div class="search-filters">
                            <form method="GET" class="row">
                                <input type="hidden" name="page" value="belgeler">
                                <?php if ($kategori_id): ?>
                                    <input type="hidden" name="kategori" value="<?= $kategori_id ?>">
                                <?php endif; ?>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" name="arama" value="<?= htmlspecialchars($arama) ?>" placeholder="Belge ara...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="sort">
                                        <option value="tarih_desc">En Yeni</option>
                                        <option value="tarih_asc">En Eski</option>
                                        <option value="ad_asc">A-Z</option>
                                        <option value="ad_desc">Z-A</option>
                                        <option value="boyut_desc">Büyük Dosyalar</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>
                                        Ara
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Belge Listesi -->
                        <div class="row" id="belgeListesi">
                            <?php if (empty($belgeler)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                                        <h5>Belge bulunamadı</h5>
                                        <p>Arama kriterlerinize uygun belge bulunmamaktadır.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($belgeler as $belge): ?>
                                    <?php
                                    // Dosya türüne göre icon
                                    $icon_class = 'fas fa-file-alt';
                                    $icon_color = 'text-secondary';
                                    
                                    switch (strtolower($belge['dosya_uzantisi'])) {
                                        case 'pdf':
                                            $icon_class = 'fas fa-file-pdf';
                                            $icon_color = 'text-danger';
                                            break;
                                        case 'doc':
                                        case 'docx':
                                            $icon_class = 'fas fa-file-word';
                                            $icon_color = 'text-primary';
                                            break;
                                        case 'xls':
                                        case 'xlsx':
                                            $icon_class = 'fas fa-file-excel';
                                            $icon_color = 'text-success';
                                            break;
                                        case 'ppt':
                                        case 'pptx':
                                            $icon_class = 'fas fa-file-powerpoint';
                                            $icon_color = 'text-warning';
                                            break;
                                        case 'jpg':
                                        case 'jpeg':
                                        case 'png':
                                        case 'gif':
                                            $icon_class = 'fas fa-file-image';
                                            $icon_color = 'text-info';
                                            break;
                                        case 'zip':
                                        case 'rar':
                                            $icon_class = 'fas fa-file-archive';
                                            $icon_color = 'text-dark';
                                            break;
                                    }
                                    
                                    // Etiketleri decode et
                                    $etiketler = json_decode($belge['etiketler'], true) ?: [];
                                    ?>
                                    
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card belge-card" data-belge-id="<?= $belge['id'] ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="dosya-icon <?= $icon_color ?>">
                                                        <i class="<?= $icon_class ?>"></i>
                                                    </div>
                                                    <div class="belge-actions">
                                                        <i class="<?= $belge['favori'] ? 'fas' : 'far' ?> fa-star favori-icon me-2" 
                                                           onclick="favoriToggle('<?= $belge['id'] ?>')"></i>
                                                        <div class="dropdown d-inline">
                                                            <i class="fas fa-ellipsis-v" data-bs-toggle="dropdown" style="cursor: pointer;"></i>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="ajax/belge_indir.php?belge_id=<?= $belge['id'] ?>" target="_blank">
                                                                    <i class="fas fa-download me-2"></i>İndir</a></li>
                                                                <li><a class="dropdown-item" href="#" onclick="belgePaylasimModal('<?= $belge['id'] ?>')">
                                                                    <i class="fas fa-share me-2"></i>Paylaş</a></li>
                                                                <li><a class="dropdown-item" href="#" onclick="belgeDetayModal('<?= $belge['id'] ?>')">
                                                                    <i class="fas fa-eye me-2"></i>Detay</a></li>
                                                                <li><a class="dropdown-item" href="#" onclick="belgeGuncelleModal('<?= $belge['id'] ?>')">
                                                                    <i class="fas fa-edit me-2"></i>Düzenle</a></li>
                                                                <?php if ($belge['olusturan_id'] == $kullanici_id): ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" onclick="belgeSil('<?= $belge['id'] ?>')">
                                                                    <i class="fas fa-trash me-2"></i>Sil</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <h6 class="card-title text-truncate" title="<?= htmlspecialchars($belge['baslik']) ?>">
                                                    <?= htmlspecialchars($belge['baslik']) ?>
                                                </h6>
                                                
                                                <p class="card-text text-muted small">
                                                    <?= htmlspecialchars(substr($belge['aciklama'], 0, 80)) ?><?= strlen($belge['aciklama']) > 80 ? '...' : '' ?>
                                                </p>
                                                
                                                <?php if (!empty($etiketler)): ?>
                                                <div class="mb-2">
                                                    <?php foreach (array_slice($etiketler, 0, 3) as $etiket): ?>
                                                    <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($etiket) ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($etiketler) > 3): ?>
                                                    <span class="badge bg-secondary">+<?= count($etiketler) - 3 ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="kategori-badge text-white" style="background-color: <?= $belge['kategori_renk'] ?? '#007bff' ?>;">
                                                        <i class="<?= $belge['kategori_icon'] ?? 'fas fa-folder' ?> me-1"></i>
                                                        <?= htmlspecialchars($belge['kategori_adi']) ?>
                                                    </span>
                                                    <small class="text-muted"><?= formatFileSize($belge['dosya_boyutu']) ?></small>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d.m.Y', strtotime($belge['belge_tarihi'])) ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= htmlspecialchars($belge['olusturan_ad'] . ' ' . substr($belge['olusturan_soyad'], 0, 1) . '.') ?>
                                                    </small>
                                                </div>
                                                
                                                <?php if ($belge['gizlilik_seviyesi'] !== 'genel'): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-lock me-1"></i>
                                                        <?= ucfirst(str_replace('_', ' ', $belge['gizlilik_seviyesi'])) ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Sayfalama -->
                        <?php if ($toplam_sayfa > 1): ?>
                        <nav aria-label="Belge sayfalama">
                            <ul class="pagination justify-content-center">
                                <?php if ($sayfa > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=belgeler<?= $kategori_id ? '&kategori=' . $kategori_id : '' ?><?= $arama ? '&arama=' . urlencode($arama) : '' ?>&sayfa=<?= $sayfa - 1 ?>">
                                        Önceki
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $sayfa - 2); $i <= min($toplam_sayfa, $sayfa + 2); $i++): ?>
                                <li class="page-item <?= ($i == $sayfa) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=belgeler<?= $kategori_id ? '&kategori=' . $kategori_id : '' ?><?= $arama ? '&arama=' . urlencode($arama) : '' ?>&sayfa=<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($sayfa < $toplam_sayfa): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=belgeler<?= $kategori_id ? '&kategori=' . $kategori_id : '' ?><?= $arama ? '&arama=' . urlencode($arama) : '' ?>&sayfa=<?= $sayfa + 1 ?>">
                                        Sonraki
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/footer.php'; ?>
    </div>

    <!-- Modallar -->
    <?php include 'modals/belge_modals.php'; ?>

    <?php include 'partials/customizer.php'; ?>
    <?php include 'partials/footer-scripts.php'; ?>
    
    <!-- Belge yönetimi için özel JS -->
    <script src="assets/js/belgeler.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Select2 başlatma
            $('.select2').select2();
            
            // Favori toggle fonksiyonu
            window.favoriToggle = function(belgeId) {
                $.ajax({
                    url: 'ajax/belge_favori_toggle.php',
                    type: 'POST',
                    data: { belge_id: belgeId },
                    success: function(response) {
                        if (response.success) {
                            // Icon güncelle
                            const icon = $(`.belge-card[data-belge-id="${belgeId}"] .favori-icon`);
                            if (response.favori) {
                                icon.removeClass('far').addClass('fas');
                            } else {
                                icon.removeClass('fas').addClass('far');
                            }
                            showAlert('success', response.message);
                        } else {
                            showAlert('error', response.message);
                        }
                    },
                    error: function() {
                        showAlert('error', 'İşlem başarısız!');
                    }
                });
            };
            
            // Alert gösterme fonksiyonu
            window.showAlert = function(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHTML = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                $('.page-content .page-container').prepend(alertHTML);
                
                setTimeout(() => {
                    $('.alert').alert('close');
                }, 5000);
            };
        });
    </script>
</body>
</html>


