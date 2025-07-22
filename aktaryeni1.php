<?php
// import_danisanlar.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/con/db.php';    // burada $pdo hazır

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Hücreden güvenli string okuma.
 */
function cell($sheet, string $col, int $row): string {
    return trim((string)($sheet->getCell("$col$row")->getValue() ?? ''));
}

// Bu import’u başlatan personel’in ID’si
$currentStaffId = 13;

try {
    // 1) Excel dosyasını aç
    $excelPath = __DIR__ . '/calisma.xlsx';
    if (!file_exists($excelPath)) {
        throw new \Exception("Excel dosyası bulunamadı: $excelPath");
    }
    $spreadsheet = IOFactory::load($excelPath);
    $sheet       = $spreadsheet->getSheetByName('ÜYE ARŞİV ORJİNAL');
    if (!$sheet) {
        throw new \Exception("Sekme ‘ÜYE ARŞİV ORJİNAL’ bulunamadı.");
    }
    $maxRow = $sheet->getHighestRow();

    // 2) not_kategori_tanimi tablosundan kod→id haritası
    $katRows = $pdo
        ->query("SELECT kod, id FROM not_kategori_tanimi")
        ->fetchAll(PDO::FETCH_KEY_PAIR);
    // Örnek: ['AKTIF_FTR' => 1, 'AKTIF_DYT' => 2, …]

    // 3) Excel sütun harfi → kategori kodu
    $excel2kod = [
        'C' => 'AKTIF_FTR',
        'D' => 'AKTIF_DYT',
        'E' => 'ARA_VEREN_FTR',
        'F' => 'ARA_VEREN_DYT',
        'G' => 'TEK_GELEN_FTR',
        'H' => 'TEK_GELEN_DYT',
        'I' => 'ARALIKLI_ARANACAK_DYT',
        'J' => 'SUREKLI_ARANACAK_FTR',
        'K' => 'SUREKLI_ARANACAK_DYT',
        'L' => 'KAMPANYA_DONEMI',
        'M' => 'ARAMA',
        'N' => 'GUNLUK_PROGRAMA_KATILANLAR',
        'O' => 'FOOTBALANCE',
    ];

    // 4) Not kodu → personel_id haritası
    $kod2pid = [
        'AB' => 16,
        'SA' => 13,
        'GK' => 21,
        'SY' => 18,
        // eksikleri buraya ekleyin...
    ];

    // 5) PDO sorgularını hazırla
    $pdo->beginTransaction();

    // 5.1) Danışan bulmak için email / telefon
    $stmtGetByEmail = $pdo->prepare("
        SELECT id FROM danisanlar WHERE email = :email LIMIT 1
    ");
    $stmtGetByTel   = $pdo->prepare("
        SELECT id FROM danisanlar WHERE telefon = :tel LIMIT 1
    ");

    // 5.2) Yeni danışan ekleme
    $stmtInsDan = $pdo->prepare("
        INSERT INTO danisanlar
          (ad, soyad, email, telefon)
        VALUES
          (:ad, :soyad, :email, :telefon)
    ");

    // 5.3) Danışan–kategori link
    $stmtInsLink = $pdo->prepare("
        INSERT IGNORE INTO danisan_kategori
          (danisan_id, kategori_id, personel_id, aktif_flag)
        VALUES
          (:did, :kid, :pid, 1)
    ");

    // 5.4) Personel notları
    $stmtInsNot = $pdo->prepare("
        INSERT INTO personel_notlari
          (danisan_id, personel_id, not_tarihi, icerik)
        VALUES
          (:did, :pid, :tarih, :icerik)
    ");

    // 6) “NOTLARIOKU” sütununu bul
    $noteCol = null;
    foreach (range('A','Z') as $c) {
        if (stripos(cell($sheet, $c, 2), 'NOTLARIOKU') === 0) {
            $noteCol = $c;
            break;
        }
    }

    // 7) Sayaçlar
    $countDan = $countKat = $countNot = 0;

    // 8) Satır satır işle
    for ($r = 3; $r <= $maxRow; ++$r) {
        if (strtoupper(cell($sheet, 'A', $r)) !== 'AD SOYAD') {
            continue;
        }

        // a) AD SOYAD, SMS/E-Mail satırını oku
        $adSoyad = cell($sheet, 'B', $r);
        $rawSms  = cell($sheet, 'B', $r + 1);

        // email mi, telefon mu?
        if (filter_var($rawSms, FILTER_VALIDATE_EMAIL)) {
            $email = $rawSms;
            $tel   = null;
        } else {
            $email = null;
            $tel   = preg_replace('/\D+/', '', $rawSms);
        }

        // b) Ad / Soyad ayrıştır
        $parts = preg_split('/\s+/', $adSoyad, 2);
        $ad    = $parts[0] ?? '';
        $soyad = $parts[1] ?? '';

        // c) Danışanı bul veya ekle
        $danId = 0;
        if ($email) {
            $stmtGetByEmail->execute([':email' => $email]);
            $danId = (int)$stmtGetByEmail->fetchColumn();
        }
        if (!$danId && $tel) {
            $stmtGetByTel->execute([':tel' => $tel]);
            $danId = (int)$stmtGetByTel->fetchColumn();
        }
        if (!$danId) {
            $stmtInsDan->execute([
                ':ad'      => $ad,
                ':soyad'   => $soyad,
                ':email'   => $email,
                ':telefon' => $tel
            ]);
            $danId = (int)$pdo->lastInsertId();
            $countDan++;
        }

        // d) Kategori flag’lerini ekle
        foreach ($excel2kod as $col => $kod) {
            if (cell($sheet, $col, $r) === '1' && isset($katRows[$kod])) {
                $stmtInsLink->execute([
                    ':did' => $danId,
                    ':kid' => $katRows[$kod],
                    ':pid' => $currentStaffId
                ]);
                $countKat++;
            }
        }

        // e) Notları oku: önce cell değeri, yoksa comment
        $rawNotes = '';
        if ($noteCol) {
            $val = cell($sheet, $noteCol, $r);
            if ($val !== '') {
                $rawNotes = $val;
            } else {
                $coord   = $noteCol . $r;
                $comment = $sheet->getComment($coord);
                if ($comment && $comment->getText()) {
                    $rawNotes = trim($comment->getText()->getPlainText());
                }
            }
        }

        // f) Not satırlarını işleyip kaydet
        if ($rawNotes !== '') {
            $lines = preg_split("/\r\n|\n|\r/", $rawNotes);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match(
                    '/^(\d{2}\.\d{2}(?:\.\d{2,4})?)[\s\/\-]+([A-Z]{2})[\s\-]+(.+)$/u',
                    $line, $m
                )) {
                    // tarih dönüştür
                    $dr = $m[1];
                    if (substr_count($dr, '.') === 1) {
                        list($g, $a) = explode('.', $dr);
                        $tarih = date('Y-m-d', mktime(0,0,0,(int)$a,(int)$g));
                    } else {
                        $dt = \DateTime::createFromFormat('d.m.y', $dr)
                           ?: \DateTime::createFromFormat('d.m.Y', $dr);
                        $tarih = $dt ? $dt->format('Y-m-d') : null;
                    }
                    // kod→personel_id
                    $noteKod = $m[2];
                    $notePid = $kod2pid[$noteKod] ?? $currentStaffId;
                    // içerik
                    $text = $m[3];

                    if ($tarih) {
                        $stmtInsNot->execute([
                            ':did'    => $danId,
                            ':pid'    => $notePid,
                            ':tarih'  => $tarih,
                            ':icerik' => $text
                        ]);
                        $countNot++;
                    }
                }
            }
        }
    }

    // 9) Commit & sonuç
    $pdo->commit();

    echo "Tamamlandı.\n";
    echo "Yeni danışan sayısı       : $countDan\n";
    echo "Eklenen kategori satırları: $countKat\n";
    echo "Eklenen not satırları     : $countNot\n";

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Hata: " . $e->getMessage() . "\n";
    exit(1);
}
