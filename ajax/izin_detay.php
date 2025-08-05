<?php
session_start();
require_once '../con/db.php';

// Giriş kontrolü
if (!isset($_SESSION['personel_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Yetkisiz erişim!</div>';
    exit;
}

$izin_id = $_GET['id'] ?? '';

if (empty($izin_id)) {
    echo '<div class="alert alert-danger">İzin ID bulunamadı!</div>';
    exit;
}

try {
    // İzin detaylarını çek
    $stmt = $pdo->prepare("
        SELECT 
            pi.*,
            p.ad as personel_ad,
            p.soyad as personel_soyad,
            p.email as personel_email,
            p.telefon as personel_telefon,
            it.ad as izin_turu_ad,
            it.renk_kodu,
            it.ucretli,
            talep_eden.ad as talep_eden_ad,
            talep_eden.soyad as talep_eden_soyad,
            onaylayan.ad as onaylayan_ad,
            onaylayan.soyad as onaylayan_soyad
        FROM personel_izinleri pi
        LEFT JOIN personel p ON pi.personel_id = p.id
        LEFT JOIN izin_turleri it ON pi.izin_turu_id = it.id
        LEFT JOIN personel talep_eden ON pi.talep_eden_id = talep_eden.id
        LEFT JOIN personel onaylayan ON pi.onaylayan_id = onaylayan.id
        WHERE pi.id = ? AND pi.aktif = 1
    ");
    
    $stmt->execute([$izin_id]);
    $izin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$izin) {
        echo '<div class="alert alert-danger">İzin kaydı bulunamadı!</div>';
        exit;
    }
    
    // Durum renkleri
    $durum_renkler = [
        'beklemede' => 'warning',
        'onaylandi' => 'success', 
        'reddedildi' => 'danger',
        'iptal_edildi' => 'secondary'
    ];
    
    $durum_metinler = [
        'beklemede' => 'Beklemede',
        'onaylandi' => 'Onaylandı',
        'reddedildi' => 'Reddedildi', 
        'iptal_edildi' => 'İptal Edildi'
    ];
    
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <!-- İzin Bilgileri -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="mdi mdi-calendar-check me-2"></i>İzin Bilgileri
                    </h6>
                    <span class="badge bg-<?php echo $durum_renkler[$izin['durum']]; ?> fs-6">
                        <?php echo $durum_metinler[$izin['durum']]; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="fw-bold" width="150">Personel:</td>
                                    <td><?php echo htmlspecialchars($izin['personel_ad'] . ' ' . $izin['personel_soyad']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">İzin Türü:</td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $izin['renk_kodu']; ?>">
                                            <?php echo htmlspecialchars($izin['izin_turu_ad']); ?>
                                        </span>
                                        <?php if ($izin['ucretli']): ?>
                                            <small class="text-success ms-2">(Ücretli)</small>
                                        <?php else: ?>
                                            <small class="text-warning ms-2">(Ücretsiz)</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Başlangıç:</td>
                                    <td><?php echo date('d.m.Y', strtotime($izin['baslangic_tarihi'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Bitiş:</td>
                                    <td><?php echo date('d.m.Y', strtotime($izin['bitis_tarihi'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Gün Sayısı:</td>
                                    <td><span class="badge bg-info"><?php echo $izin['gun_sayisi']; ?> gün</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="fw-bold" width="150">Talep Eden:</td>
                                    <td><?php echo htmlspecialchars($izin['talep_eden_ad'] . ' ' . $izin['talep_eden_soyad']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Talep Tarihi:</td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($izin['olusturma_tarihi'])); ?></td>
                                </tr>
                                <?php if ($izin['onaylayan_ad']): ?>
                                <tr>
                                    <td class="fw-bold">Onaylayan:</td>
                                    <td><?php echo htmlspecialchars($izin['onaylayan_ad'] . ' ' . $izin['onaylayan_soyad']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Onay Tarihi:</td>
                                    <td><?php echo $izin['onay_tarihi'] ? date('d.m.Y H:i', strtotime($izin['onay_tarihi'])) : '-'; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($izin['personel_email']): ?>
                                <tr>
                                    <td class="fw-bold">E-posta:</td>
                                    <td><?php echo htmlspecialchars($izin['personel_email']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($izin['personel_telefon']): ?>
                                <tr>
                                    <td class="fw-bold">Telefon:</td>
                                    <td><?php echo htmlspecialchars($izin['personel_telefon']); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Açıklama -->
            <?php if (!empty($izin['aciklama'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-text-box me-2"></i>Açıklama
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($izin['aciklama'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Red Nedeni -->
            <?php if ($izin['durum'] == 'reddedildi' && !empty($izin['red_nedeni'])): ?>
            <div class="card mb-3">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="mdi mdi-close-circle me-2"></i>Red Nedeni
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-danger"><?php echo nl2br(htmlspecialchars($izin['red_nedeni'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Belge -->
            <?php if (!empty($izin['belge_yolu'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-file-document me-2"></i>Belge
                    </h6>
                </div>
                <div class="card-body">
                    <a href="<?php echo htmlspecialchars($izin['belge_yolu']); ?>" 
                       target="_blank" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-download me-1"></i>Belgeyi İndir
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- İşlem Butonları -->
            <?php if ($izin['durum'] == 'beklemede'): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-cog me-2"></i>İşlemler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button type="button" 
                                class="btn btn-success btn-sm" 
                                onclick="izinOnaylaRed('<?php echo $izin['id']; ?>', 'onayla')">
                            <i class="mdi mdi-check me-1"></i>Onayla
                        </button>
                        <button type="button" 
                                class="btn btn-danger btn-sm" 
                                onclick="izinOnaylaRed('<?php echo $izin['id']; ?>', 'reddet')">
                            <i class="mdi mdi-close me-1"></i>Reddet
                        </button>
                        <button type="button" 
                                class="btn btn-warning btn-sm" 
                                onclick="izinDuzenle('<?php echo $izin['id']; ?>')">
                            <i class="mdi mdi-pencil me-1"></i>Düzenle
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    
} catch (Exception $e) {
    error_log("İzin detay hatası: " . $e->getMessage());
    echo '<div class="alert alert-danger">Bir hata oluştu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>