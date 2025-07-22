<?php
// import_danisanlar.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/con/db.php';    // Database connection

use PhpOffice\PhpSpreadsheet\IOFactory;

// Suppress XML namespace warnings (they don't affect functionality)
libxml_use_internal_errors(true);

/**
 * Safely get cell value as string
 */
function cell($sheet, string $col, int $row): string {
    return trim((string)($sheet->getCell("$col$row")->getValue() ?? ''));
}

// Current staff ID who is running this import
$currentStaffId = 13; // Sinem Adilak as default

try {
    // 1) Load Excel file
    $excelPath = __DIR__ . '/calisma_deneme_not_extended.xlsx';
    if (!file_exists($excelPath)) {
        throw new \Exception("Excel file not found: $excelPath");
    }
    
    // Load with XML warnings suppressed
    $reader = IOFactory::createReaderForFile($excelPath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($excelPath);
    
    $sheet = $spreadsheet->getSheetByName('ÜYE ARŞİV ORJİNAL');
    if (!$sheet) {
        throw new \Exception("Worksheet 'ÜYE ARŞİV ORJİNAL' not found.");
    }
    $maxRow = $sheet->getHighestRow();

    // 2) Get category mapping from database
    $katRows = $pdo->query("SELECT kod, id FROM danisan_kategoriler")->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3) Excel column to category code mapping - GÜNCELLENDİ
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
        'P' => 'AKTIF_PILATES', // Yeni eklenen sütun
        'Q' => 'ARA_VEREN_PILATES', // Yeni eklenen sütun
        'R' => 'TEK_GELEN_PILATES', // Yeni eklenen sütun
        'S' => 'SA', // Spor Aktiviteleri
        'T' => 'GK', // Genel Katılım
    ];

    // 4) Staff code to ID mapping - GÜNCELLENDİ
    $kod2pid = [
        'AB' => 16, // Gamze Yıldırım
        'SA' => 13, // Sinem Adilak
        'GK' => 21, // Gizem Üzülmez
        'SY' => 18, // Yasemin İflazoğlu
        'GY' => 16, // Gamze Yıldırım (alternatif kod)
        'SU' => 22, // Selen Uçar
        'DK' => 23, // Derya Kaya
        'EY' => 24, // Elif Yılmaz
        'MA' => 25, // Merve Aydın
        'SD' => 26, // Seda Demir
    ];

    // 5) Prepare PDO statements
    $pdo->beginTransaction();

    // 5.1) Find client by email or phone
    $stmtGetByEmail = $pdo->prepare("SELECT id FROM danisanlar WHERE email = :email LIMIT 1");
    $stmtGetByTel = $pdo->prepare("SELECT id FROM danisanlar WHERE telefon = :tel LIMIT 1");

    // 5.2) Insert new client (without TC column)
    $stmtInsDan = $pdo->prepare("
        INSERT INTO danisanlar (ad, soyad, email, telefon)
        VALUES (:ad, :soyad, :email, :telefon)
    ");

    // 5.3) Client-category link
    $stmtInsLink = $pdo->prepare("
        INSERT IGNORE INTO danisan_kategori_parent (danisan_id, kategori_id, personel_id, aktif_flag)
        VALUES (:did, :kid, :pid, 1)
    ");

    // 5.4) Staff notes
    $stmtInsNot = $pdo->prepare("
        INSERT INTO personel_notlari (danisan_id, personel_id, not_tarihi, icerik)
        VALUES (:did, :pid, :tarih, :icerik)
    ");

    // 6) Counters
    $countDan = $countKat = $countNot = 0;

    // 7) Process rows
    for ($r = 3; $r <= $maxRow; ++$r) {
        $rowType = cell($sheet, 'A', $r);
        
        if ($rowType !== 'AD SOYAD') {
            continue;
        }

        // a) Get client info
        $adSoyad = cell($sheet, 'B', $r);
        $rawSms = cell($sheet, 'B', $r + 1);
        $rawTc = cell($sheet, 'B', $r + 2);

        // Process phone/email
        $email = $tel = null;
        if (filter_var($rawSms, FILTER_VALIDATE_EMAIL)) {
            $email = $rawSms;
        } else {
            $tel = preg_replace('/\D+/', '', $rawSms);
            if (strlen($tel) < 10) $tel = null;
        }

        // Split name
        $parts = preg_split('/\s+/', $adSoyad, 2);
        $ad = $parts[0] ?? '';
        $soyad = $parts[1] ?? '';

        // b) Find or create client
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
                ':ad' => $ad,
                ':soyad' => $soyad,
                ':email' => $email,
                ':telefon' => $tel
            ]);
            $danId = (int)$pdo->lastInsertId();
            $countDan++;
        }

        // c) Process categories (flags in columns C-T) - GÜNCELLENDİ
        foreach ($excel2kod as $col => $kod) {
            $cellValue = cell($sheet, $col, $r);
            
            // 1 değeri olanları ve SA/GK gibi direkt kod olanları yakala
            if (($cellValue === '1' || $cellValue === $kod) && isset($katRows[$kod])) {
                $stmtInsLink->execute([
                    ':did' => $danId,
                    ':kid' => $katRows[$kod],
                    ':pid' => $currentStaffId
                ]);
                $countKat++;
            }
        }

        // d) Process notes (column C)
        $rawNotes = cell($sheet, 'C', $r);
        if ($rawNotes !== '') {
            // Split notes by line breaks
            $lines = preg_split("/\r\n|\n|\r/", $rawNotes);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Try to extract date and staff code (improved regex)
                if (preg_match('/^(\d{2}\.\d{2}(?:\.\d{2,4})?)[\s\/\-]+([A-Z]{2})[\s\-]+(.+)$/u', $line, $m)) {
                    $datePart = $m[1];
                    $staffCode = $m[2];
                    $content = $m[3];
                    
                    // Parse date (handle different formats)
                    $date = null;
                    if (strpos($datePart, '.') !== false) {
                        $parts = explode('.', $datePart);
                        if (count($parts) === 2) {
                            // Day.Month format - assume current year
                            $day = (int)$parts[0];
                            $month = (int)$parts[1];
                            $year = date('Y');
                            $date = "$year-$month-$day";
                        } elseif (count($parts) === 3) {
                            // Day.Month.Year format
                            $day = (int)$parts[0];
                            $month = (int)$parts[1];
                            $year = (int)$parts[2];
                            if ($year < 100) $year += 2000; // Handle 2-digit years
                            $date = "$year-$month-$day";
                        }
                    }
                    
                    // Get staff ID from code (case insensitive)
                    $staffCodeUpper = strtoupper($staffCode);
                    $staffId = $kod2pid[$staffCodeUpper] ?? $currentStaffId;
                    
                    if ($date) {
                        $stmtInsNot->execute([
                            ':did' => $danId,
                            ':pid' => $staffId,
                            ':tarih' => $date,
                            ':icerik' => $content
                        ]);
                        $countNot++;
                    }
                } else {
                    // If no date/staff code found, use current date and staff
                    $stmtInsNot->execute([
                        ':did' => $danId,
                        ':pid' => $currentStaffId,
                        ':tarih' => date('Y-m-d'),
                        ':icerik' => $line
                    ]);
                    $countNot++;
                }
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    echo "Import completed successfully.\n";
    echo "New clients added: $countDan\n";
    echo "Category links added: $countKat\n";
    echo "Notes added: $countNot\n";

} catch (\Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Clear XML errors
libxml_clear_errors();