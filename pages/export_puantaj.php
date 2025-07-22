<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Get parameters
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;
$export_type = isset($_GET['export']) ? $_GET['export'] : 'xlsx';

// Prevent output buffering
if (ob_get_level()) ob_end_clean();

try {
    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setCellValue('A1', 'THERAVİTA FİZYOTERAPİ PUANTAJ RAPORU - ' . getTurkishMonthName($ay) . ' ' . $yil);
    $sheet->mergeCells('A1:G1');

    // Style the title
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 14
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);

    // Get room types
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

    $stmt = $pdo->prepare($sql_room_types);
    $params = [
        ':baslangic' => "$yil-$ay-01",
        ':bitis'     => date('Y-m-t', strtotime("$yil-$ay-01"))
    ];
    
    if ($terapist_id) {
        $params[':terapist_id'] = $terapist_id;
    }
    
    $stmt->execute($params);
    $room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get therapists
    $sql_terapists = "SELECT * FROM personel WHERE rol = 'terapist' AND aktif = 1 ORDER BY ad, soyad";
    if ($terapist_id) {
        $sql_terapists .= " AND id = :terapist_id";
    }
    
    $stmt = $pdo->prepare($sql_terapists);
    if ($terapist_id) {
        $stmt->bindParam(':terapist_id', $terapist_id);
    }
    $stmt->execute();
    $terapistler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Headers - Row 3
    $sheet->setCellValue('A3', 'Tarih');
    $currentCol = 'B';
    foreach ($terapistler as $terapist) {
        foreach ($room_types as $type) {
            $sheet->setCellValue($currentCol . '3', $terapist['ad'] . ' ' . $terapist['soyad'] . "\n" . $type);
            $sheet->getStyle($currentCol . '3')->getAlignment()->setWrapText(true);
            $currentCol++;
        }
    }

    // Get the last column letter
    $currentIndex = Coordinate::columnIndexFromString($currentCol);
    $lastIndex    = $currentIndex - 1;
    $lastCol      = Coordinate::stringFromColumnIndex($lastIndex);

    // Style headers
    $headerRange = 'A3:' . $lastCol . '3';
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType'  => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E9ECEF']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical'   => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ]
    ]);

    // Get appointment data
    $sql = "SELECT 
                DATE(r.randevu_tarihi) as tarih,
                p.id as terapist_id,
                rm.type as oda_tipi,
                COUNT(DISTINCT r.id) as seans_sayisi
            FROM randevular r
            JOIN personel p ON p.id = r.personel_id
            JOIN rooms rm ON rm.id = r.room_id
            WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
            AND r.aktif = 1 AND r.durum != 'iptal_edildi'";

    if ($terapist_id) {
        $sql .= " AND p.id = :terapist_id";
    }

    $sql .= " GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
              ORDER BY tarih, p.id, rm.type";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data rows
    $row = 4;
    $current_date = new DateTime("$yil-$ay-01");
    $end_date     = new DateTime(date('Y-m-t', strtotime("$yil-$ay-01")));

    while ($current_date <= $end_date) {
        $tarih = $current_date->format('Y-m-d');
        $sheet->setCellValue('A' . $row, $current_date->format('d.m.Y'));

        $currentCol = 'B';
        foreach ($terapistler as $terapist) {
            foreach ($room_types as $type) {
                $seans_sayisi = 0;
                foreach ($puantaj_kayitlari as $kayit) {
                    if ($kayit['tarih'] == $tarih && 
                        $kayit['terapist_id'] == $terapist['id'] && 
                        $kayit['oda_tipi'] == $type) {
                        $seans_sayisi = $kayit['seans_sayisi'];
                        break;
                    }
                }
                $sheet->setCellValue($currentCol . $row, $seans_sayisi);
                $sheet->getStyle($currentCol . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentCol++;
            }
        }
        $row++;
        $current_date->modify('+1 day');
    }

    // Auto-size columns
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set print area
    $sheet->getPageSetup()->setPrintArea('A1:' . $lastCol . ($row - 1));

    // Set headers and output file
    if ($export_type === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="puantaj_' . $yil . '_' . $ay . '.csv"');
        $writer = new Csv($spreadsheet);
    } else {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="puantaj_' . $yil . '_' . $ay . '.xlsx"');
        $writer = new Xlsx($spreadsheet);
    }

    // Additional headers for better browser compatibility
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    // Output the file
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header("Location: ?page=puantaj&error=" . urlencode("Export failed: " . $e->getMessage()));
    exit;
}

function getTurkishMonthName($month) {
    $months = [
        1  => 'Ocak',  2 => 'Şubat',  3 => 'Mart',   4 => 'Nisan',
        5  => 'Mayıs',  6 => 'Haziran',7 => 'Temmuz', 8 => 'Ağustos',
        9  => 'Eylül', 10 => 'Ekim',  11 => 'Kasım', 12 => 'Aralık'
    ];
    return $months[(int)$month];
}
