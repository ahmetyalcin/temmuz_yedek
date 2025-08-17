<?php
// ajax/giris_cikis_duzenle.php - Giriş-çıkış kaydı düzenleme
session_start();
require_once '../con/db.php';

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Kayıt ID\'si gerekli</div>';
    exit;
}

try {
    // Kayıt bilgilerini getir
    $sql = "SELECT pgc.*, 
                   CONCAT(p.ad, ' ', p.soyad) as personel_adi
            FROM personel_giris_cikis pgc
            JOIN personel p ON p.id = pgc.personel_id
            WHERE pgc.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $kayit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kayit) {
        echo '<div class="alert alert-danger">Kayıt bulunamadı</div>';
        exit;
    }
    
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<form id="duzenleForm">
    <input type="hidden" name="action" value="kayit_duzenle">
    <input type="hidden" name="kayit_id" value="<?= $kayit['id'] ?>">
    <input type="hidden" name="ajax" value="1">
    
    <div class="mb-3">
        <label class="form-label">Personel</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($kayit['personel_adi']) ?>" readonly>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Tarih</label>
        <input type="text" class="form-control" value="<?= date('d.m.Y', strtotime($kayit['tarih'])) ?>" readonly>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Giriş Saati</label>
            <input type="time" name="giris_saati" class="form-control" 
                   value="<?= $kayit['giris_saati'] ? date('H:i', strtotime($kayit['giris_saati'])) : '' ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Çıkış Saati</label>
            <input type="time" name="cikis_saati" class="form-control" 
                   value="<?= $kayit['cikis_saati'] ? date('H:i', strtotime($kayit['cikis_saati'])) : '' ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Açıklama</label>
        <textarea name="aciklama" class="form-control" rows="3"><?= htmlspecialchars($kayit['aciklama']) ?></textarea>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info">
                <small>
                    <strong>Mevcut Durum:</strong> <?= getDurumText($kayit['durum']) ?><br>
                    <strong>Toplam Süre:</strong> <?= number_format($kayit['toplam_calisma_saati'], 1) ?> saat
                </small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-warning">
                <small>
                    <strong>Uyarı:</strong> Saatler değiştirildiğinde mesai ve gecikme hesaplamaları otomatik güncellenecektir.
                </small>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-primary">Güncelle</button>
    </div>
</form>

<script>
$('#duzenleForm').submit(function(e) {
    e.preventDefault();
    
    const giris = $('input[name="giris_saati"]').val();
    const cikis = $('input[name="cikis_saati"]').val();
    
    if (giris && cikis && giris >= cikis) {
        showToast('Çıkış saati giriş saatinden sonra olmalıdır!', 'error');
        return false;
    }
    
    $.post('personel-giris-cikis.php', $(this).serialize(), function(response) {
        if (response.success) {
            showToast(response.message, 'success');
            $('#duzenleModal').modal('hide');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(response.message || 'Güncelleme başarısız', 'error');
        }
    }, 'json').fail(function() {
        showToast('Bir hata oluştu', 'error');
    });
});
</script>

<?php
function getDurumText($durum) {
    $durumlar = [
        'normal' => 'Normal',
        'gecikme' => 'Gecikme',
        'erken_cikis' => 'Erken Çıkış',
        'tam_gun_izin' => 'Tam Gün İzin',
        'yarim_gun_izin' => 'Yarım Gün İzin',
        'devamsizlik' => 'Devamsızlık'
    ];
    
    return $durumlar[$durum] ?? 'Bilinmiyor';
}
?>