<?php
session_start();

require __DIR__ . '/functions.php';
require __DIR__ . '/con/db.php';

$personel_id = $_SESSION['personel_id'];
$today = date('Y-m-d');

// Terapist'in gelişmiş istatistikleri
function getTerapistGelismisIstatistikler($personel_id) {
    global $pdo;
    
    // Bu ay tamamlanan seanslar
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE personel_id = ? 
            AND MONTH(randevu_tarihi) = MONTH(CURDATE())
            AND YEAR(randevu_tarihi) = YEAR(CURDATE())
            AND durum = 'tamamlandi'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $aylik_tamamlanan = $stmt->fetchColumn();
    
    // Bu ay iptal olan seanslar
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE personel_id = ? 
            AND MONTH(randevu_tarihi) = MONTH(CURDATE())
            AND YEAR(randevu_tarihi) = YEAR(CURDATE())
            AND durum = 'iptal_edildi'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $aylik_iptal = $stmt->fetchColumn();
    
    // Bu hafta çalışılan saat
    $sql = "SELECT COUNT(*) FROM randevular r
            JOIN seans_turleri st ON r.seans_turu_id = st.id
            WHERE r.personel_id = ? 
            AND WEEK(r.randevu_tarihi) = WEEK(CURDATE())
            AND YEAR(r.randevu_tarihi) = YEAR(CURDATE())
            AND r.durum = 'tamamlandi'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $haftalik_saat = $stmt->fetchColumn();
    
    // Ortalama hasta memnuniyeti (son 30 gün)
    $sql = "SELECT AVG(puan) FROM hasta_degerlendirmeleri 
            WHERE personel_id = ? 
            AND olusturma_tarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $memnuniyet_ort = $stmt->fetchColumn() ?: 0;
    
    // Geçen aya göre performans karşılaştırması
    $gecen_ay = date('Y-m', strtotime('-1 month'));
    $sql = "SELECT COUNT(*) FROM randevular 
            WHERE personel_id = ? 
            AND DATE_FORMAT(randevu_tarihi, '%Y-%m') = ?
            AND durum = 'tamamlandi'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id, $gecen_ay]);
    $gecen_ay_seans = $stmt->fetchColumn();
    
    $performans_degisim = $gecen_ay_seans > 0 ? 
        (($aylik_tamamlanan - $gecen_ay_seans) / $gecen_ay_seans) * 100 : 0;
    
    return [
        'aylik_tamamlanan' => $aylik_tamamlanan,
        'aylik_iptal' => $aylik_iptal,
        'haftalik_saat' => $haftalik_saat,
        'memnuniyet_ort' => round($memnuniyet_ort, 1),
        'performans_degisim' => round($performans_degisim, 1),
        'iptal_orani' => $aylik_tamamlanan > 0 ? round(($aylik_iptal / ($aylik_tamamlanan + $aylik_iptal)) * 100, 1) : 0
    ];
}

// Terapist'in haftalık programı
function getTerapistHaftalikProgram($personel_id) {
    global $pdo;
    $haftanin_baslangici = date('Y-m-d', strtotime('monday this week'));
    
    $sql = "SELECT 
                DATE(randevu_tarihi) as gun,
                COUNT(*) as seans_sayisi,
                GROUP_CONCAT(TIME(randevu_tarihi) ORDER BY randevu_tarihi) as saatler
            FROM randevular 
            WHERE personel_id = ? 
            AND randevu_tarihi >= ?
            AND randevu_tarihi < DATE_ADD(?, INTERVAL 7 DAY)
            AND aktif = 1
            GROUP BY DATE(randevu_tarihi)
            ORDER BY randevu_tarihi";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id, $haftanin_baslangici, $haftanin_baslangici]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// En sık çalışılan seans türleri
function getTerapistSeansTurleri($personel_id) {
    global $pdo;
    $sql = "SELECT 
                st.ad as seans_adi,
                COUNT(*) as seans_sayisi,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM randevular WHERE personel_id = ? AND MONTH(randevu_tarihi) = MONTH(CURDATE()))), 1) as yuzde
            FROM randevular r
            JOIN seans_turleri st ON r.seans_turu_id = st.id
            WHERE r.personel_id = ? 
            AND MONTH(r.randevu_tarihi) = MONTH(CURDATE())
            AND YEAR(r.randevu_tarihi) = YEAR(CURDATE())
            AND r.durum = 'tamamlandi'
            GROUP BY st.id, st.ad
            ORDER BY seans_sayisi DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id, $personel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Yaklaşan önemli olaylar
function getTerapistYaklasanOlaylar($personel_id) {
    global $pdo;
    
    $olaylar = [];
    
    // Paket bitecek hastalar (kalan seans <= 2)
    $sql = "SELECT 
                CONCAT(d.ad, ' ', d.soyad) as hasta_adi,
                st.ad as seans_turu,
                (st.seans_adet + s.hediye_seans) - COUNT(r.id) as kalan_seans
            FROM satislar s
            JOIN danisanlar d ON s.danisan_id = d.id
            JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
            LEFT JOIN randevular r ON s.id = r.satis_id AND r.aktif = 1
            WHERE EXISTS (
                SELECT 1 FROM randevular r2 
                WHERE r2.satis_id = s.id AND r2.personel_id = ?
            )
            AND s.aktif = 1
            GROUP BY s.id
            HAVING kalan_seans <= 2 AND kalan_seans > 0
            ORDER BY kalan_seans ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $bitecek_paketler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bitecek_paketler as $paket) {
        $olaylar[] = [
            'tip' => 'paket_bitiyor',
            'mesaj' => $paket['hasta_adi'] . ' - ' . $paket['seans_turu'] . ' paketi bitiyor (' . $paket['kalan_seans'] . ' seans kaldı)',
            'tarih' => $paket['son_kullanma_tarihi'],
            'oncelik' => 'yuksek'
        ];
    }
    
    // Son 30 günde randevu almayan hastalar
    $sql = "SELECT DISTINCT
                CONCAT(d.ad, ' ', d.soyad) as hasta_adi,
                MAX(r.randevu_tarihi) as son_randevu,
                d.telefon
            FROM randevular r
            JOIN danisanlar d ON r.danisan_id = d.id
            WHERE r.personel_id = ?
            AND r.durum = 'tamamlandi'
            GROUP BY d.id
            HAVING son_randevu < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ORDER BY son_randevu DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    $uzun_sure_gelmeyenler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($uzun_sure_gelmeyenler as $hasta) {
        $olaylar[] = [
            'tip' => 'uzun_sure_gelmedi',
            'mesaj' => $hasta['hasta_adi'] . ' - ' . date('d.m.Y', strtotime($hasta['son_randevu'])) . ' tarihinden beri gelmiyor',
            'tarih' => $hasta['son_randevu'],
            'oncelik' => 'orta'
        ];
    }
    
    return $olaylar;
}

// Performans grafiği için veri
function getTerapistPerformansVerisi($personel_id) {
    global $pdo;
    
    $sql = "SELECT 
                DATE_FORMAT(randevu_tarihi, '%Y-%m') as ay_yil,
                COUNT(*) as seans_sayisi,
                AVG(COALESCE(hd.puan, 0)) as ortalama_puan
            FROM randevular r
            LEFT JOIN hasta_degerlendirmeleri hd ON r.id = hd.randevu_id
            WHERE r.personel_id = ?
            AND r.randevu_tarihi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND r.durum = 'tamamlandi'
            GROUP BY DATE_FORMAT(randevu_tarihi, '%Y-%m')
            ORDER BY ay_yil ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTerapistBugunRandevular($personel_id) {
    global $pdo;
    $sql = "SELECT r.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   d.telefon,
                   st.ad as seans_turu,
                   rm.name as room_name,
                   TIME(r.randevu_tarihi) as saat
            FROM randevular r
            JOIN danisanlar d ON d.id = r.danisan_id
            JOIN seans_turleri st ON st.id = r.seans_turu_id
            LEFT JOIN rooms rm ON rm.id = r.room_id
            WHERE r.personel_id = ? 
            AND DATE(r.randevu_tarihi) = CURDATE()
            AND r.aktif = 1
            ORDER BY r.randevu_tarihi ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personel_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Terapist'in yarınki randevuları
function getTerapistYarinRandevular($personel_id) {
    global $pdo;
    try {
        $sql = "SELECT r.*, 
                       CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                       d.telefon,
                       st.ad as seans_turu,
                       rm.name as room_name,
                       TIME(r.randevu_tarihi) as saat
                FROM randevular r
                JOIN danisanlar d ON d.id = r.danisan_id
                JOIN seans_turleri st ON st.id = r.seans_turu_id
                LEFT JOIN rooms rm ON rm.id = r.room_id
                WHERE r.personel_id = ? 
                AND DATE(r.randevu_tarihi) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND r.aktif = 1
                ORDER BY r.randevu_tarihi ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personel_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: []; // null yerine boş array döndür
    } catch (PDOException $e) {
        error_log("getTerapistYarinRandevular hatası: " . $e->getMessage());
        return []; // Hata durumunda boş array
    }
}

// Veriler
$istatistikler = getTerapistGelismisIstatistikler($personel_id);
$haftalik_program = getTerapistHaftalikProgram($personel_id);
$seans_turleri = getTerapistSeansTurleri($personel_id);
$yaklasan_olaylar = getTerapistYaklasanOlaylar($personel_id);
$performans_verisi = getTerapistPerformansVerisi($personel_id);

// Bugünkü ve yarınki randevular (mevcut fonksiyonları kullan)
$bugun_randevular = getTerapistBugunRandevular($personel_id);
$yarin_randevular = getTerapistYarinRandevular($personel_id);

$title = 'Terapist Dashboard';
$subtitle = $_SESSION['ad_soyad'];
include __DIR__ . '/partials/header.php';
?>

<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            <?php include __DIR__ . '/partials/page-title.php'; ?>

            <div class="container-fluid">

                <!-- Karşılama ve Hızlı Özet -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-gradient-primary text-white">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h3 class="text-white mb-1">Hoş geldiniz, <?= htmlspecialchars($_SESSION['ad_soyad']) ?>!</h3>
                                        <p class="mb-0 opacity-75">
                                            Bugün <?= count($bugun_randevular) ?> randevunuz var. 
                                            Bu ay <?= $istatistikler['aylik_tamamlanan'] ?> seans tamamladınız.
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <div class="me-3">
                                                <small class="text-white-50 d-block">Danışan Memnuniyeti</small>
                                                <h4 class="text-white mb-0"><?= $istatistikler['memnuniyet_ort'] ?>/5</h4>
                                            </div>
                                            <i class="bx bx-trending-up fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performans Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Bu Ay Tamamlanan</h6>
                                        <h3 class="mb-0"><?= $istatistikler['aylik_tamamlanan'] ?></h3>
                                        <?php if ($istatistikler['performans_degisim'] != 0): ?>
                                            <small class="<?= $istatistikler['performans_degisim'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <i class="bx <?= $istatistikler['performans_degisim'] > 0 ? 'bx-trending-up' : 'bx-trending-down' ?>"></i>
                                                <?= abs($istatistikler['performans_degisim']) ?>% geçen aya göre
                                            </small>
                                  
                                        <?php endif; ?>
                                        </br>

                                    </div>
                                    <div class="text-primary">
                                        <i class="bx bx-check-circle fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Bu Hafta Çalışılan</h6>
                                        <h3 class="mb-0"><?= $istatistikler['haftalik_saat'] ?> saat</h3>
                                        <small class="text-muted">Ortalama günlük: <?= round($istatistikler['haftalik_saat']/5, 1) ?> saat</small>
                                    </div>
                                    <div class="text-info">
                                        <i class="bx bx-time fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">İptal Oranı</h6>
                                        <h3 class="mb-0">%<?= $istatistikler['iptal_orani'] ?></h3>
                                        <small class="text-muted"><?= $istatistikler['aylik_iptal'] ?> iptal edildi</small>
                                    </div>
                                    <div class="<?= $istatistikler['iptal_orani'] < 10 ? 'text-success' : 'text-warning' ?>">
                                        <i class="bx bx-x-circle fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-2">Danışan Memnuniyeti</h6>
                                        <h3 class="mb-0"><?= $istatistikler['memnuniyet_ort'] ?>/5</h3>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?= ($istatistikler['memnuniyet_ort']/5)*100 ?>%"></div>
                                        </div>
                                          </br>
                                    </div>
                                      
                                    <div class="text-success">
                                        <i class="bx bx-heart fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ana İçerik Alanı -->
                <div class="row">
                    <!-- Sol Kolon -->
                    <div class="col-lg-8">
                        
                        <!-- Bugünkü Randevular -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-calendar me-2"></i>
                                    Bugünkü Randevularım (<?= count($bugun_randevular) ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($bugun_randevular)): ?>
                                    <div class="text-center py-4">
                                        <i class="bx bx-calendar-x fs-1 text-muted"></i>
                                        <p class="text-muted mt-2">Bugün randevunuz bulunmamaktadır.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline">
                                        <?php foreach ($bugun_randevular as $randevu): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-primary"></div>
                                                <div class="timeline-content">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($randevu['danisan_adi']) ?></h6>
                                                            <p class="text-muted mb-1">
                                                                <i class="bx bx-map"></i> <?= htmlspecialchars($randevu['room_name'] ?: 'Oda Atanmamış') ?> |
                                                                <i class="bx bx-phone"></i> <?= htmlspecialchars($randevu['telefon'] ?: '-') ?>
                                                            </p>
                                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($randevu['seans_turu']) ?></span>
                                                        </div>
                                                        <div class="text-end">
                                                            <h5 class="text-primary mb-0"><?= $randevu['saat'] ?></h5>
                                                            <small class="text-muted">
                                                                <?= date('i', strtotime($randevu['randevu_tarihi'])) != '00' ? 
                                                                    date('H:i', strtotime($randevu['randevu_tarihi']) + 3600) : 
                                                                    date('H:00', strtotime($randevu['randevu_tarihi']) + 3600) ?> 
                                                                biteceği
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Performans Grafiği -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Son 6 Ay Performans</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="performansChart" height="100"></canvas>
                            </div>
                        </div>

                        <!-- Haftalık Program Özeti -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Bu Hafta Program Yoğunluğu</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php 
                                    $gunler = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                                    $program_map = [];
                                    foreach ($haftalik_program as $gun) {
                                        $program_map[date('N', strtotime($gun['gun']))] = $gun;
                                    }
                                    ?>
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <div class="col">
                                            <div class="text-center">
                                                <h6 class="mb-1"><?= substr($gunler[$i-1], 0, 3) ?></h6>
                                                <div class="progress mb-2" style="height: 80px; width: 20px; margin: 0 auto;">
                                                    <?php 
                                                    $seans_sayisi = $program_map[$i]['seans_sayisi'] ?? 0;
                                                    $yuzde = min(($seans_sayisi / 8) * 100, 100); // Max 8 seans varsayımı
                                                    ?>
                                                    <div class="progress-bar bg-primary" 
                                                         style="width: 100%; height: <?= $yuzde ?>%"
                                                         title="<?= $seans_sayisi ?> seans"></div>
                                                </div>
                                                <small class="text-muted"><?= $seans_sayisi ?></small>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Kolon -->
                    <div class="col-lg-4">
                        
                        <!-- Yarınki Randevular -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-calendar-plus me-2"></i>
                                    Yarınki Randevular (<?= count($yarin_randevular) ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($yarin_randevular)): ?>
                                    <div class="text-center py-3">
                                        <i class="bx bx-calendar-x fs-3 text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">Yarın randevunuz yok.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($yarin_randevular as $randevu): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($randevu['danisan_adi']) ?></h6>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($randevu['seans_turu']) ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-success"><?= date('H:i', strtotime($randevu['randevu_tarihi'])) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- En Çok Yaptığım Seanslar -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Bu Ay En Çok Yaptığım Seanslar</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($seans_turleri)): ?>
                                    <p class="text-muted text-center py-3">Bu ay henüz seans veriniz yok.</p>
                                <?php else: ?>
                                    <?php foreach ($seans_turleri as $seans): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($seans['seans_adi']) ?></h6>
                                                <small class="text-muted"><?= $seans['seans_sayisi'] ?> seans</small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary">%<?= $seans['yuzde'] ?></span>
                                            </div>
                                        </div>
                                        <div class="progress mb-3" style="height: 4px;">
                                            <div class="progress-bar" style="width: <?= $seans['yuzde'] ?>%"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Önemli Uyarılar ve Hatırlatmalar -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bx bx-bell me-2"></i>
                                    Önemli Uyarılar
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($yaklasan_olaylar)): ?>
                                    <div class="text-center py-3">
                                        <i class="bx bx-check-circle text-success fs-3"></i>
                                        <p class="text-muted mt-2 mb-0">Şu an önemli bir uyarı yok.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($yaklasan_olaylar as $olay): ?>
                                        <div class="alert alert-<?= $olay['oncelik'] == 'yuksek' ? 'danger' : 'warning' ?> py-2 mb-2">
                                            <div class="d-flex align-items-start">
                                                <i class="bx <?= $olay['tip'] == 'paket_bitiyor' ? 'bx-package' : 'bx-user-x' ?> me-2 mt-1"></i>
                                                <small><?= htmlspecialchars($olay['mesaj']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Hızlı Eylemler -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Hızlı Eylemler</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="?page=randevular" class="btn btn-primary">
                                        <i class="bx bx-calendar me-2"></i>Randevu Takvimi
                                    </a>
                                    <a href="?page=danisanlar" class="btn btn-info">
                                        <i class="bx bx-users me-2"></i>Danışanlar
                                    </a>
                                    <a href="?page=notlarim" class="btn btn-success">
                                        <i class="bx bx-note me-2"></i>Notlarım
                                    </a>
                                    <a href="?page=seans-raporlari" class="btn btn-warning">
                                        <i class="bx bx-chart me-2"></i>Raporlarım
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Performans grafiği
    const performansData = <?= json_encode($performans_verisi) ?>;
    const ctx = document.getElementById('performansChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: performansData.map(item => {
                const [yil, ay] = item.ay_yil.split('-');
                const aylar = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
                return aylar[parseInt(ay) - 1] + ' ' + yil;
            }),
            datasets: [{
                label: 'Seans Sayısı',
                data: performansData.map(item => item.seans_sayisi),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Danışan Memnuniyeti',
                data: performansData.map(item => item.ortalama_puan),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Ay'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Seans Sayısı'
                    },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Memnuniyet (1-5)'
                    },
                    min: 0,
                    max: 5,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.timeline::before {
    content: '';
    position: absolute;
    left: -30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}
</style>

<?php include __DIR__ . '/partials/footer-scripts.php'; ?>