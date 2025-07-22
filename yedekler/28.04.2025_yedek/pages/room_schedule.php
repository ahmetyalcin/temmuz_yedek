<?php
require_once 'functions.php';

// Room schedule page
$current_date = $_GET['date'] ?? date('Y-m-d');
$rooms = getRooms();
$terapistler = getTerapistler();
$danisanlar = getDanisanlar();
$seans_turleri = getSeansTurleri();
?>
<style>

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


.room-schedule {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 20px 0;
    padding: 20px;
    overflow-x: auto;
}

.room-schedule table {
    min-width: 100%;
    border-collapse: collapse;
}

.room-schedule th {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 1;
    padding: 15px;
    font-weight: 600;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.room-cell {
    min-width: 200px;
    min-height: 120px;
    padding: 8px;
    position: relative;
    border: 1px solid #dee2e6;
    vertical-align: top;
    padding-top: 40px !important;
}

.appointment {
    background-color: #f0f7ff;
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

.patient {
    font-weight: 500;
    margin-bottom: 2px;
}

.therapist {
    color: #666;
    font-size: 0.85em;
}

.session-type {
    font-size: 0.8em;
    color: #888;
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

.time-column {
    width: 80px;
    font-weight: bold;
    background-color: #f8f9fa;
    text-align: center;
    border: 1px solid #e0e0e0;
}
</style>

<div class="container-fluid">
    <div class="navigation">
        <h2>Günlük Oda Programı</h2>
        <div class="date-navigation">
        <button class="btn btn-outline-primary" onclick="changeDate(-1)">
                <i class="bx bx-chevron-left"></i>
            </button>
            <input type="date" class="form-control" id="schedule-date" value="<?php echo $current_date; ?>" 
                   onchange="window.location.href='?page=room_schedule&date=' + this.value">
            <button class="btn btn-outline-primary" onclick="changeDate(1)">
                <i class="bx bx-chevron-right"></i>
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




    
    <div class="room-schedule">
        <table class="table">
            <thead>
                <tr>
                    <th>Saat</th>
                    <?php foreach ($rooms as $room): ?>
                        <th><?php echo htmlspecialchars($room['name']); ?></th>
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
                    <?php foreach ($schedule as $room): ?>
                        <td class="room-cell" data-room-id="<?php echo $room['room_info']['id']; ?>" data-time="<?php echo $time; ?>">
                            <button type="button" class="add-appointment-btn" 
                                    onclick="addAppointment('<?php echo $current_date . ' ' . $time; ?>', <?php echo $room['room_info']['id']; ?>)">
                                <i class="fas fa-plus"></i>
                            </button>
                            
                            <?php if (isset($room['appointments'][$time])): 
                                $apt = $room['appointments'][$time];
                            ?>
                                <div class="appointment" 
                                     draggable="true" 
                                     data-appointment-id="<?php echo $apt['id']; ?>"
                                     data-time="<?php echo $time; ?>">
                                    <button class="edit-button" onclick="editAppointment(<?php echo $apt['id']; ?>, event)">
                                        Düzenle
                                    </button>
                                    <div class="patient"><?php echo htmlspecialchars($apt['danisan']); ?></div>
                                    <div class="therapist"><?php echo htmlspecialchars($apt['terapist']); ?></div>
                                    <div class="session-type"><?php echo htmlspecialchars($apt['seans_turu']); ?></div>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Randevu Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="appointmentModalLabel">Randevu Ekle/Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="appointmentForm">
                    <input type="hidden" id="appointment_id" name="id">
                    <input type="hidden" id="room_id" name="room_id">
                    <input type="hidden" id="appointment_datetime" name="randevu_tarihi">
                    
                    <div class="mb-3">
                        <label for="danisan_id" class="form-label">Danışan</label>
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
                        <label for="personel_id" class="form-label">Terapist</label>
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
                        <label for="seans_turu_id" class="form-label">Seans Türü</label>
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
                        <label for="notlar" class="form-label">Notlar</label>
                        <textarea class="form-control" id="notlar" name="notlar" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveAppointment()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let appointmentModal;
let draggedAppointment = null;

document.addEventListener('DOMContentLoaded', function() {
    appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
    
    // Sürükle-bırak işlemleri için event listener'ları ekle
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
    const newTime = cell.dataset.time;
    const currentDate = document.getElementById('schedule-date').value;

    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('room_id', newRoomId);
    formData.append('time', newTime);
    formData.append('date', currentDate);

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

function addAppointment(datetime, roomId) {
    document.getElementById('appointment_id').value = '';
    document.getElementById('room_id').value = roomId;
    document.getElementById('appointment_datetime').value = datetime;
    document.getElementById('appointmentForm').reset();
    
    appointmentModal.show();
}

function editAppointment(appointmentId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    fetch('ajax/get_appointment.php?id=' + appointmentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('appointment_id').value = data.data.id;
                document.getElementById('room_id').value = data.data.room_id;
                document.getElementById('appointment_datetime').value = data.data.randevu_tarihi;
                document.getElementById('danisan_id').value = data.data.danisan_id;
                document.getElementById('personel_id').value = data.data.personel_id;
                document.getElementById('seans_turu_id').value = data.data.seans_turu_id;
                document.getElementById('notlar').value = data.data.notlar;
                
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

function saveAppointment() {
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

function changeDate(days) {
    const dateInput = document.getElementById('schedule-date');
    const currentDate = new Date(dateInput.value);
    currentDate.setDate(currentDate.getDate() + days);
    
    const newDate = currentDate.toISOString().split('T')[0];
    window.location.href = '?page=room_schedule&date=' + newDate;
}
</script>