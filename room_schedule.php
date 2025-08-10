<?php
include_once 'functions.php';
include 'partials/session.php';

// Room schedule page
$current_date = $_GET['date'] ?? date('Y-m-d');
$rooms = getRooms();
$terapistler = getTerapistler(true);
$danisanlar = getDanisanlarWithRemainingAppointments();
$seans_turleri = getSeansTurleri();

$filter_terapist = $_GET['terapist'] ?? '';
$filter_danisan = $_GET['danisan'] ?? '';

// DEBUG - Geçici olarak ekledik
error_log("DEBUG - Terapist sayısı: " . count($terapistler));
error_log("DEBUG - Danışan sayısı: " . count($danisanlar));
error_log("DEBUG - Seans türü sayısı: " . count($seans_turleri));

// Kilitli saatleri getir
$kilitli_saatler = getTumKilitliSaatler($current_date);

// Filtrelenmiş oda programını getir
function getFilteredRoomSchedule($date, $terapist_id = null, $danisan_id = null) {
    global $pdo;
    try {
        // Get all active rooms
        $rooms_sql = "SELECT * FROM rooms WHERE aktif = TRUE ORDER BY type, name";
        $rooms_stmt = $pdo->query($rooms_sql);
        $rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $schedule = [];
        
        foreach ($rooms as $room) {
            $sql = "SELECT 
                r.id as room_id, 
                r.name as room_name, 
                r.type as room_type,
                ran.id as randevu_id, 
                ran.randevu_tarihi, 
                ran.durum,
                ran.evaluation_type,
                ran.evaluation_notes,
                ran.personel_id,
                ran.danisan_id,
                CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                st.ad as seans_turu,
                st.evaluation_interval,
                st.sure,
                (
                    SELECT COUNT(*) 
                    FROM randevular prev 
                    WHERE prev.danisan_id = ran.danisan_id 
                    AND prev.seans_turu_id = ran.seans_turu_id 
                    AND prev.randevu_tarihi <= ran.randevu_tarihi 
                    AND prev.aktif = 1
                ) as seans_sirasi
            FROM rooms r
            LEFT JOIN randevular ran ON ran.room_id = r.id 
                AND DATE(ran.randevu_tarihi) = :date
                AND ran.aktif = 1";
            
            // Filtreler ekle
            $params = ['date' => $date, 'room_id' => $room['id']];
            
            if ($terapist_id) {
                $sql .= " AND ran.personel_id = :terapist_id";
                $params['terapist_id'] = $terapist_id;
            }
            
            if ($danisan_id) {
                $sql .= " AND ran.danisan_id = :danisan_id";
                $params['danisan_id'] = $danisan_id;
            }
            
            $sql .= " LEFT JOIN danisanlar d ON d.id = ran.danisan_id
            LEFT JOIN personel p ON p.id = ran.personel_id
            LEFT JOIN seans_turleri st ON st.id = ran.seans_turu_id
            WHERE r.id = :room_id
            ORDER BY ran.randevu_tarihi ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $schedule[$room['id']] = [
                'room_info' => [
                    'id' => $room['id'],
                    'name' => $room['name'],
                    'type' => $room['type']
                ],
                'appointments' => []
            ];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['randevu_id']) {
                    $time_slot = date('H:i', strtotime($row['randevu_tarihi']));
                    
                    // Calculate evaluation information
                    $evaluation_info = '';
                    $evaluation_type = '';
                    $evaluation_number = null;
                    
                    if ($row['evaluation_interval'] > 0) {
                        $session_number = $row['seans_sirasi'];
                        
                        if ($session_number == 1) {
                            $evaluation_type = 'initial';
                            $evaluation_info = 'İlk Değerlendirme';
                        } elseif ($session_number % $row['evaluation_interval'] == 0) {
                            $evaluation_type = 'progress';
                            $evaluation_number = floor($session_number / $row['evaluation_interval']);
                            $evaluation_info = $evaluation_number . '. Değerlendirme';
                        }
                    }
                    
                    $schedule[$room['id']]['appointments'][$time_slot] = [
                        'id' => $row['randevu_id'],
                        'danisan' => $row['danisan_adi'],
                        'terapist' => $row['terapist_adi'],
                        'seans_turu' => $row['seans_turu'],
                        'durum' => $row['durum'],
                        'evaluation_type' => $evaluation_type,
                        'evaluation_number' => $evaluation_number,
                        'evaluation_notes' => $row['evaluation_notes'],
                        'sure' => $row['sure'],
                        'seans_sirasi' => $row['seans_sirasi'],
                        'evaluation_info' => $evaluation_info,
                        'personel_id' => $row['personel_id'],
                        'danisan_id' => $row['danisan_id']
                    ];
                }
            }
        }
        
        return $schedule;
    } catch(PDOException $e) {
        error_log("Filtrelenmiş oda programı getirme hatası: " . $e->getMessage());
        return [];
    }
}

$schedule = getFilteredRoomSchedule($current_date, $filter_terapist, $filter_danisan);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php"; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }
    
    .filter-row {
        display: flex;
        align-items: end;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-group label {
        font-weight: 600;
        margin-bottom: 5px;
        display: block;
        color: #495057;
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        align-items: end;
    }
    
    .active-filters {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .filter-tag {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85rem;
        margin-right: 8px;
        margin-bottom: 5px;
    }
    
    .filter-tag .remove {
        margin-left: 5px;
        cursor: pointer;
        font-weight: bold;
    }

    /* Navigation */
    .navigation { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: 1rem; }
    .date-navigation, .controls-group { display: flex; align-items: center; gap: .5rem; }
    @media (max-width: 768px) {
        .navigation { flex-direction: column; align-items: stretch; }
        .date-navigation, .controls-group { width: 100%; justify-content: flex-start; }
    }

    /* Table & Sticky First Column */
    .room-schedule { overflow-x: auto; -webkit-overflow-scrolling: touch; background: #fff; border-radius: 8px; padding: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .room-schedule table { border-collapse: collapse; min-width: 100%; }
    .room-schedule th, .room-schedule td { border: 1px solid #e0e0e0 !important; padding: .75rem; vertical-align: top; }
    .room-schedule th.time-column, .room-schedule td.time-column { position: sticky; left: 0; background: #f8f9fa; z-index: 10; font-weight: 600; min-width: 80px; }

    /* Room Cells & Appointments */
    .room-cell { position: relative; min-height: 60px; background: #fff; cursor: pointer; transition: background-color 0.2s; }
    .room-cell.locked { background: #6c757d !important; color: white; cursor: not-allowed; position: relative; }
    .room-cell.locked::before { content: "🔒"; position: absolute; top: 5px; right: 5px; font-size: 12px; opacity: 0.8; }
    .room-cell.locked .lock-info { position: absolute; bottom: 2px; left: 2px; font-size: 10px; opacity: 0.9; background: rgba(0,0,0,0.2); padding: 1px 3px; border-radius: 2px; }
    .room-cell:not(.locked):hover { background: #e9ecef; }
    .room-cell.drag-over:not(.locked) { background: #cce5ff; border: 2px dashed #007bff; }

    .appointment { background: linear-gradient(135deg, #4a90e2, #357abd); color: white; padding: 6px 8px; border-radius: 6px; margin: 2px 0; font-size: 0.85rem; line-height: 1.2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: grab; transition: transform 0.2s, box-shadow 0.2s; }
    .appointment:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    .appointment.dragging { opacity: 0.7; transform: rotate(5deg); cursor: grabbing; }
    .appointment-time { font-weight: 600; font-size: 0.8rem; opacity: 0.9; }
    .appointment-client { font-weight: 500; margin: 2px 0; }
    .appointment-details { font-size: 0.75rem; opacity: 0.8; margin-top: 2px; }
    .session-info { font-size: 0.7rem; opacity: 0.9; margin-top: 2px; background: rgba(255,255,255,0.2); padding: 1px 4px; border-radius: 3px; }

    /* Evaluation appointments */
    .evaluation-appointment { background: linear-gradient(135deg, #ff9800, #f57c00) !important; }
    .evaluation-appointment.initial { background: linear-gradient(135deg, #4caf50, #388e3c) !important; }
    .badge { display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 500; border-radius: 3px; margin-top: 2px; }
    .badge-initial, .badge-evaluation { background: rgba(255,255,255,0.3); color: white; }

    /* Status colors */
    .appointment.beklemede { background: linear-gradient(135deg, #ffc107, #ff8f00); }
    .appointment.onaylandi { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .appointment.iptal_edildi { background: linear-gradient(135deg, #dc3545, #c82333); opacity: 0.7; }
    .appointment.tamamlandi { background: linear-gradient(135deg, #6c757d, #5a6268); }

    /* Add appointment button */
    .add-appointment-btn { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 30px; height: 30px; border: 2px dashed #ccc; background: transparent; color: #666; border-radius: 50%; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; cursor: pointer; }
    .room-cell:not(.locked):hover .add-appointment-btn { opacity: 1; }
    .add-appointment-btn:hover { border-color: #007bff; color: #007bff; background: rgba(0,123,255,0.1); }

    /* Lock management button */
    .lock-management-btn { background: linear-gradient(135deg, #6c757d, #5a6268); border: none; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: transform 0.2s; }
    .lock-management-btn:hover { transform: translateY(-1px); color: white; text-decoration: none; }
    .lock-management-btn i { margin-right: 5px; }

    /* Kilitli saat stilleri */
    .locked-time-option {
        background-color: #e9ecef !important;
        color: #6c757d !important;
        cursor: not-allowed !important;
    }

    @media (max-width: 768px) {
        .room-schedule th, .room-schedule td { padding: 0.5rem; font-size: 0.85rem; }
        .appointment { padding: 4px 6px; font-size: 0.75rem; }
        .add-appointment-btn { width: 25px; height: 25px; }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Randevu Yönetimi";
                $title = "Günlük Oda Programı - " . date('d.m.Y', strtotime($current_date));
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">

<!-- Filtreleme Bölümü -->
                                <div class="filter-section">
                                    <form method="GET" id="filterForm">
                                        <input type="hidden" name="page" value="room_schedule">
                                        <input type="hidden" name="date" value="<?= htmlspecialchars($current_date) ?>">
                                        
                                        <div class="filter-row">
                                            <div class="filter-group">
                                                <label for="terapist">Terapist Filtresi</label>
                                                <select name="terapist" id="terapist" class="form-select">
                                                    <option value="">Tüm Terapistler</option>
                                                    <?php foreach($terapistler as $terapist): ?>
                                                        <option value="<?= $terapist['id'] ?>" 
                                                                <?= $filter_terapist == $terapist['id'] ? 'selected' : '' ?>>
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
                                                        <option value="<?= $danisan['id'] ?>" 
                                                                <?= $filter_danisan == $danisan['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="filter-buttons">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-filter"></i> Filtrele
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                                    <i class="fas fa-times"></i> Temizle
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <!-- Aktif Filtreler -->
                                    <?php if($filter_terapist || $filter_danisan): ?>
                                        <div class="active-filters">
                                            <strong>Aktif Filtreler:</strong>
                                            <?php if($filter_terapist): 
                                                $selected_terapist = array_filter($terapistler, function($t) use ($filter_terapist) {
                                                    return $t['id'] == $filter_terapist;
                                                });
                                                $selected_terapist = reset($selected_terapist);
                                            ?>
                                                <span class="filter-tag">
                                                    Terapist: <?= htmlspecialchars($selected_terapist['ad'] . ' ' . $selected_terapist['soyad']) ?>
                                                    <span class="remove" onclick="removeFilter('terapist')">×</span>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if($filter_danisan): 
                                                $selected_danisan = array_filter($danisanlar, function($d) use ($filter_danisan) {
                                                    return $d['id'] == $filter_danisan;
                                                });
                                                $selected_danisan = reset($selected_danisan);
                                            ?>
                                                <span class="filter-tag">
                                                    Danışan: <?= htmlspecialchars($selected_danisan['ad'] . ' ' . $selected_danisan['soyad']) ?>
                                                    <span class="remove" onclick="removeFilter('danisan')">×</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="container-fluid">
                                    <div class="navigation">
                                        <div class="date-navigation">
                                            <button class="btn btn-outline-primary" onclick="changeDate(-1)">
                                                <i class="fas fa-chevron-left"></i> Önceki Gün
                                            </button>
                                            <input type="date" id="schedule-date" class="form-control" 
                                                   value="<?= htmlspecialchars($current_date) ?>" 
                                                   onchange="window.location.href='room_schedule.php?date='+this.value">
                                            <button class="btn btn-outline-primary" onclick="changeDate(1)">
                                                Sonraki Gün <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                        <div class="controls-group">
                                            <a href="room_lock_management.php?date=<?php echo $current_date; ?>" class="lock-management-btn">
                                                <i class="fas fa-lock"></i> Kilitleme Yönetimi
                                            </a>
                                            <a href="apps-calendar.php" class="btn btn-outline-primary">Ay</a>
                                            <a href="weekly_room_schedule.php" class="btn btn-outline-primary">Hafta</a>
                                            <a href="room_schedule.php" class="btn btn-outline-primary active">Gün</a>
                                        </div>
                                    </div>

<div class="room-schedule">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th class="time-column">Saat</th>
                                                    <?php foreach ($rooms as $room): ?>
                                                        <th>
                                                            <?php echo htmlspecialchars($room['name']); ?>
                                                            <?php 
                                                            $locked_count = count($kilitli_saatler[$room['id']] ?? []);
                                                            if ($locked_count > 0): 
                                                            ?>
                                                                <span class="badge bg-danger ms-2"><?php echo $locked_count; ?> Kilitli</span>
                                                            <?php endif; ?>
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $time_slots = generateTimeSlots('08:00', '21:00', 60);

                                                foreach ($time_slots as $time):
                                                ?>
                                                <tr>
                                                    <td class="time-column"><?php echo $time; ?></td>
                                                    <?php foreach ($rooms as $room): 
                                                        // Bu oda ve saat kilitli mi kontrol et
                                                        $is_locked = false;
                                                        $lock_info = null;
                                                        if (isset($kilitli_saatler[$room['id']])) {
                                                            foreach ($kilitli_saatler[$room['id']] as $lock) {
                                                                if ($lock['saat'] === $time.':00') {
                                                                    $is_locked = true;
                                                                    $lock_info = $lock;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                        
                                                        $cell_class = "room-cell";
                                                        if ($is_locked) {
                                                            $cell_class .= " locked";
                                                        }
                                                    ?>
                                                        <td class="<?php echo $cell_class; ?>" 
                                                            data-room-id="<?php echo $room['id']; ?>" 
                                                            data-time="<?php echo $time; ?>"
                                                            <?php if ($is_locked): ?>
                                                                title="KİLİTLİ: <?php echo htmlspecialchars($lock_info['aciklama'] ?? 'Açıklama yok'); ?>"
                                                            <?php endif; ?>>
                                                            
                                                            <?php if ($is_locked): ?>
                                                                <!-- Kilitli hücre içeriği -->
                                                                <div class="lock-info">
                                                                    <?php echo strtoupper(substr($lock_info['kilit_turu'], 0, 3)); ?>
                                                                </div>
                                                                <?php if (!empty($lock_info['aciklama'])): ?>
                                                                    <div style="font-size: 11px; margin-top: 5px; opacity: 0.8;">
                                                                        <?php echo htmlspecialchars($lock_info['aciklama']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <!-- Normal hücre - randevu eklenebilir -->
                                                                <button type="button" class="add-appointment-btn" onclick="handleAppointmentAdd('<?php echo $current_date . ' ' . $time; ?>', <?php echo $room['id']; ?>)">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>

                                                                <?php
                                                                if (isset($schedule[$room['id']]['appointments'][$time])) {
                                                                    $apt = $schedule[$room['id']]['appointments'][$time];
                                                                    $evaluationClass = '';
                                                                    $evaluationBadge = '';

                                                                    if (!empty($apt['evaluation_type'])) {
                                                                        $evaluationClass = 'evaluation-appointment';
                                                                        if ($apt['evaluation_type'] === 'initial') {
                                                                            $evaluationBadge = '<span class="badge badge-initial">İlk Değerlendirme</span>';
                                                                            $evaluationClass .= ' initial';
                                                                        } elseif ($apt['evaluation_type'] === 'progress') {
                                                                            $evaluationBadge = '<span class="badge badge-evaluation">' .
                                                                                $apt['evaluation_number'] . '. Değerlendirme</span>';
                                                                        }
                                                                    }

                                                                    echo '<div class="appointment ' . $evaluationClass . ' ' . $apt['durum'] . '" 
                                                                              draggable="true" 
                                                                              data-appointment-id="' . $apt['id'] . '" 
                                                                              data-time="' . $time . '"
                                                                              onclick="handleAppointmentEdit(\'' . $apt['id'] . '\', event)">';
                                                                    echo '<div class="appointment-time">' . $time . '</div>';
                                                                    echo '<div class="appointment-client">' . htmlspecialchars($apt['danisan']) . '</div>';
                                                                    echo '<div class="appointment-details">';
                                                                    echo htmlspecialchars($apt['terapist']) . '<br>';
                                                                    echo htmlspecialchars($apt['seans_turu']);
                                                                    echo '</div>';
                                                                    
                                                                    // Seans sayısı bilgisini göster
                                                                    if (isset($apt['seans_sirasi'])) {
                                                                        echo '<div class="session-info">';
                                                                        echo '<small>' . $apt['seans_sirasi'] . '. seans</small>';
                                                                        echo '</div>';
                                                                    }
                                                                    
                                                                    echo $evaluationBadge;
                                                                    echo '</div>';
                                                                }
                                                                ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Updated Modal with All Tabs -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Randevu Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs mb-3" id="appointmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" 
                                    data-bs-target="#details" type="button" role="tab">
                                Randevu<br>Bilgileri
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" 
                                    data-bs-target="#appointments" type="button" role="tab">
                                Randevu<br>Listesi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="randevu-not-tab" data-bs-toggle="tab" 
                                    data-bs-target="#randevu-notlari" type="button" role="tab">
                                Randevu<br>Notları
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notes-tab" data-bs-toggle="tab" 
                                    data-bs-target="#notes" type="button" role="tab">
                                Genel<br>Notlar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" 
                                    data-bs-target="#payments" type="button" role="tab">
                                Ödeme<br>Geçmişi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" id="fonksiyonel-not-tab-li" style="display:none;">
                            <button class="nav-link" id="fonksiyonel-notlar-tab" data-bs-toggle="tab" 
                                    data-bs-target="#fonksiyonel-notlar" type="button" role="tab">
                                Fonksiyonel<br>Notlar
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Contents -->
                    <div class="tab-content">
                        <!-- Randevu Bilgileri Tab -->
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
                                                    <option value="<?php echo $danisan['aktif_satis_id']; ?>" 
                                                            data-danisan-id="<?php echo $danisan['id']; ?>"
                                                            data-seans-turu-id="<?php echo $danisan['seans_turu_id']; ?>">
                                                        <?php echo htmlspecialchars($danisan['ad_soyad']); ?>
                                                        (Kalan: <?php echo $danisan['kalan_seans']; ?> seans)
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
                                                    <option value="<?php echo $terapist['id']; ?>">
                                                        <?php echo htmlspecialchars($terapist['ad'])." " .htmlspecialchars($terapist['soyad']) ?>
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
                                                    <option value="<?php echo $room['id']; ?>">
                                                        <?php echo htmlspecialchars($room['name']); ?>
                                                    </option>
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
                                                <?php for ($i = 8; $i <= 21; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                        <?php echo sprintf('%02d:00', $i); ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                            <small class="text-muted mt-1" id="lockedTimeInfo" style="display: none;">
                                                <i class="fas fa-lock text-warning"></i> Kilitli saatler seçilemez
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notlar" class="form-label">Notlar</label>
                                    <textarea name="notlar" id="notlar" class="form-control" rows="3"></textarea>
                                </div>

                                <!-- Evaluation Notes Section -->
                                <div class="evaluation-notes-section" style="display: none;">
                                    <div class="mb-3">
                                        <label class="evaluation-notes-label form-label">Değerlendirme Notları</label>
                                        <textarea name="evaluation_notes" class="form-control" rows="4" 
                                                  placeholder="Değerlendirme sonuçları ve öneriler..."></textarea>
                                    </div>
                                </div>

                                <!-- Randevu bilgileri -->
                                <div id="appointmentDetails" class="card mt-3" style="display: none;">
                                    <div class="card-header">
                                        <h6 class="mb-0">Paket Bilgileri</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">Toplam Seans:</small>
                                                <span id="totalSessions" class="fw-bold">0</span>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Kalan Seans:</small>
                                                <span id="remainingSessions" class="fw-bold">0</span>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <small class="text-muted">Sıradaki Seans:</small>
                                                <span id="nextSessionNumber" class="fw-bold">0</span>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Ödeme Durumu:</small>
                                                <span id="paymentStatus" class="fw-bold">₺0 / ₺0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Randevu Listesi Tab -->
                        <div class="tab-pane fade" id="appointments" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Saat</th>
                                            <th>Terapist</th>
                                            <th>Seans Türü</th>
                                            <th>Oda</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointmentsList"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Randevu Notları Tab -->
                        <div class="tab-pane fade" id="randevu-notlari" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Randevu Tarihi</th>
                                            <th>Terapist</th>
                                            <th>Not</th>
                                            <th>Eklenme Tarihi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="randevuNotesList"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Genel Notlar Tab -->
                        <div class="tab-pane fade" id="notes" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Ekleyen</th>
                                            <th>Not</th>
                                        </tr>
                                    </thead>
                                    <tbody id="notesList"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Ödeme Geçmişi Tab -->
                        <div class="tab-pane fade" id="payments" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Vade Tarihi</th>
                                            <th>Ödenen Tutar</th>
                                            <th>Ödeme Tipi</th>
                                            <th>Satış Personeli</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentsList"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Fonksiyonel Notlar Tab -->
                        <div class="tab-pane fade" id="fonksiyonel-notlar" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Seans No</th>
                                            <th>Başlık</th>
                                            <th>Not</th>
                                            <th>Ekleyen</th>
                                            <th>Tarih</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fonksiyonelNotesList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="deleteAppointmentBtn" style="display: none;" onclick="deleteCurrentAppointment()">Sil</button>
                    <button type="button" class="btn btn-primary" onclick="saveAppointment()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fonksiyonel Not Ekleme Modal -->
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

   <!-- room_schedule.php için düzeltilmiş JavaScript kodu -->
<script>
// Global değişkenler
var appointmentModal;
var currentRoomLockedTimes = [];
var currentAppointmentId = null;
var draggedAppointment = null;
var currentDanisanId = null;

// DOM yüklendiğinde çalışacak fonksiyon
document.addEventListener('DOMContentLoaded', function() {
    // Modal ID'yi düzelt - HTML'de appointmentModal olarak tanımlanmış
    appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
    initializeEventListeners();
    initializeDragAndDrop();
    initializeTabEventListeners();
});

// Tab Event Listeners
function initializeTabEventListeners() {
    // Tab event listeners
    document.getElementById('appointments-tab').addEventListener('shown.bs.tab', function () {
        if (currentDanisanId) loadAppointmentsList(currentDanisanId);
    });

    document.getElementById('randevu-not-tab').addEventListener('shown.bs.tab', function () {
        if (currentDanisanId) loadRandevuNotes(currentDanisanId);
    });

    document.getElementById('notes-tab').addEventListener('shown.bs.tab', function () {
        if (currentDanisanId) loadDanisanNotes(currentDanisanId);
    });

    document.getElementById('payments-tab').addEventListener('shown.bs.tab', function () {
        if (currentDanisanId) loadPaymentHistory(currentDanisanId);
    });

    document.getElementById('fonksiyonel-notlar-tab').addEventListener('shown.bs.tab', function () {
        if (currentDanisanId) loadFonksiyonelNotes(currentDanisanId);
    });
}

// Tab Content Load Functions
function loadAppointmentsList(danisan_id) {
    fetch(`ajax/get_danisan_randevular.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(data => {
        const appointmentsList = document.getElementById('appointmentsList');
        appointmentsList.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(appointment => {
                const row = document.createElement('tr');
                const date = new Date(appointment.randevu_tarihi);
                row.innerHTML = `
                    <td>${date.toLocaleDateString('tr-TR')}</td>
                    <td>${date.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'})}</td>
                    <td>${appointment.terapist_adi || '-'}</td>
                    <td>${appointment.seans_turu || '-'}</td>
                    <td>${appointment.room_name || '-'}</td>
                    <td><span class="badge bg-${appointment.durum === 'geldi' ? 'success' : appointment.durum === 'gelmedi' ? 'danger' : 'warning'}">${appointment.durum}</span></td>
                `;
                appointmentsList.appendChild(row);
            });
        } else {
            appointmentsList.innerHTML = '<tr><td colspan="6" class="text-center">Randevu bulunamadı</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('appointmentsList').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Hata oluştu</td></tr>';
    });
}

function loadRandevuNotes(danisan_id) {
    fetch(`ajax/get-danisan-randevu-notlari.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(data => {
        const notesList = document.getElementById('randevuNotesList');
        notesList.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(note => {
                const ekleyen = ((note.personel_ad || '') + ' ' + (note.personel_soyad || '')).trim() || 'Sistem';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${new Date(note.randevu_tarihi).toLocaleDateString('tr-TR')} ${new Date(note.randevu_tarihi).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</td>
                    <td>${ekleyen}</td>
                    <td>${note.notlar || '-'}</td>
                    <td>${new Date(note.guncelleme_tarihi).toLocaleDateString('tr-TR')} ${new Date(note.guncelleme_tarihi).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</td>
                `;
                notesList.appendChild(row);
            });
        } else {
            notesList.innerHTML = '<tr><td colspan="4" class="text-center">Randevu notu bulunamadı</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('randevuNotesList').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Hata oluştu</td></tr>';
    });
}

function loadDanisanNotes(danisan_id) {
    fetch(`ajax/get-danisan-notlar.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(data => {
        const notesList = document.getElementById('notesList');
        notesList.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(note => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${new Date(note.not_tarihi).toLocaleDateString('tr-TR')} ${new Date(note.not_tarihi).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</td>
                    <td>${(note.personel_ad || '') + ' ' + (note.personel_soyad || '')}</td>
                    <td>${note.icerik}</td>
                `;
                notesList.appendChild(row);
            });
        } else {
            notesList.innerHTML = '<tr><td colspan="3" class="text-center">Not bulunamadı</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('notesList').innerHTML = '<tr><td colspan="3" class="text-center text-danger">Hata oluştu</td></tr>';
    });
}

function loadPaymentHistory(danisan_id) {
    fetch(`ajax/get_danisan_odemeler.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(data => {
        const paymentsList = document.getElementById('paymentsList');
        paymentsList.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(payment => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${payment.vade_tarihi ? new Date(payment.vade_tarihi).toLocaleDateString('tr-TR') : '-'}</td>
                    <td>${parseFloat(payment.tutar).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</td>
                    <td>${payment.odeme_tipi || '-'}</td>
                    <td>${payment.personel_adi || '-'}</td>
                `;
                paymentsList.appendChild(row);
            });
        } else {
            paymentsList.innerHTML = '<tr><td colspan="4" class="text-center">Ödeme geçmişi bulunamadı</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('paymentsList').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Hata oluştu</td></tr>';
    });
}

function loadFonksiyonelNotes(danisan_id) {
    fetch(`ajax/get_fonksiyonel_seans_notlari.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(data => {
        const notesList = document.getElementById('fonksiyonelNotesList');
        
        if (data.success) {
            notesList.innerHTML = data.html;
        } else {
            notesList.innerHTML = '<tr><td colspan="6" class="text-center">Fonksiyonel not bulunamadı</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('fonksiyonelNotesList').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Hata oluştu</td></tr>';
    });
}

function saveFonksiyonelNot(btn, olcumNo) {
    const textarea = $(btn).closest('tr').find('textarea');
    const icerik = textarea.val();

    $.post('ajax/save_fonksiyonel_seans_notu.php', {
        danisan_id: currentDanisanId,
        olcum_no: olcumNo,
        icerik: icerik
    }, function(res) {
        if (res.success) {
            $(btn).text('Kaydedildi!').removeClass('btn-primary').addClass('btn-success');
            setTimeout(function() {
                $(btn).text('Kaydet').removeClass('btn-success').addClass('btn-primary');
            }, 1200);
            loadFonksiyonelNotes(currentDanisanId);
        } else {
            alert('Hata: ' + (res.message || 'Kaydedilemedi'));
        }
    }, 'json');
}

// Load Appointment Details with Tab Support
async function loadAppointmentDetails(satis_id, appointmentId = null) {
    try {
        const response = await fetch(`ajax/get_randevu_detay.php?id=${satis_id}`);
        const data = await response.json();
        
        if (data.success) {
            currentDanisanId = data.satis.danisan_id;
            
            const totalSessions = parseInt(data.satis.seans_adet || 0) + parseInt(data.satis.hediye_seans || 0);
            document.getElementById('totalSessions').textContent = totalSessions;

            const usedSessions = data.randevular ? data.randevular.filter(r => r.durum === 'geldi').length : 0;
            document.getElementById('remainingSessions').textContent = totalSessions - usedSessions;

            // Show/hide Fonksiyonel tab based on session type
            const fonksiyonelSeansTurleri = [55, 56, 57, 58];
            const currentSeansTuruId = data.satis.hizmet_paketi_id;
            const fonksiyonelTab = document.getElementById('fonksiyonel-not-tab-li');
            
            if (fonksiyonelSeansTurleri.includes(parseInt(currentSeansTuruId))) {
                fonksiyonelTab.style.display = '';
            } else {
                fonksiyonelTab.style.display = 'none';
            }

            if (appointmentId) {
                document.getElementById('deleteAppointmentBtn').style.display = 'inline-block';
            } else {
                document.getElementById('deleteAppointmentBtn').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading appointment details:', error);
    }
}

// Event listener'ları başlat
function initializeEventListeners() {
    // Danışan seçildiğinde hidden fieldları doldur
    const danisanSatisSelect = document.getElementById('danisan_satis_id');
    if (danisanSatisSelect) {
        danisanSatisSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const danisanId = selectedOption.getAttribute('data-danisan-id');
            const seansTuruId = selectedOption.getAttribute('data-seans-turu-id');
            const satisId = selectedOption.value;
            
            // Hidden fieldları doldur
            document.getElementById('danisan_id').value = danisanId || '';
            document.getElementById('seans_turu_id').value = seansTuruId || '';
            document.getElementById('satis_id').value = satisId || '';
            
            // Seçilen paketi göster
            if (selectedOption.value) {
                const packageText = selectedOption.textContent.split(' - ')[1] || 'Paket bilgisi';
                document.getElementById('selectedPackage').innerHTML = `<strong>${packageText}</strong>`;
                
                // Danışan detaylarını yükle
                if (satisId) {
                    loadDanisanDetails(satisId);
                    currentDanisanId = danisanId; // Set current danisan ID
                }
            } else {
                document.getElementById('selectedPackage').innerHTML = '<em class="text-muted">Danışan seçince görünecek</em>';
                document.getElementById('appointmentDetails').style.display = 'none';
                currentDanisanId = null;
            }
        });
    }

    // Oda seçildiğinde kilitli saatleri kontrol et
    const roomSelect = document.getElementById('room_id');
    if (roomSelect) {
        roomSelect.addEventListener('change', function() {
            const roomId = this.value;
            const selectedDate = document.getElementById('randevu_tarih').value;
            
            if (roomId && selectedDate) {
                loadRoomLockedTimes(roomId, selectedDate);
            } else {
                resetTimeOptions();
            }
        });
    }

    // Tarih değiştiğinde de kilitli saatleri kontrol et
    const dateInput = document.getElementById('randevu_tarih');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const roomId = document.getElementById('room_id').value;
            const selectedDate = this.value;
            
            if (roomId && selectedDate) {
                loadRoomLockedTimes(roomId, selectedDate);
            } else {
                resetTimeOptions();
            }
        });
    }
}

// SÜRÜKLE BIRAK FONKSİYONLARI
function initializeDragAndDrop() {
    // Tüm randevuları sürüklenebilir yap
    document.querySelectorAll('.appointment').forEach(appointment => {
        appointment.addEventListener('dragstart', handleDragStart);
        appointment.addEventListener('dragend', handleDragEnd);
    });

    // Tüm oda hücrelerini bırakma alanı yap (kilitli olanlar hariç)
    document.querySelectorAll('.room-cell:not(.locked)').forEach(cell => {
        cell.addEventListener('dragover', handleDragOver);
        cell.addEventListener('dragleave', handleDragLeave);
        cell.addEventListener('drop', handleDrop);
    });
}

function handleDragStart(e) {
    draggedAppointment = e.target;
    
    // Randevu bilgilerini sakla
    const appointmentData = {
        id: draggedAppointment.dataset.appointmentId,
        originalTime: draggedAppointment.dataset.time,
        originalRoom: draggedAppointment.closest('.room-cell')?.dataset.roomId
    };
    
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('application/json', JSON.stringify(appointmentData));
    
    // Görsel efekt
    draggedAppointment.classList.add('dragging');
    draggedAppointment.style.opacity = '0.5';
}

function handleDragEnd(e) {
    // Görsel efektleri temizle
    e.target.classList.remove('dragging');
    e.target.style.opacity = '';
    
    // Tüm hücrelerdeki vurgu efektlerini temizle
    document.querySelectorAll('.room-cell').forEach(cell => {
        cell.classList.remove('drag-over');
    });
    
    draggedAppointment = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    // Hedef hücreyi vurgula
    const cell = e.currentTarget;
    if (!cell.classList.contains('drag-over')) {
        cell.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

// GÜNCELLENEN handleDrop - DANIŞAN KONTROLÜ DAHİL
async function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const cell = e.currentTarget;
    cell.classList.remove('drag-over');

    if (!draggedAppointment) {
        console.error('Sürüklenen randevu bulunamadı!');
        return;
    }

    // Hedef bilgilerini al
    const newRoomId = cell.dataset.roomId;
    const newTime = cell.dataset.time;
    const currentDate = document.getElementById('schedule-date').value;
    const appointmentId = draggedAppointment.dataset.appointmentId;
    
    if (!appointmentId || !newRoomId || !newTime) {
        console.error('Eksik bilgi:', { appointmentId, newRoomId, newTime });
        alert('Randevu taşıma için gerekli bilgiler eksik!');
        return;
    }

    // Aynı yere bırakıldıysa işlem yapma
    const originalCell = draggedAppointment.closest('.room-cell');
    if (originalCell && originalCell.dataset.roomId === newRoomId && originalCell.dataset.time === newTime) {
        return;
    }

    // Önce mevcut randevunun danışan bilgisini alalım
    try {
        showLoading();
        
        const appointmentResponse = await fetch('ajax/get_appointment.php?id=' + appointmentId);
        const appointmentData = await appointmentResponse.json();
        
        if (!appointmentData.success) {
            hideLoading();
            alert('Randevu bilgileri alınamadı!');
            return;
        }
        
        const danisanId = appointmentData.data.danisan_id;
        const newDateTime = `${currentDate} ${newTime}:00`;
        
        // Çakışma kontrolü yap (hem oda hem danışan)
        const conflictCheck = await checkConflictsWithDanisan(newRoomId, newDateTime, appointmentId, danisanId);
        
        hideLoading();
        
        if (conflictCheck.hasConflict) {
            if (conflictCheck.danisanConflict) {
                alert(conflictCheck.message);
            } else if (conflictCheck.roomConflict) {
                alert('Bu oda ve saatte başka bir randevu bulunmaktadır!');
            }
            return;
        }
        
        // Kullanıcıya onay sor
        if (!confirm(`Randevuyu ${newTime} saatine taşımak istediğinizden emin misiniz?`)) {
            return;
        }

        // Randevuyu güncelle
        updateAppointmentRoom(appointmentId, newRoomId, newTime, currentDate);
        
    } catch (error) {
        hideLoading();
        console.error('Hata:', error);
        alert('Bir hata oluştu!');
    }
}

// YENİ FONKSİYON: Danışan dahil çakışma kontrolü
async function checkConflictsWithDanisan(roomId, datetime, appointmentId, danisanId) {
    try {
        const requestData = {
            room_id: roomId,
            datetime: datetime,
            appointment_id: appointmentId || '',
            danisan_id: danisanId || ''
        };
        
        console.log('Çakışma kontrolü isteği:', requestData);
        
        const response = await fetch('ajax/check_conflicts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        // Önce response text'i alalım
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        // JSON parse edelim
        try {
            const data = JSON.parse(responseText);
            return {
                hasConflict: data.hasConflict || false,
                roomConflict: data.roomConflict || false,
                danisanConflict: data.danisanConflict || false,
                message: data.message || ''
            };
        } catch (parseError) {
            console.error('JSON parse hatası:', parseError);
            console.error('Response text:', responseText);
            alert('Sunucu yanıtı işlenemedi. Konsolu kontrol edin.');
            return { hasConflict: true, message: 'Çakışma kontrolü yapılamadı!' };
        }
    } catch (error) {
        console.error('Çakışma kontrolü hatası:', error);
        return { hasConflict: true, message: 'Çakışma kontrolü yapılamadı!' };
    }
}

function updateAppointmentRoom(appointmentId, roomId, time, date) {
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('room_id', roomId);
    formData.append('time', time);
    formData.append('date', date);

    // Loading göstergesi
    showLoading();

    fetch('ajax/update_appointment_room.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showSuccessMessage('Randevu başarıyla taşındı!');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Hata: ' + (data.message || 'Randevu güncellenemedi'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('AJAX hatası:', error);
        alert('Bir hata oluştu! Lütfen tekrar deneyin.');
    });
}

// RANDEVU EKLEME FONKSİYONU
function handleAppointmentAdd(datetime, roomId) {
    const form = document.getElementById('appointmentForm');
    if (!form) {
        console.error('appointmentForm bulunamadı!');
        return;
    }
    
    form.reset();
    
    // Form elemanlarını doldur
    const ajaxActionInput = form.querySelector('input[name="ajax_action"]');
    const idInput = form.querySelector('input[name="id"]');
    const roomSelect = form.querySelector('select[name="room_id"]');
    const dateInput = form.querySelector('input[name="randevu_tarih"]');
    const timeSelect = form.querySelector('select[name="randevu_saat"]');
    
    if (ajaxActionInput) ajaxActionInput.value = 'randevu_ekle';
    if (idInput) idInput.value = '';
    if (roomSelect) roomSelect.value = roomId;
    
    const [date, time] = datetime.split(' ');
    if (dateInput) dateInput.value = date;
    
    // Saati doğru formatta set et
    if (timeSelect) {
        const formattedTime = time;
        console.log('Saat set ediliyor:', formattedTime);

        // Önce select'i temizle ve varsayılan option'ı koru
        const defaultOption = timeSelect.querySelector('option[value=""]');
        
        // Tüm option'ları kontrol et
        let found = false;
        Array.from(timeSelect.options).forEach(option => {
            if (option.value === formattedTime) {
                option.selected = true;
                found = true;
            } else {
                option.selected = false;
            }
        });
        
        // Eğer bulunamadıysa ve varsayılan dışında bir saat ise, hata ver
        if (!found && formattedTime !== ':00') {
            console.error('Saat bulunamadı:', formattedTime);
            // En yakın saati seç
            const hour = parseInt(time.split(':')[0]);
            if (hour >= 8 && hour <= 21) {
                timeSelect.value = formattedTime;
            }
        }
    }
    
    // Oda seçili olduğu için kilitli saatleri yükle
    if (roomId && date) {
        loadRoomLockedTimes(roomId, date);
    }
    
    // Diğer alanları sıfırla
    document.getElementById('danisan_satis_id').value = '';
    document.getElementById('selectedPackage').innerHTML = '<em class="text-muted">Danışan seçince görünecek</em>';
    document.getElementById('appointmentDetails').style.display = 'none';
    document.getElementById('deleteAppointmentBtn').style.display = 'none';
    
    currentAppointmentId = null;
    currentDanisanId = null;
    
    if (appointmentModal) {
        appointmentModal.show();
    } else {
        console.error('Modal bulunamadı!');
    }
}

// RANDEVU DÜZENLEME FONKSİYONU
function handleAppointmentEdit(appointmentId, event) {
    if (event) event.stopPropagation();

    showLoading();

    fetch('ajax/get_appointment.php?id=' + appointmentId)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (!data.success) {
                alert('Randevu bilgileri alınamadı: ' + data.message);
                return;
            }
            
            const appointment = data.data;
            const form = document.getElementById('appointmentForm');
            
            // Set current danisan ID for tabs
            currentDanisanId = appointment.danisan_id;
            
            // Load appointment details first
            if (appointment.satis_id) {
                loadAppointmentDetails(appointment.satis_id, appointmentId);
            }
            
            // Form alanlarını doldur
            form.querySelector('input[name="ajax_action"]').value = 'randevu_guncelle';
            form.querySelector('input[name="id"]').value = appointment.id;
            
            // Hidden field'ları direkt doldur (danışan her zaman aynı kalacak)
            document.getElementById('danisan_id').value = appointment.danisan_id;
            document.getElementById('seans_turu_id').value = appointment.seans_turu_id || '';
            document.getElementById('satis_id').value = appointment.satis_id || '';
            
            // Danışan satış select'ini kontrol et
            const danisanSatisSelect = form.querySelector('select[name="danisan_satis_id"]');
            let matchingOption = Array.from(danisanSatisSelect.options).find(option => 
                option.getAttribute('data-danisan-id') == appointment.danisan_id
            );
            
            // Eğer mevcut listede yoksa (paket süresi dolmuş vs), yeni bir option ekle
            if (!matchingOption && appointment.danisan_adi) {
                const newOption = document.createElement('option');
                newOption.value = appointment.satis_id || appointment.id;
                newOption.setAttribute('data-danisan-id', appointment.danisan_id);
                newOption.setAttribute('data-seans-turu-id', appointment.seans_turu_id || '');
                newOption.textContent = appointment.danisan_adi + ' (Mevcut Randevu)';
                danisanSatisSelect.appendChild(newOption);
                matchingOption = newOption;
            }
            
            if (matchingOption) {
                danisanSatisSelect.value = matchingOption.value;
                // Package bilgisini göster
                const packageText = appointment.paket_adi || 'Paket bilgisi';
                document.getElementById('selectedPackage').innerHTML = `<strong>${packageText}</strong>`;
                
                // Detayları yükle
                if (appointment.satis_id) {
                    loadDanisanDetails(appointment.satis_id);
                }
            }
            
            // Diğer form alanlarını doldur
            form.querySelector('select[name="personel_id"]').value = appointment.personel_id;
            form.querySelector('select[name="room_id"]').value = appointment.room_id;

            const [datePart, timePart] = appointment.randevu_tarihi.split(' ');
            form.querySelector('input[name="randevu_tarih"]').value = datePart;
            form.querySelector('select[name="randevu_saat"]').value = timePart.substring(0, 5);
            form.querySelector('textarea[name="notlar"]').value = appointment.notlar || '';

            // Evaluation notes handling
            const evaluationSection = form.querySelector('.evaluation-notes-section');
            const evaluationLabel = form.querySelector('.evaluation-notes-label');
            const evaluationNotes = form.querySelector('textarea[name="evaluation_notes"]');

            if (appointment.evaluation_number) {
                evaluationSection.style.display = 'block';
                evaluationLabel.textContent = `${appointment.evaluation_number}. Değerlendirme Notları`;
                evaluationNotes.value = appointment.evaluation_notes || '';
            } else {
                evaluationSection.style.display = 'none';
            }

            // Oda ve tarih seçili olduğu için kilitli saatleri yükle
            if (appointment.room_id && datePart) {
                loadRoomLockedTimes(appointment.room_id, datePart);
            }

            currentAppointmentId = appointmentId;
            document.getElementById('deleteAppointmentBtn').style.display = 'inline-block';
            
            appointmentModal.show();
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Bir hata oluştu!');
        });
}

// GÜNCELLENEN saveAppointment - DANIŞAN KONTROLÜ DAHİL
async function saveAppointment() {
    const form = document.getElementById('appointmentForm');
    const formData = new FormData(form);
    
    // Validasyon
    const requiredFields = ['danisan_satis_id', 'personel_id', 'room_id', 'randevu_tarih', 'randevu_saat'];
    for (const field of requiredFields) {
        const element = form.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            alert(`Lütfen tüm zorunlu alanları doldurun!`);
            return;
        }
    }
    
    // Kilitli saat kontrolü
    const selectedTime = formData.get('randevu_saat');
    if (currentRoomLockedTimes.includes(selectedTime)) {
        alert('Seçilen saat bu oda için kilitli! Lütfen başka bir saat seçin.');
        return;
    }
    
    // Randevu tarih/saat formatını düzenle
    const tarih = formData.get('randevu_tarih');
    const saat = formData.get('randevu_saat');
    const randevuTarihi = tarih + ' ' + saat + ':00';
    
    // Danışan ID'sini al
    const danisanId = document.getElementById('danisan_id').value;
    const roomId = formData.get('room_id');
    const appointmentId = formData.get('id');
    
    // Çakışma kontrolü (danışan dahil)
    const conflictCheck = await checkConflictsWithDanisan(roomId, randevuTarihi, appointmentId, danisanId);
    
    if (conflictCheck.hasConflict) {
        if (conflictCheck.danisanConflict) {
            alert(conflictCheck.message || 'Bu danışanın aynı saatte başka bir randevusu var!');
        } else if (conflictCheck.roomConflict) {
            alert('Bu oda ve saatte başka bir randevu bulunmaktadır!');
        }
        return;
    }
    
    formData.delete('randevu_tarih');
    formData.delete('randevu_saat');
    formData.append('randevu_tarihi', randevuTarihi);
    
    // Add evaluation notes if section is visible
    const evaluationSection = form.querySelector('.evaluation-notes-section');
    if (evaluationSection.style.display !== 'none') {
        const evaluationNotes = form.querySelector('textarea[name="evaluation_notes"]').value;
        formData.set('evaluation_notes', evaluationNotes);
    }
    
    showLoading();
    
    fetch('ajax/save_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showSuccessMessage('Randevu başarıyla kaydedildi!');
            appointmentModal.hide();
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Hata: ' + (data.message || 'Randevu kaydedilemedi'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    });
}

// RANDEVU SİLME
function deleteCurrentAppointment() {
    if (!currentAppointmentId) {
        alert('Silinecek randevu bulunamadı!');
        return;
    }
    
    if (!confirm('Bu randevuyu silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    showLoading();
    
    fetch('ajax/delete_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ appointment_id: currentAppointmentId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showSuccessMessage('Randevu başarıyla silindi!');
            appointmentModal.hide();
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Hata: ' + (data.message || 'Randevu silinemedi'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    });
}

function changeDateWithFilters(newDate) {
    const url = new URL(window.location);
    url.searchParams.set('date', newDate);
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('terapist');
    url.searchParams.delete('danisan');
    window.location.href = url.toString();
}

function removeFilter(filterType) {
    const url = new URL(window.location);
    url.searchParams.delete(filterType);
    window.location.href = url.toString();
}

document.getElementById('terapist').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('danisan').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

// TARİH NAVİGASYON
function changeDate(days) {
    const currentDate = new Date(document.getElementById('schedule-date').value);
    currentDate.setDate(currentDate.getDate() + days);
    const newDate = currentDate.toISOString().split('T')[0];
    window.location.href = 'room_schedule.php?date=' + newDate;
}

// KİLİTLİ SAATLER
function loadRoomLockedTimes(roomId, date) {
    fetch(`ajax/get_room_locked_times.php?room_id=${roomId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentRoomLockedTimes = data.locked_times.map(item => item.time);
                updateTimeOptions();
                
                const infoElement = document.getElementById('lockedTimeInfo');
                if (currentRoomLockedTimes.length > 0) {
                    infoElement.style.display = 'block';
                } else {
                    infoElement.style.display = 'none';
                }
            } else {
                console.error('Kilitli saatler yüklenemedi:', data.message);
                resetTimeOptions();
            }
        })
        .catch(error => {
            console.error('Kilitli saatler yüklenirken hata:', error);
            resetTimeOptions();
        });
}

function updateTimeOptions() {
    const timeSelect = document.getElementById('randevu_saat');
    if (!timeSelect) return;

    Array.from(timeSelect.options).forEach(option => {
        if (option.value) {
            option.classList.remove('locked-time-option');
            option.disabled = false;
            option.style.backgroundColor = '';
            option.style.color = '';
            option.textContent = option.textContent.replace(' 🔒', '');
        }
    });

    currentRoomLockedTimes.forEach(lockedTime => {
        const option = timeSelect.querySelector(`option[value="${lockedTime}"]`);
        if (option) {
            option.classList.add('locked-time-option');
            option.disabled = true;
            option.style.backgroundColor = '#e9ecef';
            option.style.color = '#6c757d';
            option.textContent = option.textContent.replace(' 🔒', '') + ' 🔒';
        }
    });

    if (timeSelect.value && currentRoomLockedTimes.includes(timeSelect.value)) {
        timeSelect.value = '';
        alert('Seçilen saat bu oda için kilitli! Lütfen başka bir saat seçin.');
    }
}

function resetTimeOptions() {
    const timeSelect = document.getElementById('randevu_saat');
    if (!timeSelect) return;

    Array.from(timeSelect.options).forEach(option => {
        if (option.value) {
            option.classList.remove('locked-time-option');
            option.disabled = false;
            option.style.backgroundColor = '';
            option.style.color = '';
            option.textContent = option.textContent.replace(' 🔒', '');
        }
    });

    currentRoomLockedTimes = [];
    const infoElement = document.getElementById('lockedTimeInfo');
    if (infoElement) {
        infoElement.style.display = 'none';
    }
}

// DANIŞAN DETAYLARI
function loadDanisanDetails(satisId) {
    fetch(`ajax/get_danisan_satis.php?satis_id=${satisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const satis = data.satis;
                document.getElementById('totalSessions').textContent = satis.toplam_seans;
                document.getElementById('remainingSessions').textContent = satis.kalan_seans;
                document.getElementById('nextSessionNumber').textContent = satis.kullanilan_seans + 1;
                document.getElementById('paymentStatus').textContent = `₺${satis.odenen_tutar} / ₺${satis.toplam_tutar}`;
                
                const paymentElement = document.getElementById('paymentStatus');
                const paymentPercent = (satis.odenen_tutar / satis.toplam_tutar) * 100;
                
                if (paymentPercent >= 100) {
                    paymentElement.className = 'fw-bold text-success';
                } else if (paymentPercent >= 50) {
                    paymentElement.className = 'fw-bold text-warning';
                } else {
                    paymentElement.className = 'fw-bold text-danger';
                }
                
                document.getElementById('appointmentDetails').style.display = 'block';
            } else {
                document.getElementById('appointmentDetails').style.display = 'none';
                if (data.message) {
                    console.log('Uyarı: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Danışan detayları yüklenirken hata:', error);
            document.getElementById('appointmentDetails').style.display = 'none';
        });
}

// YARDIMCI FONKSİYONLAR
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingIndicator';
    loadingDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div>';
    loadingDiv.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;';
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById('loadingIndicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Sayfa yüklendikten sonra drag and drop'u yeniden başlat
window.addEventListener('load', function() {
    setTimeout(function() {
        initializeDragAndDrop();
    }, 500);
});

// Global fonksiyonları window objesine ata
window.handleAppointmentAdd = handleAppointmentAdd;
window.handleAppointmentEdit = handleAppointmentEdit;
window.saveAppointment = saveAppointment;
window.changeDate = changeDate;
window.deleteCurrentAppointment = deleteCurrentAppointment;
window.saveFonksiyonelNot = saveFonksiyonelNot;
</script>
</body>
</html>