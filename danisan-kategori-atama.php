<?php
include_once 'functions.php';
$hata = $basari = "";

// URL'den danisan_id alınmalı, yoksa liste sayfasına yönlendir
$danisan_id = $_GET['danisan_id'] ?? null;
if (!$danisan_id) {
    header("Location: ?page=danisanlar");
    exit;
}

// POST ise güncelle
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $secili = $_POST['kategori'] ?? [];
    if (updateDanisanKategorileri($danisan_id, $secili)) {
        $basari = "Kategoriler başarıyla güncellendi.";
    } else {
        $hata = "Kategori güncellenirken bir hata oluştu.";
    }
}

// Veri çek
$danisan = $pdo->prepare("SELECT ad, soyad, deneme_mi FROM danisanlar WHERE id=?");
$danisan->execute([$danisan_id]);
$danisan = $danisan->fetch(PDO::FETCH_ASSOC);

if (!$danisan) {
    header("Location: ?page=danisanlar");
    exit;
}

$kategoriler = getKategoriler();
$mevcut_kategori = getDanisanKategoriIds($danisan_id);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Kategori Atama";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
        .kategori-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .kategori-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .kategori-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .kategori-checkbox {
            transform: scale(1.2);
        }
        .danisan-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .kategori-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .kategori-grid {
                grid-template-columns: 1fr;
            }
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
                $subtitle = "Kullanıcı Yönetimi";
                $title = "Kategori Atama";
                include "partials/page-title.php";
                ?>
                
                <div class="container-fluid">
                    <!-- Danışan Bilgi Kartı -->
                    <div class="danisan-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-0">
                                    <i class="bx bx-user me-2"></i>
                                    <?= htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']) ?>
                                </h4>
                                <p class="mb-0 mt-2 opacity-75">
                                    <i class="bx bx-tag me-1"></i>
                                    Bu danışana kategori atamak için aşağıdaki kategorileri seçin
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <?php if (isset($danisan['deneme_mi']) && $danisan['deneme_mi'] == 1): ?>
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                        <i class="bx bx-user-plus"></i> Deneme Üyesi
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="bx bx-user-check"></i> Normal Üye
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Ana Kart -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0">
                                        <i class="bx bx-category me-2 text-primary"></i>
                                        Kategori Seçimi
                                    </h5>
                                    <small class="text-muted">
                                        Danışana uygun kategorileri seçerek organize edin
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-info">
                                        <span id="secili-sayisi"><?= count($mevcut_kategori) ?></span> kategori seçili
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Durum Mesajları -->
                            <?php if ($hata): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bx bx-error-circle me-2"></i>
                                    <?= htmlspecialchars($hata) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($basari): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bx bx-check-circle me-2"></i>
                                    <?= htmlspecialchars($basari) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Form -->
                            <form method="post" id="kategoriForm">
                                <?php if (empty($kategoriler)): ?>
                                    <div class="text-center py-5">
                                        <i class="bx bx-category display-1 text-muted"></i>
                                        <h5 class="text-muted mt-3">Henüz kategori bulunmuyor</h5>
                                        <p class="text-muted">Önce kategori oluşturmanız gerekiyor.</p>
                                        <a href="?page=kategoriler" class="btn btn-primary">
                                            <i class="bx bx-plus"></i> Kategori Ekle
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- Hızlı Seçim Butonları -->
                                    <div class="mb-4">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="tumunuSec">
                                                <i class="bx bx-check-square"></i> Tümünü Seç
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="temizle">
                                                <i class="bx bx-square"></i> Temizle
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Kategori Grid -->
                                    <div class="kategori-grid">
                                        <?php foreach ($kategoriler as $k): ?>
                                            <div class="kategori-card card h-100 <?= in_array($k['id'], $mevcut_kategori) ? 'selected' : '' ?>" 
                                                 data-kategori-id="<?= $k['id'] ?>">
                                                <div class="card-body p-3">
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input kategori-checkbox"
                                                            type="checkbox"
                                                            name="kategori[]"
                                                            id="kat<?= $k['id'] ?>"
                                                            value="<?= $k['id'] ?>"
                                                            <?= in_array($k['id'], $mevcut_kategori) ? 'checked' : '' ?>>
                                                        <label class="form-check-label w-100" for="kat<?= $k['id'] ?>">
                                                            <div class="d-flex align-items-center">
                                                                <div class="flex-grow-1">
                                                                    <h6 class="mb-1">
                                                                        <i class="bx bx-tag text-primary me-1"></i>
                                                                        <?= htmlspecialchars($k['ad']) ?>
                                                                    </h6>
                                                                    <?php if (!empty($k['aciklama'])): ?>
                                                                        <small class="text-muted">
                                                                            <?= htmlspecialchars($k['aciklama']) ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="text-end">
                                                                    <i class="bx bx-chevron-right text-muted"></i>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <!-- Card Footer -->
                        <div class="card-footer bg-light">
                            <div class="row align-items-center">
                                <div class="col">
                                    <small class="text-muted">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Kategoriler danışanınızı gruplandırmanıza yardımcı olur
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group">
                                        <a href="?page=danisanlar" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back"></i> Geri Dön
                                        </a>
                                        <?php if (!empty($kategoriler)): ?>
                                            <button type="submit" form="kategoriForm" class="btn btn-primary">
                                                <i class="bx bx-save"></i> Kategorileri Kaydet
                                            </button>
                                        <?php endif; ?>
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
    
    <script>
        $(document).ready(function() {
            // Kategori kartına tıklama
            $('.kategori-card').on('click', function(e) {
                if (e.target.type !== 'checkbox') {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });
            
            // Checkbox değişimi
            $('.kategori-checkbox').on('change', function() {
                const card = $(this).closest('.kategori-card');
                if ($(this).is(':checked')) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
                updateSeciliSayisi();
            });
            
            // Tümünü seç
            $('#tumunuSec').on('click', function() {
                $('.kategori-checkbox').prop('checked', true).trigger('change');
            });
            
            // Temizle
            $('#temizle').on('click', function() {
                $('.kategori-checkbox').prop('checked', false).trigger('change');
            });
            
            // Seçili sayısını güncelle
            function updateSeciliSayisi() {
                const seciliSayisi = $('.kategori-checkbox:checked').length;
                $('#secili-sayisi').text(seciliSayisi);
            }
            
            // Form submit
            $('#kategoriForm').on('submit', function(e) {
                const seciliSayisi = $('.kategori-checkbox:checked').length;
                if (seciliSayisi === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kategori Seçiniz',
                        text: 'En az bir kategori seçmelisiniz.',
                        confirmButtonText: 'Tamam'
                    });
                    return false;
                }
                
                // Loading durumu
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Kaydediliyor...');
            });
        });
    </script>
</body>
</html>