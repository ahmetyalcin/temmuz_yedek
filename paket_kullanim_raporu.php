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
    
    $data = getPaketKullanimRaporu($baslangic, $bitis);
    
    $headers = [
        'Danışan Adı',
        'Telefon', 
        'Email',
        'Paket Adı',
        'Toplam Seans',
        'Kullanılan Seans',
        'Kalan Seans',
        'Kullanım %',
        'Toplam Tutar',
        'Satış Tarihi',
        'Son Kullanma',
        'Son Randevu',
        'Terapist',
        'Durum'
    ];
    
    $export_data = [];
    foreach ($data as $row) {
        $export_data[] = [
            $row['danisan_adi'],
            $row['telefon'],
            $row['email'],
            $row['paket_adi'],
            $row['toplam_seans'],
            $row['kullanilan_seans'],
            $row['kalan_seans'],
            $row['kullanim_yuzdesi'] . '%',
            number_format($row['toplam_tutar'], 2) . ' TL',
            date('d.m.Y', strtotime($row['satis_tarihi'])),
            date('d.m.Y', strtotime($row['son_kullanma_tarihi'])),
            $row['son_randevu_tarihi'] ? date('d.m.Y', strtotime($row['son_randevu_tarihi'])) : 'Yok',
            $row['terapist_adi'],
            $row['durum']
        ];
    }
    
    exportToCsv($export_data, 'paket_kullanim_raporu_' . date('Y-m-d') . '.csv', $headers);
}

// Filtreleme
$baslangic_tarih = $_GET['baslangic_tarih'] ?? '';
$bitis_tarih = $_GET['bitis_tarih'] ?? '';

// Rapor verilerini getir
$rapor_verileri = getPaketKullanimRaporu($baslangic_tarih, $bitis_tarih);
$istatistikler = getRaporIstatistikleri();

$title = "Paket Kullanım Raporu";
$subtitle = "Raporlar";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php" ?>
    <?php include 'partials/head-css.php' ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .progress-circle {
            width: 60px;
            height: 60px;
        }
        .status-badge {
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
                                    <i class="bx bx-package text-success fs-2"></i>
                                </div>
                                <h3 class="mb-1"><?= number_format($istatistikler['aktif_paket_sahipleri']) ?></h3>
                                <p class="text-muted mb-0">Aktif Paket Sahibi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="bx bx-time text-warning fs-2"></i>
                                </div>
                                <h3 class="mb-1"><?= number_format($istatistikler['uzun_sure_gelmeyenler']) ?></h3>
                                <p class="text-muted mb-0">Uzun Süredir Gelmeyen</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card h-100">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="bx bx-check-circle text-info fs-2"></i>
                                </div>
                                <h3 class="mb-1"><?= number_format($istatistikler['bu_ay_tamamlanan']) ?></h3>
                                <p class="text-muted mb-0">Bu Ay Tamamlanan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtreleme Bölümü -->
                <div class="filter-section">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="baslangic_tarih" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="baslangic_tarih" name="baslangic_tarih" 
                                   value="<?= htmlspecialchars($baslangic_tarih) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="bitis_tarih" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="bitis_tarih" name="bitis_tarih" 
                                   value="<?= htmlspecialchars($bitis_tarih) ?>">
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-search me-1"></i>Filtrele
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="bx bx-refresh me-1"></i>Temizle
                                </a>
                                <?php if (!empty($rapor_verileri)): ?>
                                <a href="?export=csv&baslangic_tarih=<?= urlencode($baslangic_tarih) ?>&bitis_tarih=<?= urlencode($bitis_tarih) ?>" 
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
                            <i class="bx bx-list-ul me-2"></i>
                            Paket Kullanımı Tamamlanmamış Danışanlar 
                            <span class="badge bg-primary"><?= count($rapor_verileri) ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rapor_verileri)): ?>
                            <div class="text-center py-5">
                                <i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Belirtilen kriterlere uygun veri bulunamadı</h5>
                                <p class="text-muted">Farklı tarih aralığı seçerek tekrar deneyin.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="paketRaporTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Danışan</th>
                                            <th>İletişim</th>
                                            <th>Paket</th>
                                            <th>Kullanım</th>
                                            <th>Kalan Seans</th>
                                            <th>Tutar</th>
                                            <th>Tarihler</th>
                                            <th>Terapist</th>
                                            <th>Durum</th>
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
                                                    <i class="bx bx-phone me-1"></i><?= htmlspecialchars($veri['telefon']) ?>
                                                </small>
                                                <?php if ($veri['email']): ?>
                                                <small class="d-block text-muted">
                                                    <i class="bx bx-envelope me-1"></i><?= htmlspecialchars($veri['email']) ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-medium"><?= htmlspecialchars($veri['paket_adi']) ?></span>
                                                <small class="d-block text-muted">
                                                    <?= $veri['toplam_seans'] ?> seans
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 60px; height: 8px;">
                                                        <div class="progress-bar 
                                                             <?= $veri['kullanim_yuzdesi'] >= 75 ? 'bg-success' : 
                                                                 ($veri['kullanim_yuzdesi'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                                             style="width: <?= $veri['kullanim_yuzdesi'] ?>%"></div>
                                                    </div>
                                                    <small><?= $veri['kullanim_yuzdesi'] ?>%</small>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $veri['kullanilan_seans'] ?>/<?= $veri['toplam_seans'] ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <?= $veri['kalan_seans'] ?> seans
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= number_format($veri['toplam_tutar'], 2) ?> ₺</strong>
                                            </td>
                                            <td>
                                                <small class="d-block">
                                                    <strong>Satış:</strong> <?= date('d.m.Y', strtotime($veri['satis_tarihi'])) ?>
                                                </small>
                                                <small class="d-block">
                                                    <strong>Bitiş:</strong> <?= date('d.m.Y', strtotime($veri['son_kullanma_tarihi'])) ?>
                                                </small>
                                                <?php if ($veri['son_randevu_tarihi']): ?>
                                                <small class="d-block">
                                                    <strong>Son:</strong> <?= date('d.m.Y', strtotime($veri['son_randevu_tarihi'])) ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($veri['terapist_adi']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge status-badge 
                                                     <?= $veri['durum'] === 'Süresi Dolmuş' ? 'bg-danger' : 
                                                         ($veri['durum'] === 'Devam Ediyor' ? 'bg-success' : 'bg-info') ?>">
                                                    <?= $veri['durum'] ?>
                                                </span>
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
            $('#paketRaporTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
                },
                pageLength: 25,
                order: [[3, 'asc']], // Kullanım yüzdesine göre sırala
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