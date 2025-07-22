<?php
require_once __DIR__ . '/../functions.php';   // DB bağlantısı, helper’lar vs.
require_once __DIR__ . '/../vendor/PhpSpreadsheet/Autoloader.php'; 
// — eğer manuel autoload kullandıysanız, ya da Composer ile vendor/autoload.php gerekiyorsa:
// require_once __DIR__ . '/../vendor/autoload.php';

// 1) Kullanıcının yetkisini kontrol etmek isterseniz (session kontrol vs.)
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Yetkisiz erişim';
    exit;
}

// 2) GET parametrelerini alın (ay, yil, terapist_id vb.)
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;
$view_type = isset($_GET['view']) ? $_GET['view'] : 'monthly';

// 3) Tarih aralıklarını hesaplayın
$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih = date('Y-m-t', strtotime($baslangic_tarih));
if ($view_type === 'yearly') {
    $baslangic_tarih = "$yil-01-01";
    $bitis_tarih = "$yil-12-31";
}

// 4) $terapistler, $room_types, $puantaj_kayitlari verilerini
//    functions.php içindeki getTerapistler() gibi helper’larla veya
//    doğrudan PDO sorgularıyla buraya çekin. Örneğin:
$terapistler = getTerapistler(); // dizi: her biri ['id'=>'…','ad'=>'…','soyad'=>'…']
// Room tiplerini çekmek (aynı kodu alabilirsiniz pages/puantaj’dan):
$pdo = getPDO(); // functions.php’de hazır PDO bağlantısı fonksiyonu olduğunuzu varsayıyorum

$sql_room = "SELECT DISTINCT rm.type 
             FROM rooms rm
             JOIN randevular r ON r.room_id = rm.id
             WHERE DATE(r.randevu_tarihi) BETWEEN :bas AND :bit
               AND r.aktif = 1 AND r.durum != 'iptal_edildi'";
if ($terapist_id) $sql_room .= " AND r.personel_id = :terapisID";
$sql_room .= " ORDER BY rm.type";

$stmt = $pdo->prepare($sql_room);
$params = [':bas' => $baslangic_tarih, ':bit' => $bitis_tarih];
if ($terapist_id) $params[':terapisID'] = $terapist_id;
$stmt->execute($params);
$room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Puantaj kayıtlarını çekin (yine pages/puantaj.php’den aynısını alabilirsiniz)
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
        WHERE DATE(r.randevu_tarihi) BETWEEN :bas AND :bit
          AND r.aktif = 1 AND r.durum != 'iptal_edildi'";
if ($terapist_id) $sql .= " AND p.id = :terapisID";
$sql .= " GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
          ORDER BY tarih, terapist_adi";

$stmt = $pdo->prepare($sql);
$params = [':bas' => $baslangic_tarih, ':bit' => $bitis_tarih];
if ($terapist_id) $params[':terapisID'] = $terapist_id;
$stmt->execute($params);
$puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) “export” tipine göre CSV ya da XLSX üretin:
$exportTip = isset($_GET['export']) ? $_GET['export'] : 'excel';
if ($exportTip === 'excel') {
    // --- CSV İndir ---
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="puantaj_' . $yil . '_' . $ay . '.csv"');
    $output = fopen('php://output', 'w');
    $baslik = ['Tarih'];
    foreach ($terapistler as $t) {
        foreach ($room_types as $type) {
            $baslik[] = $t['ad'] . ' ' . $t['soyad'] . ' - ' . $type;
        }
    }
    fputcsv($output, $baslik);

    $cur = new DateTime($baslangic_tarih);
    $end = new DateTime($bitis_tarih);
    while ($cur <= $end) {
        $tarih = $cur->format('Y-m-d');
        $satir = [ $cur->format('d.m.Y') ];
        foreach ($terapistler as $t) {
            foreach ($room_types as $type) {
                $adet = 0;
                foreach ($puantaj_kayitlari as $k) {
                    if ($k['tarih'] === $tarih
                        && $k['terapist_id'] == $t['id']
                        && $k['oda_tipi']   == $type) {
                        $adet = $k['seans_sayisi'];
                        break;
                    }
                }
                $satir[] = $adet;
            }
        }
        fputcsv($output, $satir);
        $cur->modify('+1 day');
    }
    fclose($output);
    exit;
}
elseif ($exportTip === 'xlsx') {
    // --- Gerçek XLSX İndir ---
    // Burada ya manuel autoload fonksiyonu yazın ya da Composer varsa require 'vendor/autoload.php';
    // Örnek: 
    // require_once __DIR__ . '/../vendor/autoload.php';
    // veya kendi autoload fonksiyonunuzu:
    spl_autoload_register(function($class) {
        $prefix = 'PhpOffice\\PhpSpreadsheet\\';
        $baseDir = __DIR__ . '/../vendor/PhpSpreadsheet/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $rel = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\','/',$rel) . '.php';
        if (file_exists($file)) require $file;
    });

    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1','Tarih');
    $col = 2;
    foreach ($terapistler as $t) {
        foreach ($room_types as $type) {
            $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::
                stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cellName, $t['ad'].' '.$t['soyad'].' - '.$type);
            $col++;
        }
    }
    $row = 2;
    $cur = new DateTime($baslangic_tarih);
    $end = new DateTime($bitis_tarih);
    while ($cur <= $end) {
        $sheet->setCellValue('A'.$row, $cur->format('d.m.Y'));
        $col = 2;
        foreach ($terapistler as $t) {
            foreach ($room_types as $type) {
                $adet = 0;
                foreach ($puantaj_kayitlari as $k) {
                    if ($k['tarih'] === $cur->format('Y-m-d')
                        && $k['terapist_id'] == $t['id']
                        && $k['oda_tipi']   == $type) {
                        $adet = $k['seans_sayisi'];
                        break;
                    }
                }
                $cellName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::
                    stringFromColumnIndex($col) . $row;
                $sheet->setCellValue($cellName, $adet);
                $col++;
            }
        }
        $cur->modify('+1 day');
        $row++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="puantaj_' . $yil . '_' . $ay . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
