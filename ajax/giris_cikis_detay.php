<?php
// ajax/giris_cikis_detay.php - Giriş-çıkış kayıt detayları
session_start();
require_once '../con/db.php';

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Kayıt ID\'si gerekli</div>';
    exit;
}

try {
    // Kayıt bilgilerini getir
    $sql = "SELECT pgc.*, 
                   CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                   p.sicil_no,
                   p.avatar,
                   p.rol,
                   p.telefon,
                   CONCAT(k.ad, ' ', k.soyad) as kaydeden_adi
            FROM personel_giris_cikis pgc
            JOIN personel p ON p.id = pgc.personel_id
            LEFT JOIN personel k ON k.id = pgc.kaydeden_id
            WHERE pgc.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $kayit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kayit) {
        echo '<div class="alert alert-danger">Kayıt bulunamadı</div>';
        exit;
    }
    
    // Aynı personelin son 5 gününü getir
    $sql = "SELECT tarih, giris_saati, cikis_saati, toplam_calisma_saati, durum
            FROM personel_giris_cikis 
            WHERE personel_id = ? AND tarih <= ? 
            ORDER BY tarih DESC 
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kayit['personel_id'], $kayit['tarih']]);
    $son_kayitlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="text-center mb-4">
            <img src="<?= $kayit['avatar'] ?: 'assets/images/default-avatar.png' ?>" 
                 class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
            <h5 class="mb-1"><?= htmlspecialchars($kayit['personel_adi']) ?></h5>
            <p class="text-muted mb-0">Sicil No: <?= htmlspecialchars($kayit['sicil_no']) ?></p>
            <p class="text-muted"><?= ucfirst($kayit['rol']) ?></p>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 class="text-primary mb-1">
                            <?= $kayit['giris_saati'] ? date('H:i', strtotime($kayit['giris_saati'])) : 'Giriş Yok' ?>
                        </h4>
                        <p class="mb-0">Giriş Saati</p>
                        <?php if ($kayit['gecikme_dakika'] > 0): ?>
                            <small class="text-warning">
                                <i class="fas fa-clock me-1"></i><?= $kayit['gecikme_dakika'] ?> dk gecikme
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h4 class="text-success mb-1">
                            <?= $kayit['cikis_saati'] ? date('H:i', strtotime($kayit['cikis_saati'])) : 'Çıkış Yok' ?>
                        </h4>
                        <p class="mb-0">Çıkış Saati</p>
                        <?php if ($kayit['erken_cikis_dakika'] > 0): ?>
                            <small class="text-info">
                                <i class="fas fa-clock me-1"></i><?= $kayit['erken_cikis_dakika'] ?> dk erken
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h5 class="text-primary mb-1"><?= number_format($kayit['toplam_calisma_saati'], 1) ?></h5>
                <p class="mb-0">Toplam Saat</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h5 class="text-success mb-1"><?= number_format($kayit['mesai_saati'], 1) ?></h5>
                <p class="mb-0">Mesai Saati</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h5 class="text-info mb-1">
                    <?php
                    $durum_badges = [
                        'normal' => '<span class="badge bg-success">Normal</span>',
                        'gecikme' => '<span class="badge bg-warning">Gecikme</span>',
                        'erken_cikis' => '<span class="badge bg-info">Erken Çıkış</span>',
                        'tam_gun_izin' => '<span class="badge bg-purple">Tam İzin</span>',
                        'yarim_gun_izin' => '<span class="badge bg-secondary">Yarım İzin</span>',
                        'devamsizlik' => '<span class="badge bg-danger">Devamsızlık</span>'
                    ];
                    echo $durum_badges[$kayit['durum']] ?? '<span class="badge bg-secondary">Bilinmiyor</span>';
                    ?>
                </h5>
                <p class="mb-0">Durum</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h5 class="text-warning mb-1"><?= ucfirst($kayit['kayit_tipi']) ?></h5>
                <p class="mb-0">Kayıt Tipi</p>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6 class="mb-3">Kayıt Bilgileri</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Tarih:</strong></td>
                <td><?= date('d.m.Y l', strtotime($kayit['tarih'])) ?></td>
            </tr>
            <tr>
                <td><strong>Kaydeden:</strong></td>
                <td><?= $kayit['kaydeden_adi'] ?: 'Sistem' ?></td>
            </tr>
            <tr>
                <td><strong>Kayıt Tarihi:</strong></td>
                <td><?= date('d.m.Y H:i', strtotime($kayit['olusturma_tarihi'])) ?></td>
            </tr>
            <tr>
                <td><strong>Son Güncelleme:</strong></td>
                <td><?= date('d.m.Y H:i', strtotime($kayit['guncelleme_tarihi'])) ?></td>
            </tr>
        </table>
        
        <?php if ($kayit['aciklama']): ?>
        <div class="mt-3">
            <h6>Açıklama</h6>
            <div class="alert alert-light">
                <?= nl2br(htmlspecialchars($kayit['aciklama'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h6 class="mb-3">Son 5 Gün Özet</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Giriş</th>
                        <th>Çıkış</th>
                        <th>Toplam</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($son_kayitlar as $sk): ?>
                    <tr>
                        <td><?= date('d.m', strtotime($sk['tarih'])) ?></td>
                        <td><?= $sk['giris_saati'] ? date('H:i', strtotime($sk['giris_saati'])) : '-' ?></td>
                        <td><?= $sk['cikis_saati'] ? date('H:i', strtotime($sk['cikis_saati'])) : '-' ?></td>
                        <td><?= number_format($sk['toplam_calisma_saati'], 1) ?>h</td>
                        <td>
                            <?php
                            $durum_colors = [
                                'normal' => 'success',
                                'gecikme' => 'warning',
                                'erken_cikis' => 'info',
                                'devamsizlik' => 'danger'
                            ];
                            $color = $durum_colors[$sk['durum']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= getDurumText($sk['durum']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-12">
        <h6 class="mb-3">Çalışma Süresi Analizi</h6>
        <div class="progress mb-2" style="height: 25px;">
            <?php
            $normal_saat = min(8, $kayit['toplam_calisma_saati']);
            $mesai_saat = max(0, $kayit['toplam_calisma_saati'] - 8);
            $normal_percentage = ($normal_saat / 12) * 100; // 12 saat maksimum gösterim
            $mesai_percentage = ($mesai_saat / 12) * 100;
            ?>
            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $normal_percentage ?>%">
                Normal: <?= number_format($normal_saat, 1) ?>h
            </div>
            <?php if ($mesai_saat > 0): ?>
            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $mesai_percentage ?>%">
                Mesai: <?= number_format($mesai_saat, 1) ?>h
            </div>
            <?php endif; ?>
        </div>
        <small class="text-muted">
            Normal çalışma: 8 saat | Mesai: <?= number_format($kayit['mesai_saati'], 1) ?> saat
            <?php if ($kayit['toplam_calisma_saati'] < 8): ?>
                | Eksik: <?= number_format(8 - $kayit['toplam_calisma_saati'], 1) ?> saat
            <?php endif; ?>
        </small>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
    <button type="button" class="btn btn-primary" onclick="duzenleKayit('<?= $kayit['id'] ?>')">
        <i class="fas fa-edit me-1"></i>Düzenle
    </button>
    <?php if ($kayit['telefon']): ?>
    <button type="button" class="btn btn-info" onclick="mesajGonder('<?= $kayit['personel_id'] ?>')">
        <i class="fas fa-sms me-1"></i>Mesaj Gönder
    </button>
    <?php endif; ?>
</div>

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