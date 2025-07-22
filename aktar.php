<?php
// import_danisanlar.php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// import_danisanlar.php

// 0) Gerekli kütüphaneler ve DB bağlantısı
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/con/db.php';    // Burada $pdo nesnesi olmalı

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    // 1) Excel dosyasının yolunu ve varlığını kontrol edin
    $excelPath = __DIR__ . '/calisma.xlsx';
    if (!file_exists($excelPath)) {
        throw new \Exception("Excel dosyası bulunamadı: $excelPath");
    }

    // 2) Excel’i yükleyin ve sekmeyi alın
    $spreadsheet = IOFactory::load($excelPath);
    $sheet = $spreadsheet->getSheetByName('ÜYE ARŞİV ORJİNAL');
    if (!$sheet) {
        throw new \Exception("Sekme ‘ÜYE ARŞİV ORJİNAL’ bulunamadı.");
    }
    $maxRow = $sheet->getHighestRow();

    // 3) Satır satır dolaşarak AD SOYAD – SMS/E-MAİL – TC üçlülerle kayıtları toplayın
    $records = [];
    $current = [];
    for ($r = 2; $r <= $maxRow; ++$r) {
        $attr = trim((string)$sheet->getCell("A{$r}")->getValue());
        $val  = trim((string)$sheet->getCell("B{$r}")->getValue());

        if (!in_array($attr, ['AD SOYAD','SMS / E-MAİL','TC'], true)) {
            continue;
        }

        $current[$attr] = $val;

        // 'TC' satırını gördüğümüzde bir kayıt tamamlanmış sayılır
        if ($attr === 'TC') {
            $records[] = $current;
            $current   = [];
        }
    }

    // 4) Veritabanı işlemleri: INSERT veya UPDATE
    $pdo->beginTransaction();

    // Mükerrer kontrol için sorgular
    $stmtCheckEmail = $pdo->prepare("
        SELECT id 
          FROM danisanlar 
         WHERE email = :email
    ");
    $stmtCheckTel = $pdo->prepare("
        SELECT id 
          FROM danisanlar 
         WHERE telefon = :telefon
    ");

    // Kayıt ekleme
    $stmtInsert = $pdo->prepare("
        INSERT INTO danisanlar
          (ad, soyad, email, telefon)
        VALUES
          (:ad, :soyad, :email, :telefon)
    ");

    // Kayıt güncelleme (örnek: sadece ad/soyad güncelliyoruz)
    $stmtUpdate = $pdo->prepare("
        UPDATE danisanlar
           SET ad                = :ad,
               soyad             = :soyad,
               guncelleme_tarihi = NOW()
         WHERE id = :id
    ");

    $inserted = $updated = $skipped = 0;

    foreach ($records as $rec) {
        // Zorunlu alanlar
        if (empty($rec['AD SOYAD']) || empty($rec['SMS / E-MAİL'])) {
            $skipped++;
            continue;
        }

        // AD SOYAD → ad / soyad
        $parts = preg_split('/\s+/', $rec['AD SOYAD'], 2);
        $ad    = $parts[0];
        $soyad = $parts[1] ?? '';

        // SMS/E-MAİL alanından telefon veya e-posta ayıklama
        $smsEmail = $rec['SMS / E-MAİL'];
        if (filter_var($smsEmail, FILTER_VALIDATE_EMAIL)) {
            $email   = $smsEmail;
            $telefon = null;
        } else {
            $telefon = preg_replace('/\D+/', '', $smsEmail);
            $email   = null;
        }

        // Mükerrer kontrol (önce e-posta, sonra telefon)
        if ($email !== null) {
            $stmtCheckEmail->execute([':email' => $email]);
            $exists = $stmtCheckEmail->fetchColumn();
        } else {
            $stmtCheckTel->execute([':telefon' => $telefon]);
            $exists = $stmtCheckTel->fetchColumn();
        }

        if ($exists) {
            // Var olan kaydı güncelle
            $stmtUpdate->execute([
                ':ad'    => $ad,
                ':soyad' => $soyad,
                ':id'    => $exists
            ]);
            $updated++;
        } else {
            // Yeni kayıt ekle
            $stmtInsert->execute([
                ':ad'      => $ad,
                ':soyad'   => $soyad,
                ':email'   => $email,
                ':telefon' => $telefon
            ]);
            $inserted++;
        }
    }

    $pdo->commit();

    // 5) Sonuçları raporla
    echo "Yeni eklenen: $inserted\n";
    echo "Güncellenen   : $updated\n";
    echo "Atlanan       : $skipped\n";

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Hata: " . $e->getMessage() . "\n";
    exit(1);
}
