<?php
// ajax_aktivite_raporu.php
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// DataTables parametreleri
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search_value = $_POST['search']['value'] ?? '';
$order_column = intval($_POST['order'][0]['column'] ?? 4);
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';

// Filtreleme parametreleri
$baslangic_tarih = $_POST['baslangic_tarih'] ?? date('Y-m-d', strtotime('-1 year'));
$bitis_tarih = $_POST['bitis_tarih'] ?? date('Y-m-d');
$minimum_gun = intval($_POST['minimum_gun'] ?? 30);

// Sütun mapping
$columns = [
    0 => 'danisan_adi',
    1 => 'telefon',
    2 => 'kayit_tarihi', 
    3 => 'son_randevu_tarihi',
    4 => 'gun_farki',
    5 => 'toplam_paket_sayisi',
    6 => 'son_terapist',
    7 => 'aktivite_durumu'
];

$order_column_name = $columns[$order_column] ?? 'gun_farki';

try {
    // Ana sorgu - toplam kayıt sayısı
    $total_query = "
        SELECT COUNT(DISTINCT d.id) as total
        FROM danisanlar d
        LEFT JOIN randevular r ON d.id = r.danisan_id AND r.durum != 'iptal'
        LEFT JOIN satis s ON d.id = s.danisan_id AND s.aktif = 1
        WHERE d.kayit_tarihi BETWEEN ? AND ?
        AND (
            r.randevu_tarihi IS NULL 
            OR DATEDIFF(CURDATE(), MAX(r.randevu_tarihi)) >= ?
        )
    ";
    
    $stmt = $pdo->prepare($total_query);
    $stmt->execute([$baslangic_tarih, $bitis_tarih, $minimum_gun]);
    $total_records = $stmt->fetchColumn();

    // Filtrelenmiş sorgu
    $filtered_query = "
        SELECT 
            d.id as danisan_id,
            CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
            d.telefon,
            d.email,
            d.kayit_tarihi,
            MAX(r.randevu_tarihi) as son_randevu_tarihi,
            CASE 
                WHEN MAX(r.randevu_tarihi) IS NULL THEN NULL
                ELSE DATEDIFF(CURDATE(), MAX(r.randevu_tarihi))
            END as gun_farki,
            COUNT(DISTINCT s.id) as toplam_paket_sayisi,
            COUNT(DISTINCT r.id) as toplam_randevu_sayisi,
            GROUP_CONCAT(DISTINCT hp.ad SEPARATOR ', ') as alinan_paketler,
            (SELECT CONCAT(p.ad, ' ', p.soyad) 
             FROM personel p 
             JOIN randevular r2 ON p.id = r2.personel_id 
             WHERE r2.danisan_id = d.id AND r2.durum != 'iptal'
             ORDER BY r2.randevu_tarihi DESC LIMIT 1) as son_terapist,
            CASE 
                WHEN MAX(r.randevu_tarihi) IS NULL THEN 'Hiç Randevu Almamış'
                WHEN DATEDIFF(CURDATE(), MAX(r.randevu_tarihi)) > 180 THEN 'Çok Uzun Süreli'
                WHEN DATEDIFF(CURDATE(), MAX(r.randevu_tarihi)) > 90 THEN 'Uzun Süreli'
                WHEN DATEDIFF(CURDATE(), MAX(r.randevu_tarihi)) > 30 THEN 'Orta Süreli'
                ELSE 'Normal'
            END as aktivite_durumu
        FROM danisanlar d
        LEFT JOIN randevular r ON d.id = r.danisan_id AND r.durum != 'iptal'
        LEFT JOIN satis s ON d.id = s.danisan_id AND s.aktif = 1
        LEFT JOIN hizmet_paketleri hp ON s.hizmet_paketi_id = hp.id
        WHERE d.kayit_tarihi BETWEEN ? AND ?
        GROUP BY d.id
        HAVING (
            son_randevu_tarihi IS NULL 
            OR gun_farki >= ?
        )
    ";

    // Arama filtresi ekle
    $params = [$baslangic_tarih, $bitis_tarih, $minimum_gun];
    if (!empty($search_value)) {
        $filtered_query .= " AND (
            CONCAT(d.ad, ' ', d.soyad) LIKE ? 
            OR d.telefon LIKE ? 
            OR d.email LIKE ?
        )";
        $search_param = "%{$search_value}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Sıralama ekle
    $filtered_query .= " ORDER BY {$order_column_name} {$order_dir}";
    
    // Limit ekle
    $filtered_query .= " LIMIT {$start}, {$length}";

    $stmt = $pdo->prepare($filtered_query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filtrelenmiş toplam kayıt sayısı
    $filtered_count_query = str_replace('LIMIT ' . $start . ', ' . $length, '', $filtered_query);
    $filtered_count_query = preg_replace('/ORDER BY .+/', '', $filtered_count_query);
    $filtered_count_query = "SELECT COUNT(*) FROM ({$filtered_count_query}) as temp";
    
    $stmt = $pdo->prepare($filtered_count_query);
    $stmt->execute($params);
    $filtered_records = $stmt->fetchColumn();

    // DataTables formatında veriyi hazırla
    $response_data = [];
    foreach ($data as $row) {
        $gun_farki_badge = '';
        if ($row['gun_farki']) {
            $badge_class = $row['gun_farki'] > 90 ? 'bg-danger' : 
                          ($row['gun_farki'] > 60 ? 'bg-warning text-dark' : 'bg-success');
            $gun_farki_badge = "<span class='badge {$badge_class}'>{$row['gun_farki']} gün</span>";
        } else {
            $gun_farki_badge = "<span class='text-muted'>-</span>";
        }

        $aktivite_badge_class = match($row['aktivite_durumu']) {
            'Hiç Randevu Almamış', 'Çok Uzun Süreli' => 'bg-danger',
            'Uzun Süreli' => 'bg-warning text-dark',
            'Orta Süreli' => 'bg-info',
            default => 'bg-success'
        };

        $durum_kisa = str_replace(['Hiç Randevu Almamış', 'Çok Uzun Süreli', 'Uzun Süreli', 'Orta Süreli'], 
                                 ['Hiç Randevu', 'Çok Uzun', 'Uzun', 'Orta'], $row['aktivite_durumu']);

        $email_button = $row['email'] ? 
            "<a href='mailto:{$row['email']}' class='btn btn-outline-primary btn-sm me-1' title='Email Gönder'>
                <i class='bx bx-envelope'></i>
            </a>" : '';

        $response_data[] = [
            "<strong class='text-primary'>" . htmlspecialchars($row['danisan_adi']) . "</strong>",
            "<div class='d-flex flex-column'>
                <small class='mb-1'>
                    <i class='bx bx-phone text-success me-1'></i>
                    <a href='tel:{$row['telefon']}' class='text-decoration-none'>" . htmlspecialchars($row['telefon']) . "</a>
                </small>" .
                ($row['email'] ? "<small><i class='bx bx-envelope text-primary me-1'></i>" . htmlspecialchars($row['email']) . "</small>" : "") .
            "</div>",
            "<small class='text-muted'>" . date('d.m.Y', strtotime($row['kayit_tarihi'])) . "</small>",
            $row['son_randevu_tarihi'] ? 
                "<small class='text-info'>" . date('d.m.Y', strtotime($row['son_randevu_tarihi'])) . "</small>" :
                "<span class='badge bg-warning text-dark'>Hiç randevu yok</span>",
            $gun_farki_badge,
            "<div class='d-flex flex-column'>
                <small><strong>P:</strong> {$row['toplam_paket_sayisi']}</small>
                <small><strong>R:</strong> {$row['toplam_randevu_sayisi']}</small>
            </div>",
            "<small class='text-muted'>" . htmlspecialchars($row['son_terapist'] ?? 'Yok') . "</small>",
            "<span class='badge {$aktivite_badge_class}'>{$durum_kisa}</span>",
            "<div class='btn-group btn-group-sm' role='group'>
                <a href='tel:{$row['telefon']}' class='btn btn-outline-success btn-sm me-1' title='Ara'>
                    <i class='bx bx-phone'></i>
                </a>
                {$email_button}
                <a href='room_schedule.php?danisan_id={$row['danisan_id']}' class='btn btn-outline-info btn-sm' title='Randevu Ver'>
                    <i class='bx bx-calendar-plus'></i>
                </a>
            </div>"
        ];
    }

    // JSON response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($total_records),
        'recordsFiltered' => intval($filtered_records),
        'data' => $response_data
    ]);

} catch (Exception $e) {
    error_log('AJAX Aktivite Raporu Hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>