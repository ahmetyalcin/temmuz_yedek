<?php
session_start();
require_once 'functions.php';

// Filtreleme parametreleri
$ay = $_GET['ay'] ?? date('m');
$yil = $_GET['yil'] ?? date('Y');
$gun = $_GET['gun'] ?? '';

// Tarih filtreleme
$tarih_filtre_gelir = "";
$tarih_filtre_gider = "";
$params_gelir = [];
$params_gider = [];

if (!empty($gun) && !empty($ay) && !empty($yil)) {
    $tarih = sprintf('%04d-%02d-%02d', $yil, $ay, $gun);
    $tarih_filtre_gelir = "AND DATE(s.olusturma_tarihi) = :tarih";
    $tarih_filtre_gider = "AND DATE(g.tarih) = :tarih";
    $params_gelir['tarih'] = $tarih;
    $params_gider['tarih'] = $tarih;
} elseif (!empty($ay) && !empty($yil)) {
    $tarih_filtre_gelir = "AND YEAR(s.olusturma_tarihi) = :yil AND MONTH(s.olusturma_tarihi) = :ay";
    $tarih_filtre_gider = "AND YEAR(g.tarih) = :yil AND MONTH(g.tarih) = :ay";
    $params_gelir['yil'] = $yil;
    $params_gelir['ay'] = $ay;
    $params_gider['yil'] = $yil;
    $params_gider['ay'] = $ay;
} elseif (!empty($yil)) {
    $tarih_filtre_gelir = "AND YEAR(s.olusturma_tarihi) = :yil";
    $tarih_filtre_gider = "AND YEAR(g.tarih) = :yil";
    $params_gelir['yil'] = $yil;
    $params_gider['yil'] = $yil;
}

// GELİR SORGUSU - Satışları getir
$sql_gelir = "SELECT 
    s.id as satis_id,
    s.olusturma_tarihi as tarih,
    CONCAT(d.ad, ' ', d.soyad, ' (', st.ad, ')') as ad_soyad,
    st.ad as hizmet,
    s.toplam_tutar as tutar,
    'Gelir' as tip,
    'Hizmet Satışı' as kategori,
    s.notlar as aciklama,
    CONCAT(d.ad, ' ', d.soyad) as musteri_adi,
    st.ad as seans_turu
FROM satislar s
LEFT JOIN danisanlar d ON d.id = s.danisan_id
LEFT JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
WHERE s.aktif = 1 {$tarih_filtre_gelir}
ORDER BY s.olusturma_tarihi DESC";

// GİDER SORGUSU - Giderleri getir
$sql_gider = "SELECT 
    g.id as gider_id,
    g.tarih,
    g.aciklama as aciklama,
    gk.ad as kategori,
    g.tutar,
    'Gider' as tip,
    COALESCE(g.tedarikci, 'Belirtilmemiş') as ad_soyad,
    g.fatura_no,
    g.tedarikci,
    NULL as seans_turu
FROM giderler g
LEFT JOIN gider_kategorileri gk ON g.kategori_id = gk.id
WHERE g.aktif = 1 {$tarih_filtre_gider}
ORDER BY g.tarih DESC";

try {
    // Gelirleri getir
    $stmt = $pdo->prepare($sql_gelir);
    $stmt->execute($params_gelir);
    $gelirler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Giderleri getir
    $stmt = $pdo->prepare($sql_gider);
    $stmt->execute($params_gider);
    $giderler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $gelirler = [];
    $giderler = [];
    $hata = "Veri getirme hatası: " . $e->getMessage();
}

// Tüm kayıtları birleştir ve tarihe göre sırala
$tum_kayitlar = array_merge($gelirler, $giderler);
usort($tum_kayitlar, function($a, $b) {
    $tarih_a = isset($a['tarih']) ? $a['tarih'] : $a['tarih'];
    $tarih_b = isset($b['tarih']) ? $b['tarih'] : $b['tarih'];
    return strtotime($tarih_b) - strtotime($tarih_a);
});

// Toplam hesapla
$toplam_gelir = array_sum(array_column($gelirler, 'tutar'));
$toplam_gider = array_sum(array_column($giderler, 'tutar'));
$net_kar = $toplam_gelir - $toplam_gider;

// Kategori bazlı özet
$gelir_kategoriler = [];
$gider_kategoriler = [];

foreach($gelirler as $gelir) {
    $kategori = $gelir['kategori'] ?? 'Diğer';
    if (!isset($gelir_kategoriler[$kategori])) {
        $gelir_kategoriler[$kategori] = 0;
    }
    $gelir_kategoriler[$kategori] += $gelir['tutar'];
}

foreach($giderler as $gider) {
    $kategori = $gider['kategori'] ?? 'Diğer';
    if (!isset($gider_kategoriler[$kategori])) {
        $gider_kategoriler[$kategori] = 0;
    }
    $gider_kategoriler[$kategori] += $gider['tutar'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Kar-Zarar Analizi";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
        .excel-table {
            font-size: 12px;
            white-space: nowrap;
        }
        .excel-table th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 8px 4px;
        }
        .excel-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .gelir-row { background-color: #e8f5e8; }
        .gider-row { background-color: #ffe8e8; }
        .filter-row {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-row {
            background-color: #e3f2fd;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .kar-pozitif { color: #28a745; font-weight: bold; }
        .kar-negatif { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Muhasebe";
                $title = "Kar-Zarar Analizi";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line text-primary me-2"></i>
                                    Kar-Zarar Analizi
                                </h5>
                                <button class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Excel İndir
                                </button>
                            </div>
                            <div class="card-body">

                                <!-- Filtreler -->
                                <div class="filter-row">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Yıl</label>
                                            <select name="yil" class="form-select">
                                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                                    <option value="<?= $y ?>" <?= $yil == $y ? 'selected' : '' ?>><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Ay</label>
                                            <select name="ay" class="form-select">
                                                <option value="">Tüm Aylar</option>
                                                <?php 
                                                $aylar = [
                                                    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                                    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                                    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                                ];
                                                foreach($aylar as $num => $isim): ?>
                                                    <option value="<?= $num ?>" <?= $ay == $num ? 'selected' : '' ?>><?= $isim ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Gün</label>
                                            <select name="gun" class="form-select">
                                                <option value="">Tüm Günler</option>
                                                <?php for($g = 1; $g <= 31; $g++): ?>
                                                    <option value="<?= sprintf('%02d', $g) ?>" <?= $gun == sprintf('%02d', $g) ? 'selected' : '' ?>><?= $g ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-search me-2"></i>Filtrele
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Özet Kartları -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Toplam Gelir</h6>
                                                <h4><?= number_format($toplam_gelir, 2, ',', '.') ?> ₺</h4>
                                                <small><?= count($gelirler) ?> işlem</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-danger text-white">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Toplam Gider</h6>
                                                <h4><?= number_format($toplam_gider, 2, ',', '.') ?> ₺</h4>
                                                <small><?= count($giderler) ?> işlem</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card <?= $net_kar >= 0 ? 'bg-primary' : 'bg-warning' ?> text-white">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Net Kar/Zarar</h6>
                                                <h4><?= number_format($net_kar, 2, ',', '.') ?> ₺</h4>
                                                <small><?= $net_kar >= 0 ? 'Kar' : 'Zarar' ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">Kar Marjı</h6>
                                                <h4>
                                                    <?= $toplam_gelir > 0 ? number_format(($net_kar / $toplam_gelir) * 100, 1) : 0 ?>%
                                                </h4>
                                                <small>Karlılık oranı</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grafik -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Gelir Kategori Dağılımı</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="gelirChart" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Gider Kategori Dağılımı</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="giderChart" width="400" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detay Tablo -->
                                <div class="table-responsive">
                                    <table class="table excel-table" id="kar_zarar_tablosu">
                                        <thead>
                                            <tr>
                                                <th>TARİH</th>
                                                <th>TİP</th>
                                                <th>KATEGORİ</th>
                                                <th>AÇIKLAMA</th>
                                                <th>MÜŞTERİ/TEDARİKÇİ & SEANS</th>
                                                <th>TUTAR</th>
                                                <th>NOTLAR</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($tum_kayitlar as $kayit): ?>
                                                <tr class="<?= $kayit['tip'] == 'Gelir' ? 'gelir-row' : 'gider-row' ?>">
                                                    <td class="text-center">
                                                        <?php 
                                                        $tarih = isset($kayit['tarih']) ? $kayit['tarih'] : '';
                                                        echo date('d.m.Y', strtotime($tarih)); 
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?= $kayit['tip'] == 'Gelir' ? 'success' : 'danger' ?>">
                                                            <?= $kayit['tip'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($kayit['kategori'] ?? 'Diğer') ?></td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;"
                                                             title="<?= htmlspecialchars($kayit['aciklama'] ?? '') ?>">
                                                            <?= htmlspecialchars($kayit['aciklama'] ?? '') ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($kayit['tip'] == 'Gelir'): ?>
                                                            <div>
                                                                <strong><?= htmlspecialchars($kayit['musteri_adi'] ?? '-') ?></strong>
                                                                <?php if (!empty($kayit['seans_turu'])): ?>
                                                                    <br><small class="text-muted">
                                                                        <i class="fas fa-dumbbell me-1"></i>
                                                                        <?= htmlspecialchars($kayit['seans_turu']) ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($kayit['ad_soyad'] ?? '-') ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-right fw-bold <?= $kayit['tip'] == 'Gelir' ? 'text-success' : 'text-danger' ?>">
                                                        <?= $kayit['tip'] == 'Gelir' ? '+' : '-' ?><?= number_format($kayit['tutar'], 2, ',', '.') ?> ₺
                                                    </td>
                                                    <td>
                                                        <?php if(isset($kayit['fatura_no']) && !empty($kayit['fatura_no'])): ?>
                                                            <small>Fatura: <?= htmlspecialchars($kayit['fatura_no']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="summary-row">
                                                <td colspan="5" class="text-center"><strong>NET KAR/ZARAR</strong></td>
                                                <td class="text-right">
                                                    <strong class="<?= $net_kar >= 0 ? 'kar-pozitif' : 'kar-negatif' ?>">
                                                        <?= number_format($net_kar, 2, ',', '.') ?> ₺
                                                    </strong>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#kar_zarar_tablosu').DataTable({
                    "paging": false,
                    "searching": false,
                    "info": false,
                    "ordering": false,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
                    }
                });

                // Gelir grafiği
                const gelirCtx = document.getElementById('gelirChart').getContext('2d');
                new Chart(gelirCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_keys($gelir_kategoriler)) ?>,
                        datasets: [{
                            data: <?= json_encode(array_values($gelir_kategoriler)) ?>,
                            backgroundColor: [
                                '#28a745', '#20c997', '#17a2b8', '#6f42c1', '#e83e8c',
                                '#fd7e14', '#ffc107', '#6c757d'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });

                // Gider grafiği
                const giderCtx = document.getElementById('giderChart').getContext('2d');
                new Chart(giderCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_keys($gider_kategoriler)) ?>,
                        datasets: [{
                            data: <?= json_encode(array_values($gider_kategoriler)) ?>,
                            backgroundColor: [
                                '#dc3545', '#fd7e14', '#ffc107', '#e83e8c', '#6f42c1',
                                '#6c757d', '#17a2b8', '#20c997'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            });

            function exportToExcel() {
                const table = document.getElementById('kar_zarar_tablosu').cloneNode(true);
                
                // Excel'e dönüştür
                const wb = XLSX.utils.table_to_book(table, {
                    sheet: "Kar-Zarar Analizi"
                });
                
                // Kolon genişliklerini ayarla
                const ws = wb.Sheets["Kar-Zarar Analizi"];
                ws['!cols'] = [
                    {wch: 12}, // TARİH
                    {wch: 10}, // TİP
                    {wch: 20}, // KATEGORİ
                    {wch: 30}, // AÇIKLAMA
                    {wch: 25}, // MÜŞTERİ/TEDARİKÇİ
                    {wch: 15}, // TUTAR
                    {wch: 20}  // NOTLAR
                ];
                
                const today = new Date();
                const dateStr = today.toISOString().slice(0,10);
                const filename = `Kar_Zarar_Analizi_${dateStr}.xlsx`;
                
                XLSX.writeFile(wb, filename);
                alert('Excel dosyası indirildi: ' + filename);
            }
        </script>
    </div>
</body>
</html>