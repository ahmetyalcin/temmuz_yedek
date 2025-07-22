<?php

// Tarih filtreleri
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;

$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih = date('Y-m-t', strtotime($baslangic_tarih));

// Sabit parametreler
$TEMEL_MAAS = 23000;
$YEMEK_BEDELI = 4000;
$SEANS_UCRETI = 65;

// Get therapists
try {
    $sql = "SELECT id, CONCAT(ad, ' ', soyad) as ad_soyad 
            FROM personel 
            WHERE rol = 'terapist' AND aktif = 1 
            ORDER BY ad, soyad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $terapistler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Terapist listesi getirme hatası: " . $e->getMessage());
    $terapistler = [];
}

// Terapist bazında çalışılan gün ve seans sayılarını getir
$sql_terapist_data = "SELECT 
    p.id as terapist_id,
    CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
    COUNT(DISTINCT DATE(r.randevu_tarihi)) as calisan_gun,
    COUNT(DISTINCT r.id) as seans_sayisi
FROM personel p
LEFT JOIN randevular r ON r.personel_id = p.id 
    AND YEAR(r.randevu_tarihi) = :yil 
    AND MONTH(r.randevu_tarihi) = :ay
    AND r.aktif = 1 
    AND r.durum != 'iptal_edildi'
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

// Toplam yenileme satışı hesapla
$sql_toplam_satis = "SELECT COALESCE(SUM(s.toplam_tutar), 0) as toplam_satis
                     FROM randevular r
                     JOIN satislar s ON s.id = r.satis_id
                     WHERE YEAR(r.randevu_tarihi) = :yil 
                     AND MONTH(r.randevu_tarihi) = :ay
                     AND r.aktif = 1
                     AND r.durum != 'iptal_edildi'";

try {
    $stmt = $pdo->prepare($sql_toplam_satis);
    $stmt->execute([':yil' => $yil, ':ay' => $ay]);
    $toplam_yenileme_satisi = $stmt->fetch(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    error_log("Toplam satış hesaplama hatası: " . $e->getMessage());
    $toplam_yenileme_satisi = 0;
}

// Ortalama seans hesapla
$toplam_seans = 0;
$terapist_sayisi = count($terapist_verileri);
foreach ($terapist_verileri as $veri) {
    $toplam_seans += $veri['seans_sayisi'];
}
$ortalama_seans = $terapist_sayisi > 0 ? $toplam_seans / $terapist_sayisi : 0;

// Satış kategorileri ve katsayıları
$satis_kategorileri = [
    ['min' => 0, 'max' => 15000, 'katsayi' => 1.000],
    ['min' => 15000, 'max' => 30000, 'katsayi' => 1.025],
    ['min' => 30000, 'max' => 45000, 'katsayi' => 1.055],
    ['min' => 45000, 'max' => 60000, 'katsayi' => 1.115],
    ['min' => 60000, 'max' => 75000, 'katsayi' => 1.185],
    ['min' => 75000, 'max' => 100000, 'katsayi' => 1.275],
    ['min' => 100000, 'max' => 125000, 'katsayi' => 1.395],
    ['min' => 125000, 'max' => 150000, 'katsayi' => 1.545],
    ['min' => 150000, 'max' => 175000, 'katsayi' => 1.745],
    ['min' => 175000, 'max' => PHP_FLOAT_MAX, 'katsayi' => 1.975]
];

// Para formatı
function formatMoney($amount) {
    return '₺' . number_format($amount, 2, ',', '.');
}

// Türkçe ay isimleri
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

// Excel export için veri hazırlama
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Başlık
    $sheet->setCellValue('A1', 'THERAVİTA FİZYOTERAPİST ÜCRET MODÜLÜ - ' . 
        strtoupper(getTurkishMonthName($ay)) . '/' . $yil);
    
    // Parametreler
    $sheet->setCellValue('A2', 'Toplam Yenileme Satışı');
    $sheet->setCellValue('B2', $toplam_yenileme_satisi);
    $sheet->setCellValue('E2', 'Ortalama Seans');
    $sheet->setCellValue('F2', number_format($ortalama_seans, 2));
    $sheet->setCellValue('H2', 'Temel Maaş');
    $sheet->setCellValue('I2', $TEMEL_MAAS);
    $sheet->setCellValue('K2', 'Yemek');
    $sheet->setCellValue('L2', $YEMEK_BEDELI);
    
    // Başlıklar
    $headers = [
        'A4' => 'FİZYOTERAPİST',
        'B4' => 'Çalışılan Gün',
        'C4' => 'Yenileme Payı',
        'D4' => 'Bireysel Satış',
        'E4' => 'Toplam',
        'F4' => 'Seans',
        'G4' => 'Yıl',
        'H4' => 'Bireysel Perf.',
        'I4' => 'Satış Perf.',
        'J4' => 'Seans Perf.',
        'K4' => 'Prim',
        'L4' => 'Net Ele Geçen',
        'M4' => 'Yemek Dahil, Net Kazanç'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Veriler
    $row = 5;
    foreach ($terapist_verileri as $veri) {
        // Yenileme payı hesapla (Çalışılan Gün/26*Toplam Yenileme Satışı/10)
        $yenileme_payi = ($veri['calisan_gun'] / 26) * ($toplam_yenileme_satisi / 10);
        
        // Bireysel satış (elle girilecek - şimdilik 0)
        $bireysel_satis = 0;
        
        // Toplam = Yenileme Payı + Bireysel Satış
        $toplam = $yenileme_payi + $bireysel_satis;
        
        // Prim hesapla (Seans*65*Yıl*Bireysel Perf.*Satış Perf.*Seans Perf.)
        $seans_perf = $veri['seans_sayisi'] >= $ortalama_seans ? 1.05 : 1.00;
        $bireysel_perf = 1.07; // Varsayılan
        $satis_perf = 1.00; // Varsayılan
        
        $prim = $veri['seans_sayisi'] * $SEANS_UCRETI * $yil * 
                $bireysel_perf * $satis_perf * $seans_perf;
        
        // Net ele geçen
        $net_ele_gecen = $prim + $TEMEL_MAAS;
        
        // Yemek dahil net kazanç
        $yemek_dahil_net = $net_ele_gecen + $YEMEK_BEDELI;
        
        $sheet->setCellValue('A'.$row, $veri['terapist_adi']);
        $sheet->setCellValue('B'.$row, $veri['calisan_gun']);
        $sheet->setCellValue('C'.$row, $yenileme_payi);
        $sheet->setCellValue('D'.$row, $bireysel_satis);
        $sheet->setCellValue('E'.$row, $toplam);
        $sheet->setCellValue('F'.$row, $veri['seans_sayisi']);
        $sheet->setCellValue('G'.$row, $yil);
        $sheet->setCellValue('H'.$row, $bireysel_perf);
        $sheet->setCellValue('I'.$row, $satis_perf);
        $sheet->setCellValue('J'.$row, $seans_perf);
        $sheet->setCellValue('K'.$row, $prim);
        $sheet->setCellValue('L'.$row, $net_ele_gecen);
        $sheet->setCellValue('M'.$row, $yemek_dahil_net);
        
        $row++;
    }
    
    // Toplam satırı
    $last_row = $row - 1;
    $sheet->setCellValue('A'.$row, 'Toplam');
    $sheet->setCellValue('K'.$row, '=SUM(K5:K'.$last_row.')');
    $sheet->setCellValue('L'.$row, '=SUM(L5:L'.$last_row.')');
    $sheet->setCellValue('M'.$row, '=SUM(M5:M'.$last_row.')');
    
    // Stil ayarları
    $sheet->getStyle('A1:M1')->getFont()->setBold(true);
    $sheet->getStyle('A4:M4')->getFont()->setBold(true);
    $sheet->getStyle('A'.$row.':M'.$row)->getFont()->setBold(true);
    
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="ucret_'.$yil.'_'.$ay.'.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fizyoterapist Ücret Modülü</title>

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
        .parameters {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .parameter-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .parameter-label {
            font-weight: 500;
            margin-right: 1rem;
            min-width: 200px;
        }
        .parameter-value {
            font-weight: 600;
            color: #2563eb;
        }
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .editable {
            background-color: #fff3cd;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Fizyoterapist Ücret Modülü</h1>
            <div class="btn-group">
                <a href="?page=ucret&export=excel&ay=<?= $ay ?>&yil=<?= $yil ?>&terapist_id=<?= $terapist_id ?>" 
                   class="btn btn-success">
                    <i class="bi bi-file-excel me-1"></i> Excel'e Aktar
                </a>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="ucret">
                    
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
                                    <?= $terapist['ad_soyad'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                        <a href="?page=ucret" class="btn btn-secondary">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Parametreler -->
        <div class="parameters">
            <div class="row">
                <div class="col-md-3">
                    <div class="parameter-item">
                        <span class="parameter-label">Toplam Yenileme Satışı:</span>
                        <span class="parameter-value"><?= formatMoney($toplam_yenileme_satisi) ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="parameter-item">
                        <span class="parameter-label">Ortalama Seans:</span>
                        <span class="parameter-value"><?= number_format($ortalama_seans, 2) ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="parameter-item">
                        <span class="parameter-label">Temel Maaş:</span>
                        <span class="parameter-value"><?= formatMoney($TEMEL_MAAS) ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="parameter-item">
                        <span class="parameter-label">Yemek Bedeli:</span>
                        <span class="parameter-value"><?= formatMoney($YEMEK_BEDELI) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablo -->
        <div class="card">
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Fizyoterapist</th>
                                <th>Çalışılan Gün</th>
                                <th>Yenileme Payı</th>
                                <th>Bireysel Satış</th>
                                <th>Toplam</th>
                                <th>Seans</th>
                                <th>Yıl</th>
                                <th>Bireysel Perf.</th>
                                <th>Satış Perf.</th>
                                <th>Seans Perf.</th>
                                <th>Prim</th>
                                <th>Net Ele Geçen</th>
                                <th>Yemek Dahil, Net Kazanç</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $toplam_prim = 0;
                            $toplam_net = 0;
                            $toplam_yemekli = 0;
                            
                            foreach ($terapist_verileri as $veri):
                                // Yenileme payı hesapla (Çalışılan Gün/26*Toplam Yenileme Satışı/10)
                                $yenileme_payi = ($veri['calisan_gun'] / 26) * ($toplam_yenileme_satisi / 10);
                                
                                // Bireysel satış (elle girilecek - şimdilik 0)
                                $bireysel_satis = 0;
                                
                                // Toplam = Yenileme Payı + Bireysel Satış
                                $toplam = $yenileme_payi + $bireysel_satis;
                                
                                // Prim hesapla (Seans*65*Yıl*Bireysel Perf.*Satış Perf.*Seans Perf.)
                                $seans_perf = $veri['seans_sayisi'] >= $ortalama_seans ? 1.05 : 1.00;
                                $bireysel_perf = 1.07; // Varsayılan
                                $satis_perf = 1.00; // Varsayılan
                                
                                $prim = $veri['seans_sayisi'] * $SEANS_UCRETI * $yil * 
                                        $bireysel_perf * $satis_perf * $seans_perf;
                                
                                // Net ele geçen
                                $net_ele_gecen = $prim + $TEMEL_MAAS;
                                
                                // Yemek dahil net kazanç
                                $yemek_dahil_net = $net_ele_gecen + $YEMEK_BEDELI;
                                
                                // Toplamlara ekle
                                $toplam_prim += $prim;
                                $toplam_net += $net_ele_gecen;
                                $toplam_yemekli += $yemek_dahil_net;
                            ?>
                                <tr>
                                    <td><?= $veri['terapist_adi'] ?></td>
                                    <td class="text-center"><?= $veri['calisan_gun'] ?></td>
                                    <td class="text-end"><?= formatMoney($yenileme_payi) ?></td>
                                    <td class="text-end editable" data-id="<?= $veri['terapist_id'] ?>">
                                        <?= formatMoney($bireysel_satis) ?>
                                    </td>
                                    <td class="text-end"><?= formatMoney($toplam) ?></td>
                                    <td class="text-center"><?= $veri['seans_sayisi'] ?></td>
                                    <td class="text-center"><?= $yil ?></td>
                                    <td class="text-center editable" data-id="<?= $veri['terapist_id'] ?>">
                                        <?= number_format($bireysel_perf, 3) ?>
                                    </td>
                                    <td class="text-center"><?= number_format($satis_perf, 3) ?></td>
                                    <td class="text-center"><?= number_format($seans_perf, 2) ?></td>
                                    <td class="text-end"><?= formatMoney($prim) ?></td>
                                    <td class="text-end"><?= formatMoney($net_ele_gecen) ?></td>
                                    <td class="text-end"><?= formatMoney($yemek_dahil_net) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Toplam Satırı -->
                            <tr class="total-row">
                                <td colspan="10">Toplam</td>
                                <td class="text-end"><?= formatMoney($toplam_prim) ?></td>
                                <td class="text-end"><?= formatMoney($toplam_net) ?></td>
                                <td class="text-end"><?= formatMoney($toplam_yemekli) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Satış Kategorileri Tablosu -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Satış Performans Kategorileri</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Satış Aralığı</th>
                                <th>Katsayı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($satis_kategorileri as $kategori): ?>
                                <tr>
                                    <td>
                                        <?= formatMoney($kategori['min']) ?> - 
                                        <?= $kategori['max'] == PHP_FLOAT_MAX ? 'üstü' : formatMoney($kategori['max']) ?>
                                    </td>
                                    <td><?= number_format($kategori['katsayi'], 3) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Düzenlenebilir alanlar için
        const editableCells = document.querySelectorAll('.editable');
        editableCells.forEach(cell => {
            cell.addEventListener('click', function() {
                const currentValue = this.textContent.trim().replace('₺', '').replace(/\./g, '').replace(',', '.');
                const newValue = prompt('Yeni değer:', currentValue);
                
                if (newValue !== null) {
                    // TODO: AJAX ile değeri kaydet
                    this.textContent = formatMoney(parseFloat(newValue));
                    calculateTotals(); // Toplamları güncelle
                }
            });
        });

        function formatMoney(amount) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY',
                minimumFractionDigits: 2
            }).format(amount);
        }

        function calculateTotals() {
            // TODO: Tüm hesaplamaları yeniden yap
        }
    });
    </script>
</body>
</html>