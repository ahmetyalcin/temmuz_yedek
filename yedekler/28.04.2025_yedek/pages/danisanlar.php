<?php
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'danisan_ekle':
                $sonuc = danisanEkle(
                    $_POST['ad'],
                    $_POST['soyad'],
                    $_POST['email'],
                    $_POST['telefon'],
                    $_POST['adres'],
                    $_POST['yas'],
                    $_POST['meslek']
                );
                if ($sonuc) {
                    $basari = "Danışan başarıyla eklendi.";
                    header("Location: ?page=danisanlar");
                    exit;
                } else {
                    $hata = "Danışan eklenirken bir hata oluştu.";
                }
                break;

            case 'danisan_guncelle':
                $sonuc = danisanGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['soyad'],
                    $_POST['email'],
                    $_POST['telefon'],
                    $_POST['adres'],
                    $_POST['yas'],
                    $_POST['meslek'],
                    $_POST['uyelik_turu_id']
                );
                if ($sonuc) {
                    $basari = "Danışan başarıyla güncellendi.";
                    header("Location: ?page=danisanlar");
                    exit;
                } else {
                    $hata = "Danışan güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için danışan bilgilerini al
$duzenlenecek_danisan = null;
if ($action == 'edit' && isset($_GET['id'])) {
    foreach ($danisanlar as $danisan) {
        if ($danisan['id'] == $_GET['id']) {
            $duzenlenecek_danisan = $danisan;
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
        <!-- Danışan Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danışanlar</h5>
                <a href="?page=danisanlar&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Danışan
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
                                <th>Yaş</th>
                                <th>Meslek</th>
                                <th>Üyelik</th>
                                <th>Toplam Seans</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($danisanlar as $danisan): ?>
                            <tr>
                                <td><?php echo $danisan['ad'] . ' ' . $danisan['soyad']; ?></td>
                                <td><?php echo $danisan['email']; ?></td>
                                <td><?php echo $danisan['telefon']; ?></td>
                                <td><?php echo $danisan['yas']; ?></td>
                                <td><?php echo $danisan['meslek']; ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $danisan['uyelik_adi'] ?? 'Standart'; ?>
                                    </span>
                                </td>
                                <td><?php echo $danisan['toplam_seans_sayisi']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">

                                    <a href="?page=danisan-detay&id=<?php echo $danisan['id']; ?>" class="btn btn-info">
                                     <i class="bx bx-detail"></i> Detay
                                    </a>

                                        <a href="?page=danisanlar&action=edit&id=<?php echo $danisan['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <a href="?page=randevular&danisan_id=<?php echo $danisan['id']; ?>" 
                                           class="btn btn-info">
                                            <i class="bx bx-calendar"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger"
                                                onclick="if(confirm('Bu danışanı silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisanlar&action=delete&id=<?php echo $danisan['id']; ?>'">
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
        <!-- Danışan Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Danışan Düzenle' : 'Yeni Danışan Ekle'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'danisan_guncelle' : 'danisan_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_danisan['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Soyad</label>
                            <input type="text" name="soyad" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['soyad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['email'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="telefon" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['telefon'] ?? ''; ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="3"><?php echo $duzenlenecek_danisan['adres'] ?? ''; ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yaş</label>
                            <input type="number" name="yas" class="form-control" required min="0"
                                   value="<?php echo $duzenlenecek_danisan['yas'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Meslek</label>
                            <input type="text" name="meslek" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['meslek'] ?? ''; ?>">
                        </div>
                        <?php if ($action == 'edit'): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Üyelik Türü</label>
                            <select name="uyelik_turu_id" class="form-select">
                                <option value="">Seçiniz</option>
                                <?php foreach ($uyelikTurleri as $uyelik): ?>
                                    <option value="<?php echo $uyelik['id']; ?>"
                                            <?php echo ($duzenlenecek_danisan['uyelik_turu_id'] == $uyelik['id']) ? 'selected' : ''; ?>>
                                        <?php echo $uyelik['ad']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=danisanlar" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>