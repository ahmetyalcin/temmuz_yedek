<?php
include 'con/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Üyelik Türleri Tablosu (Bağımlılığı olmayan ilk tablo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS uyelik_turleri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(50) NOT NULL,
        seviye INT NOT NULL,
        min_seans_sayisi INT NOT NULL,
        indirim_yuzdesi INT NOT NULL DEFAULT 0,
        hediye_seans_sayisi INT NOT NULL DEFAULT 0,
        hediye_seans_gecerlilik_gun INT NOT NULL DEFAULT 90,
        aciklama TEXT,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seans Türleri Tablosu (Bağımlılığı olmayan ikinci tablo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS seans_turleri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(100) NOT NULL,
        sure INT NOT NULL,
        fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
        deneme_mi BOOLEAN DEFAULT FALSE,
        aciklama TEXT,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Personel Tablosu (Bağımlılığı olmayan üçüncü tablo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS personel (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(50) NOT NULL,
        soyad VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        telefon VARCHAR(20),
        rol ENUM('terapist', 'yonetici', 'satis') NOT NULL,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Danışanlar Tablosu (uyelik_turleri'ne bağımlı)
    $pdo->exec("CREATE TABLE IF NOT EXISTS danisanlar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(50) NOT NULL,
        soyad VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        telefon VARCHAR(20),
        uyelik_turu_id INT,
        toplam_seans_sayisi INT DEFAULT 0,
        deneme_seans_kullanildi BOOLEAN DEFAULT FALSE,
        deneme_seans_tarihi TIMESTAMP NULL,
        deneme_seans_durumu ENUM('beklemede', 'geldi_aldi', 'geldi_almadi', 'gelmedi', 'ertelendi') DEFAULT 'beklemede',
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (uyelik_turu_id) REFERENCES uyelik_turleri(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Hizmet Paketleri Tablosu (seans_turleri'ne bağımlı)
    $pdo->exec("CREATE TABLE IF NOT EXISTS hizmet_paketleri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(100) NOT NULL,
        seans_turu_id INT NOT NULL,
        seans_sayisi INT NOT NULL,
        fiyat DECIMAL(10,2) NOT NULL,
        gecerlilik_gun INT NOT NULL,
        aciklama TEXT,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seans_turu_id) REFERENCES seans_turleri(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Sponsorluklar Tablosu (bağımsız tablo)
    $pdo->exec("CREATE TABLE IF NOT EXISTS sponsorluklar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad VARCHAR(100) NOT NULL,
        firma_adi VARCHAR(100),
        indirim_yuzdesi INT NOT NULL,
        baslangic_tarihi TIMESTAMP NOT NULL,
        bitis_tarihi TIMESTAMP,
        aciklama TEXT,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Sponsorluk-Seans Türü İlişki Tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS sponsorluk_seans_turleri (
        sponsorluk_id INT NOT NULL,
        seans_turu_id INT NOT NULL,
        PRIMARY KEY (sponsorluk_id, seans_turu_id),
        FOREIGN KEY (sponsorluk_id) REFERENCES sponsorluklar(id),
        FOREIGN KEY (seans_turu_id) REFERENCES seans_turleri(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Satışlar Tablosu (çoklu bağımlılık)
    $pdo->exec("CREATE TABLE IF NOT EXISTS satislar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        danisan_id INT NOT NULL,
        hizmet_paketi_id INT NOT NULL,
        personel_id INT NOT NULL,
        sponsorluk_id INT,
        miktar INT NOT NULL DEFAULT 1,
        birim_fiyat DECIMAL(10,2) NOT NULL,
        indirim_tutari DECIMAL(10,2) DEFAULT 0,
        toplam_tutar DECIMAL(10,2) NOT NULL,
        son_kullanma_tarihi TIMESTAMP NOT NULL,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (danisan_id) REFERENCES danisanlar(id),
        FOREIGN KEY (hizmet_paketi_id) REFERENCES hizmet_paketleri(id),
        FOREIGN KEY (personel_id) REFERENCES personel(id),
        FOREIGN KEY (sponsorluk_id) REFERENCES sponsorluklar(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Hediye Seanslar Tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS hediye_seanslar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        danisan_id INT NOT NULL,
        seans_turu_id INT NOT NULL,
        satis_id INT NOT NULL,
        miktar INT NOT NULL DEFAULT 1,
        kullanildi BOOLEAN DEFAULT FALSE,
        son_kullanma_tarihi TIMESTAMP NOT NULL,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (danisan_id) REFERENCES danisanlar(id),
        FOREIGN KEY (seans_turu_id) REFERENCES seans_turleri(id),
        FOREIGN KEY (satis_id) REFERENCES satislar(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Fiyat Değişiklik Logları Tablosu
    $pdo->exec("CREATE TABLE IF NOT EXISTS fiyat_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tablo_adi ENUM('seans_turleri', 'hizmet_paketleri') NOT NULL,
        kayit_id INT NOT NULL,
        eski_fiyat DECIMAL(10,2),
        yeni_fiyat DECIMAL(10,2),
        degistiren_personel_id INT,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (degistiren_personel_id) REFERENCES personel(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Randevular Tablosu (çoklu bağımlılık)
    $pdo->exec("CREATE TABLE IF NOT EXISTS randevular (
        id INT AUTO_INCREMENT PRIMARY KEY,
        danisan_id INT NOT NULL,
        personel_id INT NOT NULL,
        seans_turu_id INT NOT NULL,
        satis_id INT,
        hediye_seans_id INT,
        randevu_tarihi TIMESTAMP NOT NULL,
        durum ENUM('beklemede', 'onaylandi', 'iptal_edildi', 'tamamlandi', 'ertelendi') DEFAULT 'beklemede',
        notlar TEXT,
        aktif BOOLEAN DEFAULT TRUE,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (danisan_id) REFERENCES danisanlar(id),
        FOREIGN KEY (personel_id) REFERENCES personel(id),
        FOREIGN KEY (seans_turu_id) REFERENCES seans_turleri(id),
        FOREIGN KEY (satis_id) REFERENCES satislar(id),
        FOREIGN KEY (hediye_seans_id) REFERENCES hediye_seanslar(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Tablolar başarıyla oluşturuldu.";
} catch(PDOException $e) {
    die("Tablo oluşturma hatası: " . $e->getMessage());
}
?>