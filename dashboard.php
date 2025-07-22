<?php
// dashboard.php

// 1) Oturum ve fonksiyonlar
session_start();
require __DIR__ . '/functions.php';
require __DIR__ . '/con/db.php';

// 2) Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 3) Verileri çek
$danisanlar      = getDanisanlar();
$randevular      = getRandevular();
$seansTurleri    = getSeansTurleri();
$hizmetPaketleri = getHizmetPaketleri();

// 4) İstatistik hesaplamaları
$total_danisan = count($danisanlar);
$aylik_randevu = !empty($randevular)
    ? count(array_filter($randevular, function($r) {
        return date('Y-m') === date('Y-m', strtotime($r['randevu_tarihi']));
    }))
    : 0;
$aktif_paket = !empty($hizmetPaketleri)
    ? count(array_filter($hizmetPaketleri, function($p) {
        return (bool)$p['aktif'];
    }))
    : 0;
$ortalama_seans = !empty($seansTurleri)
    ? round(array_sum(array_column($seansTurleri, 'sure')) / count($seansTurleri))
    : 0;

// 5) Performans & bildirim metrikleri
$danisan_artis        = getDanisanArtisOrani();
$iptal_orani          = getRandevuIptalOrani();
$paket_yenileme_orani = getPaketYenilemeOrani();

$yarinki_randevu = getYarinkiRandevuSayisi();
$biten_paket     = getBitenPaketSayisi();
$geri_arama      = getGeriAramaBekleyenler();

// 6) Bugünün fix talepleri
$gunler = [
    'Monday'=>'Pazartesi','Tuesday'=>'Salı','Wednesday'=>'Çarşamba',
    'Thursday'=>'Perşembe','Friday'=>'Cuma','Saturday'=>'Cumartesi','Sunday'=>'Pazar'
];
$bugun = $gunler[date('l')] ?? '';
$sql = "SELECT ft.*, d.ad AS danisan_adi, d.soyad AS danisan_soyadi
        FROM fix_talepler ft
        JOIN danisanlar d ON d.id = ft.danisan_id
        WHERE ft.aktif = 1 AND ft.gun = ?
        ORDER BY ft.saat";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bugun]);
    $bugunun_fix_talepleri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Fix talepleri getirme hatası: ' . $e->getMessage());
    $bugunun_fix_talepleri = [];
}

// 7) Sayfa başlıkları
$title    = 'Dashboard';
$subtitle = 'Yönetim';

// 8) Ortak header (içinde <head>…</body> açılışı var)
include __DIR__ . '/partials/header.php';
?>

<!-- 9) Ekstra CSS / İkon kütüphaneleri -->
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />

<div class="wrapper">
  <div class="page-content">
    <div class="page-container">
      <?php include __DIR__ . '/partials/page-title.php'; ?>

      <div class="container-fluid">

        <!-- İstatistik Kartları -->
        <div class="row g-4 mb-4">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bx bx-user fs-3 text-primary"></i>
                <div class="ms-3">
                  <h6 class="mb-0">Toplam Danışan</h6>
                  <h2 class="mb-0"><?= $total_danisan ?></h2>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bx bx-calendar fs-3 text-success"></i>
                <div class="ms-3">
                  <h6 class="mb-0">Aylık Randevu</h6>
                  <h2 class="mb-0"><?= $aylik_randevu ?></h2>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bx bx-package fs-3 text-info"></i>
                <div class="ms-3">
                  <h6 class="mb-0">Aktif Paketler</h6>
                  <h2 class="mb-0"><?= $aktif_paket ?></h2>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
              <div class="card-body d-flex align-items-center">
                <i class="bx bx-time fs-3 text-warning"></i>
                <div class="ms-3">
                  <h6 class="mb-0">Ortalama Seans</h6>
                  <h2 class="mb-0"><?= $ortalama_seans ?> dk</h2>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <!-- Performans Metrikleri -->
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header"><h5 class="mb-0">Performans Metrikleri</h5></div>
              <div class="card-body">
                <div class="mb-4">
                  <div class="d-flex justify-content-between">
                    <span>Yeni Danışan Oranı</span>
                    <span class="<?= $danisan_artis>=0?'text-success':'text-danger' ?>">
                      <i class="bx bx-trending-<?= $danisan_artis>=0?'up':'down' ?>"></i>
                      <?= abs($danisan_artis) ?>%
                    </span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar <?= $danisan_artis>=0?'bg-success':'bg-danger' ?>"
                         style="width:<?= abs($danisan_artis) ?>%"></div>
                  </div>
                </div>
                <div class="mb-4">
                  <div class="d-flex justify-content-between">
                    <span>İptal Oranı</span>
                    <span class="<?= $iptal_orani<=10?'text-success':'text-danger' ?>">
                      <i class="bx bx-trending-<?= $iptal_orani<=10?'down':'up' ?>"></i>
                      <?= $iptal_orani ?>%
                    </span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar <?= $iptal_orani<=10?'bg-success':'bg-danger' ?>"
                         style="width:<?= $iptal_orani ?>%"></div>
                  </div>
                </div>
                <div>
                  <div class="d-flex justify-content-between">
                    <span>Paket Yenileme</span>
                    <span class="<?= $paket_yenileme_orani>=50?'text-success':'text-danger' ?>">
                      <i class="bx bx-trending-<?= $paket_yenileme_orani>=50?'up':'down' ?>"></i>
                      <?= $paket_yenileme_orani ?>%
                    </span>
                  </div>
                  <div class="progress">
                    <div class="progress-bar <?= $paket_yenileme_orani>=50?'bg-success':'bg-danger' ?>"
                         style="width:<?= $paket_yenileme_orani ?>%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bugünün Fix Talepleri -->
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bugünün Fix Talepleri</h5>
                <a href="?page=randevular&tab=fix_talepler" class="btn btn-primary btn-sm">Tümünü Gör</a>
              </div>
              <div class="card-body">
                <?php if (empty($bugunun_fix_talepleri)): ?>
                  <div class="text-center text-muted py-4">
                    <i class="bx bx-calendar-check fs-1"></i>
                    <p class="mt-2">Bugün için fix talep bulunmuyor.</p>
                  </div>
                <?php else: ?>
                  <div class="timeline">
                    <?php foreach ($bugunun_fix_talepleri as $talep): ?>
                      <div class="d-flex mb-3">
                        <div class="me-3">
                          <div class="p-2 bg-light rounded text-primary">
                            <?= substr($talep['saat'], 0, 5) ?>
                          </div>
                        </div>
                        <div>
                          <h6><?= $talep['danisan_adi'].' '.$talep['danisan_soyadi'] ?></h6>
                          <small class="text-muted">
                            <i class="bx bx-refresh me-1"></i><?= $talep['tekrar_tipi'] ?>
                            <?php if ($talep['notlar']): ?>
                              <br><i class="bx bx-note me-1"></i><?= $talep['notlar'] ?>
                            <?php endif; ?>
                          </small>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Bildirimler -->
          <div class="col-12 col-lg-4">
            <div class="card h-100">
              <div class="card-header"><h5 class="mb-0">Bildirimler</h5></div>
              <div class="card-body">
                <?php if ($yarinki_randevu): ?>
                  <div class="d-flex align-items-start mb-4">
                    <div class="p-2 bg-primary bg-opacity-10 rounded text-primary">
                      <i class="bx bx-calendar fs-4"></i>
                    </div>
                    <div class="ms-3">
                      <h6>Yarın <?= $yarinki_randevu ?> randevunuz var</h6>
                      <small class="text-muted">5 dakika önce</small>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if ($biten_paket): ?>
                  <div class="d-flex align-items-start mb-4">
                    <div class="p-2 bg-warning bg-opacity-10 rounded text-warning">
                      <i class="bx bx-package fs-4"></i>
                    </div>
                    <div class="ms-3">
                      <h6><?= $biten_paket ?> danışanın paketi bu hafta bitiyor</h6>
                      <small class="text-muted">1 saat önce</small>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if ($geri_arama): ?>
                  <div class="d-flex align-items-start">
                    <div class="p-2 bg-info bg-opacity-10 rounded text-info">
                      <i class="bx bx-phone fs-4"></i>
                    </div>
                    <div class="ms-3">
                      <h6><?= $geri_arama ?> danışan geri arama bekliyor</h6>
                      <small class="text-muted">2 saat önce</small>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if (!$yarinki_randevu && !$biten_paket && !$geri_arama): ?>
                  <div class="text-center text-muted py-4">
                    <i class="bx bx-check-circle fs-1"></i>
                    <p class="mt-2">Şu anda bildirim bulunmuyor</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Yaklaşan Randevular -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Yaklaşan Randevular</h5>
            <a href="?page=randevular" class="btn btn-primary btn-sm">Tümünü Gör</a>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="upcomingTable" class="table table-hover">
                <thead>
                  <tr>
                    <th>Danışan</th>
                    <th>Terapist</th>
                    <th>Seans</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($randevular)): ?>
                    <?php foreach (array_slice($randevular, 0, 5) as $r): ?>
                      <tr>
                        <td><?= htmlspecialchars($r['danisan_adi']) ?></td>
                        <td><?= htmlspecialchars($r['personel_adi']) ?></td>
                        <td><?= htmlspecialchars($r['seans_turu']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($r['randevu_tarihi'])) ?></td>
                        <td>
                          <span class="badge bg-<?= getStatusBadgeClass($r['durum']) ?>">
                            <?= ucfirst(htmlspecialchars($r['durum'])) ?>
                          </span>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <a href="?page=randevular&action=edit&id=<?= $r['id'] ?>" class="btn btn-primary">
                              <i class="bx bx-edit"></i>
                            </a>
                            <button class="btn btn-danger"
                                    onclick="if(confirm('Bu randevuyu iptal etmek istediğinizden emin misiniz?'))
                                             location.href='?page=randevular&action=cancel&id=<?= $r['id'] ?>'">
                              <i class="bx bx-x"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="text-center">Yaklaşan randevu bulunmuyor.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div> <!-- /.container-fluid -->
    </div> <!-- /.page-container -->
  </div> <!-- /.page-content -->
</div> <!-- /.wrapper -->

<?php
// 10) Ortak footer (kapanış </body></html>)
include __DIR__ . '/partials/footer.php';
?>

<!-- 11) Ekstra JS kütüphaneleri -->
<script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
<script>lucide.replace()</script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function() {
    $('#upcomingTable').DataTable({
      language: {
        url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
      }
    });
  });
</script>
