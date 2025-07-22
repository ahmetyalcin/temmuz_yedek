<?php
$terapist_id = $_GET['id'] ?? 0;
$hata = '';
$basari = '';

// Terapist bilgilerini al
$terapist = getTerapistDetay($terapist_id);
if (!$terapist) {
    header("Location: ?page=terapistler");
    exit;
}

// Sertifika yükleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem']) && $_POST['islem'] == 'sertifika_ekle') {
        $sertifika = handleCertificateUpload($_FILES['sertifika']);
        if ($sertifika['success']) {
            $sonuc = sertifikaEkle(
                $terapist_id,
                $_POST['sertifika_adi'],
                $sertifika['filename'],
                $_POST['veren_kurum'],
                $_POST['sertifika_tarihi']
            );
            if ($sonuc) {
                $basari = "Sertifika başarıyla eklendi.";
            } else {
                $hata = "Sertifika eklenirken bir hata oluştu.";
            }
        } else {
            $hata = $sertifika['message'];
        }
    }
}


// Sertifika silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'sertifika_sil') {
    $sertifika_id = $_GET['sertifika_id'] ?? 0;
    $sonuc = sertifikaSil($sertifika_id);
    if ($sonuc) {
        $basari = "Sertifika başarıyla silindi.";
    } else {
        $hata = "Sertifika silinirken bir hata oluştu.";
    }
}

// Terapistin sertifikalarını al
$sertifikalar = getSertifikalar($terapist_id);
?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Terapist Bilgileri -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo $terapist['avatar'] ? 'uploads/avatars/' . $terapist['avatar'] : 'assets/img/default-avatar.png'; ?>" 
                         alt="<?php echo $terapist['ad'] . ' ' . $terapist['soyad']; ?>"
                         class="rounded-circle img-thumbnail mb-3"
                         width="150" height="150">
                    
                    <h5 class="card-title mb-1"><?php echo $terapist['ad'] . ' ' . $terapist['soyad']; ?></h5>
                    <p class="text-muted mb-3"><?php echo ucfirst($terapist['rol']); ?></p>
                    
                    <div class="d-grid gap-2">
                        <a href="?page=terapistler&action=edit&id=<?php echo $terapist_id; ?>" 
                           class="btn btn-primary btn-sm">
                            <i class="bx bx-edit"></i> Düzenle
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col">
                            <h6 class="mb-0"><?php echo count($sertifikalar); ?></h6>
                            <small class="text-muted">Sertifika</small>
                        </div>
                        <div class="col">
                            <h6 class="mb-0"><?php echo $terapist['toplam_seans'] ?? 0; ?></h6>
                            <small class="text-muted">Seans</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detaylı Bilgiler -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Detaylı Bilgiler</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <small class="text-muted d-block">TC Kimlik No</small>
                            <span><?php echo $terapist['tc_no']; ?></span>
                        </li>
                        <li class="mb-3">
                            <small class="text-muted d-block">Sicil No</small>
                            <span><?php echo $terapist['sicil_no']; ?></span>
                        </li>
                        <li class="mb-3">
                            <small class="text-muted d-block">Cinsiyet</small>
                            <span><?php echo $terapist['cinsiyet']; ?></span>
                        </li>
                        <li class="mb-3">
                            <small class="text-muted d-block">Mezuniyet</small>
                            <span><?php echo $terapist['mezuniyet']; ?></span>
                        </li>
                        <li class="mb-0">
                            <small class="text-muted d-block">Kayıt Tarihi</small>
                            <span><?php echo date('d.m.Y', strtotime($terapist['olusturma_tarihi'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sertifikalar -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Sertifikalar</h6>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sertifikaEkleModal">
                        <i class="bx bx-plus"></i> Yeni Sertifika
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($sertifikalar)): ?>
                        <div class="text-center py-4">
                            <i class="bx bx-award text-muted" style="font-size: 4rem;"></i>
                            <p class="text-muted mt-2">Henüz sertifika eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Veren Kurum</th>
                                    <th>Sertifika Tarihi</th>
                                    <th>Dosya</th>
                                    <th style="width: 100px;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sertifikalar as $sertifika): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sertifika['veren_kurum']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($sertifika['sertifika_tarihi'])); ?></td>
                                    <td>
                                        <a href="uploads/certificates/<?php echo $sertifika['sertifika']; ?>" 
                                        target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-file"></i> Görüntüle
                                        </a>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu sertifikayı silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=terapist-detay&id=<?php echo $terapist_id; ?>&action=sertifika_sil&sertifika_id=<?php echo $sertifika['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sertifika Ekleme Modal -->
<div class="modal fade" id="sertifikaEkleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="islem" value="sertifika_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Sertifika Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sertifika Adı</label>
                        <input type="text" name="sertifika_adi" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sertifikayı Veren Kurum</label>
                        <input type="text" name="veren_kurum" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sertifika Tarihi</label>
                        <input type="date" name="sertifika_tarihi" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sertifika Dosyası</label>
                        <input type="file" name="sertifika" class="form-control" required accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF, JPG veya PNG formatında dosya yükleyebilirsiniz.</small>
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