<?php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $seansTurleri = [
        [
            'ad' => 'İlk Tanışma Seansı',
            'sure' => 30,
            'aciklama' => 'Danışanla ilk tanışma ve değerlendirme seansı',
            'deneme_mi' => true
        ],
        [
            'ad' => 'Bireysel Terapi',
            'sure' => 50,
            'aciklama' => 'Standart bireysel terapi seansı',
            'deneme_mi' => false
        ],
        [
            'ad' => 'Çift Terapisi',
            'sure' => 80,
            'aciklama' => 'Çiftler için terapi seansı',
            'deneme_mi' => false
        ],
        [
            'ad' => 'Aile Terapisi',
            'sure' => 90,
            'aciklama' => 'Aile için terapi seansı',
            'deneme_mi' => false
        ],
        [
            'ad' => 'EMDR Seansı',
            'sure' => 90,
            'aciklama' => 'EMDR terapi seansı',
            'deneme_mi' => false
        ],
        [
            'ad' => 'Online Terapi',
            'sure' => 45,
            'aciklama' => 'Online görüşme seansı',
            'deneme_mi' => false
        ],
        [
            'ad' => 'Kısa Seans',
            'sure' => 30,
            'aciklama' => 'Takip ve kontrol seansı',
            'deneme_mi' => false
        ]
    ];

    $sorgu = $pdo->prepare("
        INSERT INTO seans_turleri (ad, sure, aciklama, deneme_mi)
        VALUES (:ad, :sure, :aciklama, :deneme_mi)
    ");

    foreach ($seansTurleri as $seans) {
        $sorgu->execute([
            'ad' => $seans['ad'],
            'sure' => $seans['sure'],
            'aciklama' => $seans['aciklama'],
            'deneme_mi' => $seans['deneme_mi']
        ]);
    }

    echo "Seans türleri başarıyla eklendi.";
} catch(PDOException $e) {
    die("Hata oluştu: " . $e->getMessage());
}
?>