<?php
// ajax_aktivite_raporu.php - Basit Test Versiyonu
session_start();

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers (gerekirse)
header('Content-Type: application/json');

// Basit yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    // Test verileri
    $test_data = [
        [
            "<strong class='text-primary'>Test Danışan 1</strong>",
            "<div class='d-flex flex-column'>
                <small class='mb-1'>
                    <i class='bx bx-phone text-success me-1'></i>
                    <a href='tel:05551234567' class='text-decoration-none'>0555 123 45 67</a>
                </small>
                <small><i class='bx bx-envelope text-primary me-1'></i>test@email.com</small>
            </div>",
            "<small class='text-muted'>01.01.2023</small>",
            "<small class='text-info'>15.12.2024</small>",
            "<span class='badge bg-warning text-dark'>45 gün</span>",
            "<div class='d-flex flex-column'>
                <small><strong>P:</strong> 2</small>
                <small><strong>R:</strong> 8</small>
            </div>",
            "<small class='text-muted'>Dr. Test</small>",
            "<span class='badge bg-warning text-dark'>Uzun</span>",
            "<div class='btn-group btn-group-sm' role='group'>
                <a href='tel:05551234567' class='btn btn-outline-success btn-sm me-1' title='Ara'>
                    <i class='bx bx-phone'></i>
                </a>
                <a href='mailto:test@email.com' class='btn btn-outline-primary btn-sm me-1' title='Email Gönder'>
                    <i class='bx bx-envelope'></i>
                </a>
                <a href='room_schedule.php?danisan_id=1' class='btn btn-outline-info btn-sm' title='Randevu Ver'>
                    <i class='bx bx-calendar-plus'></i>
                </a>
            </div>"
        ],
        [
            "<strong class='text-primary'>Test Danışan 2</strong>",
            "<div class='d-flex flex-column'>
                <small class='mb-1'>
                    <i class='bx bx-phone text-success me-1'></i>
                    <a href='tel:05557654321' class='text-decoration-none'>0555 765 43 21</a>
                </small>
            </div>",
            "<small class='text-muted'>15.02.2023</small>",
            "<span class='badge bg-warning text-dark'>Hiç randevu yok</span>",
            "<span class='text-muted'>-</span>",
            "<div class='d-flex flex-column'>
                <small><strong>P:</strong> 0</small>
                <small><strong>R:</strong> 0</small>
            </div>",
            "<small class='text-muted'>Yok</small>",
            "<span class='badge bg-danger'>Hiç Randevu</span>",
            "<div class='btn-group btn-group-sm' role='group'>
                <a href='tel:05557654321' class='btn btn-outline-success btn-sm me-1' title='Ara'>
                    <i class='bx bx-phone'></i>
                </a>
                <a href='room_schedule.php?danisan_id=2' class='btn btn-outline-info btn-sm' title='Randevu Ver'>
                    <i class='bx bx-calendar-plus'></i>
                </a>
            </div>"
        ]
    ];

    // DataTables parametreleri
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    
    // Sayfalama simülasyonu
    $total_records = 50; // Toplam kayıt sayısı
    $filtered_records = 25; // Filtrelenmiş kayıt sayısı
    
    // Test verilerini sayfalama ile döndür
    $page_data = array_slice($test_data, $start, $length);
    
    // JSON response
    $response = [
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $filtered_records,
        'data' => $page_data
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'Test hatası: ' . $e->getMessage(),
        'draw' => intval($_POST['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
?>