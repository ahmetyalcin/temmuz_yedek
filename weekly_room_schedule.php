<?php
include_once 'functions.php';
include 'partials/session.php';

// ==============================
// DATE / WEEK HELPERS
// ==============================
$current_date = $_GET['date'] ?? date('Y-m-d');

function getStartOfWeekMonday($dateYmd) {
    // ISO: 1=Mon ... 7=Sun
    $ts = strtotime($dateYmd);
    $dow = (int)date('N', $ts);
    $monday = strtotime("-" . ($dow - 1) . " days", $ts);
    return date('Y-m-d', $monday);
}
function getWeekDays($mondayYmd) {
    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $days[] = date('Y-m-d', strtotime("+$i days", strtotime($mondayYmd)));
    }
    return $days;
}
function trDayShort($ymd) {
    static $ay = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
    $ts = strtotime($ymd);
    return date('j',$ts).' '.$ay[(int)date('n',$ts)].' '.date('Y',$ts);
}

function trDayLong($ymd){
    static $map = [1=>'Pazartesi',2=>'Salı',3=>'Çarşamba',4=>'Perşembe',5=>'Cuma',6=>'Cumartesi',7=>'Pazar'];
    $ts = strtotime($ymd);
    return $map[(int)date('N',$ts)] ?? date('l',$ts);
}
function trFullDate($ymd){
    static $ay = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
    $ts = strtotime($ymd);
    return date('j',$ts).' '.$ay[(int)date('n',$ts)].' '.date('Y',$ts);
}


$week_start = getStartOfWeekMonday($current_date);
$week_days  = getWeekDays($week_start);
$week_end   = end($week_days);
reset($week_days);

// DATA
$rooms         = getRooms();
$terapistler   = getTerapistler(true);
$danisanlar    = getDanisanlarWithRemainingAppointments();
$seans_turleri = getSeansTurleri();

$filter_terapist = $_GET['terapist'] ?? '';
$filter_danisan  = $_GET['danisan'] ?? '';

// DEBUG (isteğe bağlı)
// error_log("WEEK: $week_start -> $week_end");

// ==============================
// LOCKS – haftaya göre topla
// ==============================
function getWeekLockedTimesMap($week_days) {
    $all = [];
    foreach ($week_days as $d) {
        $locks = getTumKilitliSaatler($d); // mevcut fonksiyon tek güne çalışıyor
        // normalize
        $all[$d] = $locks ?: [];
    }
    return $all;
}
$kilitli_saatler_hafta = getWeekLockedTimesMap($week_days);

// ==============================
// SCHEDULE – haftalık çek
// ==============================
function getFilteredWeeklySchedule($startDate, $endDate, $terapist_id = null, $danisan_id = null) {
    global $pdo;

    // Odaları ver
    $rooms_sql  = "SELECT * FROM rooms WHERE aktif = TRUE ORDER BY type, name";
    $rooms_stmt = $pdo->query($rooms_sql);
    $rooms      = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Haftalık randevuları çek
    $sql = "
        SELECT 
            r.id    AS room_id,
            r.name  AS room_name,
            r.type  AS room_type,

            ran.id  AS randevu_id,
            ran.randevu_tarihi,
            ran.durum,
            ran.evaluation_type,
            ran.evaluation_notes,
            ran.personel_id,
            ran.danisan_id,
            ran.seans_turu_id,

            CONCAT(d.ad, ' ', d.soyad) AS danisan_adi,
            CONCAT(p.ad, ' ', p.soyad) AS terapist_adi,

            st.ad AS seans_turu,
            st.evaluation_interval,
            st.sure,

            (
                SELECT COUNT(*) 
                FROM randevular prev 
                WHERE prev.danisan_id = ran.danisan_id 
                  AND prev.seans_turu_id = ran.seans_turu_id
                  AND prev.randevu_tarihi <= ran.randevu_tarihi 
                  AND prev.aktif = 1
            ) AS seans_sirasi
        FROM rooms r
        LEFT JOIN randevular ran 
               ON ran.room_id = r.id
              AND DATE(ran.randevu_tarihi) BETWEEN :start_date AND :end_date
              AND ran.aktif = 1
        LEFT JOIN danisanlar d ON d.id = ran.danisan_id
        LEFT JOIN personel   p ON p.id = ran.personel_id
        LEFT JOIN seans_turleri st ON st.id = ran.seans_turu_id
        WHERE r.aktif = 1
    ";

    $params = [
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ];

    if (!empty($terapist_id)) {
        $sql .= " AND (ran.personel_id = :terapist_id OR ran.personel_id IS NULL)";
        $params[':terapist_id'] = $terapist_id;
    }
    if (!empty($danisan_id)) {
        $sql .= " AND (ran.danisan_id = :danisan_id OR ran.danisan_id IS NULL)";
        $params[':danisan_id'] = $danisan_id;
    }

    $sql .= " ORDER BY r.type, r.name, ran.randevu_tarihi ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Yapı: $schedule[YYYY-mm-dd][room_id]['room_info'|appointments][time]
    $schedule = [];

    // Tüm odaları gün/gün boş yapı ile hazırla (kolonlarda + butonu görünsün)
    $period = new DatePeriod(new DateTime($startDate), new DateInterval('P1D'), (new DateTime($endDate))->modify('+1 day'));
    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        foreach ($rooms as $room) {
            if (!isset($schedule[$d][$room['id']])) {
                $schedule[$d][$room['id']] = [
                    'room_info'   => ['id'=>$room['id'],'name'=>$room['name'],'type'=>$room['type']],
                    'appointments'=> []
                ];
            }
        }
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$row['randevu_id']) continue; // bu odada o gün randevu yoksa

        $date_str = date('Y-m-d', strtotime($row['randevu_tarihi']));
        $time_str = date('H:i',   strtotime($row['randevu_tarihi']));
        $room_id  = $row['room_id'];

        // evaluation info
        $evaluation_info   = '';
        $evaluation_type   = '';
        $evaluation_number = null;

        $interval = (int)($row['evaluation_interval'] ?? 0);
        if ($interval > 0) {
            $session_number = (int)$row['seans_sirasi'];
            if ($session_number === 1) {
                $evaluation_type = 'initial';
                $evaluation_info = 'İlk Değerlendirme';
            } elseif ($session_number % $interval === 0) {
                $evaluation_type   = 'progress';
                $evaluation_number = (int)floor($session_number / $interval);
                $evaluation_info   = $evaluation_number . '. Değerlendirme';
            }
        }

        $schedule[$date_str][$room_id]['appointments'][$time_str] = [
            'id'                 => $row['randevu_id'],
            'danisan'            => $row['danisan_adi'],
            'terapist'           => $row['terapist_adi'],
            'seans_turu'         => $row['seans_turu'],
            'durum'              => $row['durum'],
            'evaluation_type'    => $evaluation_type,
            'evaluation_number'  => $evaluation_number,
            'evaluation_notes'   => $row['evaluation_notes'],
            'sure'               => $row['sure'],
            'seans_sirasi'       => $row['seans_sirasi'],
            'evaluation_info'    => $evaluation_info,
            'personel_id'        => $row['personel_id'],
            'danisan_id'         => $row['danisan_id'],
            'seans_turu_id'      => $row['seans_turu_id'],
            'paket_adi'          => $row['seans_turu'] ?? null // gösterimde lazım olabilir
        ];
    }

    return $schedule;
}

$schedule = getFilteredWeeklySchedule($week_start, $week_end, $filter_terapist, $filter_danisan);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php"; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>


/* Gün şeritleri ve ayraçlar */
.room-schedule .day-alt th,
.room-schedule .day-alt .room-cell:not(.locked) {
  background: #fbfdff; /* çok hafif açık ton */
}

/* Hover’da da açık kalsın ama belirginleşsin */
.room-schedule .day-alt .room-cell:not(.locked):hover {
  background: #eef5ff;
}

/* Gün başı ayraç (her günün ilk oda kolonu) */
.room-schedule .day-start {
  border-left: 3px solid #d1e3f8 !important;
}

.room-schedule-top-scroll{
  position: sticky; /* sayfa kayarken tepede dursun */
  top: 0;
  background: #fff;
  z-index: 20;           /* tablo başlıklarının üstünde olsun */
  border-bottom: 1px solid #e0e0e0;
  height: 14px;          /* görünür olsun */
  margin-bottom: 6px;
  overflow-x: auto;
  overflow-y: hidden;
}
.room-schedule-top-scroll::-webkit-scrollbar{ height: 10px; }
.room-schedule-top-scroll-inner{ height:1px; }

/* wrapper sadece düzen için (opsiyonel) */
.room-schedule-wrap{ position: relative; }


        .filter-section { background:#f8f9fa;border-radius:8px;padding:20px;margin-bottom:20px;border:1px solid #e9ecef; }
        .filter-row { display:flex;align-items:end;gap:15px;flex-wrap:wrap; }
        .filter-group { flex:1;min-width:200px; }
        .filter-group label { font-weight:600;margin-bottom:5px;display:block;color:#495057; }
        .filter-buttons { display:flex;gap:10px;align-items:end; }
        .active-filters { margin-top:15px;padding-top:15px;border-top:1px solid #dee2e6; }
        .filter-tag { display:inline-block;background:#007bff;color:#fff;padding:4px 8px;border-radius:12px;font-size:.85rem;margin-right:8px;margin-bottom:5px; }
        .filter-tag .remove { margin-left:5px;cursor:pointer;font-weight:bold; }

        .navigation { display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:1rem; }
        .date-navigation,.controls-group { display:flex;align-items:center;gap:.5rem; }
        @media (max-width:768px){
            .navigation{flex-direction:column;align-items:stretch;}
            .date-navigation,.controls-group{width:100%;justify-content:flex-start;}
        }

        .room-schedule { overflow-x:auto;-webkit-overflow-scrolling:touch;background:#fff;border-radius:8px;padding:0;box-shadow:0 2px 8px rgba(0,0,0,.1); }
        .room-schedule table { border-collapse:collapse;min-width:100%; }
        .room-schedule th, .room-schedule td { border:1px solid #e0e0e0 !important;padding:.6rem;vertical-align:top; }
        .room-schedule th.time-column, .room-schedule td.time-column { position:sticky;left:0;background:#f8f9fa;z-index:11;font-weight:600;min-width:80px; }
        .room-schedule thead th.day-col { text-align:center;background:#f0f3f6;font-weight:700; }

        .room-cell{ position:relative; min-height:60px; background:#fff; cursor:pointer; transition:background-color .2s; }
        .room-cell.locked{ background:#6c757d !important;color:#fff; cursor:not-allowed; position:relative; }
        .room-cell.locked::before{ content:"🔒"; position:absolute; top:6px; right:6px; font-size:12px; opacity:.85; }
        .room-cell.locked .lock-info{ position:absolute; bottom:2px; left:2px; font-size:10px; opacity:.9; background:rgba(0,0,0,.2); padding:1px 3px; border-radius:2px; }
        .room-cell:not(.locked):hover{ background:#e9ecef; }
        .room-cell.drag-over:not(.locked){ background:#cce5ff; border:2px dashed #007bff; }

        .appointment{ background:linear-gradient(135deg,#4a90e2,#357abd); color:#fff; padding:6px 8px; border-radius:6px; margin:2px 0; font-size:.85rem; line-height:1.2; box-shadow:0 2px 4px rgba(0,0,0,.1); cursor:grab; transition:transform .2s, box-shadow .2s; }
        .appointment:hover{ transform:translateY(-1px); box-shadow:0 4px 8px rgba(0,0,0,.15); }
        .appointment.dragging{ opacity:.7; transform:rotate(5deg); cursor:grabbing; }
        .appointment-time{ font-weight:600; font-size:.8rem; opacity:.9; }
        .appointment-client{ font-weight:500; margin:2px 0; }
        .appointment-details{ font-size:.75rem; opacity:.85; margin-top:2px; }
        .session-info{ font-size:.7rem; opacity:.9; margin-top:2px; background:rgba(255,255,255,.2); padding:1px 4px; border-radius:3px; }

        .evaluation-appointment{ background:linear-gradient(135deg,#ff9800,#f57c00)!important; }
        .evaluation-appointment.initial{ background:linear-gradient(135deg,#4caf50,#388e3c)!important; }
        .badge{ display:inline-block;padding:2px 6px;font-size:.65rem;font-weight:500;border-radius:3px;margin-top:2px; }
        .badge-initial,.badge-evaluation{ background:rgba(255,255,255,.3); color:#fff; }

        .appointment.beklemede{ background:linear-gradient(135deg,#ffc107,#ff8f00); }
        .appointment.onaylandi{ background:linear-gradient(135deg,#28a745,#1e7e34); }
        .appointment.iptal_edildi{ background:linear-gradient(135deg,#dc3545,#c82333); opacity:.7; }
        .appointment.tamamlandi{ background:linear-gradient(135deg,#6c757d,#5a6268); }

        .add-appointment-btn{ position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:30px; height:30px; border:2px dashed #ccc; background:transparent; color:#666; border-radius:50%; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s; cursor:pointer; }
        .room-cell:not(.locked):hover .add-appointment-btn{ opacity:1; }
        .add-appointment-btn:hover{ border-color:#007bff;color:#007bff;background:rgba(0,123,255,.1); }

        .lock-management-btn{ background:linear-gradient(135deg,#6c757d,#5a6268); border:none; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:.9rem; transition:transform .2s; }
        .lock-management-btn:hover{ transform:translateY(-1px); color:#fff; text-decoration:none; }
        .lock-management-btn i{ margin-right:5px; }

        .locked-time-option{ background-color:#e9ecef !important; color:#6c757d !important; cursor:not-allowed !important; }

        @media (max-width:768px){
            .room-schedule th, .room-schedule td{ padding:.45rem; font-size:.82rem; }
            .appointment{ padding:4px 6px; font-size:.75rem; }
            .add-appointment-btn{ width:25px; height:25px; }
        }

        .general-note-box{ border:1px solid #e9ecef; border-radius:8px; padding:12px; margin-bottom:12px; background:#f8f9fb; }
        .tabs-hidden{ display:none !important; }
    </style>
</head>

<body class="weekly-view">
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Randevu Yönetimi";
                $title = "Haftalık Oda Programı - " . date('d.m.Y', strtotime($week_start)) . " – " . date('d.m.Y', strtotime($week_end));
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

                                <!-- Filtreleme -->
                                <div class="filter-section">
                                    <form method="GET" id="filterForm">
                                        <input type="hidden" name="page" value="weekly_room_schedule">
                                        <input type="hidden" name="date" value="<?= htmlspecialchars($current_date) ?>">
                                        <div class="filter-row">
                                            <div class="filter-group">
                                                <label for="terapist">Terapist Filtresi</label>
                                                <select name="terapist" id="terapist" class="form-select">
                                                    <option value="">Tüm Terapistler</option>
                                                    <?php foreach($terapistler as $terapist): ?>
                                                        <option value="<?= $terapist['id'] ?>" <?= $filter_terapist == $terapist['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($terapist['ad'] . ' ' . $terapist['soyad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="filter-group">
                                                <label for="danisan">Danışan Filtresi</label>
                                                <select name="danisan" id="danisan" class="form-select">
                                                    <option value="">Tüm Danışanlar</option>
                                                    <?php foreach($danisanlar as $danisan): ?>
                                                        <option value="<?= $danisan['id'] ?>" <?= $filter_danisan == $danisan['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="filter-buttons">
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrele</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()"><i class="fas fa-times"></i> Temizle</button>
                                            </div>
                                        </div>
                                    </form>

                                    <?php if($filter_terapist || $filter_danisan): ?>
                                        <div class="active-filters">
                                            <strong>Aktif Filtreler:</strong>
                                            <?php if($filter_terapist):
                                                $selected_terapist = null;
                                                foreach($terapistler as $t){ if($t['id']==$filter_terapist){ $selected_terapist=$t; break; } }
                                            ?>
                                                <span class="filter-tag">
                                                    Terapist: <?= htmlspecialchars(($selected_terapist['ad']??'').' '.($selected_terapist['soyad']??'')) ?>
                                                    <span class="remove" onclick="removeFilter('terapist')">×</span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if($filter_danisan):
                                                $selected_danisan = null;
                                                foreach($danisanlar as $d){ if($d['id']==$filter_danisan){ $selected_danisan=$d; break; } }
                                            ?>
                                                <span class="filter-tag">
                                                    Danışan: <?= htmlspecialchars(($selected_danisan['ad']??'').' '.($selected_danisan['soyad']??'')) ?>
                                                    <span class="remove" onclick="removeFilter('danisan')">×</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="container-fluid">
                                    <div class="navigation">
                                        <div class="date-navigation">
                                            <button class="btn btn-outline-primary" onclick="changeWeek(-1)">
                                                <i class="fas fa-chevron-left"></i> Önceki Hafta
                                            </button>
                                            <input type="date" id="schedule-date" class="form-control"
                                                   value="<?= htmlspecialchars($current_date) ?>"
                                                   onchange="window.location.href='weekly_room_schedule.php?date='+this.value">
                                            <button class="btn btn-outline-primary" onclick="changeWeek(1)">
                                                Sonraki Hafta <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                        <div class="controls-group">
                                            <a href="room_lock_management.php?date=<?= $current_date; ?>" class="lock-management-btn">
                                                <i class="fas fa-lock"></i> Kilitleme Yönetimi
                                            </a>
                                            <a href="weekly_room_schedule.php" class="btn btn-outline-primary active">Hafta</a>
                                            <a href="room_schedule.php?date=<?= htmlspecialchars($current_date) ?>" class="btn btn-outline-primary">Gün</a>
                                            <a href="randevulist.php" class="btn btn-outline-primary">Liste</a>
                                        </div>
                                    </div>


                    <div class="room-schedule-wrap">
                    <div class="room-schedule-top-scroll"><div class="room-schedule-top-scroll-inner"></div></div>
                    <div class="room-schedule">  <!-- mevcut tablo burada -->
                             <table class="table">
  <thead>
    <!-- Gün başlıkları -->
    <tr>
      <th class="time-column">Saat</th>
      <?php foreach ($week_days as $di => $d): ?>
        <?php $isAlt = ($di % 2) === 1; ?>
        <th class="day-col <?= $isAlt ? 'day-alt' : '' ?>" colspan="<?= count($rooms) ?>">
          <?= trDayLong($d) . ' ' . trFullDate($d) ?>
        </th>
      <?php endforeach; ?>
    </tr>

    <!-- Oda başlıkları (her gün için tekrar) -->
    <tr>
      <th class="time-column"></th>
      <?php foreach ($week_days as $di => $d): ?>
        <?php $isAlt = ($di % 2) === 1; ?>
        <?php foreach ($rooms as $ri => $room): ?>
          <th class="room-head <?= $isAlt ? 'day-alt' : '' ?> <?= $ri===0 ? 'day-start' : '' ?>">
            <?= htmlspecialchars($room['name']); ?>
            <?php $locked_count = count($kilitli_saatler_hafta[$d][$room['id']] ?? []); ?>
            <?php if ($locked_count > 0): ?>
              <span class="badge bg-danger ms-2"><?= $locked_count; ?> Kilitli</span>
            <?php endif; ?>
          </th>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tr>
  </thead>

  <tbody>
    <?php $time_slots = generateTimeSlots('08:00','21:00',60); ?>
    <?php foreach ($time_slots as $time): ?>
      <tr>
        <td class="time-column"><?= $time; ?></td>

        <?php foreach ($week_days as $di => $day): ?>
          <?php $isAlt = ($di % 2) === 1; ?>

          <?php foreach ($rooms as $ri => $room): ?>
            <?php
              // --- Kilit kontrolü (o gün/o oda/o saat) ---
              $locksForCell = $kilitli_saatler_hafta[$day][$room['id']] ?? [];
              $is_locked = false; $lock_info = null;
              foreach ($locksForCell as $lock) {
                if (($lock['saat'] ?? '') === $time.':00') { // '08:00' -> '08:00:00'
                  $is_locked = true; $lock_info = $lock; break;
                }
              }

              // --- Randevu kontrolü ---
              $apt = $schedule[$day][$room['id']]['appointments'][$time] ?? null;
              $has_apt = !empty($apt);

              // --- Hücre sınıfları ---
              $cell_class = "room-cell";
              if ($isAlt)  $cell_class .= " day-alt";
              if ($ri===0) $cell_class .= " day-start";
              if ($is_locked) {
                $cell_class .= $has_apt ? " locked has-apt" : " locked";
              }
            ?>
            <td class="<?= $cell_class; ?>"
                data-room-id="<?= $room['id']; ?>"
                data-date="<?= $day; ?>"
                data-time="<?= $time; ?>"
                <?php if ($is_locked && $lock_info): ?>
                  title="KİLİTLİ: <?= htmlspecialchars($lock_info['aciklama'] ?? 'Açıklama yok'); ?>"
                <?php endif; ?>>

              <?php if ($is_locked): ?>
                <!-- Kilit bilgisi (randevu olsa da dursun) -->
                <div class="lock-info">
                  <?= strtoupper(substr($lock_info['kilit_turu'] ?? 'KIL', 0, 3)); ?>
                </div>
                <?php if (!empty($lock_info['aciklama'])): ?>
                  <div style="font-size:11px;margin-top:5px;opacity:.8;">
                    <?= htmlspecialchars($lock_info['aciklama']); ?>
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <?php if ($has_apt): ?>
                <?php
                  // Değerlendirme görseli
                  $evaluationClass = '';
                  $evaluationBadge = '';
                  if (!empty($apt['evaluation_type'])) {
                    $evaluationClass = 'evaluation-appointment';
                    if ($apt['evaluation_type'] === 'initial') {
                      $evaluationClass .= ' initial';
                      $evaluationBadge = '<span class="badge badge-initial">İlk Değerlendirme</span>';
                    } elseif ($apt['evaluation_type'] === 'progress') {
                      $num = (int)($apt['evaluation_number'] ?? 0);
                      $evaluationBadge = '<span class="badge badge-evaluation">'.$num.'. Değerlendirme</span>';
                    }
                  }
                ?>
                <!-- RANDEVU (kilitli hücrede de görünür) -->
                <div class="appointment <?= $evaluationClass; ?> <?= htmlspecialchars($apt['durum']); ?>"
                     draggable="true"
                     data-appointment-id="<?= $apt['id']; ?>"
                     data-time="<?= $time; ?>"
                     onclick="handleAppointmentEdit('<?= $apt['id']; ?>', event)">
                  <div class="appointment-time"><?= $time; ?></div>
                  <div class="appointment-client"><?= htmlspecialchars($apt['danisan']); ?></div>
                  <div class="appointment-details">
                    <?= htmlspecialchars($apt['terapist']); ?><br>
                    <?= htmlspecialchars($apt['seans_turu'] ?? ''); ?>
                  </div>
                  <?php if (!empty($apt['seans_sirasi'])): ?>
                    <div class="session-info"><small><?= (int)$apt['seans_sirasi']; ?>. seans</small></div>
                  <?php endif; ?>
                  <?= $evaluationBadge; ?>
                </div>

              <?php elseif (!$is_locked): ?>
                <!-- Kilitli DEĞİL ve randevu YOK ise + butonu -->
                <button type="button" class="add-appointment-btn"
                        onclick="handleAppointmentAdd('<?= $day . ' ' . $time; ?>', <?= $room['id']; ?>)">
                  <i class="fas fa-plus"></i>
                </button>
              <?php endif; ?>

            </td>
          <?php endforeach; // rooms ?>
        <?php endforeach; // days ?>

      </tr>
    <?php endforeach; // time slots ?>
  </tbody>
</table>
                    </div>
                    </div>

                                </div> <!-- /.container-fluid -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Günlük sayfadaki ile aynı – hiçbir şeyi kırmadım -->
        <?php /* Aynı modal içerik: direkt günlükten alındı */ ?>
        <div class="modal fade" id="appointmentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Randevu Detayları</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-3" id="appointmentTabsNav" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                                    Randevu<br>Bilgileri
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab">
                                    Randevu<br>Listesi
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="randevu-not-tab" data-bs-toggle="tab" data-bs-target="#randevu-notlari" type="button" role="tab">
                                    Randevu<br>Notları
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">
                                    Genel<br>Notlar
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                                    Ödeme<br>Geçmişi
                                </button>
                            </li>
                            <li class="nav-item" role="presentation" id="fonksiyonel-not-tab-li" style="display:none;">
                                <button class="nav-link" id="fonksiyonel-notlar-tab" data-bs-toggle="tab" data-bs-target="#fonksiyonel-notlar" type="button" role="tab">
                                    Fonksiyonel<br>Notlar
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="appointmentTabs">
                            <!-- Randevu Bilgileri Tab (günlükten birebir) -->
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <form id="appointmentForm" onsubmit="return false;">
                                    <input type="hidden" name="ajax_action" value="">
                                    <input type="hidden" name="id" value="">
                                    <input type="hidden" name="danisan_id" id="danisan_id" value="">
                                    <input type="hidden" name="seans_turu_id" id="seans_turu_id" value="">
                                    <input type="hidden" name="satis_id" id="satis_id" value="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="danisan_satis_id" class="form-label">Danışan ve Paket</label>
                                                <select name="danisan_satis_id" id="danisan_satis_id" class="form-select" required>
                                                    <option value="">Danışan ve paket seçin...</option>
                                                    <?php foreach ($danisanlar as $danisan): ?>
                                                        <option value="<?= $danisan['aktif_satis_id']; ?>"
                                                                data-danisan-id="<?= $danisan['id']; ?>"
                                                                data-seans-turu-id="<?= $danisan['seans_turu_id']; ?>">
                                                            <?= htmlspecialchars($danisan['ad_soyad']); ?> (Kalan: <?= (int)$danisan['kalan_seans']; ?> seans)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="personel_id" class="form-label">Terapist</label>
                                                <select name="personel_id" id="personel_id" class="form-select" required>
                                                    <option value="">Terapist seçin...</option>
                                                    <?php foreach ($terapistler as $terapist): ?>
                                                        <option value="<?= $terapist['id']; ?>">
                                                            <?= htmlspecialchars($terapist['ad'])." ".htmlspecialchars($terapist['soyad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="room_id" class="form-label">Oda</label>
                                                <select name="room_id" id="room_id" class="form-select" required>
                                                    <option value="">Oda seçin...</option>
                                                    <?php foreach ($rooms as $room): ?>
                                                        <option value="<?= $room['id']; ?>"><?= htmlspecialchars($room['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Seçilen Paket</label>
                                                <div id="selectedPackage" class="form-control-plaintext">
                                                    <em class="text-muted">Danışan seçince görünecek</em>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="randevu_tarih" class="form-label">Tarih</label>
                                                <input type="date" name="randevu_tarih" id="randevu_tarih" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="randevu_saat" class="form-label">Saat</label>
                                                <select name="randevu_saat" id="randevu_saat" class="form-select" required>
                                                    <option value="">Saat seçin...</option>
                                                    <?php for ($i=8; $i<=21; $i++): ?>
                                                        <option value="<?= sprintf('%02d:00',$i); ?>"><?= sprintf('%02d:00',$i); ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <small class="text-muted mt-1" id="lockedTimeInfo" style="display:none;">
                                                    <i class="fas fa-lock text-warning"></i> Kilitli saatler seçilemez
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notlar" class="form-label">Notlar</label>
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

                            <div class="tab-pane fade" id="appointments" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>Tarih</th><th>Saat</th><th>Terapist</th><th>Seans Türü</th><th>Oda</th><th>Durum</th></tr></thead>
                                        <tbody id="appointmentsList"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="randevu-notlari" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>Randevu Tarihi</th><th>Terapist</th><th>Not</th><th>Eklenme Tarihi</th></tr></thead>
                                        <tbody id="randevuNotesList"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="notes" role="tabpanel">
                                <div class="general-note-box">
                                    <label for="generalNoteInput" class="form-label">Genel Not Ekle</label>
                                    <textarea id="generalNoteInput" class="form-control" rows="3" placeholder="Danışan için genel notunuzu yazın..."></textarea>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="button" class="btn btn-primary" onclick="submitGeneralNote()">Notu Kaydet</button>
                                        <small class="text-muted" id="generalNoteHint">Önce danışanı seçmelisiniz.</small>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>Tarih</th><th>Ekleyen</th><th>Not</th></tr></thead>
                                        <tbody id="notesList"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="payments" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>Vade Tarihi</th><th>Ödenen Tutar</th><th>Ödeme Tipi</th><th>Satış Personeli</th></tr></thead>
                                        <tbody id="paymentsList"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="fonksiyonel-notlar" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>Seans No</th><th>Başlık</th><th>Not</th><th>Ekleyen</th><th>Tarih</th><th>İşlem</th></tr></thead>
                                        <tbody id="fonksiyonelNotesList"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /.modal-body -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-danger" id="deleteAppointmentBtn" style="display:none;" onclick="deleteCurrentAppointment()">Sil</button>
                        <button type="button" class="btn btn-primary" onclick="saveAppointment()">Kaydet</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fonksiyonel Not Ekle Modal (günlükteki ile aynı) -->
        <div class="modal fade" id="fonksiyonelNotEkleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="fonksiyonelNotEkleForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Fonksiyonel Not Ekle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="seans_no" id="fonkSeansNo">
                            <input type="hidden" name="danisan_id" id="fonkDanisanId">
                            <input type="hidden" name="satis_id" id="fonkSatisId">
                            <textarea name="icerik" class="form-control" rows="3" placeholder="Notunuz"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'partials/footer-scripts.php'; ?>
        <script src="assets/js/RoomScheduleWeekly.js"></script>
    </div>
</body>
</html>
