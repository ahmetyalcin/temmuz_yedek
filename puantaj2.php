<?php
session_start();
require_once 'functions.php';

// Mevcut verileri çek
$danisanlar        = getDanisanlar();
$randevular        = getRandevular();
$seansTurleri      = getSeansTurleri();
$uyelikTurleri     = getUyelikTurleri();
$hizmetPaketleri   = getHizmetPaketleri();
$sponsorluklar     = getSponsorluklar();
$terapistler       = getTerapistler();
$aktif_terapistler = getTerapistler(true);

// Tarih filtreleri
$ay           = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil          = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$terapist_id  = isset($_GET['terapist_id']) ? $_GET['terapist_id'] : null;
$view_type    = isset($_GET['view']) ? $_GET['view'] : 'monthly';

$baslangic_tarih = "$yil-$ay-01";
$bitis_tarih     = date('Y-m-t', strtotime($baslangic_tarih));
if ($view_type === 'yearly') {
    $baslangic_tarih = "$yil-01-01";
    $bitis_tarih     = "$yil-12-31";
}

// Oda tiplerini çek
$sql_room_types = "
    SELECT DISTINCT rm.type
    FROM rooms rm
    JOIN randevular r ON r.room_id = rm.id
    WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
      AND r.aktif = 1
      AND r.durum != 'iptal_edildi'
";
if ($terapist_id) {
    $sql_room_types .= " AND r.personel_id = :terapist_id";
}
$sql_room_types .= " ORDER BY rm.type";

try {
    $stmt = $pdo->prepare($sql_room_types);
    $params = [':baslangic'=>$baslangic_tarih, ':bitis'=>$bitis_tarih];
    if ($terapist_id) $params[':terapist_id'] = $terapist_id;
    $stmt->execute($params);
    $room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    error_log("Oda tipleri getirme hatası: " . $e->getMessage());
    $room_types = [];
}

// Randevu puantaj verileri
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
    JOIN rooms rm ON rm.id = r.room_id
    JOIN danisanlar d ON d.id = r.danisan_id
    WHERE DATE(r.randevu_tarihi) BETWEEN :baslangic AND :bitis
      AND r.aktif = 1
      AND r.durum != 'iptal_edildi'
";
if ($terapist_id) {
    $sql .= " AND p.id = :terapist_id";
}
$sql .= "
    GROUP BY DATE(r.randevu_tarihi), p.id, rm.type
    ORDER BY tarih, terapist_adi
";

try {
    $stmt = $pdo->prepare($sql);
    $params = [':baslangic'=>$baslangic_tarih, ':bitis'=>$bitis_tarih];
    if ($terapist_id) $params[':terapist_id'] = $terapist_id;
    $stmt->execute($params);
    $puantaj_kayitlari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Puantaj verileri getirme hatası: " . $e->getMessage());
    $puantaj_kayitlari = [];
}

// --- Satış puantajı için sorgular ---

// Alt sorgu: her danışanın ilk satış tarihi
$firstSaleSubquery = "
    (SELECT danisan_id, MIN(olusturma_tarihi) AS first_date
     FROM satislar
     WHERE aktif = 1
     GROUP BY danisan_id
    ) AS fs
";

// Yeni müşteri satışları
$sqlYeniSales = "
    SELECT
        s.id,
        CONCAT(d.ad,' ',d.soyad) AS danisan_ad,
        CONCAT(p.ad,' ',p.soyad) AS terapist_ad,
        h.ad AS paket_ad,
        s.toplam_tutar,
        s.olusturma_tarihi
    FROM satislar s
    JOIN $firstSaleSubquery ON fs.danisan_id = s.danisan_id
    JOIN danisanlar d    ON d.id        = s.danisan_id
    JOIN personel p      ON p.id        = s.personel_id
    JOIN hizmet_paketleri h ON h.id     = s.hizmet_paketi_id
    WHERE s.aktif = 1
      AND s.olusturma_tarihi BETWEEN :baslangic AND :bitis
      AND s.olusturma_tarihi = fs.first_date
";
if ($terapist_id) {
    $sqlYeniSales .= " AND s.personel_id = :terapist_id";
}
$sqlYeniSales .= " ORDER BY s.olusturma_tarihi DESC";

$stmt = $pdo->prepare($sqlYeniSales);
$params = [':baslangic'=>$baslangic_tarih, ':bitis'=>$bitis_tarih];
if ($terapist_id) $params[':terapist_id'] = $terapist_id;
$stmt->execute($params);
$yeniSatislar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Yenileme paket satışları
$sqlRenewSales = "
    SELECT
        s.id,
        CONCAT(d.ad,' ',d.soyad) AS danisan_ad,
        CONCAT(p.ad,' ',p.soyad) AS terapist_ad,
        h.ad AS paket_ad,
        s.toplam_tutar,
        s.olusturma_tarihi
    FROM satislar s
    JOIN $firstSaleSubquery ON fs.danisan_id = s.danisan_id
    JOIN danisanlar d    ON d.id        = s.danisan_id
    JOIN personel p      ON p.id        = s.personel_id
    JOIN hizmet_paketleri h ON h.id     = s.hizmet_paketi_id
    WHERE s.aktif = 1
      AND s.olusturma_tarihi BETWEEN :baslangic AND :bitis
      AND s.olusturma_tarihi <> fs.first_date
";
if ($terapist_id) {
    $sqlRenewSales .= " AND s.personel_id = :terapist_id";
}
$sqlRenewSales .= " ORDER BY s.olusturma_tarihi DESC";

$stmt = $pdo->prepare($sqlRenewSales);
$stmt->execute($params);
$yenilemeSatislar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Excel export ve toplam hesapları (mevcut kodlarınızı aynen kullanın)

// Ara toplam ve genel toplam
$ara_toplam   = [];
$genel_toplam = [];
foreach ($puantaj_kayitlari as $kayit) {
    $key = $kayit['terapist_id'].'_'.strtolower($kayit['oda_tipi']);
    if (!isset($ara_toplam[$key])) {
        $ara_toplam[$key] = ['seans'=>0,'danisanlar'=>[]];
    }
    $ara_toplam[$key]['seans'] += $kayit['seans_sayisi'];
    $ara_toplam[$key]['danisanlar'] = array_merge(
        $ara_toplam[$key]['danisanlar'],
        explode(',', $kayit['danisanlar'])
    );
    if (!isset($genel_toplam[$key])) {
        $genel_toplam[$key] = ['seans'=>0,'danisanlar'=>[]];
    }
    $genel_toplam[$key]['seans'] += $kayit['seans_sayisi'];
    $genel_toplam[$key]['danisanlar'] = array_merge(
        $genel_toplam[$key]['danisanlar'],
        explode(',', $kayit['danisanlar'])
    );
}

// Türkçe ay isimleri
function getTurkishMonthName($month) {
    $m = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',
          7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
    return $m[(int)$month];
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php"; ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" />
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
                $subtitle = "Gelirler & Performans";
                $title    = "Fizyoterapi Puantaj Tablosu";
                include "partials/page-title.php";
                ?>

                <!-- Filtre Formu -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3" id="filterForm">
                            <div class="col-auto">
                                <select name="view" class="form-select" onchange="this.form.submit()">
                                    <option value="monthly" <?= $view_type==='monthly'?'selected':'' ?>>Aylık Görünüm</option>
                                    <option value="yearly"  <?= $view_type==='yearly' ?'selected':'' ?>>Yıllık Görünüm</option>
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

                <!-- Randevu Puantaj Tablosu (mevcut) -->
                <div class="card mb-5">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover" id="puantajTable">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <?php foreach($terapistler as $t): foreach($room_types as $rt): ?>
                                        <th class="text-center" data-terapist="<?=$t['id']?>">
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
                                        $dt = $cd->format('Y-m-d');
                                    ?>
                                    <tr>
                                        <td><?=$cd->format('d.m.Y')?></td>
                                        <?php foreach($terapistler as $t): foreach($room_types as $rt): ?>
                                        <td class="text-center position-relative" data-terapist="<?=$t['id']?>" data-bs-toggle="tooltip" title="<?php
                                            $cnt=0; $lis=[];
                                            foreach($puantaj_kayitlari as $k){
                                                if($k['tarih']==$dt && $k['terapist_id']==$t['id'] && $k['oda_tipi']==$rt){
                                                    $cnt=$k['seans_sayisi'];
                                                    $lis=explode(',',$k['danisanlar']);
                                                    break;
                                                }
                                            }
                                            echo implode(', ', array_unique($lis));
                                        ?>">
                                            <?=$cnt? '<span class="badge bg-primary">'.$cnt.'</span>':''?>
                                        </td>
                                        <?php endforeach; endforeach;?>
                                    </tr>
                                    <?php $cd->modify('+1 day'); endwhile; ?>

                                    <!-- Ara Toplam -->
                                    <tr class="table-info">
                                        <td><strong><?= $view_type==='monthly'?'Aylık':'Dönem'?> Toplam</strong></td>
                                        <?php foreach($terapistler as $t): foreach($room_types as $rt):
                                            $k = $t['id'].'_'.strtolower($rt);
                                        ?>
                                        <td class="text-center"><strong><?= $ara_toplam[$k]['seans'] ?? 0 ?></strong></td>
                                        <?php endforeach; endforeach;?>
                                    </tr>
                                    <!-- Genel Toplam -->
                                    <tr class="table-primary">
                                        <td><strong>Yıllık Toplam</strong></td>
                                        <?php foreach($terapistler as $t): foreach($room_types as $rt):
                                            $k = $t['id'].'_'.strtolower($rt);
                                        ?>
                                        <td class="text-center"><strong><?= $genel_toplam[$k]['seans'] ?? 0 ?></strong></td>
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
                                    <th>Satış ID</th>
                                    <th>Müşteri</th>
                                    <th>Terapist</th>
                                    <th>Paket</th>
                                    <th>Tutar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($yeniSatislar as $s): ?>
                                <tr>
                                    <td><?=htmlspecialchars($s['id'])?></td>
                                    <td><?=htmlspecialchars($s['danisan_ad'])?></td>
                                    <td><?=htmlspecialchars($s['terapist_ad'])?></td>
                                    <td><?=htmlspecialchars($s['paket_ad'])?></td>
                                    <td><?=number_format($s['toplam_tutar'],2,',','.')?> ₺</td>
                                    <td><?=htmlspecialchars($s['olusturma_tarihi'])?></td>
                                </tr>
                                <?php endforeach; ?>
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
                                    <th>Satış ID</th>
                                    <th>Müşteri</th>
                                    <th>Terapist</th>
                                    <th>Paket</th>
                                    <th>Tutar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($yenilemeSatislar as $s): ?>
                                <tr>
                                    <td><?=htmlspecialchars($s['id'])?></td>
                                    <td><?=htmlspecialchars($s['danisan_ad'])?></td>
                                    <td><?=htmlspecialchars($s['terapist_ad'])?></td>
                                    <td><?=htmlspecialchars($s['paket_ad'])?></td>
                                    <td><?=number_format($s['toplam_tutar'],2,',','.')?> ₺</td>
                                    <td><?=htmlspecialchars($s['olusturma_tarihi'])?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> <!-- page-container -->
        </div> <!-- page-content -->
    </div> <!-- wrapper -->

    <?php include 'partials/customizer.php'; ?>
    <?php include 'partials/footer-scripts.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
    $(function(){
        $('#puantajTable, #salesYeniTable, #salesRenewTable').DataTable({ order:[[5,'desc']] });
        var tips = [].slice.call($('[data-bs-toggle="tooltip"]'));
        tips.map(function(el){ return new bootstrap.Tooltip(el); });
        $('select[name="terapist_id"]').on('change',function(){
            var id=this.value;
            $('[data-terapist]').each(function(){
                $(this).toggle(!id||$(this).data('terapist')==id);
            });
        }).trigger('change');
    });
    </script>
</body>
</html>
