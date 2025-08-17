<?php
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!isset($_SESSION['personel_id']) || $_SESSION['rol'] !== 'yonetici') {
    header('Location: dashboard.php');
    exit;
}

$success_message = '';
$error_message = '';

// Maaş bilgisi ekleme/güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'maas_kaydet') {
            $personel_id = $_POST['personel_id'];
            $brut_maas = $_POST['brut_maas'];
            $prim_yuzdesi = $_POST['prim_yuzdesi'] ?? 0;
            $sabit_prim = $_POST['sabit_prim'] ?? 0;
            $yemek_yardimi = $_POST['yemek_yardimi'] ?? 0;
            $banka_adi = $_POST['banka_adi'] ?? '';
            $iban = $_POST['iban'] ?? '';
            $baslangic_tarihi = $_POST['baslangic_tarihi'];
            
            // Önce mevcut aktif kaydı pasif yap
            $stmt = $pdo->prepare("UPDATE personel_maas_bilgileri SET aktif = 0 WHERE personel_id = ? AND aktif = 1");
            $stmt->execute([$personel_id]);
            
            // Yeni maaş bilgisi ekle
            $stmt = $pdo->prepare("
                INSERT INTO personel_maas_bilgileri (
                    personel_id, brut_maas, prim_yuzdesi, sabit_prim, 
                    yemek_yardimi, banka_adi, iban, baslangic_tarihi, aktif
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $personel_id, $brut_maas, $prim_yuzdesi, $sabit_prim,
                $yemek_yardimi, $banka_adi, $iban, $baslangic_tarihi
            ]);
            
            $success_message = "Maaş bilgisi başarıyla kaydedildi.";
        }
        
    } catch (Exception $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}

// Personelleri getir
$stmt = $pdo->query("
    SELECT p.*, 
           pmb.brut_maas, pmb.prim_yuzdesi, pmb.sabit_prim, 
           pmb.yemek_yardimi, pmb.banka_adi, pmb.iban,
           pmb.baslangic_tarihi as maas_baslangic
    FROM personel p
    LEFT JOIN personel_maas_bilgileri pmb ON p.id = pmb.personel_id AND pmb.aktif = 1
    WHERE p.aktif = 1
    ORDER BY p.ad, p.soyad
");
$personeller = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Maaş istatistikleri
$maas_istatistik = $pdo->query("
    SELECT 
        COUNT(*) as toplam_personel,
        COUNT(pmb.id) as maas_tanimli,
        AVG(pmb.brut_maas) as ortalama_maas,
        SUM(pmb.brut_maas) as toplam_maas
    FROM personel p
    LEFT JOIN personel_maas_bilgileri pmb ON p.id = pmb.personel_id AND pmb.aktif = 1
    WHERE p.aktif = 1
")->fetch(PDO::FETCH_ASSOC);

$maas_tanimli = $maas_istatistik['maas_tanimli'];
$toplam_personel = $maas_istatistik['toplam_personel'];
$tanimlanma_orani = $toplam_personel > 0 ? ($maas_tanimli / $toplam_personel) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Personel Maaş Ayarları";
    include "partials/title-meta.php";
    ?>
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
</head>
<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "İnsan Kaynakları";
                $title = "Personel Maaş Ayarları";
                include "partials/page-title.php";
                ?>

                <!-- Mesajlar -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Uyarı Kartı -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h5><i class="mdi mdi-information me-2"></i>Maaş Ayarları Hakkında</h5>
                            <p class="mb-2">Bordro hesaplamalarının doğru çalışması için her personelin maaş bilgilerini girmeniz gerekir.</p>
                            <ul class="mb-0">
                                <li><strong>Brüt Maaş:</strong> Vergi ve kesintiler öncesi maaş tutarı</li>
                                <li><strong>Prim Yüzdesi:</strong> Satış primlerinde kullanılacak yüzde oranı</li>
                                <li><strong>Sabit Prim:</strong> Aylık sabit prim tutarı</li>
                                <li><strong>Yemek Yardımı:</strong> Aylık yemek yardımı tutarı</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- İstatistik Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>Toplam Personel</h5>
                                <h3><?php echo $toplam_personel; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>Maaş Tanımlı</h5>
                                <h3><?php echo $maas_tanimli; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>Tamamlanma Oranı</h5>
                                <h3><?php echo number_format($tanimlanma_orani, 1); ?>%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5>Aylık Toplam</h5>
                                <h3><?php echo number_format($maas_istatistik['toplam_maas'] ?? 0, 0, ',', '.'); ?> ₺</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personel Maaş Listesi -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="mdi mdi-account-cash me-2"></i>Personel Maaş Bilgileri
                                </h5>
                                <div class="progress" style="width: 200px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $tanimlanma_orani; ?>%">
                                        <?php echo number_format($tanimlanma_orani, 1); ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Personel</th>
                                                <th>Brüt Maaş</th>
                                                <th>Prim %</th>
                                                <th>Sabit Prim</th>
                                                <th>Yemek Yardımı</th>
                                                <th>Banka</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($personeller as $personel): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="assets/images/users/avatar-1.jpg" 
                                                                 alt="avatar" class="rounded-circle me-2" width="32">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($personel['ad'] . ' ' . $personel['soyad']); ?></h6>
                                                                <small class="text-muted"><?php echo htmlspecialchars($personel['sicil_no']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($personel['brut_maas']): ?>
                                                            <span class="fw-bold text-success">
                                                                <?php echo number_format($personel['brut_maas'], 2); ?> ₺
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-danger fw-bold">Tanımlanmamış</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $personel['prim_yuzdesi'] ? $personel['prim_yuzdesi'] . '%' : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $personel['sabit_prim'] ? number_format($personel['sabit_prim'], 2) . ' ₺' : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $personel['yemek_yardimi'] ? number_format($personel['yemek_yardimi'], 2) . ' ₺' : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($personel['banka_adi']): ?>
                                                            <small><?php echo htmlspecialchars($personel['banka_adi']); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($personel['brut_maas']): ?>
                                                            <span class="badge bg-success">Tanımlı</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Eksik</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                onclick="maasAyarla('<?php echo $personel['id']; ?>')">
                                                            <i class="mdi mdi-pencil"></i> <?php echo $personel['brut_maas'] ? 'Düzenle' : 'Tanımla'; ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Maaş Ayarlama Modal -->
        <div class="modal fade" id="maasModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="maas_kaydet">
                        <input type="hidden" name="personel_id" id="modal_personel_id">
                        
                        <div class="modal-header">
                            <h5 class="modal-title">Maaş Bilgileri Ayarlama</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Brüt Maaş <span class="text-danger">*</span></label>
                                        <input type="number" name="brut_maas" id="modal_brut_maas" 
                                               class="form-control" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prim Yüzdesi (%)</label>
                                        <input type="number" name="prim_yuzdesi" id="modal_prim_yuzdesi" 
                                               class="form-control" step="0.1" min="0" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Sabit Prim (₺)</label>
                                        <input type="number" name="sabit_prim" id="modal_sabit_prim" 
                                               class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Yemek Yardımı (₺)</label>
                                        <input type="number" name="yemek_yardimi" id="modal_yemek_yardimi" 
                                               class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Banka Adı</label>
                                        <select name="banka_adi" id="modal_banka_adi" class="form-select">
                                            <option value="">Banka Seçin</option>
                                            <option value="Ziraat Bankası">Ziraat Bankası</option>
                                            <option value="Garanti BBVA">Garanti BBVA</option>
                                            <option value="İş Bankası">İş Bankası</option>
                                            <option value="Akbank">Akbank</option>
                                            <option value="Yapı Kredi">Yapı Kredi</option>
                                            <option value="Halkbank">Halkbank</option>
                                            <option value="VakıfBank">VakıfBank</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">IBAN</label>
                                        <input type="text" name="iban" id="modal_iban" 
                                               class="form-control" maxlength="32" 
                                               placeholder="TR00 0000 0000 0000 0000 0000 00">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Geçerlilik Başlangıç Tarihi <span class="text-danger">*</span></label>
                                        <input type="date" name="baslangic_tarihi" id="modal_baslangic_tarihi" 
                                               class="form-control" required value="<?php echo date('Y-m-01'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check me-1"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script>
        // Maaş ayarlama modalını aç
        function maasAyarla(personelId) {
            $('#modal_personel_id').val(personelId);
            
            // Mevcut bilgileri al ve modal'a doldur
            const row = $(`button[onclick="maasAyarla('${personelId}')"]`).closest('tr');
            const maasText = row.find('td:nth-child(2)').text().trim();
            
            // Eğer maaş tanımlıysa mevcut bilgileri doldur
            if (!maasText.includes('Tanımlanmamış')) {
                // AJAX ile mevcut bilgileri getir
                $.get('ajax/get_personel_maas.php', {personel_id: personelId}, function(data) {
                    if (data.success) {
                        $('#modal_brut_maas').val(data.maas.brut_maas);
                        $('#modal_prim_yuzdesi').val(data.maas.prim_yuzdesi);
                        $('#modal_sabit_prim').val(data.maas.sabit_prim);
                        $('#modal_yemek_yardimi').val(data.maas.yemek_yardimi);
                        $('#modal_banka_adi').val(data.maas.banka_adi);
                        $('#modal_iban').val(data.maas.iban);
                    }
                }, 'json');
            } else {
                // Formu temizle
                $('#maasModal form')[0].reset();
                $('#modal_personel_id').val(personelId);
                $('#modal_baslangic_tarihi').val('<?php echo date('Y-m-01'); ?>');
            }
            
            $('#maasModal').modal('show');
        }

        // IBAN formatla
        $('#modal_iban').on('input', function() {
            let value = $(this).val().replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim();
            if (value.length > 32) value = value.substring(0, 32);
            $(this).val(value.toUpperCase());
        });
        </script>
    </div>
</body>
</html>