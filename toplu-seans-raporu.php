<?php
// toplu-seans-raporu.php
session_start();
require_once 'functions.php';

// Filtreleme parametreleri
$filtre_durum = $_GET['durum'] ?? '';
$filtre_paket = $_GET['paket'] ?? '';
$filtre_terapist = $_GET['terapist'] ?? '';
$filtre_tarih_baslangic = $_GET['tarih_baslangic'] ?? '';
$filtre_tarih_bitis = $_GET['tarih_bitis'] ?? '';
$arama = $_GET['arama'] ?? '';

// Toplu seans raporu fonksiyonu
function getTopluSeansRaporu($filtreler = []) {
    global $pdo;
    try {
        $where_conditions = ["s.aktif = 1"];
        $params = [];
        
        // Filtreleme koşulları
        if (!empty($filtreler['durum'])) {
            $where_conditions[] = "s.durum = :durum";
            $params['durum'] = $filtreler['durum'];
        }
        
        if (!empty($filtreler['paket'])) {
            $where_conditions[] = "s.hizmet_paketi_id = :paket";
            $params['paket'] = $filtreler['paket'];
        }
        
        if (!empty($filtreler['terapist'])) {
            $where_conditions[] = "s.personel_id = :terapist";
            $params['terapist'] = $filtreler['terapist'];
        }
        
        if (!empty($filtreler['tarih_baslangic'])) {
            $where_conditions[] = "DATE(s.olusturma_tarihi) >= :tarih_baslangic";
            $params['tarih_baslangic'] = $filtreler['tarih_baslangic'];
        }
        
        if (!empty($filtreler['tarih_bitis'])) {
            $where_conditions[] = "DATE(s.olusturma_tarihi) <= :tarih_bitis";
            $params['tarih_bitis'] = $filtreler['tarih_bitis'];
        }
        
        if (!empty($filtreler['arama'])) {
            $where_conditions[] = "(CONCAT(d.ad, ' ', d.soyad) LIKE :arama OR d.telefon LIKE :arama)";
            $params['arama'] = '%' . $filtreler['arama'] . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    d.id as danisan_id,
                    CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                    d.telefon,
                    d.email,
                    -- Toplam istatistikler
                    COUNT(DISTINCT s.id) as toplam_paket_sayisi,
                    SUM(st.seans_adet + COALESCE(s.hediye_seans, 0)) as toplam_seans,
                    SUM((SELECT COUNT(*) FROM randevular r WHERE r.satis_id = s.id AND r.aktif = 1)) as kullanilan_seans,
                    SUM(st.seans_adet + COALESCE(s.hediye_seans, 0)) - SUM((SELECT COUNT(*) FROM randevular r WHERE r.satis_id = s.id AND r.aktif = 1)) as kalan_seans,
                    -- Finansal bilgiler
                    SUM(s.toplam_tutar) as toplam_tutar,
                    SUM(s.odenen_tutar) as toplam_odenen,
                    SUM(s.toplam_tutar - s.odenen_tutar) as toplam_kalan_borc,
                    ROUND(AVG((s.odenen_tutar / s.toplam_tutar) * 100), 1) as ortalama_odeme_yuzdesi,
                    -- Durum bilgileri
                    COUNT(CASE WHEN (st.seans_adet + COALESCE(s.hediye_seans, 0)) - (SELECT COUNT(*) FROM randevular r WHERE r.satis_id = s.id AND r.aktif = 1) > 0 THEN 1 END) as aktif_paket_sayisi,
                    MAX(s.olusturma_tarihi) as son_satis_tarihi,
                    MAX((SELECT MAX(randevu_tarihi) FROM randevular r WHERE r.satis_id = s.id AND r.aktif = 1)) as son_randevu_tarihi
                FROM danisanlar d
                JOIN satislar s ON d.id = s.danisan_id
                JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
                WHERE {$where_clause}
                GROUP BY d.id
                ORDER BY danisan_adi";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Toplu seans raporu hatası: " . $e->getMessage());
        return [];
    }
}

// Kritik durumlar için özel sorgu
function getKritikDurumlar() {
    global $pdo;
    try {
        $sql = "SELECT 
                    CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                    d.telefon,
                    st.ad as paket_adi,
                    s.toplam_tutar,
                    s.odenen_tutar,
                    s.vade_tarihi,
                    (st.seans_adet + COALESCE(s.hediye_seans, 0)) as toplam_seans,
                    COUNT(r.id) as kullanilan_seans,
                    (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) as kalan_seans,
                    CASE 
                        WHEN s.vade_tarihi < CURDATE() AND (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) > 0 THEN 'Süresi Dolmuş'
                        WHEN (s.odenen_tutar / s.toplam_tutar) < 0.5 THEN 'Ödeme Eksik'
                        WHEN (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) <= 2 AND (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) > 0 THEN 'Seanslar Bitiyor'
                        ELSE 'Normal'
                    END as kritik_durum,
                    s.id as satis_id
                FROM danisanlar d
                JOIN satislar s ON d.id = s.danisan_id
                JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
                LEFT JOIN randevular r ON s.id = r.satis_id AND r.aktif = 1
                WHERE s.aktif = 1
                GROUP BY s.id
                HAVING kritik_durum != 'Normal'
                ORDER BY 
                    CASE kritik_durum 
                        WHEN 'Süresi Dolmuş' THEN 1
                        WHEN 'Ödeme Eksik' THEN 2
                        WHEN 'Seanslar Bitiyor' THEN 3
                        ELSE 4
                    END, danisan_adi";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Kritik durumlar hatası: " . $e->getMessage());
        return [];
    }
}

// Dropdown için veriler
$paketler = getSeansPaketleri();
$terapistler = getTerapistler(true);

// Filtreleri uygula
$filtreler = [
    'durum' => $filtre_durum,
    'paket' => $filtre_paket,
    'terapist' => $filtre_terapist,
    'tarih_baslangic' => $filtre_tarih_baslangic,
    'tarih_bitis' => $filtre_tarih_bitis,
    'arama' => $arama
];

$rapor_verileri = getTopluSeansRaporu($filtreler);
$kritik_durumlar = getKritikDurumlar();

// Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="seans-raporu-' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr>
        <th>Danışan</th>
        <th>Telefon</th>
        <th>Toplam Paket</th>
        <th>Toplam Seans</th>
        <th>Kullanılan</th>
        <th>Kalan</th>
        <th>Toplam Tutar</th>
        <th>Ödenen</th>
        <th>Kalan Borç</th>
        <th>Ödeme %</th>
        <th>Son Satış</th>
        <th>Son Randevu</th>
    </tr>';
    
    foreach ($rapor_verileri as $veri) {
        echo '<tr>';
        echo '<td>' . $veri['danisan_adi'] . '</td>';
        echo '<td>' . $veri['telefon'] . '</td>';
        echo '<td>' . $veri['toplam_paket_sayisi'] . '</td>';
        echo '<td>' . $veri['toplam_seans'] . '</td>';
        echo '<td>' . $veri['kullanilan_seans'] . '</td>';
        echo '<td>' . $veri['kalan_seans'] . '</td>';
        echo '<td>' . number_format($veri['toplam_tutar'], 2) . '</td>';
        echo '<td>' . number_format($veri['toplam_odenen'], 2) . '</td>';
        echo '<td>' . number_format($veri['toplam_kalan_borc'], 2) . '</td>';
        echo '<td>%' . $veri['ortalama_odeme_yuzdesi'] . '</td>';
        echo '<td>' . ($veri['son_satis_tarihi'] ? date('d.m.Y', strtotime($veri['son_satis_tarihi'])) : '') . '</td>';
        echo '<td>' . ($veri['son_randevu_tarihi'] ? date('d.m.Y', strtotime($veri['son_randevu_tarihi'])) : '') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

// Özet istatistikler
$genel_istatistikler = [
    'toplam_danisan' => count($rapor_verileri),
    'toplam_paket' => array_sum(array_column($rapor_verileri, 'toplam_paket_sayisi')),
    'toplam_seans' => array_sum(array_column($rapor_verileri, 'toplam_seans')),
    'kullanilan_seans' => array_sum(array_column($rapor_verileri, 'kullanilan_seans')),
    'kalan_seans' => array_sum(array_column($rapor_verileri, 'kalan_seans')),
    'toplam_ciro' => array_sum(array_column($rapor_verileri, 'toplam_tutar')),
    'toplam_tahsilat' => array_sum(array_column($rapor_verileri, 'toplam_odenen')),
    'toplam_alacak' => array_sum(array_column($rapor_verileri, 'toplam_kalan_borc'))
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Toplu Seans Raporu";
    include "partials/title-meta.php";
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-box h4 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-box p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .kritik-alert {
            border-left: 4px solid #dc3545;
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 10px;
        }
        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .btn-export {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .progress-bar-seans {
            height: 6px;
        }
    </style>
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
            $subtitle = "Raporlar";
            $title = "Toplu Seans Raporu";
            include "partials/page-title.php";
            ?>

            <!-- Genel İstatistikler -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h4><?= $genel_istatistikler['toplam_danisan'] ?></h4>
                        <p>Toplam Danışan</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h4><?= number_format($genel_istatistikler['toplam_paket']) ?></h4>
                        <p>Toplam Paket</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h4><?= number_format($genel_istatistikler['toplam_seans']) ?></h4>
                        <p>Toplam Seans</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h4><?= number_format($genel_istatistikler['kalan_seans']) ?></h4>
                        <p>Kalan Seans</p>
                    </div>
                </div>
            </div>

            <!-- Finansal İstatistikler -->
            <div class="row mb-4">
                <div class="col-lg-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h4><?= number_format($genel_istatistikler['toplam_ciro'], 0) ?>₺</h4>
                        <p>Toplam Ciro</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <h4 style="color: #333;"><?= number_format($genel_istatistikler['toplam_tahsilat'], 0) ?>₺</h4>
                        <p style="color: #666;">Toplam Tahsilat</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                        <h4 style="color: #333;"><?= number_format($genel_istatistikler['toplam_alacak'], 0) ?>₺</h4>
                        <p style="color: #666;">Toplam Alacak</p>
                    </div>
                </div>
            </div>

            <!-- Kritik Durumlar -->
            <?php if (!empty($kritik_durumlar)): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Kritik Durumlar (<?= count($kritik_durumlar) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_slice($kritik_durumlar, 0, 6) as $kritik): ?>
                        <div class="col-md-6">
                            <div class="kritik-alert">
                                <strong><?= $kritik['danisan_adi'] ?></strong>
                                <span class="badge bg-danger ms-2"><?= $kritik['kritik_durum'] ?></span>
                                <br>
                                <small class="text-muted">
                                    <?= $kritik['paket_adi'] ?> | 
                                    Kalan: <?= $kritik['kalan_seans'] ?> seans |
                                    Tel: <?= $kritik['telefon'] ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($kritik_durumlar) > 6): ?>
                    <div class="text-center mt-3">
                        <button class="btn btn-outline-danger" onclick="toggleKritikDurumlar()">
                            Tümünü Göster (<?= count($kritik_durumlar) - 6 ?> daha)
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filtreleme -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Danışan Ara</label>
                        <input type="text" name="arama" class="form-control" placeholder="Ad, soyad veya telefon" value="<?= htmlspecialchars($arama) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Paket Türü</label>
                        <select name="paket" class="form-select">
                            <option value="">Tümü</option>
                            <?php foreach ($paketler as $paket): ?>
                            <option value="<?= $paket['id'] ?>" <?= $filtre_paket == $paket['id'] ? 'selected' : '' ?>>
                                <?= $paket['ad'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Terapist</label>
                        <select name="terapist" class="form-select">
                            <option value="">Tümü</option>
                            <?php foreach ($terapistler as $terapist): ?>
                            <option value="<?= $terapist['id'] ?>" <?= $filtre_terapist == $terapist['id'] ? 'selected' : '' ?>>
                                <?= $terapist['ad'] . ' ' . $terapist['soyad'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Başlangıç</label>
                        <input type="date" name="tarih_baslangic" class="form-control" value="<?= $filtre_tarih_baslangic ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bitiş</label>
                        <input type="date" name="tarih_bitis" class="form-control" value="<?= $filtre_tarih_bitis ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Ana Rapor Tablosu -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Danışan Seans Raporu</h5>
                    <div>
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" 
                           class="btn btn-export me-2">
                            <i class="fas fa-file-excel me-2"></i>Excel'e Aktar
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Yazdır
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="seansRaporuTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Danışan</th>
                                    <th>İletişim</th>
                                    <th>Paket</th>
                                    <th>Seans Durumu</th>
                                    <th>Finansal</th>
                                    <th>Tarihler</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapor_verileri as $veri): ?>
                                <tr>
                                    <td>
                                        <strong><?= $veri['danisan_adi'] ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?= $veri['danisan_id'] ?></small>
                                    </td>
                                    <td>
                                        <div><?= $veri['telefon'] ?></div>
                                        <?php if ($veri['email']): ?>
                                        <small class="text-muted"><?= $veri['email'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $veri['toplam_paket_sayisi'] ?> Paket</span>
                                        <br>
                                        <small class="text-muted"><?= $veri['aktif_paket_sayisi'] ?> aktif</small>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small>Toplam: <?= $veri['toplam_seans'] ?></small>
                                            <small>Kalan: <strong><?= $veri['kalan_seans'] ?></strong></small>
                                        </div>
                                        <div class="progress progress-bar-seans">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?= $veri['toplam_seans'] > 0 ? ($veri['kullanilan_seans'] / $veri['toplam_seans']) * 100 : 0 ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted">Kullanılan: <?= $veri['kullanilan_seans'] ?></small>
                                    </td>
                                    <td>
                                        <div>Toplam: <strong><?= number_format($veri['toplam_tutar'], 0) ?>₺</strong></div>
                                        <div>Ödenen: <span class="text-success"><?= number_format($veri['toplam_odenen'], 0) ?>₺</span></div>
                                        <div>Kalan: <span class="text-danger"><?= number_format($veri['toplam_kalan_borc'], 0) ?>₺</span></div>
                                        <small class="text-muted">Ödeme: %<?= $veri['ortalama_odeme_yuzdesi'] ?></small>
                                    </td>
                                    <td>
                                        <?php if ($veri['son_satis_tarihi']): ?>
                                        <div>Son Satış:</div>
                                        <small><?= date('d.m.Y', strtotime($veri['son_satis_tarihi'])) ?></small>
                                        <?php endif; ?>
                                        <br>
                                        <?php if ($veri['son_randevu_tarihi']): ?>
                                        <div>Son Randevu:</div>
                                        <small><?= date('d.m.Y', strtotime($veri['son_randevu_tarihi'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical" role="group">
                                            <a href="danisan-seans-raporu.php?id=<?= $veri['danisan_id'] ?>" 
                                               class="btn btn-sm btn-primary mb-1">
                                                <i class="fas fa-eye"></i> Detay
                                            </a>
                                            <a href="room_schedule.php?danisan_id=<?= $veri['danisan_id'] ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-calendar-plus"></i> Randevu
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/customizer.php' ?>
<?php include 'partials/footer-scripts.php' ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // DataTable başlatma
    $('#seansRaporuTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json'
        },
        pageLength: 25,
        responsive: true,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: [6], orderable: false }
        ]
    });
});

function toggleKritikDurumlar() {
    // Tüm kritik durumları göster/gizle
    window.location.href = 'toplu-seans-raporu.php?show_all_kritik=1';
}

// Yazdırma stili
window.addEventListener('beforeprint', function() {
    document.querySelector('.filter-section').style.display = 'none';
    document.querySelector('.card-header .btn').style.display = 'none';
});

window.addEventListener('afterprint', function() {
    document.querySelector('.filter-section').style.display = 'block';
    document.querySelector('.card-header .btn').style.display = 'inline-block';
});
</script>
</body>
</html>