<?php
session_start();
require_once 'functions.php';

$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

$satislar = getSatislar();
$terapistler = getTerapistler(true); // Get active therapists
$rooms = getRooms(); // Get rooms
?>





<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Satışlar Listele";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Finans";
                $title = "Satışlar";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Satışlar</h5>
            <a href="satis_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Satış
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="satislar_table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Danışan</th>
                            <th>Paket</th>
                            <th>Satışı Yapan</th>
                            <th>Toplam Tutar</th>
                            <th>Ödenen</th>
                            <th>Kalan</th>
                            <th>Son Ödeme</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($satislar as $satis): 
                            $kalan = $satis['toplam_tutar'] - $satis['toplam_odenen'];
                            $durum_class = $kalan <= 0 ? 'success' : ($satis['vade_tarihi'] < date('Y-m-d') ? 'danger' : 'warning');
                        ?>
                        <tr>
                            <td><?php echo formatDate($satis['olusturma_tarihi']); ?></td>
                            <td><?php echo htmlspecialchars($satis['danisan_adi']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($satis['paket_adi']); ?>
                                <?php if ($satis['hediye_seans'] > 0): ?>
                                    <span class="badge bg-success">+<?php echo $satis['hediye_seans']; ?> Hediye</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($satis['personel_adi']); ?>
                            </td>


                            <td><?php echo formatPrice($satis['toplam_tutar']); ?></td>
                            <td><?php echo formatPrice($satis['toplam_odenen']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $durum_class; ?>">
                                    <?php echo formatPrice($kalan); ?>
                                </span>
                            </td>
                            <td><?php echo $satis['son_odeme_tarihi'] ? formatDate($satis['son_odeme_tarihi']) : '-'; ?></td>
                            <td>
                                <?php if ($kalan <= 0): ?>
                                    <span class="badge bg-success">Ödendi</span>
                                <?php elseif ($satis['vade_tarihi'] < date('Y-m-d')): ?>
                                    <span class="badge bg-danger">Gecikmiş</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Bekliyor</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="showDetails('<?php echo $satis['id']; ?>')">
                                        <i class="fas fa-info-circle"></i>Ödeme
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showScheduleModal('<?php echo $satis['id']; ?>', <?php echo $satis['seans_adet'] + $satis['hediye_seans']; ?>)">
                                        <i class="fas fa-calendar"></i> Randevu Planla
                                    </button>


                                        <?php foreach ($satislar as $satis):
                                            // Her satış için aktif randevu adedini sorgula
                                            $stmt = $pdo->prepare(
                                                "SELECT COUNT(*) 
                                                FROM randevular 
                                                WHERE satis_id = :satis_id 
                                                    AND aktif = 1"
                                            );
                                            $stmt->execute(['satis_id' => $satis['id']]);
                                            $randevuSayisi = (int)$stmt->fetchColumn();

                                            $kalan = $satis['toplam_tutar'] - $satis['toplam_odenen'];
                                            $durum_class = $kalan <= 0 
                                                ? 'success' 
                                                : ($satis['vade_tarihi'] < date('Y-m-d') ? 'danger' : 'warning');
                                        ?>

                                        <?php if ($randevuSayisi === 0): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="deleteSale('<?= $satis['id'] ?>')">
                                        <i class="fas fa-trash-alt"></i> Sil
                                    </button>
                                <?php endif; ?>

                                </div>
                            </td>
                            <?php endforeach ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="odemeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ödeme İşlemi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="odeme_yukleniyor" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
                <div id="odeme_hata" class="alert alert-danger d-none"></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vade Tarihi</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Ödeme Tarihi</th>
                                <th>Ödeme Tipi</th>
                                <th>Ödendi</th>
                            </tr>
                        </thead>
                        <tbody id="taksit_listesi"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Satış Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="satis_detay"></div>
                <div id="odeme_gecmisi"></div>
                <div id="taksit_detay"></div>
                <div id="randevu_listesi"></div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Randevu Planla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Pembe renkli randevular daha sonra terapist ve oda ataması yapılacak randevuları gösterir.
                </div>
                
                <div class="mb-3">
                    <div class="alert alert-secondary">
                        <span id="remaining_slots"></span>
                    </div>
                </div>

                <div class="row g-3 mb-4">

 <div class="col-md-3">
        <label class="form-label">Planlama Tipi</label>
        <select id="planlama_tipi" class="form-select">
            <option value="manual">Manuel</option>
            <option value="haftalik">Haftalık</option>
            <option value="aylik">Aylık</option>
        </select>
    </div>

    <div class="col-md-3 d-none" id="gun_secimi_div">
        <label class="form-label">Gün Seçimi (Opsiyonel)</label>
        <select multiple class="form-select" id="gun_secimi">
            <option value="1">Pazartesi</option>
            <option value="2">Salı</option>
            <option value="3">Çarşamba</option>
            <option value="4">Perşembe</option>
            <option value="5">Cuma</option>
            <option value="6">Cumartesi</option>
            <option value="0">Pazar</option>
        </select>
        <small class="form-text text-muted">Boş bırakılırsa ardışık günlere atanır.</small>
    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" id="appointment_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Saat</label>
                        <select id="appointment_time" class="form-select">
                            <?php
                            for ($hour = 8; $hour <= 20; $hour++) {
                                printf(
                                    '<option value="%02d:00">%02d:00</option>',
                                    $hour, $hour
                                );
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Terapist</label>
                        <select id="appointment_therapist" class="form-select">
                            <option value="">Seçiniz...</option>
                            <?php foreach ($terapistler as $terapist): ?>
                                <option value="<?php echo $terapist['id']; ?>">
                                    <?php echo htmlspecialchars($terapist['ad'] . ' ' . $terapist['soyad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Oda</label>
                        <select id="appointment_room" class="form-select">
                            <option value="">Seçiniz...</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    <?php echo htmlspecialchars($room['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="addAppointmentSlot()">
                            <i class="fas fa-plus"></i> Ekle
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
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
                        <tbody id="selected_slots"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveAppointments()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSaleId;
let totalSlots = 0;
let selectedSlots = [];
let regularSessions = 0;

document.addEventListener('DOMContentLoaded', function() {
    window.odemeModal = new bootstrap.Modal(document.getElementById('odemeModal'));
    window.detayModal = new bootstrap.Modal(document.getElementById('detayModal'));
    window.scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    
    if ($.fn.DataTable) {
        $('#satislar_table').DataTable({
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
            }
        });
    }
});

function showPaymentModal(satisId) {
    const yukleniyor = document.getElementById('odeme_yukleniyor');
    const hataDiv = document.getElementById('odeme_hata');
    const taksitListesi = document.getElementById('taksit_listesi');
    
    yukleniyor.classList.remove('d-none');
    hataDiv.classList.add('d-none');
    taksitListesi.innerHTML = '';
    window.odemeModal.show();



    fetch(`ajax/get_satis_detay.php?id=${satisId}`)
        .then(response => response.json())
        .then(data => {
            yukleniyor.classList.add('d-none');
            
            if (!data.success) {
                throw new Error(data.message || 'Veri alınamadı');
            }
            
            if (!data.taksitler || !Array.isArray(data.taksitler)) {
                throw new Error('Taksit bilgisi bulunamadı');
            }

            const unpaidInstallments = data.taksitler.filter(taksit => !taksit.odendi);
            
            if (unpaidInstallments.length === 0) {
                taksitListesi.innerHTML = '<tr><td colspan="6" class="text-center">Ödenmemiş taksit bulunmamaktadır.</td></tr>';
                return;
            }

            unpaidInstallments.forEach(taksit => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formatDate(taksit.vade_tarihi)}</td>
                    <td>${formatPrice(taksit.tutar)}</td>
                    <td>
                        <span class="badge bg-warning">Bekliyor</span>
                    </td>
                    <td>
                        <input type="date" class="form-control form-control-sm" 
                               id="odeme_tarihi_${taksit.id}"
                               value="${new Date().toISOString().split('T')[0]}">
                    </td>
                    <td>
                        <select class="form-select form-select-sm" id="odeme_tipi_${taksit.id}">
                            <option value="nakit">Nakit</option>
                            <option value="kredi_karti">Kredi Kartı</option>
                            <option value="havale">Havale</option>
                            <option value="eft">EFT</option>
                        </select>
                    </td>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="odendi_${taksit.id}"
                                   onchange="handlePaymentCheck('${taksit.id}')">
                        </div>
                    </td>
                `;
                taksitListesi.appendChild(row);
            });
        })
        .catch(error => {
            yukleniyor.classList.add('d-none');
            hataDiv.classList.remove('d-none');
            hataDiv.textContent = `Hata: ${error.message}`;
            console.error('Error:', error);
        });
}

function handlePaymentCheck(taksitId) {
    const checkbox = document.getElementById(`odendi_${taksitId}`);
    if (checkbox.checked) {
        markAsPaid(taksitId);
    }
}

function markAsPaid(taksitId) {
    const odeme_tarihi = document.getElementById(`odeme_tarihi_${taksitId}`).value;
    const odeme_tipi = document.getElementById(`odeme_tipi_${taksitId}`).value;
    
    if (!odeme_tarihi || !odeme_tipi) {
        alert('Lütfen ödeme tarihi ve ödeme tipini seçiniz!');
        document.getElementById(`odendi_${taksitId}`).checked = false;
        return;
    }
    
    const formData = new FormData();
    formData.append('taksit_id', taksitId);
    formData.append('odeme_tarihi', odeme_tarihi);
    formData.append('odeme_tipi', odeme_tipi);
    
    fetch('ajax/mark_installment_paid.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Hata: ' + data.message);
            document.getElementById(`odendi_${taksitId}`).checked = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
        document.getElementById(`odendi_${taksitId}`).checked = false;
    });
}

function showDetails(satisId) {
    fetch(`ajax/get_satis_detay.php?id=${satisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Sale details
                let detayHtml = `
                    <h6 class="mb-3">Satış Bilgileri</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Danışan:</strong> ${data.satis.danisan_adi}</p>
                            <p><strong>Paket:</strong> ${data.satis.paket_adi}</p>
                            <p><strong>Satış Personeli:</strong> ${data.satis.personel_adi}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Toplam Tutar:</strong> ${formatPrice(data.satis.toplam_tutar)}</p>
                            <p><strong>Ödenen Tutar:</strong> ${formatPrice(data.satis.toplam_odenen)}</p>
                            <p><strong>Kalan Tutar:</strong> ${formatPrice(data.satis.toplam_tutar - data.satis.toplam_odenen)}</p>
                        </div>
                    </div>`;
                
                // Payment history
                let odemeHtml = `
                    <h6 class="mb-3">Ödeme Geçmişi</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Tip</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                if (data.odemeler && data.odemeler.length > 0) {
                    data.odemeler.forEach(odeme => {
                        odemeHtml += `
                            <tr>
                                <td>${formatDate(odeme.odeme_tarihi)}</td>
                                <td>${formatPrice(odeme.tutar)}</td>
                                <td>${odeme.odeme_tipi}</td>
                            </tr>`;
                    });
                } else {
                    odemeHtml += '<tr><td colspan="3" class="text-center">Ödeme bulunmamaktadır.</td></tr>';
                }
                
                odemeHtml += `</tbody></table></div>`;

                // Taksit details with payment controls
                let taksitHtml = `
                    <h6 class="mb-3">Taksitler</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Vade Tarihi</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th>Ödeme Tarihi</th>
                                    <th>Ödeme Tipi</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>`;

                if (data.taksitler && data.taksitler.length > 0) {
                    data.taksitler.forEach(taksit => {
                        const isOdendi = taksit.odendi === '1' || taksit.odendi === 1;
                        taksitHtml += `
                            <tr>
                                <td>${formatDate(taksit.vade_tarihi)}</td>
                                <td>${formatPrice(taksit.tutar)}</td>
                                <td>
                                    <span class="badge bg-${isOdendi ? 'success' : 'warning'}">
                                        ${isOdendi ? 'Ödendi' : 'Bekliyor'}
                                    </span>
                                </td>
                                <td>
                                    ${isOdendi ? formatDate(taksit.odeme_tarihi) : `
                                        <input type="date" class="form-control form-control-sm" 
                                               id="detay_odeme_tarihi_${taksit.id}"
                                               value="${new Date().toISOString().split('T')[0]}"
                                               ${isOdendi ? 'disabled' : ''}>
                                    `}
                                </td>
                                <td>
                                    ${isOdendi ? taksit.odeme_tipi : `
                                        <select class="form-select form-select-sm" 
                                                id="detay_odeme_tipi_${taksit.id}"
                                                ${isOdendi ? 'disabled' : ''}>
                                            <option value="nakit">Nakit</option>
                                            <option value="kredi_karti">Kredi Kartı</option>
                                            <option value="havale">Havale</option>
                                            <option value="eft">EFT</option>
                                        </select>
                                    `}
                                </td>
                                <td>
                                    ${!isOdendi ? `
                                        <div class="d-flex gap-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="detay_odendi_${taksit.id}"
                                                       onchange="handleDetailPaymentCheck('${taksit.id}')">
                                            </div>
                                            <button type="button" class="btn btn-sm btn-success"
                                                    onclick="markDetailAsPaid('${taksit.id}')">
                                                Kaydet
                                            </button>
                                        </div>
                                    ` : ''}
                                </td>
                            </tr>`;
                    });
                } else {
                    taksitHtml += '<tr><td colspan="6" class="text-center">Taksit bulunmamaktadır.</td></tr>';
                }
                
                taksitHtml += `</tbody></table></div>`;
                
                // Appointments
                let randevuHtml = `
                    <h6 class="mb-3">Randevular</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Terapist</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                if (data.randevular && data.randevular.length > 0) {
                    data.randevular.forEach(randevu => {
                        randevuHtml += `
                            <tr>
                                <td>${formatDate(randevu.randevu_tarihi)}</td>
                                <td>${randevu.terapist_adi}</td>
                                <td>${randevu.durum}</td>
                            </tr>`;
                    });
                } else {
                    randevuHtml += '<tr><td colspan="3" class="text-center">Randevu bulunmamaktadır.</td></tr>';
                }
                
                randevuHtml += `</tbody></table></div>`;
                
                document.getElementById('satis_detay').innerHTML = detayHtml;
                document.getElementById('odeme_gecmisi').innerHTML = odemeHtml;
                document.getElementById('taksit_detay').innerHTML = taksitHtml;
                document.getElementById('randevu_listesi').innerHTML = randevuHtml;
                
                window.detayModal.show();
            }
        });
}

function handleDetailPaymentCheck(taksitId) {
    const checkbox = document.getElementById(`detay_odendi_${taksitId}`);
    if (!checkbox.checked) {
        document.getElementById(`detay_odeme_tarihi_${taksitId}`).disabled = true;
        document.getElementById(`detay_odeme_tipi_${taksitId}`).disabled = true;
    } else {
        document.getElementById(`detay_odeme_tarihi_${taksitId}`).disabled = false;
        document.getElementById(`detay_odeme_tipi_${taksitId}`).disabled = false;
    }
}

function markDetailAsPaid(taksitId) {
    const odeme_tarihi = document.getElementById(`detay_odeme_tarihi_${taksitId}`).value;
    const odeme_tipi = document.getElementById(`detay_odeme_tipi_${taksitId}`).value;
    
    if (!odeme_tarihi || !odeme_tipi) {
        alert('Lütfen ödeme tarihi ve ödeme tipini seçiniz!');
        return;
    }
    
    const formData = new FormData();
    formData.append('taksit_id', taksitId);
    formData.append('odeme_tarihi', odeme_tarihi);
    formData.append('odeme_tipi', odeme_tipi);
    
    fetch('ajax/mark_installment_paid.php', {
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

function showScheduleModal(saleId, slots) {
    currentSaleId = saleId;
    totalSlots = slots;
    
  
    // Get sale details to determine regular sessions
    fetch(`ajax/get_satis_detay.php?id=${saleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                regularSessions = data.satis.seans_adet;
                selectedSlots = data.randevular ? data.randevular.map(randevu => ({
                    id: randevu.id,
                    datetime: randevu.randevu_tarihi,
                    personel_id: randevu.personel_id,
                    room_id: randevu.room_id,
                    therapist_name: randevu.terapist_adi,
                    room_name: randevu.room_name,
                    is_gift: randevu.is_gift === 1,
                    evaluation_type: randevu.evaluation_type
                })) : [];
            } else {
                selectedSlots = [];
            }
            updateSelectedSlots();
            window.scheduleModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            selectedSlots = [];
            updateSelectedSlots();
            window.scheduleModal.show();
        });
}
function addAppointmentSlot() {
    if (selectedSlots.length >= totalSlots) {
        alert('Maksimum randevu sayısına ulaştınız!');
        return;
    }

    const date = document.getElementById('appointment_date').value;
    const time = document.getElementById('appointment_time').value;
    const therapist = document.getElementById('appointment_therapist');
    const room = document.getElementById('appointment_room');
    
    if (!date || !time) {
        alert('Lütfen tarih ve saat seçiniz!');
        return;
    }

    const datetime = `${date} ${time}:00`;
    
    // Check if slot already exists
    if (selectedSlots.some(slot => slot.datetime === datetime)) {
        alert('Bu tarih ve saat için zaten bir randevu eklediniz!');
        return;
    }

    // Get current session number
    const currentIndex = selectedSlots.length + 1;
    
    // Get session type from sale details
    fetch(`ajax/get_seans_turu.php?satis_id=${currentSaleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const { evaluation_interval } = data;
                let evaluation_type = null;
                
                if (evaluation_interval) {
                    if (currentIndex === 1) {
                        evaluation_type = 'initial';
                    } else if (currentIndex === totalSlots) {
                        evaluation_type = 'final';
                    } else if (evaluation_interval && currentIndex % evaluation_interval === 0) {
                        evaluation_type = 'progress';
                    }
                }
                
                selectedSlots.push({
                    datetime,
                    personel_id: therapist.value || null,
                    room_id: room.value || null,
                    therapist_name: therapist.value ? therapist.options[therapist.selectedIndex].text : null,
                    room_name: room.value ? room.options[room.selectedIndex].text : null,
                    evaluation_type
                });
                
                updateSelectedSlots();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Seans türü bilgisi alınamadı!');
        });
}

function removeSlot(index) {
    selectedSlots.splice(index, 1);
    updateSelectedSlots();
}
function updateSelectedSlots1() {
    const tbody = document.getElementById('selected_slots');
    const remainingElement = document.getElementById('remaining_slots');
    
    tbody.innerHTML = '';
    
    selectedSlots.forEach((slot, index) => {
        const datetime = new Date(slot.datetime);
        const tr = document.createElement('tr');
        
        // Add gift session indicator class
        if (slot.is_gift) {
            tr.classList.add('table-warning');
        }
        
        // Evaluation type badges
        let evaluationBadge = '';
        if (slot.evaluation_type) {
            switch(slot.evaluation_type) {
                case 'initial':
                    evaluationBadge = '<span class="badge bg-info">İlk Değerlendirme</span>';
                    break;
                case 'progress':
                    evaluationBadge = '<span class="badge bg-warning">Değerlendirme</span>';
                    break;
                case 'final':
                    evaluationBadge = '<span class="badge bg-success">Son Değerlendirme</span>';
                    break;
            }
        }
        
        tr.innerHTML = `
        <td>${datetime.toLocaleDateString('tr-TR')}</td>
        <td>${datetime.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}</td>
        <td>${slot.therapist_name || '-'}</td>
        <td>${slot.room_name || '-'}</td>
        <td>${evaluationBadge}</td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeSlot(${index})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
        <td>
        <button class="btn btn-sm btn-outline-danger" onclick="cancelSlot(${index})">
            <i class='fas fa-ban'></i> İptal
        </button>
        </td>
    `;
        tbody.appendChild(tr);
    });
 
    // Update remaining slots info
    const remaining = totalSlots - selectedSlots.length;
    remainingElement.textContent = `Toplam Seans: ${totalSlots} | Kalan: ${remaining}`;
}

function updateSelectedSlots() {
    const tbody = document.getElementById('selected_slots');
    const remainingElement = document.getElementById('remaining_slots');

    tbody.innerHTML = '';

    selectedSlots.forEach((slot, index) => {
        const datetime = new Date(slot.datetime);
        const tr = document.createElement('tr');

        // Add gift session indicator class
        if (slot.is_gift) {
            tr.classList.add('table-warning');
        }

        
        // Add cancelled session style
        if (slot.is_cancelled) {
            tr.classList.add('table-danger');
        }

        // Evaluation type badges
        let evaluationBadge = '';
        if (slot.evaluation_type) {
            switch(slot.evaluation_type) {
                case 'initial':
                    evaluationBadge = '<span class="badge bg-info">İlk Değerlendirme</span>';
                    break;
                case 'progress':
                    evaluationBadge = '<span class="badge bg-warning">Değerlendirme</span>';
                    break;
                case 'final':
                    evaluationBadge = '<span class="badge bg-success">Son Değerlendirme</span>';
                    break;
            }
        }

        tr.innerHTML = `
            <td>${datetime.toLocaleDateString('tr-TR')}</td>
            <td>${datetime.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })}</td>
            <td>${slot.therapist_name || '-'}</td>
            <td>${slot.room_name || '-'}</td>
            <td>${evaluationBadge}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSlot(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            <td>
                <button
                    class="btn btn-sm btn-outline-danger"
                    onclick="cancelSlot(${index})">
                    <i class='fas fa-ban'></i> İptal
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Update remaining slots info (sadece aktif olanları say)
    const activeCount = selectedSlots.filter(s => !s.is_cancelled).length;
    const remaining = totalSlots - activeCount;
    remainingElement.textContent = `Toplam Seans: ${totalSlots} | Kalan: ${remaining}`;
}


function saveAppointments() {
    if (selectedSlots.length === 0) {
        alert('Lütfen en az bir randevu ekleyiniz!');
        return;
    }

    fetch('ajax/schedule_appointments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            satis_id: currentSaleId,
            appointments: selectedSlots
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.scheduleModal.hide();
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

function formatPrice(price) {
    return new Intl.NumberFormat('tr-TR', { 
        style: 'currency', 
        currency: 'TRY' 
    }).format(price);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('tr-TR');
}
</script>

<script>
document.getElementById('planlama_tipi').addEventListener('change', function () {
    const gunSecimiDiv = document.getElementById('gun_secimi_div');
    gunSecimiDiv.classList.toggle('d-none', this.value === 'manual');
});

function addAppointmentSlot() {
    const planTipi = document.getElementById('planlama_tipi').value;

    if (planTipi === 'manual') {
        addManualAppointment();
    } else {
        addAutoAppointments(planTipi);
    }
}

function addManualAppointment() {
    const date = document.getElementById('appointment_date').value;
    const time = document.getElementById('appointment_time').value;
    const therapist = document.getElementById('appointment_therapist');
    const room = document.getElementById('appointment_room');

    if (!date || !time) {
        alert('Lütfen tarih ve saat seçiniz!');
        return;
    }

    const datetime = `${date} ${time}:00`;

    if (selectedSlots.some(slot => slot.datetime === datetime)) {
        alert('Bu tarih ve saat için zaten bir randevu eklediniz!');
        return;
    }

    const currentIndex = selectedSlots.length + 1;

    fetch(`ajax/get_seans_turu.php?satis_id=${currentSaleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const { evaluation_interval } = data;
                let evaluation_type = null;

                if (evaluation_interval) {
                    if (currentIndex === 1) evaluation_type = 'initial';
                    else if (currentIndex === totalSlots) evaluation_type = 'final';
                    else if (currentIndex % evaluation_interval === 0) evaluation_type = 'progress';
                }

                selectedSlots.push({
                    datetime,
                    personel_id: therapist.value || null,
                    room_id: room.value || null,
                    therapist_name: therapist.value ? therapist.options[therapist.selectedIndex].text : null,
                    room_name: room.value ? room.options[room.selectedIndex].text : null,
                    evaluation_type
                });

                updateSelectedSlots();
            }
        });
}

function addAutoAppointments1(planTipi) {
    const baseDate = new Date(document.getElementById('appointment_date').value);
    const time = document.getElementById('appointment_time').value;
    const therapist = document.getElementById('appointment_therapist');
    const room = document.getElementById('appointment_room');
    const gunler = Array.from(document.getElementById('gun_secimi').selectedOptions).map(o => parseInt(o.value));

    if (!baseDate || !time) {
        alert('Lütfen başlangıç tarihi ve saati seçiniz!');
        return;
    }

    let eklendi = 0;
    let loopDate = new Date(baseDate);

    while (eklendi < totalSlots) {
        const gunUygun = gunler.length === 0 || gunler.includes(loopDate.getDay());

        if (gunUygun) {
            const dateStr = loopDate.toISOString().split('T')[0];
            const datetime = `${dateStr} ${time}:00`;

            if (!selectedSlots.some(slot => slot.datetime === datetime)) {
                selectedSlots.push({
                    datetime,
                    personel_id: therapist.value || null,
                    room_id: room.value || null,
                    therapist_name: therapist.value ? therapist.options[therapist.selectedIndex].text : null,
                    room_name: room.value ? room.options[room.selectedIndex].text : null,
                    evaluation_type: null
                });
                eklendi++;
            }
        }

        if (planTipi === 'haftalik') loopDate.setDate(loopDate.getDate() + 1);
        else if (planTipi === 'aylik') loopDate.setMonth(loopDate.getMonth() + 1);
    }

    updateSelectedSlots();
}

function addAutoAppointments(planTipi) {
    const baseDate = new Date(document.getElementById('appointment_date').value);
    const time = document.getElementById('appointment_time').value;
    const therapist = document.getElementById('appointment_therapist');
    const room = document.getElementById('appointment_room');
    const gunler = Array.from(document.getElementById('gun_secimi').selectedOptions).map(o => parseInt(o.value));

    if (!baseDate || !time) {
        alert('Lütfen başlangıç tarihi ve saati seçiniz!');
        return;
    }

    let eklendi = 0;
    let loopDate = new Date(baseDate);

    const kalanSeans = totalSlots - selectedSlots.length;
    if (kalanSeans <= 0) {
        alert('Zaten maksimum seans sayısına ulaştınız.');
        return;
    }

    while (eklendi < kalanSeans) {
        const gunUygun = gunler.length === 0 || gunler.includes(loopDate.getDay());

        if (gunUygun) {
            const dateStr = loopDate.toISOString().split('T')[0];
            const datetime = `${dateStr} ${time}:00`;

            if (!selectedSlots.some(slot => slot.datetime === datetime)) {
                selectedSlots.push({
                    datetime,
                    personel_id: therapist.value || null,
                    room_id: room.value || null,
                    therapist_name: therapist.value ? therapist.options[therapist.selectedIndex].text : null,
                    room_name: room.value ? room.options[room.selectedIndex].text : null,
                    evaluation_type: null
                });
                eklendi++;
            }
        }

        if (planTipi === 'haftalik') loopDate.setDate(loopDate.getDate() + 1);
        else if (planTipi === 'aylik') loopDate.setMonth(loopDate.getMonth() + 1);
    }

    updateSelectedSlots();
}

function deleteSale(id) {
    if (!confirm('Bu satışı kalıcı olarak silmek istediğinizden emin misiniz?')) return;
    fetch('ajax/delete_sale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Silme işlemi başarısız: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Sunucu hatası: ' + err);
    });
}



function cancelSlot(index) {
    const slot = selectedSlots[index];

    const randevuId = slot.id; // doğrudan slot içinden al

    
    const cancelledCount = selectedSlots.filter(s => s.is_cancelled).length;
    if (cancelledCount >= 4) {
        alert("En fazla 4 seans iptal edebilirsiniz.");
        return;
    }

    const confirmCancel = confirm(`${slot.datetime} tarihli seansı iptal etmek istiyor musunuz?`);
    if (!confirmCancel) return;

    selectedSlots[index].is_cancelled = true;
    updateSelectedSlots();

    
    // ✅ ID varsa veritabanında güncelle
    if (randevuId) {
        fetch('randevu_iptal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ randevu_id: randevuId })
        })
        .then(response => response.json()) // burası bozuluyordu
        .then(data => {
            if (!data.success) {
                alert("İptal işlemi başarısız: " + data.message);
            }
        })
        .catch(error => {
            alert("Sunucu hatası: " + error);
        });

    }
}


function cancelSlot0(index, randevuId) {
    const slot = selectedSlots[index];

      alert(slot.id);

    const cancelledCount = selectedSlots.filter(s => s.is_cancelled).length;
    if (cancelledCount >= 4) {
        alert("En fazla 4 seans iptal edebilirsiniz.");
        return;
    }
  
 
    const confirmCancel = confirm(`${slot.datetime} tarihli seansı iptal etmek istiyor musunuz?`);
    if (!confirmCancel) return;

    selectedSlots[index].is_cancelled = true;
    updateSelectedSlots();

    if (randevuId) {
     
        fetch('randevu_iptal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ randevu_id: randevuId })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("İptal işlemi başarısız: " + data.message);
            }
        })
        .catch(error => {
            alert("Sunucu hatası: " + error);
        });
    }
}



function cancelSlot1(index) {
    const slot = selectedSlots[index];

    const iptalSayisi = selectedSlots.filter(s => s.is_cancelled).length;
    if (iptalSayisi >= 4) {
        alert("En fazla 4 randevu iptal edebilirsiniz.");
        return;
    }

    const onay = confirm(slot.datetime + " tarihli randevuyu iptal etmek istiyor musunuz?");
    if (!onay) return;

    selectedSlots[index].is_cancelled = true;
    updateSelectedSlots();
}

function cancelSlot2(index) {
    const slot = selectedSlots[index];

    const cancelledCount = selectedSlots.filter(s => s.is_cancelled).length;
    if (cancelledCount >= 4) {
        alert("En fazla 4 seans iptal edebilirsiniz.");
        return;
    }

    const confirmCancel = confirm(`${slot.datetime} tarihli seansı iptal etmek istiyor musunuz?`);
    if (!confirmCancel) return;

    // Frontend'de işaretle
    selectedSlots[index].is_cancelled = true;
    updateSelectedSlots();

    // Backend'e bildir: randevuyu iptal et (aktif = 0 yap)
    fetch('randevu_iptal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ randevu_id: slot.id }) // id ile güncelle
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('İptal işlemi başarılı');
        } else {
            alert('İptal işlemi başarısız: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Hata:', error);
    });
}


</script>

<style>
/* Add styles for gift sessions */
.table-warning {
    background-color: #fff3cd !important;
}

.badge {
    margin-right: 4px;
}
</style>

                            

                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/customizer.php' ?>

    <?php include 'partials/footer-scripts.php' ?>


</body>

</html>