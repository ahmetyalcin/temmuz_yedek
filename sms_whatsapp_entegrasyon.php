<?php
// sms_whatsapp_entegrasyon.php - SMS ve WhatsApp Entegrasyon Sistemi

class SMSWhatsAppEntegrasyon {
    private $pdo;
    private $config;
    
    public function __construct($pdo, $config = []) {
        $this->pdo = $pdo;
        $this->config = array_merge([
            // SMS API Ayarları (İletimerkezi örneği)
            'sms_api_url' => 'https://api.iletimerkezi.com/v1/send-sms/get/',
            'sms_username' => '',
            'sms_password' => '',
            'sms_sender' => 'PhysioVita',
            
            // WhatsApp API Ayarları (Twilio örneği)
            'whatsapp_api_url' => 'https://api.twilio.com/2010-04-01/Accounts/{account_sid}/Messages.json',
            'whatsapp_account_sid' => '',
            'whatsapp_auth_token' => '',
            'whatsapp_from' => 'whatsapp:+14155238886', // Twilio sandbox numarası
            
            // Genel ayarlar
            'default_country_code' => '+90',
            'max_retry_count' => 3,
            'rate_limit_per_minute' => 60
        ], $config);
    }
    
    /**
     * Mesaj gönder (otomatik yöntem seçimi)
     */
    public function mesajGonder($telefon, $mesaj, $tip = 'auto', $oncelik = 'normal') {
        $telefon = $this->telefonFormatla($telefon);
        
        // Rate limit kontrolü
        if (!$this->rateLimitKontrol($telefon)) {
            throw new Exception("Rate limit aşıldı. Lütfen 1 dakika bekleyiniz.");
        }
        
        // Otomatik tip seçimi
        if ($tip === 'auto') {
            $tip = $this->otomatikTipSec($telefon);
        }
        
        $sonuc = null;
        $hatalar = [];
        
        try {
            if ($tip === 'whatsapp') {
                $sonuc = $this->whatsappGonder($telefon, $mesaj);
            } elseif ($tip === 'sms') {
                $sonuc = $this->smsGonder($telefon, $mesaj);
            } else {
                // Hem WhatsApp hem SMS dene
                try {
                    $sonuc = $this->whatsappGonder($telefon, $mesaj);
                } catch (Exception $e) {
                    $hatalar[] = "WhatsApp: " . $e->getMessage();
                    $sonuc = $this->smsGonder($telefon, $mesaj);
                }
            }
            
            // Başarılı gönderimi kaydet
            $this->gonderimKaydet($telefon, $mesaj, $sonuc['method'], 'basarili', $sonuc);
            
            return $sonuc;
            
        } catch (Exception $e) {
            $hatalar[] = $e->getMessage();
            
            // Başarısız gönderimi kaydet
            $this->gonderimKaydet($telefon, $mesaj, $tip, 'basarisiz', [
                'error' => implode(', ', $hatalar)
            ]);
            
            throw new Exception("Mesaj gönderilemedi: " . implode(', ', $hatalar));
        }
    }
    
    /**
     * WhatsApp mesajı gönder
     */
    private function whatsappGonder($telefon, $mesaj) {
        $url = str_replace('{account_sid}', $this->config['whatsapp_account_sid'], 
                          $this->config['whatsapp_api_url']);
        
        $data = [
            'From' => $this->config['whatsapp_from'],
            'To' => 'whatsapp:' . $this->config['default_country_code'] . $telefon,
            'Body' => $mesaj
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_USERPWD => $this->config['whatsapp_account_sid'] . ':' . $this->config['whatsapp_auth_token'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("WhatsApp CURL Hatası: " . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 201 && isset($responseData['sid'])) {
            return [
                'success' => true,
                'method' => 'whatsapp',
                'message_id' => $responseData['sid'],
                'status' => $responseData['status'] ?? 'sent',
                'response' => $responseData
            ];
        } else {
            $errorMsg = $responseData['message'] ?? "HTTP {$httpCode}: {$response}";
            throw new Exception("WhatsApp API Hatası: " . $errorMsg);
        }
    }
    
    /**
     * SMS gönder
     */
    private function smsGonder($telefon, $mesaj) {
        $params = [
            'username' => $this->config['sms_username'],
            'password' => $this->config['sms_password'],
            'text' => $mesaj,
            'receipents' => $telefon,
            'sender' => $this->config['sms_sender']
        ];
        
        $url = $this->config['sms_api_url'] . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("SMS CURL Hatası: " . $error);
        }
        
        $responseData = json_decode($response, true);
        
        // İletimerkezi API yanıt formatına göre kontrol
        if ($httpCode === 200 && isset($responseData['response']['status']) && 
            $responseData['response']['status'] === 200) {
            return [
                'success' => true,
                'method' => 'sms',
                'message_id' => $responseData['response']['id'] ?? uniqid(),
                'status' => 'sent',
                'response' => $responseData
            ];
        } else {
            $errorMsg = $responseData['response']['message'] ?? "HTTP {$httpCode}: {$response}";
            throw new Exception("SMS API Hatası: " . $errorMsg);
        }
    }
    
    /**
     * Toplu mesaj gönder
     */
    public function topluMesajGonder($alicilar, $mesaj, $tip = 'auto') {
        $sonuclar = [];
        $basarili = 0;
        $basarisiz = 0;
        
        foreach ($alicilar as $alici) {
            $telefon = is_array($alici) ? $alici['telefon'] : $alici;
            $kisiselMesaj = is_array($alici) && isset($alici['mesaj']) ? $alici['mesaj'] : $mesaj;
            
            try {
                $sonuc = $this->mesajGonder($telefon, $kisiselMesaj, $tip);
                $sonuclar[] = [
                    'telefon' => $telefon,
                    'durum' => 'basarili',
                    'mesaj_id' => $sonuc['message_id'],
                    'yontem' => $sonuc['method']
                ];
                $basarili++;
                
                // Rate limit için bekleme
                usleep(100000); // 100ms bekle
                
            } catch (Exception $e) {
                $sonuclar[] = [
                    'telefon' => $telefon,
                    'durum' => 'basarisiz',
                    'hata' => $e->getMessage()
                ];
                $basarisiz++;
            }
        }
        
        return [
            'toplam' => count($alicilar),
            'basarili' => $basarili,
            'basarisiz' => $basarisiz,
            'detay' => $sonuclar
        ];
    }
    
    /**
     * Randevu hatırlatma mesajları
     */
    public function randevuHatirlatmalari($tip = 'yarin') {
        if ($tip === 'yarin') {
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
        } else {
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
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $gonderilen = 0;
        foreach ($randevular as $randevu) {
            // Bu randevu için bugün hatırlatma gönderilmiş mi kontrol et
            if (!$this->hatirlatmaGonderildiMi($randevu['randevu_id'], $tip)) {
                $mesaj = $this->randevuHatirlatmaMesaji($randevu, $tip);
                $yontem = $randevu['whatsapp_onay'] ? 'whatsapp' : 'sms';
                
                try {
                    $this->mesajGonder($randevu['telefon'], $mesaj, $yontem);
                    $this->hatirlatmaKaydet($randevu['randevu_id'], $tip);
                    $gonderilen++;
                } catch (Exception $e) {
                    error_log("Randevu hatırlatma hatası: " . $e->getMessage());
                }
            }
        }
        
        return $gonderilen;
    }
    
    /**
     * Kampanya mesajları gönder
     */
    public function kampanyaMesajlari($kampanya_id) {
        $sql = "SELECT k.*, 
                       GROUP_CONCAT(kt.hedef_tip) as hedef_tipler,
                       GROUP_CONCAT(kt.hedef_deger) as hedef_degerler
                FROM kampanyalar k
                LEFT JOIN kampanya_hedefleri kt ON k.id = kt.kampanya_id
                WHERE k.id = ? AND k.durum = 'aktif'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kampanya_id]);
        $kampanya = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kampanya) {
            throw new Exception("Kampanya bulunamadı");
        }
        
        // Hedef kitleyi belirle
        $hedef_musteriler = $this->kampanyaHedefleriniGetir($kampanya);
        
        $mesaj = $kampanya['mesaj_metni'];
        $alicilar = [];
        
        foreach ($hedef_musteriler as $musteri) {
            $alicilar[] = [
                'telefon' => $musteri['telefon'],
                'mesaj' => $this->kisisellestirilmisMesaj($mesaj, $musteri)
            ];
        }
        
        return $this->topluMesajGonder($alicilar, $mesaj, $kampanya['gonderim_yontemi']);
    }
    
    /**
     * Doğum günü kutlama mesajları
     */
    public function dogumGunuKutlamalari() {
        $bugun = date('m-d');
        
        $sql = "SELECT 
                    id,
                    CONCAT(ad, ' ', soyad) as ad_soyad,
                    telefon,
                    whatsapp_onay,
                    sadakat_seviyesi
                FROM danisanlar 
                WHERE DATE_FORMAT(dogum_tarihi, '%m-%d') = ?
                AND aktif = 1
                AND telefon IS NOT NULL
                AND dogum_tarihi IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bugun]);
        $dogum_gunu_olanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $gonderilen = 0;
        foreach ($dogum_gunu_olanlar as $musteri) {
            // Bu yıl doğum günü mesajı gönderilmiş mi kontrol et
            if (!$this->dogumGunuMesajiGonderildiMi($musteri['id'])) {
                $mesaj = $this->dogumGunuMesaji($musteri);
                $yontem = $musteri['whatsapp_onay'] ? 'whatsapp' : 'sms';
                
                try {
                    $this->mesajGonder($musteri['telefon'], $mesaj, $yontem);
                    $this->dogumGunuMesajiKaydet($musteri['id']);
                    $gonderilen++;
                } catch (Exception $e) {
                    error_log("Doğum günü mesajı hatası: " . $e->getMessage());
                }
            }
        }
        
        return $gonderilen;
    }
    
    /**
     * Mesaj şablonları
     */
    public function getMessajSablonlari() {
        $sql = "SELECT * FROM mesaj_sablonlari WHERE aktif = 1 ORDER BY kod";
        $stmt = $this->pdo->query($sql);
        $sablonlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sonuc = [];
        foreach ($sablonlar as $sablon) {
            $sonuc[$sablon['kod']] = [
                'baslik' => $sablon['baslik'],
                'icerik' => $sablon['icerik']
            ];
        }
        
        return $sonuc;
    }
    
    /**
     * Şablon ile mesaj gönder
     */
    public function sablonMesajGonder($telefon, $sablon_kod, $degiskenler = [], $tip = 'auto') {
        $sql = "SELECT icerik FROM mesaj_sablonlari WHERE kod = ? AND aktif = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sablon_kod]);
        $sablon = $stmt->fetchColumn();
        
        if (!$sablon) {
            throw new Exception("Mesaj şablonu bulunamadı: " . $sablon_kod);
        }
        
        // Değişkenleri değiştir
        $mesaj = $this->degiskenleriDegistir($sablon, $degiskenler);
        
        // Şablon kullanım sayısını artır
        $sql = "UPDATE mesaj_sablonlari SET kullanim_sayisi = kullanim_sayisi + 1 WHERE kod = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sablon_kod]);
        
        return $this->mesajGonder($telefon, $mesaj, $tip);
    }
    
    /**
     * Yardımcı fonksiyonlar
     */
    private function telefonFormatla($telefon) {
        $telefon = preg_replace('/[^0-9]/', '', $telefon);
        
        if (substr($telefon, 0, 2) == '90') {
            $telefon = substr($telefon, 2);
        } elseif (substr($telefon, 0, 1) == '0') {
            $telefon = substr($telefon, 1);
        }
        
        return $telefon;
    }
    
    private function otomatikTipSec($telefon) {
        // Müşteri tercihini veritabanından al
        $sql = "SELECT whatsapp_onay FROM danisanlar WHERE telefon LIKE ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['%' . $telefon . '%']);
        $whatsapp_onay = $stmt->fetchColumn();
        
        return $whatsapp_onay ? 'whatsapp' : 'sms';
    }
    
    private function rateLimitKontrol($telefon) {
        $sql = "SELECT COUNT(*) FROM mesaj_gonderim_log 
                WHERE telefon = ? AND gonderim_tarihi >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$telefon]);
        $dakikalik_mesaj = $stmt->fetchColumn();
        
        return $dakikalik_mesaj < $this->config['rate_limit_per_minute'];
    }
    
    private function gonderimKaydet($telefon, $mesaj, $yontem, $durum, $detay = []) {
        $sql = "INSERT INTO mesaj_gonderim_log 
                (telefon, mesaj, yontem, durum, detay, gonderim_tarihi) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $telefon, 
            $mesaj, 
            $yontem, 
            $durum, 
            json_encode($detay, JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    private function hatirlatmaGonderildiMi($randevu_id, $tip) {
        $sql = "SELECT id FROM randevu_hatirlatmalari 
                WHERE randevu_id = ? AND tip = ? AND DATE(gonderim_tarihi) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$randevu_id, $tip]);
        return $stmt->fetchColumn() ? true : false;
    }
    
    private function hatirlatmaKaydet($randevu_id, $tip) {
        $sql = "INSERT INTO randevu_hatirlatmalari 
                (randevu_id, tip, gonderim_tarihi, durum) 
                VALUES (?, ?, NOW(), 'basarili')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$randevu_id, $tip]);
    }
    
    private function dogumGunuMesajiGonderildiMi($danisan_id) {
        $sql = "SELECT id FROM mesaj_gonderim_log 
                WHERE telefon IN (SELECT telefon FROM danisanlar WHERE id = ?)
                AND mesaj LIKE '%Doğum gününüz kutlu olsun%'
                AND DATE(gonderim_tarihi) = CURDATE()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        return $stmt->fetchColumn() ? true : false;
    }
    
    private function dogumGunuMesajiKaydet($danisan_id) {
        // Bu fonksiyon gerekirse özel bir tablo için kullanılabilir
        // Şimdilik mesaj_gonderim_log tablosu yeterli
    }
    
    private function randevuHatirlatmaMesaji($randevu, $tip) {
        $tarih = date('d.m.Y H:i', strtotime($randevu['randevu_tarihi']));
        
        if ($tip === 'yarin') {
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
    
    private function dogumGunuMesaji($musteri) {
        $seviye_bonusu = '';
        if (isset($musteri['sadakat_seviyesi']) && $musteri['sadakat_seviyesi'] >= 3) {
            $seviye_bonusu = " Size özel %20 indirim kodu: DOGUMGUNU2024";
        }
        
        $mesaj = "🎉 Doğum gününüz kutlu olsun {$musteri['ad_soyad']}!\n\n";
        $mesaj .= "PhysioVita ailesi olarak bu özel gününüzü kutlar, sağlık ve mutluluk dolu bir yaş dileriz.\n\n";
        $mesaj .= "🎁 Size özel hediye seans için bizi arayabilirsiniz.{$seviye_bonusu}\n\n";
        $mesaj .= "PhysioVita Fizik Tedavi";
        
        return $mesaj;
    }
    
    private function kampanyaHedefleriniGetir($kampanya) {
        // Kampanya hedef kriterlerine göre müşteri listesi oluştur
        $sql = "SELECT DISTINCT d.* 
                FROM danisanlar d 
                WHERE d.aktif = 1 AND d.telefon IS NOT NULL AND d.telefon != ''";
        
        $hedefler = explode(',', $kampanya['hedef_tipler'] ?? '');
        $degerler = explode(',', $kampanya['hedef_degerler'] ?? '');
        
        for ($i = 0; $i < count($hedefler); $i++) {
            $hedef = trim($hedefler[$i]);
            $deger = trim($degerler[$i]);
            
            if (empty($hedef) || empty($deger)) continue;
            
            switch ($hedef) {
                case 'seviye':
                    $sql .= " AND d.sadakat_seviyesi >= " . intval($deger);
                    break;
                case 'son_randevu':
                    $sql .= " AND d.id IN (SELECT danisan_id FROM randevular WHERE randevu_tarihi >= DATE_SUB(CURDATE(), INTERVAL {$deger} DAY))";
                    break;
                case 'yasgrubu':
                    $yas_aralik = explode('-', $deger);
                    if (count($yas_aralik) == 2) {
                        $sql .= " AND TIMESTAMPDIFF(YEAR, d.dogum_tarihi, CURDATE()) BETWEEN {$yas_aralik[0]} AND {$yas_aralik[1]}";
                    }
                    break;
                case 'cinsiyet':
                    $sql .= " AND d.cinsiyet = '" . $this->pdo->quote($deger) . "'";
                    break;
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function kisisellestirilmisMesaj($mesaj, $musteri) {
        $degistirmeler = [
            '{MUSTERI_ADI}' => ($musteri['ad'] ?? '') . ' ' . ($musteri['soyad'] ?? ''),
            '{AD}' => $musteri['ad'] ?? '',
            '{SOYAD}' => $musteri['soyad'] ?? '',
            '{SEVIYE}' => $musteri['sadakat_seviyesi'] ?? 1,
            '{TELEFON}' => $musteri['telefon'] ?? '',
            '{EMAIL}' => $musteri['email'] ?? ''
        ];
        
        return str_replace(array_keys($degistirmeler), array_values($degistirmeler), $mesaj);
    }
    
    private function degiskenleriDegistir($mesaj, $degiskenler) {
        $varsayilan_degiskenler = [
            '{KLINIK_ADI}' => 'PhysioVita',
            '{KLINIK_TELEFON}' => '0XXX XXX XX XX',
            '{KLINIK_ADRES}' => 'Klinik adresiniz',
            '{TARIH_SAAT}' => date('d.m.Y H:i'),
            '{YIL}' => date('Y'),
            '{AY}' => date('m'),
            '{GUN}' => date('d')
        ];
        
        $tum_degiskenler = array_merge($varsayilan_degiskenler, $degiskenler);
        
        return str_replace(array_keys($tum_degiskenler), array_values($tum_degiskenler), $mesaj);
    }
    
    /**
     * Mesaj gönderim istatistikleri
     */
    public function getGonderimIstatistikleri($baslangic_tarih = null, $bitis_tarih = null) {
        if (!$baslangic_tarih) $baslangic_tarih = date('Y-m-01');
        if (!$bitis_tarih) $bitis_tarih = date('Y-m-t');
        
        $sql = "SELECT 
                    yontem,
                    durum,
                    COUNT(*) as adet,
                    DATE(gonderim_tarihi) as tarih
                FROM mesaj_gonderim_log 
                WHERE DATE(gonderim_tarihi) BETWEEN ? AND ?
                GROUP BY yontem, durum, DATE(gonderim_tarihi)
                ORDER BY tarih DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$baslangic_tarih, $bitis_tarih]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kampanya oluştur
     */
    public function kampanyaOlustur($baslik, $mesaj, $hedefler, $gonderim_yontemi = 'auto') {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO kampanyalar (baslik, mesaj_metni, gonderim_yontemi, durum, olusturma_tarihi, olusturan_kullanici_id) 
                    VALUES (?, ?, ?, 'aktif', NOW(), ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$baslik, $mesaj, $gonderim_yontemi, $_SESSION['user_id'] ?? null]);
            
            $kampanya_id = $this->pdo->lastInsertId();
            
            foreach ($hedefler as $hedef) {
                $sql = "INSERT INTO kampanya_hedefleri (kampanya_id, hedef_tip, hedef_deger) 
                        VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$kampanya_id, $hedef['tip'], $hedef['deger']]);
            }
            
            $this->pdo->commit();
            return $kampanya_id;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * API bağlantısını test et
     */
    public function apiTestEt($tip, $test_telefon = null) {
        if (!$test_telefon) {
            $test_telefon = '5xxxxxxxxx'; // Test numarası
        }
        
        $test_mesaj = "API Test - PhysioVita - " . date('Y-m-d H:i:s');
        
        try {
            if ($tip === 'sms') {
                if (empty($this->config['sms_username']) || empty($this->config['sms_password'])) {
                    throw new Exception('SMS API ayarları eksik');
                }
                return $this->smsGonder($test_telefon, $test_mesaj);
                
            } elseif ($tip === 'whatsapp') {
                if (empty($this->config['whatsapp_account_sid']) || empty($this->config['whatsapp_auth_token'])) {
                    throw new Exception('WhatsApp API ayarları eksik');
                }
                return $this->whatsappGonder($test_telefon, $test_mesaj);
                
            } else {
                throw new Exception('Geçersiz test tipi');
            }
        } catch (Exception $e) {
            throw new Exception("API Test Hatası ({$tip}): " . $e->getMessage());
        }
    }
    
    /**
     * Mesaj durumu sorgula (Twilio için)
     */
    public function mesajDurumuSorgula($message_id, $tip) {
        if ($tip === 'whatsapp') {
            $url = str_replace('{account_sid}', $this->config['whatsapp_account_sid'], 
                              'https://api.twilio.com/2010-04-01/Accounts/{account_sid}/Messages/' . $message_id . '.json');
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $this->config['whatsapp_account_sid'] . ':' . $this->config['whatsapp_auth_token'],
                CURLOPT_TIMEOUT => 15
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
        }
        
        return null;
    }
    
    /**
     * Randevu durumu değiştiğinde mesaj gönder
     */
    public function randevuDurumMesaji($randevu_id, $yeni_durum) {
        // Randevu bilgilerini al
        $sql = "SELECT r.*, 
                       CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                       d.telefon, d.whatsapp_onay,
                       CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                       st.ad as seans_turu,
                       rm.name as oda_adi
                FROM randevular r
                JOIN danisanlar d ON r.danisan_id = d.id
                JOIN personel p ON r.personel_id = p.id
                JOIN seans_turleri st ON r.seans_turu_id = st.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                WHERE r.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$randevu_id]);
        $randevu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$randevu || !$randevu['telefon']) {
            return false;
        }
        
        $degiskenler = [
            '{MUSTERI_ADI}' => $randevu['danisan_adi'],
            '{TARIH}' => date('d.m.Y', strtotime($randevu['randevu_tarihi'])),
            '{SAAT}' => date('H:i', strtotime($randevu['randevu_tarihi'])),
            '{TERAPIST}' => $randevu['terapist_adi'],
            '{SEANS_TURU}' => $randevu['seans_turu'],
            '{ODA}' => $randevu['oda_adi'] ?: 'Belirtilmemiş'
        ];
        
        $sablon_kod = '';
        switch ($yeni_durum) {
            case 'onaylandi':
                $sablon_kod = 'randevu_onay';
                break;
            case 'iptal_edildi':
                $sablon_kod = 'randevu_iptal';
                break;
            case 'tamamlandi':
                // Değerlendirme daveti gönder
                $sablon_kod = 'degerlendirme_daveti';
                $degerlendirme_link = $this->degerlendirmeLinki($randevu_id);
                $degiskenler['{DEGERLENDIRME_LINKI}'] = $degerlendirme_link;
                break;
        }
        
        if ($sablon_kod) {
            try {
                $yontem = $randevu['whatsapp_onay'] ? 'whatsapp' : 'sms';
                return $this->sablonMesajGonder($randevu['telefon'], $sablon_kod, $degiskenler, $yontem);
            } catch (Exception $e) {
                error_log("Randevu durum mesajı hatası: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Değerlendirme linki oluştur
     */
    private function degerlendirmeLinki($randevu_id) {
        $token = md5($randevu_id . 'degerlendirme_token_2024');
        return "https://yourdomain.com/degerlendirme.php?randevu=" . $randevu_id . "&token=" . $token;
    }
    
    /**
     * Ödeme hatırlatması gönder
     */
    public function odemeHatirlatmasi($danisan_id, $tutar, $vade_tarihi) {
        // Müşteri bilgilerini al
        $sql = "SELECT CONCAT(ad, ' ', soyad) as ad_soyad, telefon, whatsapp_onay 
                FROM danisanlar WHERE id = ? AND aktif = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $musteri = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$musteri || !$musteri['telefon']) {
            return false;
        }
        
        $degiskenler = [
            '{MUSTERI_ADI}' => $musteri['ad_soyad'],
            '{TUTAR}' => number_format($tutar, 2),
            '{TARIH}' => date('d.m.Y', strtotime($vade_tarihi))
        ];
        
        try {
            $yontem = $musteri['whatsapp_onay'] ? 'whatsapp' : 'sms';
            return $this->sablonMesajGonder($musteri['telefon'], 'odeme_hatirlatma', $degiskenler, $yontem);
        } catch (Exception $e) {
            error_log("Ödeme hatırlatma mesajı hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Paket bitiş uyarısı
     */
    public function paketBitisUyarisi($danisan_id, $seans_turu, $kalan_seans = 0) {
        // Müşteri bilgilerini al
        $sql = "SELECT CONCAT(ad, ' ', soyad) as ad_soyad, telefon, whatsapp_onay 
                FROM danisanlar WHERE id = ? AND aktif = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $musteri = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$musteri || !$musteri['telefon']) {
            return false;
        }
        
        $degiskenler = [
            '{MUSTERI_ADI}' => $musteri['ad_soyad'],
            '{SEANS_TURU}' => $seans_turu,
            '{KALAN_SEANS}' => $kalan_seans
        ];
        
        $sablon_kod = $kalan_seans > 0 ? 'paket_bitiyor' : 'paket_bitti';
        
        // Eğer 'paket_bitiyor' şablonu yoksa 'paket_bitti' kullan
        $sql = "SELECT id FROM mesaj_sablonlari WHERE kod = ? AND aktif = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sablon_kod]);
        if (!$stmt->fetchColumn()) {
            $sablon_kod = 'paket_bitti';
        }
        
        try {
            $yontem = $musteri['whatsapp_onay'] ? 'whatsapp' : 'sms';
            return $this->sablonMesajGonder($musteri['telefon'], $sablon_kod, $degiskenler, $yontem);
        } catch (Exception $e) {
            error_log("Paket bitiş uyarısı hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Konfigürasyonu güncelle
     */
    public function configGuncelle($yeni_config) {
        $this->config = array_merge($this->config, $yeni_config);
    }
    
    /**
     * Sistem durumu kontrolü
     */
    public function sistemDurumu() {
        $durum = [
            'sms_ayarlari' => [
                'durum' => !empty($this->config['sms_username']) && !empty($this->config['sms_password']),
                'eksik_alanlar' => []
            ],
            'whatsapp_ayarlari' => [
                'durum' => !empty($this->config['whatsapp_account_sid']) && !empty($this->config['whatsapp_auth_token']),
                'eksik_alanlar' => []
            ],
            'veritabani' => [
                'durum' => $this->veritabaniKontrol(),
                'eksik_tablolar' => []
            ]
        ];
        
        // SMS eksik alanları
        if (empty($this->config['sms_username'])) $durum['sms_ayarlari']['eksik_alanlar'][] = 'Kullanıcı adı';
        if (empty($this->config['sms_password'])) $durum['sms_ayarlari']['eksik_alanlar'][] = 'Şifre';
        if (empty($this->config['sms_sender'])) $durum['sms_ayarlari']['eksik_alanlar'][] = 'Gönderen adı';
        
        // WhatsApp eksik alanları
        if (empty($this->config['whatsapp_account_sid'])) $durum['whatsapp_ayarlari']['eksik_alanlar'][] = 'Account SID';
        if (empty($this->config['whatsapp_auth_token'])) $durum['whatsapp_ayarlari']['eksik_alanlar'][] = 'Auth Token';
        if (empty($this->config['whatsapp_from'])) $durum['whatsapp_ayarlari']['eksik_alanlar'][] = 'WhatsApp numarası';
        
        return $durum;
    }
    
    /**
     * Veritabanı tablolarını kontrol et
     */
    private function veritabaniKontrol() {
        $gerekli_tablolar = [
            'mesaj_gonderim_log',
            'mesaj_sablonlari',
            'randevu_hatirlatmalari',
            'sistem_ayarlari'
        ];
        
        foreach ($gerekli_tablolar as $tablo) {
            try {
                $sql = "SELECT 1 FROM {$tablo} LIMIT 1";
                $this->pdo->query($sql);
            } catch (Exception $e) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Debug modu için log
     */
    private function debugLog($mesaj, $veri = null) {
        if (defined('SMS_WHATSAPP_DEBUG') && SMS_WHATSAPP_DEBUG) {
            error_log("SMS/WhatsApp Debug: " . $mesaj . ($veri ? " - " . json_encode($veri) : ""));
        }
    }
}

// Kullanım örneği ve helper fonksiyonları

/**
 * SMS/WhatsApp sistemi başlat
 */
function initSMSWhatsApp() {
    global $pdo;
    
    // Konfigürasyonu yükle
    require_once 'config/sms_whatsapp_config.php';
    $config = getSMSWhatsAppConfig();
    
    return new SMSWhatsAppEntegrasyon($pdo, $config);
}

/**
 * Hızlı randevu hatırlatması gönder
 */
function sendRandevuHatirlatmasi($randevu_id, $tip = 'yarin') {
    try {
        $sms_whatsapp = initSMSWhatsApp();
        
        // Randevu bilgilerini al
        global $pdo;
        $sql = "SELECT r.*, 
                       CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                       d.telefon, d.whatsapp_onay,
                       CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                       st.ad as seans_turu,
                       rm.name as oda_adi
                FROM randevular r
                JOIN danisanlar d ON r.danisan_id = d.id
                JOIN personel p ON r.personel_id = p.id
                JOIN seans_turleri st ON r.seans_turu_id = st.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                WHERE r.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$randevu_id]);
        $randevu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$randevu || !$randevu['telefon']) {
            return false;
        }
        
        $degiskenler = [
            '{MUSTERI_ADI}' => $randevu['danisan_adi'],
            '{TARIH}' => date('d.m.Y', strtotime($randevu['randevu_tarihi'])),
            '{SAAT}' => date('H:i', strtotime($randevu['randevu_tarihi'])),
            '{TERAPIST}' => $randevu['terapist_adi'],
            '{SEANS_TURU}' => $randevu['seans_turu'],
            '{ODA}' => $randevu['oda_adi'] ?: 'Belirtilmemiş'
        ];
        
        $yontem = $randevu['whatsapp_onay'] ? 'whatsapp' : 'sms';
        return $sms_whatsapp->sablonMesajGonder($randevu['telefon'], 'randevu_hatirlatma', $degiskenler, $yontem);
        
    } catch (Exception $e) {
        error_log("Randevu hatırlatma hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Hızlı doğum günü mesajı gönder
 */
function sendDogumGunuMesaji($danisan_id) {
    try {
        $sms_whatsapp = initSMSWhatsApp();
        
        global $pdo;
        $sql = "SELECT CONCAT(ad, ' ', soyad) as ad_soyad, telefon, whatsapp_onay, sadakat_seviyesi
                FROM danisanlar WHERE id = ? AND aktif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $musteri = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$musteri || !$musteri['telefon']) {
            return false;
        }
        
        $degiskenler = [
            '{MUSTERI_ADI}' => $musteri['ad_soyad']
        ];
        
        $yontem = $musteri['whatsapp_onay'] ? 'whatsapp' : 'sms';
        return $sms_whatsapp->sablonMesajGonder($musteri['telefon'], 'dogum_gunu', $degiskenler, $yontem);
        
    } catch (Exception $e) {
        error_log("Doğum günü mesajı hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Sistem durumu kontrolü
 */
function checkSMSWhatsAppStatus() {
    try {
        $sms_whatsapp = initSMSWhatsApp();
        return $sms_whatsapp->sistemDurumu();
    } catch (Exception $e) {
        return [
            'durum' => false,
            'hata' => $e->getMessage()
        ];
    }
}

// Debug modu için konstant
// Geliştirme ortamında true, üretimde false yapın
define('SMS_WHATSAPP_DEBUG', false);

?>