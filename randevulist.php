<?php
// randevu_liste.php
include_once 'functions.php';
include 'partials/session.php';

// ---- Tarih aralığı (varsayılan: bu hafta) ----
function getStartOfWeekMonday($dateYmd){
  $ts = strtotime($dateYmd); $dow=(int)date('N',$ts); $mon=strtotime("-".($dow-1)." days",$ts);
  return date('Y-m-d',$mon);
}
$today = date('Y-m-d');
$start = $_GET['start'] ?? getStartOfWeekMonday($today);
$end   = $_GET['end']   ?? date('Y-m-d', strtotime($start.' +6 days'));

$filter_terapist = $_GET['terapist'] ?? '';
$filter_danisan  = $_GET['danisan']  ?? '';

// ---- Seçimler ----
$terapistler   = getTerapistler(true);
$danisanlar    = getDanisanlarWithRemainingAppointments();
$rooms         = getRooms();

// ---- Randevuları çek ----
global $pdo;
$sql = "
  SELECT
    ran.id,
    ran.randevu_tarihi,
    ran.durum,
    ran.personel_id,
    ran.danisan_id,
    ran.seans_turu_id,
    CONCAT(d.ad,' ',d.soyad)  AS danisan_adi,
    CONCAT(p.ad,' ',p.soyad)  AS terapist_adi,
    st.ad                      AS seans_turu,
    r.id                       AS room_id,
    r.name                     AS room_name,
    st.evaluation_interval,
    (
      SELECT COUNT(*)
      FROM randevular prev
      WHERE prev.danisan_id = ran.danisan_id
        AND prev.seans_turu_id = ran.seans_turu_id
        AND prev.randevu_tarihi <= ran.randevu_tarihi
        AND prev.aktif = 1
    ) AS seans_sirasi
  FROM randevular ran
  LEFT JOIN danisanlar d ON d.id = ran.danisan_id
  LEFT JOIN personel   p ON p.id = ran.personel_id
  LEFT JOIN seans_turleri st ON st.id = ran.seans_turu_id
  LEFT JOIN rooms r ON r.id = ran.room_id
  WHERE ran.aktif = 1
    AND DATE(ran.randevu_tarihi) BETWEEN :start AND :end
";
$params = [':start'=>$start, ':end'=>$end];

if ($filter_terapist !== '') { $sql .= " AND ran.personel_id = :terapist_id"; $params[':terapist_id'] = $filter_terapist; }
if ($filter_danisan  !== '') { $sql .= " AND ran.danisan_id  = :danisan_id";  $params[':danisan_id']  = $filter_danisan;  }

$sql .= " ORDER BY ran.randevu_tarihi ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// ---- Günlere göre grupla ----
$byDay = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $d = date('Y-m-d', strtotime($row['randevu_tarihi']));
  $t = date('H:i',   strtotime($row['randevu_tarihi']));
  // Değerlendirme etiketi
  $evaluation_info = '';
  if ((int)$row['evaluation_interval'] > 0) {
    $sn = (int)$row['seans_sirasi'];
    if ($sn === 1) $evaluation_info = 'İlk Değerlendirme';
    elseif ($sn % (int)$row['evaluation_interval'] === 0) $evaluation_info = floor($sn/(int)$row['evaluation_interval']).'. Değerlendirme';
  }
  $byDay[$d][] = [
    'id'            => $row['id'],
    'time'          => $t,
    'danisan'       => $row['danisan_adi'] ?? '-',
    'terapist'      => $row['terapist_adi'] ?? '-',
    'seans_turu'    => $row['seans_turu'] ?? '-',
    'room'          => $row['room_name'] ?? '-',
    'durum'         => $row['durum'] ?? 'beklemede',
    'seans_sirasi'  => (int)($row['seans_sirasi'] ?? 0),
    'evaluation'    => $evaluation_info
  ];
}
// Tarih aralığı gün listesi
$period = new DatePeriod(new DateTime($start), new DateInterval('P1D'), (new DateTime($end))->modify('+1 day'));

// Türkçe tarih başlık
function trDayLong($ymd){
  static $m=[1=>'Pazartesi',2=>'Salı',3=>'Çarşamba',4=>'Perşembe',5=>'Cuma',6=>'Cumartesi',7=>'Pazar'];
  $ts=strtotime($ymd); return $m[(int)date('N',$ts)] ?? date('l',$ts);
}
function trFullDate($ymd){
  static $ay=[1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
  $ts=strtotime($ymd); return date('j',$ts).' '.$ay[(int)date('n',$ts)].' '.date('Y',$ts);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include "partials/title-meta.php"; ?>
  <?php include 'partials/head-css.php'; ?>
  <style>
    /* Basit, mobil öncelikli liste */
    .filters{ background:#f8f9fa; border:1px solid #e9ecef; border-radius:10px; padding:14px; margin-bottom:12px; }
    .filters .row{ row-gap:10px; }
    .list-container{ max-width:100%; }
    .day-block{ border:1px solid #e9ecef; border-radius:12px; overflow:hidden; margin-bottom:12px; background:#fff; }
    .day-block > summary{ padding:12px 14px; font-weight:700; background:#f5f7fb; list-style:none; cursor:pointer; }
    .day-block[open] > summary{ border-bottom:1px solid #e9ecef; }
    .empty{ padding:12px 14px; color:#888; font-style:italic; }

    .apt-list{ list-style:none; margin:0; padding:0; }
    .apt-item{ padding:10px 12px; border-bottom:1px dashed #e8edf3; display:flex; gap:10px; align-items:flex-start; }
    .apt-item:last-child{ border-bottom:none; }
    .apt-time{ min-width:56px; font-weight:700; }
    .apt-main{ flex:1; }
    .apt-title{ font-weight:600; }
    .apt-meta{ font-size:.9rem; color:#666; margin-top:2px; }
    .tags{ margin-top:6px; display:flex; gap:6px; flex-wrap:wrap; }
    .tag{ font-size:.75rem; padding:2px 6px; border-radius:999px; background:#eef3ff; color:#334; }
    .tag.seq{ background:#f3f6ff; }
    .tag.eval{ background:#fff2e0; color:#7a4b00; }
    .tag.status-beklemede{ background:#fff6e0; color:#996b00; }
    .tag.status-onaylandi{ background:#e8f8ec; color:#176b2a; }
    .tag.status-iptal_edildi{ background:#fde8ea; color:#8a1f2a; }
    .tag.status-tamamlandi{ background:#eceff2; color:#4b5563; }
    .btn-mini{ border:none; background:none; color:#0d6efd; padding:0; font-size:.9rem; }
  </style>
</head>
<body class="list-view">
  <div class="wrapper">
    <?php include 'partials/sidenav.php'; ?>
    <?php include 'partials/topbar.php'; ?>

    <div class="page-content">
      <div class="page-container">
        <?php
          $subtitle = "Randevu Yönetimi";
          $title = "Randevu Listesi - ".trFullDate($start)." – ".trFullDate($end);
          include "partials/page-title.php";
        ?>

        <div class="card">
          <div class="card-body">
            <!-- Filtreler -->
            <form method="GET" class="filters">
              <div class="row">
                <div class="col-12 col-md-3">
                  <label class="form-label">Başlangıç</label>
                  <input type="date" name="start" class="form-control" value="<?= htmlspecialchars($start) ?>">
                </div>
                <div class="col-12 col-md-3">
                  <label class="form-label">Bitiş</label>
                  <input type="date" name="end" class="form-control" value="<?= htmlspecialchars($end) ?>">
                </div>
                <div class="col-12 col-md-3">
                  <label class="form-label">Terapist</label>
                  <select name="terapist" class="form-select">
                    <option value="">Tüm Terapistler</option>
                    <?php foreach($terapistler as $t): ?>
                      <option value="<?= $t['id'] ?>" <?= $filter_terapist==$t['id']?'selected':'' ?>>
                        <?= htmlspecialchars($t['ad'].' '.$t['soyad']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 col-md-3">
                  <label class="form-label">Danışan</label>
                  <select name="danisan" class="form-select">
                    <option value="">Tüm Danışanlar</option>
                    <?php foreach($danisanlar as $d): ?>
                      <option value="<?= $d['id'] ?>" <?= $filter_danisan==$d['id']?'selected':'' ?>>
                        <?= htmlspecialchars($d['ad'].' '.$d['soyad']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                  <button class="btn btn-primary"><i class="fas fa-filter"></i> Filtrele</button>
                  <a class="btn btn-outline-secondary" href="randevu_liste.php">Temizle</a>
                  <a class="btn btn-outline-primary" href="?start=<?= getStartOfWeekMonday($today) ?>&end=<?= date('Y-m-d', strtotime(getStartOfWeekMonday($today).' +6 days')) ?>">Bu Hafta</a>
                  <a class="btn btn-outline-info" href="?start=<?= $today ?>&end=<?= $today ?>">Bugün</a>
                </div>
              </div>
            </form>

            <!-- Liste -->
            <div class="list-container mx-auto">
              <?php foreach ($period as $dt): $day = $dt->format('Y-m-d'); ?>
                <?php
                  $items = $byDay[$day] ?? [];
                  // Saat sıralı
                  usort($items, function($a,$b){ return strcmp($a['time'],$b['time']); });
                  $open = ($day === date('Y-m-d')) ? 'open' : '';
                ?>
                <details class="day-block" <?= $open ?>>
                  <summary><?= trDayLong($day).' '.trFullDate($day); ?></summary>
                  <?php if (!$items): ?>
                    <div class="empty">Bu günde randevu yok.</div>
                  <?php else: ?>
                    <ul class="apt-list">
                      <?php foreach ($items as $apt): 
                        $st = strtolower($apt['durum']);
                        if (!in_array($st, ['beklemede','onaylandi','iptal_edildi','tamamlandi'])) $st = 'beklemede';
                      ?>
                        <li class="apt-item" onclick="handleAppointmentEdit('<?= $apt['id'] ?>')">
                          <div class="apt-time"><?= htmlspecialchars($apt['time']) ?></div>
                          <div class="apt-main">
                            <div class="apt-title">
                              <?= htmlspecialchars($apt['danisan']) ?>
                              <?php if ($apt['seans_sirasi']): ?>
                                <span class="tag seq">#<?= (int)$apt['seans_sirasi'] ?></span>
                              <?php endif; ?>
                            </div>
                            <div class="apt-meta">
                              <?= htmlspecialchars($apt['seans_turu']) ?> •
                              <?= htmlspecialchars($apt['room']) ?> •
                              <?= htmlspecialchars($apt['terapist']) ?>
                            </div>
                            <div class="tags">
                              <?php if ($apt['evaluation']): ?><span class="tag eval"><?= htmlspecialchars($apt['evaluation']) ?></span><?php endif; ?>
                              <span class="tag status-<?= $st ?>"><?= htmlspecialchars($apt['durum']) ?></span>
                            </div>
                          </div>
                          <button class="btn-mini" type="button">Düzenle</button>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </details>
              <?php endforeach; ?>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Modal: mevcut sayfalardakiyle aynı modalı ekliyoruz ki edit açılsın -->
  <div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Randevu Detayları</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Günlük/Haftalıkta kullandığın aynı içerik: kısalttım ama ID’ler aynı -->
          <ul class="nav nav-tabs mb-3" id="appointmentTabsNav" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#details" type="button">Randevu<br>Bilgileri</button></li>
            <li class="nav-item"><button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button">Randevu<br>Listesi</button></li>
            <li class="nav-item"><button class="nav-link" id="randevu-not-tab" data-bs-toggle="tab" data-bs-target="#randevu-notlari" type="button">Randevu<br>Notları</button></li>
            <li class="nav-item"><button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button">Genel<br>Notlar</button></li>
            <li class="nav-item"><button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">Ödeme<br>Geçmişi</button></li>
            <li class="nav-item" id="fonksiyonel-not-tab-li" style="display:none;"><button class="nav-link" id="fonksiyonel-notlar-tab" data-bs-toggle="tab" data-bs-target="#fonksiyonel-notlar" type="button">Fonksiyonel<br>Notlar</button></li>
          </ul>

          <div class="tab-content" id="appointmentTabs">
            <div class="tab-pane fade show active" id="details">
              <form id="appointmentForm" onsubmit="return false;">
                <input type="hidden" name="ajax_action" value="">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="danisan_id" id="danisan_id" value="">
                <input type="hidden" name="seans_turu_id" id="seans_turu_id" value="">
                <input type="hidden" name="satis_id" id="satis_id" value="">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Danışan ve Paket</label>
                      <select name="danisan_satis_id" id="danisan_satis_id" class="form-select" required>
                        <option value="">Danışan ve paket seçin...</option>
                        <?php foreach ($danisanlar as $dn): ?>
                          <option value="<?= $dn['aktif_satis_id']; ?>"
                                  data-danisan-id="<?= $dn['id']; ?>"
                                  data-seans-turu-id="<?= $dn['seans_turu_id']; ?>">
                            <?= htmlspecialchars($dn['ad_soyad']); ?> (Kalan: <?= (int)$dn['kalan_seans']; ?>)
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Terapist</label>
                      <select name="personel_id" id="personel_id" class="form-select" required>
                        <option value="">Terapist seçin...</option>
                        <?php foreach ($terapistler as $t): ?>
                          <option value="<?= $t['id']; ?>"><?= htmlspecialchars($t['ad'].' '.$t['soyad']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Oda</label>
                      <select name="room_id" id="room_id" class="form-select" required>
                        <option value="">Oda seçin...</option>
                        <?php foreach ($rooms as $r): ?>
                          <option value="<?= $r['id']; ?>"><?= htmlspecialchars($r['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Seçilen Paket</label>
                      <div id="selectedPackage" class="form-control-plaintext"><em class="text-muted">Danışan seçince görünecek</em></div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Tarih</label>
                      <input type="date" name="randevu_tarih" id="randevu_tarih" class="form-control" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label">Saat</label>
                      <select name="randevu_saat" id="randevu_saat" class="form-select" required>
                        <option value="">Saat seçin...</option>
                        <?php for($i=8;$i<=21;$i++): ?>
                          <option value="<?= sprintf('%02d:00',$i) ?>"><?= sprintf('%02d:00',$i) ?></option>
                        <?php endfor; ?>
                      </select>
                      <small class="text-muted mt-1" id="lockedTimeInfo" style="display:none;">
                        <i class="fas fa-lock text-warning"></i> Kilitli saatler seçilemez
                      </small>
                    </div>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Notlar</label>
                  <textarea name="notlar" id="notlar" class="form-control" rows="3"></textarea>
                </div>

                <div class="evaluation-notes-section" style="display:none;">
                  <div class="mb-3">
                    <label class="evaluation-notes-label form-label">Değerlendirme Notları</label>
                    <textarea name="evaluation_notes" class="form-control" rows="4" placeholder="Değerlendirme sonuçları ve öneriler..."></textarea>
                  </div>
                </div>

                <div id="appointmentDetails" class="card mt-3" style="display:none;">
                  <div class="card-header"><h6 class="mb-0">Paket Bilgileri</h6></div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6"><small class="text-muted">Toplam Seans:</small> <span id="totalSessions" class="fw-bold">0</span></div>
                      <div class="col-md-6"><small class="text-muted">Kalan Seans:</small> <span id="remainingSessions" class="fw-bold">0</span></div>
                    </div>
                    <div class="row mt-2">
                      <div class="col-md-6"><small class="text-muted">Sıradaki Seans:</small> <span id="nextSessionNumber" class="fw-bold">0</span></div>
                      <div class="col-md-6"><small class="text-muted">Ödeme Durumu:</small> <span id="paymentStatus" class="fw-bold">₺0 / ₺0</span></div>
                    </div>
                  </div>
                </div>
              </form>
            </div>

            <div class="tab-pane fade" id="appointments"><div class="table-responsive"><table class="table"><tbody id="appointmentsList"></tbody></table></div></div>
            <div class="tab-pane fade" id="randevu-notlari"><div class="table-responsive"><table class="table"><tbody id="randevuNotesList"></tbody></table></div></div>
            <div class="tab-pane fade" id="notes">
              <div class="mb-2"><textarea id="generalNoteInput" class="form-control" rows="3" placeholder="Genel not..."></textarea>
              <div class="mt-2 d-flex gap-2"><button type="button" class="btn btn-primary" onclick="submitGeneralNote()">Notu Kaydet</button>
              <small class="text-muted" id="generalNoteHint">Önce danışanı seçmelisiniz.</small></div></div>
              <div class="table-responsive"><table class="table"><tbody id="notesList"></tbody></table></div>
            </div>
            <div class="tab-pane fade" id="payments"><div class="table-responsive"><table class="table"><tbody id="paymentsList"></tbody></table></div></div>
            <div class="tab-pane fade" id="fonksiyonel-notlar"><div class="table-responsive"><table class="table"><tbody id="fonksiyonelNotesList"></tbody></table></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="button" class="btn btn-danger" id="deleteAppointmentBtn" style="display:none;" onclick="deleteCurrentAppointment()">Sil</button>
          <button type="button" class="btn btn-primary" onclick="saveAppointment()">Kaydet</button>
        </div>
      </div>
    </div>
  </div>

  <?php include 'partials/footer-scripts.php'; ?>
  <!-- Mevcut modal/işlevler için JS (günlük/haftalık dosyalarını reuse ediyoruz) -->
  <script src="assets/js/RoomSchedule.js"></script>
  <script src="assets/js/RoomScheduleWeekly.js"></script>
</body>
</html>
