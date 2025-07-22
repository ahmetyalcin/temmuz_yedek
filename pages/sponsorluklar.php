<?php
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'sponsorluk_ekle':
                $sonuc = sponsorlukEkle(
                    $_POST['ad'],
                    $_POST['firma_adi'],
                    $_POST['indirim_yuzdesi'],
                    $_POST['baslangic_tarihi'],
                    $_POST['bitis_tarihi'],
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Sponsorluk başarıyla eklendi.";
                    header("Location: ?page=sponsorluklar");
                    exit;
                } else {
                    $hata = "Sponsorluk eklenirken bir hata oluştu.";
                }
                break;

            case 'sponsorluk_guncelle':
                $sonuc = sponsorlukGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['firma_adi'],
                    $_POST['indirim_yuzdesi'],
                    $_POST['baslangic_tarihi'],
                    $_POST['bitis_tarihi'],
                    $_POST['aciklama']
                );
                if ($sonuc) {
                    $basari = "Sponsorluk başarıyla güncellendi.";
                    header("Location: ?page=sponsorluklar");
                    exit;
                } else {
                    $hata = "Sponsorluk güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için sponsorluk bilgilerini al
$duzenlenecek_sponsorluk = null;
if ($action == 'edit' && isset($_GET['id'])) {
    foreach ($sponsorluklar as $sponsorluk) {
        if ($sponsorluk['id'] == $_GET['id']) {
            $duzenlenecek_sponsorluk = $sponsorluk;
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
        <!-- Sponsorluklar Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sponsorluklar</h5>
                <a href="?page=sponsorluklar&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Sponsorluk
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad</th>
                                <th>Firma</th>
                                <th>İndirim %</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sponsorluklar as $sponsorluk): ?>
                            <tr>
                                <td><?php echo $sponsorluk['ad']; ?></td>
                                <td><?php echo $sponsorluk['firma_adi'] ?? '-'; ?></td>
                                <td><?php echo $sponsorluk['indirim_yuzdesi']; ?>%</td>
                                <td><?php echo formatDate($sponsorluk['baslangic_tarihi']); ?></td>
                                <td><?php echo $sponsorluk['bitis_tarihi'] ? formatDate($sponsorluk['bitis_tarihi']) : 'Süresiz'; ?></td>
                                <td>
                                    <?php
                                    $bugun = new DateTime();
                                    $baslangic = new DateTime($sponsorluk['baslangic_tarihi']);
                                    $bitis = $sponsorluk['bitis_tarihi'] ? new DateTime($sponsorluk['bitis_tarihi']) : null;
                                    
                                    if ($baslangic > $bugun) {
                                        echo '<span class="badge bg-warning">Başlayacak</span>';
                                    } elseif (!$bitis || $bitis >= $bugun) {
                                        echo '<span class="badge bg-success">Aktif</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Sona Erdi</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=sponsorluklar&action=edit&id=<?php echo $sponsorluk['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger"
                                                onclick="if(confirm('Bu sponsorluğu silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=sponsorluklar&action=delete&id=<?php echo $sponsorluk['id']; ?>'">
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
        <!-- Sponsorluk Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Sponsorluk Düzenle' : 'Yeni Sponsorluk'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'sponsorluk_guncelle' : 'sponsorluk_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_sponsorluk['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_sponsorluk['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Firma Adı</label>
                            <input type="text" name="firma_adi" class="form-control"
                                   value="<?php echo $duzenlenecek_sponsorluk['firma_adi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İndirim Yüzdesi (%)</label>
                            <input type="number" name="indirim_yuzdesi" class="form-control" required min="0" max="100"
                                   value="<?php echo $duzenlenecek_sponsorluk['indirim_yuzdesi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" name="baslangic_tarihi" class="form-control" required
                                   value="<?php echo $duzenlenecek_sponsorluk ? date('Y-m-d', strtotime($duzenlenecek_sponsorluk['baslangic_tarihi'])) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bitiş Tarihi (Boş bırakılırsa süresiz)</label>
                            <input type="date" name="bitis_tarihi" class="form-control"
                                   value="<?php echo $duzenlenecek_sponsorluk && $duzenlenecek_sponsorluk['bitis_tarihi'] ? date('Y-m-d', strtotime($duzenlenecek_sponsorluk['bitis_tarihi'])) : ''; ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"><?php echo $duzenlenecek_sponsorluk['aciklama'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=sponsorluklar" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>