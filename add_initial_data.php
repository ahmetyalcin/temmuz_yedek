<?php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Üyelik Türleri
    $uyelikTurleri = [
        [
            'ad' => 'Bronze',
            'seviye' => 1,
            'min_seans_sayisi' => 4,
            'indirim_yuzdesi' => 5,
            'hediye_seans_sayisi' => 1,
            'hediye_seans_gecerlilik_gun' => 90,
            'aciklama' => 'Temel üyelik seviyesi. %5 indirim ve 1 hediye seans.'
        ],
        [
            'ad' => 'Silver',
            'seviye' => 2,
            'min_seans_sayisi' => 20,
            'indirim_yuzdesi' => 10,
            'hediye_seans_sayisi' => 2,
            'hediye_seans_gecerlilik_gun' => 120,
            'aciklama' => 'Orta seviye üyelik. %10 indirim, 2 hediye seans ve öncelikli randevu.'
        ],
        [
            'ad' => 'Gold',
            'seviye' => 3,
            'min_seans_sayisi' => 30,
            'indirim_yuzdesi' => 20,
            'hediye_seans_sayisi' => 3,
            'hediye_seans_gecerlilik_gun' => 180,
            'aciklama' => 'Yüksek seviye üyelik. %20 indirim, 3 hediye seans ve VIP hizmetler.'
        ],
        [
            'ad' => 'Platinum',
            'seviye' => 4,
            'min_seans_sayisi' => 50,
            'indirim_yuzdesi' => 25,
            'hediye_seans_sayisi' => 5,
            'hediye_seans_gecerlilik_gun' => 365,
            'aciklama' => 'En yüksek seviye üyelik. %25 indirim, 5 hediye seans ve özel ayrıcalıklar.'
        ]
    ];

    $sorgu = $pdo->prepare("
        INSERT INTO uyelik_turleri (
            ad, seviye, min_seans_sayisi, indirim_yuzdesi, 
            hediye_seans_sayisi, hediye_seans_gecerlilik_gun, aciklama
        )
        VALUES (
            :ad, :seviye, :min_seans_sayisi, :indirim_yuzdesi,
            :hediye_seans_sayisi, :hediye_seans_gecerlilik_gun, :aciklama
        )
    ");

    foreach ($uyelikTurleri as $uyelik) {
        $sorgu->execute($uyelik);
    }

    // Seans Türleri
    $seansTurleri = [
        [
            'ad' => 'Deneme Seansı',
            'sure' => 30,
            'fiyat' => 0,
            'deneme_mi' => true,
            'aciklama' => 'Ücretsiz ilk tanışma seansı'
        ],
        [
            'ad' => 'Bireysel Terapi',
            'sure' => 50,
            'fiyat' => 1000,
            'deneme_mi' => false,
            'aciklama' => 'Standart bireysel terapi seansı'
        ],
        [
            'ad' => 'Çift Terapisi',
            'sure' => 80,
            'fiyat' => 1500,
            'deneme_mi' => false,
            'aciklama' => 'Çiftler için terapi seansı'
        ],
        [
            'ad' => 'Aile Terapisi',
            'sure' => 90,
            'fiyat' => 2000,
            'deneme_mi' => false,
            'aciklama' => 'Aile için terapi seansı'
        ],
        [
            'ad' => 'EMDR Seansı',
            'sure' => 90,
            'fiyat' => 1800,
            'deneme_mi' => false,
            'aciklama' => 'EMDR terapi seansı'
        ],
        [
            'ad' => 'Online Terapi',
            'sure' => 45,
            'fiyat' => 800,
            'deneme_mi' => false,
            'aciklama' => 'Online görüşme seansı'
        ],
        [
            'ad' => 'Fix Danışan Seansı',
            'sure' => 50,
            'fiyat' => 900,
            'deneme_mi' => false,
            'aciklama' => 'Düzenli danışanlar için özel seans'
        ],
        [
            'ad' => 'İlk Full Seans',
            'sure' => 90,
            'fiyat' => 1200,
            'deneme_mi' => false,
            'aciklama' => 'Yeni danışanlar için detaylı ilk seans'
        ],
        [
            'ad' => 'Acil Durum Seansı',
            'sure' => 60,
            'fiyat' => 1500,
            'deneme_mi' => false,
            'aciklama' => 'Acil durumlar için öncelikli seans'
        ],
        [
            'ad' => 'Grup Terapisi',
            'sure' => 120,
            'fiyat' => 500,
            'deneme_mi' => false,
            'aciklama' => 'Grup terapi seansı (kişi başı)'
        ],
        [
            'ad' => 'Özel Durum Seansı',
            'sure' => 60,
            'fiyat' => 1300,
            'deneme_mi' => false,
            'aciklama' => 'Özel durumlar için uyarlanmış seans'
        ],
        [
            'ad' => 'Takip Seansı',
            'sure' => 30,
            'fiyat' => 600,
            'deneme_mi' => false,
            'aciklama' => 'Kısa süreli kontrol ve takip seansı'
        ]
    ];

    $sorgu = $pdo->prepare("
        INSERT INTO seans_turleri (ad, sure, fiyat, deneme_mi, aciklama)
        VALUES (:ad, :sure, :fiyat, :deneme_mi, :aciklama)
    ");

    foreach ($seansTurleri as $seans) {
        $sorgu->execute($seans);
    }

    // Örnek Sponsorluklar
    $sponsorluklar = [
        [
            'ad' => 'Kurumsal İndirim A',
            'firma_adi' => 'ABC Şirketi',
            'indirim_yuzdesi' => 15,
            'baslangic_tarihi' => '2024-03-01 00:00:00',
            'bitis_tarihi' => '2024-12-31 23:59:59',
            'aciklama' => 'ABC Şirketi çalışanlarına özel %15 indirim.'
        ],
        [
            'ad' => 'Öğrenci İndirimi',
            'firma_adi' => null,
            'indirim_yuzdesi' => 20,
            'baslangic_tarihi' => '2024-03-01 00:00:00',
            'bitis_tarihi' => null,
            'aciklama' => 'Öğrenci belgesi ile %20 indirim.'
        ],
        [
            'ad' => 'Kurumsal Anlaşma B',
            'firma_adi' => 'XYZ Holding',
            'indirim_yuzdesi' => 25,
            'baslangic_tarihi' => '2024-03-01 00:00:00',
            'bitis_tarihi' => '2024-12-31 23:59:59',
            'aciklama' => 'XYZ Holding çalışanlarına özel %25 indirim.'
        ]
    ];

    $sorgu = $pdo->prepare("
        INSERT INTO sponsorluklar (
            ad, firma_adi, indirim_yuzdesi, baslangic_tarihi, 
            bitis_tarihi, aciklama
        )
        VALUES (
            :ad, :firma_adi, :indirim_yuzdesi, :baslangic_tarihi,
            :bitis_tarihi, :aciklama
        )
    ");

    foreach ($sponsorluklar as $sponsorluk) {
        $sorgu->execute($sponsorluk);
    }

    // Örnek Personel
    $personel = [
        [
            'ad' => 'Admin',
            'soyad' => 'User',
            'email' => 'admin@example.com',
            'telefon' => '5551234567',
            'rol' => 'yonetici'
        ]
    ];

    $sorgu = $pdo->prepare("
        INSERT INTO personel (ad, soyad, email, telefon, rol)
        VALUES (:ad, :soyad, :email, :telefon, :rol)
    ");

    foreach ($personel as $p) {
        $sorgu->execute($p);
    }

    echo "Başlangıç verileri başarıyla eklendi.";
} catch(PDOException $e) {
    die("Veri ekleme hatası: " . $e->getMessage());
}
?>