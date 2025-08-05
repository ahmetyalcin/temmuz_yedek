<?php
/**
 * DANIŞAN SATIŞ BİLGİLERİ - GÜNCELLENMIŞ
 * ajax/get_danisan_satis.php
 */

session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $satis_id = $_GET['satis_id'] ?? null;
    
    if (empty($satis_id)) {
        throw new Exception('Satış ID gerekli');
    }
    
    // Satış bilgilerini getir
    $sql = "SELECT 
                s.id,
                s.danisan_id,
                s.hizmet_paketi_id,
                s.toplam_tutar,
                s.odenen_tutar,
                s.hediye_seans,
                st.ad as seans_turu,
                st.sure,
                st.seans_adet,
                st.evaluation_interval,
                (st.seans_adet + COALESCE(s.hediye_seans, 0)) as toplam_seans,
                COUNT(r.id) as kullanilan_seans,
                (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) as kalan_seans,
                (s.toplam_tutar - s.odenen_tutar) as kalan_borc
            FROM satislar s
            JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
            LEFT JOIN randevular r ON s.id = r.satis_id AND r.aktif = 1
            WHERE s.id = ? 
            AND s.aktif = 1
            GROUP BY s.id";
    
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$satis_id]);
    $satis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$satis) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu satış için bilgi bulunamadı'
        ]);
        exit;
    }
    
    if ($satis['kalan_seans'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu paketin tüm seansları kullanılmış'
        ]);
        exit;
    }
    
    // Ödeme kontrolü
    $odeme_yuzdesi = ($satis['odenen_tutar'] / $satis['toplam_tutar']) * 100;
    
    if ($odeme_yuzdesi < 50) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu paketin ödemesi yetersiz. Randevu alabilmek için en az %50 ödeme gerekli. Mevcut: %' . number_format($odeme_yuzdesi, 1)
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'satis' => [
            'id' => $satis['id'],
            'toplam_seans' => $satis['toplam_seans'],
            'kullanilan_seans' => $satis['kullanilan_seans'],
            'kalan_seans' => $satis['kalan_seans'],
            'toplam_tutar' => number_format($satis['toplam_tutar'], 2),
            'odenen_tutar' => number_format($satis['odenen_tutar'], 2),
            'kalan_borc' => number_format($satis['kalan_borc'], 2),
            'odeme_yuzdesi' => round($odeme_yuzdesi, 1),
            'seans_turu' => $satis['seans_turu']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Danışan satış bilgisi hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
}
?>