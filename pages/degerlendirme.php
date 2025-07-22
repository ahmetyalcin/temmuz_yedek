<?php
// Tarih filtreleri
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;

$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih = date('Y-m-t', strtotime($baslangic_tarih));

// Get therapists
try {
    $sql = "SELECT * FROM personel WHERE rol = 'terapist' AND aktif = 1 ORDER BY ad, soyad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $terapistler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Terapist listesi getirme hatası: " . $e->getMessage());
    $terapistler = [];
}

// Terapist bazında verileri getir
$sql_terapist_data = "SELECT 
    p.id as terapist_id,
    CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
    COUNT(DISTINCT r.id) as seans_sayisi,
    COUNT(DISTINCT DATE(r.randevu_tarihi)) as calisan_gun,
    COUNT(DISTINCT r.danisan_id) as danisan_sayisi,
    SUM(CASE WHEN r.evaluation_type IS NOT NULL THEN 1 ELSE 0 END) as degerlendirme_sayisi,
    SUM(CASE WHEN r.durum = 'iptal_edildi' THEN 1 ELSE 0 END) as iptal_sayisi
FROM personel p
LEFT JOIN randevular r ON r.personel_id = p.id 
    AND YEAR(r.randevu_tarihi) = :yil 
    AND MONTH(r.randevu_tarihi) = :ay
    AND r.aktif = 1 
WHERE p.rol = 'terapist' AND p.aktif = 1 " . 
($terapist_id ? "AND p.id = :terapist_id " : "") . "
GROUP BY p.id, p.ad, p.soyad
ORDER BY p.ad, p.soyad";

try {
    $stmt = $pdo->prepare($sql_terapist_data);
    $params = [':yil' => $yil, ':ay' => $ay];
    if ($terapist_id) {
        $params[':terapist_id'] = $terapist_id;
    }
    $stmt->execute($params);
    $terapist_verileri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Terapist verileri getirme hatası: " . $e->getMessage());
    $terapist_verileri = [];
}

// Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Başlık
    $sheet->setCellValue('A1', 'THERAVİTA FİZYOTERAPİST PERFORMANS DEĞERLENDİRME - ' . 
        strtoupper(getTurkishMonthName($ay)) . '/' . $yil);
    
    // Başlıklar
    $headers = [
        'A3' => 'FİZYOTERAPİST',
        'B3' => 'Çalışılan Gün',
        'C3' => 'Toplam Seans',
        'D3' => 'Danışan Sayısı',
        'E3' => 'Değerlendirme',
        'F3' => 'İptal Oranı',
        'G3' => 'Performans Puanı',
        'H3' => 'Değerlendirme Notu'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Veriler
    $row = 4;
    foreach ($terapist_verileri as $veri) {
        $iptal_orani = $veri['seans_sayisi'] > 0 ? 
            round(($veri['iptal_sayisi'] / $veri['seans_sayisi']) * 100, 2) : 0;
            
        $performans_puani = calculatePerformans(
            $veri['seans_sayisi'],
            $veri['calisan_gun'],
            $veri['danisan_sayisi'],
            $veri['degerlendirme_sayisi'],
            $iptal_orani
        );
        
        $sheet->setCellValue('A'.$row, $veri['terapist_adi']);
        $sheet->setCellValue('B'.$row, $veri['calisan_gun']);
        $sheet->setCellValue('C'.$row, $veri['seans_sayisi']);
        $sheet->setCellValue('D'.$row, $veri['danisan_sayisi']);
        $sheet->setCellValue('E'.$row, $veri['degerlendirme_sayisi']);
        $sheet->setCellValue('F'.$row, $iptal_orani . '%');
        $sheet->setCellValue('G'.$row, $performans_puani);
        $sheet->setCellValue('H'.$row, getDegerlendirmeNotu($performans_puani));
        
        $row++;
    }
    
    // Stil ayarları
    $sheet->getStyle('A1:H1')->getFont()->setBold(true);
    $sheet->getStyle('A3:H3')->getFont()->setBold(true);
    
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="degerlendirme_'.$yil.'_'.$ay.'.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
}

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

function calculatePerformans($seans_sayisi, $calisan_gun, $danisan_sayisi, $degerlendirme_sayisi, $iptal_orani) {
    // Baz puanlar
    $seans_puan = min(($seans_sayisi / 100) * 30, 30); // Max 30 puan
    $gun_puan = min(($calisan_gun / 22) * 20, 20); // Max 20 puan
    $danisan_puan = min(($danisan_sayisi / 30) * 20, 20); // Max 20 puan
    $degerlendirme_puan = min(($degerlendirme_sayisi / 20) * 20, 20); // Max 20 puan
    
    // İptal oranı düşürme
    $iptal_dusurme = $iptal_orani > 10 ? ($iptal_orani - 10) : 0;
    
    $toplam_puan = $seans_puan + $gun_puan + $danisan_puan + $degerlendirme_puan - $iptal_dusurme;
    
    return round(max($toplam_puan, 0), 2);
}

function getDegerlendirmeNotu($puan) {
    if ($puan >= 90) return 'Çok İyi';
    if ($puan >= 80) return 'İyi';
    if ($puan >= 70) return 'Orta';
    if ($puan >= 60) return 'Geliştirilmeli';
    return 'Yetersiz';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fizyoterapist Performans Değerlendirme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-wrapper {
            overflow-x: auto;
        }
        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
            font-size: 0.875rem;
        }
        .table td {
            font-size: 0.875rem;
        }
        .performance-card {
            border-radius: 0.5rem;
            padding: 1rem;
            height: 100%;
        }
        .performance-value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .performance-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .grade-A { background-color: #d1e7dd; }
        .grade-B { background-color: #fff3cd; }
        .grade-C { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Fizyoterapist Performans Değerlendirme</h1>
            <div class="btn-group">
                <a href="?page=degerlendirme&export=excel&ay=<?= $ay ?>&yil=<?= $yil ?>&terapist_id=<?= $terapist_id ?>" 
                   class="btn btn-success">
                    <i class="bi bi-file-excel me-1"></i> Excel'e Aktar
                </a>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="degerlendirme">
                    
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
                            <?php foreach($terapistler as $terapist): ?>
                                <option value="<?= $terapist['id'] ?>" 
                                        <?= $terapist_id == $terapist['id'] ? 'selected' : '' ?>>
                                    <?= $terapist['ad'] . ' ' . $terapist['soyad'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                        <a href="?page=degerlendirme" class="btn btn-secondary">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Performans Tablosu -->
        <div class="card">
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Fizyoterapist</th>
                                <th>Çalışılan Gün</th>
                                <th>Toplam Seans</th>
                                <th>Danışan Sayısı</th>
                                <th>Değerlendirme</th>
                                <th>İptal Oranı</th>
                                <th>Performans Puanı</th>
                                <th>Değerlendirme Notu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($terapist_verileri as $veri): 
                                $iptal_orani = $veri['seans_sayisi'] > 0 ? 
                                    round(($veri['iptal_sayisi'] / $veri['seans_sayisi']) * 100, 2) : 0;
                                    
                                $performans_puani = calculatePerformans(
                                    $veri['seans_sayisi'],
                                    $veri['calisan_gun'],
                                    $veri['danisan_sayisi'],
                                    $veri['degerlendirme_sayisi'],
                                    $iptal_orani
                                );
                                
                                $not = getDegerlendirmeNotu($performans_puani);
                                $not_class = $performans_puani >= 80 ? 'grade-A' : 
                                           ($performans_puani >= 70 ? 'grade-B' : 'grade-C');
                            ?>
                                <tr>
                                    <td><?= $veri['terapist_adi'] ?></td>
                                    <td class="text-center"><?= $veri['calisan_gun'] ?></td>
                                    <td class="text-center"><?= $veri['seans_sayisi'] ?></td>
                                    <td class="text-center"><?= $veri['danisan_sayisi'] ?></td>
                                    <td class="text-center"><?= $veri['degerlendirme_sayisi'] ?></td>
                                    <td class="text-center"><?= $iptal_orani ?>%</td>
                                    <td class="text-center"><?= $performans_puani ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $not_class ?>"><?= $not ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Değerlendirme Kriterleri -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Değerlendirme Kriterleri</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="performance-card bg-light">
                            <div class="performance-value">30%</div>
                            <div class="performance-label">Toplam Seans</div>
                            <small class="text-muted">100 seans = 30 puan</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="performance-card bg-light">
                            <div class="performance-value">20%</div>
                            <div class="performance-label">Çalışılan Gün</div>
                            <small class="text-muted">22 gün = 20 puan</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="performance-card bg-light">
                            <div class="performance-value">20%</div>
                            <div class="performance-label">Danışan Sayısı</div>
                            <small class="text-muted">30 danışan = 20 puan</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="performance-card bg-light">
                            <div class="performance-value">20%</div>
                            <div class="performance-label">Değerlendirme</div>
                            <small class="text-muted">20 değerlendirme = 20 puan</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Not Aralıkları:</h6>
                    <ul class="list-unstyled">
                        <li>90-100 puan: Çok İyi</li>
                        <li>80-89 puan: İyi</li>
                        <li>70-79 puan: Orta</li>
                        <li>60-69 puan: Geliştirilmeli</li>
                        <li>0-59 puan: Yetersiz</li>
                    </ul>
                    <p class="text-muted small">
                        * İptal oranı %10'un üzerinde olan her puan için toplam puandan 1 puan düşülür.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>