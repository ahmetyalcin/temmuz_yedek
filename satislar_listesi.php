<?php
// satislar.php - Güncellenmiş satış listesi
session_start();
require_once 'functions.php';

// AJAX istekleri için faturalama durumu güncelleme
if (isset($_POST['action']) && $_POST['action'] == 'update_fatura_status') {
    $satis_id = $_POST['satis_id'];
    $faturalandi = $_POST['faturalandi'];
    
    try {
        $sql = "UPDATE satislar SET faturalandi = :faturalandi WHERE id = :satis_id";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            'faturalandi' => $faturalandi,
            'satis_id' => $satis_id
        ]);
        
        echo json_encode(['success' => $success]);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Satışları getir
$satislar = getSatislar();

$title = 'Satış Listesi';
include __DIR__ . '/partials/header.php';
?>

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            <?php include __DIR__ . '/partials/page-title.php'; ?>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Satış Listesi</h5>
                                <a href="satis_ekle.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Yeni Satış
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="satislarTable">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Danışan</th>
                                                <th>Hizmet Paketi</th>
                                                <th>Personel</th>
                                                <th>Toplam Tutar</th>
                                                <th>Durum</th>
                                                <th>Faturalama</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($satislar as $satis): ?>
                                                <tr>
                                                    <td><?= date('d.m.Y', strtotime($satis['olusturma_tarihi'])) ?></td>
                                                    <td><?= htmlspecialchars($satis['danisan_adi']) ?></td>
                                                    <td><?= htmlspecialchars($satis['paket_adi']) ?></td>
                                                    <td><?= htmlspecialchars($satis['personel_adi']) ?></td>
                                                    <td><?= number_format($satis['toplam_tutar'], 2) ?> ₺</td>
                                                    <td>
                                                        <?php if($satis['durum'] == 'odendi'): ?>
                                                            <span class="badge bg-success">Ödendi</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Beklemede</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input fatura-switch" 
                                                                   type="checkbox" 
                                                                   id="fatura_<?= $satis['id'] ?>"
                                                                   data-satis-id="<?= $satis['id'] ?>"
                                                                   <?= $satis['faturalandi'] ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="fatura_<?= $satis['id'] ?>">
                                                                <span class="fatura-label">
                                                                    <?= $satis['faturalandi'] ? 'Faturalı' : 'Faturasız' ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-info btn-sm" 
                                                                    onclick="showSalesDetail('<?= $satis['id'] ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <a href="satis_duzenle.php?id=<?= $satis['id'] ?>" 
                                                               class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-danger btn-sm" 
                                                                    onclick="deleteSale('<?= $satis['id'] ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
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

<!-- Satış Detay Modal -->
<div class="modal fade" id="salesDetailModal" tabindex="-1">
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

<?php include 'partials/footer-scripts.php'; ?>

<script>
$(document).ready(function() {
    // DataTable initialization
    $('#satislarTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[0, "desc"]]
    });
    
    // Faturalama durumu değişikliği
    $('.fatura-switch').change(function() {
        const satisId = $(this).data('satis-id');
        const faturalandi = $(this).is(':checked') ? 1 : 0;
        const labelElement = $(this).siblings('label').find('.fatura-label');
        
        $.ajax({
            url: '',
            method: 'POST',
            data: {
                action: 'update_fatura_status',
                satis_id: satisId,
                faturalandi: faturalandi
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Label'ı güncelle
                    labelElement.text(faturalandi ? 'Faturalı' : 'Faturasız');
                    
                    // Toast mesajı göster
                    showToast(
                        faturalandi ? 'Satış faturalı olarak işaretlendi' : 'Satış faturasız olarak işaretlendi',
                        'success'
                    );
                } else {
                    // Hata durumunda checkbox'ı eski haline getir
                    $(this).prop('checked', !faturalandi);
                    showToast('Faturalama durumu güncellenirken hata oluştu', 'error');
                }
            }.bind(this),
            error: function() {
                // Hata durumunda checkbox'ı eski haline getir
                $(this).prop('checked', !faturalandi);
                showToast('Bir hata oluştu', 'error');
            }.bind(this)
        });
    });
});

// Toast mesajı gösterme fonksiyonu
function showToast(message, type = 'info') {
    const toastClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    
    const toastHTML = `
        <div class="toast align-items-center text-white ${toastClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Toast container oluştur
    if (!$('#toast-container').length) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>');
    }
    
    const toastElement = $(toastHTML).appendTo('#toast-container');
    const toast = new bootstrap.Toast(toastElement[0]);
    toast.show();
    
    // Toast gösterildikten sonra DOM'dan kaldır
    toastElement.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Satış detayını göster
function showSalesDetail(salesId) {
    // AJAX ile satış detaylarını getir ve modal'da göster
    // Bu fonksiyonu ihtiyaçlarına göre genişletebilirsin
    $('#salesDetailModal').modal('show');
    $('#satis_detay').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    // Satış detaylarını getir
    $.get('get_sales_detail.php', {id: salesId}, function(data) {
        $('#satis_detay').html(data);
    });
}

// Satış silme
function deleteSale(salesId) {
    if (confirm('Bu satışı silmek istediğinize emin misiniz?')) {
        $.post('delete_sale.php', {id: salesId}, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Satış silinirken hata oluştu');
            }
        }, 'json');
    }
}
</script>

<style>
.fatura-switch {
    transform: scale(1.2);
}

.form-check-label {
    font-weight: 500;
}

.fatura-label {
    margin-left: 8px;
}

.form-switch .form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.form-switch .form-check-input:not(:checked) {
    background-color: #6c757d;
    border-color: #6c757d;
}

.toast-container {
    z-index: 1060;
}
</style>

<?php include 'partials/footer.php'; ?>