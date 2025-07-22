<?php
$action = $_GET['action'] ?? 'list';
$tab = $_GET['tab'] ?? 'randevular';
$hata = '';
$basari = '';

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    if ($_POST['ajax_action'] === 'fix_talep_ekle') {
        $sonuc = fixTalepEkle(
            $_POST['danisan_id'],
            $_POST['gun'],
            $_POST['saat'],
            $_POST['tekrar_tipi'],
            $_POST['notlar'] ?? null
        );
        
        echo json_encode([
            'success' => $sonuc,
            'message' => $sonuc ? 'Fix talep başarıyla eklendi.' : 'Fix talep eklenirken bir hata oluştu.'
        ]);
        exit;
    }
    
    if ($_POST['ajax_action'] === 'fix_talep_sil') {
        $sonuc = fixTalepSil($_POST['talep_id']);
        
        echo json_encode([
            'success' => $sonuc,
            'message' => $sonuc ? 'Fix talep başarıyla silindi.' : 'Fix talep silinirken bir hata oluştu.'
        ]);
        exit;
    }
}




// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'randevu_ekle':
                $sonuc = randevuEkle(
                    $_POST['danisan_id'],
                    $_POST['personel_id'],
                    $_POST['seans_turu_id'],
                    $_POST['randevu_tarihi'],
                    $_POST['notlar'] ?? '',
                    $_POST['satis_id'] ?? null,
                    $_POST['hediye_seans_id'] ?? null
                );
                if ($sonuc) {
                    $basari = "Randevu başarıyla eklendi.";
                    header("Location: ?page=randevular");
                    exit;
                } else {
                    $hata = "Randevu eklenirken bir hata oluştu.";
                }
                break;

            case 'randevu_guncelle':
                $sonuc = randevuDurumGuncelle(
                    $_POST['id'],
                    $_POST['durum'],
                    $_POST['notlar'] ?? null
                );
                if ($sonuc) {
                    $basari = "Randevu durumu güncellendi.";
                    header("Location: ?page=randevular");
                    exit;
                } else {
                    $hata = "Randevu güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için randevu bilgilerini al
$duzenlenecek_randevu = null;
if ($action == 'edit' && isset($_GET['id'])) {
    foreach ($randevular as $randevu) {
        if ($randevu['id'] == $_GET['id']) {
            $duzenlenecek_randevu = $randevu;
            break;
        }
    }
}

// Tüm fix talepleri al
$tum_fix_talepler = [];
$sql = "SELECT ft.*, d.ad as danisan_adi, d.soyad as danisan_soyadi 
        FROM fix_talepler ft 
        JOIN danisanlar d ON d.id = ft.danisan_id 
        WHERE ft.aktif = 1 
        ORDER BY d.ad, d.soyad, ft.gun, ft.saat";
try {
    $stmt = $pdo->query($sql);
    $tum_fix_talepler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Fix talepleri getirme hatası: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab == 'randevular' ? 'active' : ''; ?>" 
               href="?page=randevular&tab=randevular">
                <i class='bx bx-calendar'></i> Randevular
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab == 'fix_talepler' ? 'active' : ''; ?>" 
               href="?page=randevular&tab=fix_talepler">
                <i class='bx bx-time-five'></i> Fix Talepler
            </a>
        </li>
    </ul>

    <?php if ($tab == 'randevular'): ?>
        <?php if ($action == 'list'): ?>
            <!-- Randevu Listesi -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Randevular</h5>
                    <a href="?page=randevular&action=new" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Yeni Randevu
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Danışan</th>
                                    <th>Terapist</th>
                                    <th>Seans Türü</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($randevular as $randevu): ?>
                                <tr>
                                    <td><?php echo $randevu['danisan_adi']; ?></td>
                                    <td><?php echo $randevu['personel_adi']; ?></td>
                                    <td><?php echo $randevu['seans_turu']; ?></td>
                                    <td><?php echo formatDateTime($randevu['randevu_tarihi']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($randevu['durum']); ?>">
                                            <?php echo ucfirst($randevu['durum']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=randevular&action=edit&id=<?php echo $randevu['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="if(confirm('Bu randevuyu iptal etmek istediğinizden emin misiniz?'))
                                                    window.location.href='?page=randevular&action=cancel&id=<?php echo $randevu['id']; ?>'">
                                                <i class="bx bx-x"></i>
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
        <?php else: ?>
            <!-- Randevu Formu -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $action == 'edit' ? 'Randevu Düzenle' : 'Yeni Randevu'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="islem" 
                               value="<?php echo $action == 'edit' ? 'randevu_guncelle' : 'randevu_ekle'; ?>">
                        
                        <?php if ($action == 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $duzenlenecek_randevu['id']; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <?php if ($action != 'edit'): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Danışan</label>
                                <select name="danisan_id" id="danisan_select" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($danisanlar as $danisan): ?>
                                        <option value="<?php echo $danisan['id']; ?>"
                                                data-fix-talepler='<?php echo json_encode(getFixTalepler($danisan['id'])); ?>'>
                                            <?php echo $danisan['ad'] . ' ' . $danisan['soyad']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Randevu Tarihi</label>
                                <input type="datetime-local" name="randevu_tarihi" class="form-control" required>
                            </div>

                            <!-- Danışanın Fix Talepleri -->
                            <div class="col-12 mb-3" id="fix-talep-bilgisi" style="display: none;">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading mb-2">
                                        <i class='bx bx-info-circle me-2'></i>
                                        Danışanın Fix Talepleri
                                    </h6>
                                    <div id="fix-talep-listesi"></div>
                                </div>
                            </div>

                            <?php else: ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Durum</label>
                                <select name="durum" class="form-select" required>
                                    <option value="beklemede" <?php echo $duzenlenecek_randevu['durum'] == 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                                    <option value="onaylandi" <?php echo $duzenlenecek_randevu['durum'] == 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                                    <option value="iptal_edildi" <?php echo $duzenlenecek_randevu['durum'] == 'iptal_edildi' ? 'selected' : ''; ?>>İptal Edildi</option>
                                    <option value="tamamlandi" <?php echo $duzenlenecek_randevu['durum'] == 'tamamlandi' ? 'selected' : ''; ?>>Tamamlandı</option>
                                    <option value="ertelendi" <?php echo $duzenlenecek_randevu['durum'] == 'ertelendi' ? 'selected' : ''; ?>>Ertelendi</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-12 mb-3">
                                <label class="form-label">Notlar</label>
                                <textarea name="notlar" class="form-control" rows="3"><?php echo $duzenlenecek_randevu['notlar'] ?? ''; ?></textarea>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                            </button>
                            <a href="?page=randevular" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Fix Talepler -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Fix Talepler</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fixTalepModal">
                    <i class="bx bx-plus"></i> Yeni Fix Talep
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Danışan</th>
                                <th>Gün</th>
                                <th>Saat</th>
                                <th>Tekrar Tipi</th>
                                <th>Notlar</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tum_fix_talepler)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Henüz fix talep bulunmuyor.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tum_fix_talepler as $talep): ?>
                                    <tr>
                                        <td><?php echo $talep['danisan_adi'] . ' ' . $talep['danisan_soyadi']; ?></td>
                                        <td><?php echo $talep['gun']; ?></td>
                                        <td><?php echo substr($talep['saat'], 0, 5); ?></td>
                                        <td><?php echo $talep['tekrar_tipi']; ?></td>
                                        <td><?php echo $talep['notlar'] ?: '-'; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="if(confirm('Bu fix talebi silmek istediğinizden emin misiniz?')) fixTalepSil(<?php echo $talep['id']; ?>)">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Fix Talep Modal -->
<div class="modal fade" id="fixTalepModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="fixTalepForm">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Fix Talep</h5>
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
                        <label class="form-label">Gün</label>
                        <select name="gun" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <option value="Pazartesi">Pazartesi</option>
                            <option value="Salı">Salı</option>
                            <option value="Çarşamba">Çarşamba</option>
                            <option value="Perşembe">Perşembe</option>
                            <option value="Cuma">Cuma</option>
                            <option value="Cumartesi">Cumartesi</option>
                            <option value="Pazar">Pazar</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Saat</label>
                        <input type="time" name="saat" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tekrar Tipi</label>
                        <select name="tekrar_tipi" class="form-select" required>
                            <option value="Haftalık">Haftalık</option>
                            <option value="İki Haftalık">İki Haftalık</option>
                            <option value="Aylık">Aylık</option>
                        </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix Talep formu gönderimi
    const fixTalepForm = document.getElementById('fixTalepForm');
    if (fixTalepForm) {
        fixTalepForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax_action', 'fix_talep_ekle');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    fixTalepForm.reset();
                    bootstrap.Modal.getInstance(document.getElementById('fixTalepModal')).hide();
                    // Sayfayı yenile
                    window.location.reload();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu.', 'danger');
            });
        });
    }

    // Danışan seçildiğinde fix talepleri göster
    const danisanSelect = document.getElementById('danisan_select');
    if (danisanSelect) {
        danisanSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fixTalepler = selectedOption.dataset.fixTalepler ? JSON.parse(selectedOption.dataset.fixTalepler) : [];
            const fixTalepBilgisi = document.getElementById('fix-talep-bilgisi');
            const fixTalepListesi = document.getElementById('fix-talep-listesi');
            
            if (fixTalepler.length > 0) {
                fixTalepListesi.innerHTML = fixTalepler.map(talep => `
                    <div class="mb-1">
                        <i class='bx bx-time me-1'></i>
                        ${talep.gun} - ${talep.saat.substring(0, 5)} (${talep.tekrar_tipi})
                        ${talep.notlar ? `<br><small class="text-muted ms-4">${talep.notlar}</small>` : ''}
                    </div>
                `).join('');
                fixTalepBilgisi.style.display = 'block';
            } else {
                fixTalepBilgisi.style.display = 'none';
            }
        });
    }
});

function showAlert(message, type) {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container-fluid').insertBefore(
        alertContainer,
        document.querySelector('.container-fluid').firstChild
    );
    
    setTimeout(() => {
        alertContainer.remove();
    }, 3000);
}

function fixTalepSil(id) {
    const formData = new FormData();
    formData.append('ajax_action', 'fix_talep_sil');
    formData.append('talep_id', id);

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Sayfayı yenile
            window.location.reload();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Bir hata oluştu.', 'danger');
    });
}
</script>
