<?php
// reminder_system.php - Otomatik Randevu Hatırlatma Sistemi

class RandevuHatirlatmaSistemi {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Yarınki randevular için SMS/WhatsApp hatırlatması gönder
     */
    public function yarinRandevuHatirlat() {
        $sql = "SELECT 
                    r.id as randevu_id,
                    r.randevu_tarihi,
                    CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                    d.telefon,
                    d.whatsapp_onay,
                    CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                    st.ad as seans_turu,
                    rm.name as oda_adi
                FROM randevular r
                JOIN danisanlar d ON r.danisan_id = d.id
                JOIN personel p ON r.personel_id = p.id
                JOIN seans_turleri st ON r.seans_turu_id = st.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                WHERE DATE(r.randevu_tarihi) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                AND r.aktif = 1
                AND r.durum = 'onaylandi'
                AND d.telefon IS NOT NULL
                AND d.telefon != ''";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($randevular as $randevu) {
            // Daha önce hatırlatma gönderilmiş mi kontrol et
            if (!$this->hatirlatmaGonderildiMi($randevu['randevu_id'], 'yarin')) {
                $this->hatirlatmaGonder($randevu, 'yarin');
            }
        }
        
        return count($randevular);
    }
    
    /**
     * Bugünkü randevular için 2 saat öncesinde hatırlatma
     */
    public function bugunRandevuHatirlat() {
        $sql = "SELECT 
                    r.id as randevu_id,
                    r.randevu_tarihi,
                    CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                    d.telefon,
                    d.whatsapp_onay,
                    CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                    st.ad as seans_turu,
                    rm.name as oda_adi
                FROM randevular r
                JOIN danisanlar d ON r.danisan_id = d.id
                JOIN personel p ON r.personel_id = p.id
                JOIN seans_turleri st ON r.seans_turu_id = st.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                WHERE DATE(r.randevu_tarihi) = CURDATE()
                AND r.randevu_tarihi BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 HOUR)
                AND r.aktif = 1
                AND r.durum = 'onaylandi'
                AND d.telefon IS NOT NULL
                AND d.telefon != ''";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($randevular as $randevu) {
            if (!$this->hatirlatmaGonderildiMi($randevu['randevu_id'], 'bugun')) {
                $this->hatirlatmaGonder($randevu, 'bugun');
            }
        }
        
        return count($randevular);
    }
    
    /**
     * Hatırlatma mesajı gönder
     */
    private function hatirlatmaGonder($randevu, $tip) {
        $mesaj = $this->mesajOlustur($randevu, $tip);
        
        // WhatsApp öncelikli, sonra SMS
        if ($randevu['whatsapp_onay'] == 1) {
            $gonderimSonuc = $this->whatsappGonder($randevu['telefon'], $mesaj);
        } else {
            $gonderimSonuc = $this->smsGonder($randevu['telefon'], $mesaj);
        }
        
        // Hatırlatma kaydını veritabanına kaydet
        $this->hatirlatmaKaydet($randevu['randevu_id'], $tip, $mesaj, $gonderimSonuc);
        
        return $gonderimSonuc;
    }
    
    /**
     * Hatırlatma mesajı oluştur
     */
    private function mesajOlustur($randevu, $tip) {
        $tarih = date('d.m.Y H:i', strtotime($randevu['randevu_tarihi']));
        
        if ($tip == 'yarin') {
            $mesaj = "🏥 PhysioVita Randevu Hatırlatması\n\n";
            $mesaj .= "Sayın {$randevu['danisan_adi']},\n\n";
            $mesaj .= "Yarın {$tarih} tarihinde {$randevu['terapist_adi']} ile {$randevu['seans_turu']} randevunuz bulunmaktadır.\n\n";
            if ($randevu['oda_adi']) {
                $mesaj .= "📍 Oda: {$randevu['oda_adi']}\n\n";
            }
            $mesaj .= "İptal veya erteleme için lütfen bizi arayınız.\n\n";
            $mesaj .= "PhysioVita Fizik Tedavi";
        } else {
            $mesaj = "⏰ PhysioVita Randevu Hatırlatması\n\n";
            $mesaj .= "Sayın {$randevu['danisan_adi']},\n\n";
            $mesaj .= "Bugün {$tarih} tarihindeki randevunuzu hatırlatırız.\n\n";
            $mesaj .= "👨‍⚕️ Terapist: {$randevu['terapist_adi']}\n";
            $mesaj .= "🔹 Seans: {$randevu['seans_turu']}\n";
            if ($randevu['oda_adi']) {
                $mesaj .= "📍 Oda: {$randevu['oda_adi']}\n\n";
            }
            $mesaj .= "İyi günler dileriz.";
        }
        
        return $mesaj;
    }
    
    /**
     * WhatsApp mesajı gönder
     */
    private function whatsappGonder($telefon, $mesaj) {
        // WhatsApp Business API entegrasyonu
        // Örnek: Twilio WhatsApp API kullanımı
        
        $telefon = $this->telefonFormatla($telefon);
        
        // API ayarları (gerçek değerlerle değiştirilmeli)
        $api_url = 'https://api.twilio.com/2010-04-01/Accounts/YOUR_ACCOUNT_SID/Messages.json';
        $account_sid = 'YOUR_ACCOUNT_SID';
        $auth_token = 'YOUR_AUTH_TOKEN';
        $from_whatsapp = 'whatsapp:+YOUR_WHATSAPP_NUMBER';
        
        $data = [
            'From' => $from_whatsapp,
            'To' => 'whatsapp:+90' . $telefon,
            'Body' => $mesaj
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 201) {
                return ['success' => true, 'type' => 'whatsapp', 'response' => $response];
            } else {
                return ['success' => false, 'type' => 'whatsapp', 'error' => $response];
            }
        } catch (Exception $e) {
            return ['success' => false, 'type' => 'whatsapp', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * SMS gönder
     */
    private function smsGonder($telefon, $mesaj) {
        // SMS API entegrasyonu (örnek: iletimerkezi.com)
        
        $telefon = $this->telefonFormatla($telefon);
        
        // API ayarları
        $api_url = 'https://api.iletimerkezi.com/v1/send-sms/get/';
        $username = 'YOUR_SMS_USERNAME';
        $password = 'YOUR_SMS_PASSWORD';
        $sender = 'PhysioVita';
        
        $data = [
            'username' => $username,
            'password' => $password,
            'text' => $mesaj,
            'receipents' => $telefon,
            'sender' => $sender
        ];
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url . '?' . http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                return ['success' => true, 'type' => 'sms', 'response' => $response];
            } else {
                return ['success' => false, 'type' => 'sms', 'error' => $response];
            }
        } catch (Exception $e) {
            return ['success' => false, 'type' => 'sms', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Telefon numarasını formatla
     */
    private function telefonFormatla($telefon) {
        // Türkiye telefon formatına çevir
        $telefon = preg_replace('/[^0-9]/', '', $telefon);
        
        if (substr($telefon, 0, 2) == '90') {
            $telefon = substr($telefon, 2);
        } elseif (substr($telefon, 0, 1) == '0') {
            $telefon = substr($telefon, 1);
        }
        
        return $telefon;
    }
    
    /**
     * Hatırlatma gönderilmiş mi kontrol et
     */
    private function hatirlatmaGonderildiMi($randevu_id, $tip) {
        $sql = "SELECT id FROM randevu_hatirlatmalari 
                WHERE randevu_id = ? AND tip = ? AND DATE(gonderim_tarihi) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$randevu_id, $tip]);
        return $stmt->fetchColumn() ? true : false;
    }
    
    /**
     * Hatırlatma kaydını veritabanına kaydet
     */
    private function hatirlatmaKaydet($randevu_id, $tip, $mesaj, $sonuc) {
        $sql = "INSERT INTO randevu_hatirlatmalari 
                (randevu_id, tip, mesaj, gonderim_yontemi, durum, gonderim_tarihi, api_yanit) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $randevu_id,
            $tip,
            $mesaj,
            $sonuc['type'],
            $sonuc['success'] ? 'basarili' : 'basarisiz',
            json_encode($sonuc)
        ]);
    }
    
    /**
     * Hatırlatma istatistikleri
     */
    public function hatirlatmaIstatistikleri($baslangic_tarih = null, $bitis_tarih = null) {
        if (!$baslangic_tarih) $baslangic_tarih = date('Y-m-01');
        if (!$bitis_tarih) $bitis_tarih = date('Y-m-t');
        
        $sql = "SELECT 
                    gonderim_yontemi,
                    durum,
                    COUNT(*) as adet
                FROM randevu_hatirlatmalari 
                WHERE DATE(gonderim_tarihi) BETWEEN ? AND ?
                GROUP BY gonderim_yontemi, durum";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$baslangic_tarih, $bitis_tarih]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Veritabanı tablosu oluşturma SQL'i
/*
CREATE TABLE randevu_hatirlatmalari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    randevu_id INT NOT NULL,
    tip ENUM('yarin', 'bugun') NOT NULL,
    mesaj TEXT NOT NULL,
    gonderim_yontemi ENUM('whatsapp', 'sms') NOT NULL,
    durum ENUM('basarili', 'basarisiz') NOT NULL,
    gonderim_tarihi DATETIME NOT NULL,
    api_yanit TEXT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (randevu_id) REFERENCES randevular(id)
);

ALTER TABLE danisanlar ADD COLUMN whatsapp_onay TINYINT(1) DEFAULT 1;
*/

// Kullanım örneği ve cron job
/*
// cron_reminder.php - Günlük çalıştırılacak dosya

require_once 'con/db.php';
require_once 'reminder_system.php';

$hatirlatma = new RandevuHatirlatmaSistemi($pdo);

// Her gün 18:00'da yarınki randevular için hatırlatma
if (date('H') == 18) {
    $yarin_count = $hatirlatma->yarinRandevuHatirlat();
    echo "Yarın için {$yarin_count} hatırlatma gönderildi.\n";
}

// Her 2 saatte bir bugünkü randevular için hatırlatma
if (date('H') % 2 == 0) {
    $bugun_count = $hatirlatma->bugunRandevuHatirlat();
    echo "Bugün için {$bugun_count} hatırlatma gönderildi.\n";
}

// Crontab örneği:
// 0  /usr/bin/php /path/to/your/project/cron_reminder.php */


?>