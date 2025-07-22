<?php
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'satis_ekle':
                try {
                    $sonuc = satisEkle(
                        $_POST['danisan_id'],
                        $_POST['hizmet_paketi_id'],
                        $_POST['personel_id'],
                        $_POST['sponsorluk_id'] ?: null
                    );
                    if ($sonuc) {
                        $basari = "Satış başarıyla kaydedildi.";
                        header("Location: ?page=satislar");
                        exit;
                    }
                } catch(Exception $e) {
                    $hata = "Satış kaydedilirken bir hata oluştu: " . $e->getMessage();
                }
                break;
        }
    }
}

// Aktif paketleri al
$aktif_paketler = array_filter($hizmetPaketleri, function($p) {
    return $p['aktif'];
});

// Aktif sponsorlukları al
$aktif_sponsorluklar = array_filter($sponsorluklar, function($s) {
    return $s['aktif'];
});

// Satış personelini al
$satis_personeli = getSatisPersoneli();

?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <!-- Satış Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Satışlar</h5>
                <a href="?page=satislar&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Satış
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Danışan</th>
                                <th>Paket</th>
                                <th>Personel</th>
                                <th>Tutar</th>
                                <th>İndirim</th>
                                <th>Net Tutar</th>
                                <th>Son Kullanma</th>
                                <th>Sponsorluk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $satislar = getSatislar();
                            foreach ($satislar as $satis): 
                            ?>
                            <tr>
                                <td><?php echo $satis['danisan_adi']; ?></td>
                                <td><?php echo $satis['paket_adi']; ?></td>
                                <td><?php echo $satis['personel_adi']; ?></td>
                                <td><?php echo formatPrice($satis['birim_fiyat']); ?></td>
                                <td><?php echo formatPrice($satis['indirim_tutari']); ?></td>
                                <td><?php echo formatPrice($satis['toplam_tutar']); ?></td>
                                <td><?php echo formatDate($satis['son_kullanma_tarihi']); ?></td>
                                <td><?php echo $satis['sponsorluk_adi'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Satış Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Yeni Satış</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" value="satis_ekle">

                    <div class="row">
                        <div class="col-md-6 mb-3">
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hizmet Paketi</label>
                            <select name="hizmet_paketi_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($aktif_paketler as $paket): ?>
                                    <option value="<?php echo $paket['id']; ?>">
                                        <?php echo $paket['ad'] . ' (' . formatPrice($paket['fiyat']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Satış Personeli</label>
                            <select name="personel_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($satis_personeli as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo $p['ad'] . ' ' . $p['soyad']; ?>
                                        </option>
                                  
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sponsorluk (Varsa)</label>
                            <select name="sponsorluk_id" class="form-select">
                                <option value="">Seçiniz</option>
                                <?php foreach ($aktif_sponsorluklar as $sponsorluk): ?>
                                    <option value="<?php echo $sponsorluk['id']; ?>">
                                        <?php echo $sponsorluk['ad'] . ' (%' . $sponsorluk['indirim_yuzdesi'] . ' indirim)'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Satışı Tamamla</button>
                        <a href="?page=satislar" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>