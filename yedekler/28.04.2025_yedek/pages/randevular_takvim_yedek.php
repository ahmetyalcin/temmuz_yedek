<?php
require_once 'functions.php';

// JSON dönüş fonksiyonu
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    $raw = file_get_contents('php://input');
    $postData = json_decode($raw, true);

    // 💥 DEBUG
    file_put_contents(__DIR__ . '/debug.log', "Ham JSON: $raw\n\nVeri: " . print_r($postData, true), FILE_APPEND);

    if (!is_array($postData)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz JSON verisi']);
        exit;
    }

    // 🧠 Devam: ajax_action kontrolü
    if (!isset($postData['ajax_action'])) {
        echo json_encode(['success' => false, 'message' => 'ajax_action eksik']);
        exit;
    }

    // 🧪 Test cevabı
    echo json_encode(['success' => true, 'message' => 'Her şey çalışıyor']);
    exit;
}



// GET ile randevuları çek
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_randevular') {
    ob_clean(); // olası önceden gelen çıktıyı temizle
    $randevular = getRandevular();
    $events = [];

    foreach ($randevular as $randevu) {
        $baslangic = new DateTime($randevu['randevu_tarihi']);
        $bitis = clone $baslangic;
        $bitis->modify('+1 hour');

        $events[] = [
            'id' => $randevu['id'],
            'title' => $randevu['danisan_adi'] . ' - ' . $randevu['seans_turu'],
            'start' => $baslangic->format('Y-m-d\TH:i:s'),
            'end' => $bitis->format('Y-m-d\TH:i:s'),
            'backgroundColor' => getRandevuColor($randevu['durum']),
            'borderColor' => getRandevuColor($randevu['durum']),
            'extendedProps' => [
                'danisan_id' => $randevu['danisan_id'],
                'personel_id' => $randevu['personel_id'],
                'seans_turu_id' => $randevu['seans_turu_id'],
                'notlar' => $randevu['notlar'],
                'durum' => $randevu['durum']
            ]
        ];
    }

    json_response($events);
}

// POST işlemleri (randevu ekle/güncelle)
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    // JSON veya klasik POST yakalama
    $postData = json_decode(file_get_contents('php://input'), true);
    if (!$postData || !is_array($postData)) {
        $postData = $_POST;
    }

    // Logla (hata ayıklama için)
   // file_put_contents('debug.txt', "GELEN POST:\n" . print_r($postData, true) . "\n", FILE_APPEND);
 //  file_put_contents(__DIR__ . '/debug.log', "GELEN POST:\n" . print_r($postData, true) . "\n", FILE_APPEND);

 echo "<pre>";
 print_r($postData);
 exit;

    if (!isset($postData['ajax_action'])) {
        json_response(['success' => false, 'message' => 'ajax_action yok.']);
    }

    try {
        if ($postData['ajax_action'] === 'randevu_ekle') {
            $sonuc = randevuEkle(
                $postData['danisan_id'],
                $postData['personel_id'],
                $postData['seans_turu_id'],
                date('Y-m-d H:i:s', strtotime($postData['randevu_tarihi'])),
                $postData['notlar'] ?? null
            );

            json_response([
                'success' => $sonuc,
                'message' => $sonuc ? 'Randevu başarıyla eklendi.' : 'Randevu eklenirken bir hata oluştu.'
            ]);
        }

        if ($postData['ajax_action'] === 'randevu_guncelle') {
            $sonuc = randevuGuncelle(
                $postData['id'],
                $postData['danisan_id'],
                $postData['personel_id'],
                $postData['seans_turu_id'],
                date('Y-m-d H:i:s', strtotime($postData['randevu_tarihi'])),
                $postData['notlar'] ?? null
            );

            json_response([
                'success' => $sonuc,
                'message' => $sonuc ? 'Randevu başarıyla güncellendi.' : 'Randevu güncellenirken hata oluştu.'
            ]);
        }

        json_response(['success' => false, 'message' => 'Geçersiz işlem türü.']);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

// Renk fonksiyonu
function getRandevuColor($durum) {
    switch ($durum) {
        case 'beklemede': return '#fbbf24';
        case 'onaylandi': return '#34d399';
        case 'iptal_edildi': return '#f87171';
        case 'tamamlandi': return '#60a5fa';
        default: return '#9ca3af';
    }
}

// Sayfa için kalan işlemler (formlar, HTML vs.)
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';
$aktif_terapistler = getTerapistler(true);

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Randevular</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#randevuModal">
                <i class="bx bx-plus"></i> Yeni Randevu
            </button>
        </div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Randevu Modal -->
<div class="modal fade" id="randevuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="randevuForm">
                <input type="hidden" name="ajax_action" value="randevu_ekle">
                <input type="hidden" name="id" id="randevu_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Randevu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Danışan</label>
                        <select name="danisan_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($danisanlar as $danisan): ?>
                                <option value="<?php echo $danisan['id']; ?>">
                                    <?php echo $danisan['ad'] . ' ' . $danisan['soyad']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Terapist</label>
                        <select name="personel_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($aktif_terapistler as $terapist): ?>
                                <option value="<?php echo $terapist['id']; ?>">
                                    <?php echo $terapist['ad'] . ' ' . $terapist['soyad']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seans Türü</label>
                        <select name="seans_turu_id" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($seansTurleri as $seans): ?>
                                <option value="<?php echo $seans['id']; ?>">
                                    <?php echo $seans['ad'] . ' (' . $seans['sure'] . ' dk)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tarih ve Saat</label>
                        <input type="datetime-local" name="randevu_tarihi" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea name="notlar" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gerekli CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<style>
    #calendar {
        margin: 20px auto;
        padding: 0 10px;
    }
    .fc-event {
        cursor: pointer;
    }
    .fc-daygrid-day {
        cursor: pointer;
    }
    .fc-event-title {
        font-size: 0.85em;
        font-weight: 500;
        white-space: normal;
    }
</style>

<!-- Gerekli JavaScript -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialView: 'dayGridMonth',
        locale: 'tr',
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        events: '?page=randevular&ajax_action=get_randevular',
        select: function(arg) {
            var modal = new bootstrap.Modal(document.getElementById('randevuModal'));
            document.querySelector('input[name="randevu_tarihi"]').value = 
                arg.start.toISOString().slice(0, 16);
            
            // Form'u sıfırla ve yeni randevu modunu ayarla
            var form = document.getElementById('randevuForm');
            form.reset();
            form.querySelector('input[name="ajax_action"]').value = 'randevu_ekle';
            form.querySelector('input[name="id"]').value = '';
            form.querySelector('input[name="randevu_tarihi"]').value = arg.start.toISOString().slice(0, 16);
            
            modal.show();
        },
        eventClick: function(arg) {
            var event = arg.event;
            var modal = new bootstrap.Modal(document.getElementById('randevuModal'));
            var form = document.getElementById('randevuForm');
            
            // Form alanlarını doldur
            form.querySelector('input[name="ajax_action"]').value = 'randevu_guncelle';
            form.querySelector('input[name="id"]').value = event.id;
            form.querySelector('select[name="danisan_id"]').value = event.extendedProps.danisan_id;
            form.querySelector('select[name="personel_id"]').value = event.extendedProps.personel_id;
            form.querySelector('select[name="seans_turu_id"]').value = event.extendedProps.seans_turu_id;
            form.querySelector('input[name="randevu_tarihi"]').value = event.start.toISOString().slice(0, 16);
            form.querySelector('textarea[name="notlar"]').value = event.extendedProps.notlar || '';
            
            modal.show();
        },
        eventDrop: function(arg) {
            if (!confirm("Randevu tarihini değiştirmek istediğinizden emin misiniz?")) {
                arg.revert();
                return;
            }

            var event = arg.event;
            var data = {
                ajax_action: 'randevu_guncelle',
                id: event.id,
                danisan_id: event.extendedProps.danisan_id,
                personel_id: event.extendedProps.personel_id,
                seans_turu_id: event.extendedProps.seans_turu_id,
                randevu_tarihi: event.start.toISOString().slice(0, 16),
                notlar: event.extendedProps.notlar
            };

            fetch('?page=randevular', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    arg.revert();
                    alert('Randevu tarihi güncellenirken bir hata oluştu.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                arg.revert();
                alert('Bir hata oluştu.');
            });
        }
    });

    calendar.render();

    // Form submit işlemi
    document.getElementById('randevuForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log("form submit"); // ✅ Bu çalıştıysa fetch de hemen burada olmalı!

    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => data[key] = value);

    console.log("Gönderilecek veri:", data); // 👈 bu da eklensin

    fetch('?page=randevular', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log("Gelen cevap:", data); // 👈 buraya da log ekle
        if (data.success) {
            calendar.refetchEvents();
            bootstrap.Modal.getInstance(document.getElementById('randevuModal')).hide();
            this.reset();
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('HATA:', error); // 👈 Hataları görmek için bu önemli
    });
});



});
</script>