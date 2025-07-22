<?php
session_start();
require_once 'functions.php';

// --- Veriler ---
$danisanlar        = getDanisanlar();
$randevular        = getRandevular();
$seansTurleri      = getSeansTurleri();
$uyelikTurleri     = getUyelikTurleri();
$sponsorluklar     = getSponsorluklar();
$terapistler       = getTerapistler();
$aktif_terapistler = getTerapistler(true);

// --- Tarih & Terapist Filtreleri ---
$ay          = $_GET['ay']          ?? date('m');
$yil         = $_GET['yil']         ?? date('Y');
$terapist_id = $_GET['terapist_id'] ?? null;
$view_type   = $_GET['view']        ?? 'monthly';

$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih     = date('Y-m-t', strtotime($baslangic_tarih));
if ($view_type === 'yearly') {
    $baslangic_tarih = "$yil-01-01";
    $bitis_tarih     = "$yil-12-31";
}

// --- Sütunlarda gösterilecek terapistler ---
if ($terapist_id) {
    $display_terapistler = array_filter(
        $terapistler,
        fn($t) => $t['id'] == $terapist_id
    );
} else {
    $display_terapistler = $terapistler;
}

// --- Oda Tipleri ---
$sql = "
  SELECT DISTINCT rm.type
  FROM rooms rm
  JOIN randevular r ON r.room_id = rm.id
  WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
    AND r.aktif = 1
    AND r.durum != 'iptal_edildi'
" . ($terapist_id ? " AND r.personel_id = :terapist_id" : "") . "
  ORDER BY rm.type
";
$stmt = $pdo->prepare($sql);
$params = [':baslangic'=>$baslangic_tarih, ':bitis'=>$bitis_tarih];
if ($terapist_id) $params[':terapist_id'] = $terapist_id;
$stmt->execute($params);
$room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// --- Randevu Puantaj ---
$sql = "
  SELECT
    DATE(r.randevu_tarihi) AS tarih,
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
    AND r.aktif = 1
    AND r.durum != 'iptal_edildi'
" . ($terapist_id ? " AND p.id = :terapist_id" : "") . "
  GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
  ORDER BY tarih, terapist_adi
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Satış Alt Sorgu ---
$sqlFirst = "
  SELECT 
    s0.danisan_id,
    s0.hizmet_paketi_id AS seans_turu_id,
    MIN(s0.olusturma_tarihi) AS first_date
  FROM satislar s0
  WHERE s0.aktif = 1
  GROUP BY s0.danisan_id, s0.hizmet_paketi_id
";

// --- Yeni Satışlar ---
$sqlYeni = "
  SELECT
    s.id,
    CONCAT(d.ad,' ',d.soyad) AS danisan_ad,
    CONCAT(p.ad,' ',p.soyad) AS terapist_ad,
    st.ad                     AS seans_tur_ad,
    s.toplam_tutar,
    s.olusturma_tarihi
  FROM satislar s
  JOIN ({$sqlFirst}) fs
    ON fs.danisan_id    = s.danisan_id
   AND fs.seans_turu_id = s.hizmet_paketi_id
  JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
  JOIN danisanlar    d  ON d.id = s.danisan_id
  JOIN personel      p  ON p.id = s.personel_id
  WHERE s.aktif = 1
    AND s.olusturma_tarihi BETWEEN :baslangic AND :bitis
    AND s.olusturma_tarihi = fs.first_date
" . ($terapist_id ? " AND s.personel_id = :terapist_id" : "") . "
  ORDER BY s.olusturma_tarihi DESC
";
$stmt = $pdo->prepare($sqlYeni);
$stmt->execute($params);
$yeniSatislar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Yenileme Satışlar ---
$sqlYenileme = "
  SELECT
    s.id,
    CONCAT(d.ad,' ',d.soyad) AS danisan_ad,
    CONCAT(p.ad,' ',p.soyad) AS terapist_ad,
    st.ad                     AS seans_tur_ad,
    s.toplam_tutar,
    s.olusturma_tarihi
  FROM satislar s
  JOIN ({$sqlFirst}) fs
    ON fs.danisan_id    = s.danisan_id
   AND fs.seans_turu_id = s.hizmet_paketi_id
  JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
  JOIN danisanlar    d  ON d.id = s.danisan_id
  JOIN personel      p  ON p.id = s.personel_id
  WHERE s.aktif = 1
    AND s.olusturma_tarihi BETWEEN :baslangic AND :bitis
    AND s.olusturma_tarihi <> fs.first_date
" . ($terapist_id ? " AND s.personel_id = :terapist_id" : "") . "
  ORDER BY s.olusturma_tarihi DESC
";
$stmt = $pdo->prepare($sqlYenileme);
$stmt->execute($params);
$yenilemeSatislar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Ara/Genel Toplamlar ---
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

function getTurkishMonthName($m){
  $map = [
    1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',
    6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',
    10=>'Ekim',11=>'Kasım',12=>'Aralık'
  ];
  return $map[(int)$m];
}

// Excel/PDF export başlığı
$monthName    = getTurkishMonthName($ay);
$therapistName = $terapist_id
    ? array_values(array_filter($terapistler, fn($t)=>$t['id']==$terapist_id))[0]['ad'].' '.
      array_values(array_filter($terapistler, fn($t)=>$t['id']==$terapist_id))[0]['soyad']
    : 'Tüm Terapistler';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include 'partials/title-meta.php'; ?>
  <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css"/>
  <?php include 'partials/session.php'; ?>
  <?php include 'partials/head-css.php'; ?>
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

        <!-- Excel Export Butonu -->
        <div class="mb-3 text-end">
          <a href="?page=puantaj&ay=<?=htmlspecialchars($ay)?>&yil=<?=htmlspecialchars($yil)?>&terapist_id=<?=htmlspecialchars($terapist_id)?>&export=excel"
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
                  <option value="<?=str_pad($i,2,'0',STR_PAD_LEFT)?>" <?= $ay==str_pad($i,2,'0',STR_PAD_LEFT)?'selected':''?>>
                    <?=getTurkishMonthName($i)?>
                  </option>
                  <?php endfor; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-auto">
                <select name="yil" class="form-select">
                  <?php for($i=date('Y')-1;$i<=date('Y')+1;$i++): ?>
                  <option value="<?=$i?>" <?= $yil==$i?'selected':''?>><?=$i?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-auto">
                <select name="terapist_id" class="form-select">
                  <option value="">Tüm Terapistler</option>
                  <?php foreach($terapistler as $t): ?>
                  <option value="<?=$t['id']?>" <?= $terapist_id==$t['id']?'selected':''?>>
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
                        <?=$t['ad'].' '.$t['soyad']?><br>
                        <small><?=$rt?></small>
                      </th>
                    <?php endforeach; endforeach;?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $cd = new DateTime($baslangic_tarih);
                  $ed = new DateTime($bitis_tarih);
                  while($cd <= $ed):
                    $d = $cd->format('Y-m-d');
                  ?>
                  <tr>
                    <td><?=$cd->format('d.m.Y')?></td>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt):
                      $cnt=0; $names=[];
                      foreach($puantaj_kayitlari as $k){
                        if($k['tarih']==$d
                           && $k['terapist_id']==$t['id']
                           && $k['oda_tipi']==$rt) {
                          $cnt=$k['seans_sayisi'];
                          $names=explode(',',$k['danisanlar']);
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
                  <?php $cd->modify('+1 day'); endwhile; ?>

                  <tr class="table-info">
                    <td><strong><?=$view_type==='monthly'?'Aylık':'Dönem'?> Toplam</strong></td>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt):
                      $k = $t['id'].'_'.strtolower($rt);
                    ?>
                      <td class="text-center"><strong><?=$ara_toplam[$k]['seans']??0?></strong></td>
                    <?php endforeach; endforeach;?>
                  </tr>
                  <tr class="table-primary">
                    <td><strong>Yıllık Toplam</strong></td>
                    <?php foreach($display_terapistler as $t): foreach($room_types as $rt):
                      $k = $t['id'].'_'.strtolower($rt);
                    ?>
                      <td class="text-center"><strong><?=$genel_toplam[$k]['seans']??0?></strong></td>
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

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script>
    $(function(){
      $('#puantajTable, #salesYeniTable, #salesRenewTable').DataTable({
        ordering: true,
        pageLength: 100,
        searching: false
      });
      $('[data-bs-toggle="tooltip"]').each(function(){
        new bootstrap.Tooltip(this);
      });
      $('select[name="terapist_id"]').on('change', function(){
        const id = this.value;
        $('[data-terapist]').each(function(){
          $(this).toggle(!id || $(this).data('terapist') == id);
        });
      }).trigger('change');
    });
  </script>
</body>
</html>