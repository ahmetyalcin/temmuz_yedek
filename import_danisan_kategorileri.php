<?php
// import_danisan_kategorileri.php
// Kullanım: php import_danisan_kategorileri.php /path/danisan_kategori_liste.xlsx

require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// ---- DB ---- (senin PDO kodunu doğrudan kullan)
$host = "localhost";
$dbname = "u1989180_test";
$username = "u1989180_test";
$password = "NdH#bY#)?[+0";

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// ---- Parametre / Dosya ----
$excelPath = $argv[1] ?? null;
if (!$excelPath || !is_file($excelPath)) {
    die("Kullanım: php import_danisan_kategorileri.php /tam/yol/dosya.xlsx\n");
}

// ---- Yardımcılar ----
function tr_upper($s){ return mb_strtoupper($s ?? '', 'UTF-8'); }
function norm_name($s){
    $s = trim((string)$s);
    $map = ['ı'=>'i','İ'=>'I','ş'=>'s','Ş'=>'S','ğ'=>'g','Ğ'=>'G','ç'=>'c','Ç'=>'C','ö'=>'o','Ö'=>'O','ü'=>'u','Ü'=>'U'];
    $s = strtr($s,$map);
    $s = preg_replace('/\s+/u',' ',$s);
    return $s;
}
function norm_phone($p){
    $p = preg_replace('/\D+/', '', (string)$p);
    return strlen($p) > 10 ? substr($p, -10) : $p; // TR GSM son 10 hane
}

// ---- Kategori haritası (ad -> id) ----
$kategoriMap = [];
foreach ($pdo->query("SELECT id, ad FROM kategori") as $row) {
    $kategoriMap[tr_upper(trim($row['ad']))] = (int)$row['id'];
}
// (İstersen kısaltma/yanlış yazım eşlemesi burada tanımlanabilir)

// ---- Danışan arama prepared ----
$findByPhone = $pdo->prepare("
    SELECT id, ad, soyad, telefon
    FROM danisanlar
    WHERE RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(telefon,' ',''),'-',''),'(',''),')',''),10) = :tel10
");
$findByName  = $pdo->prepare("
    SELECT id, ad, soyad, telefon
    FROM danisanlar
    WHERE UPPER(ad) = :ad AND UPPER(soyad) = :soyad
");

// Var ise ekleme (çift kayıt önleme) — şema değiştirmeden:
$insertIfNotExists = $pdo->prepare("
    INSERT INTO danisan_kategori (danisan_id, kategori_id)
    SELECT :danisan_id, :kategori_id
    FROM DUAL
    WHERE NOT EXISTS (
        SELECT 1 FROM danisan_kategori
        WHERE danisan_id = :danisan_id AND kategori_id = :kategori_id
    )
");

// ---- Excel oku (başlıklardan otomatik sütun bul) ----
$headerAliases = [
    'ad'       => ['ad','isim','name','first name','adi'],
    'soyad'    => ['soyad','soyadı','surname','last name'],
    'telefon'  => ['telefon','gsm','tel','phone','cep'],
    'kategori' => ['kategori','durum','etiket','label','status']
];

$sheet = IOFactory::load($excelPath)->getActiveSheet();
$highestRow = $sheet->getHighestRow();
$highestCol = $sheet->getHighestColumn();

$headerRow = 1;
$headers = [];
for ($c=1; $c<=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol); $c++) {
    $val = trim((string)$sheet->getCellByColumnAndRow($c, $headerRow)->getValue());
    $headers[$c] = tr_upper($val);
}
function findCol($key, $headers, $aliases){
    foreach ($headers as $c=>$h){
        foreach ($aliases[$key] as $alias){
            if (tr_upper($alias) === $h) return $c;
        }
    }
    return null;
}
$colAd       = findCol('ad', $headers, $headerAliases);
$colSoyad    = findCol('soyad', $headers, $headerAliases);
$colTelefon  = findCol('telefon', $headers, $headerAliases);
$colKategori = findCol('kategori', $headers, $headerAliases);

if (!$colKategori || (!$colTelefon && !($colAd && $colSoyad))) {
    die("Başlıklar bulunamadı. Gerekenler: KATEGORI ve (TELEFON veya AD+SOYAD).\n");
}

// ---- Döngü ----
$log = ['inserted'=>0,'skipped'=>0,'no_category'=>[],'no_client'=>[],'ambiguous'=>[]];

$pdo->beginTransaction();

for ($r=$headerRow+1; $r<=$highestRow; $r++){
    $ad   = $colAd       ? trim((string)$sheet->getCellByColumnAndRow($colAd,$r)->getValue())       : '';
    $soy  = $colSoyad    ? trim((string)$sheet->getCellByColumnAndRow($colSoyad,$r)->getValue())    : '';
    $tel  = $colTelefon  ? (string)$sheet->getCellByColumnAndRow($colTelefon,$r)->getValue()        : '';
    $kat  =              trim((string)$sheet->getCellByColumnAndRow($colKategori,$r)->getValue());

    if ($ad==='' && $soy==='' && $tel==='' && $kat==='') continue;

    $katKey = tr_upper($kat);
    if (!isset($kategoriMap[$katKey])) {
        $log['no_category'][] = ['row'=>$r,'kategori_raw'=>$kat];
        $log['skipped']++;
        continue;
    }
    $kategori_id = $kategoriMap[$katKey];

    // Danışanı bul
    $danisan = null;
    $tel10 = norm_phone($tel);
    if ($tel10 !== '') {
        $findByPhone->execute([':tel10'=>$tel10]);
        $rows = $findByPhone->fetchAll();
        if (count($rows) === 1) {
            $danisan = $rows[0];
        } elseif (count($rows) > 1) {
            // Aynı tel birden fazla kişide, ad/soyad ile daraltmayı deneriz
            $cand = array_values(array_filter($rows, function($x) use ($ad,$soy){
                return tr_upper($x['ad'])===tr_upper($ad) && tr_upper($x['soyad'])===tr_upper($soy);
            }));
            if (count($cand) === 1) $danisan = $cand[0];
            else $log['ambiguous'][] = ['row'=>$r,'telefon'=>$tel,'adet'=>count($rows)];
        }
    }
    if (!$danisan && $colAd && $colSoyad && $ad!=='' && $soy!=='') {
        $findByName->execute([':ad'=>tr_upper(norm_name($ad)), ':soyad'=>tr_upper(norm_name($soy))]);
        $rows = $findByName->fetchAll();
        if (count($rows) === 1) $danisan = $rows[0];
        elseif (count($rows) > 1) $log['ambiguous'][] = ['row'=>$r,'ad'=>$ad,'soyad'=>$soy,'adet'=>count($rows)];
    }
    if (!$danisan) {
        $log['no_client'][] = ['row'=>$r,'ad'=>$ad,'soyad'=>$soy,'telefon'=>$tel];
        $log['skipped']++;
        continue;
    }

    // Var mı kontrol ederek ekle
    $insertIfNotExists->execute([
        ':danisan_id'=>$danisan['id'],
        ':kategori_id'=>$kategori_id
    ]);
    $log['inserted'] += $insertIfNotExists->rowCount(); // 1 ise yeni eklendi, 0 ise zaten vardı
}

$pdo->commit();

file_put_contents(__DIR__.'/import_log.json', json_encode($log, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

echo "Bitti. Yeni eklenen: {$log['inserted']} | Atlanan: {$log['skipped']}\n";
echo "Log: ".(__DIR__.'/import_log.json')."\n";
