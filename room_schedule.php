<?php
include_once 'functions.php';
include 'partials/session.php';

// Room schedule page
$current_date = $_GET['date'] ?? date('Y-m-d');
$rooms = getRooms();
$terapistler = getTerapistler(true);
$danisanlar = getDanisanlarWithRemainingAppointments();
$seans_turleri = getSeansTurleri();

// DEBUG - Geçici olarak ekledik
error_log("DEBUG - Terapist sayısı: " . count($terapistler));
error_log("DEBUG - Danışan sayısı: " . count($danisanlar));
error_log("DEBUG - Seans türü sayısı: " . count($seans_turleri));

// Kilitli saatleri getir
$kilitli_saatler = getTumKilitliSaatler($current_date);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include "partials/title-meta.php"; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
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
                                                $schedule = getRoomSchedule($current_date);

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

    <!-- Randevu Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Randevu İşlemleri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                                        <?php for ($i = 8; $i <= 19; $i++): ?>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="deleteAppointmentBtn" style="display: none;" onclick="deleteCurrentAppointment()">Sil</button>
                    <button type="button" class="btn btn-primary" onclick="saveAppointment()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer-scripts.php'; ?>

    <script>
    // Global değişkenler
    var appointmentModal;
    var currentRoomLockedTimes = [];
    var currentAppointmentId = null;

    // DOM yüklendiğinde çalışacak fonksiyon
    document.addEventListener('DOMContentLoaded', function() {
        appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
        initializeEventListeners();
    });

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
                    }
                } else {
                    document.getElementById('selectedPackage').innerHTML = '<em class="text-muted">Danışan seçince görünecek</em>';
                    document.getElementById('appointmentDetails').style.display = 'none';
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

        // Drag and drop event listener'larını ekle
        initializeDragAndDrop();
    }

    // Randevu ekleme fonksiyonu
    function handleAppointmentAdd(datetime, roomId) {
        const form = document.getElementById('appointmentForm');
        if (!form) {
            console.error('appointmentForm bulunamadı!');
            return;
        }
        
        form.reset();
        
        // Form elemanlarının varlığını kontrol et
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
        if (timeSelect) timeSelect.value = time;
        
        // Oda seçili olduğu için kilitli saatleri yükle
        if (roomId && date) {
            loadRoomLockedTimes(roomId, date);
        }
        
        // Diğer alanları sıfırla
        const danisanSatisSelect = document.getElementById('danisan_satis_id');
        if (danisanSatisSelect) {
            danisanSatisSelect.value = '';
        }
        
        const selectedPackage = document.getElementById('selectedPackage');
        if (selectedPackage) {
            selectedPackage.innerHTML = '<em class="text-muted">Danışan seçince görünecek</em>';
        }
        
        const appointmentDetails = document.getElementById('appointmentDetails');
        if (appointmentDetails) {
            appointmentDetails.style.display = 'none';
        }
        
        const deleteBtn = document.getElementById('deleteAppointmentBtn');
        if (deleteBtn) {
            deleteBtn.style.display = 'none';
        }
        
        if (appointmentModal) {
            appointmentModal.show();
        }
    }

    // Randevu düzenleme fonksiyonu
    function handleAppointmentEdit(appointmentId, event) {
        if (event) event.stopPropagation();

        fetch('ajax/get_appointment.php?id=' + appointmentId)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Randevu bilgileri alınamadı: ' + data.message);
                    return;
                }
                const appointment = data.data;
                
                const form = document.getElementById('appointmentForm');
                form.querySelector('input[name="ajax_action"]').value = 'randevu_guncelle';
                form.querySelector('input[name="id"]').value = appointment.id;
                
                // Danışan satış ID'sini bul ve seç
                const danisanSatisSelect = form.querySelector('select[name="danisan_satis_id"]');
                const matchingOption = Array.from(danisanSatisSelect.options).find(option => 
                    option.getAttribute('data-danisan-id') == appointment.danisan_id &&
                    option.getAttribute('data-seans-turu-id') == appointment.seans_turu_id
                );
                
                if (matchingOption) {
                    danisanSatisSelect.value = matchingOption.value;
                    // Change event'ini tetikle
                    danisanSatisSelect.dispatchEvent(new Event('change'));
                }
                
                form.querySelector('select[name="personel_id"]').value = appointment.personel_id;
                form.querySelector('select[name="room_id"]').value = appointment.room_id;

                const [datePart, timePart] = appointment.randevu_tarihi.split(' ');
                form.querySelector('input[name="randevu_tarih"]').value = datePart;
                form.querySelector('select[name="randevu_saat"]').value = timePart.substring(0, 5);
                form.querySelector('textarea[name="notlar"]').value = appointment.notlar || '';

                // Oda ve tarih seçili olduğu için kilitli saatleri yükle
                if (appointment.room_id && datePart) {
                    loadRoomLockedTimes(appointment.room_id, datePart);
                }

                currentAppointmentId = appointmentId;
                document.getElementById('deleteAppointmentBtn').style.display = 'inline-block';
                appointmentModal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu!');
            });
    }

    // Tarih navigasyon fonksiyonları
    function changeDate(days) {
        const currentDate = new Date(document.getElementById('schedule-date').value);
        currentDate.setDate(currentDate.getDate() + days);
        const newDate = currentDate.toISOString().split('T')[0];
        window.location.href = 'room_schedule.php?date=' + newDate;
    }

    // Randevu kaydetme fonksiyonu
    function saveAppointment() {
        const form = document.getElementById('appointmentForm');
        const formData = new FormData(form);
        
        // Gerekli alanların dolu olduğunu kontrol et
        const requiredFields = ['danisan_satis_id', 'personel_id', 'room_id', 'randevu_tarih', 'randevu_saat'];
        let hasError = false;
        
        for (const field of requiredFields) {
            const element = form.querySelector(`[name="${field}"]`);
            if (!element || !element.value.trim()) {
                alert(`${field} alanı zorunludur!`);
                hasError = true;
                break;
            }
        }
        
        if (hasError) return;
        
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
        
        // FormData'yı güncelle
        formData.delete('randevu_tarih');
        formData.delete('randevu_saat');
        formData.append('randevu_tarihi', randevuTarihi);
        
        fetch('ajax/save_appointment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Randevu başarıyla kaydedildi!');
                appointmentModal.hide();
                location.reload();
            } else {
                alert('Hata: ' + (data.message || 'Randevu kaydedilemedi'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu!');
        });
    }

    // Randevu silme
    function deleteCurrentAppointment() {
        if (!currentAppointmentId) {
            alert('Silinecek randevu bulunamadı!');
            return;
        }
        
        if (!confirm('Bu randevuyu silmek istediğinizden emin misiniz?')) {
            return;
        }
        
        fetch('ajax/delete_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: currentAppointmentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Randevu başarıyla silindi!');
                appointmentModal.hide();
                location.reload();
            } else {
                alert('Hata: ' + (data.message || 'Randevu silinemedi'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu!');
        });
    }

    // Oda kilitli saatlerini yükle
    function loadRoomLockedTimes(roomId, date) {
        fetch(`ajax/get_room_locked_times.php?room_id=${roomId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentRoomLockedTimes = data.locked_times.map(item => item.time);
                    updateTimeOptions();
                    
                    // Bilgi mesajını göster
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

    // Saat seçeneklerini güncelle (kilitli saatleri işaretle)
    function updateTimeOptions() {
        const timeSelect = document.getElementById('randevu_saat');
        if (!timeSelect) return;

        // Tüm seçenekleri normal duruma getir
        Array.from(timeSelect.options).forEach(option => {
            if (option.value) {
                option.classList.remove('locked-time-option');
                option.disabled = false;
                option.style.backgroundColor = '';
                option.style.color = '';
                option.textContent = option.textContent.replace(' 🔒', '');
            }
        });

        // Kilitli saatleri işaretle ve devre dışı bırak
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

        // Eğer seçili saat kilitli ise, seçimi temizle
        if (timeSelect.value && currentRoomLockedTimes.includes(timeSelect.value)) {
            timeSelect.value = '';
            alert('Seçilen saat bu oda için kilitli! Lütfen başka bir saat seçin.');
        }
    }

    // Saat seçeneklerini sıfırla
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
        document.getElementById('lockedTimeInfo').style.display = 'none';
    }

    // Danışan detayları yükleme
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
                    
                    // Ödeme durumu rengini ayarla
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
                        alert('Uyarı: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Danışan detayları yüklenirken hata:', error);
                document.getElementById('appointmentDetails').style.display = 'none';
            });
    }

    // Drag and drop fonksiyonları
    function initializeDragAndDrop() {
        // Tüm randevu elemanlarına drag event'leri ekle
        document.querySelectorAll('.appointment').forEach(appointment => {
            appointment.addEventListener('dragstart', handleDragStart);
            appointment.addEventListener('dragend', handleDragEnd);
        });

        // Tüm kilitli olmayan hücrelere drop event'leri ekle
        document.querySelectorAll('.room-cell:not(.locked)').forEach(cell => {
            cell.addEventListener('dragover', handleDragOver);
            cell.addEventListener('dragleave', handleDragLeave);
            cell.addEventListener('drop', handleDrop);
        });
    }

    function handleDragStart(e) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', e.target.outerHTML);
        e.target.classList.add('dragging');
    }

    function handleDragEnd(e) {
        e.target.classList.remove('dragging');
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        e.target.classList.add('drag-over');
        return false;
    }

    function handleDragLeave(e) {
        e.target.classList.remove('drag-over');
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        e.target.classList.remove('drag-over');
        
        // Drag drop işlemi burada yapılacak
        console.log('Drop işlemi:', e.target);
        return false;
    }

    // Sayfa yüklendikten sonra drag and drop'u yeniden başlat
    window.addEventListener('load', function() {
        setTimeout(function() {
            initializeDragAndDrop();
        }, 500);
    });

    // Global fonksiyonları window objesine ata (onclick event'ları için)
    </script>

</body>
</html>