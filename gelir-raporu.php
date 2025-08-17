<?php
session_start();
require_once 'functions.php';

// Filtreleme parametreleri
$ay = $_GET['ay'] ?? date('m');
$yil = $_GET['yil'] ?? date('Y');
$gun = $_GET['gun'] ?? '';
$satis_turu_id = $_GET['satis_turu_id'] ?? '';
$odeme_turu_id = $_GET['odeme_turu_id'] ?? '';

// Dropdown verileri
$satis_turleri = getSatisTurleri();
$odeme_turleri = getOdemeTurleri();

// Tarih filtreleme
$tarih_filtre = "";
$params = [];

if (!empty($gun) && !empty($ay) && !empty($yil)) {
    $tarih_filtre = "AND DATE(s.olusturma_tarihi) = :tarih";
    $params['tarih'] = sprintf('%04d-%02d-%02d', $yil, $ay, $gun);
} elseif (!empty($ay) && !empty($yil)) {
    $tarih_filtre = "AND YEAR(s.olusturma_tarihi) = :yil AND MONTH(s.olusturma_tarihi) = :ay";
    $params['yil'] = $yil;
    $params['ay'] = $ay;
} elseif (!empty($yil)) {
    $tarih_filtre = "AND YEAR(s.olusturma_tarihi) = :yil";
    $params['yil'] = $yil;
}

// Satış türü filtresi
if (!empty($satis_turu_id)) {
    $tarih_filtre .= " AND s.satis_turu_id = :satis_turu_id";
    $params['satis_turu_id'] = $satis_turu_id;
}

// Ödeme türü filtresi
if (!empty($odeme_turu_id)) {
    $tarih_filtre .= " AND s.odeme_turu_id = :odeme_turu_id";
    $params['odeme_turu_id'] = $odeme_turu_id;
}

// Ana sorgu - Satışları getir (faturalandi kolonu eklendi)
$sql = "SELECT 
    s.id as satis_id,
    s.olusturma_tarihi as tarih,
    s.faturalandi,  -- Gerçek faturalama durumu
    CONCAT(d.ad, ' ', d.soyad) as ad_soyad,
    st_seans.ad as hizmet,
    st_satis.ad as satis_turu,
    ot.ad as odeme_turu,
    s.toplam_tutar as nakit_satis,
    s.toplam_tutar as kdv_dahil_satis,
    s.notlar,
    d.vergi_numarasi as vkn_tc,
    d.email,
    COALESCE(d.fatura_adresi, d.adres) as fatura_adresi,
    d.vergi_dairesi,
    CONCAT(p_islem.ad, ' ', p_islem.soyad) as odemeyi_alan,
    CONCAT(p_satis.ad, ' ', p_satis.soyad) as satis_yapan,
    -- Taksit bilgisi
    (SELECT GROUP_CONCAT(
        CONCAT(vade_tarihi, ':', tutar, '₺') 
        ORDER BY vade_tarihi 
        SEPARATOR ' | '
    ) FROM taksitler WHERE satis_id = s.id AND aktif = 1) as taksitler
FROM satislar s
LEFT JOIN danisanlar d ON d.id = s.danisan_id
LEFT JOIN seans_turleri st_seans ON st_seans.id = s.hizmet_paketi_id
LEFT JOIN satis_turleri st_satis ON st_satis.id = s.satis_turu_id
LEFT JOIN odeme_turleri ot ON ot.id = s.odeme_turu_id
LEFT JOIN personel p_islem ON p_islem.id = s.islem_login_id
LEFT JOIN personel p_satis ON p_satis.id = s.personel_id
WHERE s.aktif = 1 {$tarih_filtre}
ORDER BY s.olusturma_tarihi DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gelirler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $gelirler = [];
    $hata = "Veri getirme hatası: " . $e->getMessage();
}

// Toplam hesapla
$toplam_gelir = array_sum(array_column($gelirler, 'nakit_satis'));

// Faturalı/faturasız oranları hesapla
$faturali_count = count(array_filter($gelirler, function($g) { return $g['faturalandi'] == 1; }));
$faturasiz_count = count($gelirler) - $faturali_count;
$faturali_tutar = array_sum(array_column(array_filter($gelirler, function($g) { return $g['faturalandi'] == 1; }), 'nakit_satis'));
$faturasiz_tutar = $toplam_gelir - $faturali_tutar;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Gelir Raporu";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
        .excel-table {
            font-size: 12px;
            white-space: nowrap;
        }
        .excel-table th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 8px 4px;
        }
        .excel-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .fatura-edildi { 
            background-color: #d4edda !important; 
            color: #155724;
        }
        .fatura-edilmedi { 
            background-color: #f8d7da !important; 
            color: #721c24;
        }
        .filter-row {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-row {
            background-color: #e3f2fd;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .fatura-istatistik {
            border-left: 4px solid #007bff;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .fatura-badge {
            font-size: 0.85em;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 600;
        }
        
        .fatura-badge.faturali {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #a3cfbb;
        }
        
        .fatura-badge.faturasiz {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f1aeb5;
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
                $subtitle = "Muhasebe";
                $title = "Gelir Raporu";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Gelir Raporu</h5>
                                <button class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Excel İndir
                                </button>
                            </div>
                            <div class="card-body">

                                <!-- Filtreler -->
                                <div class="filter-row">
                                    <form method="GET" class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label">Yıl</label>
                                            <select name="yil" class="form-select">
                                                <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                                    <option value="<?= $y ?>" <?= $yil == $y ? 'selected' : '' ?>><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Ay</label>
                                            <select name="ay" class="form-select">
                                                <option value="">Tüm Aylar</option>
                                                <?php 
                                                $aylar = [
                                                    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                                    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                                    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                                ];
                                                foreach($aylar as $num => $isim): ?>
                                                    <option value="<?= $num ?>" <?= $ay == $num ? 'selected' : '' ?>><?= $isim ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Gün</label>
                                            <select name="gun" class="form-select">
                                                <option value="">Tüm Günler</option>
                                                <?php for($g = 1; $g <= 31; $g++): ?>
                                                    <option value="<?= sprintf('%02d', $g) ?>" <?= $gun == sprintf('%02d', $g) ? 'selected' : '' ?>><?= $g ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Satış Türü</label>
                                            <select name="satis_turu_id" class="form-select">
                                                <option value="">Tüm Türler</option>
                                                <?php foreach($satis_turleri as $tur): ?>
                                                    <option value="<?= $tur['id'] ?>" <?= $satis_turu_id == $tur['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tur['ad']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Ödeme Türü</label>
                                            <select name="odeme_turu_id" class="form-select">
                                                <option value="">Tüm Türler</option>
                                                <?php foreach($odeme_turleri as $tur): ?>
                                                    <option value="<?= $tur['id'] ?>" <?= $odeme_turu_id == $tur['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tur['ad']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">Filtrele</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Gelişmiş Özet Kartları -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h5>Toplam Gelir</h5>
                                                <h3><?= number_format($toplam_gelir, 2, ',', '.') ?> ₺</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body">
                                                <h5>Satış Sayısı</h5>
                                                <h3><?= count($gelirler) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card fatura-istatistik">
                                            <div class="card-body">
                                                <h6 class="text-primary mb-2">📄 Faturalı Satışlar</h6>
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold"><?= $faturali_count ?> adet</span>
                                                    <span class="text-success fw-bold"><?= number_format($faturali_tutar, 0, ',', '.') ?> ₺</span>
                                                </div>
                                                <small class="text-muted">
                                                    Toplam satışın %<?= count($gelirler) > 0 ? round(($faturali_count / count($gelirler)) * 100, 1) : 0 ?>'si
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card fatura-istatistik">
                                            <div class="card-body">
                                                <h6 class="text-warning mb-2">📋 Faturasız Satışlar</h6>
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold"><?= $faturasiz_count ?> adet</span>
                                                    <span class="text-warning fw-bold"><?= number_format($faturasiz_tutar, 0, ',', '.') ?> ₺</span>
                                                </div>
                                                <small class="text-muted">
                                                    Toplam satışın %<?= count($gelirler) > 0 ? round(($faturasiz_count / count($gelirler)) * 100, 1) : 0 ?>'si
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tablo -->
                                <div class="table-responsive">
                                    <table class="table excel-table" id="gelir_tablosu">
                                        <thead>
                                            <tr>
                                                <th rowspan="2">FATURA DURUMU</th>
                                                <th rowspan="2">TARİH</th>
                                                <th rowspan="2">AD SOYAD</th>
                                                <th rowspan="2">HİZMET</th>
                                                <th rowspan="2">ÖDEME TÜRÜ</th>
                                                <th rowspan="2">NAKİT SATIŞ</th>
                                                <th rowspan="2">KDV DAHİL SATIŞ</th>
                                                <th rowspan="2">SATIŞ TÜRÜ</th>
                                                <th rowspan="2">ÖDEMEYİ ALAN</th>
                                                <th rowspan="2">SATIŞ YAPAN</th>
                                                <th rowspan="2">TAKSİT DURUMU</th>
                                                <th rowspan="2">VKN / TC</th>
                                                <th rowspan="2">VERGİ DAİRESİ</th>
                                                <th rowspan="2">EMAIL</th>
                                                <th rowspan="2">FATURA ADRESİ</th>
                                                <th rowspan="2">NOTLAR</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($gelirler as $index => $gelir): ?>
                                                <tr class="<?= $gelir['faturalandi'] ? 'fatura-edildi' : 'fatura-edilmedi' ?>">
                                                    <td class="text-center">
                                                        <span class="fatura-badge <?= $gelir['faturalandi'] ? 'faturali' : 'faturasiz' ?>">
                                                            <?= $gelir['faturalandi'] ? '✅ FATURA EDİLDİ' : '❌ FATURA EDİLMEDİ' ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center"><?= date('d.m.Y', strtotime($gelir['tarih'])) ?></td>
                                                    <td><?= htmlspecialchars($gelir['ad_soyad']) ?></td>
                                                    <td><?= htmlspecialchars($gelir['hizmet']) ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($gelir['odeme_turu'] ?? '-') ?></td>
                                                    <td class="text-right"><?= number_format($gelir['nakit_satis'], 0, ',', '.') ?></td>
                                                    <td class="text-right"><?= number_format($gelir['kdv_dahil_satis'], 0, ',', '.') ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($gelir['satis_turu'] ?? '-') ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($gelir['odemeyi_alan'] ?? '-') ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($gelir['satis_yapan'] ?? '-') ?></td>
                                                    <td class="text-center">
                                                        <?php if(!empty($gelir['taksitler'])): ?>
                                                            <span class="badge bg-warning">TAKSİTLİ</span>
                                                            <br><small><?= htmlspecialchars($gelir['taksitler']) ?></small>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">PEŞİN</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><?= htmlspecialchars($gelir['vkn_tc'] ?? '-') ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($gelir['vergi_dairesi'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($gelir['email'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($gelir['fatura_adresi'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($gelir['notlar'] ?? '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="summary-row">
                                                <td class="text-center"><strong>TOPLAM</strong></td>
                                                <td colspan="4" class="text-center">
                                                    <span class="badge bg-primary me-2">Faturalı: <?= $faturali_count ?></span>
                                                    <span class="badge bg-secondary">Faturasız: <?= $faturasiz_count ?></span>
                                                </td>
                                                <td class="text-right"><strong><?= number_format($toplam_gelir, 0, ',', '.') ?></strong></td>
                                                <td class="text-right"><strong><?= number_format($toplam_gelir, 0, ',', '.') ?></strong></td>
                                                <td colspan="9"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#gelir_tablosu').DataTable({
                    "paging": false,
                    "searching": false,
                    "info": false,
                    "ordering": false,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
                    }
                });
            });

            function exportToExcel() {
                // Tabloyu kopyala ve Excel formatına uyarla
                const originalTable = document.getElementById('gelir_tablosu');
                const table = originalTable.cloneNode(true);
                
                // Başlık satırını düzenle - tek satır yap
                const thead = table.querySelector('thead');
                thead.innerHTML = `
                    <tr style="background-color: #4CAF50; color: white; font-weight: bold;">
                        <th>FATURA DURUMU</th>
                        <th>TARİH</th>
                        <th>AD SOYAD</th>
                        <th>HİZMET</th>
                        <th>ÖDEME TÜRÜ</th>
                        <th>NAKİT SATIŞ</th>
                        <th>KDV DAHİL SATIŞ</th>
                        <th>SATIŞ TÜRÜ</th>
                        <th>ÖDEMEYİ ALAN</th>
                        <th>SATIŞ YAPAN</th>
                        <th>TAKSİT DURUMU</th>
                        <th>VKN / TC</th>
                        <th>VERGİ DAİRESİ</th>
                        <th>EMAIL</th>
                        <th>FATURA ADRESİ</th>
                        <th>NOTLAR</th>
                    </tr>`;
                
                // Veri satırlarını düzenle
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                
                rows.forEach((row, index) => {
                    const cells = row.querySelectorAll('td');
                    
                    // Fatura durumu hücresini basitleştir
                    if (cells[0]) {
                        const faturaDurumu = cells[0].textContent.includes('FATURA EDİLDİ') ? 'FATURA EDİLDİ' : 'FATURA EDİLMEDİ';
                        cells[0].innerHTML = faturaDurumu;
                        cells[0].style.backgroundColor = faturaDurumu === 'FATURA EDİLDİ' ? '#d4edda' : '#f8d7da';
                        cells[0].style.color = faturaDurumu === 'FATURA EDİLDİ' ? '#155724' : '#721c24';
                        cells[0].style.fontWeight = 'bold';
                    }
                    
                    // Taksit durumu hücresini basitleştir
                    if (cells[10]) {
                        const taksitContent = cells[10].textContent.trim();
                        if (taksitContent.includes('TAKSİTLİ')) {
                            cells[10].innerHTML = 'TAKSİTLİ';
                            cells[10].style.backgroundColor = '#ff9800';
                            cells[10].style.color = 'white';
                        } else {
                            cells[10].innerHTML = 'PEŞİN';
                            cells[10].style.backgroundColor = '#4caf50';
                            cells[10].style.color = 'white';
                        }
                    }
                    
                    // Sayıları düzenle (sadece rakam kalsın)
                    if (cells[5]) cells[5].innerHTML = cells[5].textContent.replace(/[^\d,]/g, '');
                    if (cells[6]) cells[6].innerHTML = cells[6].textContent.replace(/[^\d,]/g, '');
                });
                
                // Toplam satırını düzenle
                const tfoot = table.querySelector('tfoot tr');
                if (tfoot) {
                    tfoot.style.backgroundColor = '#e3f2fd';
                    tfoot.style.fontWeight = 'bold';
                    const cells = tfoot.querySelectorAll('td');
                    if (cells[5]) cells[5].innerHTML = cells[5].textContent.replace(/[^\d,]/g, '');
                    if (cells[6]) cells[6].innerHTML = cells[6].textContent.replace(/[^\d,]/g, '');
                }
                
                // Excel'e dönüştür
                const wb = XLSX.utils.table_to_book(table, {
                    sheet: "Gelir Raporu",
                    raw: false
                });
                
                // Worksheet'i al ve stil ekle
                const ws = wb.Sheets["Gelir Raporu"];
                
                // Kolon genişliklerini ayarla
                ws['!cols'] = [
                    {wch: 18}, // FATURA DURUMU
                    {wch: 12}, // TARİH
                    {wch: 20}, // AD SOYAD
                    {wch: 25}, // HİZMET
                    {wch: 15}, // ÖDEME TÜRÜ
                    {wch: 12}, // NAKİT SATIŞ
                    {wch: 15}, // KDV DAHİL SATIŞ
                    {wch: 20}, // SATIŞ TÜRÜ
                    {wch: 15}, // ÖDEMEYİ ALAN
                    {wch: 15}, // SATIŞ YAPAN
                    {wch: 15}, // TAKSİT DURUMU
                    {wch: 15}, // VKN/TC
                    {wch: 20}, // VERGİ DAİRESİ
                    {wch: 25}, // EMAIL
                    {wch: 30}, // FATURA ADRESİ
                    {wch: 30}  // NOTLAR
                ];
                
                // Dosya adına tarih ekle
                const today = new Date();
                const dateStr = today.toISOString().slice(0,10);
                const filename = `Gelir_Raporu_${dateStr}.xlsx`;
                
                XLSX.writeFile(wb, filename);
                
                // Başarı mesajı
                alert('Excel dosyası indirildi: ' + filename);
            }

            // Satır renklerini güncelle
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('#gelir_tablosu tbody tr');
                rows.forEach((row, index) => {
                    // Fatura durumu kontrolü ile renklendirme
                    const faturaCell = row.querySelector('td:first-child');
                    if (faturaCell) {
                        const isFaturali = faturaCell.textContent.includes('FATURA EDİLDİ');
                        if (isFaturali) {
                            row.classList.add('fatura-edildi');
                        } else {
                            row.classList.add('fatura-edilmedi');
                        }
                    }
                });
            });
        </script>
    </div>
</body>
</html>