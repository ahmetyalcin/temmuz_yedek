<?php
// danisan-seans-raporu.php
session_start();
require_once 'functions.php';

$danisan_id = $_GET['id'] ?? null;
if (!$danisan_id) {
    header('Location: danisan-listele.php');
    exit;
}

// Danışan bilgilerini al
$danisan = getDanisanDetay($danisan_id);
if (!$danisan) {
    $_SESSION['hata'] = 'Danışan bulunamadı!';
    header('Location: danisan-listele.php');
    exit;
}

// Danışanın tüm seans paketlerini al
function getDanisanSeansRaporu($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT 
                    s.id as satis_id,
                    s.olusturma_tarihi as satis_tarihi,
                    s.toplam_tutar,
                    s.odenen_tutar,
                    s.vade_tarihi,
                    s.durum as satis_durum,
                    s.hediye_seans,
                    s.notlar,
                    st.ad as paket_adi,
                    st.seans_adet,
                    st.sure as seans_suresi,
                    CONCAT(p.ad, ' ', p.soyad) as satan_personel,
                    -- Seans hesaplamaları
                    (st.seans_adet + COALESCE(s.hediye_seans, 0)) as toplam_seans,
                    COUNT(r.id) as kullanilan_seans,
                    (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) as kalan_seans,
                    -- Son randevu tarihi
                    MAX(r.randevu_tarihi) as son_randevu_tarihi,
                    -- Ödeme durumu
                    (s.toplam_tutar - s.odenen_tutar) as kalan_borc,
                    ROUND((s.odenen_tutar / s.toplam_tutar) * 100, 1) as odeme_yuzdesi,
                    -- Paket durumu
                    CASE 
                        WHEN (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) <= 0 THEN 'Tamamlandı'
                        WHEN s.vade_tarihi < CURDATE() THEN 'Süresi Dolmuş'
                        WHEN (s.odenen_tutar / s.toplam_tutar) < 0.5 THEN 'Ödeme Eksik'
                        ELSE 'Aktif'
                    END as paket_durumu
                FROM satislar s
                JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
                JOIN personel p ON s.personel_id = p.id
                LEFT JOIN randevular r ON s.id = r.satis_id AND r.aktif = 1
                WHERE s.danisan_id = :danisan_id 
                AND s.aktif = 1
                GROUP BY s.id
                ORDER BY s.olusturma_tarihi DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['danisan_id' => $danisan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Danışan seans raporu hatası: " . $e->getMessage());
        return [];
    }
}

// Paket detay bilgilerini al
function getPaketDetayBilgileri($satis_id) {
    global $pdo;
    try {
        $sql = "SELECT 
                    r.id,
                    r.randevu_tarihi,
                    r.durum,
                    r.notlar,
                    CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                    rm.name as oda_adi
                FROM randevular r
                LEFT JOIN personel p ON r.personel_id = p.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                WHERE r.satis_id = :satis_id 
                AND r.aktif = 1
                ORDER BY r.randevu_tarihi DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['satis_id' => $satis_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Paket detay bilgileri hatası: " . $e->getMessage());
        return [];
    }
}

$seans_raporu = getDanisanSeansRaporu($danisan_id);

// İstatistikler
$toplam_satis = count($seans_raporu);
$aktif_paket_sayisi = count(array_filter($seans_raporu, function($r) { return $r['paket_durumu'] == 'Aktif'; }));
$toplam_seans = array_sum(array_column($seans_raporu, 'toplam_seans'));
$kullanilan_seans = array_sum(array_column($seans_raporu, 'kullanilan_seans'));
$kalan_seans = array_sum(array_column($seans_raporu, 'kalan_seans'));
$toplam_odenen = array_sum(array_column($seans_raporu, 'odenen_tutar'));
$toplam_borc = array_sum(array_column($seans_raporu, 'kalan_borc'));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Danışan Seans Raporu";
    include "partials/title-meta.php";
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        .paket-card {
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .paket-card.tamamlandi { border-left-color: #28a745; }
        .paket-card.suresi-dolmus { border-left-color: #dc3545; }
        .paket-card.odeme-eksik { border-left-color: #ffc107; }
        .badge-aktif { background-color: #007bff; }
        .badge-tamamlandi { background-color: #28a745; }
        .badge-suresi-dolmus { background-color: #dc3545; }
        .badge-odeme-eksik { background-color: #ffc107; }
        .progress-seans {
            height: 8px;
            margin-top: 5px;
        }
        .collapse-toggle {
            cursor: pointer;
            transition: all 0.3s;
        }
        .collapse-toggle:hover {
            background-color: #f8f9fa;
        }
    </style>
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
            $subtitle = "Danışan Yönetimi";
            $title = $danisan['ad'] . ' ' . $danisan['soyad'] . ' - Seans Raporu';
            include "partials/page-title.php";
            ?>

            <!-- Danışan Bilgileri -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-1"><?= $danisan['ad'] . ' ' . $danisan['soyad'] ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-phone me-2"></i><?= $danisan['telefon'] ?>
                                <i class="fas fa-envelope ms-3 me-2"></i><?= $danisan['email'] ?? 'E-posta yok' ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="danisan-detay.php?id=<?= $danisan_id ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-user"></i> Danışan Detay
                            </a>
                            <a href="danisanlar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Geri
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3><?= $toplam_satis ?></h3>
                        <p>Toplam Paket</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?= $aktif_paket_sayisi ?></h3>
                        <p>Aktif Paket</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?= $toplam_seans ?></h3>
                        <p>Toplam Seans</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?= $kullanilan_seans ?></h3>
                        <p>Kullanılan</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h3><?= $kalan_seans ?></h3>
                        <p>Kalan Seans</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <h3 style="color: #333;"><?= number_format($toplam_borc, 0) ?>₺</h3>
                        <p style="color: #666;">Kalan Borç</p>
                    </div>
                </div>
            </div>

            <!-- Paket Listesi -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Seans Paketleri</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($seans_raporu)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Henüz seans paketi bulunmuyor</h5>
                            <p class="text-muted">Bu danışan için henüz satış kaydı bulunamadı.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($seans_raporu as $index => $paket): ?>
                            <div class="paket-card card <?= strtolower(str_replace(' ', '-', $paket['paket_durumu'])) ?>">
                                <div class="card-header collapse-toggle" data-bs-toggle="collapse" data-bs-target="#paket<?= $index ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h6 class="mb-1"><?= $paket['paket_adi'] ?></h6>
                                            <small class="text-muted">
                                                Satış: <?= date('d.m.Y', strtotime($paket['satis_tarihi'])) ?> | 
                                                <?= $paket['satan_personel'] ?>
                                            </small>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge badge-<?= strtolower(str_replace(' ', '-', $paket['paket_durumu'])) ?>">
                                                <?= $paket['paket_durumu'] ?>
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Toplam</small>
                                                    <strong><?= $paket['toplam_seans'] ?></strong>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Kullanılan</small>
                                                    <strong class="text-success"><?= $paket['kullanilan_seans'] ?></strong>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Kalan</small>
                                                    <strong class="text-warning"><?= $paket['kalan_seans'] ?></strong>
                                                </div>
                                            </div>
                                            <div class="progress progress-seans">
                                                <div class="progress-bar bg-success" style="width: <?= ($paket['kullanilan_seans'] / $paket['toplam_seans']) * 100 ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <div>
                                                <small class="text-muted d-block">Ödenen</small>
                                                <strong><?= number_format($paket['odenen_tutar'], 0) ?>₺</strong>
                                            </div>
                                            <div>
                                                <small class="text-muted">%<?= $paket['odeme_yuzdesi'] ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="collapse" id="paket<?= $index ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Paket Bilgileri</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td>Toplam Tutar:</td>
                                                        <td><strong><?= number_format($paket['toplam_tutar'], 2) ?>₺</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Kalan Borç:</td>
                                                        <td><strong class="text-danger"><?= number_format($paket['kalan_borc'], 2) ?>₺</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Hediye Seans:</td>
                                                        <td><?= $paket['hediye_seans'] ?: 0 ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Seans Süresi:</td>
                                                        <td><?= $paket['seans_suresi'] ?> dakika</td>
                                                    </tr>
                                                    <?php if ($paket['vade_tarihi']): ?>
                                                    <tr>
                                                        <td>Vade Tarihi:</td>
                                                        <td><?= date('d.m.Y', strtotime($paket['vade_tarihi'])) ?></td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <?php if ($paket['son_randevu_tarihi']): ?>
                                                    <tr>
                                                        <td>Son Randevu:</td>
                                                        <td><?= date('d.m.Y H:i', strtotime($paket['son_randevu_tarihi'])) ?></td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </table>
                                                
                                                <?php if ($paket['notlar']): ?>
                                                <div class="mt-3">
                                                    <h6>Notlar</h6>
                                                    <p class="text-muted small"><?= nl2br(htmlspecialchars($paket['notlar'])) ?></p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Randevu Geçmişi</h6>
                                                <?php 
                                                $randevular = getPaketDetayBilgileri($paket['satis_id']);
                                                if (!empty($randevular)): 
                                                ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Tarih</th>
                                                                <th>Terapist</th>
                                                                <th>Oda</th>
                                                                <th>Durum</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($randevular as $randevu): ?>
                                                            <tr>
                                                                <td><?= date('d.m.Y H:i', strtotime($randevu['randevu_tarihi'])) ?></td>
                                                                <td><?= $randevu['terapist_adi'] ?: 'Atanmadı' ?></td>
                                                                <td><?= $randevu['oda_adi'] ?: 'Atanmadı' ?></td>
                                                                <td>
                                                                    <?php
                                                                    $durum_class = '';
                                                                    switch($randevu['durum']) {
                                                                        case 'geldi': $durum_class = 'success'; break;
                                                                        case 'gelmedi': $durum_class = 'danger'; break;
                                                                        case 'iptal': $durum_class = 'secondary'; break;
                                                                        default: $durum_class = 'primary';
                                                                    }
                                                                    ?>
                                                                    <span class="badge bg-<?= $durum_class ?>"><?= ucfirst($randevu['durum']) ?></span>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <?php else: ?>
                                                <p class="text-muted">Henüz randevu bulunmuyor.</p>
                                                <?php endif; ?>
                                                
                                                <div class="mt-3">
                                                    <a href="room_schedule.php?danisan_id=<?= $danisan_id ?>&satis_id=<?= $paket['satis_id'] ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-calendar-plus"></i> Randevu Al
                                                    </a>
                                                    <a href="satislar.php?satis_id=<?= $paket['satis_id'] ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i> Satış Detay
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/customizer.php' ?>
<?php include 'partials/footer-scripts.php' ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Collapse ikonlarını döndür
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(element) {
        element.addEventListener('click', function() {
            const icon = this.querySelector('.fas');
            if (icon.classList.contains('fa-chevron-down')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    });
});
</script>
</body>
</html>