<?php
require_once 'functions.php';

// Haftalık görünüm için tarih hesaplamaları
$current_date = $_GET['date'] ?? date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_date)));

$rooms = getRooms();
$terapistler = getTerapistler();
$danisanlar = getDanisanlar();
$seans_turleri = getSeansTurleri();

// Türkçe gün isimleri
$gunler = [
    'Monday' => 'Pazartesi',
    'Tuesday' => 'Salı',
    'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe',
    'Friday' => 'Cuma',
    'Saturday' => 'Cumartesi',
    'Sunday' => 'Pazar'
];
?>

<style>
.weekly-schedule {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 20px 0;
    padding: 20px;
}

.schedule-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.schedule-table th, 
.schedule-table td {
    border: 1px solid #e0e0e0;
    padding: 10px;
    vertical-align: top;
    height: 100px;
}

.schedule-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    text-align: center;
    padding: 15px;
}

.schedule-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #fff;
    border-bottom: 2px solid #dee2e6;
}

.room-name {
    font-weight: 600;
    color: #2c3e50;
    background-color: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.appointment {
    background-color: #e3f2fd;
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 8px;
    cursor: move;
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.1);
    position: relative;
}

.appointment:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.appointment.dragging {
    opacity: 0.5;
    transform: scale(0.95);
}

.room-cell.drag-over {
    background-color: rgba(25, 118, 210, 0.1);
}

.add-appointment-btn {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background-color: #f8f9fa;
    color: #6c757d;
    border: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 8px;
    right: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 5;
}

.add-appointment-btn:hover {
    background-color: #e9ecef;
    color: #495057;
    border-color: #adb5bd;
}

.add-appointment-btn i {
    font-size: 14px;
}

.navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.date-navigation {
    display: flex;
    align-items: center;
    gap: 15px;
}

.current-week {
    font-size: 1.2em;
    font-weight: 500;
    color: #2c3e50;
}

.today {
    background-color: #e3f2fd !important;
}

.room-cell {
    min-width: 150px;
    position: relative;
    min-height: 120px;
    padding-top: 40px !important;
}

.appointment .time {
    font-weight: 600;
    color: #1976d2;
    font-size: 0.9em;
    margin-bottom: 4px;
}

.appointment .patient {
    font-weight: 500;
    margin-bottom: 2px;
}

.appointment .therapist {
    color: #666;
    font-size: 0.85em;
}

.edit-button {
    position: absolute;
    top: 5px;
    right: 5px;
    opacity: 0;
    transition: opacity 0.2s ease;
    background-color: #fff;
    border: 1px solid #1976d2;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    z-index: 2;
}

.appointment:hover .edit-button {
    opacity: 1;
}
</style>

<div class="container-fluid">
    <div class="navigation">
        <h2>Haftalık Oda Programı</h2>
        <div class="date-navigation">
            <button type="button" class="btn btn-outline-primary" onclick="handleWeekChange(-1)">
                <i class="fas fa-chevron-left"></i> Önceki Hafta
            </button>
            <span class="current-week">
                <?php echo date('d.m.Y', strtotime($week_start)) . ' - ' . date('d.m.Y', strtotime($week_end)); ?>
            </span>
            <button type="button" class="btn btn-outline-primary" onclick="handleWeekChange(1)">
                Sonraki Hafta <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="d-flex gap-2">
                 <a href="?page=randevular" class="btn btn-outline-primary">
                    <i class="bx bx-calendar"></i> Ay
                </a>
                <a href="?page=weekly_room_schedule" class="btn btn-outline-primary">
                    <i class="bx bx-calendar-week"></i> Hafta
                </a>
                <a href="?page=room_schedule" class="btn btn-outline-primary">
                    <i class="bx bx-calendar-event"></i> Gün
                </a>
                <button type="button" class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#randevuModal">
                    <i class="bx bx-plus"></i> Yeni Randevu
                </button>
            </div>
    </div>

    <div class="weekly-schedule">
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Oda / Saat</th>
                    <?php
                    $current_date = new DateTime($week_start);
                    $today = new DateTime();
                    for ($i = 0; $i < 7; $i++) {
                        $date_str = $current_date->format('Y-m-d');
                        $is_today = $current_date->format('Y-m-d') === $today->format('Y-m-d');
                        $gun_adi = $gunler[date('l', strtotime($date_str))];
                        echo '<th class="' . ($is_today ? 'today' : '') . '">';
                        echo $current_date->format('d.m.Y') . '<br>' . $gun_adi;
                        echo '</th>';
                        $current_date->modify('+1 day');
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td class="room-name"><?php echo htmlspecialchars($room['name']); ?></td>
                    <?php
                    $current_date = new DateTime($week_start);
                    for ($i = 0; $i < 7; $i++) {
                        $date_str = $current_date->format('Y-m-d');
                        $schedule = getRoomSchedule($date_str);
                        echo '<td class="room-cell" data-room-id="' . $room['id'] . '" data-date="' . $date_str . '">';
                        
                        // Add appointment button at the top of each cell
                        echo '<button type="button" class="add-appointment-btn" onclick="handleAppointmentAdd(\'' . $date_str . ' 09:00:00\', ' . $room['id'] . ')">';
                        echo '<i class="fas fa-plus"></i>';
                        echo '</button>';
                        
                        // Display appointments if they exist
                        if (isset($schedule[$room['id']]['appointments'])) {
                            foreach ($schedule[$room['id']]['appointments'] as $time => $apt) {
                                echo '<div class="appointment" draggable="true" data-appointment-id="' . $apt['id'] . '" data-time="' . $time . '">';
                                echo '<div class="time">' . $time . '</div>';
                                echo '<div class="patient">' . htmlspecialchars($apt['danisan']) . '</div>';
                                echo '<div class="therapist">' . htmlspecialchars($apt['terapist']) . '</div>';
                                echo '<button class="edit-button" onclick="handleAppointmentEdit(' . $apt['id'] . ', event)">Düzenle</button>';
                                echo '</div>';
                            }
                        }
                        
                        echo '</td>';
                        $current_date->modify('+1 day');
                    }
                    ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Randevu Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Randevu Ekle/Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="appointmentForm">
                    <input type="hidden" id="appointment_id" name="id">
                    <input type="hidden" id="room_id" name="room_id">
                    <input type="hidden" id="appointment_datetime" name="randevu_tarihi">
                    
                    <div class="mb-3">
                        <label class="form-label">Danışan</label>
                        <select class="form-select" id="danisan_id" name="danisan_id" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($danisanlar as $danisan): ?>
                            <option value="<?php echo $danisan['id']; ?>">
                                <?php echo htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Terapist</label>
                        <select class="form-select" id="personel_id" name="personel_id" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($terapistler as $terapist): ?>
                            <option value="<?php echo $terapist['id']; ?>">
                                <?php echo htmlspecialchars($terapist['ad'] . ' ' . $terapist['soyad']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saat</label>
                        <select class="form-select" id="appointment_time" required>
                            <?php
                            $start = strtotime('08:00');
                            $end = strtotime('21:00');
                            for ($time = $start; $time <= $end; $time += 3600) {
                                echo '<option value="' . date('H:i', $time) . '">' . date('H:i', $time) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Seans Türü</label>
                        <select class="form-select" id="seans_turu_id" name="seans_turu_id" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($seans_turleri as $seans): ?>
                            <option value="<?php echo $seans['id']; ?>">
                                <?php echo htmlspecialchars($seans['ad']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" id="notlar" name="notlar" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="handleAppointmentSave()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let appointmentModal = null;
let draggedAppointment = null;

document.addEventListener('DOMContentLoaded', function() {
    appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
    
    // Initialize time selection handling
    const timeSelect = document.getElementById('appointment_time');
    if (timeSelect) {
        timeSelect.addEventListener('change', function() {
            const dateInput = document.getElementById('appointment_datetime');
            const [datePart] = dateInput.value.split(' ');
            dateInput.value = datePart + ' ' + this.value + ':00';
        });
    }

    // Initialize drag and drop
    document.querySelectorAll('.appointment').forEach(appointment => {
        appointment.addEventListener('dragstart', handleDragStart);
        appointment.addEventListener('dragend', handleDragEnd);
    });

    document.querySelectorAll('.room-cell').forEach(cell => {
        cell.addEventListener('dragover', handleDragOver);
        cell.addEventListener('dragleave', handleDragLeave);
        cell.addEventListener('drop', handleDrop);
    });
});

function handleDragStart(e) {
    draggedAppointment = e.target;
    e.target.classList.add('dragging');
    e.dataTransfer.setData('text/plain', e.target.dataset.time);
}

function handleDragEnd(e) {
    e.target.classList.remove('dragging');
    document.querySelectorAll('.room-cell').forEach(cell => {
        cell.classList.remove('drag-over');
    });
    draggedAppointment = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    const cell = e.currentTarget;
    cell.classList.remove('drag-over');

    if (!draggedAppointment) return;

    const appointmentId = draggedAppointment.dataset.appointmentId;
    const newRoomId = cell.dataset.roomId;
    const newDate = cell.dataset.date;
    const originalTime = draggedAppointment.dataset.time;

    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('room_id', newRoomId);
    formData.append('date', newDate);
    formData.append('time', originalTime);

    fetch('ajax/update_appointment_room.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    });
}

function handleAppointmentAdd(datetime, roomId) {
    document.getElementById('appointment_id').value = '';
    document.getElementById('room_id').value = roomId;
    document.getElementById('appointment_datetime').value = datetime;
    document.getElementById('appointmentForm').reset();
    
    const timeSelect = document.getElementById('appointment_time');
    const [, timePart] = datetime.split(' ');
    const [hours] = timePart.split(':');
    timeSelect.value = `${hours}:00`;
    
    appointmentModal.show();
}

function handleAppointmentEdit(appointmentId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    fetch('ajax/get_appointment.php?id=' + appointmentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const appointment = data.data;
                document.getElementById('appointment_id').value = appointment.id;
                document.getElementById('room_id').value = appointment.room_id;
                document.getElementById('appointment_datetime').value = appointment.randevu_tarihi;
                document.getElementById('danisan_id').value = appointment.danisan_id;
                document.getElementById('personel_id').value = appointment.personel_id;
                document.getElementById('seans_turu_id').value = appointment.seans_turu_id;
                document.getElementById('notlar').value = appointment.notlar;
                
                const timeSelect = document.getElementById('appointment_time');
                const [, timePart] = appointment.randevu_tarihi.split(' ');
                const [hours] = timePart.split(':');
                timeSelect.value = `${hours}:00`;
                
                appointmentModal.show();
            } else {
                alert('Randevu bilgileri alınamadı: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu!');
        });
}

function handleAppointmentSave() {
    const form = document.getElementById('appointmentForm');
    const formData = new FormData(form);
    
    fetch('ajax/save_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appointmentModal.hide();
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    });
}

function handleWeekChange(weeks) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentDate = urlParams.get('date') || '<?php echo date('Y-m-d'); ?>';
    
    const date = new Date(currentDate);
    date.setDate(date.getDate() + (weeks * 7));
    
    const newDate = date.toISOString().split('T')[0];
    window.location.href = '?page=weekly_room_schedule&date=' + newDate;
}

// Make functions globally available
window.handleAppointmentAdd = handleAppointmentAdd;
window.handleAppointmentEdit = handleAppointmentEdit;
window.handleAppointmentSave = handleAppointmentSave;
window.handleWeekChange = handleWeekChange;
</script>