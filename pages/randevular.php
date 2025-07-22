<?php
require_once 'functions.php';

// Replace the danisanlar variable initialization with:
$danisanlar = getDanisanlarWithRemainingAppointments();

// AJAX ile randevuları getir
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_randevular') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    try {
        $randevular = getRandevular();
        $events = [];

        foreach ($randevular as $randevu) {
            $baslangic = new DateTime($randevu['randevu_tarihi']);
            $bitis = clone $baslangic;
            $bitis->modify('+1 hour');

            $saatBilgisi = $baslangic->format('H:i');
            
            $durumMetni = match($randevu['durum']) {
                'beklemede' => '[Beklemede]',
                'onaylandi' => '[Onaylandı]',
                'iptal_edildi' => '[İptal]',
                'tamamlandi' => '[Tamamlandı]',
                default => ''
            };

            $events[] = [
                'id' => $randevu['id'],
                'title' => $saatBilgisi,
                'start' => $baslangic->format('Y-m-d\TH:i:s'),
                'end' => $bitis->format('Y-m-d\TH:i:s'),
                'textColor' => '#333333',
                'allDay' => false,
                'extendedProps' => [
                    'danisan_id' => $randevu['danisan_id'],
                    'personel_id' => $randevu['personel_id'],
                    'seans_turu_id' => $randevu['seans_turu_id'],
                    'room_id' => $randevu['room_id'],
                    'notlar' => $randevu['notlar'] ?? '',
                    'durum' => $randevu['durum'],
                    'satis_id' => $randevu['satis_id'],
                    'originalDateTime' => $baslangic->format('Y-m-d\TH:i:s'),
                    'danisan' => $randevu['danisan_adi'],
                    'personel' => $randevu['personel_adi'],
                    'seans' => $randevu['seans_turu'],
                    'durum' => $durumMetni,
                    'room_name' => $randevu['room_name'],
                    'is_gift' => $randevu['is_gift'] ?? false,
                    'evaluation_type' => $randevu['evaluation_type'] ?? null,
                    'evaluation_number' => $randevu['evaluation_number'] ?? null,
                    'evaluation_notes' => $randevu['evaluation_notes'] ?? '',
                    'kalan_seans' => $randevu['kalan_seans'] ?? 0
                ]
            ];
        }

        echo json_encode($events, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        if (isset($_POST['current_view'])) {
            $_SESSION['last_calendar_view'] = $_POST['current_view'];
        }
        if (isset($_POST['current_date'])) {
            $_SESSION['last_calendar_date'] = $_POST['current_date'];
        }

        if ($_POST['ajax_action'] === 'randevu_ekle') {
            if (empty($_POST['danisan_id']) || empty($_POST['personel_id']) || 
                empty($_POST['seans_turu_id']) || empty($_POST['randevu_tarihi'])) {
                throw new Exception('Tüm zorunlu alanları doldurunuz.');
            }

            $sonuc = randevuEkle(
                $_POST['danisan_id'],
                $_POST['personel_id'],
                $_POST['seans_turu_id'],
                $_POST['randevu_tarihi'],
                isset($_POST['notlar']) ? $_POST['notlar'] : ''
            );
            
            if ($sonuc) {
                header('Location: ?page=randevular&success=1&message=' . urlencode('Randevu başarıyla eklendi.'));
                exit;
            } else {
                throw new Exception('Randevu eklenirken bir hata oluştu.');
            }
        }
        
        else if ($_POST['ajax_action'] === 'randevu_guncelle') {
            if (empty($_POST['id']) || empty($_POST['danisan_id']) || empty($_POST['personel_id']) || 
             empty($_POST['randevu_tarihi'])) {
                throw new Exception('Tüm zorunlu alanları doldurunuz. 111');
            }

            $sonuc = randevuGuncelle(
                $_POST['id'],
                $_POST['danisan_id'],
                $_POST['personel_id'],
                $_POST['seans_turu_id'],
                $_POST['randevu_tarihi'],
                isset($_POST['notlar']) ? $_POST['notlar'] : ''
            );
            
            if ($sonuc) {
                header('Location: ?page=randevular&success=1&message=' . urlencode('Randevu başarıyla güncellendi.'));
                exit;
            } else {
                throw new Exception('Randevu güncellenirken bir hata oluştu.');
            }
        }
    } catch (Exception $e) {
        header('Location: ?page=randevular&error=1&message=' . urlencode($e->getMessage()));
        exit;
    }
}

$aktif_terapistler = getTerapistler(true);
$seansTurleri = getSeansTurleri();
$rooms = getRooms();

$lastView = isset($_SESSION['last_calendar_view']) ? $_SESSION['last_calendar_view'] : 'dayGridMonth';
$lastDate = isset($_SESSION['last_calendar_date']) ? $_SESSION['last_calendar_date'] : '';
echo "<script>
    var lastView = '" . htmlspecialchars($lastView, ENT_QUOTES) . "';
    var lastDate = '" . htmlspecialchars($lastDate, ENT_QUOTES) . "';
</script>";

unset($_SESSION['last_calendar_view']);
unset($_SESSION['last_calendar_date']);
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_GET['message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_GET['message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="container-fluid">
    <div class="navigation">
        <h2>Aylık Oda Programı</h2>
        <div class="d-flex gap-2 card-header justify-content-between align-items-center">
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

    <div class="card">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
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
                                    <?php foreach ($aktif_terapistler as $terapist): ?>
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
                            <div class="evaluation-notes-section" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Değerlendirme Notları</label>
                                <textarea name="evaluation_notes" class="form-control" rows="3"></textarea>
                            </div>
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


<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<style>

.badge-initial {
    background-color: #cfe2ff;
    color: #0a58ca;
}

.badge-progress {
    background-color: #fff3cd;
    color: #997404;
}

.badge-final {
    background-color: #d1e7dd;
    color: #146c43;
}

.badge-normal {
    background-color: #e9ecef;
    color: #495057;
}

.appointment-type-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
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




.fc-event {
    border: none !important;
    border-radius: 4px !important;
    margin: 2px !important;
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

.bg-primary {
    background-color: #0d6efd !important;
    color: white !important;
}

.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.bg-success {
    background-color: #198754 !important;
    color: white !important;
}




.past-appointment {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
}

.appointment-type-badge {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.badge-normal {
    background-color: #e3f2fd;
    color: #1976d2;
}

.badge-gift {
    background-color: #fff3cd;
    color: #856404;
}

.badge-initial {
    background-color: #d1e7dd;
    color: #0f5132;
}

.badge-final {
    background-color: #f8d7da;
    color: #842029;
}


.fc-event.gift-session {
    background-color: #FFB6C1 !important;
}

.fc-event.initial-evaluation {
    background-color: #FFE4B5 !important;
}

.fc-event.final-evaluation {
    background-color: #98FB98 !important;
}

.evaluation-notes {
    margin-top: 8px;
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 4px;
    font-size: 12px;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
    margin-left: 6px;
}



.fc-event-title-container {
    padding: 4px !important;
}

.fc-event.fc-daygrid-event {
    white-space: normal !important;
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

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 20px 0;
}

#calendar {
    margin: 20px auto;
    padding: 0 10px;
}

.fc-event {
    cursor: pointer;
    background-color: #f0f7ff !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
    border-radius: 6px !important;
    padding: 4px !important;
    margin-bottom: 4px !important;
    transition: all 0.2s ease !important;
}

.fc-event:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.fc-daygrid-day {
    cursor: pointer;
}

.fc-event-title {
    font-size: 0.85em !important;
    font-weight: 500 !important;
    white-space: normal !important;
    color: #333 !important;
}

.fc-toolbar-chunk:last-child {
    display: none !important;
}

.fc-header-toolbar {
    margin-bottom: 1.5em !important;
    padding: 0.5rem !important;
}

.fc-day-today {
    background-color: #f8f9fa !important;
}

.fc-daygrid-day-number {
    font-weight: 500;
    color: #333;
}

.fc-button-primary {
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
    color: #6c757d !important;
}

.fc-button-primary:hover {
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    color: #495057 !important;
}

.fc-button-primary:disabled {
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    color: #adb5bd !important;
}

.fc-button-active {
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    color: #495057 !important;
}

.fc-daygrid-event-dot {
    display: none !important;
}

.appointment-card {
    padding: 10px;
    background: #ffffff;
    border-left: 4px solid #3b82f6;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.appointment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.appointment-time {
    color: #3b82f6;
    font-weight: 600;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.appointment-room {
    background-color: #f0f7ff;
    color: #1e40af;
    font-weight: 600;
    font-size: 10px;
    padding: 2px 4px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
    border: 1px solid #bfdbfe;
}

.appointment-details {
    border-top: 1px solid #e5e7eb;
    padding-top: 8px;
}

.appointment-patient {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
    font-size: 14px;
}

.appointment-therapist {
    color: #4b5563;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 4px;
}

.appointment-session {
    color: #059669;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 500;
}

.fc-event {
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
}

.fc-event:hover {
    transform: translateY(-2px);
    border-color: #3b82f6 !important;
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.15) !important;
}
</style>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  //  const danisanSelect = document.querySelector('select[name="danisan_id"]');
  //  const seansTuruSelect = document.querySelector('select[name="seans_turu_id"]');
  //  const evaluationNotesSection = document.querySelector('.evaluation-notes-section');


   const danisanField = document.querySelector('select[name="danisan_id"]');
    if (danisanField) {
        danisanField.closest('.mb-3').style.display = 'none';
    }

    // Hide seans türü selection initially
 //   seansTuruSelect.closest('.mb-3').style.display = 'none';

    danisanSelect.addEventListener('change', async function() {
        const danisanId = this.value;
        if (!danisanId) return;

        try {
            const response = await fetch(`ajax/get_satis_bilgileri.php?danisan_id=${danisanId}`);
            const data = await response.json();
            
            if (data.success) {
                // Auto-select seans türü
                seansTuruSelect.value = data.seans_turu_id;
                
                // Show evaluation notes section only for initial/final evaluations
                const isInitialEvaluation = data.kullanilan_seans === 0;
                const isFinalEvaluation = data.kullanilan_seans === (data.seans_adet + data.hediye_seans - 1);
                
                evaluationNotesSection.style.display = 
                    (isInitialEvaluation || isFinalEvaluation) ? 'block' : 'none';
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});

let calendarInstance;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    calendarInstance = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialView: lastView || 'dayGridMonth',
        initialDate: lastDate || undefined,
        locale: 'tr',
        editable: true,
        selectable: true,
        displayEventTime: false,
        selectMirror: true,
        dayMaxEvents: false,
        views: {
            dayGrid: {
                displayEventTime: false
            },
            timeGrid: {
                displayEventTime: false
            }
        },
        events: {
            url: window.location.pathname + '?page=randevular&ajax_action=get_randevular',
            method: 'GET',
            failure: function(error) {
                console.error('Randevular yüklenirken hata:', error);
                alert('Randevular yüklenirken bir hata oluştu: ' + JSON.stringify(error));
            }
        },
  
        
       eventDidMount: function(info) {
    const { danisan, personel, room_name, kalan_seans, evaluation_type, evaluation_number, evaluation_notes } = info.event.extendedProps;
    
    let evaluationBadge = '';
    let bgColor = '';
    let textColor = '#333'; // Default text color

    if (evaluation_type === 'initial') {
        evaluationBadge = '<span class="badge bg-primary">İlk Değerlendirme</span>';
        bgColor = '#cfe2ff';
        textColor = '#0a58ca';
    } else if (evaluation_type === 'progress') {
        evaluationBadge = `<span class="badge bg-warning">${evaluation_number}. Değerlendirme</span>`;
        bgColor = '#fff3cd';
        textColor = '#997404';
    } else if (evaluation_type === 'final') {
        evaluationBadge = '<span class="badge bg-success">Son Değerlendirme</span>';
        bgColor = '#d1e7dd';
        textColor = '#146c43';
    }

    let appointmentContent = `
        <div class="fc-event-content-wrapper">
            <div class="fc-event-time">${info.event.title}</div>
               <div class="fc-event-title">
                ${danisan}
               
            </div>
            <div class="appointment-room">
            ${room_name}
            ${evaluationBadge}
            </div>
         
        </div>
    `;

    info.el.innerHTML = appointmentContent;

    if (evaluation_type) {
        info.el.style.backgroundColor = bgColor;
        info.el.style.color = textColor; // Apply text color
        info.el.classList.add('evaluation-appointment');
    }
},

      eventDrop: async function(info) {
            var event = info.event;
            var originalDateTime = new Date(event.extendedProps.originalDateTime);
            var newDate = new Date(event.start);
            
            if (calendarInstance.view.type === 'dayGridMonth') {
                newDate = new Date(
                    newDate.getFullYear(),
                    newDate.getMonth(),
                    newDate.getDate(),
                    originalDateTime.getHours(),
                    originalDateTime.getMinutes()
                );
            }

            const hasConflict = await checkConflicts(
                event.extendedProps.room_id,
                formatDateForInputLocal(newDate),
                event.id
            );

            if (hasConflict) {
                info.revert();
                alert('Bu oda ve saatte başka bir randevu bulunmaktadır!');
                return;
            }
            
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=randevular';

            var formData = {
                'ajax_action': 'randevu_guncelle',
                'id': event.id,
                'danisan_id': event.extendedProps.danisan_id,
                'personel_id': event.extendedProps.personel_id,
                'seans_turu_id': event.extendedProps.seans_turu_id,
                'room_id': event.extendedProps.room_id,
                'randevu_tarihi': formatDateForInputLocal(newDate),
                'notlar': event.extendedProps.notlar || '',
                'current_view': calendarInstance.view.type,
                'current_date': calendarInstance.getDate().toISOString()
            };

            Object.keys(formData).forEach(key => {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = formData[key];
                form.appendChild(input);
            });

            if (confirm('Randevu tarihi güncellenecek. Onaylıyor musunuz?')) {
                document.body.appendChild(form);
                form.submit();
            } else {
                info.revert();
            }
        },
        
        select: function(arg) {
            var modal = new bootstrap.Modal(document.getElementById('randevuModal'));
            var form = document.querySelector('#randevuModal form');
            
            var selectedDate = new Date(arg.start);
            
            form.reset();
            form.querySelector('input[name="ajax_action"]').value = 'randevu_ekle';
            form.querySelector('input[name="id"]').value = '';
            form.querySelector('input[name="randevu_tarih"]').value = selectedDate.toISOString().split('T')[0];
            form.querySelector('select[name="randevu_saat"]').value = '09:00';
            form.querySelector('input[name="current_view"]').value = calendarInstance.view.type;
            form.querySelector('input[name="current_date"]').value = calendarInstance.getDate().toISOString();
            
            modal.show();
        },

        
        
        eventClick: function(arg) {
    var event = arg.event;
    var modal = new bootstrap.Modal(document.getElementById('randevuModal'));
    var form = document.getElementById('appointmentForm');
    
    // Load appointment details
    loadAppointmentDetails(event.extendedProps.satis_id, event.id);
    
    var eventDate = new Date(event.start);
    
    // Set form values
    form.querySelector('input[name="ajax_action"]').value = 'randevu_guncelle';
    form.querySelector('input[name="id"]').value = event.id;
    form.querySelector('input[name="danisan_id"]').value = event.extendedProps.danisan_id;
    form.querySelector('select[name="personel_id"]').value = event.extendedProps.personel_id;
    form.querySelector('select[name="room_id"]').value = event.extendedProps.room_id;
    form.querySelector('input[name="randevu_tarih"]').value = eventDate.toISOString().split('T')[0];
    form.querySelector('select[name="randevu_saat"]').value = eventDate.getHours().toString().padStart(2, '0') + ':00';
    form.querySelector('textarea[name="notlar"]').value = event.extendedProps.notlar || '';
    
    // Show evaluation notes section if it's an evaluation appointment
    const evaluationSection = form.querySelector('.evaluation-notes-section');
    if (event.extendedProps.evaluation_type) {
        evaluationSection.style.display = 'block';
        form.querySelector('textarea[name="evaluation_notes"]').value = 
            event.extendedProps.evaluation_notes || '';
    } else {
        evaluationSection.style.display = 'none';
    }
    
    modal.show();
}
    });

    calendarInstance.render();
});

function formatDateForInputLocal(date) {
    const pad = n => n.toString().padStart(2, '0');
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());
    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

async function loadAppointmentDetails(satis_id, appointmentId = null) {
    try {
        const response = await fetch(`ajax/get_randevu_detay.php?id=${satis_id}`);
        const data = await response.json();
        
        if (data.success) {
            // Calculate total sessions correctly
            const totalSessions = parseInt(data.satis.seans_adet || 0) + parseInt(data.satis.hediye_seans || 0);
            document.getElementById('totalSessions').textContent = totalSessions;

            // Calculate remaining sessions
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
                    
                    // Updated logic for session types
                    let typeLabel = 'Normal Seans';
                    let typeBadgeClass = 'badge-normal';
                    



                    
                    if (apt.evaluation_type === 'initial') {
                        typeLabel = `Değerlendirme`;
                        typeBadgeClass = 'badge-initial';
                    } else if (apt.evaluation_type === 'progress') {
                        typeLabel = `Değerlendirme`;
                        typeBadgeClass = 'badge-initial';
                    } else if (apt.evaluation_type === 'final') {
                        typeLabel = `Değerlendirme`;
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


async function checkConflicts(roomId, datetime, appointmentId = null) {
    try {
        const response = await fetch('ajax/check_conflicts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_id: roomId,
                datetime: datetime,
                appointment_id: appointmentId
            })
        });

        const data = await response.json();
        return data.hasConflict;
    } catch (error) {
        console.error('Çakışma kontrolü hatası:', error);
        return true;
    }
}
/*
async function saveAppointment() {
    var form = document.getElementById('appointmentForm');
    var formData = new FormData(form);

    var tarih = form.querySelector('input[name="randevu_tarih"]').value;
    var saat = form.querySelector('select[name="randevu_saat"]').value;
    var roomId = form.querySelector('select[name="room_id"]').value;
    var datetime = tarih + 'T' + saat;
    var appointmentId = form.querySelector('input[name="id"]').value;
    var danisanId = form.querySelector('select[name="danisan_id"]').value;

    // Get satis_id for the selected danisan
    try {
        const response = await fetch(`ajax/get_satis_bilgileri.php?danisan_id=${danisanId}`);
        const data = await response.json();
        
        if (data.success) {
            formData.append('satis_id', data.satis_id);
        } else {
            console.warn('No active sale found for danisan:', data.message);
        }
    } catch (error) {
        console.error('Error fetching satis_id:', error);
    }

    const hasConflict = await checkConflicts(roomId, datetime, appointmentId);
    
    if (hasConflict) {
        alert('Bu oda ve saatte başka bir randevu bulunmaktadır!');
        return;
    }

    formData.append('randevu_tarihi', datetime);

    try {
        const response = await fetch('ajax/save_appointment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('randevuModal'));
            modal.hide();
            window.location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    }
}
*/

async function saveAppointment() {
    var form = document.getElementById('appointmentForm');
    var formData = new FormData(form);

    var tarih = form.querySelector('input[name="randevu_tarih"]').value;
    var saat = form.querySelector('select[name="randevu_saat"]').value;
    var roomId = form.querySelector('select[name="room_id"]').value;
    var datetime = tarih + 'T' + saat;
    var appointmentId = form.querySelector('input[name="id"]').value;
    var danisanId = form.querySelector('input[name="danisan_id"]').value;

    // Get evaluation notes if section is visible
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
        } else {
            console.warn('No active sale found for danisan:', data.message);
        }
    } catch (error) {
        console.error('Error fetching satis_id:', error);
    }

    const hasConflict = await checkConflicts(roomId, datetime, appointmentId);
    
    if (hasConflict) {
        alert('Bu oda ve saatte başka bir randevu bulunmaktadır!');
        return;
    }

    formData.append('randevu_tarihi', datetime);

    try {
        const response = await fetch('ajax/save_appointment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('randevuModal'));
            modal.hide();
            window.location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    }
}



</script>
