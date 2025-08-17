<?php
// sadakat_sistemi.php - Müşteri Sadakat Programı

class MusteriSadakatSistemi {
    private $pdo;
    
    // Puan kazanma nedenleri
    const PUAN_TURU = [
        'seans_tamamlama' => 10,        // Her seans tamamlama
        'zamaninda_gelme' => 5,         // Zamanında gelme bonusu
        'referans_getirme' => 50,       // Yeni müşteri getirme
        'degerlendirme_yapma' => 15,    // Seans değerlendirmesi yapma
        'sosyal_medya_paylasum' => 20,  // Sosyal medya paylaşımı
        'dogum_gunu_bonus' => 100,      // Doğum günü bonusu
        'yildonumu_bonus' => 75,        // Yıl dönümü bonusu
        'paket_yenileme' => 25,         // Paket yenileme bonusu
        'online_odeme' => 10,           // Online ödeme bonusu
        'anket_doldurma' => 30          // Memnuniyet anketi doldurma
    ];
    
    // Seviye sistemi
    const SEVIYELER = [
        1 => ['min_puan' => 0, 'ad' => 'Bronz', 'indirim' => 0, 'hediye_seans' => 0],
        2 => ['min_puan' => 100, 'ad' => 'Gümüş', 'indirim' => 5, 'hediye_seans' => 1],
        3 => ['min_puan' => 300, 'ad' => 'Altın', 'indirim' => 10, 'hediye_seans' => 2],
        4 => ['min_puan' => 600, 'ad' => 'Platin', 'indirim' => 15, 'hediye_seans' => 3],
        5 => ['min_puan' => 1000, 'ad' => 'Elmas', 'indirim' => 20, 'hediye_seans' => 5]
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Müşteriye puan ekle
     */
    public function puanEkle($danisan_id, $puan_turu, $miktar = null, $aciklama = '', $referans_id = null) {
        if (!isset(self::PUAN_TURU[$puan_turu])) {
            throw new Exception("Geçersiz puan türü: " . $puan_turu);
        }
        
        $puan = $miktar ?: self::PUAN_TURU[$puan_turu];
        
        try {
            $this->pdo->beginTransaction();
            
            // Puan geçmişine kaydet
            $sql = "INSERT INTO sadakat_puan_gecmisi 
                    (danisan_id, puan_turu, puan_miktari, aciklama, referans_id, olusturma_tarihi) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$danisan_id, $puan_turu, $puan, $aciklama, $referans_id]);
            
            // Toplam puanı güncelle
            $sql = "UPDATE danisanlar SET 
                    sadakat_puani = sadakat_puani + ?, 
                    son_puan_tarihi = NOW() 
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$puan, $danisan_id]);
            
            // Seviye kontrolü yap
            $this->seviyeKontrolEt($danisan_id);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'eklenen_puan' => $puan,
                'toplam_puan' => $this->getMusteriPuani($danisan_id)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Puan harca (hediye, indirim vb.)
     */
    public function puanHarca($danisan_id, $miktar, $aciklama, $referans_id = null) {
        $mevcutPuan = $this->getMusteriPuani($danisan_id);
        
        if ($mevcutPuan < $miktar) {
            throw new Exception("Yetersiz puan. Mevcut: {$mevcutPuan}, Gerekli: {$miktar}");
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Puan harcama kaydı
            $sql = "INSERT INTO sadakat_puan_gecmisi 
                    (danisan_id, puan_turu, puan_miktari, aciklama, referans_id, olusturma_tarihi) 
                    VALUES (?, 'harcama', ?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$danisan_id, -$miktar, $aciklama, $referans_id]);
            
            // Toplam puanı güncelle
            $sql = "UPDATE danisanlar SET sadakat_puani = sadakat_puani - ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$miktar, $danisan_id]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'harcanan_puan' => $miktar,
                'kalan_puan' => $this->getMusteriPuani($danisan_id)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Müşteri seviyesini kontrol et ve güncelle
     */
    private function seviyeKontrolEt($danisan_id) {
        $mevcutPuan = $this->getMusteriPuani($danisan_id);
        $yeniSeviye = $this->puanaGoreSeviye($mevcutPuan);
        
        // Mevcut seviyeyi al
        $sql = "SELECT sadakat_seviyesi FROM danisanlar WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $mevcutSeviye = $stmt->fetchColumn() ?: 1;
        
        if ($yeniSeviye > $mevcutSeviye) {
            // Seviye yükseldi
            $sql = "UPDATE danisanlar SET sadakat_seviyesi = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$yeniSeviye, $danisan_id]);
            
            // Seviye yükselme bonusu
            $bonus = self::SEVIYELER[$yeniSeviye]['hediye_seans'] * 10; // Her hediye seans için 10 puan
            if ($bonus > 0) {
                $this->puanEkle($danisan_id, 'seviye_bonus', $bonus, 
                    "Seviye yükselme bonusu: " . self::SEVIYELER[$yeniSeviye]['ad']);
            }
            
            // Bildirim gönder
            $this->seviyeYukselmeBildirimiGonder($danisan_id, $yeniSeviye);
        }
    }
    
    /**
     * Puana göre seviye belirle
     */
    private function puanaGoreSeviye($puan) {
        $seviye = 1;
        foreach (self::SEVIYELER as $s => $bilgi) {
            if ($puan >= $bilgi['min_puan']) {
                $seviye = $s;
            }
        }
        return $seviye;
    }
    
    /**
     * Müşteri puanını getir
     */
    public function getMusteriPuani($danisan_id) {
        $sql = "SELECT sadakat_puani FROM danisanlar WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    /**
     * Müşteri seviye bilgilerini getir
     */
    public function getMusteriSeviyeBilgileri($danisan_id) {
        $sql = "SELECT 
                    sadakat_puani, 
                    sadakat_seviyesi,
                    son_puan_tarihi
                FROM danisanlar WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $musteri = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$musteri) {
            return null;
        }
        
        $mevcutSeviye = $musteri['sadakat_seviyesi'] ?: 1;
        $mevcutPuan = $musteri['sadakat_puani'] ?: 0;
        
        // Sonraki seviye bilgileri
        $sonrakiSeviye = $mevcutSeviye < 5 ? $mevcutSeviye + 1 : null;
        $sonrakiSeviyeIcinGerekenPuan = $sonrakiSeviye ? 
            self::SEVIYELER[$sonrakiSeviye]['min_puan'] - $mevcutPuan : 0;
        
        return [
            'mevcut_puan' => $mevcutPuan,
            'mevcut_seviye' => $mevcutSeviye,
            'mevcut_seviye_adi' => self::SEVIYELER[$mevcutSeviye]['ad'],
            'mevcut_seviye_indirim' => self::SEVIYELER[$mevcutSeviye]['indirim'],
            'sonraki_seviye' => $sonrakiSeviye,
            'sonraki_seviye_adi' => $sonrakiSeviye ? self::SEVIYELER[$sonrakiSeviye]['ad'] : null,
            'sonraki_seviye_icin_gereken_puan' => $sonrakiSeviyeIcinGerekenPuan,
            'son_puan_tarihi' => $musteri['son_puan_tarihi']
        ];
    }
    
    /**
     * Puan geçmişini getir
     */
    public function getPuanGecmisi($danisan_id, $limit = 20) {
        $sql = "SELECT * FROM sadakat_puan_gecmisi 
                WHERE danisan_id = ? 
                ORDER BY olusturma_tarihi DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Otomatik puan kazandırma - seans tamamlama
     */
    public function seansTamamlamaPuani($randevu_id) {
        $sql = "SELECT r.danisan_id, r.randevu_tarihi, 
                       CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                       st.ad as seans_turu
                FROM randevular r
                JOIN danisanlar d ON r.danisan_id = d.id
                JOIN seans_turleri st ON r.seans_turu_id = st.id
                WHERE r.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$randevu_id]);
        $randevu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$randevu) {
            return false;
        }
        
        // Seans tamamlama puanı
        $this->puanEkle(
            $randevu['danisan_id'], 
            'seans_tamamlama', 
            null, 
            "Seans tamamlama: " . $randevu['seans_turu'],
            $randevu_id
        );
        
        // Zamanında gelme bonusu kontrolü (randevu saatinden 5 dk önce - 10 dk sonra arası)
        $randevu_zamani = strtotime($randevu['randevu_tarihi']);
        $suanki_zaman = time();
        $fark = abs($suanki_zaman - $randevu_zamani) / 60; // dakika cinsinden
        
        if ($fark <= 15) { // 15 dakika tolerans
            $this->puanEkle(
                $randevu['danisan_id'], 
                'zamaninda_gelme', 
                null, 
                "Zamanında gelme bonusu",
                $randevu_id
            );
        }
        
        return true;
    }
    
    /**
     * Referans bonusu
     */
    public function referansBonusu($referans_veren_id, $yeni_musteri_id) {
        // Referans veren için puan
        $this->puanEkle(
            $referans_veren_id, 
            'referans_getirme', 
            null, 
            "Yeni müşteri referansı",
            $yeni_musteri_id
        );
        
        // Yeni müşteri için hoş geldin bonusu
        $this->puanEkle(
            $yeni_musteri_id, 
            'hosgeldin_bonus', 
            25, 
            "Hoş geldin bonusu",
            $referans_veren_id
        );
    }
    
    /**
     * Doğum günü bonusu kontrolü
     */
    public function dogumGunuBonusKontrol() {
        $bugun = date('m-d');
        
        $sql = "SELECT id, CONCAT(ad, ' ', soyad) as ad_soyad
                FROM danisanlar 
                WHERE DATE_FORMAT(dogum_tarihi, '%m-%d') = ?
                AND aktif = 1
                AND dogum_tarihi IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bugun]);
        $dogum_gunu_olanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dogum_gunu_olanlar as $musteri) {
            // Bu yıl doğum günü bonusu verilmiş mi kontrol et
            $sql = "SELECT id FROM sadakat_puan_gecmisi 
                    WHERE danisan_id = ? 
                    AND puan_turu = 'dogum_gunu_bonus'
                    AND YEAR(olusturma_tarihi) = YEAR(CURDATE())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$musteri['id']]);
            
            if (!$stmt->fetchColumn()) {
                $this->puanEkle(
                    $musteri['id'], 
                    'dogum_gunu_bonus', 
                    null, 
                    "Doğum günü bonusu - " . date('Y')
                );
            }
        }
        
        return count($dogum_gunu_olanlar);
    }
    
    /**
     * Puan ile satın alınabilir hediyeler
     */
    public function getHediyeListesi() {
        return [
            'seans_indirimi_10' => [
                'ad' => '%10 Seans İndirimi',
                'puan' => 50,
                'aciklama' => 'Bir sonraki seansınızda %10 indirim'
            ],
            'seans_indirimi_20' => [
                'ad' => '%20 Seans İndirimi', 
                'puan' => 100,
                'aciklama' => 'Bir sonraki seansınızda %20 indirim'
            ],
            'ucretsiz_seans' => [
                'ad' => 'Ücretsiz Seans',
                'puan' => 200,
                'aciklama' => 'Seçtiğiniz seans türünde ücretsiz 1 seans'
            ],
            'masaj_seansı' => [
                'ad' => 'Relax Masaj Seansı',
                'puan' => 150,
                'aciklama' => '30 dakikalık rahatlama masajı'
            ],
            'fizik_tedavi_seti' => [
                'ad' => 'Ev Fizik Tedavi Seti',
                'puan' => 300,
                'aciklama' => 'Egzersiz bandı, theraband ve egzersiz topu'
            ],
            'beslenme_danismanligi' => [
                'ad' => 'Beslenme Danışmanlığı',
                'puan' => 250,
                'aciklama' => 'Uzman diyetisyen ile 1 saatlik danışmanlık'
            ]
        ];
    }
    
    /**
     * Hediye satın al
     */
    public function hediyeSatinAl($danisan_id, $hediye_kodu) {
        $hediyeler = $this->getHediyeListesi();
        
        if (!isset($hediyeler[$hediye_kodu])) {
            throw new Exception("Geçersiz hediye kodu");
        }
        
        $hediye = $hediyeler[$hediye_kodu];
        $gerekenPuan = $hediye['puan'];
        
        $this->puanHarca(
            $danisan_id, 
            $gerekenPuan, 
            "Hediye satın alma: " . $hediye['ad']
        );
        
        // Hediye kuponunu oluştur
        $kupon_kodu = $this->hediyeKuponu($danisan_id, $hediye_kodu);
        
        return [
            'success' => true,
            'hediye' => $hediye,
            'kupon_kodu' => $kupon_kodu,
            'kalan_puan' => $this->getMusteriPuani($danisan_id)
        ];
    }
    
    /**
     * Hediye kuponu oluştur
     */
    private function hediyeKuponu($danisan_id, $hediye_kodu) {
        $kupon_kodu = 'HDY' . date('Ymd') . str_pad($danisan_id, 4, '0', STR_PAD_LEFT) . rand(100, 999);
        
        $sql = "INSERT INTO hediye_kuponlari 
                (kupon_kodu, danisan_id, hediye_tipi, durum, olusturma_tarihi, gecerlilik_tarihi)
                VALUES (?, ?, ?, 'aktif', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY))";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kupon_kodu, $danisan_id, $hediye_kodu]);
        
        return $kupon_kodu;
    }
    
    /**
     * En iyi müşteriler listesi
     */
    public function getEnIyiMusteriler($limit = 10) {
        $sql = "SELECT 
                    d.id,
                    CONCAT(d.ad, ' ', d.soyad) as ad_soyad,
                    d.sadakat_puani,
                    d.sadakat_seviyesi,
                    COUNT(r.id) as toplam_seans,
                    SUM(s.toplam_tutar) as toplam_harcama
                FROM danisanlar d
                LEFT JOIN randevular r ON d.id = r.danisan_id AND r.durum = 'tamamlandi'
                LEFT JOIN satislar s ON d.id = s.danisan_id AND s.aktif = 1
                WHERE d.aktif = 1
                GROUP BY d.id
                ORDER BY d.sadakat_puani DESC
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Seviye yükselme bildirimi gönder
     */
    private function seviyeYukselmeBildirimiGonder($danisan_id, $yeni_seviye) {
        // SMS/WhatsApp bildirimi için müşteri bilgilerini al
        $sql = "SELECT CONCAT(ad, ' ', soyad) as ad_soyad, telefon, whatsapp_onay 
                FROM danisanlar WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $musteri = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($musteri && $musteri['telefon']) {
            $seviye_adi = self::SEVIYELER[$yeni_seviye]['ad'];
            $indirim = self::SEVIYELER[$yeni_seviye]['indirim'];
            
            $mesaj = "🎉 Tebrikler {$musteri['ad_soyad']}!\n\n";
            $mesaj .= "PhysioVita sadakat programında {$seviye_adi} seviyesine yükseldiniz!\n\n";
            $mesaj .= "✨ Yeni ayrıcalıklarınız:\n";
            $mesaj .= "• %{$indirim} indirim hakkı\n";
            $mesaj .= "• Öncelikli randevu hakkı\n";
            $mesaj .= "• Özel kampanyalara erişim\n\n";
            $mesaj .= "PhysioVita Fizik Tedavi";
            
            // Hatırlatma sistemini kullanarak mesaj gönder
            // (Bu kısım hatırlatma sistemi ile entegre edilecek)
        }
    }
    
    /**
     * Sadakat istatistikleri
     */
    public function getSadakatIstatistikleri() {
        // Seviye dağılımı
        $sql = "SELECT sadakat_seviyesi, COUNT(*) as musteri_sayisi
                FROM danisanlar 
                WHERE aktif = 1 
                GROUP BY sadakat_seviyesi 
                ORDER BY sadakat_seviyesi";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $seviye_dagilimi = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Aylık puan dağılımı
        $sql = "SELECT 
                    DATE_FORMAT(olusturma_tarihi, '%Y-%m') as ay,
                    puan_turu,
                    SUM(puan_miktari) as toplam_puan
                FROM sadakat_puan_gecmisi 
                WHERE olusturma_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY ay, puan_turu
                ORDER BY ay DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $aylik_puan_dagilimi = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'seviye_dagilimi' => $seviye_dagilimi,
            'aylik_puan_dagilimi' => $aylik_puan_dagilimi
        ];
    }
}

// Veritabanı tabloları SQL
/*
-- Sadakat puan geçmişi tablosu
CREATE TABLE sadakat_puan_gecmisi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    danisan_id INT NOT NULL,
    puan_turu VARCHAR(50) NOT NULL,
    puan_miktari INT NOT NULL,
    aciklama TEXT,
    referans_id INT NULL,
    olusturma_tarihi DATETIME NOT NULL,
    FOREIGN KEY (danisan_id) REFERENCES danisanlar(id),
    INDEX idx_danisan_tarih (danisan_id, olusturma_tarihi)
);

-- Hediye kuponları tablosu
CREATE TABLE hediye_kuponlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kupon_kodu VARCHAR(20) UNIQUE NOT NULL,
    danisan_id INT NOT NULL,
    hediye_tipi VARCHAR(50) NOT NULL,
    durum ENUM('aktif', 'kullanildi', 'iptal') DEFAULT 'aktif',
    kullanim_tarihi DATETIME NULL,
    olusturma_tarihi DATETIME NOT NULL,
    gecerlilik_tarihi DATETIME NOT NULL,
    FOREIGN KEY (danisan_id) REFERENCES danisanlar(id)
);

-- Danışanlar tablosuna eklenmesi gereken kolonlar
ALTER TABLE danisanlar ADD COLUMN sadakat_puani INT DEFAULT 0;
ALTER TABLE danisanlar ADD COLUMN sadakat_seviyesi INT DEFAULT 1;
ALTER TABLE danisanlar ADD COLUMN son_puan_tarihi DATETIME NULL;
ALTER TABLE danisanlar ADD COLUMN referans_veren_id INT NULL;
*/

?>