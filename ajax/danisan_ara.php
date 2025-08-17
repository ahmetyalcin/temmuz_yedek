<?php
// ajax/danisan_ara.php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    // POST verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    $arama_terimi = $input['arama'] ?? '';
    
    if (empty($arama_terimi) || strlen($arama_terimi) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'En az 2 karakter girmelisiniz'
        ]);
        exit;
    }
    
    // Danışan arama fonksiyonunu çağır
    $danisanlar = danisanAra($arama_terimi, 10);
    
    echo json_encode([
        'success' => true,
        'danisanlar' => $danisanlar,
        'toplam' => count($danisanlar)
    ]);
    
} catch (Exception $e) {
    error_log("Danışan arama AJAX hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Arama sırasında hata oluştu'
    ]);
}
?>

---

<?php
// ajax/get_dashboard_stats.php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    // İstatistikleri getir
    $stats = getSeansIstatistikleri();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard stats AJAX hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İstatistikler yüklenirken hata oluştu'
    ]);
}
?>

---

<?php
// ajax/get_danisan_paket_detay.php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $danisan_id = $_GET['danisan_id'] ?? null;
    
    if (!$danisan_id) {
        throw new Exception('Danışan ID gerekli');
    }
    
    // Danışan bilgilerini al
    $danisan = getDanisanDetay($danisan_id);
    if (!$danisan) {
        throw new Exception('Danışan bulunamadı');
    }
    
    // Seans özetini al
    $seans_ozeti = getDanisanSeansOzeti($danisan_id);
    
    // Son 5 randevuyu al
    global $pdo;
    $sql = "SELECT 
                r.randevu_tarihi,
                r.durum,
                CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                st.ad as seans_turu
            FROM randevular r
            JOIN satislar s ON r.satis_id = s.id
            LEFT JOIN personel p ON r.personel_id = p.id
            LEFT JOIN seans_turleri st ON r.seans_turu_id = st.id
            WHERE s.danisan_id = :danisan_id AND r.aktif = 1
            ORDER BY r.randevu_tarihi DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['danisan_id' => $danisan_id]);
    $son_randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'danisan' => $danisan,
        'seans_ozeti' => $seans_ozeti,
        'son_randevular' => $son_randevular
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

---

<?php
// ajax/get_paket_satis_trendi.php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $ay_sayisi = $_GET['ay_sayisi'] ?? 6;
    
    $trend_verileri = getPaketSatisTrendi($ay_sayisi);
    
    // Chart.js için veri formatla
    $chart_data = [];
    $aylar = [];
    $paket_adlari = [];
    
    foreach ($trend_verileri as $veri) {
        $ay = $veri['ay'];
        $paket = $veri['paket_adi'];
        
        if (!in_array($ay, $aylar)) {
            $aylar[] = $ay;
        }
        
        if (!in_array($paket, $paket_adlari)) {
            $paket_adlari[] = $paket;
        }
        
        $chart_data[$paket][$ay] = $veri['satis_sayisi'];
    }
    
    // Eksik ayları sıfırla doldur
    foreach ($paket_adlari as $paket) {
        foreach ($aylar as $ay) {
            if (!isset($chart_data[$paket][$ay])) {
                $chart_data[$paket][$ay] = 0;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'aylar' => $aylar,
        'paketler' => $paket_adlari,
        'veriler' => $chart_data,
        'ham_veri' => $trend_verileri
    ]);
    
} catch (Exception $e) {
    error_log("Paket satış trendi AJAX hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Trend verileri yüklenirken hata oluştu'
    ]);
}
?>

---

<?php
// ajax/update_randevu_durum.php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

try {
    $randevu_id = $_POST['randevu_id'] ?? null;
    $yeni_durum = $_POST['durum'] ?? null;
    $notlar = $_POST['notlar'] ?? null;
    
    if (!$randevu_id || !$yeni_durum) {
        throw new Exception('Randevu ID ve durum gerekli');
    }
    
    // Geçerli durumlar
    $gecerli_durumlar = ['beklemede', 'geldi', 'gelmedi', 'iptal'];
    if (!in_array($yeni_durum, $gecerli_durumlar)) {
        throw new Exception('Geçersiz durum');
    }
    
    global $pdo;
    
    // Randevu durumunu güncelle
    $sql = "UPDATE randevular 
            SET durum = :durum, 
                notlar = :notlar,
                guncelleme_tarihi = CURRENT_TIMESTAMP 
            WHERE id = :randevu_id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'durum' => $yeni_durum,
        'notlar' => $notlar,
        'randevu_id' => $randevu_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Randevu durumu güncellendi'
        ]);
    } else {
        throw new Exception('Güncelleme başarısız');
    }
    
} catch (Exception $e) {
    error_log("Randevu durum güncelleme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

---

<?php
// ajax/get_monthly_stats.php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $ay_sayisi = $_GET['ay_sayisi'] ?? 12;
    
    // Aylık performans verilerini al
    $performans_verileri = getAylikSeansPerformansi($ay_sayisi);
    
    // Chart.js için formatla
    $aylar = [];
    $toplam_randevular = [];
    $gelen_randevular = [];
    $basari_oranlari = [];
    
    foreach ($performans_verileri as $veri) {
        $aylar[] = date('M Y', strtotime($veri['ay'] . '-01'));
        $toplam_randevular[] = (int)$veri['toplam_randevu'];
        $gelen_randevular[] = (int)$veri['gelen_randevu'];
        $basari_oranlari[] = (float)$veri['basari_orani'];
    }
    
    echo json_encode([
        'success' => true,
        'labels' => array_reverse($aylar), // Eskiden yeniye
        'datasets' => [
            [
                'label' => 'Toplam Randevu',
                'data' => array_reverse($toplam_randevular),
                'backgroundColor' => 'rgba(54, 162, 235, 0.8)'
            ],
            [
                'label' => 'Gelen Randevu',
                'data' => array_reverse($gelen_randevular),
                'backgroundColor' => 'rgba(75, 192, 192, 0.8)'
            ]
        ],
        'basari_oranlari' => array_reverse($basari_oranlari)
    ]);
    
} catch (Exception $e) {
    error_log("Aylık istatistik AJAX hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Aylık veriler yüklenirken hata oluştu'
    ]);
}
?>

---

<?php
// ajax/export_seans_raporu.php
session_start();
require_once '../functions.php';

// Excel export için gerekli header'lar
if (isset($_GET['format']) && $_GET['format'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="danisan-seans-raporu-' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

try {
    $danisan_id = $_GET['danisan_id'] ?? null;
    
    if (!$danisan_id) {
        die('Danışan ID gerekli');
    }
    
    // Danışan ve seans verilerini al
    $danisan = getDanisanDetay($danisan_id);
    if (!$danisan) {
        die('Danışan bulunamadı');
    }
    
    // Seans raporunu al (toplu rapor sayfasından fonksiyonu kullan)
    $seans_raporu = getDanisanSeansRaporu($danisan_id);
    
    echo '
    <table border="1">
        <tr>
            <td colspan="10" align="center"><h2>' . $danisan['ad'] . ' ' . $danisan['soyad'] . ' - Seans Raporu</h2></td>
        </tr>
        <tr>
            <td colspan="10">Rapor Tarihi: ' . date('d.m.Y H:i') . '</td>
        </tr>
        <tr><td colspan="10">&nbsp;</td></tr>
        <tr bgcolor="#cccccc">
            <th>Paket Adı</th>
            <th>Satış Tarihi</th>
            <th>Satan Personel</th>
            <th>Toplam Seans</th>
            <th>Kullanılan</th>
            <th>Kalan</th>
            <th>Toplam Tutar</th>
            <th>Ödenen</th>
            <th>Kalan Borç</th>
            <th>Durum</th>
        </tr>';
    
    foreach ($seans_raporu as $paket) {
        echo '<tr>';
        echo '<td>' . $paket['paket_adi'] . '</td>';
        echo '<td>' . date('d.m.Y', strtotime($paket['satis_tarihi'])) . '</td>';
        echo '<td>' . $paket['satan_personel'] . '</td>';
        echo '<td>' . $paket['toplam_seans'] . '</td>';
        echo '<td>' . $paket['kullanilan_seans'] . '</td>';
        echo '<td>' . $paket['kalan_seans'] . '</td>';
        echo '<td>' . number_format($paket['toplam_tutar'], 2) . ' ₺</td>';
        echo '<td>' . number_format($paket['odenen_tutar'], 2) . ' ₺</td>';
        echo '<td>' . number_format($paket['kalan_borc'], 2) . ' ₺</td>';
        echo '<td>' . $paket['paket_durumu'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (Exception $e) {
    die('Export hatası: ' . $e->getMessage());
}
?>