<?php
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'seans_turu_ekle':
                $sonuc = seansTuruEkle(
                    $_POST['ad'],
                    $_POST['sure'],
                    $_POST['fiyat'],
                    isset($_POST['deneme_mi']),
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Seans türü başarıyla eklendi.";
                    header("Location: ?page=seans_turleri");
                    exit;
                } else {
                    $hata = "Seans türü eklenirken bir hata oluştu.";
                }
                break;

            case 'seans_turu_guncelle':
                $sonuc = seansTuruGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['sure'],
                    $_POST['fiyat'],
                    isset($_POST['deneme_mi']),
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Seans türü başarıyla güncellendi.";
                    header("Location: ?page=seans_turleri");
                    exit;
                } else {
                    $hata = "Seans türü güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için seans türü bilgilerini al
$duzenlenecek_seans = null;
if ($action == 'edit' && isset($_GET['id'])) {
    foreach ($seansTurleri as $seans) {
        if ($seans['id'] == $_GET['id']) {
            $duzenlenecek_seans = $seans;
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
        <!-- Seans Türleri Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Seans Türleri</h5>
                <a href="?page=seans_turleri&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Seans Türü
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad</th>
                                <th>Süre (dk)</th>
                                <th>Fiyat</th>
                                <th>Deneme Seansı</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seansTurleri as $seans): ?>
                            <tr>
                                <td><?php echo $seans['ad']; ?></td>
                                <td><?php echo $seans['sure']; ?></td>
                                <td><?php echo formatPrice($seans['fiyat']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $seans['deneme_mi'] ? 'info' : 'secondary'; ?>">
                                        <?php echo $seans['deneme_mi'] ? 'Evet' : 'Hayır'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $seans['aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $seans['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=seans_turleri&action=edit&id=<?php echo $seans['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <?php if ($seans['aktif']): ?>
                                        <button type="button" class="btn btn-warning"
                                                onclick="if(confirm('Bu seans türünü pasife almak istediğinizden emin misiniz?'))
                                                window.location.href='?page=seans_turleri&action=deactivate&id=<?php echo $seans['id']; ?>'">
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-success"
                                                onclick="if(confirm('Bu seans türünü aktife almak istediğinizden emin misiniz?'))
                                                window.location.href='?page=seans_turleri&action=activate&id=<?php echo $seans['id']; ?>'">
                                            <i class="bx bx-power-off"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-danger"
                                                onclick="if(confirm('Bu seans türünü silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=seans_turleri&action=delete&id=<?php echo $seans['id']; ?>'">
                                            <i class="bx bx-trash"></i>
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
        <!-- Seans Türü Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Seans Türü Düzenle' : 'Yeni Seans Türü'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'seans_turu_guncelle' : 'seans_turu_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_seans['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_seans['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Süre (dakika)</label>
                            <input type="number" name="sure" class="form-control" required min="1"
                                   value="<?php echo $duzenlenecek_seans['sure'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fiyat (₺)</label>
                            <input type="number" name="fiyat" class="form-control" required min="0" step="0.01"
                                   value="<?php echo $duzenlenecek_seans['fiyat'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="deneme_mi" class="form-check-input" id="deneme_mi"
                                       <?php echo ($duzenlenecek_seans['deneme_mi'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="deneme_mi">Deneme Seansı</label>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"><?php echo $duzenlenecek_seans['aciklama'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=seans_turleri" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>