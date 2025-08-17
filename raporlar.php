<?php
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Özet istatistikleri getir
$istatistikler = getRaporIstatistikleri();

// Son güncellemeler için veri
$son_paket_kullanim = getPaketKullanimRaporu('', '', 5); // Son 5 kayıt
$son_aktivite = getDanisanAktiviteRaporu(date('Y-m-d', strtotime('-1 year')), date('Y-m-d'), 30, 5); // Son 5 kayıt

$title = "Raporlar";
$subtitle = "Raporlar";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php" ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .report-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .report-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .stats-widget {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .quick-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php' ?>
        <?php include 'partials/topbar.php' ?>

        <div class="page-content">
            <div class="page-container">
                <?php include "partials/page-title.php" ?>

                <!-- Ana İstatistik Kartları -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card stats-widget h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-user-circle text-white" style="font-size: 3rem;"></i>
                                <h2 class="text-white mt-3 mb-1"><?= number_format($istatistikler['toplam_danisan']) ?></h2>
                                <p class="text-white-50 mb-0">Toplam Danışan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-package text-white" style="font-size: 3rem;"></i>
                                <h2 class="text-white mt-3 mb-1"><?= number_format($istatistikler['aktif_paket_sahipleri']) ?></h2>
                                <p class="text-white-50 mb-0">Aktif Paket Sahibi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-time-five text-white" style="font-size: 3rem;"></i>
                                <h2 class="text-white mt-3 mb-1"><?= number_format($istatistikler['uzun_sure_gelmeyenler']) ?></h2>
                                <p class="text-white-50 mb-0">Uzun Süredir Gelmeyen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-check-circle text-white" style="font-size: 3rem;"></i>
                                <h2 class="text-white mt-3 mb-1"><?= number_format($istatistikler['bu_ay_tamamlanan']) ?></h2>
                                <p class="text-white-50 mb-0">Bu Ay Tamamlanan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapor Kartları -->
                <div class="row g-4 mb-5">
                    <div class="col-lg-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-package text-primary report-icon"></i>
                                <h5 class="card-title">Paket Kullanım Raporu</h5>
                                <p class="card-text text-muted">
                                    Paket satın almış ancak tamamını kullanmamış danışanların detaylı raporu
                                </p>
                                <div class="quick-stats mb-3">
                                    <small class="d-block">
                                        <strong>Aktif Paket:</strong> <?= count($son_paket_kullanim) ?> danışan
                                    </small>
                                </div>
                                <a href="paket_kullanim_raporu.php" class="btn btn-primary">
                                    <i class="bx bx-chart-line me-1"></i>Raporu Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-user-check text-success report-icon"></i>
                                <h5 class="card-title">Danışan Aktivite Raporu</h5>
                                <p class="card-text text-muted">
                                    Belirli tarih aralığında hizmet almamış danışanların analiz raporu
                                </p>
                                <div class="quick-stats mb-3">
                                    <small class="d-block">
                                        <strong>30+ gün gelmeyen:</strong> <?= $istatistikler['uzun_sure_gelmeyenler'] ?> danışan
                                    </small>
                                </div>
                                <a href="danisan_aktivite_raporu.php" class="btn btn-success">
                                    <i class="bx bx-user-search me-1"></i>Raporu Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card report-card h-100">
                            <div class="card-body text-center">
                                <i class="bx bx-time text-info report-icon"></i>
                                <h5 class="card-title">Puantaj Raporu</h5>
                                <p class="card-text text-muted">
                                    Terapist bazlı aylık ve yıllık performans puantaj raporu
                                </p>
                                <div class="quick-stats mb-3">
                                    <small class="d-block">
                                        <strong>Bu ay:</strong> Detaylı analiz mevcut
                                    </small>
                                </div>
                                <a href="puantaj.php" class="btn btn-info">
                                    <i class="bx bx-time-five me-1"></i>Raporu Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Güncellemeler -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bx bx-package me-2"></i>Son Paket Kullanım Durumu
                                </h6>
                                <a href="paket_kullanim_raporu.php" class="btn btn-sm btn-outline-primary">
                                    Tümünü Gör
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($son_paket_kullanim)): ?>
                                    <div class="text-center py-3">
                                        <i class="bx bx-smile text-success fs-3"></i>
                                        <p class="text-muted mt-2 mb-0">Tüm paketler başarıyla kullanılıyor!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($son_paket_kullanim, 0, 5) as $paket): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($paket['danisan_adi']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($paket['paket_adi']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="progress mb-1" style="width: 80px; height: 6px;">
                                                <div class="progress-bar bg-warning" 
                                                     style="width: <?= $paket['kullanim_yuzdesi'] ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $paket['kullanim_yuzdesi'] ?>%</small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bx bx-user-x me-2"></i>Uzun Süredir Gelmeyen Danışanlar
                                </h6>
                                <a href="danisan_aktivite_raporu.php" class="btn btn-sm btn-outline-primary">
                                    Tümünü Gör
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($son_aktivite)): ?>
                                    <div class="text-center py-3">
                                        <i class="bx bx-happy text-success fs-3"></i>
                                        <p class="text-muted mt-2 mb-0">Tüm danışanlar düzenli geliyor!</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($son_aktivite, 0, 5) as $aktivite): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($aktivite['danisan_adi']) ?></h6>
                                            <small class="text-muted">
                                                <i class="bx bx-phone me-1"></i><?= htmlspecialchars($aktivite['telefon']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($aktivite['gun_farki']): ?>
                                                <span class="badge bg-warning"><?= $aktivite['gun_farki'] ?> gün</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Hiç randevu yok</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="row g-4 mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bx bx-zap me-2"></i>Hızlı İşlemler
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <a href="paket_kullanim_raporu.php?export=csv" class="btn btn-outline-success w-100">
                                            <i class="bx bx-download me-2"></i>
                                            Paket Raporu İndir
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="danisan_aktivite_raporu.php?export=csv" class="btn btn-outline-info w-100">
                                            <i class="bx bx-download me-2"></i>
                                            Aktivite Raporu İndir
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="apps-calendar.php" class="btn btn-outline-primary w-100">
                                            <i class="bx bx-calendar me-2"></i>
                                            Randevu Takvimi
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="dashboard.php" class="btn btn-outline-secondary w-100">
                                            <i class="bx bx-home me-2"></i>
                                            Ana Sayfa
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'partials/vendor-scripts.php' ?>
    
    <script>
        // Sayfa yüklendiğinde istatistikleri güncelle
        $(document).ready(function() {
            // Hover efektleri için animasyon
            $('.report-card').hover(
                function() {
                    $(this).find('.report-icon').addClass('animate__animated animate__pulse');
                },
                function() {
                    $(this).find('.report-icon').removeClass('animate__animated animate__pulse');
                }
            );
        });
    </script>
</body>
</html>