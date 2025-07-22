<?php


session_start();
require_once 'functions.php';

// Composer autoload for PhpSpreadsheet
require_once __DIR__ . '/vendor/autoload.php';

// --- COMMON DATA (so $terapistler is available everywhere) ---
$danisanlar        = getDanisanlar();
$randevular        = getRandevular();
$seansTurleri      = getSeansTurleri();
$uyelikTurleri     = getUyelikTurleri();
$sponsorluklar     = getSponsorluklar();
$terapistler       = getTerapistler();
$aktif_terapistler = getTerapistler(true);

// --- FILTER PARAMS ---
$ay          = $_GET['ay']          ?? date('m');
$yil         = $_GET['yil']         ?? date('Y');
$terapist_id = $_GET['terapist_id'] ?? null;
$view_type   = $_GET['view']        ?? 'monthly';

$baslangic = "$yil-$ay-01";
$bitis     = date('Y-m-t', strtotime($baslangic));
if ($view_type === 'yearly') {
    $baslangic = "$yil-01-01";
    $bitis     = "$yil-12-31";
}

// --- EXCEL EXPORT BLOCK ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // 1) Fetch room types
    $sql = "SELECT DISTINCT rm.type
            FROM rooms rm
            JOIN randevular r ON r.room_id = rm.id
            WHERE DATE(r.randevu_tarihi) BETWEEN ? AND ?
              AND r.aktif = 1
              AND r.durum != 'iptal_edildi'";
    $p = [$baslangic, $bitis];
    if ($terapist_id) {
        $sql .= " AND r.personel_id = ?";
        $p[] = $terapist_id;
    }
    $sql .= " ORDER BY rm.type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);
    $room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2) Fetch puantaj records including room type
    $sql = "SELECT
               DATE(r.randevu_tarihi) AS tarih,
               p.id                       AS terapist_id,
               rm.type                    AS oda_tipi,
               COUNT(DISTINCT r.id)       AS seans_sayisi
            FROM randevular r
            JOIN personel p ON p.id = r.personel_id
            JOIN rooms    rm ON rm.id = r.room_id
            WHERE DATE(r.randevu_tarihi) BETWEEN ? AND ?
              AND r.aktif = 1
              AND r.durum != 'iptal_edildi'";
    $p = [$baslangic, $bitis];
    if ($terapist_id) {
        $sql .= " AND p.id = ?";
        $p[]   = $terapist_id;
    }
    $sql .= " GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
              ORDER BY tarih, terapist_id, oda_tipi";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);
    $puantaj = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Yeni satışlar
    $sqlFirst = "SELECT danisan_id, hizmet_paketi_id AS seans_turu_id, MIN(olusturma_tarihi) AS first_date
                 FROM satislar WHERE aktif=1
                 GROUP BY danisan_id, hizmet_paketi_id";
    $sqlYeni = "SELECT
                   s.id,
                   CONCAT(d.ad,' ',d.soyad) AS danisan_ad,
                   CONCAT(p.ad,' ',p.soyad) AS terapist_ad,
                   st.ad                     AS seans_tur_ad,
                   s.toplam_tutar,
                   s.olusturma_tarihi
                FROM satislar s
                JOIN ($sqlFirst) fs
                  ON fs.danisan_id    = s.danisan_id
                 AND fs.seans_turu_id = s.hizmet_paketi_id
                JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
                JOIN danisanlar    d  ON d.id = s.danisan_id
                JOIN personel      p  ON p.id = s.personel_id
                WHERE s.aktif = 1
                  AND s.olusturma_tarihi BETWEEN ? AND ?
                  AND s.olusturma_tarihi = fs.first_date";
    $p = [$baslangic, $bitis];
    if ($terapist_id) {
        $sqlYeni .= " AND s.personel_id = ?";
        $p[] = $terapist_id;
    }
    $sqlYeni .= " ORDER BY s.olusturma_tarihi DESC";
    $stmt = $pdo->prepare($sqlYeni);
    $stmt->execute($p);
    $yeni = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4) Yenileme satışlar
    $sqlYen = str_replace("= fs.first_date", "<> fs.first_date", $sqlYeni);
    $stmt = $pdo->prepare($sqlYen);
    $stmt->execute($p);
    $yenileme = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- BUILD SPREADSHEET ---
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $writer      = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    // A) Puantaj sheet
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Puantaj');
    $sheet->setCellValue('A1','Tarih');
    $col = 'B';

    $exportTherap = $terapist_id
      ? array_filter($terapistler, fn($t)=>$t['id']==$terapist_id)
      : $terapistler;

    foreach ($exportTherap as $t) {
      foreach ($room_types as $rt) {
        $sheet->setCellValue($col.'1', $t['ad'].' '.$t['soyad']." / $rt");
        $col++;
      }
    }

    $row = 2;
    $d   = new DateTime($baslangic);
    $end = new DateTime($bitis);
    while ($d <= $end) {
      $sheet->setCellValue("A{$row}", $d->format('Y-m-d'));
      $c = 2;
      foreach ($exportTherap as $t) {
        foreach ($room_types as $rt) {
          $val = 0;
          foreach ($puantaj as $p) {
            if ($p['tarih']=== $d->format('Y-m-d')
             && $p['terapist_id']== $t['id']
             && $p['oda_tipi']   === $rt) {
              $val = $p['seans_sayisi'];
              break;
            }
          }
          $sheet->setCellValueByColumnAndRow($c, $row, $val);
          $c++;
        }
      }
      $d->modify('+1 day');
      $row++;
    }

    // thin borders
    $lastCol = $sheet->getHighestColumn();
    $lastRow = $sheet->getHighestRow();
    $sheet
      ->getStyle("A1:{$lastCol}{$lastRow}")
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // B) Yeni Müşteri Satışları sheet
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Yeni Müşteri Satışları');
    $sheet2->fromArray(
      ['Satış ID','Müşteri','Terapist','Seans Türü','Tutar','Tarih'],
      NULL,'A1'
    );
    $r = 2;
    foreach ($yeni as $s) {
      $sheet2->fromArray([
        $s['id'], $s['danisan_ad'], $s['terapist_ad'],
        $s['seans_tur_ad'], $s['toplam_tutar'], $s['olusturma_tarihi']
      ], NULL, "A{$r}");
      $r++;
    }
    $lastCol2 = $sheet2->getHighestColumn();
    $lastRow2 = $sheet2->getHighestRow();
    $sheet2
      ->getStyle("A1:{$lastCol2}{$lastRow2}")
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // C) Yenileme Paket Satışları sheet
    $sheet3 = $spreadsheet->createSheet();
    $sheet3->setTitle('Yenileme Paket Satışları');
    $sheet3->fromArray(
      ['Satış ID','Müşteri','Terapist','Seans Türü','Tutar','Tarih'],
      NULL,'A1'
    );
    $r = 2;
    foreach ($yenileme as $s) {
      $sheet3->fromArray([
        $s['id'], $s['danisan_ad'], $s['terapist_ad'],
        $s['seans_tur_ad'], $s['toplam_tutar'], $s['olusturma_tarihi']
      ], NULL, "A{$r}");
      $r++;
    }
    $lastCol3 = $sheet3->getHighestColumn();
    $lastRow3 = $sheet3->getHighestRow();
    $sheet3
      ->getStyle("A1:{$lastCol3}{$lastRow3}")
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // OUTPUT to browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"puantaj_{$yil}_{$ay}.xlsx\"");
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

// -----------------------------------------------------------------------------
// NORMAL PAGE RENDER
// -----------------------------------------------------------------------------

// Fetch room types again for display
$sql = "
  SELECT DISTINCT rm.type
  FROM rooms rm
  JOIN randevular r ON r.room_id = rm.id
  WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
    AND r.aktif = 1 AND r.durum != 'iptal_edildi'
" . ($terapist_id ? " AND r.personel_id = :terapist_id" : "") . "
  ORDER BY rm.type
";
$stmt = $pdo->prepare($sql);
$params = [':baslangic'=>$baslangic, ':bitis'=>$bitis];
if ($terapist_id) $params[':terapist_id'] = $terapist_id;
$stmt->execute($params);
$room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Puantaj kayıtları for display
$sql = "
  SELECT DATE(r.randevu_tarihi) AS tarih,
         p.id AS terapist_id,
         CONCAT(p.ad,' ',p.soyad) AS terapist_adi,
         rm.type AS oda_tipi,
         COUNT(DISTINCT r.id) AS seans_sayisi,
         GROUP_CONCAT(DISTINCT d.ad,' ',d.soyad) AS danisanlar
  FROM randevular r
  JOIN personel p ON p.id = r.personel_id
  JOIN rooms rm   ON rm.id = r.room_id
  JOIN danisanlar d ON d.id = r.danisan_id
  WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
    AND r.aktif = 1 AND r.durum != 'iptal_edildi'
" . ($terapist_id ? " AND p.id = :terapist_id" : "") . "
  GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
  ORDER BY tarih, terapist_adi
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Yeni satışlar for display
$sqlFirst = "
  SELECT danisan_id, hizmet_paketi_id AS seans_turu_id, MIN(olusturma_tarihi) AS first_date
  FROM satislar WHERE aktif=1
  GROUP BY danisan_id, hizmet_paketi_id
";
$sqlYeni = "
  SELECT s.id,
         CONCAT(d.ad,' ',d.soyad) AS danisan_ad,
         CONCAT(p.ad,' ',p.soyad) AS terapist_ad,
         st.ad AS seans_tur_ad,
         s.toplam_tutar,
         s.olusturma_tarihi
  FROM satislar s
  JOIN ($sqlFirst) fs
    ON fs.danisan_id = s.danisan_id
   AND fs.seans_turu_id = s.hizmet_paketi_id
  JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
  JOIN danisanlar d     ON d.id = s.danisan_id
  JOIN personel p       ON p.id = s.personel_id
  WHERE s.aktif=1
    AND s.olusturma_tarihi BETWEEN :baslangic AND :bitis
    AND s.olusturma_tarihi = fs.first_date
" . ($terapist_id?" AND s.personel_id=:terapist_id":"") . "
  ORDER BY s.olusturma_tarihi DESC
";
$stmt = $pdo->prepare($sqlYeni);
$stmt->execute($params);
$yeniSatislar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Yenileme satışlar for display
$sqlYenileme = str_replace("=fs.first_date","<>fs.first_date",$sqlYeni);
$stmt = $pdo->prepare($sqlYenileme);
$stmt->execute($params);
$yenilemeSatislar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotals
$ara_toplam = $genel_toplam = [];
foreach ($puantaj_kayitlari as $k) {
  $key = $k['terapist_id'].'_'.strtolower($k['oda_tipi']);
  $ara_toplam[$key]['seans']      = ($ara_toplam[$key]['seans'] ?? 0) + $k['seans_sayisi'];
  $ara_toplam[$key]['danisanlar'] = array_merge(
    $ara_toplam[$key]['danisanlar'] ?? [],
    explode(',', $k['danisanlar'])
  );
  $genel_toplam[$key]['seans']      = ($genel_toplam[$key]['seans'] ?? 0) + $k['seans_sayisi'];
  $genel_toplam[$key]['danisanlar'] = array_merge(
    $genel_toplam[$key]['danisanlar'] ?? [],
    explode(',', $k['danisanlar'])
  );
}

// Build display_terapistler array
$display_terapistler = $terapist_id
  ? array_filter($terapistler, fn($t)=>$t['id']==$terapist_id)
  : $terapistler;

// Helper for month names
function getTurkishMonthName($m){
  $map=[1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',
        6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',
        10=>'Ekim',11=>'Kasım',12=>'Aralık'];
  return $map[(int)$m];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include 'partials/title-meta.php'; ?>
  <?php include 'partials/session.php'; ?>
  <?php include 'partials/head-css.php'; ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css"/>
</head>
<body>
  <div class="wrapper">
    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>
    <div class="page-content">
      <div class="page-container">
        <?php
          $subtitle = "Gelirler";
          $title    = "Fizyoterapi Puantaj Tablosu";
          include 'partials/page-title.php';
        ?>
        <!-- Excel İndir -->
        <div class="mb-3 text-end">
          <a href="?page=puantaj
                     &ay=<?=urlencode($ay)?>
                     &yil=<?=urlencode($yil)?>
                     &terapist_id=<?=urlencode($terapist_id)?>
                     &view=<?=urlencode($view_type)?>
                     &export=excel"
             class="btn btn-success">
            Excel İndir
          </a>
        </div>

        <!-- Filtreler -->
        <div class="card mb-4">
          <div class="card-body">
            <form class="row g-3">
              <input type="hidden" name="page" value="puantaj">
              <div class="col-auto">
                <select name="view" class="form-select" onchange="this.form.submit()">
                  <option value="monthly" <?= $view_type==='monthly'?'selected':'' ?>>Aylık</option>
                  <option value="yearly"  <?= $view_type==='yearly' ?'selected':'' ?>>Yıllık</option>
                </select>
              </div>
              <?php if($view_type==='monthly'): ?>
              <div class="col-auto">
                <select name="ay" class="form-select">
                  <?php for($i=1;$i<=12;$i++): ?>
                  <option value="<?=str_pad($i,2,'0',STR_PAD_LEFT)?>"
                    <?= $ay==str_pad($i,2,'0',STR_PAD_LEFT)?'selected':'' ?>>
                    <?=getTurkishMonthName($i)?>
                  </option>
                  <?php endfor; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-auto">
                <select name="yil" class="form-select">
                  <?php for($i=date('Y')-1;$i<=date('Y')+1;$i++): ?>
                  <option value="<?=$i?>" <?= $yil==$i?'selected':'' ?>><?=$i?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-auto">
                <select name="terapist_id" class="form-select">
                  <option value="">Tüm Terapistler</option>
                  <?php foreach($terapistler as $t): ?>
                  <option value="<?=$t['id']?>" <?= $terapist_id==$t['id']?'selected':'' ?>>
                    <?=$t['ad'].' '.$t['soyad']?>
                  </option>
                  <?php endforeach;?>
                </select>
              </div>
              <div class="col-auto">
                <button class="btn btn-primary">Filtrele</button>
                <a href="?page=puantaj" class="btn btn-secondary">Sıfırla</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Puantaj Tablosu -->
        <div class="card mb-5">
          <div class="card-body">
            <div class="table-responsive">
              <table id="puantajTable" class="table table-bordered table-sm table-hover">
                <thead>
                  <tr>
                    <th>Tarih</th>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt): ?>
                      <th data-terapist="<?=$t['id']?>" class="text-center">
                        <?=$t['ad'].' '.$t['soyad']?><br><small><?=$rt?></small>
                      </th>
                    <?php endforeach; endforeach;?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $cursor = new DateTime($baslangic);
                  $end    = new DateTime($bitis);
                  while($cursor <= $end):
                    $d = $cursor->format('Y-m-d');
                  ?>
                  <tr>
                    <td><?=$cursor->format('d.m.Y')?></td>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt):
                      $cnt = 0; $names = [];
                      foreach($puantaj_kayitlari as $k){
                        if($k['tarih']==$d
                           && $k['terapist_id']==$t['id']
                           && $k['oda_tipi']==$rt) {
                          $cnt = $k['seans_sayisi'];
                          $names = explode(',',$k['danisanlar']);
                          break;
                        }
                      }
                    ?>
                      <td data-terapist="<?=$t['id']?>" class="text-center"
                          data-bs-toggle="tooltip"
                          title="<?=implode(', ',array_unique($names))?>">
                        <?=$cnt?'<span class="badge bg-primary">'.$cnt.'</span>':''?>
                      </td>
                    <?php endforeach; endforeach;?>
                  </tr>
                  <?php $cursor->modify('+1 day'); endwhile; ?>

                  <tr class="table-info">
                    <td><strong><?=$view_type==='monthly'?'Aylık':'Dönem'?> Toplam</strong></td>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt):
                      $key = $t['id'].'_'.strtolower($rt);
                    ?>
                      <td class="text-center"><strong><?=$ara_toplam[$key]['seans']??0?></strong></td>
                    <?php endforeach; endforeach;?>
                  </tr>
                  <tr class="table-primary">
                    <td><strong>Yıllık Toplam</strong></td>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt):
                      $key = $t['id'].'_'.strtolower($rt);
                    ?>
                      <td class="text-center"><strong><?=$genel_toplam[$key]['seans']??0?></strong></td>
                    <?php endforeach; endforeach;?>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Yeni Müşteri Satışları -->
        <div class="card mb-4">
          <div class="card-body">
            <h4>Yeni Müşteri Satışları</h4>
            <table id="salesYeniTable" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Satış ID</th><th>Müşteri</th><th>Satış Yapan Terapist</th>
                  <th>Seans Türü</th><th>Tutar</th><th>Tarih</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($yeniSatislar as $s): ?>
                <tr>
                  <td><?=htmlspecialchars($s['id'])?></td>
                  <td><?=htmlspecialchars($s['danisan_ad'])?></td>
                  <td><?=htmlspecialchars($s['terapist_ad'])?></td>
                  <td><?=htmlspecialchars($s['seans_tur_ad'])?></td>
                  <td><?=number_format($s['toplam_tutar'],2,',','.')?> ₺</td>
                  <td><?=htmlspecialchars($s['olusturma_tarihi'])?></td>
                </tr>
                <?php endforeach;?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Yenileme Paket Satışları -->
        <div class="card mb-5">
          <div class="card-body">
            <h4>Yenileme Paket Satışları</h4>
            <table id="salesRenewTable" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Satış ID</th><th>Müşteri</th><th>Satış Yapan Terapist</th>
                  <th>Seans Türü</th><th>Tutar</th><th>Tarih</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($yenilemeSatislar as $s): ?>
                <tr>
                  <td><?=htmlspecialchars($s['id'])?></td>
                  <td><?=htmlspecialchars($s['danisan_ad'])?></td>
                  <td><?=htmlspecialchars($s['terapist_ad'])?></td>
                  <td><?=htmlspecialchars($s['seans_tur_ad'])?></td>
                  <td><?=number_format($s['toplam_tutar'],2,',','.')?> ₺</td>
                  <td><?=htmlspecialchars($s['olusturma_tarihi'])?></td>
                </tr>
                <?php endforeach;?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <?php include 'partials/customizer.php'; ?>
  <?php include 'partials/footer-scripts.php'; ?>

  <!-- DataTables & Buttons -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
  <script>
    $(function(){
      $('#puantajTable').DataTable({
        dom: "<'row'<'col-sm-6'B>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
          { extend: 'excelHtml5', text: 'Excel İndir', title: `Puantaj ${getTurkishMonthName(<?= $ay ?>)} <?= $yil ?>` }
        ],
        ordering: true,
        pageLength: 100,
        searching: false
      });
      $('#salesYeniTable, #salesRenewTable').DataTable({
        ordering: true,
        pageLength: 100,
        searching: false
      });
      $('[data-bs-toggle="tooltip"]').each(function(){ new bootstrap.Tooltip(this) });
      $('select[name="terapist_id"]').on('change',function(){
        const id=this.value;
        $('[data-terapist]').each(function(){
          $(this).toggle(!id||$(this).data('terapist')==id);
        });
      }).trigger('change');
    });
  </script>
</body>
</html>
