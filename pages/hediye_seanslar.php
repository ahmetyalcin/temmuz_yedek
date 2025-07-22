<?php
$danisan_id = $_GET['danisan_id'] ?? null;
$hata = '';
$basari = '';

if (isset($_GET['action']) && $_GET['action'] == 'kullan' && isset($_GET['id'])) {
    $sonuc = hediyeSeansKullan($_GET['id']);
    if ($sonuc) {
        $basari = "Hediye seans başarıyla kullanıldı olarak işaretlendi.";
    } else {
        $hata = "Hediye seans kullanıldı olarak işaretlenirken bir hata oluştu.";
    }
}

// Hediye seansları al
$hediye_seanslar = getHediyeSeanslar($danisan_id);
?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Hediye Seanslar</h5>
            <?php if ($danisan_id): ?>
                <a href="?page=danisanlar" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Danışanlara Dön
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!$danisan_id): ?>
            <div class="mb-4">
                <form method="GET" action="" class="row g-3">
                    <input type="hidden" name="page" value="hediye_seanslar">
                    <div class="col-md-4">
                        <select name="danisan_id" class="form-select">
                            <option value="">Tüm Danışanlar</option>
                            <?php foreach ($danisanlar as $danisan): ?>
                                <option value="<?php echo $danisan['id']; ?>"
                                        <?php echo ($danisan_id == $danisan['id']) ? 'selected' : ''; ?>>
                                    <?php echo $danisan['ad'] . ' ' . $danisan['soyad']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Danışan</th>
                            <th>Seans Türü</th>
                            <th>Miktar</th>
                            <th>Son Kullanma</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hediye_seanslar as $seans): ?>
                        <tr>
                            <td><?php echo $seans['danisan_adi']; ?></td>
                            <td><?php echo $seans['seans_turu_adi']; ?></td>
                            <td><?php echo $seans['miktar']; ?></td>
                            <td><?php echo formatDate($seans['son_kullanma_tarihi']); ?></td>
                            <td>
                                <?php if ($seans['kullanildi']): ?>
                                    <span class="badge bg-success">Kullanıldı</span>
                                <?php else: ?>
                                    <?php if (strtotime($seans['son_kullanma_tarihi']) < time()): ?>
                                        <span class="badge bg-danger">Süresi Doldu</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Aktif</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$seans['kullanildi'] && strtotime($seans['son_kullanma_tarihi']) >= time()): ?>
                                    <button type="button" class="btn btn-sm btn-success"
                                            onclick="if(confirm('Bu hediye seansı kullanıldı olarak işaretlemek istediğinizden emin misiniz?'))
                                            window.location.href='?page=hediye_seanslar&action=kullan&id=<?php echo $seans['id']; ?><?php echo $danisan_id ? "&danisan_id=".$danisan_id : ""; ?>'">
                                        <i class="bx bx-check"></i> Kullan
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($hediye_seanslar)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Hediye seans bulunamadı.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>