<?php
// dashboard_router.php - Ana dashboard yönlendirme sistemi
session_start();
require __DIR__ . '/functions.php';
require __DIR__ . '/con/db.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: auth-login.php');
    exit;
}

$rol = $_SESSION['rol'];
$personel_id = $_SESSION['personel_id'];

// Rol bazlı dashboard yönlendirme
switch($rol) {
    case 'yonetici':
        include 'dashboard_yonetici.php';
        break;
    case 'terapist':
        include 'dashboard_terapist.php';
        break;
    case 'satis':
        include 'dashboard_satis.php';
        break;
    default:
        header('Location: auth-login.php');
        exit;
}
?>

<?php
// dashboard_terapist.php - Terapist özel dashboard
session_start();
require __DIR__ . '/functions.php';
require __DIR__ . '/con/db.php';

$personel_id = $_SESSION['personel_id'];
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Terapist'in bugünkü randevuları
function getTerapistBugunRandevular($personel_id) {
    global $pdo;
    $sql = "SELECT r.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   d.telefon,
                   st.ad as seans_turu,
                   rm.name as room_name,
                   TIME(r.randevu_tarihi) as saat
            FROM randevular r
            JOIN danisanlar d ON d.id = r.danisan_id
            JOIN seans_turleri st ON st.id = r.seans_turu_id
            LEFT JOIN rooms rm ON rm.id = r.room_id
            WHERE r.personel_id = ? 
            AND DATE(r.randevu_tarihi) = CURDATE()
            AND r.aktif = 1
            ORDER BY r.randevu_tarihi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Terapist'in yarınki randevuları
function getTerapistYarinRandevular($personel_id) {
    global $pdo;
    $sql = "SELECT r.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   d.telefon,
                   st.ad as seans_turu,
                   rm.name as room_name,
                   TIME(r.randevu_tarihi) as saat
            FROM randevular r
            JOIN danisanlar d ON d.id = r.danisan_id
            JOIN seans_turleri st ON st.id = r.seans_turu_id
            LEFT JOIN rooms rm ON rm.id = r.room_id
            WHERE r.personel_id = ? 
            AND DATE(r.randevu_tarihi) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND r.aktif = 1
            ORDER BY r.randevu_tarihi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Terapist'in istatistikleri
function getTerapistIstatistikler($personel_id) {
    global $pdo;
    
    // Bu ay toplam seans
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE personel_id = ? 
            AND MONTH(randevu_tarihi) = MONTH(CURDATE())
            AND YEAR(randevu_tarihi) = YEAR(CURDATE())
            AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $aylik_seans = $stmt->fetchColumn();
    
    // Bu hafta toplam seans
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE personel_id = ? 
            AND WEEK(randevu_tarihi) = WEEK(CURDATE())
            AND YEAR(randevu_tarihi) = YEAR(CURDATE())
            AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $haftalik_seans = $stmt->fetchColumn();
    
    // Benzersiz danışan sayısı (bu ay)
    $sql = "SELECT COUNT(DISTINCT danisan_id) FROM randevular 
            WHERE personel_id = ? 
            AND MONTH(randevu_tarihi) = MONTH(CURDATE())
            AND YEAR(randevu_tarihi) = YEAR(CURDATE())
            AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $benzersiz_danisan = $stmt->fetchColumn();
    
    // En son değerlendirme skoru
    $sql = "SELECT c_skoru FROM personel_degerlendirme 
            WHERE personel_id = ? 
            ORDER BY olusturma_tarihi DESC 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $son_degerlendirme = $stmt->fetchColumn() ?: 0;
    
    return [
        'aylik_seans' => $aylik_seans,
        'haftalik_seans' => $haftalik_seans,
        'benzersiz_danisan' => $benzersiz_danisan,
        'son_degerlendirme' => $son_degerlendirme
    ];
}

// Terapist'in devam eden tedavileri
function getTerapistDevamEdenTedaviler($personel_id) {
    global $pdo;
    $sql = "SELECT 
                d.id,
                CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                d.telefon,
                st.ad as seans_turu,
                s.id as satis_id,
                st.seans_adet + s.hediye_seans as toplam_seans,
                COUNT(r.id) as kullanilan_seans,
                (st.seans_adet + s.hediye_seans) - COUNT(r.id) as kalan_seans,
                MAX(r.randevu_tarihi) as son_randevu
            FROM satislar s
            JOIN danisanlar d ON d.id = s.danisan_id
            JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
            LEFT JOIN randevular r ON r.satis_id = s.id AND r.aktif = 1
            WHERE s.aktif = 1 
            AND s.durum != 'iptal'
            AND EXISTS (
                SELECT 1 FROM randevular r2 
                WHERE r2.satis_id = s.id 
                AND r2.personel_id = ?
                AND r2.aktif = 1
            )
            GROUP BY s.id, d.id, st.id
            HAVING kalan_seans > 0
            ORDER BY son_randevu DESC
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$bugun_randevular = getTerapistBugunRandevular($personel_id);
$yarin_randevular = getTerapistYarinRandevular($personel_id);
$istatistikler = getTerapistIstatistikler($personel_id);
$devam_eden_tedaviler = getTerapistDevamEdenTedaviler($personel_id);

$title = 'Terapist Dashboard';
$subtitle = $_SESSION['ad_soyad'];
include __DIR__ . '/partials/header.php';
?>

<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" />

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            <?php include __DIR__ . '/partials/page-title.php'; ?>

            <div class="container-fluid">

                <!-- Karşılama Mesajı -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bx bx-sun fs-3 me-3"></i>
                            <div>
                                <h4 class="alert-heading mb-1">Günaydın, <?= htmlspecialchars($_SESSION['ad_soyad']) ?>!</h4>
                                <p class="mb-0">Bugün <?= count($bugun_randevular) ?> randevunuz var. Harika bir gün geçirmeniz dileğiyle!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-start border-primary border-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bx bx-calendar-week fs-3 text-primary"></i>
                                <div class="ms-3">
                                    <h6 class="mb-0">Bu Hafta</h6>
                                    <h2 class="mb-0"><?= $istatistikler['haftalik_seans'] ?></h2>
                                    <small class="text-muted">Seans</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-start border-success border-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bx bx-calendar fs-3 text-success"></i>
                                <div class="ms-3">
                                    <h6 class="mb-0">Bu Ay</h6>
                                    <h2 class="mb-0"><?= $istatistikler['aylik_seans'] ?></h2>
                                    <small class="text-muted">Seans</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-start border-info border-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bx bx-user fs-3 text-info"></i>
                                <div class="ms-3">
                                    <h6 class="mb-0">Aktif Danışan</h6>
                                    <h2 class="mb-0"><?= $istatistikler['benzersiz_danisan'] ?></h2>
                                    <small class="text-muted">Bu Ay</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-start border-warning border-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bx bx-star fs-3 text-warning"></i>
                                <div class="ms-3">
                                    <h6 class="mb-0">Performans</h6>
                                    <h2 class="mb-0"><?= number_format($istatistikler['son_degerlendirme'], 1) ?></h2>
                                    <small class="text-muted">Son Değerlendirme</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Bugünkü Randevular -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-calendar-today me-2"></i>
                                    Bugünkü Randevularım (<?= count($bugun_randevular) ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($bugun_randevular)): ?>
                                    <div class="text-center py-4">
                                        <i class="bx bx-calendar-check fs-1 text-muted"></i>
                                        <p class="text-muted mt-2">Bugün randevunuz bulunmamaktadır.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($bugun_randevular as $randevu): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold"><?= htmlspecialchars($randevu['danisan_adi']) ?></div>
                                                    <small class="text-muted">
                                                        <i class="bx bx-time"></i> <?= date('H:i', strtotime($randevu['randevu_tarihi'])) ?> | 
                                                        <i class="bx bx-map"></i> <?= htmlspecialchars($randevu['room_name'] ?: 'Oda Atanmamış') ?> |
                                                        <i class="bx bx-phone"></i> <?= htmlspecialchars($randevu['telefon'] ?: '-') ?>
                                                    </small>
                                                    <div class="badge bg-light text-dark"><?= htmlspecialchars($randevu['seans_turu']) ?></div>
                                                </div>
                                                <span class="badge bg-primary rounded-pill"><?= $randevu['saat'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Yarınki Randevular -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-calendar-plus me-2"></i>
                                    Yarınki Randevularım (<?= count($yarin_randevular) ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($yarin_randevular)): ?>
                                    <div class="text-center py-4">
                                        <i class="bx bx-calendar-x fs-1 text-muted"></i>
                                        <p class="text-muted mt-2">Yarın randevunuz bulunmamaktadır.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($yarin_randevular as $randevu): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold"><?= htmlspecialchars($randevu['danisan_adi']) ?></div>
                                                    <small class="text-muted">
                                                        <i class="bx bx-time"></i> <?= date('H:i', strtotime($randevu['randevu_tarihi'])) ?> | 
                                                        <i class="bx bx-map"></i> <?= htmlspecialchars($randevu['room_name'] ?: 'Oda Atanmamış') ?> |
                                                        <i class="bx bx-phone"></i> <?= htmlspecialchars($randevu['telefon'] ?: '-') ?>
                                                    </small>
                                                    <div class="badge bg-light text-dark"><?= htmlspecialchars($randevu['seans_turu']) ?></div>
                                                </div>
                                                <span class="badge bg-success rounded-pill"><?= $randevu['saat'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Devam Eden Tedaviler -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-user-check me-2"></i>
                            Devam Eden Tedavilerim
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($devam_eden_tedaviler)): ?>
                            <div class="text-center py-4">
                                <i class="bx bx-user-x fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Devam eden tedaviniz bulunmamaktadır.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Danışan</th>
                                            <th>Telefon</th>
                                            <th>Seans Türü</th>
                                            <th>Kalan/Toplam</th>
                                            <th>Son Randevu</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($devam_eden_tedaviler as $tedavi): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tedavi['danisan_adi']) ?></td>
                                                <td><?= htmlspecialchars($tedavi['telefon'] ?: '-') ?></td>
                                                <td><?= htmlspecialchars($tedavi['seans_turu']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $tedavi['kalan_seans'] > 3 ? 'success' : ($tedavi['kalan_seans'] > 1 ? 'warning' : 'danger') ?>">
                                                        <?= $tedavi['kalan_seans'] ?>/<?= $tedavi['toplam_seans'] ?>
                                                    </span>
                                                </td>
                                                <td><?= $tedavi['son_randevu'] ? date('d.m.Y', strtotime($tedavi['son_randevu'])) : '-' ?></td>
                                                <td>
                                                    <a href="?page=randevular&satis_id=<?= $tedavi['satis_id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="bx bx-calendar-plus"></i> Randevu Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
<script>lucide.replace()</script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>