<?php
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'terapist_ekle':
                $sonuc = personelEkle(
                    $_POST['ad'],
                    $_POST['soyad'],
                    $_POST['email'],
                    $_POST['telefon'],
                    'terapist'
                );
                if ($sonuc) {
                    $basari = "Terapist başarıyla eklendi.";
                    header("Location: ?page=terapistler");
                    exit;
                } else {
                    $hata = "Terapist eklenirken bir hata oluştu.";
                }
                break;

            case 'terapist_guncelle':
                $sonuc = personelGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['soyad'],
                    $_POST['email'],
                    $_POST['telefon']
                );
                if ($sonuc) {
                    $basari = "Terapist başarıyla güncellendi.";
                    header("Location: ?page=terapistler");
                    exit;
                } else {
                    $hata = "Terapist güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için terapist bilgilerini al
$duzenlenecek_terapist = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $terapistler = getTerapistler();
    foreach ($terapistler as $terapist) {
        if ($terapist['id'] == $_GET['id']) {
            $duzenlenecek_terapist = $terapist;
            break;
        }
    }
}
?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <!-- Terapist Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Terapistler</h5>
                <a href="?page=terapistler&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Terapist
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $terapistler = getTerapistler();
                            foreach ($terapistler as $terapist): 
                            ?>
                            <tr>
                                <td><?php echo $terapist['ad'] . ' ' . $terapist['soyad']; ?></td>
                                <td><?php echo $terapist['email']; ?></td>
                                <td><?php echo $terapist['telefon']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $terapist['aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $terapist['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=terapistler&action=edit&id=<?php echo $terapist['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <?php if ($terapist['aktif']): ?>
                                        <button type="button" class="btn btn-warning"
                                                onclick="if(confirm('Bu terapisti pasife almak istediğinizden emin misiniz?'))
                                                window.location.href='?page=terapistler&action=deactivate&id=<?php echo $terapist['id']; ?>'">
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-success"
                                                onclick="if(confirm('Bu terapisti aktife almak istediğinizden emin misiniz?'))
                                                window.location.href='?page=terapistler&action=activate&id=<?php echo $terapist['id']; ?>'">
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                        <?php endif; ?>
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
        <!-- Terapist Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Terapist Düzenle' : 'Yeni Terapist'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'terapist_guncelle' : 'terapist_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_terapist['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_terapist['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Soyad</label>
                            <input type="text" name="soyad" class="form-control" required
                                   value="<?php echo $duzenlenecek_terapist['soyad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo $duzenlenecek_terapist['email'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="telefon" class="form-control" required
                                   value="<?php echo $duzenlenecek_terapist['telefon'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=terapistler" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>