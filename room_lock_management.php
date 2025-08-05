<?php
/**
 * ODA KİLİTLEME YÖNETİM SAYFASI
 * room_lock_management.php
 */

session_start();
require_once 'functions.php';

// AJAX işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'lock_time':
            $result = odaSaatKilitle(
                $_POST['room_id'],
                $_POST['tarih'],
                $_POST['saat'],
                $_POST['aciklama'] ?? '',
                $_POST['kilit_turu'] ?? 'manuel'
            );
            echo json_encode($result);
            exit;
            
        case 'unlock_time':
            $result = odaSaatKilidiniAc($_POST['room_id'], $_POST['tarih'], $_POST['saat']);
            echo json_encode($result);
            exit;
            
        case 'bulk_lock':
            $saatler = json_decode($_POST['saatler'], true);
            $result = topluOdaKilitle(
                $_POST['room_id'],
                $_POST['tarih'],
                $saatler,
                $_POST['aciklama'] ?? '',
                $_POST['kilit_turu'] ?? 'manuel'
            );
            echo json_encode($result);
            exit;
            
        case 'get_locks':
            $locks = getTumKilitliSaatler($_POST['tarih']);
            echo json_encode(['success' => true, 'data' => $locks]);
            exit;
    }
}

$rooms = getRooms();
$selected_date = $_GET['date'] ?? date('Y-m-d');
$kilitli_saatler = getTumKilitliSaatler($selected_date);

// Çalışma saatleri (08:00 - 20:00)
$calisma_saatleri = [];
for ($i = 8; $i <= 19; $i++) {
    $calisma_saatleri[] = sprintf('%02d:00', $i);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Oda Kilitleme Yönetimi";
    include "partials/title-meta.php";
    ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
    .time-slot {
        border: 1px solid #ddd;
        padding: 8px;
        margin: 2px;
        cursor: pointer;
        border-radius: 4px;
        background: #fff;
        transition: all 0.3s;
    }
    .time-slot.locked {
        background: #dc3545;
        color: white;
        cursor: not-allowed;
    }
    .time-slot.selected {
        background: #007bff;
        color: white;
    }
    .time-slot:hover:not(.locked) {
        background: #e9ecef;
    }
    .room-schedule {
        border: 1px solid #ddd;
        border-radius: 6px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .room-header {
        background: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    .time-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 5px;
        padding: 15px;
    }
    .lock-controls {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
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
                $title = "Oda Kilitleme Yönetimi";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                
                                <!-- Tarih Seçici -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="selected_date" class="form-label">Tarih Seçin:</label>
                                        <input type="date" id="selected_date" class="form-control" 
                                               value="<?php echo $selected_date; ?>" 
                                               onchange="window.location.href='room_lock_management.php?date='+this.value">>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Hızlı Tarih:</label>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="changeDate(0)">Bugün</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="changeDate(1)">Yarın</button>
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="changeDate(7)">1 Hafta</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kilitleme Kontrolleri -->
                                <div class="lock-controls">
                                    <h5>Toplu İşlemler</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select id="bulk_room" class="form-select">
                                                <option value="">Oda Seçin</option>
                                                <?php foreach ($rooms as $room): ?>
                                                    <option value="<?php echo $room['id']; ?>">
                                                        <?php echo htmlspecialchars($room['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" id="bulk_description" class="form-control" 
                                                   placeholder="Açıklama (opsiyonel)">
                                        </div>
                                        <div class="col-md-3">
                                            <select id="bulk_lock_type" class="form-select">
                                                <option value="manuel">Manuel</option>
                                                <option value="bakım">Bakım</option>
                                                <option value="otomatik">Otomatik</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-primary" onclick="bulkLockSelected()">
                                                Seçilenleri Kilitle
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Oda Programları -->
                                <?php foreach ($rooms as $room): ?>
                                    <div class="room-schedule">
                                        <div class="room-header">
                                            <?php echo htmlspecialchars($room['name']); ?>
                                            <span class="float-end">
                                                <small class="text-muted">
                                                    Kilitli: 
                                                    <span class="badge bg-danger">
                                                        <?php echo count($kilitli_saatler[$room['id']] ?? []); ?>
                                                    </span>
                                                </small>
                                            </span>
                                        </div>
                                        <div class="time-grid">
                                            <?php foreach ($calisma_saatleri as $saat): ?>
                                                <?php 
                                                $is_locked = false;
                                                $lock_info = null;
                                                if (isset($kilitli_saatler[$room['id']])) {
                                                    foreach ($kilitli_saatler[$room['id']] as $lock) {
                                                        if ($lock['saat'] === $saat.':00') {
                                                            $is_locked = true;
                                                            $lock_info = $lock;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <div class="time-slot <?php echo $is_locked ? 'locked' : ''; ?>" 
                                                     data-room="<?php echo $room['id']; ?>" 
                                                     data-time="<?php echo $saat; ?>"
                                                     onclick="toggleTimeSlot(this)"
                                                     title="<?php echo $is_locked ? 'Kilitli: '.($lock_info['aciklama'] ?? 'Açıklama yok') : 'Kilitsiz'; ?>">
                                                    <?php echo $saat; ?>
                                                    <?php if ($is_locked): ?>
                                                        <br><small><?php echo substr($lock_info['kilit_turu'], 0, 3); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer-scripts.php'; ?>

    <script>
    let selectedSlots = [];

    function changeDate(days) {
        const date = new Date('<?php echo $selected_date; ?>');
        date.setDate(date.getDate() + days);
        const newDate = date.toISOString().split('T')[0];
        window.location.href = '?page=room_lock_management&date=' + newDate;
    }

    function toggleTimeSlot(element) {
        const roomId = element.dataset.room;
        const time = element.dataset.time;
        const isLocked = element.classList.contains('locked');
        
        if (isLocked) {
            // Kilidi aç
            if (confirm('Bu saatin kilidini açmak istediğinizden emin misiniz?')) {
                unlockTimeSlot(roomId, time, element);
            }
        } else {
            // Seçili durumu değiştir
            element.classList.toggle('selected');
            const slotKey = roomId + '-' + time;
            
            if (element.classList.contains('selected')) {
                if (!selectedSlots.includes(slotKey)) {
                    selectedSlots.push(slotKey);
                }
            } else {
                selectedSlots = selectedSlots.filter(slot => slot !== slotKey);
            }
        }
    }

    function unlockTimeSlot(roomId, time, element) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=unlock_time&room_id=${roomId}&tarih=<?php echo $selected_date; ?>&saat=${time}:00`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('locked');
                element.title = 'Kilitsiz';
                element.innerHTML = time;
                showAlert('success', data.message);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Bir hata oluştu');
        });
    }

    function bulkLockSelected() {
        if (selectedSlots.length === 0) {
            showAlert('warning', 'Lütfen kilitleme için saat seçin');
            return;
        }

        const roomId = document.getElementById('bulk_room').value;
        const description = document.getElementById('bulk_description').value;
        const lockType = document.getElementById('bulk_lock_type').value;

        if (!roomId) {
            showAlert('warning', 'Lütfen oda seçin');
            return;
        }

        // Sadece seçilen odadaki saatleri filtrele
        const roomSlots = selectedSlots.filter(slot => slot.startsWith(roomId + '-'));
        
        if (roomSlots.length === 0) {
            showAlert('warning', 'Seçili odada kilitleme için saat seçin');
            return;
        }

        const times = roomSlots.map(slot => slot.split('-')[1] + ':00');

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=bulk_lock&room_id=${roomId}&tarih=<?php echo $selected_date; ?>&saatler=${JSON.stringify(times)}&aciklama=${encodeURIComponent(description)}&kilit_turu=${lockType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Bir hata oluştu');
        });
    }

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Varolan alertleri temizle
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Yeni alert ekle
        const container = document.querySelector('.card-body');
        container.insertAdjacentHTML('afterbegin', alertHtml);
    }
    </script>

</body>
</html>