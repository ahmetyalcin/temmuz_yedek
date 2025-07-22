<?php
require_once 'functions.php';

// Haftalık görünüm için tarih hesaplamaları
$current_date = $_GET['date'] ?? date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($current_date)));

$rooms = getRooms();
$terapistler = getTerapistler(true);
$danisanlar = getDanisanlarWithRemainingAppointments();
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
    background-color: #ffffff;
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 8px;
    cursor: move;
    transition: all 0.2s ease;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    position: relative;
}

.appointment:hover {
    transform: translateY(-2px);
    border-color: #3b82f6;
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.15);
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

.evaluation-appointment {
    border-left: 4px solid #0d6efd !important;
}

.badge-evaluation {
    background-color: #fd7e14 !important;
    color: white !important;
}

.appointment-type-badge {
    padding: 0.25em 0.6em;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.badge-initial {
    background-color: #0d6efd;
    color: white;
}

.badge-evaluation {
    background-color: #fd7e14;
    color: white;
}

.badge-final {
    background-color: #198754;
    color: white;
}

.badge-normal {
    background-color: #6c757d;
    color: white;
}

.fc-event.gift-session {
    background-color: #FFB6C1 !important;
}

.evaluation-notes {
    background-color: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    margin-top: 8px;
    font-size: 0.9em;
}

.badge {
    display: inline-block;
    padding: 0.4em 0.6em;
    margin-bottom: 4px;
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
                <i class="fas fa-calendar"></i> Ay
            </a>
            <a href="?page=weekly_room_schedule" class="btn btn-outline-primary">
                <i class="fas fa-calendar-week"></i> Hafta
            </a>
            <a href="?page=room_schedule" class="btn btn-outline-primary">
                <i class="fas fa-calendar-day"></i> Gün
            </a>
            <button type="button" class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#randevuModal">
                <i class="fas fa-plus"></i> Yeni Randevu
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
                        
                       // echo '<button type="button" class="add-appointment-btn" onclick="handleAppointmentAdd(\'' . $date_str . ' 09:00:00\', ' . $room['id'] . ')">';
                        echo '<i class="fas fa-plus"></i>';
                        echo '</button>';
                        
          
                        if (isset($schedule[$room['id']]['appointments'])) {
                            foreach ($schedule[$room['id']]['appointments'] as $time => $apt) {
                                $evaluationClass = '';
                                $evaluationBadge = '';
                                
                                // Add proper checks for evaluation_type
                                if (isset($apt['evaluation_type']) && $apt['evaluation_type']) {
                                    $evaluationClass = 'evaluation-appointment';
                                    if ($apt['evaluation_type'] === 'initial') {
                                        $evaluationBadge = '<span class="badge bg-primary">İlk Değerlendirme</span>';
                                    } elseif ($apt['evaluation_type'] === 'progress') {
                                        $evaluationBadge = '<span class="badge bg-warning">' . 
                                            (isset($apt['evaluation_number']) ? $apt['evaluation_number'] : '') . 
                                            '. Değerlendirme</span>';
                                    } elseif ($apt['evaluation_type'] === 'final') {
                                        $evaluationBadge = '<span class="badge bg-success">Son Değerlendirme</span>';
                                    }
                                }
                                
                                echo '<div class="appointment ' . $evaluationClass . '" draggable="true" data-appointment-id="' . $apt['id'] . '" data-time="' . $time . '">';
                                echo '<div class="time">' . $time . '</div>';
                                echo '<div class="patient">' . htmlspecialchars($apt['danisan']) . ' ' . $evaluationBadge . '</div>';
                                echo '<div class="therapist">' . htmlspecialchars($apt['terapist']) . '</div>';
                                
                                // Add check for evaluation_notes
                                if (isset($apt['evaluation_notes']) && $apt['evaluation_notes']) {
                                    echo '<div class="evaluation-notes">' . htmlspecialchars($apt['evaluation_notes']) . '</div>';
                                }
                                
                                echo '<button class="edit-button" onclick="handleAppointmentEdit(\'' . $apt['id'] . '\', event)">Düzenle</button>';
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
<div class="modal fade" id="randevuModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Randevu Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="appointmentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" 
                                data-bs-target="#details" type="button" role="tab">
                            Randevu Bilgileri
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" 
                                data-bs-target="#appointments" type="button" role="tab">
                            Randevu Listesi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payments-tab" data-bs-toggle="tab" 
                                data-bs-target="#payments" type="button" role="tab">
                            Ödeme Geçmişi
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="appointmentTabContent">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                 
                        <div class="session-info alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Toplam Seans: <span id="totalSessions">-</span></span>
                            <span>Kalan Seans: <span id="remainingSessions">-</span></span>
                        </div>
                         </div>


                        <form id="appointmentForm" method="POST">
                            <input type="hidden" name="ajax_action" value="randevu_ekle">
                            <input type="hidden" name="id">
                            <input type="hidden" name="current_view">
                            <input type="hidden" name="current_date">
                            <input type="hidden" name="danisan_id">
                            
                            <div class="mb-3">
                                <label class="form-label">Terapist</label>
                                <select name="personel_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($terapistler as $terapist): ?>
                                        <option value="<?php echo $terapist['id']; ?>">
                                            <?php echo htmlspecialchars($terapist['ad'] . ' ' . $terapist['soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Oda</label>
                                <select name="room_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>">
                                            <?php echo htmlspecialchars($room['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tarih</label>
                                    <input type="date" name="randevu_tarih" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Saat</label>
                                    <select name="randevu_saat" class="form-select" required>
                                        <option value="">Seçiniz</option>
                                        <?php 
                                        for($saat = 8; $saat <= 21; $saat++) {
                                            $formatted = sprintf("%02d:00", $saat);
                                            echo "<option value=\"{$formatted}\">{$formatted}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notlar</label>
                                <textarea name="notlar" class="form-control" rows="3"></textarea>
                            </div>
              
                <div class="evaluation-notes-section mb-3" style="display: none;">
                    <label class="form-label evaluation-notes-label">Değerlendirme Notları</label>
                    <textarea name="evaluation_notes" class="form-control" rows="3"></textarea>
                </div>

                        </form>
                    </div>

                    <!-- Appointments Tab -->
                    <div class="tab-pane fade" id="appointments" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Saat</th>
                                        <th>Terapist</th>
                                        <th>Oda</th>
                                        <th>Tür</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsList"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Tab -->
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
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveAppointment()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let appointmentModal = null;
let draggedAppointment = null;

document.addEventListener('DOMContentLoaded', function() {
    appointmentModal = new bootstrap.Modal(document.getElementById('randevuModal'));
    
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
    const form = document.getElementById('appointmentForm');
    form.reset();
    form.querySelector('input[name="ajax_action"]').value = 'randevu_ekle';
    form.querySelector('input[name="id"]').value = '';
    form.querySelector('select[name="room_id"]').value = roomId;
    
    const [datePart, timePart] = datetime.split(' ');
    form.querySelector('input[name="randevu_tarih"]').value = datePart;
    form.querySelector('select[name="randevu_saat"]').value = timePart.substring(0, 5);
    
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
                loadAppointmentDetails(appointment.satis_id, appointmentId);
                
                const form = document.getElementById('appointmentForm');
                form.querySelector('input[name="ajax_action"]').value = 'randevu_guncelle';
                form.querySelector('input[name="id"]').value = appointment.id;
                form.querySelector('input[name="danisan_id"]').value = appointment.danisan_id;
                form.querySelector('select[name="personel_id"]').value = appointment.personel_id;
                form.querySelector('select[name="room_id"]').value = appointment.room_id;
                
                const [datePart, timePart] = appointment.randevu_tarihi.split(' ');
                form.querySelector('input[name="randevu_tarih"]').value = datePart;
                form.querySelector('select[name="randevu_saat"]').value = timePart.substring(0, 5);
                form.querySelector('textarea[name="notlar"]').value = appointment.notlar || '';
                
                // Updated evaluation notes handling based on evaluation_number
                const evaluationSection = form.querySelector('.evaluation-notes-section');
                const evaluationLabel = form.querySelector('.evaluation-notes-label');
                const evaluationNotes = form.querySelector('textarea[name="evaluation_notes"]');
                
                if (appointment.evaluation_number) {
                    evaluationSection.style.display = 'block';
                    evaluationLabel.textContent = `${appointment.evaluation_number}. Değerlendirme Notları`;
                    evaluationNotes.value = appointment.evaluation_notes || '';
                } else if (appointment.evaluation_type === 'initial') {
                    evaluationSection.style.display = 'block';
                    evaluationLabel.textContent = 'İlk Değerlendirme Notları';
                    evaluationNotes.value = appointment.evaluation_notes || '';
                } else if (appointment.evaluation_type === 'final') {
                    evaluationSection.style.display = 'block';
                    evaluationLabel.textContent = 'Son Değerlendirme Notları';
                    evaluationNotes.value = appointment.evaluation_notes || '';
                } else {
                    evaluationSection.style.display = 'none';
                }
                
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



async function loadAppointmentDetails(satis_id, appointmentId = null) {
    try {
        const response = await fetch(`ajax/get_randevu_detay.php?id=${satis_id}`);
        const data = await response.json();
        
        if (data.success) {
            const totalSessions = parseInt(data.satis.seans_adet || 0) + parseInt(data.satis.hediye_seans || 0);
            document.getElementById('totalSessions').textContent = totalSessions;

            const usedSessions = data.randevular ? data.randevular.length : 0;
            const remainingSessions = totalSessions - usedSessions;
            document.getElementById('remainingSessions').textContent = remainingSessions;

            const appointmentsList = document.getElementById('appointmentsList');
            appointmentsList.innerHTML = '';
            
            if (data.randevular && data.randevular.length > 0) {
                data.randevular.forEach(apt => {
                    const row = document.createElement('tr');
                    const aptDate = new Date(apt.randevu_tarihi);
                    const isPast = aptDate < new Date();
                    
                    if (isPast) {
                        row.classList.add('past-appointment');
                    }
                    
                    let typeLabel = 'Normal Seans';
                    let typeBadgeClass = 'badge-normal';
                    
                    if (apt.evaluation_type === 'initial') {
                        typeLabel = 'İlk Değerlendirme';
                        typeBadgeClass = 'badge-initial';
                    } else if (apt.evaluation_type === 'progress') {
                        typeLabel = `${apt.evaluation_number}. Değerlendirme`;
                        typeBadgeClass = 'badge-evaluation';
                    } else if (apt.evaluation_type === 'final') {
                        typeLabel = 'Son Değerlendirme';
                        typeBadgeClass = 'badge-final';
                    }
                    
                    row.innerHTML = `
                        <td>${aptDate.toLocaleDateString('tr-TR')}</td>
                        <td>${aptDate.toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</td>
                        <td>${apt.terapist_adi || '-'}</td>
                        <td>${apt.room_name || '-'}</td>
                        <td><span class="appointment-type-badge ${typeBadgeClass}">${typeLabel}</span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="deleteAppointment('${apt.id}')"
                                    ${isPast ? 'disabled' : ''}>
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    appointmentsList.appendChild(row);
                });
            } else {
                appointmentsList.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">Randevu bulunamadı</td>
                    </tr>
                `;
            }

            const paymentsList = document.getElementById('paymentsList');
            paymentsList.innerHTML = '';
            
            if (data.odemeler && data.odemeler.length > 0) {
                data.odemeler.forEach(payment => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${new Date(payment.odeme_tarihi).toLocaleDateString('tr-TR')}</td>
                        <td>${payment.tutar} ₺</td>
                        <td>${payment.odeme_tipi}</td>
                        <td>${data.satis.personel_adi}</td>
                    `;
                    paymentsList.appendChild(row);
                });
            } else {
                paymentsList.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">Ödeme bulunamadı</td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading appointment details:', error);
    }
}

async function deleteAppointment(appointmentId) {
    if (!confirm('Bu randevuyu silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    try {
        const response = await fetch('ajax/delete_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: appointmentId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    }
}

async function saveAppointment() {
    const form = document.getElementById('appointmentForm');
    const formData = new FormData(form);

    const tarih = form.querySelector('input[name="randevu_tarih"]').value;
    const saat = form.querySelector('select[name="randevu_saat"]').value;
    const roomId = form.querySelector('select[name="room_id"]').value;
    const datetime = tarih + 'T' + saat;
    const appointmentId = form.querySelector('input[name="id"]').value;
    const danisanId = form.querySelector('input[name="danisan_id"]').value;

    const evaluationSection = form.querySelector('.evaluation-notes-section');
    if (evaluationSection.style.display !== 'none') {
        const evaluationNotes = form.querySelector('textarea[name="evaluation_notes"]').value;
        formData.append('evaluation_notes', evaluationNotes);
    }

    try {
        const response = await fetch(`ajax/get_satis_bilgileri.php?danisan_id=${danisanId}`);
        const data = await response.json();
        
        if (data.success) {
            formData.append('satis_id', data.satis_id);
        }
    } catch (error) {
        console.error('Error fetching satis_id:', error);
    }

    formData.append('randevu_tarihi', datetime);

    try {
        const response = await fetch('ajax/save_appointment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            appointmentModal.hide();
            window.location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    }
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
window.saveAppointment = saveAppointment;
window.handleWeekChange = handleWeekChange;
window.deleteAppointment = deleteAppointment;
</script>
