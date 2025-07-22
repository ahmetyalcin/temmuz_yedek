<?php
// İstatistikleri hesapla
$total_danisan = count($danisanlar);

// Aylık randevu sayısı
$aylik_randevu = 0;
if (!empty($randevular)) {
    $aylik_randevu = count(array_filter($randevular, function($r) {
        return date('m', strtotime($r['randevu_tarihi'])) == date('m');
    }));
}

// Aktif paket sayısı
$aktif_paket = 0;
if (!empty($hizmetPaketleri)) {
    $aktif_paket = count(array_filter($hizmetPaketleri, function($p) {
        return $p['aktif'];
    }));
}

// Ortalama seans süresi
$ortalama_seans = 0;
if (!empty($seansTurleri)) {
    $toplam_sure = array_sum(array_column($seansTurleri, 'sure'));
    $ortalama_seans = round($toplam_sure / count($seansTurleri));
}

// Performans metrikleri
$danisan_artis = getDanisanArtisOrani();
$iptal_orani = getRandevuIptalOrani();
$paket_yenileme_orani = getPaketYenilemeOrani();

// Bildirimler
$yarinki_randevu = getYarinkiRandevuSayisi();
$biten_paket = getBitenPaketSayisi();
$geri_arama = getGeriAramaBekleyenler();

// Fix Talepleri Al
$bugunun_gunu = date('l');
$gunler = [
    'Monday' => 'Pazartesi',
    'Tuesday' => 'Salı',
    'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe',
    'Friday' => 'Cuma',
    'Saturday' => 'Cumartesi',
    'Sunday' => 'Pazar'
];
$bugun = $gunler[$bugunun_gunu];

// Bugünün fix taleplerini getir
$sql = "SELECT ft.*, d.ad as danisan_adi, d.soyad as danisan_soyadi 
        FROM fix_talepler ft 
        JOIN danisanlar d ON d.id = ft.danisan_id 
        WHERE ft.aktif = 1 AND ft.gun = ?
        ORDER BY ft.saat";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bugun]);
    $bugunun_fix_talepleri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Fix talepleri getirme hatası: " . $e->getMessage());
    $bugunun_fix_talepleri = [];
}
?>

<div class="container-fluid">
    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <span class="text-primary">
                                <i class='bx bx-user fs-3'></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Toplam Danışan</h6>
                            <h2 class="mb-0"><?php echo $total_danisan; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <span class="text-success">
                                <i class='bx bx-calendar fs-3'></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Aylık Randevu</h6>
                            <h2 class="mb-0"><?php echo $aylik_randevu; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <span class="text-info">
                                <i class='bx bx-package fs-3'></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Aktif Paketler</h6>
                            <h2 class="mb-0"><?php echo $aktif_paket; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <span class="text-warning">
                                <i class='bx bx-time fs-3'></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Ortalama Seans</h6>
                            <h2 class="mb-0"><?php echo $ortalama_seans; ?> dk</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Performans Metrikleri -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performans Metrikleri</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Yeni Danışan Oranı</span>
                            <span class="<?php echo $danisan_artis >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <i class='bx bx-trending-<?php echo $danisan_artis >= 0 ? 'up' : 'down'; ?>'></i> 
                                <?php echo abs($danisan_artis); ?>%
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?php echo $danisan_artis >= 0 ? 'bg-success' : 'bg-danger'; ?>" 
                                 style="width: <?php echo abs($danisan_artis); ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>İptal Oranı</span>
                            <span class="<?php echo $iptal_orani <= 10 ? 'text-success' : 'text-danger'; ?>">
                                <i class='bx bx-trending-<?php echo $iptal_orani <= 10 ? 'down' : 'up'; ?>'></i> 
                                <?php echo $iptal_orani; ?>%
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?php echo $iptal_orani <= 10 ? 'bg-success' : 'bg-danger'; ?>" 
                                 style="width: <?php echo $iptal_orani; ?>%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paket Yenileme</span>
                            <span class="<?php echo $paket_yenileme_orani >= 50 ? 'text-success' : 'text-danger'; ?>">
                                <i class='bx bx-trending-<?php echo $paket_yenileme_orani >= 50 ? 'up' : 'down'; ?>'></i> 
                                <?php echo $paket_yenileme_orani; ?>%
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?php echo $paket_yenileme_orani >= 50 ? 'bg-success' : 'bg-danger'; ?>" 
                                 style="width: <?php echo $paket_yenileme_orani; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fix Talepler -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Bugünün Fix Talepleri</h5>
                    <a href="?page=randevular&tab=fix_talepler" class="btn btn-primary btn-sm">
                        Tümünü Gör
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($bugunun_fix_talepleri)): ?>
                        <div class="text-center text-muted py-4">
                            <i class='bx bx-calendar-check fs-1'></i>
                            <p class="mt-2">Bugün için fix talep bulunmuyor.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($bugunun_fix_talepleri as $talep): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="p-2 bg-light rounded text-primary">
                                            <?php echo substr($talep['saat'], 0, 5); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo $talep['danisan_adi'] . ' ' . $talep['danisan_soyadi']; ?></h6>
                                        <p class="text-muted mb-0">
                                            <small>
                                                <i class='bx bx-refresh me-1'></i>
                                                <?php echo $talep['tekrar_tipi']; ?>
                                                <?php if ($talep['notlar']): ?>
                                                    <br>
                                                    <i class='bx bx-note me-1'></i>
                                                    <?php echo $talep['notlar']; ?>
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bildirimler -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Bildirimler</h5>
                </div>
                <div class="card-body">
                    <?php if ($yarinki_randevu > 0): ?>
                    <div class="d-flex align-items-start mb-4">
                        <div class="p-2 bg-primary bg-opacity-10 rounded-3 text-primary">
                            <i class='bx bx-calendar fs-4'></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Yarın <?php echo $yarinki_randevu; ?> randevunuz var</h6>
                            <small class="text-muted">5 dakika önce</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($biten_paket > 0): ?>
                    <div class="d-flex align-items-start mb-4">
                        <div class="p-2 bg-warning bg-opacity-10 rounded-3 text-warning">
                            <i class='bx bx-package fs-4'></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1"><?php echo $biten_paket; ?> danışanın paketi bu hafta bitiyor</h6>
                            <small class="text-muted">1 saat önce</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($geri_arama > 0): ?>
                    <div class="d-flex align-items-start">
                        <div class="p-2 bg-info bg-opacity-10 rounded-3 text-info">
                            <i class='bx bx-phone fs-4'></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1"><?php echo $geri_arama; ?> danışan geri arama bekliyor</h6>
                            <small class="text-muted">2 saat önce</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($yarinki_randevu == 0 && $biten_paket == 0 && $geri_arama == 0): ?>
                    <div class="text-center text-muted py-4">
                        <i class='bx bx-check-circle fs-1'></i>
                        <p class="mt-2">Şu anda bildirim bulunmuyor</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Yaklaşan Randevular -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Yaklaşan Randevular</h5>
            <a href="?page=randevular" class="btn btn-primary btn-sm">
                Tümünü Gör
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Danışan</th>
                            <th>Terapist</th>
                            <th>Seans</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($randevular)): ?>
                            <?php foreach(array_slice($randevular, 0, 5) as $randevu): ?>
                            <tr>
                                <td><?php echo $randevu['danisan_adi']; ?></td>
                                <td><?php echo $randevu['personel_adi']; ?></td>
                                <td><?php echo $randevu['seans_turu']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($randevu['randevu_tarihi'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($randevu['durum']); ?>">
                                        <?php echo ucfirst($randevu['durum']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=randevular&action=edit&id=<?php echo $randevu['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger"
                                                onclick="if(confirm('Bu randevuyu iptal etmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=randevular&action=cancel&id=<?php echo $randevu['id']; ?>'">
                                            <i class="bx bx-x"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Yaklaşan randevu bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>