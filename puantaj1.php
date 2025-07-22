<?php

session_start();
require_once 'functions.php';

$danisanlar = getDanisanlar();
$randevular = getRandevular();
$seansTurleri = getSeansTurleri();
$uyelikTurleri = getUyelikTurleri();
$hizmetPaketleri = getHizmetPaketleri();
$sponsorluklar = getSponsorluklar();
$terapistler = getTerapistler();
$aktif_terapistler = getTerapistler(true);


// Tarih filtreleri
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;
$view_type = isset($_GET['view']) ? $_GET['view'] : 'monthly';

$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih = date('Y-m-t', strtotime($baslangic_tarih));

if ($view_type === 'yearly') {
    $baslangic_tarih = "$yil-01-01";
    $bitis_tarih = "$yil-12-31";
}

// Oda tiplerini getir - terapist seçiliyse sadece onun çalıştığı odaları getir
// Oda tiplerini getir - terapist seçiliyse sadece o tarih aralığında çalıştığı odaları getir
// Oda tiplerini getir - seçilen tarih aralığında aktif olan odaları getir
$sql_room_types = "SELECT DISTINCT rm.type 
                   FROM rooms rm
                   JOIN randevular r ON r.room_id = rm.id
                   WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
                   AND r.aktif = 1 
                   AND r.durum != 'iptal_edildi'";

if ($terapist_id) {
    $sql_room_types .= " AND r.personel_id = :terapist_id";
}

$sql_room_types .= " ORDER BY rm.type";

try {
    $stmt = $pdo->prepare($sql_room_types);
    $params = [
        ':baslangic' => $baslangic_tarih,
        ':bitis' => $bitis_tarih
    ];
    
    if ($terapist_id) {
        $params[':terapist_id'] = $terapist_id;
    }
    
    $stmt->execute($params);
    $room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    error_log("Oda tipleri getirme hatası: " . $e->getMessage());
    $room_types = [];
}




// Randevulardan puantaj verilerini çek
// Randevulardan puantaj verilerini çek
// Randevulardan puantaj verilerini çek
$sql = "SELECT 
            DATE(r.randevu_tarihi) as tarih,
            p.id as terapist_id,
            CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
            rm.type as oda_tipi,
            COUNT(DISTINCT r.id) as seans_sayisi,
            GROUP_CONCAT(DISTINCT d.ad, ' ', d.soyad) as danisanlar
        FROM randevular r
        JOIN personel p ON p.id = r.personel_id
        JOIN rooms rm ON rm.id = r.room_id
        JOIN danisanlar d ON d.id = r.danisan_id
        WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
        AND r.aktif = 1 AND r.durum != 'iptal_edildi'";

if ($terapist_id) {
    $sql .= " AND p.id = :terapist_id";
}

$sql .= " GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
          ORDER BY tarih, terapist_adi";





          
try {
    $stmt = $pdo->prepare($sql);
    $params = [':baslangic' => $baslangic_tarih, ':bitis' => $bitis_tarih];
    if ($terapist_id) {
        $params[':terapist_id'] = $terapist_id;
    }
    $stmt->execute($params);
    $puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Puantaj verileri getirme hatası: " . $e->getMessage());
    $puantaj_kayitlari = [];
}

// Excel export için veri hazırlama
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    require_once 'vendor/autoload.php';
    
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Başlıklar
    $sheet->setCellValue('A1', 'Tarih');
    $col = 'B';
    foreach($terapistler as $terapist) {
        foreach($room_types as $type) {
            $sheet->setCellValue($col.'1', $terapist['ad'].' '.$terapist['soyad']."\n".$type);
            $col++;
        }
    }
    
    // Veriler
    $row = 2;
    $current_date = new DateTime($baslangic_tarih);
    $end_date = new DateTime($bitis_tarih);
    
    while($current_date <= $end_date) {
        $tarih = $current_date->format('Y-m-d');
        $sheet->setCellValue('A'.$row, $current_date->format('d.m.Y'));
        
        $col = 'B';
        foreach($terapistler as $terapist) {
            foreach($room_types as $type) {
                $seans_sayisi = 0;
                foreach($puantaj_kayitlari as $kayit) {
                    if($kayit['tarih'] == $tarih && 
                       $kayit['terapist_id'] == $terapist['id'] && 
                       $kayit['oda_tipi'] == $type) {
                        $seans_sayisi = $kayit['seans_sayisi'];
                        break;
                    }
                }
                $sheet->setCellValue($col.$row, $seans_sayisi);
                $col++;
            }
        }
        $row++;
        $current_date->modify('+1 day');
    }
    
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="puantaj_'.$yil.'_'.$ay.'.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

// Ara toplam ve genel toplam hesapla
$ara_toplam = [];
$genel_toplam = [];

foreach ($puantaj_kayitlari as $kayit) {
    $key = $kayit['terapist_id'] . '_' . strtolower($kayit['oda_tipi']);
    
    if (!isset($ara_toplam[$key])) {
        $ara_toplam[$key] = [
            'seans' => 0,
            'danisanlar' => []
        ];
    }
    $ara_toplam[$key]['seans'] += $kayit['seans_sayisi'];
    $ara_toplam[$key]['danisanlar'] = array_merge(
        $ara_toplam[$key]['danisanlar'],
        explode(',', $kayit['danisanlar'])
    );
    
    if (!isset($genel_toplam[$key])) {
        $genel_toplam[$key] = [
            'seans' => 0,
            'danisanlar' => []
        ];
    }
    $genel_toplam[$key]['seans'] += $kayit['seans_sayisi'];
    $genel_toplam[$key]['danisanlar'] = array_merge(
        $genel_toplam[$key]['danisanlar'],
        explode(',', $kayit['danisanlar'])
    );
}

// Get month names in Turkish
function getTurkishMonthName($month) {
    $months = [
        1 => 'Ocak',
        2 => 'Şubat',
        3 => 'Mart',
        4 => 'Nisan',
        5 => 'Mayıs',
        6 => 'Haziran',
        7 => 'Temmuz',
        8 => 'Ağustos',
        9 => 'Eylül',
        10 => 'Ekim',
        11 => 'Kasım',
        12 => 'Aralık'
    ];
    return $months[(int)$month];
}
?>



<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Satışlar Listele";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
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
                $subtitle = "Gelirler";
                $title = "Fizyoterapi Puantaj Tablosu";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">


<div class="container-fluid">
    <!-- Başlık ve Butonlar -->
    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-0"></h4>
        </div>
        <div class="col-auto">
            <div class="btn-group">


            <div class="btn-group">
    <a href="?page=export_puantaj&ay=<?= $ay ?>&yil=<?= $yil ?>&terapist_id=<?= $terapist_id ?>&export=xlsx" 
       class="btn btn-success">
        <i class="mdi mdi-file-excel me-1"></i> Excel İndir
    </a>
    <a href="?page=export_puantaj&ay=<?= $ay ?>&yil=<?= $yil ?>&terapist_id=<?= $terapist_id ?>&export=csv" 
       class="btn btn-primary">
        <i class="mdi mdi-file-delimited me-1"></i> CSV İndir
    </a>
</div>




            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3" id="filterForm">
                <input type="hidden" name="page" value="puantaj">
                
                <div class="col-auto">
                    <select name="view" class="form-select" onchange="this.form.submit()">
                        <option value="monthly" <?= $view_type === 'monthly' ? 'selected' : '' ?>>Aylık Görünüm</option>
                        <option value="yearly" <?= $view_type === 'yearly' ? 'selected' : '' ?>>Yıllık Görünüm</option>
                    </select>
                </div>

                <?php if ($view_type === 'monthly'): ?>
                <div class="col-auto">
                    <select name="ay" class="form-select">
                        <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" 
                                    <?= $ay == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                                <?= getTurkishMonthName($i) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-auto">
                    <select name="yil" class="form-select">
                        <?php for($i = date('Y')-1; $i <= date('Y')+1; $i++): ?>
                            <option value="<?= $i ?>" <?= $yil == $i ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-auto">
                    <select name="terapist_id" class="form-select">
                        <option value="">Tüm Terapistler</option>
                        <?php foreach($terapistler as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $terapist_id == $t['id'] ? 'selected' : '' ?>>
                                <?= $t['ad'] . ' ' . $t['soyad'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filtrele</button>
                    <a href="?page=puantaj" class="btn btn-secondary">Sıfırla</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Puantaj Tablosu -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-hover" id="puantajTable">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <?php foreach($terapistler as $terapist): ?>
                                <?php foreach($room_types as $type): ?>
                                    <th class="text-center" data-terapist="<?= $terapist['id'] ?>">
                                        <?= $terapist['ad'] . ' ' . $terapist['soyad'] ?><br>
                                        <small class="text-muted"><?= $type ?></small>
                                    </th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_date = new DateTime($baslangic_tarih);
                        $end_date = new DateTime($bitis_tarih);
                        
                        while($current_date <= $end_date): 
                            $tarih = $current_date->format('Y-m-d');
                        ?>
                            <tr>
                                <td><?= $current_date->format('d.m.Y') ?></td>
                                <?php foreach($terapistler as $terapist): ?>
                                    <?php foreach($room_types as $type): ?>
                                        <td class="text-center position-relative" 
                                            data-terapist="<?= $terapist['id'] ?>"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top">
                                            <?php
                                            $seans_sayisi = 0;
                                            $danisanlar = [];
                                            foreach($puantaj_kayitlari as $kayit) {
                                                if($kayit['tarih'] == $tarih && 
                                                   $kayit['terapist_id'] == $terapist['id'] && 
                                                   $kayit['oda_tipi'] == $type) {
                                                    $seans_sayisi = $kayit['seans_sayisi'];
                                                    $danisanlar = explode(',', $kayit['danisanlar']);
                                                    break;
                                                }
                                            }
                                            if ($seans_sayisi > 0) {
                                                echo '<span class="badge bg-primary" 
                                                      title="' . implode(', ', array_unique($danisanlar)) . '">' . 
                                                      $seans_sayisi . '</span>';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php 
                            $current_date->modify('+1 day');
                        endwhile; 
                        ?>
                        
                        <!-- Ara Toplam -->
                        <tr class="table-info">
                            <td><strong><?= $view_type === 'monthly' ? 'Aylık' : 'Dönem' ?> Toplam</strong></td>
                            <?php foreach($terapistler as $terapist): ?>
                                <?php foreach($room_types as $type): 
                                    $key = $terapist['id'] . '_' . strtolower($type);
                                ?>
                                    <td class="text-center" data-terapist="<?= $terapist['id'] ?>">
                                        <strong><?= isset($ara_toplam[$key]) ? $ara_toplam[$key]['seans'] : 0 ?></strong>
                                    </td>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                        
                        <!-- Genel Toplam -->
                        <tr class="table-primary">
                            <td><strong>Yıllık Toplam</strong></td>
                            <?php foreach($terapistler as $terapist): ?>
                                <?php foreach($room_types as $type): 
                                    $key = $terapist['id'] . '_' . strtolower($type);
                                ?>
                                    <td class="text-center" data-terapist="<?= $terapist['id'] ?>">
                                        <strong><?= isset($genel_toplam[$key]) ? $genel_toplam[$key]['seans'] : 0 ?></strong>
                                    </td>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- İstatistik Modalı -->
<div class="modal fade" id="istatistikModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Terapist İstatistikleri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php foreach($terapistler as $terapist): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $terapist['ad'] . ' ' . $terapist['soyad'] ?></h5>
                                    <div class="mt-3">
                                        <?php foreach($room_types as $type): 
                                            $key = $terapist['id'] . '_' . strtolower($type);
                                            $aylik = isset($ara_toplam[$key]) ? $ara_toplam[$key]['seans'] : 0;
                                            $yillik = isset($genel_toplam[$key]) ? $genel_toplam[$key]['seans'] : 0;
                                            $danisan_sayisi = isset($ara_toplam[$key]) ? 
                                                count(array_unique($ara_toplam[$key]['danisanlar'])) : 0;
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span><?= $type ?>:</span>
                                                <div class="text-end">
                                                    <div>
                                                        <span class="badge bg-primary"><?= $aylik ?></span>
                                                        <small class="text-muted">Aylık</small>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-success"><?= $yillik ?></span>
                                                        <small class="text-muted">Yıllık</small>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-info"><?= $danisan_sayisi ?></span>
                                                        <small class="text-muted">Danışan</small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Terapist filtresi
    const terapistSelect = document.querySelector('select[name="terapist_id"]');
    if (terapistSelect) {
        terapistSelect.addEventListener('change', function() {
            const selectedId = this.value;
            const allColumns = document.querySelectorAll('[data-terapist]');
            
            allColumns.forEach(col => {
                if (!selectedId || col.dataset.terapist === selectedId) {
                    col.style.display = '';
                } else {
                    col.style.display = 'none';
                }
            });
        });

        // Sayfa yüklendiğinde mevcut seçimi uygula
        if (terapistSelect.value) {
            const event = new Event('change');
            terapistSelect.dispatchEvent(event);
        }
    }
});
</script>
                            

                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/customizer.php' ?>

    <?php include 'partials/footer-scripts.php' ?>


</body>

</html>