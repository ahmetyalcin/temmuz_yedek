<?php
/*********************************************************
 * import_kolon_kategori.php
 * - B, C, D ... sütun başlıkları KATEGORİ adlarıdır
 * - Her sütunda altta satır satır şu yapı var:
 *     A: AD-SOYAD  → aynı satırdaki [B/C/...] hücresinde İsim
 *     A: GSM      → bir alt satırda [B/C/...] hücresinde Telefon
 * - Kişi yoksa deneme olarak eklenir: deneme_mi = 1
 * - Kategori yoksa oluşturulur; kişi o kategoriye bağlanır
 *********************************************************/
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
libxml_use_internal_errors(true);

require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// --- DB (senin çalışan blok) ---
$host = "localhost";
$dbname = "u1989180_test";
$username = "u1989180_test";
$password = "NdH#bY#)?[+0";
try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Veritabanı bağlantı hatası: ".$e->getMessage()); }

// --- helpers ---
function tr_map($s){ return strtr($s ?? '', ['ı'=>'i','İ'=>'I','ş'=>'s','Ş'=>'S','ğ'=>'g','Ğ'=>'G','ç'=>'c','Ç'=>'C','ö'=>'o','Ö'=>'O','ü'=>'u','Ü'=>'U']); }
function norm_name($s){ return preg_replace('/\s+/u',' ', trim(tr_map((string)$s))); }
function split_name($full){ $full=trim(preg_replace('/\s+/u',' ',$full)); if($full==='')return['','']; $p=explode(' ',$full); if(count($p)===1)return[$p[0],'']; $soy=array_pop($p); return [implode(' ',$p),$soy]; }
function norm_phone($p){ $p=preg_replace('/\D+/', '', (string)$p); return strlen($p)>10?substr($p,-10):$p; }
function cell($sh,$col,$row){ return trim((string)($sh->getCell($col.$row)->getValue() ?? '')); }
function is_label($val, array $alts){ $v = mb_strtoupper(tr_map(trim((string)$val)), 'UTF-8'); foreach($alts as $a){ if($v===$a) return true; } return false; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --- prepared ---
$selKat = $pdo->prepare("SELECT id FROM kategori WHERE ad=:ad LIMIT 1");
$insKat = $pdo->prepare("INSERT INTO kategori (ad) VALUES (:ad)");
$findByPhone = $pdo->prepare("SELECT id FROM danisanlar
  WHERE RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(telefon,' ',''),'-',''),'(',''),')',''),10)=:tel10");
$findByName  = $pdo->prepare("SELECT id FROM danisanlar WHERE UPPER(ad)=:ad AND UPPER(soyad)=:soyad");
$insClient   = $pdo->prepare("INSERT INTO danisanlar (ad, soyad, telefon, deneme_mi, aktif, whatsapp_onay, sms_onay)
  VALUES (:ad,:soyad,:telefon,1,1,1,1)");
$insLinkIfNot= $pdo->prepare("INSERT INTO danisan_kategori (danisan_id, kategori_id)
  SELECT :d,:k FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM danisan_kategori WHERE danisan_id=:d AND kategori_id=:k
  )");

// --- run ---
$log = null; $err = null;
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['excel'])) {
  $headerRow = (int)($_POST['header_row'] ?? 1); // başlıkların olduğu satır (default 1)
  $purge = isset($_POST['purge']);               // var olan bağları temizle

  try {
    $sh = IOFactory::load($_FILES['excel']['tmp_name'])->getActiveSheet();
    $maxRow = $sh->getHighestRow();
    $maxColIdx = Coordinate::columnIndexFromString($sh->getHighestColumn());

    // 1) Kategori başlıkları (B'den sağa)
    $kategori = []; // letter => ['id'=>int,'name'=>string]
    for ($i=2; $i <= $maxColIdx; $i++){
      $col = Coordinate::stringFromColumnIndex($i);
      $name = trim(cell($sh,$col,$headerRow));
      if ($name==='') continue;
      $selKat->execute([':ad'=>$name]);
      $row = $selKat->fetch(PDO::FETCH_ASSOC);
      if ($row){ $kid=(int)$row['id']; }
      else { $insKat->execute([':ad'=>$name]); $kid=(int)$pdo->lastInsertId(); }
      $kategori[$col] = ['id'=>$kid,'name'=>$name];
    }
    if (!$kategori) throw new RuntimeException("Başlık satırında (B.. sağ) kategori bulunamadı.");

    // 2) Etiketler: A sütunundaki AD-SOYAD ve GSM satır çiftleri
    $log = ['new_clients'=>0,'used_clients'=>0,'links'=>0,'purged'=>0];

    $delLinks = $pdo->prepare("DELETE FROM danisan_kategori WHERE danisan_id=:d");

    $pdo->beginTransaction();

    for ($r=$headerRow+1; $r <= $maxRow; $r++){
      $label = cell($sh,'A',$r);
      if (!is_label($label, ['AD-SOYAD','AD SOYAD','ADSOYAD'])) continue;

      $gsmRow = $r+1;
      if ($gsmRow > $maxRow || !is_label(cell($sh,'A',$gsmRow), ['GSM','SMS / E-MAIL','SMS/E-MAIL','TEL','TELEFON'])) {
        // beklenen eş çift yoksa bu satırı atla
        continue;
      }

      // Her kategori sütunu için bir isim/telefon olabilir
      foreach ($kategori as $col=>$meta){
        $fullName = cell($sh,$col,$r);
        $phone    = cell($sh,$col,$gsmRow);
        if ($fullName==='' && $phone==='') continue;

        [$ad,$soyad] = split_name($fullName);
        $tel10 = norm_phone($phone);

        // kişiyi bul/oluştur
        $danisanId = null;
        if ($tel10!==''){ $findByPhone->execute([':tel10'=>$tel10]); $row=$findByPhone->fetch(PDO::FETCH_ASSOC); if($row) $danisanId=(int)$row['id']; }
        if (!$danisanId && $ad!=='' && $soyad!==''){
          $findByName->execute([':ad'=>mb_strtoupper(norm_name($ad),'UTF-8'), ':soyad'=>mb_strtoupper(norm_name($soyad),'UTF-8')]);
          $row=$findByName->fetch(PDO::FETCH_ASSOC); if($row) $danisanId=(int)$row['id'];
        }
        if (!$danisanId){
          $insClient->execute([':ad'=>$ad, ':soyad'=>$soyad, ':telefon'=>($tel10!==''?$tel10:null)]);
          $danisanId = (int)$pdo->lastInsertId();
          $log['new_clients']++;
        } else {
          $log['used_clients']++;
        }

        // bağları sıfırla istenmişse
        if ($purge){
          $delLinks->execute([':d'=>$danisanId]);
          $log['purged'] += $delLinks->rowCount();
        }

        // kategori bağla
        $insLinkIfNot->execute([':d'=>$danisanId, ':k'=>$meta['id']]);
        $log['links'] += $insLinkIfNot->rowCount();
      }

      // sonraki isim bloğuna atlamak için r++ zaten döngü sonunda olacak; GSM satırını da atlayalım:
      $r++; // GSM satırını geç
    }

    $pdo->commit();

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $err = "Hata: ".$e->getMessage();
  }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Excel İçe Aktarma (Sütun=Kategori, Altında AD-SOYAD/GSM)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>body{font-family:system-ui;max-width:1000px;margin:24px auto;padding:0 16px}
.card{border:1px solid #ddd;border-radius:10px;padding:14px;margin-bottom:14px}
.err{color:#b00020}.ok{color:#0a7d11}</style>
</head>
<body>
<h2>Excel → danisanlar (deneme_mi=1) + kategori + danisan_kategori</h2>

<div class="card">
  <form method="post" enctype="multipart/form-data">
    <label>Excel (.xlsx/.xls):</label>
    <input type="file" name="excel" accept=".xlsx,.xls" required>
    <label style="margin-left:12px">Başlık satırı:
      <input type="number" name="header_row" value="1" min="1" style="width:80px">
    </label>
    <label style="margin-left:12px">
      <input type="checkbox" name="purge"> Kişinin mevcut kategori bağlarını temizle
    </label>
    <button type="submit" style="margin-left:12px">Yükle ve Aktar</button>
  </form>
  <p>A sütunu etiket; her kategorinin altındaki iki satır (AD-SOYAD, sonra GSM) birlikte işlenir.</p>
</div>

<?php if(isset($err)): ?>
  <div class="card err"><strong><?=h($err)?></strong></div>
<?php endif; ?>
<?php if(isset($log)): ?>
  <div class="card">
    <p class="ok"><b>Yeni kişi:</b> <?= (int)$log['new_clients'] ?> |
      <b>Var olan kişi (kullanıldı):</b> <?= (int)$log['used_clients'] ?> |
      <b>Yeni ilişki:</b> <?= (int)$log['links'] ?> |
      <b>Silinen ilişki:</b> <?= (int)$log['purged'] ?></p>
  </div>
<?php endif; ?>
</body>
</html>
