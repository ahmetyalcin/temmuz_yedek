<?php
include_once '../con/db.php';
header('Content-Type: application/json');

$danisan_id = $_GET['danisan_id'] ?? '';
if (!$danisan_id) die(json_encode(['success'=>false]));

// Fonksiyonel seans_turu_id'ler (güncellemek gerekirse değiştir)
define('ID_KINESIO', '55,56,57,58');

$stmt = $pdo->prepare("SELECT * FROM randevular WHERE danisan_id = ? AND seans_turu_id IN (".ID_KINESIO.") AND aktif = 1 ORDER BY randevu_tarihi ASC");
$stmt->execute([$danisan_id]);
$randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notlar (ölçüm noktalarına yazılmışlar)
$stmt2 = $pdo->prepare("SELECT * FROM fonksiyonel_seans_notlari WHERE danisan_id = ?");
$stmt2->execute([$danisan_id]);
$notlar = [];
foreach ($stmt2 as $n) {
    $notlar[$n['olcum_no']] = $n; // olcum_no kullanıyoruz!
}

// Akış üretimi
$rows = '';
$seansSayac = 1;
$olcumSayac = 1;
$evaluation_interval = 4;
$totalSeans = count($randevular);

// SIRALAMA: BAŞTA ÖLÇÜM, SONRA 4 SEANS, TEKRAR ÖLÇÜM...
for ($i = 0; $i < $totalSeans; ) {
    // 1) ÖLÇÜM (not girilecek)
    $not = $notlar[$olcumSayac]['icerik'] ?? '';
    $personel_id = $notlar[$olcumSayac]['personel_id'] ?? '';
    $not_tarihi = $notlar[$olcumSayac]['not_tarihi'] ?? '';
    $ekleyen = $personel_id ? getPersonelName($personel_id, $pdo) : '';
    $tarih = $not_tarihi ? date('d.m.Y H:i', strtotime($not_tarihi)) : '';
    $rows .= "<tr>
        <td><b>{$olcumSayac}. ÖLÇÜM FT</b></td>
        <td>
            <textarea class='form-control' data-olcum-no='{$olcumSayac}'>{$not}</textarea>
        </td>
        <td></td>
        <td>{$ekleyen}</td>
        <td>{$tarih}</td>
        <td>
            <button onclick='saveFonksiyonelNot(this, {$olcumSayac})' class='btn btn-primary btn-sm mt-1'>Kaydet</button>
        </td>
      </tr>";

    // 2) 4 SEANS
    for ($j = 0; $j < $evaluation_interval && $i < $totalSeans; $j++, $i++, $seansSayac++) {
        $rows .= "<tr>
            <td>{$seansSayac}. SEANS</td>
            <td colspan='5'>not girilemez</td>
          </tr>";
    }
    $olcumSayac++;
}

// ⬇️⬇️⬇️ EN SONA EKSTRA ÖLÇÜM SATIRI! (sadece %4 == 0 ise)
if ($totalSeans > 0 && $totalSeans % $evaluation_interval == 0) {
    $not = $notlar[$olcumSayac]['icerik'] ?? '';
    $personel_id = $notlar[$olcumSayac]['personel_id'] ?? '';
    $not_tarihi = $notlar[$olcumSayac]['not_tarihi'] ?? '';
    $ekleyen = $personel_id ? getPersonelName($personel_id, $pdo) : '';
    $tarih = $not_tarihi ? date('d.m.Y H:i', strtotime($not_tarihi)) : '';
    $rows .= "<tr>
        <td><b>{$olcumSayac}. ÖLÇÜM FT</b></td>
        <td>
            <textarea class='form-control' data-olcum-no='{$olcumSayac}'>{$not}</textarea>
        </td>
        <td></td>
        <td>{$ekleyen}</td>
        <td>{$tarih}</td>
        <td>
            <button onclick='saveFonksiyonelNot(this, {$olcumSayac})' class='btn btn-primary btn-sm mt-1'>Kaydet</button>
        </td>
      </tr>";
}

// Personel adı için
function getPersonelName($id, $pdo) {
    $q = $pdo->prepare("SELECT ad, soyad FROM personel WHERE id=?");
    $q->execute([$id]);
    $p = $q->fetch(PDO::FETCH_ASSOC);
    if (!$p) return '';
    return $p['ad'].' '.$p['soyad'];
}

echo json_encode([
    'success'=>true,
    'html'=>$rows
]);
exit;
