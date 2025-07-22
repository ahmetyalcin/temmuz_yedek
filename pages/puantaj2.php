<?php
require_once 'config.php';

// Tarih filtreleri
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : null;

$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih = date('Y-m-t', strtotime($baslangic_tarih));

// Get therapists
try {
    $sql = "SELECT * FROM terapistler WHERE aktif = 1 ORDER BY ad_soyad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $terapistler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Terapist listesi getirme hatası: " . $e->getMessage());
    $terapistler = [];
}

// Get room types
try {
    $sql = "SELECT DISTINCT type FROM rooms WHERE aktif = 1 ORDER BY type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    error_log("Oda tipleri getirme hatası: " . $e->getMessage());
    $room_types = [];
}

// Build the appointments query
$sql = "SELECT 
            DATE(r.randevu_tarihi) as tarih,
            p.id as terapist_id,
            p.ad_soyad as terapist_adi,
            rm.type as oda_tipi,
            COUNT(*) as seans_sayisi,
            GROUP_CONCAT(DISTINCT d.ad_soyad) as danisanlar
        FROM randevular r
        JOIN personel p ON p.id = r.personel_id
        JOIN rooms rm ON rm.id = r.room_id
        JOIN danisanlar d ON d.id = r.danisan_id
        WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
        AND r.aktif = 1 AND r.durum != 'iptal_edildi'";

if ($terapist_id) {
    $sql .= " AND p.id = :terapist_id";
}

if ($room_type) {
    $sql .= " AND rm.type = :room_type";
}

$sql .= " GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
          ORDER BY tarih, terapist_adi";

try {
    $stmt = $pdo->prepare($sql);
    $params = [':baslangic' => $baslangic_tarih, ':bitis' => $bitis_tarih];
    
    if ($terapist_id) {
        $params[':terapist_id'] = $terapist_id;
    }
    if ($room_type) {
        $params[':room_type'] = $room_type;
    }
    
    $stmt->execute($params);
    $puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Puantaj verileri getirme hatası: " . $e->getMessage());
    $puantaj_kayitlari = [];
}

// Excel export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    require 'vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Headers
    $sheet->setCellValue('A1', 'Tarih');
    $col = 'B';
    foreach($terapistler as $terapist) {
        foreach($room_types as $type) {
            $sheet->setCellValue($col.'1', $terapist['ad_soyad'] . "\n" . $type);
            $col++;
        }
    }
    
    // Data
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
    
    $writer = new Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="puantaj_'.$yil.'_'.$ay.'.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
}

// Helper function for Turkish month names
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fizyoterapi Puantaj Sistemi</title>
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
        .ara-toplam {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .genel-toplam {
            background-color: #dee2e6;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Fizyoterapi Puantaj Tablosu</h1>
            <div class="btn-group">
                <a href="?export=excel&ay=<?= $ay ?>&yil=<?= $yil ?>&terapist_id=<?= $terapist_id ?>&room_type=<?= urlencode($room_type) ?>" 
                   class="btn btn-success">
                    <i class="bi bi-file-excel me-1"></i> Excel'e Aktar
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
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
                        <select name="room_type" class="form-select">
                            <option value="">Tüm Oda Tipleri</option>
                            <?php foreach($room_types as $type): ?>
                                <option value="<?= $type ?>" 
                                        <?= $room_type == $type ? 'selected' : '' ?>>
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                        <a href="?" class="btn btn-secondary">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <?php foreach($terapistler as $terapist): ?>
                                    <?php foreach($room_types as $type): ?>
                                        <th class="text-center">
                                            <?= $terapist['ad_soyad'] ?><br>
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
                                            <td class="text-center">
                                                <?php
                                                $seans_sayisi = 0;
                                                foreach($puantaj_kayitlari as $kayit) {
                                                    if($kayit['tarih'] == $tarih && 
                                                       $kayit['terapist_id'] == $terapist['id'] && 
                                                       $kayit['oda_tipi'] == $type) {
                                                        $seans_sayisi = $kayit['seans_sayisi'];
                                                        break;
                                                    }
                                                }
                                                if ($seans_sayisi > 0) {
                                                    echo '<span class="badge bg-primary">' . $seans_sayisi . '</span>';
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>