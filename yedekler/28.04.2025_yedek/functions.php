<?php
include_once 'con/db.php';
function checkLogin($kullanici_adi, $sifre) {
    global $pdo;
    try {
        $sql = "SELECT * FROM personel WHERE kullanici_adi = ? AND aktif = TRUE";
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$kullanici_adi]);
        $personel = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($personel && password_verify($sifre, $personel['sifre'])) {
            return $personel;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Şifre değiştirme fonksiyonu
function sifreDegistir($personel_id, $eski_sifre, $yeni_sifre) {
    global $pdo;
    try {
        // Önce eski şifreyi kontrol et
        $sql = "SELECT sifre FROM personel WHERE id = ?";
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$personel_id]);
        $personel = $sorgu->fetch(PDO::FETCH_ASSOC);

        if (!$personel || !password_verify($eski_sifre, $personel['sifre'])) {
            return ['success' => false, 'message' => 'Mevcut şifre hatalı!'];
        }

        // Yeni şifreyi hashle ve güncelle
        $hashed_password = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        $sql = "UPDATE personel SET sifre = ?, guncelleme_tarihi = CURRENT_TIMESTAMP WHERE id = ?";
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$hashed_password, $personel_id]);

        return ['success' => true, 'message' => 'Şifre başarıyla güncellendi.'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Şifre güncellenirken bir hata oluştu.'];
    }
}
// Personel güncelleme fonksiyonu
function personelGuncelle($id, $data) {
    global $pdo;
    try {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }
        }
        
        $values['id'] = $id;
        $values['guncelleme_tarihi'] = date('Y-m-d H:i:s');
        
        $sql = "UPDATE personel SET " . implode(', ', $fields) . ", guncelleme_tarihi = :guncelleme_tarihi WHERE id = :id";
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute($values);
    } catch(PDOException $e) {
        return false;
    }
}

// Personel silme fonksiyonu
function personelSil($id) {
    global $pdo;
    try {
        $sql = "UPDATE personel 
                SET aktif = FALSE, 
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute(['id' => $id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Rol badge rengini belirle
function getRoleBadgeClass($rol) {
    switch($rol) {
        case 'terapist':
            return 'info';
        case 'yonetici':
            return 'primary';
        case 'satis':
            return 'success';
        default:
            return 'secondary';
    }
}

// Personel İşlemleri
function getTerapistler($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM personel WHERE rol = 'terapist'";
        if ($aktif_only) {
            $sql .= " AND aktif = TRUE";
        }
        $sql .= " ORDER BY ad, soyad";
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}


// Personel İşlemleri

/*
// Yeni fonksiyon: Satış personeli ve yöneticileri getir
function getSatisPersoneli($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM personel WHERE rol IN ('satis', 'yonetici','terapist')";
        if ($aktif_only) {
            $sql .= " AND aktif = TRUE";
        }
        $sql .= " ORDER BY ad, soyad";
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
*/
function personelEkle($ad, $soyad, $email, $telefon, $rol) {
    global $pdo;
    try {
        $sql = "INSERT INTO personel (ad, soyad, email, telefon, rol)
                VALUES (:ad, :soyad, :email, :telefon, :rol)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'ad' => $ad,
            'soyad' => $soyad,
            'email' => $email,
            'telefon' => $telefon,
            'rol' => $rol
        ]);
    } catch(PDOException $e) {
        return false;
    }
}


// Avatar yükleme işlemi
function handleAvatarUpload($file) {
    $upload_dir = "uploads/avatars/";
    
    // Dizin yoksa oluştur
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed_types)) {
        return [
            'success' => false,
            'message' => 'Sadece JPG, JPEG veya PNG dosyaları yükleyebilirsiniz!'
        ];
    }

    $filename = time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return [
            'success' => true,
            'filename' => $filename
        ];
    }

    return [
        'success' => false,
        'message' => 'Dosya yüklenirken bir hata oluştu!'
    ];
}


// Personel durum güncelleme
function personelDurumGuncelle($id, $aktif) {
    global $pdo;
    try {
        $sql = "UPDATE personel 
                SET aktif = :aktif, 
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'aktif' => $aktif
        ]);
    } catch(PDOException $e) {
        return false;
    }
}


// Add to functions.php
function getDanisanlarWithRemainingAppointments() {
    global $pdo;
    try {
        $sql = "SELECT DISTINCT d.*, 
                       s.id as satis_id,
                       s.hizmet_paketi_id,
                       s.hediye_seans,
                       st.seans_adet,
                       (SELECT COUNT(*) 
                        FROM randevular r 
                        WHERE r.satis_id = s.id 
                        AND r.aktif = 1) as kullanilan_seans
                FROM danisanlar d
                JOIN satislar s ON s.danisan_id = d.id
                JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
                WHERE d.aktif = 1 
                AND s.aktif = 1
                AND s.durum != 'iptal'
                HAVING (st.seans_adet + s.hediye_seans) > kullanilan_seans
                ORDER BY d.ad, d.soyad";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Danışan getirme hatası: " . $e->getMessage());
        return [];
    }
}

function getSatisBilgileri($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT s.*, st.seans_adet, st.id as seans_turu_id,
                       (SELECT COUNT(*) 
                        FROM randevular r 
                        WHERE r.satis_id = s.id 
                        AND r.aktif = 1) as kullanilan_seans
                FROM satislar s
                JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
                WHERE s.danisan_id = :danisan_id 
                AND s.aktif = 1
                AND s.durum != 'iptal'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['danisan_id' => $danisan_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Satış bilgisi getirme hatası: " . $e->getMessage());
        return null;
    }
}



function getRandevular($filters = []) {
    global $pdo;
    try {
        $sql = "SELECT r.*, 
                CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                st.ad as seans_turu,
                r.room_id,
                rm.name as room_name,
                s.hediye_seans,
                st.seans_adet,
                s.id as satis_id,
                r.is_gift,
                r.evaluation_type,
                r.evaluation_notes,
                (
                    SELECT COUNT(*) 
                    FROM randevular r2 
                    WHERE r2.satis_id = s.id 
                    AND r2.aktif = 1
                    AND r2.is_gift = 0
                    AND r2.randevu_tarihi <= r.randevu_tarihi
                ) as normal_seans_sirasi,
                (
                    SELECT COUNT(*) 
                    FROM randevular r2 
                    WHERE r2.satis_id = s.id 
                    AND r2.aktif = 1
                    AND r2.is_gift = 1
                    AND r2.randevu_tarihi <= r.randevu_tarihi
                ) as hediye_seans_sirasi,
                (
                    SELECT COUNT(*) 
                    FROM randevular r3
                    WHERE r3.satis_id = s.id 
                    AND r3.aktif = 1
                ) as kullanilan_seans
                FROM randevular r
                JOIN danisanlar d ON d.id = r.danisan_id
                JOIN personel p ON p.id = r.personel_id
                JOIN seans_turleri st ON st.id = r.seans_turu_id
                LEFT JOIN rooms rm ON rm.id = r.room_id
                LEFT JOIN satislar s ON s.id = r.satis_id
                WHERE r.aktif = 1";
        
        if (!empty($filters['personel_id'])) {
            $sql .= " AND r.personel_id = :personel_id";
            $params['personel_id'] = $filters['personel_id'];
        }
        
        $sql .= " ORDER BY r.randevu_tarihi DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params ?? []);
        $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate remaining sessions and determine gift sessions
        foreach ($randevular as &$randevu) {
            if ($randevu['satis_id']) {
                $normal_seans = intval($randevu['seans_adet']);
                $hediye_seans = intval($randevu['hediye_seans']);
                $normal_seans_sirasi = intval($randevu['normal_seans_sirasi']);
                $hediye_seans_sirasi = intval($randevu['hediye_seans_sirasi']);
                $kullanilan_seans = intval($randevu['kullanilan_seans']);
                $toplam_seans = $normal_seans + $hediye_seans;
                
                // Keep the existing is_gift flag from the database
                $is_gift = (bool)$randevu['is_gift'];
                
                // Only update evaluation type for gift sessions
                if ($is_gift) {
                    if ($hediye_seans_sirasi === 1) {
                        $randevu['evaluation_type'] = 'initial';
                    } else if ($hediye_seans_sirasi === $hediye_seans) {
                        $randevu['evaluation_type'] = 'final';
                    }
                }
                
                // Calculate total remaining sessions
                $randevu['kalan_seans'] = $toplam_seans - $kullanilan_seans;
            } else {
                $randevu['kalan_seans'] = 0;
            }
        }

        return $randevular;
    } catch(PDOException $e) {
        error_log("Randevu getirme hatası: " . $e->getMessage());
        return [];
    }
}




function randevuEkle($danisan_id, $personel_id, $seans_turu_id, $randevu_tarihi, $notlar = null, $satis_id = null, $hediye_seans_id = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO randevular (
                    danisan_id, personel_id, seans_turu_id, 
                    randevu_tarihi, notlar, satis_id, hediye_seans_id,
                    durum, aktif
                ) VALUES (
                    :danisan_id, :personel_id, :seans_turu_id,
                    :randevu_tarihi, :notlar, :satis_id, :hediye_seans_id,
                    'beklemede', 1
                )";
        
        $sorgu = $pdo->prepare($sql);
        $sonuc = $sorgu->execute([
            'danisan_id' => $danisan_id,
            'personel_id' => $personel_id,
            'seans_turu_id' => $seans_turu_id,
            'randevu_tarihi' => $randevu_tarihi,
            'notlar' => $notlar,
            'satis_id' => $satis_id,
            'hediye_seans_id' => $hediye_seans_id
        ]);

        if ($sonuc) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch(PDOException $e) {
        error_log("Randevu ekleme hatası: " . $e->getMessage());
        return false;
    }
}

function randevuGuncelle($id, $danisan_id, $personel_id, $seans_turu_id, $randevu_tarihi, $notlar = null) {
    global $pdo;
    try {
        $sql = "UPDATE randevular 
                SET danisan_id = :danisan_id,
                    personel_id = :personel_id,
                    seans_turu_id = :seans_turu_id,
                    randevu_tarihi = :randevu_tarihi,
                    notlar = :notlar
                WHERE id = :id AND aktif = 1";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'danisan_id' => $danisan_id,
            'personel_id' => $personel_id,
            'seans_turu_id' => $seans_turu_id,
            'randevu_tarihi' => $randevu_tarihi,
            'notlar' => $notlar
        ]);
    } catch(PDOException $e) {
        error_log("Randevu güncelleme hatası: " . $e->getMessage());
        return false;
    }
}




// Danışan ekleme
function danisanEkle($ad, $soyad, $email, $telefon, $adres, $yas, $meslek) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisanlar (ad, soyad, email, telefon, adres, yas, meslek) 
                VALUES (:ad, :soyad, :email, :telefon, :adres, :yas, :meslek)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'ad' => $ad,
            'soyad' => $soyad,
            'email' => $email,
            'telefon' => $telefon,
            'adres' => $adres,
            'yas' => $yas,
            'meslek' => $meslek
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Danışan güncelleme
function danisanGuncelle($id, $ad, $soyad, $email, $telefon, $adres, $yas, $meslek, $uyelik_turu_id = null) {
    global $pdo;
    try {
        $sql = "UPDATE danisanlar 
                SET ad = :ad, 
                    soyad = :soyad, 
                    email = :email, 
                    telefon = :telefon,
                    adres = :adres,
                    yas = :yas,
                    meslek = :meslek,
                    uyelik_turu_id = :uyelik_turu_id
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'ad' => $ad,
            'soyad' => $soyad,
            'email' => $email,
            'telefon' => $telefon,
            'adres' => $adres,
            'yas' => $yas,
            'meslek' => $meslek,
            'uyelik_turu_id' => $uyelik_turu_id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Danışanları getir
function getDanisanlar() {
    global $pdo;
    try {
        $sql = "SELECT d.*, 
                       ut.ad as uyelik_adi,
                       (SELECT COUNT(*) FROM randevular r WHERE r.danisan_id = d.id) as toplam_seans_sayisi
                FROM danisanlar d
                LEFT JOIN uyelik_turleri ut ON d.uyelik_turu_id = ut.id
                ORDER BY d.ad, d.soyad";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute();
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}



// Üyelik Türleri İşlemleri
function getUyelikTurleri($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM uyelik_turleri";
        if ($aktif_only) {
            $sql .= " WHERE aktif = TRUE";
        }
        $sql .= " ORDER BY seviye";
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function uyelikEkle($ad, $seviye, $min_seans_sayisi, $indirim_yuzdesi, $hediye_seans_sayisi, $hediye_seans_gecerlilik_gun, $aciklama = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO uyelik_turleri (
                    ad, seviye, min_seans_sayisi, indirim_yuzdesi, 
                    hediye_seans_sayisi, hediye_seans_gecerlilik_gun, aciklama
                )
                VALUES (
                    :ad, :seviye, :min_seans_sayisi, :indirim_yuzdesi,
                    :hediye_seans_sayisi, :hediye_seans_gecerlilik_gun, :aciklama
                )";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'ad' => $ad,
            'seviye' => $seviye,
            'min_seans_sayisi' => $min_seans_sayisi,
            'indirim_yuzdesi' => $indirim_yuzdesi,
            'hediye_seans_sayisi' => $hediye_seans_sayisi,
            'hediye_seans_gecerlilik_gun' => $hediye_seans_gecerlilik_gun,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function uyelikGuncelle($id, $ad, $seviye, $min_seans_sayisi, $indirim_yuzdesi, $hediye_seans_sayisi, $hediye_seans_gecerlilik_gun, $aciklama = null) {
    global $pdo;
    try {
        $sql = "UPDATE uyelik_turleri 
                SET ad = :ad, seviye = :seviye, min_seans_sayisi = :min_seans_sayisi,
                    indirim_yuzdesi = :indirim_yuzdesi, hediye_seans_sayisi = :hediye_seans_sayisi,
                    hediye_seans_gecerlilik_gun = :hediye_seans_gecerlilik_gun, aciklama = :aciklama,
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'ad' => $ad,
            'seviye' => $seviye,
            'min_seans_sayisi' => $min_seans_sayisi,
            'indirim_yuzdesi' => $indirim_yuzdesi,
            'hediye_seans_sayisi' => $hediye_seans_sayisi,
            'hediye_seans_gecerlilik_gun' => $hediye_seans_gecerlilik_gun,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Seans Türleri İşlemleri
function getSeansTurleri($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM seans_turleri";
        if ($aktif_only) {
            $sql .= " WHERE aktif = TRUE";
        }
        $sql .= " ORDER BY ad";
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Hizmet Paketleri İşlemleri
function getHizmetPaketleri($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT hp.*, st.ad as seans_turu_adi 
                FROM hizmet_paketleri hp 
                JOIN seans_turleri st ON hp.seans_turu_id = st.id";
        if ($aktif_only) {
            $sql .= " WHERE hp.aktif = TRUE";
        }
        $sql .= " ORDER BY hp.ad";
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function paketEkle($ad, $seans_turu_id, $seans_sayisi, $fiyat, $gecerlilik_gun, $aciklama = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO hizmet_paketleri (
                    ad, seans_turu_id, seans_sayisi, fiyat, 
                    gecerlilik_gun, aciklama
                )
                VALUES (
                    :ad, :seans_turu_id, :seans_sayisi, :fiyat,
                    :gecerlilik_gun, :aciklama
                )";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'ad' => $ad,
            'seans_turu_id' => $seans_turu_id,
            'seans_sayisi' => $seans_sayisi,
            'fiyat' => $fiyat,
            'gecerlilik_gun' => $gecerlilik_gun,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function paketGuncelle($id, $ad, $seans_turu_id, $seans_sayisi, $fiyat, $gecerlilik_gun, $aciklama = null) {
    global $pdo;
    try {
        $sql = "UPDATE hizmet_paketleri 
                SET ad = :ad, seans_turu_id = :seans_turu_id, seans_sayisi = :seans_sayisi,
                    fiyat = :fiyat, gecerlilik_gun = :gecerlilik_gun, aciklama = :aciklama,
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'ad' => $ad,
            'seans_turu_id' => $seans_turu_id,
            'seans_sayisi' => $seans_sayisi,
            'fiyat' => $fiyat,
            'gecerlilik_gun' => $gecerlilik_gun,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function paketDurumGuncelle($id, $aktif) {
    global $pdo;
    try {
        $sql = "UPDATE hizmet_paketleri 
                SET aktif = :aktif, guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'aktif' => $aktif
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Sponsorluk İşlemleri
function getSponsorluklar($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM sponsorluklar";
        if ($aktif_only) {
            $sql .= " WHERE aktif = TRUE AND (bitis_tarihi IS NULL OR bitis_tarihi >= CURRENT_TIMESTAMP)";
        }
        $sql .= " ORDER BY ad";
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function sponsorlukEkle($ad, $firma_adi, $indirim_yuzdesi, $baslangic_tarihi, $bitis_tarihi = null, $aciklama = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO sponsorluklar (
                    ad, firma_adi, indirim_yuzdesi, baslangic_tarihi, 
                    bitis_tarihi, aciklama
                )
                VALUES (
                    :ad, :firma_adi, :indirim_yuzdesi, :baslangic_tarihi,
                    :bitis_tarihi, :aciklama
                )";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'ad' => $ad,
            'firma_adi' => $firma_adi,
            'indirim_yuzdesi' => $indirim_yuzdesi,
            'baslangic_tarihi' => $baslangic_tarihi,
            'bitis_tarihi' => $bitis_tarihi,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function sponsorlukGuncelle($id, $ad, $firma_adi, $indirim_yuzdesi, $baslangic_tarihi, $bitis_tarihi = null, $aciklama = null) {
    global $pdo;
    try {
        $sql = "UPDATE sponsorluklar 
                SET ad = :ad, firma_adi = :firma_adi, indirim_yuzdesi = :indirim_yuzdesi,
                    baslangic_tarihi = :baslangic_tarihi, bitis_tarihi = :bitis_tarihi,
                    aciklama = :aciklama, guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'id' => $id,
            'ad' => $ad,
            'firma_adi' => $firma_adi,
            'indirim_yuzdesi' => $indirim_yuzdesi,
            'baslangic_tarihi' => $baslangic_tarihi,
            'bitis_tarihi' => $bitis_tarihi,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function getRemainingSessionCount($danisan_id, $seans_turu_id) {
    global $pdo;
    try {
        $sql = "SELECT kalan_seans 
                FROM danisan_seans_paketleri 
                WHERE danisan_id = ? 
                AND seans_turu_id = ? 
                AND aktif = 1 
                ORDER BY olusturma_tarihi DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$danisan_id, $seans_turu_id]);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);
        
        return $result ?: 0;
    } catch(PDOException $e) {
        error_log("Kalan seans sayısı getirme hatası: " . $e->getMessage());
        return 0;
    }
}

function addSessionPackage($danisan_id, $seans_turu_id, $toplam_seans) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_seans_paketleri 
                (id, danisan_id, seans_turu_id, toplam_seans, kalan_seans) 
                VALUES (UUID(), ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$danisan_id, $seans_turu_id, $toplam_seans, $toplam_seans]);
    } catch(PDOException $e) {
        error_log("Seans paketi ekleme hatası: " . $e->getMessage());
        return false;
    }
}

function getSatisPersoneli() {
    global $pdo;
    try {
        $sql = "SELECT * FROM personel WHERE rol = 'satis' AND aktif = 1 ORDER BY ad, soyad";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Satış personeli getirme hatası: " . $e->getMessage());
        return [];
    }
}
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function satisEkle($danisan_id, $hizmet_paketi_id, $personel_id, $toplam_tutar, $odenen_tutar, $odeme_tipi, $vade_tarihi = null, $hediye_seans = 0, $indirim_tutari = 0, $indirim_yuzdesi = null) {
    global $pdo;
    try {
        $satis_id = generateUUID();
        
        $sql = "INSERT INTO satislar (
                    id, danisan_id, hizmet_paketi_id, personel_id,
                    toplam_tutar, odenen_tutar, odeme_tipi,
                    vade_tarihi, hediye_seans, indirim_tutari,
                    indirim_yuzdesi, durum
                ) VALUES (
                    :id, :danisan_id, :hizmet_paketi_id, :personel_id,
                    :toplam_tutar, :odenen_tutar, :odeme_tipi,
                    :vade_tarihi, :hediye_seans, :indirim_tutari,
                    :indirim_yuzdesi, 'beklemede'
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $satis_id,
            'danisan_id' => $danisan_id,
            'hizmet_paketi_id' => $hizmet_paketi_id,
            'personel_id' => $personel_id,
            'toplam_tutar' => $toplam_tutar,
            'odenen_tutar' => $odenen_tutar,
            'odeme_tipi' => $odeme_tipi,
            'vade_tarihi' => $vade_tarihi,
            'hediye_seans' => $hediye_seans,
            'indirim_tutari' => $indirim_tutari,
            'indirim_yuzdesi' => $indirim_yuzdesi
        ]);
        
        return $satis_id;
    } catch(PDOException $e) {
        error_log("Satış ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

function taksitEkle($satis_id, $tutar, $vade_tarihi) {
    global $pdo;
    try {
        $taksit_id = generateUUID();
        
        $sql = "INSERT INTO taksitler (
                    id, satis_id, tutar, vade_tarihi, odendi
                ) VALUES (
                    :id, :satis_id, :tutar, :vade_tarihi, 0
                )";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id' => $taksit_id,
            'satis_id' => $satis_id,
            'tutar' => $tutar,
            'vade_tarihi' => $vade_tarihi
        ]);
    } catch(PDOException $e) {
        error_log("Taksit ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

function odemeEkle($satis_id, $tutar, $odeme_tipi, $odeme_tarihi) {
    global $pdo;
    try {
        $odeme_id = generateUUID();
        
        // Add payment record
        $sql = "INSERT INTO odemeler (
                    id, satis_id, tutar, odeme_tipi, odeme_tarihi
                ) VALUES (
                    :id, :satis_id, :tutar, :odeme_tipi, :odeme_tarihi
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $odeme_id,
            'satis_id' => $satis_id,
            'tutar' => $tutar,
            'odeme_tipi' => $odeme_tipi,
            'odeme_tarihi' => $odeme_tarihi
        ]);

        // Update total paid amount in sales record
        $sql = "UPDATE satislar 
                SET odenen_tutar = (
                    SELECT COALESCE(SUM(tutar), 0)
                    FROM odemeler
                    WHERE satis_id = :satis_id
                    AND aktif = 1
                ),
                guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :satis_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['satis_id' => $satis_id]);

        return true;
    } catch(PDOException $e) {
        error_log("Ödeme ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}


function updateSatisDurum($satis_id) {
    global $pdo;
    try {
        // Get total amount and paid amount
        $sql = "SELECT toplam_tutar, 
                       (SELECT COALESCE(SUM(tutar), 0) FROM odemeler WHERE satis_id = s.id AND aktif = 1) as odenen_tutar
                FROM satislar s 
                WHERE id = :satis_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['satis_id' => $satis_id]);
        $satis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update status based on payment completion
        $durum = $satis['odenen_tutar'] >= $satis['toplam_tutar'] ? 'odendi' : 'beklemede';
        
        $sql = "UPDATE satislar 
                SET durum = :durum,
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :satis_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'durum' => $durum,
            'satis_id' => $satis_id
        ]);
    } catch(PDOException $e) {
        error_log("Satış durumu güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

function getSatislar() {
    global $pdo;
    try {
        $sql = "SELECT s.*, 
                       CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                       st.ad as paket_adi,
                       st.seans_adet,
                       CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                       (SELECT MAX(odeme_tarihi) FROM odemeler WHERE satis_id = s.id AND aktif = 1) as son_odeme_tarihi,
                       (SELECT COUNT(*) FROM taksitler WHERE satis_id = s.id AND aktif = 1 AND odendi = 0) as odenmemis_taksit_sayisi,
                       (SELECT COALESCE(SUM(tutar), 0) FROM odemeler WHERE satis_id = s.id AND aktif = 1) as toplam_odenen
                FROM satislar s
                JOIN danisanlar d ON d.id = s.danisan_id
                JOIN seans_turleri st ON st.id = s.hizmet_paketi_id
                JOIN personel p ON p.id = s.personel_id
                WHERE s.aktif = 1
                ORDER BY s.olusturma_tarihi DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Satış listesi getirme hatası: " . $e->getMessage());
        return [];
    }
}





function getSeansPaketleri() {
    global $pdo;
    try {
        $sql = "SELECT st.*, 
                       COALESCE(st.seans_adet, 0) as seans_sayisi,
                       COALESCE(st.fiyat, 0) as fiyat
                FROM seans_turleri st 
                WHERE st.aktif = 1 
                ORDER BY st.ad";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Seans paketleri getirme hatası: " . $e->getMessage());
        return [];
    }
}


/*
// Satış İşlemleri
function satisEkle($danisan_id, $hizmet_paketi_id, $personel_id, $sponsorluk_id = null) {
    global $pdo;
    try {
        // Hizmet paketi bilgilerini al
        $paket_sorgu = $pdo->prepare("SELECT * FROM hizmet_paketleri WHERE id = ?");
        $paket_sorgu->execute([$hizmet_paketi_id]);
        $paket = $paket_sorgu->fetch(PDO::FETCH_ASSOC);

        if (!$paket) {
            throw new Exception("Hizmet paketi bulunamadı.");
        }

        // Danışan üyelik bilgilerini al
        $danisan_sorgu = $pdo->prepare("
            SELECT d.*, ut.indirim_yuzdesi, ut.hediye_seans_sayisi, ut.hediye_seans_gecerlilik_gun
            FROM danisanlar d
            LEFT JOIN uyelik_turleri ut ON d.uyelik_turu_id = ut.id
            WHERE d.id = ?
        ");
        $danisan_sorgu->execute([$danisan_id]);
        $danisan = $danisan_sorgu->fetch(PDO::FETCH_ASSOC);

        // İndirim hesaplama
        $birim_fiyat = $paket['fiyat'];
        $toplam_indirim = 0;

        // Üyelik indirimi
        if ($danisan['indirim_yuzdesi'] > 0) {
            $uyelik_indirimi = ($birim_fiyat * $danisan['indirim_yuzdesi']) / 100;
            $toplam_indirim += $uyelik_indirimi;
        }

        // Sponsorluk indirimi
        if ($sponsorluk_id) {
            $sponsorluk_sorgu = $pdo->prepare("SELECT indirim_yuzdesi FROM sponsorluklar WHERE id = ?");
            $sponsorluk_sorgu->execute([$sponsorluk_id]);
            $sponsorluk = $sponsorluk_sorgu->fetch(PDO::FETCH_ASSOC);
            
            if ($sponsorluk) {
                $sponsorluk_indirimi = ($birim_fiyat * $sponsorluk['indirim_yuzdesi']) / 100;
                $toplam_indirim += $sponsorluk_indirimi;
            }
        }

        // Son kullanma tarihi hesaplama
        $son_kullanma = date('Y-m-d H:i:s', strtotime('+' . $paket['gecerlilik_gun'] . ' days'));

        // Satış kaydı oluştur
        $pdo->beginTransaction();

        $satis_sorgu = $pdo->prepare("
            INSERT INTO satislar (
                danisan_id, hizmet_paketi_id, personel_id, sponsorluk_id,
                birim_fiyat, indirim_tutari, toplam_tutar, son_kullanma_tarihi
            ) VALUES (
                :danisan_id, :hizmet_paketi_id, :personel_id, :sponsorluk_id,
                :birim_fiyat, :indirim_tutari, :toplam_tutar, :son_kullanma_tarihi
            )
        ");

        $toplam_tutar = $birim_fiyat - $toplam_indirim;

        $satis_sorgu->execute([
            'danisan_id' => $danisan_id,
            'hizmet_paketi_id' => $hizmet_paketi_id,
            'personel_id' => $personel_id,
            'sponsorluk_id' => $sponsorluk_id,
            'birim_fiyat' => $birim_fiyat,
            'indirim_tutari' => $toplam_indirim,
            'toplam_tutar' => $toplam_tutar,
            'son_kullanma_tarihi' => $son_kullanma
        ]);

        $satis_id = $pdo->lastInsertId();

        // Hediye seans kontrolü ve ekleme
        if ($danisan['hediye_seans_sayisi'] > 0) {
            $hediye_son_kullanma = date('Y-m-d H:i:s', 
                strtotime('+' . $danisan['hediye_seans_gecerlilik_gun'] . ' days'));

            $hediye_sorgu = $pdo->prepare("
                INSERT INTO hediye_seanslar (
                    danisan_id, seans_turu_id, satis_id, miktar,
                    son_kullanma_tarihi
                ) VALUES (
                    :danisan_id, :seans_turu_id, :satis_id, :miktar,
                    :son_kullanma_tarihi
                )
            ");

            $hediye_sorgu->execute([
                'danisan_id' => $danisan_id,
                'seans_turu_id' => $paket['seans_turu_id'],
                'satis_id' => $satis_id,
                'miktar' => $danisan['hediye_seans_sayisi'],
                'son_kullanma_tarihi' => $hediye_son_kullanma
            ]);
        }

        // Danışanın toplam seans sayısını güncelle
        $toplam_seans_guncelle = $pdo->prepare("
            UPDATE danisanlar 
            SET toplam_seans_sayisi = toplam_seans_sayisi + :seans_sayisi
            WHERE id = :danisan_id
        ");

        $toplam_seans_guncelle->execute([
            'seans_sayisi' => $paket['seans_sayisi'] + ($danisan['hediye_seans_sayisi'] ?? 0),
            'danisan_id' => $danisan_id
        ]);

        $pdo->commit();
        return $satis_id;

    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// Satış Listeleme
function getSatislar($aktif_only = true) {
    global $pdo;
    try {
        $sql = "
            SELECT s.*, 
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                   hp.ad as paket_adi,
                   sp.ad as sponsorluk_adi
            FROM satislar s
            JOIN danisanlar d ON s.danisan_id = d.id
            JOIN personel p ON s.personel_id = p.id
            JOIN hizmet_paketleri hp ON s.hizmet_paketi_id = hp.id
            LEFT JOIN sponsorluklar sp ON s.sponsorluk_id = sp.id
        ";
        
        if ($aktif_only) {
            $sql .= " WHERE s.aktif = TRUE";
        }
        
        $sql .= " ORDER BY s.olusturma_tarihi DESC";
        
        $sorgu = $pdo->query($sql);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}
*/
// Hediye Seans İşlemleri
function getHediyeSeanslar($danisan_id = null, $aktif_only = true) {
    global $pdo;
    try {
        $sql = "
            SELECT hs.*,
                   CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                   st.ad as seans_turu_adi
            FROM hediye_seanslar hs
            JOIN danisanlar d ON hs.danisan_id = d.id
            JOIN seans_turleri st ON hs.seans_turu_id = st.id
            WHERE 1=1
        ";
        
        if ($danisan_id) {
            $sql .= " AND hs.danisan_id = :danisan_id";
        }
        
        if ($aktif_only) {
            $sql .= " AND hs.aktif = TRUE AND hs.kullanildi = FALSE 
                     AND hs.son_kullanma_tarihi >= CURRENT_TIMESTAMP";
        }
        
        $sql .= " ORDER BY hs.son_kullanma_tarihi";
        
        $sorgu = $pdo->prepare($sql);
        
        if ($danisan_id) {
            $sorgu->execute(['danisan_id' => $danisan_id]);
        } else {
            $sorgu->execute();
        }
        
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function hediyeSeansKullan($hediye_seans_id) {
    global $pdo;
    try {
        $sql = "
            UPDATE hediye_seanslar 
            SET kullanildi = TRUE,
                guncelleme_tarihi = CURRENT_TIMESTAMP
            WHERE id = :id AND kullanildi = FALSE
        ";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute(['id' => $hediye_seans_id]);
    } catch(PDOException $e) {
        return false;
    }
}

function handleCertificateUpload($file) {
    $upload_dir = "uploads/certificates/";
    
    // Dizin yoksa oluştur
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed_types)) {
        return [
            'success' => false,
            'message' => 'Sadece PDF, JPG, JPEG veya PNG dosyaları yükleyebilirsiniz!'
        ];
    }

    $filename = time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return [
            'success' => true,
            'filename' => $filename
        ];
    }

    return [
        'success' => false,
        'message' => 'Dosya yüklenirken bir hata oluştu!'
    ];
}

// Sertifika ekleme
/*
function sertifikaEkle($personel_id, $sertifika_adi, $sertifika) {
    global $pdo;
    try {
        $sql = "INSERT INTO personel_sertifikalar (personel_id, sertifika_adi, sertifika)
                VALUES (:personel_id, :sertifika_adi, :sertifika)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'personel_id' => $personel_id,
            'sertifika_adi' => $sertifika_adi,
            'sertifika' => $sertifika
        ]);
    } catch(PDOException $e) {
        return false;
    }
}
*/

function sertifikaEkle($personel_id, $sertifika_adi, $sertifika, $veren_kurum, $sertifika_tarihi) {
    global $pdo;
    try {
        $sql = "INSERT INTO personel_sertifikalar 
                (personel_id, sertifika_adi, sertifika, veren_kurum, sertifika_tarihi)
                VALUES (:personel_id, :sertifika_adi, :sertifika, :veren_kurum, :sertifika_tarihi)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'personel_id' => $personel_id,
            'sertifika_adi' => $sertifika_adi,
            'sertifika' => $sertifika,
            'veren_kurum' => $veren_kurum,
            'sertifika_tarihi' => $sertifika_tarihi
        ]);
    } catch(PDOException $e) {
        return false;
    }
}



// Sertifika silme
function sertifikaSil($id) {
    global $pdo;
    try {
        // Önce sertifika dosyasını bul
        $sql = "SELECT sertifika FROM personel_sertifikalar WHERE id = ?";
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$id]);
        $sertifika = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($sertifika) {
            // Dosyayı sil
            $dosya_yolu = "uploads/certificates/" . $sertifika['sertifika'];
            if (file_exists($dosya_yolu)) {
                unlink($dosya_yolu);
            }

            // Veritabanı kaydını sil
            $sql = "DELETE FROM personel_sertifikalar WHERE id = ?";
            $sorgu = $pdo->prepare($sql);
            return $sorgu->execute([$id]);
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Sertifikaları getir
function getSertifikalar($personel_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM personel_sertifikalar WHERE personel_id = ? ORDER BY olusturma_tarihi DESC";
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$personel_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Terapist detay bilgilerini getir
function getTerapistDetay($id) {
    global $pdo;
    try {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM randevular r WHERE r.personel_id = p.id) as toplam_seans
                FROM personel p 
                WHERE p.id = ?";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$id]);
        return $sorgu->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}


// Danışan detay bilgilerini getir
function getDanisanDetay($id) {
    global $pdo;
    try {
        $sql = "SELECT d.*, ut.ad as uyelik_adi
                FROM danisanlar d
                LEFT JOIN uyelik_turleri ut ON d.uyelik_turu_id = ut.id
                WHERE d.id = ?";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$id]);
        return $sorgu->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}

// İlk kayıt tespiti ekle
function ilkKayitEkle($danisan_id, $tespit) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_ilk_kayit_tespitleri (danisan_id, tespit)
                VALUES (:danisan_id, :tespit)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'tespit' => $tespit
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Ölçüm değeri ekle
function olcumEkle($danisan_id, $yag, $kas, $kilo, $posturel_analiz) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_olcum_degerleri (danisan_id, yag, kas, kilo, posturel_analiz)
                VALUES (:danisan_id, :yag, :kas, :kilo, :posturel_analiz)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'yag' => $yag,
            'kas' => $kas,
            'kilo' => $kilo,
            'posturel_analiz' => $posturel_analiz
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Dosya yükleme işlemi
function handleFileUpload($file, $folder) {
    $upload_dir = "uploads/" . $folder . "/";
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed_types)) {
        return [
            'success' => false,
            'message' => 'Sadece PDF, JPG, JPEG veya PNG dosyaları yükleyebilirsiniz!'
        ];
    }

    $filename = time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return [
            'success' => true,
            'filename' => $filename
        ];
    }

    return [
        'success' => false,
        'message' => 'Dosya yüklenirken bir hata oluştu!'
    ];
}

// Rapor ekle
function raporEkle($danisan_id, $rapor) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_doktor_raporlari (danisan_id, rapor)
                VALUES (:danisan_id, :rapor)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'rapor' => $rapor
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Beslenme listesi ekle
function beslenmeListesiEkle($danisan_id, $liste) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_beslenme_listeleri (danisan_id, liste)
                VALUES (:danisan_id, :liste)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'liste' => $liste
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Talep ekle
function talepEkle($danisan_id, $talep) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_talepler (danisan_id, talep)
                VALUES (:danisan_id, :talep)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'talep' => $talep
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Açıklama ekle
function aciklamaEkle($danisan_id, $aciklama) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_aciklamalar (danisan_id, aciklama)
                VALUES (:danisan_id, :aciklama)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'aciklama' => $aciklama
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// İletişim kaydı ekle
function iletisimEkle($danisan_id, $arama_turu, $personel_id, $notlar) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_iletisim (danisan_id, arama_turu, personel_id, notlar)
                VALUES (:danisan_id, :arama_turu, :personel_id, :notlar)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'danisan_id' => $danisan_id,
            'arama_turu' => $arama_turu,
            'personel_id' => $personel_id,
            'notlar' => $notlar
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// İlk kayıtları getir
function getIlkKayitlar($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM danisan_ilk_kayit_tespitleri 
                WHERE danisan_id = ? 
                ORDER BY tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Ölçümleri getir
function getOlcumler($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM danisan_olcum_degerleri 
                WHERE danisan_id = ? 
                ORDER BY tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Raporları getir
function getRaporlar($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM danisan_doktor_raporlari 
                WHERE danisan_id = ? 
                ORDER BY tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Beslenme listelerini getir
function getBeslenmeListe($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM danisan_beslenme_listeleri 
                WHERE danisan_id = ? 
                ORDER BY tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Talepleri getir
function getTalepler($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM danisan_talepler 
                WHERE danisan_id = ? 
                ORDER BY tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Açıklamaları getir
function getAciklamalar($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT * FROM danisan_aciklamalar 
                WHERE danisan_id = ? 
                ORDER BY tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// İletişim kayıtlarını getir
function getIletisimKayitlari($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT i.*, CONCAT(p.ad, ' ', p.soyad) as personel_adi
                FROM danisan_iletisim i
                LEFT JOIN personel p ON i.personel_id = p.id
                WHERE i.danisan_id = ? 
                ORDER BY i.tarih DESC";
        
        $sorgu = $pdo->prepare($sql);
        $sorgu->execute([$danisan_id]);
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

// Kayıt silme fonksiyonları
function ilkKayitSil($id) {
    return kayitSil('danisan_ilk_kayit_tespitleri', $id);
}

function olcumSil($id) {
    return kayitSil('danisan_olcum_degerleri', $id);
}

function raporSil($id) {
    return kayitSil('danisan_doktor_raporlari', $id);
}

function beslenmeListesiSil($id) {
    return kayitSil('danisan_beslenme_listeleri', $id);
}

function talepSil($id) {
    return kayitSil('danisan_talepler', $id);
}

function aciklamaSil($id) {
    return kayitSil('danisan_aciklamalar', $id);
}

function iletisimKaydiSil($id) {
    return kayitSil('danisan_iletisim', $id);
}

// Genel kayıt silme fonksiyonu
function kayitSil($tablo, $id) {
    global $pdo;
    try {
        $sql = "DELETE FROM $tablo WHERE id = ?";
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

// Fix Talep Fonksiyonları
// functions.php dosyasına eklenecek fonksiyonlar

function fixTalepEkle($danisan_id, $gun, $saat, $tekrar_tipi, $notlar = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO fix_talepler (danisan_id, gun, saat, tekrar_tipi, notlar) 
                VALUES (:danisan_id, :gun, :saat, :tekrar_tipi, :notlar)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'danisan_id' => $danisan_id,
            'gun' => $gun,
            'saat' => $saat,
            'tekrar_tipi' => $tekrar_tipi,
            'notlar' => $notlar
        ]);
    } catch(PDOException $e) {
        error_log("Fix talep ekleme hatası: " . $e->getMessage());
        return false;
    }
}

function fixTalepSil($id) {
    global $pdo;
    try {
        // Soft delete - aktif durumunu false yap
        $sql = "UPDATE fix_talepler SET aktif = 0 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    } catch(PDOException $e) {
        error_log("Fix talep silme hatası: " . $e->getMessage());
        return false;
    }
}

function getFixTalepler($danisan_id = null) {
    global $pdo;
    try {
        $sql = "SELECT ft.*, d.ad as danisan_adi, d.soyad as danisan_soyadi 
                FROM fix_talepler ft 
                JOIN danisanlar d ON d.id = ft.danisan_id 
                WHERE ft.aktif = 1";
        
        $params = [];
        if ($danisan_id) {
            $sql .= " AND ft.danisan_id = :danisan_id";
            $params['danisan_id'] = $danisan_id;
        }
        
        $sql .= " ORDER BY d.ad, d.soyad, ft.gun, ft.saat";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Fix talepleri getirme hatası: " . $e->getMessage());
        return [];
    }
}

// AJAX işlemlerini handle eden fonksiyon

// AJAX işlemlerini handle eden fonksiyon
function handleAjaxRequest() {
    header('Content-Type: application/json');
    
    if (!isset($_POST['ajax_action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    switch ($_POST['ajax_action']) {
        case 'fix_talep_ekle':
            $sonuc = fixTalepEkle(
                $_POST['danisan_id'],
                $_POST['gun'],
                $_POST['saat'],
                $_POST['tekrar_tipi'],
                $_POST['notlar'] ?? null
            );
            
            echo json_encode([
                'success' => $sonuc,
                'message' => $sonuc ? 'Fix talep başarıyla eklendi.' : 'Fix talep eklenirken bir hata oluştu.'
            ]);
            break;

        case 'fix_talep_sil':
            $sonuc = fixTalepSil($_POST['talep_id']);
            
            echo json_encode([
                'success' => $sonuc,
                'message' => $sonuc ? 'Fix talep başarıyla silindi.' : 'Fix talep silinirken bir hata oluştu.'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}

// AJAX isteği kontrolü
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    handleAjaxRequest();
}

// Diğer mevcut fonksiyonlar...

// Performans Metrikleri için Fonksiyonlar
function getDanisanArtisOrani() {
    global $pdo;
    try {
        // Bu ayın ve geçen ayın danışan sayılarını al
        $sql = "SELECT 
                (SELECT COUNT(*) FROM danisanlar WHERE MONTH(olusturma_tarihi) = MONTH(CURRENT_DATE)) as bu_ay,
                (SELECT COUNT(*) FROM danisanlar WHERE MONTH(olusturma_tarihi) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)) as gecen_ay";
        
        $sorgu = $pdo->query($sql);
        $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC);
        
        if ($sonuc['gecen_ay'] > 0) {
            return round((($sonuc['bu_ay'] - $sonuc['gecen_ay']) / $sonuc['gecen_ay']) * 100);
        }
        return 0;
    } catch(PDOException $e) {
        return 0;
    }
}

function getRandevuIptalOrani() {
    global $pdo;
    try {
        $sql = "SELECT 
                COUNT(CASE WHEN durum = 'iptal_edildi' THEN 1 END) as iptal_sayisi,
                COUNT(*) as toplam_randevu
                FROM randevular 
                WHERE MONTH(randevu_tarihi) = MONTH(CURRENT_DATE)";
        
        $sorgu = $pdo->query($sql);
        $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC);
        
        if ($sonuc['toplam_randevu'] > 0) {
            return round(($sonuc['iptal_sayisi'] / $sonuc['toplam_randevu']) * 100);
        }
        return 0;
    } catch(PDOException $e) {
        return 0;
    }
}

function getPaketYenilemeOrani() {
    global $pdo;
    try {
        $sql = "SELECT 
                COUNT(DISTINCT d.id) as yenileyen_danisan,
                COUNT(DISTINCT s.danisan_id) as toplam_danisan
                FROM satislar s
                LEFT JOIN danisanlar d ON d.id = s.danisan_id
                WHERE s.olusturma_tarihi >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                AND EXISTS (
                    SELECT 1 FROM satislar s2 
                    WHERE s2.danisan_id = s.danisan_id 
                    AND s2.olusturma_tarihi < s.olusturma_tarihi
                )";
        
        $sorgu = $pdo->query($sql);
        $sonuc = $sorgu->fetch(PDO::FETCH_ASSOC);
        
        if ($sonuc['toplam_danisan'] > 0) {
            return round(($sonuc['yenileyen_danisan'] / $sonuc['toplam_danisan']) * 100);
        }
        return 0;
    } catch(PDOException $e) {
        return 0;
    }
}

// Bildirimler için Fonksiyonlar
function getYarinkiRandevuSayisi() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) as sayi FROM randevular 
                WHERE DATE(randevu_tarihi) = DATE(CURRENT_DATE + INTERVAL 1 DAY)
                AND durum = 'onaylandi'";
        
        $sorgu = $pdo->query($sql);
        return $sorgu->fetch(PDO::FETCH_ASSOC)['sayi'];
    } catch(PDOException $e) {
        return 0;
    }
}

function getBitenPaketSayisi() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) as sayi FROM satislar 
                WHERE expiry_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)";
        
        $sorgu = $pdo->query($sql);
        return $sorgu->fetch(PDO::FETCH_ASSOC)['sayi'];
    } catch(PDOException $e) {
        return 0;
    }
}

function getGeriAramaBekleyenler() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) as sayi FROM danisan_iletisim 
                WHERE tarih >= DATE_SUB(CURRENT_DATE, INTERVAL 24 HOUR)
                AND arama_turu IN ('Yeni Danışan İlk Temas Fizyo', 'Yeni Danışan İlk Temas Diyet')";
        
        $sorgu = $pdo->query($sql);
        return $sorgu->fetch(PDO::FETCH_ASSOC)['sayi'];
    } catch(PDOException $e) {
        return 0;
    }
}


//Hedefler
// Hedef Yönetimi Fonksiyonları
function getHedefTurleri($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM hedef_turleri";
        if ($aktif_only) {
            $sql .= " WHERE aktif = 1";
        }
        $sql .= " ORDER BY sira";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Hedef türlerini getirme hatası: " . $e->getMessage());
        return [];
    }
}

function getDanisanHedefleri($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT 
                dh.*,
                ht.ad as hedef_adi,
                ht.birim
                FROM danisan_hedefleri dh
                JOIN hedef_turleri ht ON ht.id = dh.hedef_turu_id
                WHERE dh.danisan_id = ? AND dh.durum = 'devam_ediyor'
                ORDER BY ht.sira";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Hedefleri getirme hatası: " . $e->getMessage());
        return [];
    }
}

function hedefEkle($danisan_id, $hedef_turu_id, $hedef_deger, $baslangic_deger = null, $bitis_tarihi = null, $notlar = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_hedefleri (
            danisan_id, hedef_turu_id, hedef_deger, baslangic_deger, 
            bitis_tarihi, notlar, durum
        ) VALUES (
            :danisan_id, :hedef_turu_id, :hedef_deger, :baslangic_deger,
            :bitis_tarihi, :notlar, 'devam_ediyor'
        )";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'danisan_id' => $danisan_id,
            'hedef_turu_id' => $hedef_turu_id,
            'hedef_deger' => $hedef_deger,
            'baslangic_deger' => $baslangic_deger,
            'bitis_tarihi' => $bitis_tarihi ? date('Y-m-d', strtotime($bitis_tarihi)) : null,
            'notlar' => $notlar
        ]);

        if (!$result) {
            error_log("Hedef ekleme başarısız: " . print_r($stmt->errorInfo(), true));
            return false;
        }

        return true;
    } catch(PDOException $e) {
        error_log("Hedef ekleme hatası: " . $e->getMessage());
        return false;
    }
}

function hedefOlcumEkle($hedef_id, $olcum_deger, $olcum_tarihi, $notlar = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisan_hedef_gecmisi (
            danisan_hedef_id, olcum_deger, olcum_tarihi, notlar
        ) VALUES (
            :hedef_id, :olcum_deger, :olcum_tarihi, :notlar
        )";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'hedef_id' => $hedef_id,
            'olcum_deger' => $olcum_deger,
            'olcum_tarihi' => date('Y-m-d', strtotime($olcum_tarihi)),
            'notlar' => $notlar
        ]);
    } catch(PDOException $e) {
        error_log("Ölçüm ekleme hatası: " . $e->getMessage());
        return false;
    }
}



// Room management functions



// Room management functions

function createRoomsTable() {
    global $pdo;
    try {
        // Önce rooms tablosunu oluştur
        $sql = "CREATE TABLE IF NOT EXISTS rooms (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(50) NOT NULL,
            type enum('değerlendirme','fizyoterapi','fonksiyonel','salon','egzersiz') NOT NULL,
            capacity int(11) DEFAULT 1,
            aktif tinyint(1) DEFAULT 1,
            olusturma_tarihi timestamp NOT NULL DEFAULT current_timestamp(),
            guncelleme_tarihi timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        
        // Randevular tablosuna room_id kolonu ekle
        $sql = "SHOW COLUMNS FROM randevular LIKE 'room_id'";
        $result = $pdo->query($sql);
        if ($result->rowCount() == 0) {
            $sql = "ALTER TABLE randevular ADD COLUMN room_id int(11) DEFAULT NULL,
                   ADD FOREIGN KEY (room_id) REFERENCES rooms(id)";
            $pdo->exec($sql);
        }
        
        // Default odaları ekle
        $default_rooms = [
            ['Değerlendirme Odası', 'değerlendirme'],
            ['Fizyoterapi Odası 1', 'fizyoterapi'],
            ['Fizyoterapi Odası 2', 'fizyoterapi'],
            ['Fonksiyonel Oda (Üst)', 'fonksiyonel'],
            ['Fonksiyonel Oda (Alt)', 'fonksiyonel'],
            ['Salon', 'salon'],
            ['Egzersiz Odası (Alt)', 'egzersiz']
        ];

        $check_sql = "SELECT COUNT(*) FROM rooms WHERE name = ? AND type = ?";
        $insert_sql = "INSERT INTO rooms (name, type) VALUES (?, ?)";
        
        $check_stmt = $pdo->prepare($check_sql);
        $insert_stmt = $pdo->prepare($insert_sql);
        
        foreach ($default_rooms as $room) {
            $check_stmt->execute([$room[0], $room[1]]);
            if ($check_stmt->fetchColumn() == 0) {
                $insert_stmt->execute($room);
            }
        }

        return true;
    } catch(PDOException $e) {
        error_log("Oda tablosu oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

function getRooms($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM rooms";
        if ($aktif_only) {
            $sql .= " WHERE aktif = TRUE";
        }
        $sql .= " ORDER BY type, name";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Odaları getirme hatası: " . $e->getMessage());
        return [];
    }
}



function getRoomSchedule($date) {
    global $pdo;
    try {
        // Önce tüm aktif odaları al
        $rooms_sql = "SELECT * FROM rooms WHERE aktif = TRUE ORDER BY type, name";
        $rooms_stmt = $pdo->query($rooms_sql);
        $rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $schedule = [];
        
        // Her oda için ayrı ayrı randevuları al
        foreach ($rooms as $room) {
            $sql = "SELECT r.id as room_id, r.name as room_name, r.type as room_type,
                           ran.id as randevu_id, ran.randevu_tarihi, ran.durum,
                           CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                           CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                           st.ad as seans_turu
                    FROM rooms r
                    LEFT JOIN randevular ran ON ran.room_id = r.id 
                        AND DATE(ran.randevu_tarihi) = :date 
                        AND ran.aktif = 1
                    LEFT JOIN danisanlar d ON d.id = ran.danisan_id
                    LEFT JOIN personel p ON p.id = ran.personel_id
                    LEFT JOIN seans_turleri st ON st.id = ran.seans_turu_id
                    WHERE r.id = :room_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'date' => $date,
                'room_id' => $room['id']
            ]);
            
            $schedule[$room['id']] = [
                'room_info' => [
                    'id' => $room['id'],
                    'name' => $room['name'],
                    'type' => $room['type']
                ],
                'appointments' => []
            ];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['randevu_id']) {
                    $time_slot = date('H:i', strtotime($row['randevu_tarihi']));
                    $schedule[$room['id']]['appointments'][$time_slot] = [
                        'id' => $row['randevu_id'],
                        'danisan' => $row['danisan_adi'],
                        'terapist' => $row['terapist_adi'],
                        'seans_turu' => $row['seans_turu'],
                        'durum' => $row['durum']
                    ];
                }
            }
        }
        
        return $schedule;
    } catch(PDOException $e) {
        error_log("Oda programı getirme hatası: " . $e->getMessage());
        return [];
    }
}


function displayRoomSchedule($date) {
    $schedule = getRoomSchedule($date);
    $time_slots = generateTimeSlots('08:00', '21:00', 60);
    
    $html = '<div class="room-schedule">';
    $html .= '<table class="table table-bordered">';
    
    // Header row with room names
    $html .= '<thead><tr><th>Saat</th>';
    foreach ($schedule as $room) {
        $html .= '<th>' . htmlspecialchars($room['room_info']['name']) . '</th>';
    }
    $html .= '</tr></thead>';
    
    // Time slots and appointments
    $html .= '<tbody>';
    foreach ($time_slots as $time) {
        $html .= '<tr>';
        $html .= '<td class="time-column">' . $time . '</td>';
        
        foreach ($schedule as $room) {
            $html .= '<td class="room-cell" data-room-id="' . $room['room_info']['id'] . '" data-time="' . $time . '">';
            if (isset($room['appointments'][$time])) {
                $apt = $room['appointments'][$time];
                $status_class = getStatusBadgeClass($apt['durum']);
                $html .= '<div class="appointment ' . $status_class . '" draggable="true" data-appointment-id="' . $apt['id'] . '">';
                $html .= '<div class="patient">' . htmlspecialchars($apt['danisan']) . '</div>';
                $html .= '<div class="therapist">' . htmlspecialchars($apt['terapist']) . '</div>';
                $html .= '<div class="session-type">' . htmlspecialchars($apt['seans_turu']) . '</div>';
                $html .= '<div class="appointment-actions">';
                $html .= '<button onclick="editAppointment(' . $apt['id'] . ')" class="btn btn-sm btn-primary">Düzenle</button>';
                $html .= '</div>';
                $html .= '</div>';
            } else {
                $slot_datetime = date('Y-m-d', strtotime($date)) . ' ' . $time;
                $html .= '<button onclick="addAppointment(\'' . $slot_datetime . '\', ' . $room['room_info']['id'] . ')" 
                         class="btn btn-sm btn-outline-primary add-appointment">+</button>';
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    
    return $html;
}

function generateTimeSlots($start, $end, $interval = 60) {
    $slots = [];
    $current = strtotime($start);
    $end = strtotime($end);
    
    while ($current <= $end) {
        $slots[] = date('H:i', $current);
        $current = strtotime('+' . $interval . ' minutes', $current);
    }
    
    return $slots;
}

// Create rooms table when this file is included
createRoomsTable();







function hedefSil($hedef_id) {
    global $pdo;
    try {
        $sql = "DELETE FROM danisan_hedefleri WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$hedef_id]);
    } catch(PDOException $e) {
        error_log("Hedef silme hatası: " . $e->getMessage());
        return false;
    }
}

function getHedefDurumClass($durum) {
    switch ($durum) {
        case 'devam_ediyor':
            return 'primary';
        case 'tamamlandi':
            return 'success';
        case 'iptal_edildi':
            return 'danger';
        case 'ertelendi':
            return 'warning';
        default:
            return 'secondary';
    }
}





// Yardımcı Fonksiyonlar
function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}


function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d.m.Y H:i', strtotime($datetime));
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'beklemede':
            return 'warning';
        case 'onaylandi':
            return 'success';
        case 'iptal_edildi':
            return 'danger';
        case 'tamamlandi':
            return 'info';
        case 'ertelendi':
            return 'secondary';
        default:
            return 'primary';
    }
}
?>