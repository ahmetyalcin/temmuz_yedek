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

    /* Yeni: Not ekleme kutusunu biraz ayıralım */
    .general-note-box { border: 1px solid #e9ecef; border-radius: 8px; padding: 12px; margin-bottom: 12px; background:#f8f9fb; }
    .tabs-hidden { display:none !important; } /* Add modunda tab başlıklarını tamamen gizlemek için */
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
                                         
                                            <a href="weekly_room_schedule.php" class="btn btn-outline-primary">Hafta</a>
                                            <a href="room_schedule.php" class="btn btn-outline-primary active">Gün</a>
                                               <a href="randevulist.php" class="btn btn-outline-primary">Liste</a>
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
                    <!-- Tab Navigation (add modunda gizlenecek) -->
                    <ul class="nav nav-tabs mb-3" id="appointmentTabsNav" role="tablist">
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
                    <div class="tab-content" id="appointmentTabs">
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
                                                        <?php echo htmlspecialchars($terapistler[$terapist['id']]['ad'] ?? $terapist['ad'])." " .htmlspecialchars($terapistler[$terapist['id']]['soyad'] ?? $terapist['soyad']) ?>
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
                            <!-- Yeni: Not ekleme kutusu -->
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

   <script src="assets/js/RoomSchedule.js"></script>

   <!-- room_schedule.php için düzeltilmiş JavaScript kodu -->
</body>
</html>