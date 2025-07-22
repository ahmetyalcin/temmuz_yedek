<?php
session_start();
require_once 'functions.php';
?>
<style>

#appointmentTabs .nav-link {
    line-height: 1.1;
    padding-top: 0.7rem;
    padding-bottom: 0.6rem;
    min-width: 120px;
}



</style>


<?php
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
                     'seans_turu' => $randevu['seans_turu'],
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


<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Randevular";
    include "partials/title-meta.php" ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <?php include 'partials/sidenav.php' ?>

        <?php include 'partials/topbar.php' ?>

        <div class="page-content">

            <div class="page-container">

                <?php
                $subtitle = "Randevular";
                $title = "Aylık Oda Programı";
                include "partials/page-title.php" ?>

                <div class="card">

                
                    <div class="card-body">

<div class="d-flex flex-wrap justify-content-center justify-content-md-start mb-3 gap-2">
                                <a href="apps-calendar.php"
                        class="btn btn-outline-primary<?php if(!isset($_GET['view']) || $_GET['view']==='dayGridMonth') echo ' '; ?>">
                        Ay
                    </a>
                    <a href="weekly_room_schedule.php"
                        class="btn btn-outline-primary<?php if(isset($_GET['view']) && $_GET['view']==='timeGridWeek') echo ' active'; ?>">
                        Hafta
                    </a>
                    <a href="room_schedule.php"
                        class="btn btn-outline-primary<?php if(isset($_GET['view']) && $_GET['view']==='timeGridDay') echo ' '; ?>">
                        Gün
                    </a>
</div>


                <div  id="calendar"></div>


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
    <li class="nav-item" id="fonksiyonel-not-tab-li" style="display:none;">
        <button class="nav-link" id="fonksiyonel-notlar-tab" data-bs-toggle="tab" data-bs-target="#fonksiyonel-notlar" type="button" role="tab">
            Fonksiyonel<br>Notlar
        </button>
    </li>
</ul>



                <div class="tab-content" id="appointmentTabContent">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <div class="session-info alert alert-info mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Kalan Seans: <span id="totalSessions">-</span></span>
                                <span>Toplam Seans: <span id="remainingSessions">-</span></span>
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

          <!-- Randevu Notlar Tab -->
<div class="tab-pane fade" id="randevu-notlari" role="tabpanel">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Ekleyen</th>
                    <th>Not</th>
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

           <!-- fonksiyonal Tab -->              
<!-- Modal içindeki Fonksiyonel Notlar Sekmesi -->
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
      <tbody id="fonksiyonelNotesList">
        <!-- Satırlar ajax ile dolacak -->
      </tbody>
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
                        <!--end row-->
                    </div>
                </div>

                <!-- Add New Event MODAL -->
                <div class="modal fade" id="event-modal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form class="needs-validation" name="event-form" id="forms-event" novalidate>
                                <div class="modal-header p-3 border-bottom-0">
                                    <h5 class="modal-title" id="modal-title">
                                        Event
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body px-3 pb-3 pt-0">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label form-label">Event
                                                    Name</label>
                                                <input class="form-control" placeholder="Insert Event Name" type="text" name="title" id="event-title" required />
                                                <div class="invalid-feedback">Please provide a valid event name</div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="control-label form-label">Category</label>
                                                <select class="form-select" name="category" id="event-category" required>
                                                    <option value="bg-primary">Blue</option>
                                                    <option value="bg-secondary">Gray Dark</option>
                                                    <option value="bg-success">Green</option>
                                                    <option value="bg-info">Cyan</option>
                                                    <option value="bg-warning">Yellow</option>
                                                    <option value="bg-danger">Red</option>
                                                    <option value="bg-dark">Dark</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a valid event category</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <button type="button" class="btn btn-danger" id="btn-delete-event">
                                            Delete
                                        </button>

                                        <button type="button" class="btn btn-light ms-auto" data-bs-dismiss="modal">
                                            Close
                                        </button>

                                        <button type="submit" class="btn btn-primary" id="btn-save-event">
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- end modal-content-->
                    </div>
                    <!-- end modal dialog-->
                </div>
                <!-- end modal-->


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



            </div> <!-- container -->

            <?php include 'partials/footer.php' ?>

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->
     

    <?php include 'partials/customizer.php' ?>

    <?php include 'partials/footer-scripts.php' ?>

    <!-- Fullcalendar js -->
    <script src="assets/vendor/fullcalendar/index.global.min.js"></script>

    <!-- Calendar App Demo js -->
    <script src="assets/js/pages/apps-calendar.js"></script>




    

<script>


function saveFonksiyonelNot(btn, olcumNo) {
    var textarea = $(btn).closest('tr').find('textarea');
    var icerik = textarea.val();

    $.post('ajax/save_fonksiyonel_seans_notu.php', {
        danisan_id: currentDanisanId,
        olcum_no: olcumNo,  // Ölçüm numarası
        icerik: icerik
    }, function(res){
        if(res.success){
            $(btn).text('Kaydedildi!').removeClass('btn-primary').addClass('btn-success');
            setTimeout(function(){
                $(btn).text('Kaydet').removeClass('btn-success').addClass('btn-primary');
            }, 1200);
            loadFonksiyonelNotes(currentDanisanId);
        } else {
            alert('Hata: ' + (res.message || 'Kaydedilemedi'));
        }
    }, 'json');
}








document.getElementById('fonksiyonel-notlar-tab').addEventListener('shown.bs.tab', function () {
    loadFonksiyonelNotes(currentDanisanId);
});

function loadFonksiyonelNotes(danisan_id){
$.get('ajax/get_fonksiyonel_seans_notlari.php', { danisan_id }, function(res){
    if(res.success) {
        $('#fonksiyonelNotesList').html(res.html);
    } else {
        $('#fonksiyonelNotesList').html('<tr><td colspan="3">Not bulunamadı</td></tr>');
    }
}, 'json')
.fail(function(xhr, status, error){
    alert('AJAX Hatası: ' + error + '\n\n' + (xhr.responseText || ''));
    $('#fonksiyonelNotesList').html('<tr><td colspan="3">Bir hata oluştu!</td></tr>');
});
}
</script>


<script>
let currentDanisanId = null;
document.getElementById('randevu-not-tab').addEventListener('shown.bs.tab', function () {
    loadRandevuNotes(currentDanisanId);
});

function loadRandevuNotes(danisan_id) {
    fetch(`ajax/get-danisan-randevu-notlari.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(res => {
        const notesList = document.getElementById('randevuNotesList');
        notesList.innerHTML = '';
        if (res.success === false) {
            notesList.innerHTML = `<tr><td colspan="3" class="text-danger">Randevu notları yüklenemedi</td></tr>`;
        } else if (Array.isArray(res.data) && res.data.length) {
            res.data.forEach(function(n) {
                const ekleyen = ((n.personel_ad || '') + ' ' + (n.personel_soyad || '')).trim() || 'Sistem';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${new Date(n.not_tarihi).toLocaleDateString('tr-TR')} ${new Date(n.not_tarihi).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</td>
                    <td>${ekleyen}</td>
                    <td>${n.icerik}</td>
                `;
                notesList.appendChild(row);
            });
        } else {
            notesList.innerHTML = `<tr><td colspan="3" class="text-center">Not bulunamadı</td></tr>`;
        }
    });
}



document.getElementById('notes-tab').addEventListener('shown.bs.tab', function () {
    // Modal açıldığında veya detay yüklenirken danisan_id değişkenini set etmelisin
    loadDanisanNotes(currentDanisanId);
});


function loadDanisanNotes(danisan_id) {
    fetch(`ajax/get-danisan-notlar.php?danisan_id=${danisan_id}`)
    .then(response => response.json())
    .then(res => {
        const notesList = document.getElementById('notesList');
        notesList.innerHTML = '';
        if (res.success === false) {
            notesList.innerHTML = `<tr><td colspan="3" class="text-danger">Notlar yüklenemedi</td></tr>`;
        } else if (Array.isArray(res.data) && res.data.length) {
            res.data.forEach(function(n) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${new Date(n.not_tarihi).toLocaleDateString('tr-TR')} ${new Date(n.not_tarihi).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}</td>
                    <td>${(n.personel_ad || '') + ' ' + (n.personel_soyad || '')}</td>
                    <td>${n.icerik}</td>
                `;
                notesList.appendChild(row);
            });
        } else {
            notesList.innerHTML = `<tr><td colspan="3" class="text-center">Not bulunamadı</td></tr>`;
        }
    });
}





let calendarInstance;



document.addEventListener('DOMContentLoaded', function() {





    var calendarEl = document.getElementById('calendar');
    calendarInstance = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
        },
        initialView: lastView || 'dayGridMonth',
        initialDate: lastDate || undefined,
        locale: 'tr',
        editable: true,
        selectable: true,
        displayEventTime: false,
        height: 'parent',
        contentHeight: 'auto',
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
            url: window.location.pathname + '?ajax_action=get_randevular',
            method: 'GET',
            failure: function(error) {
                console.error('Randevular yüklenirken hata 111:', error);
                alert('Randevular yüklenirken bir hata oluştu: 222 ' + JSON.stringify(error));
            }
        },
  
        
    eventDidMount: function(info) {
    const { danisan, personel, room_name, kalan_seans, evaluation_type, evaluation_number, evaluation_note,seans_turu,personel_id } = info.event.extendedProps;
    
    var myPersonelId = <?= (int)$_SESSION['personel_id'] ?>;



    let evaluationBadge = '';
    let bgColor = '';
    let textColor = '#333'; // Default text color


if (personel_id && String(personel_id) === String(myPersonelId)) {
        bgColor = '#ffe0ef';
        textColor = '#c2185b';
    } else {
        bgColor = '#50c9ad';
        textColor = '#fff';
    }



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
                ${info.event.title} 
                ${danisan}
            </div>
            <div class="appointment-room">
                <div class="room-name">${room_name}</div>
                <div class="seans-turu">${seans_turu}</div>
                ${evaluationBadge}
            </div>
        </div>
    `;


    info.el.innerHTML = appointmentContent;
    info.el.style.backgroundColor = bgColor;
    info.el.style.color = textColor;
    info.el.classList.add('appointment-custom-style');
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
    
         
            currentDanisanId = event.extendedProps.danisan_id;
            var fonksiyonelSeansTurleri = [55,56,57,58];
            var currentDanisanSeansTuruId = null;

    currentDanisanSeansTuruId = Number(event.extendedProps.seans_turu_id);


    if (fonksiyonelSeansTurleri.includes(currentDanisanSeansTuruId)) {
        document.getElementById('fonksiyonel-not-tab-li').style.display = '';
    } else {
        document.getElementById('fonksiyonel-not-tab-li').style.display = 'none';
    }


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
    

</body>

</html>