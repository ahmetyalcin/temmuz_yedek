<?php
include_once 'functions.php';
include 'partials/session.php';

// Room schedule page
$current_date = $_GET['date'] ?? date('Y-m-d');
$rooms = getRooms();
$terapistler = getTerapistler(true);
$danisanlar = getDanisanlarWithRemainingAppointments();
$seans_turleri = getSeansTurleri();
?>
 <style>
    /* --------------------------------------------------
       NAVIGATION
       -------------------------------------------------- */
    .navigation {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: .5rem;
      margin-bottom: 1rem;
    }
    .date-navigation,
    .controls-group {
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    @media (max-width: 768px) {
      .navigation {
        flex-direction: column;
        align-items: stretch;
      }
      .date-navigation,
      .controls-group {
        width: 100%;
        justify-content: flex-start;
      }
    }

    /* --------------------------------------------------
       TABLE & STICKY FIRST COLUMN
       -------------------------------------------------- */
    .room-schedule {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      background: #fff;
      border-radius: 8px;
      padding: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .room-schedule table {
      border-collapse: collapse;
      min-width: 100%;
    }
    .room-schedule th,
    .room-schedule td {
      border: 1px solid #e0e0e0 !important;
      padding: .75rem;
      vertical-align: top;
    }
    /* Saat sütununu sabitle */
    .room-schedule th.time-column,
    .room-schedule td.time-column {
      position: sticky;
      left: 0;
      background: #f8f9fa;
      z-index: 3;
      white-space: nowrap;
    }
    /* veri hücresi rengi */
    .room-schedule td.time-column {
      background: #fff;
      z-index: 2;
    }

    /* --------------------------------------------------
       RANDEVU KARTLARI
       -------------------------------------------------- */
    .appointment {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 6px;
      margin-bottom: 6px;
      position: relative;
      cursor: move;
      transition: transform .2s, box-shadow .2s;
      white-space: normal;
    }
    .appointment:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .appointment .patient {
      font-weight: 500;
      margin-bottom: 2px;
    }
    .appointment .therapist {
      font-size: .85rem;
      color: #666;
    }
    .edit-button {
      position: absolute;
      top: 4px; right: 4px;
      background: #fff;
      border: 1px solid #1976d2;
      color: #1976d2;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: .75rem;
      opacity: 0;
      transition: opacity .2s;
    }
    .appointment:hover .edit-button {
      opacity: 1;
    }
    .badge-initial   { background:#0d6efd; color:#fff; padding:2px 4px; border-radius:3px; font-size:.7rem; }
    .badge-evaluation{ background:#fd7e14; color:#fff; padding:2px 4px; border-radius:3px; font-size:.7rem; }
    .badge-final     { background:#198754; color:#fff; padding:2px 4px; border-radius:3px; font-size:.7rem; }
    </style>
<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    include "partials/title-meta.php";
    ?>

    <?php include 'partials/head-css.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Kullanıcı Yönetimi";
                $title = "Günlük Oda Programı";
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
            <input
              type="date"
              id="schedule-date"
              class="form-control"
              value="<?= htmlspecialchars($current_date) ?>"
              onchange="window.location='?page=room_schedule&date='+this.value"
            >
            <button class="btn btn-outline-primary" onclick="changeDate(1)">
              Sonraki Gün <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <div class="controls-group">
  <a href="apps-calendar.php"
     class="btn btn-outline-primary<?php if(!isset($_GET['view']) || $_GET['view']==='dayGridMonth') echo ''; ?>">
    Ay
  </a>
  <a href="weekly_room_schedule.php"
     class="btn btn-outline-primary<?php if(isset($_GET['view']) && $_GET['view']==='timeGridWeek') echo ' '; ?>">
    Hafta
  </a>
  <a href="room_schedule.php"
     class="btn btn-outline-primary<?php if(isset($_GET['view']) && $_GET['view']==='timeGridDay') echo ' active'; ?>">
    Gün
  </a>

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
                    <?php foreach ($rooms as $room): ?>
                        <td class="room-cell" data-room-id="<?php echo $room['id']; ?>" data-time="<?php echo $time; ?>">
                        <!-- <button type="button" class="add-appointment-btn"
                                    onclick="handleAppointmentAdd('<?php echo $current_date . ' ' . $time; ?>', <?php echo $room['id']; ?>)">
                                <i class="fas fa-plus"></i>
                </button> //-->

                            <?php
                            if (isset($schedule[$room['id']]['appointments'][$time])) {
                                $apt = $schedule[$room['id']]['appointments'][$time];
                                $evaluationClass = '';
                                $evaluationBadge = '';

                                if (!empty($apt['evaluation_type'])) {
                                    $evaluationClass = 'evaluation-appointment';
                                    if ($apt['evaluation_type'] === 'initial') {
                                        $evaluationBadge = '<span class="badge badge-initial">İlk Değerlendirme</span>';
                                    } elseif ($apt['evaluation_type'] === 'progress') {
                                        $evaluationBadge = '<span class="badge badge-evaluation">' .
                                            $apt['evaluation_number'] . '. Değerlendirme</span>';
                                    } elseif ($apt['evaluation_type'] === 'final') {
                                        $evaluationBadge = '<span class="badge badge-final">Son Değerlendirme</span>';
                                    }
                                }
                            ?>
                                <div class="appointment <?php echo $evaluationClass; ?>"
                                     draggable="true"
                                     data-appointment-id="<?php echo $apt['id']; ?>"
                                     data-time="<?php echo $time; ?>">
                                    <button class="edit-button" onclick="handleAppointmentEdit('<?php echo $apt['id']; ?>', event)">
                                        Düzenle
                                    </button>
                                    <div class="patient">
                                        <?php echo htmlspecialchars($apt['danisan']); ?>
                                        <?php echo $evaluationBadge; ?>
                                    </div>
                                    <div class="therapist"><?php echo htmlspecialchars($apt['terapist']); ?></div>
                                    <div class="session-type"><?php echo htmlspecialchars($apt['seans_turu']); ?></div>
                                    <?php if (!empty($apt['evaluation_notes'])): ?>
                                        <div class="evaluation-notes"><?php echo htmlspecialchars($apt['evaluation_notes']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php } ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

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
 

      
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'partials/customizer.php' ?>

<?php include 'partials/footer-scripts.php' ?>

<!-- dropify File Upload js -->
<script src="assets/vendor/dropify/js/dropify.min.js"></script>

<!-- File Upload Demo js -->
<script src="assets/js/pages/form-fileupload.js"></script>
</body>

</html>






<script>
let appointmentModal = null;
let draggedAppointment = null;

document.addEventListener('DOMContentLoaded', function() {
    appointmentModal = new bootstrap.Modal(document.getElementById('randevuModal'));

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

function handleAppointmentAdd(datetime, roomId) {
    const form = document.getElementById('appointmentForm');
    form.reset();
    form.querySelector('input[name="ajax_action"]').value = 'randevu_ekle';
    form.querySelector('input[name="id"]').value = '';
    form.querySelector('input[name="room_id"]').value = roomId;
    form.querySelector('input[name="randevu_tarihi"]').value = datetime;

    appointmentModal.show();
}

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
            loadAppointmentDetails(appointment.satis_id, appointmentId);

            const form = document.getElementById('appointmentForm');
            form.querySelector('input[name="ajax_action"]').value = 'randevu_guncelle';
            form.querySelector('input[name="id"]').value = appointment.id;
            form.querySelector('input[name="danisan_id"]').value = appointment.danisan_id;
            
            // Add hidden satis_id field if it doesn't exist
            let satisIdInput = form.querySelector('input[name="satis_id"]');
            if (!satisIdInput) {
                satisIdInput = document.createElement('input');
                satisIdInput.type = 'hidden';
                satisIdInput.name = 'satis_id';
                form.appendChild(satisIdInput);
            }
            satisIdInput.value = appointment.satis_id;

            form.querySelector('select[name="personel_id"]').value = appointment.personel_id;
            form.querySelector('select[name="room_id"]').value = appointment.room_id;

            const [datePart, timePart] = appointment.randevu_tarihi.split(' ');
            form.querySelector('input[name="randevu_tarih"]').value = datePart;
            form.querySelector('select[name="randevu_saat"]').value = timePart.substring(0, 5);
            form.querySelector('textarea[name="notlar"]').value = appointment.notlar || '';

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
        if (!data.success) return;

        const totalSessions = parseInt(data.satis.seans_adet || 0) + parseInt(data.satis.hediye_seans || 0);
        document.getElementById('totalSessions').textContent = totalSessions;

        const usedSessions = (data.randevular || []).length;
        document.getElementById('remainingSessions').textContent = totalSessions - usedSessions;

        const appointmentsList = document.getElementById('appointmentsList');
        appointmentsList.innerHTML = '';
        if (data.randevular.length) {
            data.randevular.forEach(apt => {
                const row = document.createElement('tr');
                const aptDate = new Date(apt.randevu_tarihi);
                const isPast = aptDate < new Date();

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
                if (isPast) row.classList.add('past-appointment');
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
        if (data.odemeler.length) {
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
    } catch (error) {
        console.error('Error loading appointment details:', error);
    }
}

async function deleteAppointment(appointmentId) {
    if (!confirm('Bu randevuyu silmek istediğinizden emin misiniz?')) return;

    try {
        const response = await fetch('ajax/delete_appointment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
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

    // Get date and time separately and combine them
    const date = form.querySelector('input[name="randevu_tarih"]').value;
    const time = form.querySelector('select[name="randevu_saat"]').value;
    const randevuTarihi = `${date} ${time}`;
    formData.set('randevu_tarihi', randevuTarihi);

    // Preserve satis_id if it exists
    const satisIdInput = form.querySelector('input[name="satis_id"]');
    if (satisIdInput && satisIdInput.value) {
        formData.set('satis_id', satisIdInput.value);
    }

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


function changeDate(days) {
    const dateInput = document.getElementById('schedule-date');
    const currentDate = new Date(dateInput.value);
    currentDate.setDate(currentDate.getDate() + days);
    const newDate = currentDate.toISOString().split('T')[0];
    window.location.href = '?page=room_schedule&date=' + newDate;
}

// Make functions globally available
window.handleAppointmentAdd   = handleAppointmentAdd;
window.handleAppointmentEdit  = handleAppointmentEdit;
window.saveAppointment        = saveAppointment;
window.changeDate             = changeDate;
window.deleteAppointment      = deleteAppointment;
</script>
