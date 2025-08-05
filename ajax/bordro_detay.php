<?php
session_start();
require_once '../con/db.php';

/*
// Giriş kontrolü
if (!isset($_SESSION['personel_id'])) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Yetkisiz erişim!</div>';
    exit;
}
*/
$bordro_id = $_GET['id'] ?? '';

if (empty($bordro_id)) {
    echo '<div class="alert alert-danger">Bordro ID bulunamadı!</div>';
    exit;
}

try {
    // Bordro detaylarını çek
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            p.ad as personel_ad,
            p.soyad as personel_soyad,
            p.sicil_no,
            p.email as personel_email,
            p.telefon as personel_telefon,
            pmb.banka_adi,
            pmb.iban,
            olusturan.ad as olusturan_ad,
            olusturan.soyad as olusturan_soyad,
            onaylayan.ad as onaylayan_ad,
            onaylayan.soyad as onaylayan_soyad
        FROM bordrolar b
        LEFT JOIN personel p ON b.personel_id = p.id
        LEFT JOIN personel_maas_bilgileri pmb ON p.id = pmb.personel_id AND pmb.aktif = 1
        LEFT JOIN personel olusturan ON b.olusturan_id = olusturan.id
        LEFT JOIN personel onaylayan ON b.onaylayan_id = onaylayan.id
        WHERE b.id = ? AND b.aktif = 1
    ");
    
    $stmt->execute([$bordro_id]);
    $bordro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bordro) {
        echo '<div class="alert alert-danger">Bordro kaydı bulunamadı!</div>';
        exit;
    }
    
    // Bordro detaylarını çek (ek hakediş/kesintiler)
    $stmt = $pdo->prepare("
        SELECT * FROM bordro_detaylari 
        WHERE bordro_id = ? 
        ORDER BY kalem_tipi, kalem_adi
    ");
    $stmt->execute([$bordro_id]);
    $detaylar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Durum renkleri
    $durum_renkler = [
        'taslak' => 'warning',
        'onaylandi' => 'success', 
        'odendi' => 'info'
    ];
    
    $durum_metinler = [
        'taslak' => 'Taslak',
        'onaylandi' => 'Onaylandı',
        'odendi' => 'Ödendi'
    ];
    
    // Ay adları
    $ay_adlari = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <!-- Bordro Bilgileri -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="mdi mdi-file-document me-2"></i>Bordro Bilgileri
                    </h6>
                    <span class="badge bg-<?php echo $durum_renkler[$bordro['durum']]; ?> fs-6">
                        <?php echo $durum_metinler[$bordro['durum']]; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="fw-bold" width="150">Personel:</td>
                                    <td><?php echo htmlspecialchars($bordro['personel_ad'] . ' ' . $bordro['personel_soyad']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Sicil No:</td>
                                    <td><?php echo htmlspecialchars($bordro['sicil_no']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Bordro Dönemi:</td>
                                    <td><?php echo $ay_adlari[$bordro['ay']] . ' ' . $bordro['yil']; ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Çalışılan Gün:</td>
                                    <td><?php echo $bordro['calisan_gun_sayisi']; ?> gün</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">İzin Günü:</td>
                                    <td>
                                        <?php echo $bordro['izin_gun_sayisi']; ?> gün
                                        <?php if ($bordro['ucretli_izin_gun'] > 0 || $bordro['ucretsiz_izin_gun'] > 0): ?>
                                            <br><small class="text-muted">
                                                (Ücretli: <?php echo $bordro['ucretli_izin_gun']; ?>, Ücretsiz: <?php echo $bordro['ucretsiz_izin_gun']; ?>)
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="fw-bold" width="150">Oluşturan:</td>
                                    <td><?php echo htmlspecialchars($bordro['olusturan_ad'] . ' ' . $bordro['olusturan_soyad']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Oluşturma:</td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($bordro['olusturma_tarihi'])); ?></td>
                                </tr>
                                <?php if ($bordro['onaylayan_ad']): ?>
                                <tr>
                                    <td class="fw-bold">Onaylayan:</td>
                                    <td><?php echo htmlspecialchars($bordro['onaylayan_ad'] . ' ' . $bordro['onaylayan_soyad']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Onay Tarihi:</td>
                                    <td><?php echo $bordro['onay_tarihi'] ? date('d.m.Y H:i', strtotime($bordro['onay_tarihi'])) : '-'; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['odeme_tarihi']): ?>
                                <tr>
                                    <td class="fw-bold">Ödeme Tarihi:</td>
                                    <td><?php echo date('d.m.Y', strtotime($bordro['odeme_tarihi'])); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Maaş Hesapları -->
            <div class="row">
                <div class="col-md-6">
                    <!-- Hakediş Bilgileri -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="mdi mdi-plus-circle me-2"></i>Hakediş
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td>Brüt Maaş:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['brut_maas'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php if ($bordro['prim_tutari'] > 0): ?>
                                <tr>
                                    <td>Prim:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['prim_tutari'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['ek_odemeler'] > 0): ?>
                                <tr>
                                    <td>Ek Ödemeler:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['ek_odemeler'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['yemek_yardimi'] > 0): ?>
                                <tr>
                                    <td>Yemek Yardımı:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['yemek_yardimi'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                
                                <!-- Ek hakediş kalemleri -->
                                <?php 
                                $ek_hakedisler = array_filter($detaylar, function($d) { return $d['kalem_tipi'] == 'hakediş'; });
                                foreach ($ek_hakedisler as $hakediş): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($hakediş['kalem_adi']); ?>:</td>
                                    <td class="text-end"><strong><?php echo number_format($hakediş['tutar'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <tr class="table-success">
                                    <td><strong>BRÜT TOPLAM:</strong></td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['brut_toplam'], 2); ?> ₺</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Kesinti Bilgileri -->
                    <div class="card mb-3">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="mdi mdi-minus-circle me-2"></i>Kesintiler
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <?php if ($bordro['sgk_isci_payi'] > 0): ?>
                                <tr>
                                    <td>SGK İşçi Payı:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['sgk_isci_payi'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['issizlik_isci_payi'] > 0): ?>
                                <tr>
                                    <td>İşsizlik İşçi Payı:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['issizlik_isci_payi'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['gelir_vergisi'] > 0): ?>
                                <tr>
                                    <td>Gelir Vergisi:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['gelir_vergisi'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['damga_vergisi'] > 0): ?>
                                <tr>
                                    <td>Damga Vergisi:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['damga_vergisi'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['avans'] > 0): ?>
                                <tr>
                                    <td>Avans:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['avans'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($bordro['diger_kesintiler'] > 0): ?>
                                <tr>
                                    <td>Diğer Kesintiler:</td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['diger_kesintiler'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endif; ?>
                                
                                <!-- Ek kesinti kalemleri -->
                                <?php 
                                $ek_kesintiler = array_filter($detaylar, function($d) { return $d['kalem_tipi'] == 'kesinti'; });
                                foreach ($ek_kesintiler as $kesinti): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($kesinti['kalem_adi']); ?>:</td>
                                    <td class="text-end"><strong><?php echo number_format($kesinti['tutar'], 2); ?> ₺</strong></td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <tr class="table-danger">
                                    <td><strong>TOPLAM KESİNTİ:</strong></td>
                                    <td class="text-end"><strong><?php echo number_format($bordro['toplam_kesinti'], 2); ?> ₺</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Net Ödeme -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="mdi mdi-cash me-2"></i>Net Ödeme
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-5">Brüt Toplam:</span>
                                <span class="fs-5 text-success fw-bold"><?php echo number_format($bordro['brut_toplam'], 2); ?> ₺</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-5">Toplam Kesinti:</span>
                                <span class="fs-5 text-danger fw-bold">-<?php echo number_format($bordro['toplam_kesinti'], 2); ?> ₺</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-4 fw-bold">NET ÖDEME:</span>
                                <span class="fs-4 fw-bold text-primary"><?php echo number_format($bordro['net_odeme'], 2); ?> ₺</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php if ($bordro['banka_adi']): ?>
                                <div class="bg-light p-3 rounded">
                                    <h6 class="mb-2">Banka Bilgileri</h6>
                                    <small class="text-muted">Banka:</small><br>
                                    <strong><?php echo htmlspecialchars($bordro['banka_adi']); ?></strong><br>
                                    
                                    <?php if ($bordro['iban']): ?>
                                        <small class="text-muted">IBAN:</small><br>
                                        <span class="font-monospace"><?php echo htmlspecialchars($bordro['iban']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notlar -->
            <?php if (!empty($bordro['notlar'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-note-text me-2"></i>Notlar
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($bordro['notlar'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- İşlem Butonları -->
            <?php if ($_SESSION['rol'] === 'yonetici'): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-cog me-2"></i>İşlemler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <?php if ($bordro['durum'] === 'taslak'): ?>
                            <button type="button" 
                                    class="btn btn-success btn-sm" 
                                    onclick="bordroOnayla('<?php echo $bordro['id']; ?>')">
                                <i class="mdi mdi-check me-1"></i>Onayla
                            </button>
                            <button type="button" 
                                    class="btn btn-warning btn-sm" 
                                    onclick="bordroDuzenle('<?php echo $bordro['id']; ?>')">
                                <i class="mdi mdi-pencil me-1"></i>Düzenle
                            </button>
                        <?php elseif ($bordro['durum'] === 'onaylandi'): ?>
                            <button type="button" 
                                    class="btn btn-info btn-sm" 
                                    onclick="bordroOde('<?php echo $bordro['id']; ?>')">
                                <i class="mdi mdi-cash me-1"></i>Ödeme Yap
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="btn btn-primary btn-sm" 
                                onclick="bordroPrint('<?php echo $bordro['id']; ?>')">
                            <i class="mdi mdi-printer me-1"></i>Yazdır
                        </button>
                        
                        <button type="button" 
                                class="btn btn-secondary btn-sm" 
                                onclick="bordroEmail('<?php echo $bordro['id']; ?>')">
                            <i class="mdi mdi-email me-1"></i>E-posta Gönder
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    
} catch (Exception $e) {
    error_log("Bordro detay hatası: " . $e->getMessage());
    echo '<div class="alert alert-danger">Bir hata oluştu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>