<?php
session_start();
require_once 'functions.php';

// Düzenleme modunu kontrol et
$edit_mode = false;
$edit_satis = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit'];
    
    // Satış verilerini getir
    $edit_satis = getSatisById($edit_id);
    if (!$edit_satis) {
        $error = "Düzenlenecek satış bulunamadı!";
        $edit_mode = false;
    }
}

// Verileri getir - SADECE bu fonksiyonu değiştirdim
function getDanisanlarTumFixed() {
    global $pdo;
    try {
        $sql = "SELECT id, ad, soyad, telefon, email 
                FROM danisanlar 
                WHERE aktif = 1 
                AND ad IS NOT NULL 
                AND ad != '' 
                AND TRIM(ad) != ''
                AND soyad IS NOT NULL 
                AND soyad != '' 
                AND TRIM(soyad) != ''
                ORDER BY ad, soyad";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Danışanları getirme hatası: " . $e->getMessage());
        return [];
    }
}

$danisanlar = getDanisanlarTumFixed(); // Sadece bu satırı değiştirdim
$paketler    = getSeansPaketleri();
$personel    = getSatisPersoneli();
$satis_turleri = getSatisTurleri();
$odeme_turleri = getOdemeTurleri();

// Login olan kullanıcının ID'sini al
$current_user_id = $_SESSION['user_id'] ?? null;

// ÇOKLU PAKET DESTEĞİ - Backend kısmını değiştirdim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // Çoklu paket seçimi kontrolü - YENİ
        $selectedPackages = !empty($_POST['selected_packages']) ? $_POST['selected_packages'] : [$_POST['hizmet_paketi_id']];
        
        if (empty($selectedPackages) || (count($selectedPackages) == 1 && empty($selectedPackages[0]))) {
            throw new Exception("En az bir paket seçilmelidir!");
        }

        // Tek paket mi çoklu paket mi?
        $isMultiPackage = count($selectedPackages) > 1;

        // Vade tarihi kontrolü
        $vade_tarihi = null;
        if (!empty($_POST['taksitler']) && is_array($_POST['taksitler'])) {
            $son_taksit = end($_POST['taksitler']);
            if (!empty($son_taksit['vade_tarihi'])) {
                $vade_tarihi = $son_taksit['vade_tarihi'];
            }
        }

        if ($isMultiPackage) {
            // ÇOKLU PAKET İŞLEMİ - YENİ
            $toplamPaketFiyati = 0;
            $paketBilgileri = [];
            
            foreach ($selectedPackages as $paket_id) {
                if (empty($paket_id)) continue;
                
                foreach ($paketler as $p) {
                    if ($p['id'] == $paket_id) {
                        $toplamPaketFiyati += floatval($p['fiyat']);
                        $paketBilgileri[] = $p;
                        break;
                    }
                }
            }

            $indirim_yuzdesi = floatval($_POST['indirim_yuzde'] ?? 0);
            $indirim_sabit_tutar = floatval($_POST['indirim_sabit_tutar'] ?? 0);
            
            $yuzde_indirim_tutari = ($toplamPaketFiyati * $indirim_yuzdesi) / 100;
            $toplam_indirim_tutari = $yuzde_indirim_tutari + $indirim_sabit_tutar;
            $toplam_indirim = min($toplam_indirim_tutari, $toplamPaketFiyati);
            $toplam_net_tutar = max(0, $toplamPaketFiyati - $toplam_indirim);

            $satis_idleri = [];
            $toplam_odenen = floatval($_POST['odenen_tutar']);
            
            foreach ($paketBilgileri as $index => $paket) {
                $paketFiyati = floatval($paket['fiyat']);
                $paketOrani = $toplamPaketFiyati > 0 ? ($paketFiyati / $toplamPaketFiyati) : 0;
                $paketIndirimi = $toplam_indirim * $paketOrani;
                $paketNetTutar = max(0, $paketFiyati - $paketIndirimi);
                $paketOdemesi = $toplam_odenen * $paketOrani;
                $paketIndirimYuzdesi = $paketFiyati > 0 ? ($paketIndirimi / $paketFiyati) * 100 : 0;
                $paketSabitIndirim = $indirim_sabit_tutar * $paketOrani;

                $satis_id = satisEkle(
                    $_POST['danisan_id'],
                    $paket['id'],
                    $_POST['personel_id'],
                    $paketNetTutar,
                    $paketOdemesi,
                    $vade_tarihi,
                    $index == 0 ? intval($_POST['hediye_seans']) : 0,
                    $paketIndirimi,
                    $paketIndirimYuzdesi,
                    $index == 0 ? $_POST['notlar'] : ("Grup satış #" . ($index + 1) . " - " . $_POST['notlar']),
                    intval($_POST['satis_turu_id']),
                    $current_user_id,
                    intval($_POST['odeme_turu_id']),
                    $paketSabitIndirim
                );

                $satis_idleri[] = $satis_id;

                if ($paketOdemesi > 0) {
                    odemeEkle(
                        $satis_id,
                        $paketOdemesi,
                        date('Y-m-d H:i:s'),
                        null,
                        intval($_POST['odeme_turu_id'])
                    );
                }
            }

            // Taksit sadece ilk pakete
            if (!empty($_POST['taksitler']) && is_array($_POST['taksitler']) && !empty($satis_idleri)) {
                foreach ($_POST['taksitler'] as $taksit) {
                    if (!empty($taksit['tutar']) && !empty($taksit['vade_tarihi'])) {
                        taksitEkle(
                            $satis_idleri[0],
                            floatval($taksit['tutar']),
                            $taksit['vade_tarihi'],
                            intval($_POST['odeme_turu_id'])
                        );
                    }
                }
            }
        } else {
            // TEK PAKET İŞLEMİ - ESKİ KOD AYNEN KORUNDU
            $secilenPaketFiyati = 0;
            $paket_id = $selectedPackages[0];
            foreach ($paketler as $p) {
                if ($p['id'] == $paket_id) {
                    $secilenPaketFiyati = floatval($p['fiyat']);
                    break;
                }
            }

            $indirim_yuzdesi = floatval($_POST['indirim_yuzde'] ?? 0);
            $indirim_sabit_tutar = floatval($_POST['indirim_sabit_tutar'] ?? 0);
            
            $yuzde_indirim_tutari = ($secilenPaketFiyati * $indirim_yuzdesi) / 100;
            $toplam_indirim_tutari = $yuzde_indirim_tutari + $indirim_sabit_tutar;
            $indirim_tutari = min($toplam_indirim_tutari, $secilenPaketFiyati);
            $net_tutar = max(0, $secilenPaketFiyati - $indirim_tutari);

            if ($edit_mode && !empty($_POST['edit_id'])) {
                $update_result = satisGuncelle(
                    $_POST['edit_id'],
                    $_POST['danisan_id'],
                    $paket_id,
                    $_POST['personel_id'],
                    $net_tutar,
                    floatval($_POST['odenen_tutar']),
                    $vade_tarihi,
                    intval($_POST['hediye_seans']),
                    $indirim_tutari,
                    $indirim_yuzdesi,
                    $_POST['notlar'],
                    intval($_POST['satis_turu_id']),
                    $current_user_id,
                    intval($_POST['odeme_turu_id']),
                    $indirim_sabit_tutar
                );

                if ($update_result) {
                    $success_message = "Satış başarıyla güncellendi!";
                }
            } else {
                $satis_id = satisEkle(
                    $_POST['danisan_id'],
                    $paket_id,
                    $_POST['personel_id'],
                    $net_tutar,
                    floatval($_POST['odenen_tutar']),
                    $vade_tarihi,
                    intval($_POST['hediye_seans']),
                    $indirim_tutari,
                    $indirim_yuzdesi,
                    $_POST['notlar'],
                    intval($_POST['satis_turu_id']),
                    $current_user_id,
                    intval($_POST['odeme_turu_id']),
                    $indirim_sabit_tutar
                );

                if (floatval($_POST['odenen_tutar']) > 0) {
                    odemeEkle(
                        $satis_id,
                        floatval($_POST['odenen_tutar']),
                        date('Y-m-d H:i:s'),
                        null,
                        intval($_POST['odeme_turu_id'])
                    );
                }

                if (!empty($_POST['taksitler']) && is_array($_POST['taksitler'])) {
                    foreach ($_POST['taksitler'] as $taksit) {
                        if (!empty($taksit['tutar']) && !empty($taksit['vade_tarihi'])) {
                            taksitEkle(
                                $satis_id,
                                floatval($taksit['tutar']),
                                $taksit['vade_tarihi'],
                                intval($_POST['odeme_turu_id'])
                            );
                        }
                    }
                }
            }
        }

        $pdo->commit();
        
        if (!$edit_mode) {
            header("Location: satislar.php");
            exit;
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = $edit_mode ? "Satış Düzenle" : "Yeni Satış";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
        /* ESKİ STYLE KORUNDU + SADECE GEREKLI DÜZELTMELER */
        .discount-type-section {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            background-color: #f8f9fa;
        }
        .edit-mode-header {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        /* ÇOKLU PAKET İÇİN MİNİMAL EKLEMELER */
        .multi-package-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            color: #1565c0;
        }
        
        .package-summary-box {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .package-summary-box h6 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        /* SELECT2 DÜZELTMELERİ */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            min-height: 38px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border: 1px solid #007bff;
            color: white;
            border-radius: 4px;
            padding: 2px 8px;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255,255,255,0.7);
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: white;
        }
        
        /* DROPDOWN MAX HEIGHT */
        .select2-results__options {
            max-height: 200px !important;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Finans";
                $title = $edit_mode ? "Satış Düzenle" : "Yeni Satış";
                include "partials/page-title.php";
                ?>

                <div class="card">
                    <div class="card-body">
                        <?php if ($edit_mode): ?>
                            <div class="edit-mode-header">
                                <h5 class="mb-2">🔧 Düzenleme Modu</h5>
                                <p class="mb-0">Satış ID: <strong><?= htmlspecialchars($edit_satis['id']) ?></strong></p>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                        <?php endif; ?>

                        <form method="POST" id="satis_form">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_satis['id']) ?>">
                            <?php endif; ?>
                            
                            <div class="row g-3">
                                <!-- Satış Türü -->
                                <div class="col-12">
                                    <label>Satış Türü <span class="text-danger">*</span></label>
                                    <select name="satis_turu_id" class="form-select searchable-select" required>
                                        <option value="">Seçiniz...</option>
                                        <?php foreach($satis_turleri as $tur): ?>
                                            <option value="<?= $tur['id'] ?>" 
                                                <?= ($edit_mode && $edit_satis['satis_turu_id'] == $tur['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tur['ad']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Hizmet Paketleri - ESKİ TASARIM KORUNDU -->
                                <div class="col-12">
                                    <?php if ($edit_mode): ?>
                                        <!-- Edit modunda tek seçim - ESKİ HALİ -->
                                        <label>Hizmet Paketi <span class="text-danger">*</span></label>
                                        <select name="hizmet_paketi_id" class="form-select searchable-select" required id="hizmet_paketi_select">
                                            <option value="">Seçiniz...</option>
                                            <?php foreach($paketler as $paket): ?>
                                                <option value="<?= $paket['id'] ?>"
                                                        data-fiyat="<?= $paket['fiyat'] ?>"
                                                        data-seans="<?= $paket['seans_adet'] ?>"
                                                        <?= ($edit_mode && $edit_satis['hizmet_paketi_id'] == $paket['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($paket['ad']) ?>
                                                    (<?= $paket['seans_adet'] ?> Seans - <?= number_format($paket['fiyat'],2,',','.') ?> ₺)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <!-- Yeni satışta çoklu seçim - YENİ -->
                                        <label>Hizmet Paketleri <span class="text-danger">*</span> 
                                            <small class="text-muted">(Birden fazla seçebilirsiniz)</small>
                                        </label>
                                        <select name="selected_packages[]" class="form-select" multiple="multiple" required id="multi_paket_select">
                                            <?php foreach($paketler as $paket): ?>
                                                <option value="<?= $paket['id'] ?>"
                                                        data-fiyat="<?= $paket['fiyat'] ?>"
                                                        data-seans="<?= $paket['seans_adet'] ?>">
                                                    <?= htmlspecialchars($paket['ad']) ?>
                                                    (<?= $paket['seans_adet'] ?> Seans - <?= number_format($paket['fiyat'],2,',','.') ?> ₺)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <!-- Paket Özeti - YENİ -->
                                        <div id="package_summary" class="package-summary-box" style="display: none;">
                                            <h6>📦 Seçilen Paketler Özeti</h6>
                                            <div class="row text-center">
                                                <div class="col-3">
                                                    <strong id="total_packages">0</strong><br>
                                                    <small>Paket</small>
                                                </div>
                                                <div class="col-3">
                                                    <strong id="total_sessions">0</strong><br>
                                                    <small>Seans</small>
                                                </div>
                                                <div class="col-3">
                                                    <strong id="total_price">0 ₺</strong><br>
                                                    <small>Toplam</small>
                                                </div>
                                                <div class="col-3">
                                                    <strong id="net_price">0 ₺</strong><br>
                                                    <small>Net</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- İndirim Bölümü - ESKİ HALİ KORUNDU -->
                                <div class="col-12">
                                    <div class="discount-type-section">
                                        <h6 class="mb-3">İndirim Seçenekleri</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label>Yüzde İndirimi (%)</label>
                                                <div class="input-group">
                                                    <input type="number" name="indirim_yuzde" id="indirim_yuzde" class="form-control" 
                                                           value="<?= $edit_mode ? number_format($edit_satis['indirim_yuzdesi'], 2, '.', '') : '0' ?>" 
                                                           min="0" max="100" step="0.01" placeholder="Örn: 10">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label>Sabit Tutar İndirimi (₺)</label>
                                                <div class="input-group">
                                                    <input type="number" name="indirim_sabit_tutar" id="indirim_sabit_tutar" class="form-control" 
                                                           value="<?= $edit_mode ? number_format($edit_satis['indirim_sabit_tutar'] ?? 0, 2, '.', '') : '0' ?>" 
                                                           min="0" step="0.01" placeholder="Örn: 1200">
                                                    <span class="input-group-text">₺</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fiyat Hesaplama - ESKİ HALİ KORUNDU -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label>Toplam Tutar (₺)</label>
                                            <input type="number" name="toplam_tutar" id="toplam_tutar_input" class="form-control" 
                                                   value="<?= $edit_mode ? number_format($edit_satis['toplam_tutar'], 2, '.', '') : '' ?>" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Peşinat (₺)</label>
                                            <input type="number" name="odenen_tutar" class="form-control" 
                                                   value="<?= $edit_mode ? number_format($edit_satis['odenen_tutar'], 2, '.', '') : '' ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Kalan (₺)</label>
                                            <input type="number" id="kalan_tutar" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Ödeme Tipi <span class="text-danger">*</span></label>
                                            <select name="odeme_turu_id" class="form-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($odeme_turleri as $odeme): ?>
                                                    <option value="<?= $odeme['id'] ?>" 
                                                            <?= ($edit_mode && $edit_satis['odeme_turu_id'] == $odeme['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($odeme['ad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Danışan ve Personel - SADECE FİLTRE EKLENDİ -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label>Danışan <span class="text-danger">*</span></label>
                                            <select name="danisan_id" class="form-select searchable-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($danisanlar as $d): ?>
                                                    <option value="<?= $d['id'] ?>"
                                                            <?= ($edit_mode && $edit_satis['danisan_id'] == $d['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($d['ad'] . ' ' . $d['soyad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-info">ℹ️ Sadece ad/soyad bilgisi tam olanlar gösterilir</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Satış Personeli <span class="text-danger">*</span></label>
                                            <select name="personel_id" class="form-select searchable-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($personel as $p): ?>
                                                    <option value="<?= $p['id'] ?>"
                                                            <?= ($edit_mode && $edit_satis['personel_id'] == $p['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($p['ad'] . ' ' . $p['soyad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hediye Seans - ESKİ HALİ KORUNDU -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label>Hediye Seans Adedi</label>
                                            <input type="number" name="hediye_seans" class="form-control" 
                                                   value="<?= $edit_mode ? $edit_satis['hediye_seans'] : '0' ?>" min="0">
                                            <small class="text-muted">💡 Bu alan sıkça unutuluyor! Kontrol edin.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notlar - ESKİ HALİ KORUNDU -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label>Notlar</label>
                                            <textarea name="notlar" class="form-control" rows="5" placeholder="Satış notları..."><?= $edit_mode ? htmlspecialchars($edit_satis['notlar']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ödeme Planı - ESKİ HALİ KORUNDU -->
                                <?php if (!$edit_mode): ?>
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Ödeme Planı</h6>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="addTaksit()">+ Taksit Ekle</button>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr><th>Vade Tarihi</th><th>Tutar (₺)</th><th></th></tr>
                                                    </thead>
                                                    <tbody id="taksit_listesi"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div>
                            
                            <!-- Hidden alanlar - ESKİ HALİ KORUNDU -->
                            <input type="hidden" name="hesaplanan_indirim_tutari" id="hesaplanan_indirim_tutari" value="0">
                            <input type="hidden" name="hesaplanan_toplam_yuzde" id="hesaplanan_toplam_yuzde" value="0">
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-<?= $edit_mode ? 'warning' : 'primary' ?>">
                                    <i class="fas fa-<?= $edit_mode ? 'edit' : 'save' ?> me-2"></i>
                                    <?= $edit_mode ? 'Güncelle' : 'Kaydet' ?>
                                </button>
                                <a href="satislar.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>İptal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// ESKİ SCRIPT KORUNDU + ÇOKLU PAKET DESTEĞİ EKLENDİ
let secilenPaketFiyati = 0;
const editMode = <?= $edit_mode ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', function() {
    // ESKİ SELECT2 AYARLARI KORUNDU
    $('.searchable-select').select2({ 
        width: '100%', 
        placeholder: 'Seçiniz...',
        allowClear: true
    });
    
    if (!editMode) {
        // ÇOKLU PAKET SELECT2 - YENİ
        $('#multi_paket_select').select2({
            width: '100%',
            placeholder: 'Paket seçiniz... (Ctrl+Click ile çoklu seçim)',
            allowClear: true
        });
        
        $('#multi_paket_select').on('change', function() {
            updateMultiPackageCalculation();
        });
    } else {
        // ESKİ TEK PAKET EVENT'İ KORUNDU
        $('#hizmet_paketi_select').on('select2:select', function (e) {
            updatePrice();
            setTimeout(() => {
                $(this).select2('close');
            }, 100);
        });
    }
    
    // ESKİ EVENT'LER KORUNDU
    document.getElementById('indirim_yuzde').addEventListener('input', function() {
        if (editMode) {
            updateTotalPrice();
        } else {
            updateMultiPackageCalculation();
        }
    });
    
    document.getElementById('indirim_sabit_tutar').addEventListener('input', function() {
        if (editMode) {
            updateTotalPrice();
        } else {
            updateMultiPackageCalculation();
        }
    });
    
    document.querySelector('[name="odenen_tutar"]').addEventListener('input', function() {
        if (editMode) {
            updateTotalPrice();
        } else {
            updateMultiPackageCalculation();
        }
    });
    
    // ESKİ EDİT MODU KORUNDU
    if (editMode) {
        updatePrice();
        setTimeout(() => {
            updateTotalPrice();
        }, 500);
    }
});

// YENİ FONKSİYON - Çoklu paket hesaplama
function updateMultiPackageCalculation() {
    const selectedValues = $('#multi_paket_select').val() || [];
    const packageSummary = document.getElementById('package_summary');
    
    if (selectedValues.length === 0) {
        packageSummary.style.display = 'none';
        document.getElementById('toplam_tutar_input').value = '';
        document.getElementById('kalan_tutar').value = '';
        return;
    }
    
    packageSummary.style.display = 'block';
    
    let totalPrice = 0;
    let totalSessions = 0;
    let packageCount = selectedValues.length;
    
    selectedValues.forEach(packageId => {
        const option = document.querySelector(`#multi_paket_select option[value="${packageId}"]`);
        if (option) {
            totalPrice += parseFloat(option.dataset.fiyat) || 0;
            totalSessions += parseInt(option.dataset.seans) || 0;
        }
    });
    
    const discountPercent = parseFloat(document.getElementById('indirim_yuzde').value) || 0;
    const discountFixed = parseFloat(document.getElementById('indirim_sabit_tutar').value) || 0;
    
    const percentDiscountAmount = (totalPrice * discountPercent) / 100;
    const totalDiscountAmount = Math.min(percentDiscountAmount + discountFixed, totalPrice);
    const netPrice = Math.max(0, totalPrice - totalDiscountAmount);
    
    document.getElementById('total_packages').textContent = packageCount;
    document.getElementById('total_sessions').textContent = totalSessions;
    document.getElementById('total_price').textContent = formatMoney(totalPrice) + ' ₺';
    document.getElementById('net_price').textContent = formatMoney(netPrice) + ' ₺';
    
    document.getElementById('toplam_tutar_input').value = netPrice.toFixed(2);
    
    const paidAmount = parseFloat(document.querySelector('[name="odenen_tutar"]').value) || 0;
    document.getElementById('kalan_tutar').value = Math.max(0, netPrice - paidAmount).toFixed(2);
    
    document.getElementById('hesaplanan_indirim_tutari').value = totalDiscountAmount.toFixed(2);
    document.getElementById('hesaplanan_toplam_yuzde').value = (totalPrice > 0 ? (totalDiscountAmount / totalPrice) * 100 : 0).toFixed(2);
    
    // Bilgilendirme mesajı
    showMultiPackageInfo(packageCount);
}

// YENİ FONKSİYON - Bilgilendirme mesajı
function showMultiPackageInfo(count) {
    // Önceki mesajı temizle
    const existingInfo = document.querySelector('.multi-package-info');
    if (existingInfo) {
        existingInfo.remove();
    }
    
    if (count > 1) {
        const infoDiv = document.createElement('div');
        infoDiv.className = 'multi-package-info';
        infoDiv.innerHTML = `
            <strong>${count} paket seçildi:</strong> Her paket için ayrı satış kaydı oluşturulacak, 
            indirim ve ödeme orantılı dağıtılacak.
        `;
        document.getElementById('package_summary').appendChild(infoDiv);
    }
}

// ESKİ FONKSİYONLAR KORUNDU
function updatePrice() {
    const sel = document.querySelector('[name="hizmet_paketi_id"]');
    const selectedOption = sel.options[sel.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.fiyat) {
        secilenPaketFiyati = parseFloat(selectedOption.dataset.fiyat);
    } else {
        secilenPaketFiyati = 0;
    }
    
    updateTotalPrice();
}

function updateTotalPrice() {
    if (secilenPaketFiyati <= 0) {
        return;
    }

    const yuzdeValue = parseFloat(document.getElementById('indirim_yuzde').value) || 0;
    const sabitValue = parseFloat(document.getElementById('indirim_sabit_tutar').value) || 0;

    const yuzdeIndirimlutari = (secilenPaketFiyati * yuzdeValue) / 100;
    const toplamIndirimlutari = yuzdeIndirimlutari + sabitValue;
    const gercekToplamIndirim = Math.min(toplamIndirimlutari, secilenPaketFiyati);
    const netFiyat = Math.max(0, secilenPaketFiyati - gercekToplamIndirim);

    document.querySelector('[name="toplam_tutar"]').value = netFiyat.toFixed(2);
    
    document.getElementById('hesaplanan_indirim_tutari').value = gercekToplamIndirim.toFixed(2);
    document.getElementById('hesaplanan_toplam_yuzde').value = (secilenPaketFiyati > 0 ? (gercekToplamIndirim / secilenPaketFiyati) * 100 : 0).toFixed(2);

    const odenen = parseFloat(document.querySelector('[name="odenen_tutar"]').value) || 0;
    document.getElementById('kalan_tutar').value = Math.max(0, netFiyat - odenen).toFixed(2);
}

function formatMoney(amount) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function addTaksit() {
    const tbody = document.getElementById('taksit_listesi');
    const idx = tbody.children.length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="date" name="taksitler[${idx}][vade_tarihi]" class="form-control" required></td>
        <td><input type="number" name="taksitler[${idx}][tutar]" class="form-control" step="0.01" required></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

// YENİ - Form submit kontrolü
document.getElementById('satis_form').addEventListener('submit', function(e) {
    if (!editMode) {
        const selectedPackages = $('#multi_paket_select').val() || [];
        if (selectedPackages.length === 0) {
            e.preventDefault();
            alert('⚠️ Lütfen en az bir paket seçiniz!');
            return false;
        }
        
        if (selectedPackages.length > 1) {
            const confirmMessage = `📦 ${selectedPackages.length} paket seçtiniz.\n\n` +
                                 `• Her paket için ayrı satış kaydı oluşturulacak\n` +
                                 `• İndirim toplam tutardan orantılı dağıtılacak\n\n` +
                                 `Devam etmek istiyor musunuz?`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        }
    }
});
</script>
    </div>
</body>
</html>