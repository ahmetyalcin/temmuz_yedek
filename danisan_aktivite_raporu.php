<?php
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// CSV Export işlemi
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $baslangic = $_GET['baslangic_tarih'] ?? '';
    $bitis = $_GET['bitis_tarih'] ?? '';
    $minimum_gun = $_GET['minimum_gun'] ?? 30;
    
    $data = getDanisanAktiviteRaporu($baslangic, $bitis, $minimum_gun);
    
    $headers = [
        'Danışan Adı',
        'Telefon',
        'Email',
        'Kayıt Tarihi',
        'Son Randevu',
        'Geçen Gün',
        'Paket Sayısı',
        'Randevu Sayısı',
        'Alınan Paketler',
        'Son Terapist',
        'Aktivite Durumu'
    ];
    
    $export_data = [];
    foreach ($data as $row) {
        $export_data[] = [
            $row['danisan_adi'],
            $row['telefon'],
            $row['email'],
            date('d.m.Y', strtotime($row['kayit_tarihi'])),
            $row['son_randevu_tarihi'] ? date('d.m.Y', strtotime($row['son_randevu_tarihi'])) : 'Hiç randevu yok',
            $row['gun_farki'] ?? 'N/A',
            $row['toplam_paket_sayisi'],
            $row['toplam_randevu_sayisi'],
            $row['alinan_paketler'] ?? 'Yok',
            $row['son_terapist'] ?? 'Yok',
            $row['aktivite_durumu']
        ];
    }
    
    exportToCsv($export_data, 'danisan_aktivite_raporu_' . date('Y-m-d') . '.csv', $headers);
}

// Filtreleme parametreleri
$baslangic_tarih = $_GET['baslangic_tarih'] ?? date('Y-m-d', strtotime('-1 year'));
$bitis_tarih = $_GET['bitis_tarih'] ?? date('Y-m-d');
$minimum_gun = $_GET['minimum_gun'] ?? 30;

// Rapor verilerini getir
$rapor_verileri = getDanisanAktiviteRaporu($baslangic_tarih, $bitis_tarih, $minimum_gun);
$istatistikler = getRaporIstatistikleri();

$title = "Danışan Aktivite Raporu";
$subtitle = "Raporlar";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php" ?>
    <?php include 'partials/head-css.php' ?>
    
    <!-- BoxIcons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .activity-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .days-indicator {
            font-weight: bold;
        }
        .days-critical { color: #dc3545; }
        .days-warning { color: #fd7e14; }
        .days-normal { color: #198754; }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php' ?>
        <?php include 'partials/topbar.php' ?>

        <div class="page-content">
            <div class="page-container">
                <?php include "partials/page-title.php" ?>

                <!-- İstatistik Kartları -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="bx bx-user-circle text-primary fs-2"></i>
                                </div>
                                <h3 class="mb-1"><?= number_format($istatistikler['toplam_danisan']) ?></h3>
                                <p class="text-muted mb-0">Toplam Danışan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="bx bx-time-five text-warning fs-2"></i>
                                </div>
                                <h3 class="mb-1"><?= number_format(count($rapor_verileri)) ?></h3>
                                <p class="text-muted mb-0">Uzun Süredir Gelmeyen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="bx bx-user-x text-danger fs-2"></i>
                                </div>
                                <h3 class="mb-1">
                                    <?= number_format(count(array_filter($rapor_verileri, function($v) { 
                                        return is_null($v['son_randevu_tarihi']); 
                                    }))) ?>
                                </h3>
                                <p class="text-muted mb-0">Hiç Randevu Almamış</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="bx bx-error-circle text-info fs-2"></i>
                                </div>
                                <h3 class="mb-1">
                                    <?= number_format(count(array_filter($rapor_verileri, function($v) { 
                                        return !is_null($v['son_randevu_tarihi']) && $v['gun_farki'] > 90; 
                                    }))) ?>
                                </h3>
                                <p class="text-muted mb-0">90+ Gün Gelmeyen</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtreleme Bölümü -->
                <div class="filter-section">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="baslangic_tarih" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="baslangic_tarih" name="baslangic_tarih" 
                                   value="<?= htmlspecialchars($baslangic_tarih) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="bitis_tarih" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="bitis_tarih" name="bitis_tarih" 
                                   value="<?= htmlspecialchars($bitis_tarih) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="minimum_gun" class="form-label">Minimum Gün Aralığı</label>
                            <select class="form-select" id="minimum_gun" name="minimum_gun">
                                <option value="15" <?= $minimum_gun == 15 ? 'selected' : '' ?>>15 gün</option>
                                <option value="30" <?= $minimum_gun == 30 ? 'selected' : '' ?>>30 gün</option>
                                <option value="60" <?= $minimum_gun == 60 ? 'selected' : '' ?>>60 gün</option>
                                <option value="90" <?= $minimum_gun == 90 ? 'selected' : '' ?>>90 gün</option>
                                <option value="180" <?= $minimum_gun == 180 ? 'selected' : '' ?>>6 ay</option>
                                <option value="365" <?= $minimum_gun == 365 ? 'selected' : '' ?>>1 yıl</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-search me-1"></i>Filtrele
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="bx bx-refresh me-1"></i>Temizle
                                </a>
                                <?php if (!empty($rapor_verileri)): ?>
                                <a href="?export=csv&baslangic_tarih=<?= urlencode($baslangic_tarih) ?>&bitis_tarih=<?= urlencode($bitis_tarih) ?>&minimum_gun=<?= $minimum_gun ?>" 
                                   class="btn btn-success">
                                    <i class="bx bx-download me-1"></i>Excel
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Rapor Tablosu -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-user-check me-2"></i>
                            <?= $minimum_gun ?> Günden Fazla Süredir Hizmet Almayan Danışanlar
                            <span class="badge bg-primary"><?= count($rapor_verileri) ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rapor_verileri)): ?>
                            <div class="text-center py-5">
                                <i class="bx bx-smile text-success" style="font-size: 3rem;"></i>
                                <h5 class="text-success mt-3">Harika! Belirtilen kriterlere uygun danışan bulunamadı</h5>
                                <p class="text-muted">Tüm danışanlarınız düzenli olarak hizmet alıyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="aktiviteRaporTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Danışan</th>
                                            <th>İletişim</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>Son Randevu</th>
                                            <th>Geçen Süre</th>
                                            <th>Paket Bilgileri</th>
                                            <th>Son Terapist</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rapor_verileri as $veri): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($veri['danisan_adi']) ?></strong>
                                            </td>
                                            <td>
                                                <small class="d-block">
                                                    <i class="bx bx-phone me-1"></i>
                                                    <a href="tel:<?= $veri['telefon'] ?>"><?= htmlspecialchars($veri['telefon']) ?></a>
                                                </small>
                                                <?php if ($veri['email']): ?>
                                                <small class="d-block text-muted">
                                                    <i class="bx bx-envelope me-1"></i>
                                                    <a href="mailto:<?= $veri['email'] ?>"><?= htmlspecialchars($veri['email']) ?></a>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('d.m.Y', strtotime($veri['kayit_tarihi'])) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($veri['son_randevu_tarihi']): ?>
                                                    <small><?= date('d.m.Y', strtotime($veri['son_randevu_tarihi'])) ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Hiç randevu yok</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($veri['gun_farki']): ?>
                                                    <span class="days-indicator 
                                                         <?= $veri['gun_farki'] > 90 ? 'days-critical' : 
                                                             ($veri['gun_farki'] > 60 ? 'days-warning' : 'days-normal') ?>">
                                                        <?= $veri['gun_farki'] ?> gün
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="d-block">
                                                    <strong>Paket:</strong> <?= $veri['toplam_paket_sayisi'] ?>
                                                </small>
                                                <small class="d-block">
                                                    <strong>Randevu:</strong> <?= $veri['toplam_randevu_sayisi'] ?>
                                                </small>
                                                <?php if ($veri['alinan_paketler']): ?>
                                                <small class="text-muted" title="<?= htmlspecialchars($veri['alinan_paketler']) ?>">
                                                    <?= strlen($veri['alinan_paketler']) > 20 ? 
                                                        substr($veri['alinan_paketler'], 0, 20) . '...' : 
                                                        $veri['alinan_paketler'] ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($veri['son_terapist'] ?? 'Yok') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge activity-badge 
                                                     <?= $veri['aktivite_durumu'] === 'Hiç Randevu Almamış' ? 'bg-danger' : 
                                                         ($veri['aktivite_durumu'] === 'Çok Uzun Süreli' ? 'bg-danger' : 
                                                          ($veri['aktivite_durumu'] === 'Uzun Süreli' ? 'bg-warning' : 
                                                           ($veri['aktivite_durumu'] === 'Orta Süreli' ? 'bg-info' : 'bg-success'))) ?>">
                                                    <?= $veri['aktivite_durumu'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="tel:<?= $veri['telefon'] ?>" class="btn btn-outline-success btn-sm" 
                                                       title="Ara">
                                                        <i class="bx bx-phone"></i>
                                                    </a>
                                                    <?php if ($veri['email']): ?>
                                                    <a href="mailto:<?= $veri['email'] ?>" class="btn btn-outline-primary btn-sm" 
                                                       title="Email Gönder">
                                                        <i class="bx bx-envelope"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="room_schedule.php?danisan_id=<?= $veri['danisan_id'] ?>" 
                                                       class="btn btn-outline-info btn-sm" title="Randevu Ver">
                                                        <i class="bx bx-calendar-plus"></i>
                                                    </a>
                                                </div>
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

    <?php include 'partials/vendor-scripts.php' ?>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#aktiviteRaporTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
                },
                pageLength: 25,
                order: [[4, 'desc']], // Geçen süreye göre sırala (en uzun süre önce)
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="bx bx-download me-1"></i>Excel',
                        className: 'btn btn-success btn-sm'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="bx bx-file-pdf me-1"></i>PDF',
                        className: 'btn btn-danger btn-sm'
                    }
                ]
            });
        });
    </script>

    <?php include 'partials/customizer.php' ?>
    <?php include 'partials/footer-scripts.php' ?>
</body>
</html>