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




// -- kategori.php fonksiyonları --
/**
 * Tüm kategorileri döner.
 */
function getKategoriler() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM kategori ORDER BY ad");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Yeni kategori ekler.
 */
function addKategori($ad, $aciklama) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO kategori (ad, aciklama) VALUES (:ad, :aciklama)");
    return $stmt->execute(['ad'=>$ad,'aciklama'=>$aciklama]);
}

/**
 * Danışanın bağlı olduğu kategori ID’lerini döner.
 */
function getDanisanKategoriIds($danisan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT kategori_id FROM danisan_kategori WHERE danisan_id = ?");
    $stmt->execute([$danisan_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Danışanın kategori atamasını günceller.
 * Mevcutlarını siler, yenilerini ekler.
 */
function updateDanisanKategorileri($danisan_id, array $kategori_ids) {
    global $pdo;
    $pdo->beginTransaction();
    // önce sil
    $pdo->prepare("DELETE FROM danisan_kategori WHERE danisan_id = ?")
        ->execute([$danisan_id]);
    // yenilerini ekle
    $stmt = $pdo->prepare("INSERT INTO danisan_kategori (danisan_id, kategori_id) VALUES (?, ?)");
    foreach ($kategori_ids as $kid) {
        $stmt->execute([$danisan_id, $kid]);
    }
    return $pdo->commit();
}



// functions.php içine ekleyin
function getDanisanNotlari($danisanId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT not_tarihi, icerik, personel_id
        FROM personel_notlari
        WHERE danisan_id = ?
        ORDER BY not_tarihi DESC
    ");
    $stmt->execute([$danisanId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// functions.php içinde

/**
 * Filtrelenmiş danışan sayısını döner (arama + kategori).
 */
function getDanisanlarCountFiltered($arama, $kategori_id = null) {
    global $pdo;
    $sql = "SELECT COUNT(DISTINCT d.id)
            FROM danisanlar d
            LEFT JOIN danisan_kategori dk ON dk.danisan_id = d.id
            WHERE (d.ad    LIKE :arama
                OR d.soyad LIKE :arama
                OR d.email LIKE :arama)";
    if ($kategori_id) {
        $sql .= " AND dk.kategori_id = :kategori_id";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('arama', "%$arama%", PDO::PARAM_STR);
    if ($kategori_id) {
        $stmt->bindValue('kategori_id', $kategori_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/**
 * Filtreli + pageli danışanları döner; her kayda GROUP_CONCAT ile kategori adı ekler.
 */
function getDanisanlarPagingFiltered($arama, $limit, $offset, $kategori_id = null) {
    global $pdo;
    $sql = "SELECT
                d.*,
                ut.ad AS uyelik_adi,
                GROUP_CONCAT(k.ad SEPARATOR ', ') AS kategoriler
            FROM danisanlar d
            LEFT JOIN uyelik_turleri ut
              ON ut.id = d.uyelik_turu_id
            LEFT JOIN danisan_kategori dk
              ON dk.danisan_id = d.id
            LEFT JOIN kategori k
              ON k.id = dk.kategori_id
            WHERE (d.ad    LIKE :arama
                OR d.soyad LIKE :arama
                OR d.email LIKE :arama)";
    if ($kategori_id) {
        $sql .= " AND dk.kategori_id = :kategori_id";
    }
    $sql .= " GROUP BY d.id
              ORDER BY d.ad, d.soyad
              LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('arama', "%$arama%", PDO::PARAM_STR);
    if ($kategori_id) {
        $stmt->bindValue('kategori_id', $kategori_id, PDO::PARAM_INT);
    }
    $stmt->bindValue('offset', (int)$offset, PDO::PARAM_INT);
    $stmt->bindValue('limit',  (int)$limit,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

function deleteInstallmentsBySale($saleId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM taksitler WHERE satis_id = ?");
    $stmt->execute([$saleId]);
}
function deletePaymentsBySale($saleId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM odemeler WHERE satis_id = ?");
    $stmt->execute([$saleId]);
}
function deleteSaleById($saleId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM satislar WHERE id = ?");
    $stmt->execute([$saleId]);
}



function getKategoriler1(): array {
    global $pdo;
    // Kendi kategori tablonuzun adını ve sütununu kullanın
    $sql = "SELECT id, ad FROM kategori ORDER BY ad";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Danışan notu ekler
 */
function danisanNotEkle(int $danisanId, int $kategoriId, string $notTarihi, string $icerik): bool {
    global $pdo;
    $sql = "INSERT INTO danisan_not (danisan_id,kategori_id,not_tarihi,icerik) \
            VALUES (:did,:kid,:nt,:ic)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':did' => $danisanId,
        ':kid' => $kategoriId,
        ':nt'  => $notTarihi,
        ':ic'  => $icerik,
    ]);
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

function getTerapistler11($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT 
                    p.id,
                    CONCAT(p.ad, ' ', p.soyad) as ad_soyad,
                    p.ad,
                    p.soyad,
                    p.telefon,
                    p.email,
                    p.uzmanlik_alani,
                    p.aktif
                FROM personel p 
                WHERE p.rol = 'terapist'";
        
        if ($aktif_only) {
            $sql .= " AND p.aktif = 1";
        }
        
        $sql .= " ORDER BY p.ad, p.soyad";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Terapistleri getirme hatası: " . $e->getMessage());
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
function getDanisanlarWithRemainingAppointments11() {
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

function getDanisanlarWithRemainingAppointments() {
    global $pdo;
    try {
        $sql = "SELECT 
                    d.id,
                    CONCAT(d.ad, ' ', d.soyad, ' - ', st.ad) as ad_soyad,
                    d.telefon,
                    d.email,
                    s.id as aktif_satis_id,
                    s.hizmet_paketi_id,
                    st.ad as seans_turu,
                    st.id as seans_turu_id,
                    (st.seans_adet + COALESCE(s.hediye_seans, 0)) as toplam_seans,
                    COUNT(r.id) as kullanilan_seans,
                    (st.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) as kalan_seans,
                    s.toplam_tutar,
                    s.odenen_tutar,
                    s.toplam_tutar - s.odenen_tutar as kalan_borc
                FROM danisanlar d
                JOIN satislar s ON d.id = s.danisan_id AND s.aktif = 1
                JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
                LEFT JOIN randevular r ON s.id = r.satis_id AND r.aktif = 1
                WHERE d.aktif = 1 
                GROUP BY d.id, s.id
                HAVING kalan_seans > 0
                ORDER BY d.ad, d.soyad, st.ad";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Danışanları getirme hatası: " . $e->getMessage());
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


function getRandevular() {
    global $pdo;
    
    $sql = "SELECT r.*, 
           CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
           CONCAT(p.ad, ' ', p.soyad) as personel_adi,
           st.ad as seans_turu,
           st.sure,
           st.evaluation_interval,
           rm.name as room_name,
           (
               SELECT COUNT(*) 
               FROM randevular r2 
               WHERE r2.satis_id = r.satis_id 
               AND r2.aktif = 1 
               AND r2.randevu_tarihi <= r.randevu_tarihi
           ) as seans_sirasi,
           CASE 
               WHEN st.evaluation_interval > 0 THEN
                   CASE 
                       WHEN (
                           SELECT COUNT(*) 
                           FROM randevular r2 
                           WHERE r2.satis_id = r.satis_id 
                           AND r2.aktif = 1 
                           AND r2.randevu_tarihi <= r.randevu_tarihi
                       ) = 1 THEN 'initial'
                       WHEN (
                           SELECT COUNT(*) 
                           FROM randevular r2 
                           WHERE r2.satis_id = r.satis_id 
                           AND r2.aktif = 1 
                           AND r2.randevu_tarihi <= r.randevu_tarihi
                       ) % st.evaluation_interval = 0 THEN 'progress'
                       ELSE NULL
                   END
               ELSE NULL
           END as evaluation_type,
           CASE 
               WHEN st.evaluation_interval > 0 THEN
                   FLOOR((
                       SELECT COUNT(*) 
                       FROM randevular r2 
                       WHERE r2.satis_id = r.satis_id 
                       AND r2.aktif = 1 
                       AND r2.randevu_tarihi <= r.randevu_tarihi
                   ) / st.evaluation_interval)
               ELSE NULL
           END as evaluation_number
    FROM randevular r
    JOIN danisanlar d ON d.id = r.danisan_id
    JOIN personel p ON p.id = r.personel_id
    JOIN seans_turleri st ON st.id = r.seans_turu_id
    LEFT JOIN rooms rm ON rm.id = r.room_id
    WHERE r.aktif = 1
    ORDER BY r.randevu_tarihi ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getAktifSatisByDanisanId($danisan_id) {
    global $pdo;
    $sorgu = $pdo->prepare("
        SELECT * FROM satislar 
        WHERE danisan_id = ? AND aktif = 1 
        ORDER BY olusturma_tarihi DESC 
        LIMIT 1
    ");
    $sorgu->execute([$danisan_id]);
    return $sorgu->fetch(PDO::FETCH_ASSOC);
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




function danisanEkle($ad, $soyad, $email, $telefon, $adres, $yas, $meslek, $vergi_dairesi = null, $vergi_numarasi = null, $fatura_adresi = null) {
    global $pdo;
    try {
        $sql = "INSERT INTO danisanlar (ad, soyad, email, telefon, vergi_dairesi, vergi_numarasi, fatura_adresi, adres, yas, meslek) 
                VALUES (:ad, :soyad, :email, :telefon, :vergi_dairesi, :vergi_numarasi, :fatura_adresi, :adres, :yas, :meslek)";
        
        $sorgu = $pdo->prepare($sql);
        return $sorgu->execute([
            'ad' => $ad,
            'soyad' => $soyad,
            'email' => $email,
            'telefon' => $telefon,
            'vergi_dairesi' => $vergi_dairesi,
            'vergi_numarasi' => $vergi_numarasi,
            'fatura_adresi' => $fatura_adresi,
            'adres' => $adres,
            'yas' => $yas,
            'meslek' => $meslek
        ]);
    } catch(PDOException $e) {
        error_log("Danışan ekleme hatası: " . $e->getMessage());
        return false;
    }
}

// Danışan güncelleme - YENİ ALANLAR EKLENDİ
function danisanGuncelle($id, $ad, $soyad, $email, $telefon, $adres, $yas, $meslek, $uyelik_turu_id = null, $vergi_dairesi = null, $vergi_numarasi = null, $fatura_adresi = null) {
    global $pdo;
    try {
        $sql = "UPDATE danisanlar 
                SET ad = :ad, 
                    soyad = :soyad, 
                    email = :email, 
                    telefon = :telefon,
                    vergi_dairesi = :vergi_dairesi,
                    vergi_numarasi = :vergi_numarasi,
                    fatura_adresi = :fatura_adresi,
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
            'vergi_dairesi' => $vergi_dairesi,
            'vergi_numarasi' => $vergi_numarasi,
            'fatura_adresi' => $fatura_adresi,
            'adres' => $adres,
            'yas' => $yas,
            'meslek' => $meslek,
            'uyelik_turu_id' => $uyelik_turu_id
        ]);
    } catch(PDOException $e) {
        error_log("Danışan güncelleme hatası: " . $e->getMessage());
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
function getSeansTurleri1($aktif_only = true) {
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
function getSeansTurleri($aktif_only = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM seans_turleri";
        if ($aktif_only) {
            $sql .= " WHERE aktif = 1";
        }
        $sql .= " ORDER BY ad";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Seans türlerini getirme hatası: " . $e->getMessage());
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


function getDanisanAktifSatisGelismis($danisan_id) {
    global $pdo;
    try {
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
                WHERE s.danisan_id = ? 
                AND s.aktif = 1 
                GROUP BY s.id
                HAVING kalan_seans > 0
                ORDER BY s.olusturma_tarihi DESC
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            error_log("Danışan $danisan_id için aktif satış bulunamadı");
            return false;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Danışan satış bilgisi hatası: " . $e->getMessage());
        return false;
    }
}

function getDanisanAktifSatis($danisan_id) {
    global $pdo;
    try {
        $sql = "SELECT 
                    s.*,
                    st.ad as seans_turu,
                    st.sure,
                    st.evaluation_interval,
                    (s.seans_adet + COALESCE(s.hediye_seans, 0)) as toplam_seans,
                    COUNT(r.id) as kullanilan_seans,
                    (s.seans_adet + COALESCE(s.hediye_seans, 0)) - COUNT(r.id) as kalan_seans,
                    COALESCE(SUM(o.tutar), 0) as odenen_tutar,
                    s.toplam_tutar - COALESCE(SUM(o.tutar), 0) as kalan_borc
                FROM satislar s
                JOIN seans_turleri st ON s.hizmet_paketi_id = st.id
                LEFT JOIN randevular r ON s.id = r.satis_id AND r.aktif = 1
                LEFT JOIN odemeler o ON s.id = o.satis_id
                WHERE s.danisan_id = ? 
                AND s.aktif = 1 
                AND s.durum IN ('onaylandi', 'devam_ediyor')
                GROUP BY s.id
                HAVING kalan_seans > 0
                ORDER BY s.id DESC
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$danisan_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Danışan satış bilgisi hatası: " . $e->getMessage());
        return false;
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


function randevuEkleGelismis($danisan_id, $personel_id, $seans_turu_id, $room_id, $randevu_tarihi, $notlar = '') {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Kilitleme kontrolü
        $tarih = date('Y-m-d', strtotime($randevu_tarihi));
        $saat = date('H:i:s', strtotime($randevu_tarihi));
        
        if (odaSaatKilitliMi($room_id, $tarih, $saat)) {
            throw new Exception('Bu oda ve saat kilitlidir');
        }
        
        // 2. Çakışma kontrolü
        $conflict_sql = "SELECT id FROM randevular 
                        WHERE room_id = ? AND randevu_tarihi = ? AND aktif = 1";
        $conflict_stmt = $pdo->prepare($conflict_sql);
        $conflict_stmt->execute([$room_id, $randevu_tarihi]);
        
        if ($conflict_stmt->fetch()) {
            throw new Exception('Bu oda ve saatte başka bir randevu bulunmaktadır');
        }
        
        // 3. Danışanın aktif satışını kontrol et
        $satis = getDanisanAktifSatisGelismis($danisan_id);
        if (!$satis) {
            throw new Exception('Bu danışan için aktif ve kullanılabilir satış bulunamadı');
        }
        
        if ($satis['kalan_seans'] <= 0) {
            throw new Exception('Bu danışanın tüm seansları kullanılmış');
        }
        
        // 4. Ödeme kontrolü - en az %50 ödenmiş olmalı
        $odeme_yuzdesi = ($satis['odenen_tutar'] / $satis['toplam_tutar']) * 100;
        if ($odeme_yuzdesi < 50) {
            throw new Exception('Randevu alabilmek için en az %50 ödeme yapılmalıdır. Mevcut ödeme: %' . number_format($odeme_yuzdesi, 1));
        }
        
        // 5. Kaçıncı seans olduğunu hesapla
        $seans_sirasi_sql = "SELECT COUNT(*) + 1 FROM randevular 
                            WHERE satis_id = ? AND aktif = 1";
        $seans_stmt = $pdo->prepare($seans_sirasi_sql);
        $seans_stmt->execute([$satis['id']]);
        $seans_sirasi = $seans_stmt->fetchColumn();
        
        // 6. Değerlendirme randevusu mu kontrol et
        $evaluation_type = null;
        $evaluation_number = null;
        
        if ($satis['evaluation_interval'] > 0) {
            if ($seans_sirasi == 1) {
                $evaluation_type = 'initial';
            } elseif ($seans_sirasi % $satis['evaluation_interval'] == 0) {
                $evaluation_type = 'progress';
                $evaluation_number = floor($seans_sirasi / $satis['evaluation_interval']);
            }
        }
        
        // 7. Randevuyu ekle
        $insert_sql = "INSERT INTO randevular 
                       (danisan_id, personel_id, seans_turu_id, room_id, randevu_tarihi, 
                        notlar, satis_id, durum, evaluation_type, evaluation_number) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'onaylandi', ?, ?)";
        
        $insert_stmt = $pdo->prepare($insert_sql);
        $result = $insert_stmt->execute([
            $danisan_id, $personel_id, $seans_turu_id, $room_id, 
            $randevu_tarihi, $notlar, $satis['id'], $evaluation_type, $evaluation_number
        ]);
        
        if (!$result) {
            throw new Exception('Randevu eklenirken veritabanı hatası oluştu');
        }
        
        $randevu_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Randevu başarıyla eklendi',
            'randevu_id' => $randevu_id,
            'seans_sirasi' => $seans_sirasi,
            'kalan_seans' => $satis['kalan_seans'] - 1,
            'evaluation_type' => $evaluation_type
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Randevu güncelleme - geliştirilmiş kontroller ile
 */
function randevuGuncelleGelismis($randevu_id, $danisan_id, $personel_id, $seans_turu_id, $room_id, $randevu_tarihi, $notlar = '', $evaluation_notes = '') {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. Mevcut randevuyu getir
        $current_sql = "SELECT * FROM randevular WHERE id = ? AND aktif = 1";
        $current_stmt = $pdo->prepare($current_sql);
        $current_stmt->execute([$randevu_id]);
        $current = $current_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            throw new Exception('Güncellenecek randevu bulunamadı');
        }
        
        // 2. Oda/saat değişiyorsa kilitleme kontrolü
        if ($current['room_id'] != $room_id || $current['randevu_tarihi'] != $randevu_tarihi) {
            $tarih = date('Y-m-d', strtotime($randevu_tarihi));
            $saat = date('H:i:s', strtotime($randevu_tarihi));
            
            if (odaSaatKilitliMi($room_id, $tarih, $saat)) {
                throw new Exception('Bu oda ve saat kilitlidir');
            }
            
            // Çakışma kontrolü (kendi randevusu hariç)
            $conflict_sql = "SELECT id FROM randevular 
                            WHERE room_id = ? AND randevu_tarihi = ? AND aktif = 1 AND id != ?";
            $conflict_stmt = $pdo->prepare($conflict_sql);
            $conflict_stmt->execute([$room_id, $randevu_tarihi, $randevu_id]);
            
            if ($conflict_stmt->fetch()) {
                throw new Exception('Bu oda ve saatte başka bir randevu bulunmaktadır');
            }
        }
        
        // 3. Randevuyu güncelle
        $update_sql = "UPDATE randevular SET 
                       danisan_id = ?, personel_id = ?, seans_turu_id = ?, 
                       room_id = ?, randevu_tarihi = ?, notlar = ?, evaluation_notes = ?
                       WHERE id = ? AND aktif = 1";
        
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([
            $danisan_id, $personel_id, $seans_turu_id, $room_id, 
            $randevu_tarihi, $notlar, $evaluation_notes, $randevu_id
        ]);
        
        if (!$result) {
            throw new Exception('Randevu güncellenirken veritabanı hatası oluştu');
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Randevu başarıyla güncellendi'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
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
       // $sql = "SELECT * FROM personel WHERE rol = 'satis' AND aktif = 1 ORDER BY ad, soyad";
       $sql = "SELECT * FROM personel WHERE rol = 'terapist' AND aktif = 1 ORDER BY ad, soyad";
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

// Ödeme türlerini getiren fonksiyon (functions.php'ye eklenecek)
function getOdemeTurleri() {
    global $pdo;
    try {
        $sql = "SELECT id, kod, ad, aciklama FROM odeme_turleri WHERE aktif = 1 ORDER BY ad";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Ödeme türleri getirme hatası: " . $e->getMessage());
        return [];
    }
}


function getSatisTurleri() {
    global $pdo;
    try {
        $sql = "SELECT id, ad, aciklama FROM satis_turleri WHERE aktif = 1 ORDER BY ad";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Satış türleri getirme hatası: " . $e->getMessage());
        return [];
    }
}

function satisEkle($danisan_id, $hizmet_paketi_id, $personel_id, $toplam_tutar, $odenen_tutar, $vade_tarihi = null, $hediye_seans = 0, $indirim_tutari = 0, $indirim_yuzdesi = null, $notlar = null, $satis_turu_id = 1, $islem_login_id = null, $odeme_turu_id = null) {
    global $pdo;
    try {
        $satis_id = generateUUID();
        
        $sql = "INSERT INTO satislar (
                    id, danisan_id, hizmet_paketi_id, personel_id,
                    toplam_tutar, odenen_tutar, odeme_turu_id,
                    vade_tarihi, hediye_seans, indirim_tutari,
                    indirim_yuzdesi, durum, notlar, satis_turu_id, islem_login_id
                ) VALUES (
                    :id, :danisan_id, :hizmet_paketi_id, :personel_id,
                    :toplam_tutar, :odenen_tutar, :odeme_turu_id,
                    :vade_tarihi, :hediye_seans, :indirim_tutari,
                    :indirim_yuzdesi, 'beklemede', :notlar, :satis_turu_id, :islem_login_id
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $satis_id,
            'danisan_id' => $danisan_id,
            'hizmet_paketi_id' => $hizmet_paketi_id,
            'personel_id' => $personel_id,
            'toplam_tutar' => $toplam_tutar,
            'odenen_tutar' => $odenen_tutar,
            'odeme_turu_id' => $odeme_turu_id,
            'vade_tarihi' => $vade_tarihi,
            'hediye_seans' => $hediye_seans,
            'indirim_tutari' => $indirim_tutari,
            'indirim_yuzdesi' => $indirim_yuzdesi,
            'notlar' => $notlar,
            'satis_turu_id' => $satis_turu_id,
            'islem_login_id' => $islem_login_id
        ]);
        
        return $satis_id;
    } catch(PDOException $e) {
        error_log("Satış ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

function satisEkleTemmuz27($danisan_id, $hizmet_paketi_id, $personel_id, $toplam_tutar, $odenen_tutar, $odeme_tipi, $vade_tarihi = null, $hediye_seans = 0, $indirim_tutari = 0, $indirim_yuzdesi = null,$notlar = null) {
    global $pdo;
    try {
        $satis_id = generateUUID();
        
        $sql = "INSERT INTO satislar (
                    id, danisan_id, hizmet_paketi_id, personel_id,
                    toplam_tutar, odenen_tutar, odeme_tipi,
                    vade_tarihi, hediye_seans, indirim_tutari,
                    indirim_yuzdesi, durum,notlar
                ) VALUES (
                    :id, :danisan_id, :hizmet_paketi_id, :personel_id,
                    :toplam_tutar, :odenen_tutar, :odeme_tipi,
                    :vade_tarihi, :hediye_seans, :indirim_tutari,
                    :indirim_yuzdesi, 'beklemede', :notlar
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
            'indirim_yuzdesi' => $indirim_yuzdesi,
             'notlar' => $notlar
        ]);
        
        return $satis_id;
    } catch(PDOException $e) {
        error_log("Satış ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

// Güncellenmiş taksitEkle fonksiyonu - sadece odeme_turu_id
function taksitEkle($satis_id, $tutar, $vade_tarihi, $odeme_turu_id = null) {
    global $pdo;
    try {
        $taksit_id = generateUUID();
        
        $sql = "INSERT INTO taksitler (
                    id, satis_id, tutar, vade_tarihi, odeme_turu_id
                ) VALUES (
                    :id, :satis_id, :tutar, :vade_tarihi, :odeme_turu_id
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $taksit_id,
            'satis_id' => $satis_id,
            'tutar' => $tutar,
            'vade_tarihi' => $vade_tarihi,
            'odeme_turu_id' => $odeme_turu_id
        ]);
        
        return $taksit_id;
    } catch(PDOException $e) {
        error_log("Taksit ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}


// Güncellenmiş odemeEkle fonksiyonu (functions.php'de güncellenecek)
// Temizlenmiş odemeEkle fonksiyonu - sadece odeme_turu_id
function odemeEkle($satis_id, $tutar, $odeme_tarihi, $notlar = null, $odeme_turu_id = null) {
    global $pdo;
    try {
        $odeme_id = generateUUID();
        
        $sql = "INSERT INTO odemeler (
                    id, satis_id, tutar, odeme_turu_id,
                    odeme_tarihi, notlar
                ) VALUES (
                    :id, :satis_id, :tutar, :odeme_turu_id,
                    :odeme_tarihi, :notlar
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $odeme_id,
            'satis_id' => $satis_id,
            'tutar' => $tutar,
            'odeme_turu_id' => $odeme_turu_id,
            'odeme_tarihi' => $odeme_tarihi,
            'notlar' => $notlar
        ]);
        
        return $odeme_id;
    } catch(PDOException $e) {
        error_log("Ödeme ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

function odemeEkleTemmuz($satis_id, $tutar, $odeme_tipi, $odeme_tarihi) {
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

function getSatislar11() {
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

function getSatislar() {
    global $pdo;
    try {
        $sql = "SELECT s.*, 
                       s.faturalandi,  -- Faturalama durumu kolonu eklendi
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
        $sql .= " ORDER BY id,type, name";
        
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
        // Get all active rooms
        $rooms_sql = "SELECT * FROM rooms WHERE aktif = TRUE ORDER BY type, name";
        $rooms_stmt = $pdo->query($rooms_sql);
        $rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $schedule = [];
        
        foreach ($rooms as $room) {
            $sql = "SELECT 
                r.id as room_id, 
                r.name as room_name, 
                r.type as room_type,
                ran.id as randevu_id, 
                ran.randevu_tarihi, 
                ran.durum,
                ran.evaluation_type,
                ran.evaluation_notes,
                CONCAT(d.ad, ' ', d.soyad) as danisan_adi,
                CONCAT(p.ad, ' ', p.soyad) as terapist_adi,
                st.ad as seans_turu,
                st.evaluation_interval,
                st.sure,
                (
                    SELECT COUNT(*) 
                    FROM randevular prev 
                    WHERE prev.danisan_id = ran.danisan_id 
                    AND prev.seans_turu_id = ran.seans_turu_id 
                    AND prev.randevu_tarihi <= ran.randevu_tarihi 
                    AND prev.aktif = 1
                ) as seans_sirasi
            FROM rooms r
            LEFT JOIN randevular ran ON ran.room_id = r.id 
                AND DATE(ran.randevu_tarihi) = :date
                AND ran.aktif = 1
            LEFT JOIN danisanlar d ON d.id = ran.danisan_id
            LEFT JOIN personel p ON p.id = ran.personel_id
            LEFT JOIN seans_turleri st ON st.id = ran.seans_turu_id
            WHERE r.id = :room_id
            ORDER BY ran.randevu_tarihi ASC";
            
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
                    
                    // Calculate evaluation information
                    $evaluation_info = '';
                    $evaluation_type = '';
                    $evaluation_number = null;
                    
                    if ($row['evaluation_interval'] > 0) {
                        $session_number = $row['seans_sirasi'];
                        
                        if ($session_number == 1) {
                            $evaluation_type = 'initial';
                            $evaluation_info = 'İlk Değerlendirme';
                        } elseif ($session_number % $row['evaluation_interval'] == 0) {
                            $evaluation_type = 'progress';
                            $evaluation_number = floor($session_number / $row['evaluation_interval']);
                            $evaluation_info = $evaluation_number . '. Değerlendirme';
                        }
                    }
                    
                    $schedule[$room['id']]['appointments'][$time_slot] = [
                        'id' => $row['randevu_id'],
                        'danisan' => $row['danisan_adi'],
                        'terapist' => $row['terapist_adi'],
                        'seans_turu' => $row['seans_turu'],
                        'durum' => $row['durum'],
                        'evaluation_type' => $evaluation_type,
                        'evaluation_number' => $evaluation_number,
                        'evaluation_notes' => $row['evaluation_notes'],
                        'sure' => $row['sure'],
                        'seans_sirasi' => $row['seans_sirasi'],
                        'evaluation_info' => $evaluation_info
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



// UUID oluşturma fonksiyonu (eğer yoksa)
if (!function_exists('generateUUID')) {
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// ====== GİDER SİSTEMİ FONKSİYONLARI ======

// Gider kategorilerini getir
function getGiderKategorileri() {
    global $pdo;
    try {
        $sql = "SELECT id, ad, aciklama FROM gider_kategorileri WHERE aktif = 1 ORDER BY ad";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Gider kategorileri getirme hatası: " . $e->getMessage());
        return [];
    }
}

// Harcama türlerini getir
function getHarcamaTurleri() {
    global $pdo;
    try {
        $sql = "SELECT id, ad, aciklama FROM harcama_turleri WHERE aktif = 1 ORDER BY ad";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Harcama türleri getirme hatası: " . $e->getMessage());
        return [];
    }
}

// Gider ekle
function giderEkle($tarih, $kategori_id, $aciklama, $tutar, $harcama_turu_id, $fatura_no = null, $tedarikci = null, $notlar = null, $kayit_yapan_id = null) {
    global $pdo;
    try {
        $gider_id = generateUUID();
        
        $sql = "INSERT INTO giderler (
                    id, tarih, kategori_id, aciklama, tutar, 
                    harcama_turu_id, odenmemis_kalan, fatura_no, 
                    tedarikci, notlar, kayit_yapan_id
                ) VALUES (
                    :id, :tarih, :kategori_id, :aciklama, :tutar,
                    :harcama_turu_id, :odenmemis_kalan, :fatura_no,
                    :tedarikci, :notlar, :kayit_yapan_id
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $gider_id,
            'tarih' => $tarih,
            'kategori_id' => $kategori_id,
            'aciklama' => $aciklama,
            'tutar' => $tutar,
            'harcama_turu_id' => $harcama_turu_id,
            'odenmemis_kalan' => $tutar, // Başlangıçta tüm tutar ödenmemiş
            'fatura_no' => $fatura_no,
            'tedarikci' => $tedarikci,
            'notlar' => $notlar,
            'kayit_yapan_id' => $kayit_yapan_id
        ]);
        
        return $gider_id;
    } catch(PDOException $e) {
        error_log("Gider ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

// Gider ödemesi ekle
function giderOdemeEkle($gider_id, $odeme_tarihi, $tutar, $odeme_yontemi, $aciklama = null, $kayit_yapan_id = null) {
    global $pdo;
    try {
        $odeme_id = generateUUID();
        
        $sql = "INSERT INTO gider_odemeleri (
                    id, gider_id, odeme_tarihi, tutar,
                    odeme_yontemi, aciklama, kayit_yapan_id
                ) VALUES (
                    :id, :gider_id, :odeme_tarihi, :tutar,
                    :odeme_yontemi, :aciklama, :kayit_yapan_id
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $odeme_id,
            'gider_id' => $gider_id,
            'odeme_tarihi' => $odeme_tarihi,
            'tutar' => $tutar,
            'odeme_yontemi' => $odeme_yontemi,
            'aciklama' => $aciklama,
            'kayit_yapan_id' => $kayit_yapan_id
        ]);
        
        return $odeme_id;
    } catch(PDOException $e) {
        error_log("Gider ödeme ekleme hatası: " . $e->getMessage());
        throw $e;
    }
}

// Giderleri listele (filtreleme ile)
function getGiderler($tarih_baslangic = null, $tarih_bitis = null, $kategori_id = null, $harcama_turu_id = null, $durum = null) {
    global $pdo;
    try {
        $where_conditions = ["g.aktif = 1"];
        $params = [];
        
        if ($tarih_baslangic) {
            $where_conditions[] = "g.tarih >= :tarih_baslangic";
            $params['tarih_baslangic'] = $tarih_baslangic;
        }
        
        if ($tarih_bitis) {
            $where_conditions[] = "g.tarih <= :tarih_bitis";
            $params['tarih_bitis'] = $tarih_bitis;
        }
        
        if ($kategori_id) {
            $where_conditions[] = "g.kategori_id = :kategori_id";
            $params['kategori_id'] = $kategori_id;
        }
        
        if ($harcama_turu_id) {
            $where_conditions[] = "g.harcama_turu_id = :harcama_turu_id";
            $params['harcama_turu_id'] = $harcama_turu_id;
        }
        
        if ($durum) {
            $where_conditions[] = "g.durum = :durum";
            $params['durum'] = $durum;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    g.*,
                    gk.ad as kategori_adi,
                    ht.ad as harcama_turu_adi,
                    CONCAT(p.ad, ' ', p.soyad) as kayit_yapan_adi,
                    (SELECT SUM(tutar) FROM gider_odemeleri WHERE gider_id = g.id AND aktif = 1) as odenen_tutar
                FROM giderler g
                LEFT JOIN gider_kategorileri gk ON g.kategori_id = gk.id
                LEFT JOIN harcama_turleri ht ON g.harcama_turu_id = ht.id
                LEFT JOIN personel p ON g.kayit_yapan_id = p.id
                WHERE {$where_clause}
                ORDER BY g.tarih DESC, g.olusturma_tarihi DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Giderler getirme hatası: " . $e->getMessage());
        return [];
    }
}

// Gider detayını getir (ödemeler ile birlikte)
function getGiderDetay($gider_id) {
    global $pdo;
    try {
        // Gider bilgisi
        $sql = "SELECT 
                    g.*,
                    gk.ad as kategori_adi,
                    ht.ad as harcama_turu_adi,
                    CONCAT(p.ad, ' ', p.soyad) as kayit_yapan_adi
                FROM giderler g
                LEFT JOIN gider_kategorileri gk ON g.kategori_id = gk.id
                LEFT JOIN harcama_turleri ht ON g.harcama_turu_id = ht.id
                LEFT JOIN personel p ON g.kayit_yapan_id = p.id
                WHERE g.id = :gider_id AND g.aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gider_id' => $gider_id]);
        $gider = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$gider) {
            return null;
        }
        
        // Ödemeler
        $sql = "SELECT 
                    go.*,
                    CONCAT(p.ad, ' ', p.soyad) as kayit_yapan_adi
                FROM gider_odemeleri go
                LEFT JOIN personel p ON go.kayit_yapan_id = p.id
                WHERE go.gider_id = :gider_id AND go.aktif = 1
                ORDER BY go.odeme_tarihi DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['gider_id' => $gider_id]);
        $odemeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'gider' => $gider,
            'odemeler' => $odemeler
        ];
    } catch(PDOException $e) {
        error_log("Gider detay getirme hatası: " . $e->getMessage());
        return null;
    }
}

// Gider güncelle
function giderGuncelle($gider_id, $tarih, $kategori_id, $aciklama, $tutar, $harcama_turu_id, $fatura_no = null, $tedarikci = null, $notlar = null) {
    global $pdo;
    try {
        $sql = "UPDATE giderler SET 
                    tarih = :tarih,
                    kategori_id = :kategori_id,
                    aciklama = :aciklama,
                    tutar = :tutar,
                    harcama_turu_id = :harcama_turu_id,
                    fatura_no = :fatura_no,
                    tedarikci = :tedarikci,
                    notlar = :notlar,
                    guncelleme_tarihi = CURRENT_TIMESTAMP
                WHERE id = :gider_id AND aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'gider_id' => $gider_id,
            'tarih' => $tarih,
            'kategori_id' => $kategori_id,
            'aciklama' => $aciklama,
            'tutar' => $tutar,
            'harcama_turu_id' => $harcama_turu_id,
            'fatura_no' => $fatura_no,
            'tedarikci' => $tedarikci,
            'notlar' => $notlar
        ]);
    } catch(PDOException $e) {
        error_log("Gider güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

// Gider sil (soft delete)
function giderSil($gider_id) {
    global $pdo;
    try {
        $sql = "UPDATE giderler SET aktif = 0, guncelleme_tarihi = CURRENT_TIMESTAMP WHERE id = :gider_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['gider_id' => $gider_id]);
    } catch(PDOException $e) {
        error_log("Gider silme hatası: " . $e->getMessage());
        return false;
    }
}

// Aylık gider özetini getir
function getAylikGiderOzeti($yil = null, $ay = null) {
    global $pdo;
    
    if (!$yil) $yil = date('Y');
    if (!$ay) $ay = date('m');
    
    try {
        $sql = "SELECT 
                    gk.ad as kategori,
                    COUNT(g.id) as adet,
                    SUM(g.tutar) as toplam_tutar,
                    SUM(COALESCE((SELECT SUM(tutar) FROM gider_odemeleri WHERE gider_id = g.id AND aktif = 1), 0)) as odenen_tutar,
                    SUM(g.odenmemis_kalan) as kalan_tutar
                FROM giderler g
                LEFT JOIN gider_kategorileri gk ON g.kategori_id = gk.id
                WHERE g.aktif = 1 
                AND YEAR(g.tarih) = :yil 
                AND MONTH(g.tarih) = :ay
                GROUP BY g.kategori_id, gk.ad
                ORDER BY toplam_tutar DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['yil' => $yil, 'ay' => $ay]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Aylık gider özeti hatası: " . $e->getMessage());
        return [];
    }
}



// İzin Yönetimi Fonksiyonları
function getPersoneller() {
    global $pdo;
    try {
        $sql = "SELECT id, ad, soyad, sicil_no, avatar FROM personel WHERE aktif = 1 ORDER BY ad, soyad";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Personel listesi hatası: " . $e->getMessage());
        return [];
    }
}

function getIzinTurleri() {
    global $pdo;
    try {
        $sql = "SELECT * FROM izin_turleri WHERE aktif = 1 ORDER BY ad";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("İzin türleri hatası: " . $e->getMessage());
        return [];
    }
}

function getBekleyenIzinler() {
    global $pdo;
    try {
        $sql = "SELECT pi.*, 
                       CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                       p.sicil_no,
                       p.avatar,
                       it.ad as izin_turu_adi,
                       it.renk_kodu,
                       it.ucretli
                FROM personel_izinleri pi
                JOIN personel p ON p.id = pi.personel_id
                JOIN izin_turleri it ON it.id = pi.izin_turu_id
                WHERE pi.durum = 'beklemede' AND pi.aktif = 1
                ORDER BY pi.olusturma_tarihi DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Bekleyen izinler hatası: " . $e->getMessage());
        return [];
    }
}

function getOnaylananIzinler() {
    global $pdo;
    try {
        $sql = "SELECT pi.*, 
                       CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                       p.sicil_no,
                       p.avatar,
                       it.ad as izin_turu_adi,
                       it.renk_kodu,
                       it.ucretli
                FROM personel_izinleri pi
                JOIN personel p ON p.id = pi.personel_id
                JOIN izin_turleri it ON it.id = pi.izin_turu_id
                WHERE pi.durum = 'onaylandi' 
                AND pi.aktif = 1
                AND MONTH(pi.baslangic_tarihi) = MONTH(CURDATE())
                AND YEAR(pi.baslangic_tarihi) = YEAR(CURDATE())
                ORDER BY pi.baslangic_tarihi DESC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Onaylanan izinler hatası: " . $e->getMessage());
        return [];
    }
}

function getBugunkuIzinliSayisi() {
    global $pdo;
    try {
        $sql = "SELECT COUNT(DISTINCT personel_id) 
                FROM personel_izinleri 
                WHERE durum = 'onaylandi' 
                AND CURDATE() BETWEEN baslangic_tarihi AND bitis_tarihi
                AND aktif = 1";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchColumn() ?: 0;
    } catch(PDOException $e) {
        error_log("Bugünkü izinli sayısı hatası: " . $e->getMessage());
        return 0;
    }
}

// Bordro Yönetimi Fonksiyonları
function getPersonellerWithMaas() {
    global $pdo;
    try {
        $sql = "SELECT p.*, pmb.brut_maas, pmb.banka_adi, pmb.iban
                FROM personel p
                LEFT JOIN personel_maas_bilgileri pmb ON pmb.personel_id = p.id AND pmb.aktif = 1
                WHERE p.aktif = 1
                ORDER BY p.ad, p.soyad";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Maaşlı personel listesi hatası: " . $e->getMessage());
        return [];
    }
}

function getBordrolar($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT b.*, 
                       CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                       p.sicil_no
                FROM bordrolar b
                JOIN personel p ON p.id = b.personel_id
                WHERE b.ay = ? AND b.yil = ? AND b.aktif = 1
                ORDER BY p.ad, p.soyad";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Bordro listesi hatası: " . $e->getMessage());
        return [];
    }
}

function getBordroIstatistik($ay, $yil) {
    global $pdo;
    try {
        // Toplam personel sayısı
        $toplam_personel_sql = "SELECT COUNT(*) FROM personel WHERE aktif = 1";
        $stmt = $pdo->query($toplam_personel_sql);
        $toplam_personel = $stmt->fetchColumn();
        
        // Bordro durumları
        $sql = "SELECT 
                    durum,
                    COUNT(*) as adet,
                    SUM(net_odeme) as toplam_net
                FROM bordrolar 
                WHERE ay = ? AND yil = ? AND aktif = 1
                GROUP BY durum";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        $durumlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $istatistik = [
            'toplam_personel' => $toplam_personel,
            'taslak_bordro' => 0,
            'onaylanan_bordro' => 0,
            'odenen_bordro' => 0,
            'toplam_net' => 0
        ];
        
        foreach ($durumlar as $durum) {
            if ($durum['durum'] == 'taslak') {
                $istatistik['taslak_bordro'] = $durum['adet'];
            } elseif ($durum['durum'] == 'onaylandi') {
                $istatistik['onaylanan_bordro'] = $durum['adet'];
            } elseif ($durum['durum'] == 'odendi') {
                $istatistik['odenen_bordro'] = $durum['adet'];
            }
            $istatistik['toplam_net'] += $durum['toplam_net'];
        }
        
        return $istatistik;
    } catch(PDOException $e) {
        error_log("Bordro istatistik hatası: " . $e->getMessage());
        return [
            'toplam_personel' => 0,
            'taslak_bordro' => 0,
            'onaylanan_bordro' => 0,
            'odenen_bordro' => 0,
            'toplam_net' => 0
        ];
    }
}

function createBordro($personel_id, $ay, $yil) {
    global $pdo;
    try {
        // Personel maaş bilgilerini al
        $maas_sql = "SELECT * FROM personel_maas_bilgileri WHERE personel_id = ? AND aktif = 1";
        $maas_stmt = $pdo->prepare($maas_sql);
        $maas_stmt->execute([$personel_id]);
        $maas_bilgi = $maas_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$maas_bilgi) {
            throw new Exception("Personel maaş bilgisi bulunamadı");
        }
        
        // İzin günlerini hesapla
        $izin_sql = "SELECT 
                        SUM(CASE WHEN it.ucretli = 1 THEN pi.gun_sayisi ELSE 0 END) as ucretli_izin,
                        SUM(CASE WHEN it.ucretli = 0 THEN pi.gun_sayisi ELSE 0 END) as ucretsiz_izin
                     FROM personel_izinleri pi
                     JOIN izin_turleri it ON it.id = pi.izin_turu_id
                     WHERE pi.personel_id = ? 
                     AND pi.durum = 'onaylandi'
                     AND MONTH(pi.baslangic_tarihi) = ? 
                     AND YEAR(pi.baslangic_tarihi) = ?";
        
        $izin_stmt = $pdo->prepare($izin_sql);
        $izin_stmt->execute([$personel_id, $ay, $yil]);
        $izin_bilgi = $izin_stmt->fetch(PDO::FETCH_ASSOC);
        
        $ucretli_izin = $izin_bilgi['ucretli_izin'] ?: 0;
        $ucretsiz_izin = $izin_bilgi['ucretsiz_izin'] ?: 0;
        $calisan_gun = 30 - $ucretsiz_izin; // Ücretsiz izin günlerini çalışma gününden çıkar
        
        // Bordro hesaplamaları
        $brut_maas = $maas_bilgi['brut_maas'];
        $prim_tutari = $maas_bilgi['sabit_prim'];
        $yemek_yardimi = $maas_bilgi['yemek_yardimi'];
        $brut_toplam = $brut_maas + $prim_tutari + $yemek_yardimi;
        
        // Kesintiler (sistem ayarlarından al)
        $sgk_oran = getSistemAyari('bordro', 'sgk_isci_payi', 14.0);
        $issizlik_oran = getSistemAyari('bordro', 'issizlik_isci_payi', 1.0);
        $damga_oran = getSistemAyari('bordro', 'damga_vergisi', 0.759);
        
        $sgk_kesinti = ($brut_toplam * $sgk_oran) / 100;
        $issizlik_kesinti = ($brut_toplam * $issizlik_oran) / 100;
        $damga_vergisi = ($brut_toplam * $damga_oran) / 100;
        
        // Gelir vergisi hesaplama (basitleştirilmiş)
        $vergi_matrah = $brut_toplam - $sgk_kesinti - $issizlik_kesinti;
        $gelir_vergisi = calculateGelirVergisi($vergi_matrah);
        
        $toplam_kesinti = $sgk_kesinti + $issizlik_kesinti + $gelir_vergisi + $damga_vergisi;
        $net_odeme = $brut_toplam - $toplam_kesinti;
        
        // Bordroyu kaydet
        $bordro_id = generateUUID();
        $bordro_sql = "INSERT INTO bordrolar 
                       (id, personel_id, ay, yil, brut_maas, prim_tutari, yemek_yardimi, 
                        brut_toplam, sgk_isci_payi, issizlik_isci_payi, gelir_vergisi, 
                        damga_vergisi, toplam_kesinti, net_odeme, calisan_gun_sayisi, 
                        ucretli_izin_gun, ucretsiz_izin_gun, olusturan_id)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($bordro_sql);
        $success = $stmt->execute([
            $bordro_id, $personel_id, $ay, $yil, $brut_maas, $prim_tutari, $yemek_yardimi,
            $brut_toplam, $sgk_kesinti, $issizlik_kesinti, $gelir_vergisi,
            $damga_vergisi, $toplam_kesinti, $net_odeme, $calisan_gun,
            $ucretli_izin, $ucretsiz_izin, $_SESSION['user_id']
        ]);
        
        return $success ? $bordro_id : false;
        
    } catch(Exception $e) {
        error_log("Bordro oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

function createAllBordrolar($ay, $yil) {
    $personeller = getPersonellerWithMaas();
    $success_count = 0;
    
    foreach ($personeller as $personel) {
        if ($personel['brut_maas'] > 0) {
            // Mevcut bordro var mı kontrol et
            if (!bordroExists($personel['id'], $ay, $yil)) {
                if (createBordro($personel['id'], $ay, $yil)) {
                    $success_count++;
                }
            }
        }
    }
    
    return $success_count;
}

function bordroExists($personel_id, $ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT COUNT(*) FROM bordrolar WHERE personel_id = ? AND ay = ? AND yil = ? AND aktif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personel_id, $ay, $yil]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

function approveBordro($bordro_id) {
    global $pdo;
    try {
        $sql = "UPDATE bordrolar 
                SET durum = 'onaylandi', 
                    onaylayan_id = ?, 
                    onay_tarihi = NOW() 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$_SESSION['user_id'], $bordro_id]);
    } catch(PDOException $e) {
        error_log("Bordro onaylama hatası: " . $e->getMessage());
        return false;
    }
}

function calculateGelirVergisi($matrah) {
    // Basitleştirilmiş gelir vergisi hesabı
    $gvk_1_dilim = getSistemAyari('bordro', 'gvk_1_dilim', 110000);
    $gvk_1_oran = getSistemAyari('bordro', 'gvk_1_oran', 15);
    $gvk_2_oran = getSistemAyari('bordro', 'gvk_2_oran', 20);
    
    if ($matrah <= $gvk_1_dilim) {
        return ($matrah * $gvk_1_oran) / 100;
    } else {
        $birinci_dilim_vergi = ($gvk_1_dilim * $gvk_1_oran) / 100;
        $ikinci_dilim_vergi = (($matrah - $gvk_1_dilim) * $gvk_2_oran) / 100;
        return $birinci_dilim_vergi + $ikinci_dilim_vergi;
    }
}

function getSistemAyari($kategori, $anahtar, $varsayilan = null) {
    global $pdo;
    try {
        $sql = "SELECT deger FROM sistem_ayarlari WHERE kategori = ? AND anahtar = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kategori, $anahtar]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $varsayilan;
    } catch(PDOException $e) {
        return $varsayilan;
    }
}

function getAylikIzinOzeti($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    SUM(CASE WHEN it.ucretli = 1 THEN pi.gun_sayisi ELSE 0 END) as ucretli,
                    SUM(CASE WHEN it.ucretli = 0 THEN pi.gun_sayisi ELSE 0 END) as ucretsiz,
                    SUM(pi.gun_sayisi) as toplam_gun
                FROM personel_izinleri pi
                JOIN izin_turleri it ON it.id = pi.izin_turu_id
                WHERE pi.durum = 'onaylandi'
                AND MONTH(pi.baslangic_tarihi) = ?
                AND YEAR(pi.baslangic_tarihi) = ?
                AND pi.aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'ucretli' => $result['ucretli'] ?: 0,
            'ucretsiz' => $result['ucretsiz'] ?: 0,
            'toplam_gun' => $result['toplam_gun'] ?: 0
        ];
    } catch(PDOException $e) {
        error_log("Aylık izin özeti hatası: " . $e->getMessage());
        return ['ucretli' => 0, 'ucretsiz' => 0, 'toplam_gun' => 0];
    }
}

// Personel maaş bilgisi ekleme/güncelleme
function updatePersonelMaasBilgisi($personel_id, $brut_maas, $prim_yuzdesi = 0, $sabit_prim = 0, 
                                   $yemek_yardimi = 0, $banka_adi = '', $iban = '') {
    global $pdo;
    try {
        // Mevcut aktif kaydı pasif yap
        $deactivate_sql = "UPDATE personel_maas_bilgileri SET aktif = 0 WHERE personel_id = ? AND aktif = 1";
        $pdo->prepare($deactivate_sql)->execute([$personel_id]);
        
        // Yeni kayıt ekle
        $sql = "INSERT INTO personel_maas_bilgileri 
                (personel_id, brut_maas, prim_yuzdesi, sabit_prim, yemek_yardimi, 
                 banka_adi, iban, baslangic_tarihi) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $personel_id, $brut_maas, $prim_yuzdesi, $sabit_prim, 
            $yemek_yardimi, $banka_adi, $iban
        ]);
    } catch(PDOException $e) {
        error_log("Maaş bilgisi güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

// İzin bakiye güncelleme
function updateIzinBakiye($personel_id, $yil, $izin_turu_id, $kullanilan_gun) {
    global $pdo;
    try {
        $sql = "INSERT INTO personel_izin_bakiye (personel_id, yil, izin_turu_id, kullanilan, kalan)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                kullanilan = kullanilan + VALUES(kullanilan),
                kalan = yillik_hak - kullanilan";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$personel_id, $yil, $izin_turu_id, $kullanilan_gun, 0]);
    } catch(PDOException $e) {
        error_log("İzin bakiye güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

// Personel çalışma saatleri kaydetme
function kaydetGirisCikis($personel_id, $tarih, $giris_saati = null, $cikis_saati = null, $aciklama = '') {
    global $pdo;
    try {
        $id = generateUUID();
        $toplam_saat = 0;
        $durum = 'normal';
        
        if ($giris_saati && $cikis_saati) {
            $giris = new DateTime($giris_saati);
            $cikis = new DateTime($cikis_saati);
            $fark = $cikis->diff($giris);
            $toplam_saat = $fark->h + ($fark->i / 60);
            
            // Geç gelme kontrolü (09:00'dan sonra)
            if ($giris->format('H:i') > '09:00') {
                $durum = 'gecikme';
            }
            
            // Erken çıkış kontrolü (18:00'dan önce)
            if ($cikis->format('H:i') < '18:00') {
                $durum = 'erken_cikis';
            }
        }
        
        $sql = "INSERT INTO personel_giris_cikis 
                (id, personel_id, tarih, giris_saati, cikis_saati, toplam_calisma_saati, 
                 durum, aciklama, kayit_tipi, kaydeden_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'manuel', ?)
                ON DUPLICATE KEY UPDATE
                giris_saati = VALUES(giris_saati),
                cikis_saati = VALUES(cikis_saati),
                toplam_calisma_saati = VALUES(toplam_calisma_saati),
                durum = VALUES(durum),
                aciklama = VALUES(aciklama),
                guncelleme_tarihi = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $id, $personel_id, $tarih, $giris_saati, $cikis_saati, 
            $toplam_saat, $durum, $aciklama, $_SESSION['user_id']
        ]);
    } catch(PDOException $e) {
        error_log("Giriş/çıkış kaydetme hatası: " . $e->getMessage());
        return false;
    }
}

// Personel performans raporu
function getPersonelPerformansRaporu($personel_id, $baslangic_tarih, $bitis_tarih) {
    global $pdo;
    try {
        $sql = "SELECT 
                    COUNT(*) as toplam_gun,
                    SUM(toplam_calisma_saati) as toplam_saat,
                    AVG(toplam_calisma_saati) as ortalama_saat,
                    SUM(CASE WHEN durum = 'gecikme' THEN 1 ELSE 0 END) as gecikme_sayisi,
                    SUM(CASE WHEN durum = 'erken_cikis' THEN 1 ELSE 0 END) as erken_cikis_sayisi,
                    SUM(gecikme_dakika) as toplam_gecikme_dakika
                FROM personel_giris_cikis 
                WHERE personel_id = ? 
                AND tarih BETWEEN ? AND ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personel_id, $baslangic_tarih, $bitis_tarih]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Performans raporu hatası: " . $e->getMessage());
        return [];
    }
}

// Departman bazlı maaş özeti
function getDepartmanMaasOzeti($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    p.rol as departman,
                    COUNT(*) as personel_sayisi,
                    SUM(b.brut_toplam) as toplam_brut,
                    SUM(b.toplam_kesinti) as toplam_kesinti,
                    SUM(b.net_odeme) as toplam_net,
                    AVG(b.net_odeme) as ortalama_net
                FROM bordrolar b
                JOIN personel p ON p.id = b.personel_id
                WHERE b.ay = ? AND b.yil = ? AND b.aktif = 1
                GROUP BY p.rol
                ORDER BY toplam_net DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Departman maaş özeti hatası: " . $e->getMessage());
        return [];
    }
}

// Bordro email gönderme
function sendBordroEmail($bordro_id) {
    global $pdo;
    try {
        // Bordro bilgilerini al
        $sql = "SELECT b.*, 
                       CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                       p.email
                FROM bordrolar b
                JOIN personel p ON p.id = b.personel_id
                WHERE b.id = ? AND b.durum = 'onaylandi'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bordro_id]);
        $bordro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bordro || !$bordro['email']) {
            return false;
        }
        
        // Email içeriği hazırla
        $aylar = [
            '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
            '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
            '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
        ];
        
        $ay_adi = $aylar[sprintf('%02d', $bordro['ay'])];
        
        $subject = "TheraVita - {$ay_adi} {$bordro['yil']} Bordronuz";
        
        $message = "
        <h2>Sayın {$bordro['personel_adi']},</h2>
        <p>{$ay_adi} {$bordro['yil']} ayına ait bordronuz hazır.</p>
        
        <table border='1' style='border-collapse: collapse; width: 100%;'>
            <tr><td><strong>Brüt Maaş:</strong></td><td>" . number_format($bordro['brut_maas'], 2) . " ₺</td></tr>
            <tr><td><strong>Primler:</strong></td><td>" . number_format($bordro['prim_tutari'], 2) . " ₺</td></tr>
            <tr><td><strong>Yemek Yardımı:</strong></td><td>" . number_format($bordro['yemek_yardimi'], 2) . " ₺</td></tr>
            <tr><td><strong>Brüt Toplam:</strong></td><td>" . number_format($bordro['brut_toplam'], 2) . " ₺</td></tr>
            <tr><td><strong>Kesintiler:</strong></td><td>" . number_format($bordro['toplam_kesinti'], 2) . " ₺</td></tr>
            <tr style='background-color: #f0f0f0;'><td><strong>Net Ödeme:</strong></td><td><strong>" . number_format($bordro['net_odeme'], 2) . " ₺</strong></td></tr>
        </table>
        
        <p><small>Detaylı bordronuz için insan kaynakları departmanı ile iletişime geçebilirsiniz.</small></p>
        ";
        
        // Email gönderme (PHPMailer veya mail() fonksiyonu kullanılabilir)
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@theravita.com" . "\r\n";
        
        return mail($bordro['email'], $subject, $message, $headers);
        
    } catch(Exception $e) {
        error_log("Bordro email gönderme hatası: " . $e->getMessage());
        return false;
    }
}

// Yıllık izin hakkı hesaplama
function calculateYillikIzinHakki($personel_id, $yil) {
    global $pdo;
    try {
        // Personelin işe başlama tarihini al
        $sql = "SELECT olusturma_tarihi FROM personel WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personel_id]);
        $baslama_tarihi = $stmt->fetchColumn();
        
        if (!$baslama_tarihi) {
            return 14; // Varsayılan
        }
        
        // Çalışma yılını hesapla
        $baslama = new DateTime($baslama_tarihi);
        $yil_sonu = new DateTime("{$yil}-12-31");
        $calisma_yili = $baslama->diff($yil_sonu)->y;
        
        // İzin hakkı hesaplama (Türk İş Kanunu'na göre)
        if ($calisma_yili < 1) {
            return 0;
        } elseif ($calisma_yili < 5) {
            return 14; // 14 gün
        } elseif ($calisma_yili < 15) {
            return 20; // 20 gün
        } else {
            return 26; // 26 gün
        }
    } catch(Exception $e) {
        error_log("Yıllık izin hakkı hesaplama hatası: " . $e->getMessage());
        return 14;
    }
}

// İzin bakiyelerini güncelle (yıl başında çalıştırılacak)
function updateAllIzinBakiyeleri($yil) {
    global $pdo;
    try {
        $personeller = getPersoneller();
        $success_count = 0;
        
        foreach ($personeller as $personel) {
            $yillik_hak = calculateYillikIzinHakki($personel['id'], $yil);
            
            // Yıllık izin bakiyesini güncelle
            $sql = "INSERT INTO personel_izin_bakiye (personel_id, yil, izin_turu_id, yillik_hak, kalan)
                    VALUES (?, ?, 1, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    yillik_hak = VALUES(yillik_hak),
                    kalan = VALUES(yillik_hak) - kullanilan";
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$personel['id'], $yil, $yillik_hak, $yillik_hak])) {
                $success_count++;
            }
        }
        
        return $success_count;
    } catch(Exception $e) {
        error_log("Toplu izin bakiye güncelleme hatası: " . $e->getMessage());
        return 0;
    }
}

// Bordro Excel export için veri hazırlama
function prepareBordroExcelData($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    CONCAT(p.ad, ' ', p.soyad) as ad_soyad,
                    p.sicil_no,
                    b.brut_maas,
                    b.prim_tutari,
                    b.yemek_yardimi,
                    b.brut_toplam,
                    b.sgk_isci_payi,
                    b.issizlik_isci_payi,
                    b.gelir_vergisi,
                    b.damga_vergisi,
                    b.toplam_kesinti,
                    b.net_odeme,
                    b.calisan_gun_sayisi,
                    b.ucretli_izin_gun,
                    b.ucretsiz_izin_gun,
                    pmb.banka_adi,
                    pmb.iban
                FROM bordrolar b
                JOIN personel p ON p.id = b.personel_id
                LEFT JOIN personel_maas_bilgileri pmb ON pmb.personel_id = b.personel_id AND pmb.aktif = 1
                WHERE b.ay = ? AND b.yil = ? AND b.aktif = 1
                ORDER BY p.ad, p.soyad";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Excel veri hazırlama hatası: " . $e->getMessage());
        return [];
    }
}


function getPersonelIzinDurumlari($yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    p.id as personel_id,
                    p.ad,
                    p.soyad,
                    CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                    p.sicil_no,
                    p.avatar,
                    p.rol as departman,
                    p.olusturma_tarihi as ise_baslama,
                    TIMESTAMPDIFF(YEAR, p.olusturma_tarihi, CURDATE()) as calisma_yili,
                    COALESCE(pib.yillik_hak, 0) as yillik_hak,
                    COALESCE(pib.kullanilan, 0) as kullanilan,
                    COALESCE(pib.kalan, 0) as kalan,
                    COALESCE(pib.devredilen, 0) as devredilen
                FROM personel p
                LEFT JOIN personel_izin_bakiye pib ON pib.personel_id = p.id 
                    AND pib.yil = ? AND pib.izin_turu_id = 1
                WHERE p.aktif = 1
                ORDER BY p.ad, p.soyad";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$yil]);
        $sonuclar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // İzin hakkı hesaplanmamış olanları hesapla
        foreach ($sonuclar as &$sonuc) {
            if ($sonuc['yillik_hak'] == 0) {
                $sonuc['yillik_hak'] = calculateYillikIzinHakki($sonuc['personel_id'], $yil);
                $sonuc['kalan'] = $sonuc['yillik_hak'] - $sonuc['kullanilan'];
            }
        }
        
        return $sonuclar;
    } catch(PDOException $e) {
        error_log("İzin durumları getirme hatası: " . $e->getMessage());
        return [];
    }
}

function getIzinIstatistikleri($yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as toplam_personel,
                    COALESCE(SUM(pib.yillik_hak), 0) as toplam_hak,
                    COALESCE(SUM(pib.kullanilan), 0) as kullanilan,
                    COALESCE(SUM(pib.kalan), 0) as kalan,
                    COALESCE(AVG(pib.kullanilan * 100.0 / NULLIF(pib.yillik_hak, 0)), 0) as ortalama_kullanim,
                    COUNT(CASE WHEN pib.kalan < (pib.yillik_hak * 0.2) THEN 1 END) as kritik_personel
                FROM personel p
                LEFT JOIN personel_izin_bakiye pib ON pib.personel_id = p.id 
                    AND pib.yil = ? AND pib.izin_turu_id = 1
                WHERE p.aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$yil]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("İzin istatistikleri hatası: " . $e->getMessage());
        return [
            'toplam_personel' => 0,
            'toplam_hak' => 0,
            'kullanilan' => 0,
            'kalan' => 0,
            'ortalama_kullanim' => 0,
            'kritik_personel' => 0
        ];
    }
}

function updateAllIzinHakki($yil) {
    global $pdo;
    try {
        $personeller = getPersoneller();
        $guncellenen = 0;
        
        foreach ($personeller as $personel) {
            $yillik_hak = calculateYillikIzinHakki($personel['id'], $yil);
            
            // Mevcut kullanılan izni al
            $kullanilan_sql = "SELECT COALESCE(SUM(gun_sayisi), 0) 
                              FROM personel_izinleri pi
                              JOIN izin_turleri it ON it.id = pi.izin_turu_id
                              WHERE pi.personel_id = ? 
                              AND YEAR(pi.baslangic_tarihi) = ?
                              AND pi.durum = 'onaylandi'
                              AND it.id = 1"; // Yıllık izin
            
            $stmt = $pdo->prepare($kullanilan_sql);
            $stmt->execute([$personel['id'], $yil]);
            $kullanilan = $stmt->fetchColumn() ?: 0;
            
            // Bakiyeyi güncelle
            $sql = "INSERT INTO personel_izin_bakiye 
                    (personel_id, yil, izin_turu_id, yillik_hak, kullanilan, kalan)
                    VALUES (?, ?, 1, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    yillik_hak = VALUES(yillik_hak),
                    kullanilan = VALUES(kullanilan),
                    kalan = VALUES(yillik_hak) - VALUES(kullanilan)";
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$personel['id'], $yil, $yillik_hak, $kullanilan, $yillik_hak - $kullanilan])) {
                $guncellenen++;
            }
        }
        
        return $guncellenen;
    } catch(Exception $e) {
        error_log("Toplu izin hakkı güncelleme hatası: " . $e->getMessage());
        return 0;
    }
}

function manuelIzinHakGuncelle($personel_id, $yil, $yeni_hak) {
    global $pdo;
    try {
        // Mevcut kullanılan izni al
        $kullanilan_sql = "SELECT COALESCE(kullanilan, 0) 
                          FROM personel_izin_bakiye 
                          WHERE personel_id = ? AND yil = ? AND izin_turu_id = 1";
        
        $stmt = $pdo->prepare($kullanilan_sql);
        $stmt->execute([$personel_id, $yil]);
        $kullanilan = $stmt->fetchColumn() ?: 0;
        
        // Manuel güncelleme kaydet
        $sql = "INSERT INTO personel_izin_bakiye 
                (personel_id, yil, izin_turu_id, yillik_hak, kullanilan, kalan)
                VALUES (?, ?, 1, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                yillik_hak = VALUES(yillik_hak),
                kalan = VALUES(yillik_hak) - kullanilan";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$personel_id, $yil, $yeni_hak, $kullanilan, $yeni_hak - $kullanilan]);
    } catch(PDOException $e) {
        error_log("Manuel izin hakkı güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

// === ÇALIŞMA SAATLERİ TAKİP FONKSİYONLARI ===

function getGunlukCalismaKayitlari($tarih, $personel_id = null) {
    global $pdo;
    try {
        $where_clause = "WHERE pgc.tarih = ?";
        $params = [$tarih];
        
        if ($personel_id) {
            $where_clause .= " AND pgc.personel_id = ?";
            $params[] = $personel_id;
        }
        
        $sql = "SELECT pgc.*, 
                       CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                       p.sicil_no,
                       p.avatar
                FROM personel_giris_cikis pgc
                JOIN personel p ON p.id = pgc.personel_id
                {$where_clause}
                ORDER BY pgc.giris_saati ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Günlük çalışma kayıtları hatası: " . $e->getMessage());
        return [];
    }
}

function getAylikCalismaOzeti($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    p.id as personel_id,
                    CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                    COUNT(pgc.id) as calisilan_gun,
                    AVG(pgc.toplam_calisma_saati) as ortalama_saat,
                    SUM(pgc.mesai_saati) as toplam_mesai,
                    SUM(pgc.gecikme_dakika) as toplam_gecikme,
                    COUNT(CASE WHEN pgc.durum = 'gecikme' THEN 1 END) as gecikme_sayisi,
                    COUNT(CASE WHEN pgc.durum = 'devamsizlik' THEN 1 END) as devamsizlik_sayisi
                FROM personel p
                LEFT JOIN personel_giris_cikis pgc ON pgc.personel_id = p.id
                    AND MONTH(pgc.tarih) = ? AND YEAR(pgc.tarih) = ?
                WHERE p.aktif = 1
                GROUP BY p.id
                ORDER BY p.ad, p.soyad";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Aylık çalışma özeti hatası: " . $e->getMessage());
        return [];
    }
}

function getCalismaIstatistikleri($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    AVG(toplam_calisma_saati) as ortalama_saat,
                    COUNT(CASE WHEN durum = 'normal' THEN 1 END) as zamaninda_gelen,
                    COUNT(CASE WHEN durum = 'gecikme' THEN 1 END) as geciken,
                    COUNT(CASE WHEN durum = 'devamsizlik' THEN 1 END) as devamsiz,
                    MAX(gecikme_dakika) as max_gecikme,
                    SUM(mesai_saati) as toplam_mesai
                FROM personel_giris_cikis 
                WHERE MONTH(tarih) = ? AND YEAR(tarih) = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Çalışma istatistikleri hatası: " . $e->getMessage());
        return [
            'ortalama_saat' => 0,
            'zamaninda_gelen' => 0,
            'geciken' => 0,
            'devamsiz' => 0,
            'max_gecikme' => 0,
            'toplam_mesai' => 0
        ];
    }
}

// === PERFORMANS RAPORU FONKSİYONLARI ===

function getPerformansRaporlari($baslangic_tarih, $bitis_tarih, $personel_id = null, $departman = null) {
    global $pdo;
    try {
        $where_clause = "WHERE p.aktif = 1";
        $params = [$baslangic_tarih, $bitis_tarih];
        
        if ($personel_id) {
            $where_clause .= " AND p.id = ?";
            $params[] = $personel_id;
        }
        
        if ($departman) {
            $where_clause .= " AND p.rol = ?";
            $params[] = $departman;
        }
        
        $sql = "SELECT 
                    p.id as personel_id,
                    CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                    p.sicil_no,
                    p.rol as departman,
                    p.avatar,
                    
                    -- Devam durumu
                    COUNT(DISTINCT pgc.tarih) as calisilan_gun,
                    (COUNT(DISTINCT pgc.tarih) * 100.0 / 
                     DATEDIFF(?, ?)) as devam_orani,
                    
                    -- Çalışma saatleri
                    AVG(pgc.toplam_calisma_saati) as ortalama_calisma_saati,
                    SUM(pgc.mesai_saati) as mesai_saati,
                    
                    -- Değerlendirme skoru
                    (SELECT pd.c_skoru FROM personel_degerlendirme pd 
                     WHERE pd.personel_id = p.id 
                     ORDER BY pd.olusturma_tarihi DESC LIMIT 1) as son_degerlendirme_skoru,
                    (SELECT pd.olusturma_tarihi FROM personel_degerlendirme pd 
                     WHERE pd.personel_id = p.id 
                     ORDER BY pd.olusturma_tarihi DESC LIMIT 1) as son_degerlendirme_tarihi,
                    
                    -- Departmana özel metrikler
                    CASE 
                        WHEN p.rol = 'terapist' THEN 
                            (SELECT COUNT(DISTINCT r.danisan_id) 
                             FROM randevular r 
                             WHERE r.personel_id = p.id 
                             AND r.randevu_tarihi BETWEEN ? AND ?)
                        WHEN p.rol = 'satis' THEN 
                            (SELECT COUNT(*) 
                             FROM satislar s 
                             WHERE s.personel_id = p.id 
                             AND s.olusturma_tarihi BETWEEN ? AND ?)
                        ELSE 0
                    END as departman_metrik,
                    
                    -- Müşteri memnuniyeti (terapistler için)
                    CASE 
                        WHEN p.rol = 'terapist' THEN 
                            (SELECT AVG(hd.memnuniyet_skoru) 
                             FROM hasta_degerlendirmeleri hd
                             JOIN randevular r ON r.id = hd.randevu_id
                             WHERE r.personel_id = p.id 
                             AND hd.olusturma_tarihi BETWEEN ? AND ?)
                        ELSE NULL
                    END as musteri_memnuniyet
                    
                FROM personel p
                LEFT JOIN personel_giris_cikis pgc ON pgc.personel_id = p.id
                    AND pgc.tarih BETWEEN ? AND ?
                {$where_clause}
                GROUP BY p.id
                ORDER BY p.ad, p.soyad";
        
        // Parametreleri ekle
        $params = array_merge($params, [$baslangic_tarih, $bitis_tarih]); // departman metrik için
        $params = array_merge($params, [$baslangic_tarih, $bitis_tarih]); // satış metrik için
        $params = array_merge($params, [$baslangic_tarih, $bitis_tarih]); // müşteri memnuniyeti için
        $params = array_merge($params, [$baslangic_tarih, $bitis_tarih]); // çalışma saatleri için
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $personel_performanslari = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Genel performans skorlarını hesapla
        foreach ($personel_performanslari as &$personel) {
            // Departmana özel değişkenler
            if ($personel['departman'] == 'terapist') {
                $personel['hasta_sayisi'] = $personel['departman_metrik'];
                $personel['satis_sayisi'] = 0;
            } else {
                $personel['hasta_sayisi'] = 0;
                $personel['satis_sayisi'] = $personel['departman_metrik'];
            }
            
            // Verimlilik skoru hesaplama
            $verimlilik_skoru = 0;
            if ($personel['departman'] == 'terapist') {
                // Terapist için: hasta sayısı + memnuniyet
                $verimlilik_skoru = ($personel['hasta_sayisi'] * 2) + ($personel['musteri_memnuniyet'] * 20);
            } elseif ($personel['departman'] == 'satis') {
                // Satış için: satış sayısı
                $verimlilik_skoru = $personel['satis_sayisi'] * 5;
            }
            $personel['verimlilik_skoru'] = min(100, $verimlilik_skoru);
            
            // Genel skor hesaplama (ağırlıklı ortalama)
            $devam_agirlik = 0.3;
            $calisma_agirlik = 0.2;
            $verimlilik_agirlik = 0.3;
            $degerlendirme_agirlik = 0.2;
            
            $genel_skor = ($personel['devam_orani'] * $devam_agirlik) +
                         (min(100, $personel['ortalama_calisma_saati'] * 12.5) * $calisma_agirlik) +
                         ($personel['verimlilik_skoru'] * $verimlilik_agirlik) +
                         (($personel['son_degerlendirme_skoru'] * 10) * $degerlendirme_agirlik);
            
            $personel['genel_skor'] = $genel_skor;
        }
        
        // Genel istatistikleri hesapla
        $toplam_personel = count($personel_performanslari);
        $genel_skor = $toplam_personel > 0 ? array_sum(array_column($personel_performanslari, 'genel_skor')) / $toplam_personel : 0;
        $devam_orani = $toplam_personel > 0 ? array_sum(array_column($personel_performanslari, 'devam_orani')) / $toplam_personel : 0;
        $verimlilik_skoru = $toplam_personel > 0 ? array_sum(array_column($personel_performanslari, 'verimlilik_skoru')) / $toplam_personel : 0;
        $musteri_memnuniyet = 0;
        $memnuniyet_sayisi = 0;
        
        foreach ($personel_performanslari as $p) {
            if ($p['musteri_memnuniyet'] !== null) {
                $musteri_memnuniyet += $p['musteri_memnuniyet'];
                $memnuniyet_sayisi++;
            }
        }
        $musteri_memnuniyet = $memnuniyet_sayisi > 0 ? $musteri_memnuniyet / $memnuniyet_sayisi : 0;
        
        return [
            'genel_skor' => $genel_skor,
            'devam_orani' => $devam_orani,
            'verimlilik_skoru' => $verimlilik_skoru,
            'musteri_memnuniyet' => $musteri_memnuniyet,
            'detay' => $personel_performanslari
        ];
        
    } catch(PDOException $e) {
        error_log("Performans raporları hatası: " . $e->getMessage());
        return [
            'genel_skor' => 0,
            'devam_orani' => 0,
            'verimlilik_skoru' => 0,
            'musteri_memnuniyet' => 0,
            'detay' => []
        ];
    }
}

function getDepartmanKarsilastirma($baslangic_tarih, $bitis_tarih) {
    global $pdo;
    try {
        $sql = "SELECT 
                    p.rol as departman,
                    COUNT(*) as personel_sayisi,
                    AVG(CASE 
                        WHEN pgc.personel_id IS NOT NULL THEN 
                            (COUNT(DISTINCT pgc.tarih) * 100.0 / DATEDIFF(?, ?))
                        ELSE 0
                    END) as ortalama_devam,
                    AVG(COALESCE(pgc_avg.ortalama_saat, 0)) as ortalama_calisma_saati
                FROM personel p
                LEFT JOIN personel_giris_cikis pgc ON pgc.personel_id = p.id
                    AND pgc.tarih BETWEEN ? AND ?
                LEFT JOIN (
                    SELECT personel_id, AVG(toplam_calisma_saati) as ortalama_saat
                    FROM personel_giris_cikis
                    WHERE tarih BETWEEN ? AND ?
                    GROUP BY personel_id
                ) pgc_avg ON pgc_avg.personel_id = p.id
                WHERE p.aktif = 1
                GROUP BY p.rol
                ORDER BY p.rol";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bitis_tarih, $baslangic_tarih, $baslangic_tarih, $bitis_tarih, $baslangic_tarih, $bitis_tarih]);
        $departmanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ortalama skor hesapla
        foreach ($departmanlar as &$dept) {
            $devam_skor = $dept['ortalama_devam'];
            $calisma_skor = min(100, $dept['ortalama_calisma_saati'] * 12.5);
            $dept['ortalama_skor'] = ($devam_skor * 0.6) + ($calisma_skor * 0.4);
        }
        
        return $departmanlar;
    } catch(PDOException $e) {
        error_log("Departman karşılaştırma hatası: " . $e->getMessage());
        return [];
    }
}

function getTrendAnalizi($baslangic_tarih, $bitis_tarih) {
    global $pdo;
    try {
        // Son 30 günlük trend
        $sql = "SELECT 
                    DATE(pgc.tarih) as tarih,
                    AVG(CASE WHEN pgc.durum = 'normal' THEN 100 ELSE 
                        CASE WHEN pgc.durum = 'gecikme' THEN 80 ELSE 60 END
                    END) as performans_skoru,
                    (COUNT(CASE WHEN pgc.durum IN ('normal', 'gecikme') THEN 1 END) * 100.0 / 
                     COUNT(*)) as devam_orani
                FROM personel_giris_cikis pgc
                WHERE pgc.tarih BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
                GROUP BY DATE(pgc.tarih)
                ORDER BY pgc.tarih";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bitis_tarih, $bitis_tarih]);
        $grafikler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Trend hesaplama
        $performans_degerler = array_column($grafikler, 'performans_skoru');
        $genel_trend = 0;
        
        if (count($performans_degerler) >= 2) {
            $ilk_degerler = array_slice($performans_degerler, 0, 5);
            $son_degerler = array_slice($performans_degerler, -5);
            
            $ilk_ortalama = array_sum($ilk_degerler) / count($ilk_degerler);
            $son_ortalama = array_sum($son_degerler) / count($son_degerler);
            
            $genel_trend = $son_ortalama - $ilk_ortalama;
        }
        
        return [
            'genel_trend' => $genel_trend,
            'grafikler' => $grafikler
        ];
    } catch(PDOException $e) {
        error_log("Trend analizi hatası: " . $e->getMessage());
        return [
            'genel_trend' => 0,
            'grafikler' => []
        ];
    }
}

function getPerformansRankingList($baslangic_tarih, $bitis_tarih) {
    global $pdo;
    try {
        $performans_verileri = getPerformansRaporlari($baslangic_tarih, $bitis_tarih);
        $detay = $performans_verileri['detay'];
        
        // Skora göre sırala
        usort($detay, function($a, $b) {
            return $b['genel_skor'] <=> $a['genel_skor'];
        });
        
        // Trend hesapla (önceki ay ile karşılaştır)
        $onceki_ay_baslangic = date('Y-m-d', strtotime($baslangic_tarih . ' -1 month'));
        $onceki_ay_bitis = date('Y-m-d', strtotime($bitis_tarih . ' -1 month'));
        $onceki_performans = getPerformansRaporlari($onceki_ay_baslangic, $onceki_ay_bitis);
        
        foreach ($detay as &$personel) {
            // Önceki ay skorunu bul
            $onceki_skor = 0;
            foreach ($onceki_performans['detay'] as $onceki) {
                if ($onceki['personel_id'] == $personel['personel_id']) {
                    $onceki_skor = $onceki['genel_skor'];
                    break;
                }
            }
            
            $personel['trend'] = $personel['genel_skor'] - $onceki_skor;
        }
        
        return array_slice($detay, 0, 10); // İlk 10'u döndür
    } catch(Exception $e) {
        error_log("Performans sıralama hatası: " . $e->getMessage());
        return [];
    }
}

// Gelişmiş performans metrikleri
function calculatePerformanceScore($personel_data) {
    $scores = [];
    
    // Devam skoru (0-100)
    $scores['attendance'] = min(100, $personel_data['devam_orani']);
    
    // Çalışma saati skoru (8 saat = 100 puan)
    $scores['working_hours'] = min(100, ($personel_data['ortalama_calisma_saati'] / 8) * 100);
    
    // Kalite skoru (değerlendirme skoruna göre)
    $scores['quality'] = $personel_data['son_degerlendirme_skoru'] * 10;
    
    // Verimlilik skoru
    $scores['productivity'] = $personel_data['verimlilik_skoru'];
    
    // Ağırlıklı genel skor
    $weights = [
        'attendance' => 0.25,
        'working_hours' => 0.20,
        'quality' => 0.30,
        'productivity' => 0.25
    ];
    
    $total_score = 0;
    foreach ($scores as $metric => $score) {
        $total_score += $score * $weights[$metric];
    }
    
    return [
        'total_score' => $total_score,
        'breakdown' => $scores
    ];
}

// Performans uyarı sistemi
function checkPerformanceAlerts($baslangic_tarih, $bitis_tarih) {
    global $pdo;
    
    $alerts = [];
    $performans_verileri = getPerformansRaporlari($baslangic_tarih, $bitis_tarih);
    
    foreach ($performans_verileri['detay'] as $personel) {
        // Düşük performans uyarısı
        if ($personel['genel_skor'] < 60) {
            $alerts[] = [
                'type' => 'low_performance',
                'personel_id' => $personel['personel_id'],
                'personel_adi' => $personel['personel_adi'],
                'skor' => $personel['genel_skor'],
                'mesaj' => 'Düşük performans tespit edildi'
            ];
        }
        
        // Devamsızlık uyarısı
        if ($personel['devam_orani'] < 80) {
            $alerts[] = [
                'type' => 'attendance_issue',
                'personel_id' => $personel['personel_id'],
                'personel_adi' => $personel['personel_adi'],
                'oran' => $personel['devam_orani'],
                'mesaj' => 'Devam problemi tespit edildi'
            ];
        }
        
        // Çalışma saati uyarısı
        if ($personel['ortalama_calisma_saati'] < 6) {
            $alerts[] = [
                'type' => 'working_hours_low',
                'personel_id' => $personel['personel_id'],
                'personel_adi' => $personel['personel_adi'],
                'saat' => $personel['ortalama_calisma_saati'],
                'mesaj' => 'Düşük çalışma saati tespit edildi'
            ];
        }
    }
    
    return $alerts;
}

// Otomatik rapor oluşturma
function generateAutomaticReport($tarih) {
    global $pdo;
    
    $baslangic = date('Y-m-01', strtotime($tarih));
    $bitis = date('Y-m-t', strtotime($tarih));
    
    // Performans verilerini al
    $performans = getPerformansRaporlari($baslangic, $bitis);
    $uyarilar = checkPerformanceAlerts($baslangic, $bitis);
    
    // Rapor içeriği oluştur
    $rapor = [
        'tarih' => $tarih,
        'genel_performans' => $performans['genel_skor'],
        'en_iyi_performans' => array_slice($performans['detay'], 0, 3),
        'iyilestirme_gereken' => array_filter($performans['detay'], function($p) {
            return $p['genel_skor'] < 70;
        }),
        'uyarilar' => $uyarilar,
        'oneriler' => generateRecommendations($performans['detay'])
    ];
    
    return $rapor;
}

// Öneri sistemi
function generateRecommendations($performans_detaylari) {
    $oneriler = [];
    
    foreach ($performans_detaylari as $personel) {
        $personel_onerileri = [];
        
        if ($personel['devam_orani'] < 85) {
            $personel_onerileri[] = "Devam durumunu iyileştirmek için esnek çalışma saatleri değerlendirilebilir";
        }
        
        if ($personel['verimlilik_skoru'] < 70) {
            $personel_onerileri[] = "Verimlilik artışı için ek eğitim programları planlanabilir";
        }
        
        if ($personel['ortalama_calisma_saati'] > 10) {
            $personel_onerileri[] = "Aşırı mesai yapıyor, iş yükü dengelemesi gerekli";
        }
        
        if (!empty($personel_onerileri)) {
            $oneriler[$personel['personel_id']] = [
                'personel_adi' => $personel['personel_adi'],
                'oneriler' => $personel_onerileri
            ];
        }
    }
    
    return $oneriler;
}

// Excel export için veri hazırlama
function preparePerformanceExcelData($baslangic_tarih, $bitis_tarih) {
    $performans_verileri = getPerformansRaporlari($baslangic_tarih, $bitis_tarih);
    $excel_data = [];
    
    foreach ($performans_verileri['detay'] as $personel) {
        $excel_data[] = [
            'Personel Adı' => $personel['personel_adi'],
            'Sicil No' => $personel['sicil_no'],
            'Departman' => ucfirst($personel['departman']),
            'Devam Oranı (%)' => number_format($personel['devam_orani'], 2),
            'Ortalama Çalışma Saati' => number_format($personel['ortalama_calisma_saati'], 2),
            'Toplam Mesai Saati' => number_format($personel['mesai_saati'], 2),
            'Verimlilik Skoru (%)' => number_format($personel['verimlilik_skoru'], 2),
            'Son Değerlendirme' => $personel['son_degerlendirme_skoru'] ? number_format($personel['son_degerlendirme_skoru'], 2) : 'N/A',
            'Genel Performans Skoru (%)' => number_format($personel['genel_skor'], 2),
            'Performans Seviyesi' => $personel['genel_skor'] >= 85 ? 'Mükemmel' : 
                                  ($personel['genel_skor'] >= 70 ? 'İyi' : 
                                  ($personel['genel_skor'] >= 50 ? 'Orta' : 'Geliştirilmeli'))
        ];
    }
    
    return $excel_data;
}



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


// Belge kategorilerini getir
function getBelgeKategorileri($aktif = true) {
    global $pdo;
    try {
        $sql = "SELECT * FROM belge_kategorileri";
        if ($aktif) {
            $sql .= " WHERE aktif = 1";
        }
        $sql .= " ORDER BY ad ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}



// Belgeleri listele
function getBelgeler($kategori_id = null, $arama = '', $sayfa = 1, $limit = 20, $kullanici_id = null, $gizlilik_kontrolu = true) {
    global $pdo;
    
    try {
        $offset = ($sayfa - 1) * $limit;
        
        $sql = "SELECT b.*, bk.ad as kategori_adi, bk.icon as kategori_icon, bk.renk as kategori_renk,
                       p.ad as olusturan_ad, p.soyad as olusturan_soyad
                FROM belgeler b
                LEFT JOIN belge_kategorileri bk ON b.kategori_id = bk.id
                LEFT JOIN personel p ON b.olusturan_id = p.id
                WHERE b.aktif = 1";
        
        $params = [];
        
        // Kategori filtresi
        if ($kategori_id) {
            $sql .= " AND b.kategori_id = :kategori_id";
            $params['kategori_id'] = $kategori_id;
        }
        
        // Arama filtresi
        if ($arama) {
            $sql .= " AND (b.baslik LIKE :arama OR b.aciklama LIKE :arama OR b.etiketler LIKE :arama)";
            $params['arama'] = '%' . $arama . '%';
        }
        
        // Gizlilik kontrolü
        if ($gizlilik_kontrolu && $kullanici_id) {
            $sql .= " AND (b.gizlilik_seviyesi = 'genel' OR b.olusturan_id = :kullanici_id 
                      OR EXISTS (SELECT 1 FROM belge_erisim_izinleri bei 
                                WHERE bei.belge_id = b.id AND bei.kullanici_id = :kullanici_id2 AND bei.aktif = 1))";
            $params['kullanici_id'] = $kullanici_id;
            $params['kullanici_id2'] = $kullanici_id;
        }
        
        $sql .= " ORDER BY b.olusturma_tarihi DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        
        // Parametreleri bağla
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $belgeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Toplam kayıt sayısını al
        $count_sql = str_replace('SELECT b.*, bk.ad as kategori_adi, bk.icon as kategori_icon, bk.renk as kategori_renk, p.ad as olusturan_ad, p.soyad as olusturan_soyad', 'SELECT COUNT(*)', $sql);
        $count_sql = preg_replace('/ORDER BY.*?LIMIT.*/', '', $count_sql);
        
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue(':' . $key, $value);
        }
        $count_stmt->execute();
        $toplam = $count_stmt->fetchColumn();
        
        return [
            'belgeler' => $belgeler,
            'toplam' => $toplam,
            'sayfa' => $sayfa,
            'toplam_sayfa' => ceil($toplam / $limit)
        ];
        
    } catch(PDOException $e) {
        return [
            'belgeler' => [],
            'toplam' => 0,
            'sayfa' => 1,
            'toplam_sayfa' => 0
        ];
    }
}

// Belge detayını getir
function getBelgeDetay($belge_id, $kullanici_id = null) {
    global $pdo;
    
    try {
        $sql = "SELECT b.*, bk.ad as kategori_adi, bk.icon as kategori_icon, bk.renk as kategori_renk,
                       p.ad as olusturan_ad, p.soyad as olusturan_soyad
                FROM belgeler b
                LEFT JOIN belge_kategorileri bk ON b.kategori_id = bk.id
                LEFT JOIN personel p ON b.olusturan_id = p.id
                WHERE b.id = :belge_id AND b.aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['belge_id' => $belge_id]);
        $belge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$belge) {
            return null;
        }
        
        // Gizlilik kontrolü
        if ($belge['gizlilik_seviyesi'] !== 'genel' && $kullanici_id) {
            if ($belge['olusturan_id'] !== $kullanici_id) {
                // Erişim izni var mı kontrol et
                $izin_sql = "SELECT COUNT(*) FROM belge_erisim_izinleri 
                            WHERE belge_id = :belge_id AND kullanici_id = :kullanici_id AND aktif = 1";
                $izin_stmt = $pdo->prepare($izin_sql);
                $izin_stmt->execute(['belge_id' => $belge_id, 'kullanici_id' => $kullanici_id]);
                
                if ($izin_stmt->fetchColumn() == 0) {
                    return null; // Erişim izni yok
                }
            }
        }
        
        // Etiketleri decode et
        $belge['etiketler'] = json_decode($belge['etiketler'], true) ?: [];
        
        return $belge;
        
    } catch(PDOException $e) {
        return null;
    }
}

// Belge versiyonu ekleme
function belgeVersiyonEkle($belge_id, $versiyon_no, $dosya_adi, $dosya_boyutu, $degisiklik_aciklamasi, $olusturan_id) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO belge_versiyonlari (belge_id, versiyon_no, dosya_adi, dosya_boyutu, degisiklik_aciklamasi, olusturan_id)
                VALUES (:belge_id, :versiyon_no, :dosya_adi, :dosya_boyutu, :degisiklik_aciklamasi, :olusturan_id)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'belge_id' => $belge_id,
            'versiyon_no' => $versiyon_no,
            'dosya_adi' => $dosya_adi,
            'dosya_boyutu' => $dosya_boyutu,
            'degisiklik_aciklamasi' => $degisiklik_aciklamasi,
            'olusturan_id' => $olusturan_id
        ]);
        
    } catch(PDOException $e) {
        return false;
    }
}


// Belge aktivitesi ekleme
function belgeAktiviteEkle($belge_id, $kullanici_id, $aktivite, $aciklama = null) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO belge_aktiviteleri (belge_id, kullanici_id, aktivite, aciklama, ip_adresi, user_agent)
                VALUES (:belge_id, :kullanici_id, :aktivite, :aciklama, :ip_adresi, :user_agent)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'belge_id' => $belge_id,
            'kullanici_id' => $kullanici_id,
            'aktivite' => $aktivite,
            'aciklama' => $aciklama,
            'ip_adresi' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
    } catch(PDOException $e) {
        return false;
    }
}

// Belge indirme
function belgeIndir($belge_id, $kullanici_id) {
    $belge = getBelgeDetay($belge_id, $kullanici_id);
    
    if (!$belge) {
        return false;
    }
    
    $dosya_yolu = 'uploads/belgeler/' . $belge['dosya_adi'];
    
    if (!file_exists($dosya_yolu)) {
        return false;
    }
    
    // Aktivite kaydı oluştur
    belgeAktiviteEkle($belge_id, $kullanici_id, 'indirildi');
    
    // Dosyayı indir
    header('Content-Type: ' . $belge['mime_type']);
    header('Content-Disposition: attachment; filename="' . $belge['orijinal_dosya_adi'] . '"');
    header('Content-Length: ' . filesize($dosya_yolu));
    readfile($dosya_yolu);
    exit;
}

// Belge silme
function belgeSil($belge_id, $kullanici_id) {
    global $pdo;
    
    try {
        $belge = getBelgeDetay($belge_id, $kullanici_id);
        
        if (!$belge) {
            return [
                'success' => false,
                'message' => 'Belge bulunamadı veya erişim izniniz yok!'
            ];
        }
        
        // Sadece oluşturan veya admin silebilir
        if ($belge['olusturan_id'] !== $kullanici_id) {
            // Admin kontrolü yapılabilir
            return [
                'success' => false,
                'message' => 'Bu belgeyi silme yetkiniz yok!'
            ];
        }
        
        $pdo->beginTransaction();
        
        // Belgeyi pasif yap (soft delete)
        $sql = "UPDATE belgeler SET aktif = 0, guncelleyen_id = :kullanici_id WHERE id = :belge_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['belge_id' => $belge_id, 'kullanici_id' => $kullanici_id]);
        
        // Aktivite kaydı oluştur
        belgeAktiviteEkle($belge_id, $kullanici_id, 'silindi', 'Belge sistemden kaldırıldı');
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Belge başarıyla silindi!'
        ];
        
    } catch(PDOException $e) {
        $pdo->rollback();
        return [
            'success' => false,
            'message' => 'Silme işlemi başarısız!'
        ];
    }
}

// Belge güncelleme
function belgeGuncelle($belge_id, $baslik, $aciklama, $kategori_id, $belge_tarihi, $etiketler, $gizlilik_seviyesi, $kullanici_id) {
    global $pdo;
    
    try {
        $belge = getBelgeDetay($belge_id, $kullanici_id);
        
        if (!$belge) {
            return [
                'success' => false,
                'message' => 'Belge bulunamadı veya erişim izniniz yok!'
            ];
        }
        
        $sql = "UPDATE belgeler SET 
                    baslik = :baslik,
                    aciklama = :aciklama,
                    kategori_id = :kategori_id,
                    belge_tarihi = :belge_tarihi,
                    etiketler = :etiketler,
                    gizlilik_seviyesi = :gizlilik_seviyesi,
                    guncelleyen_id = :kullanici_id
                WHERE id = :belge_id";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'baslik' => $baslik,
            'aciklama' => $aciklama,
            'kategori_id' => $kategori_id,
            'belge_tarihi' => $belge_tarihi,
            'etiketler' => json_encode($etiketler),
            'gizlilik_seviyesi' => $gizlilik_seviyesi,
            'kullanici_id' => $kullanici_id,
            'belge_id' => $belge_id
        ]);
        
        if ($result) {
            // Aktivite kaydı oluştur
            belgeAktiviteEkle($belge_id, $kullanici_id, 'guncellendi', 'Belge bilgileri güncellendi');
            
            return [
                'success' => true,
                'message' => 'Belge başarıyla güncellendi!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Güncelleme başarısız!'
        ];
        
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Güncelleme işlemi başarısız!'
        ];
    }
}

// Favorilere ekleme/çıkarma
function belgeFavoriToggle($belge_id, $kullanici_id) {
    global $pdo;
    
    try {
        $belge = getBelgeDetay($belge_id, $kullanici_id);
        
        if (!$belge) {
            return [
                'success' => false,
                'message' => 'Belge bulunamadı!'
            ];
        }
        
        $yeni_durum = $belge['favori'] ? 0 : 1;
        
        $sql = "UPDATE belgeler SET favori = :favori WHERE id = :belge_id";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['favori' => $yeni_durum, 'belge_id' => $belge_id]);
        
        if ($result) {
            $aktivite = $yeni_durum ? 'favorilere_eklendi' : 'favorilerden_cikarildi';
            belgeAktiviteEkle($belge_id, $kullanici_id, 'favorilere_eklendi', 
                            $yeni_durum ? 'Favorilere eklendi' : 'Favorilerden çıkarıldı');
            
            return [
                'success' => true,
                'favori' => $yeni_durum,
                'message' => $yeni_durum ? 'Favorilere eklendi!' : 'Favorilerden çıkarıldı!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'İşlem başarısız!'
        ];
        
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'İşlem başarısız!'
        ];
    }
}

// Belge paylaşım linki oluştur
function belgePaylasimLinkiOlustur($belge_id, $kullanici_id, $sifre = null, $gecerlilik_tarihi = null, $indirme_limiti = null) {
    global $pdo;
    
    try {
        $belge = getBelgeDetay($belge_id, $kullanici_id);
        
        if (!$belge) {
            return [
                'success' => false,
                'message' => 'Belge bulunamadı!'
            ];
        }
        
        $paylasim_id = generateUUID();
        $paylasim_linki = md5($paylasim_id . time());
        
        $sql = "INSERT INTO belge_paylasimlari (id, belge_id, paylasim_linki, sifre, indirme_limiti, son_gecerlilik_tarihi, olusturan_id)
                VALUES (:id, :belge_id, :paylasim_linki, :sifre, :indirme_limiti, :son_gecerlilik_tarihi, :olusturan_id)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            'id' => $paylasim_id,
            'belge_id' => $belge_id,
            'paylasim_linki' => $paylasim_linki,
            'sifre' => $sifre ? password_hash($sifre, PASSWORD_DEFAULT) : null,
            'indirme_limiti' => $indirme_limiti,
            'son_gecerlilik_tarihi' => $gecerlilik_tarihi,
            'olusturan_id' => $kullanici_id
        ]);
        
        if ($result) {
            // Aktivite kaydı oluştur
            belgeAktiviteEkle($belge_id, $kullanici_id, 'paylasildi', 'Paylaşım linki oluşturuldu');
            
            return [
                'success' => true,
                'paylasim_linki' => $paylasim_linki,
                'tam_link' => $_SERVER['HTTP_HOST'] . '/belge_paylasim.php?link=' . $paylasim_linki,
                'message' => 'Paylaşım linki oluşturuldu!'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Paylaşım linki oluşturulamadı!'
        ];
        
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'İşlem başarısız!'
        ];
    }
}


// Belge arama (gelişmiş)
function belgelerdeArama($arama_terimi, $kategori_id = null, $tarih_baslangic = null, $tarih_bitis = null, $gizlilik_seviyesi = null, $kullanici_id = null) {
    global $pdo;
    
    try {
        $sql = "SELECT b.*, bk.ad as kategori_adi, bk.icon as kategori_icon, bk.renk as kategori_renk,
                       p.ad as olusturan_ad, p.soyad as olusturan_soyad,
                       MATCH(b.baslik, b.aciklama, b.etiketler) AGAINST(:arama_terimi IN BOOLEAN MODE) as relevance
                FROM belgeler b
                LEFT JOIN belge_kategorileri bk ON b.kategori_id = bk.id
                LEFT JOIN personel p ON b.olusturan_id = p.id
                WHERE b.aktif = 1";
        
        $params = ['arama_terimi' => $arama_terimi];
        
        // Arama terimi
        if ($arama_terimi) {
            $sql .= " AND (MATCH(b.baslik, b.aciklama, b.etiketler) AGAINST(:arama_terimi2 IN BOOLEAN MODE)
                     OR b.baslik LIKE :arama_like 
                     OR b.aciklama LIKE :arama_like2)";
            $params['arama_terimi2'] = $arama_terimi;
            $params['arama_like'] = '%' . $arama_terimi . '%';
            $params['arama_like2'] = '%' . $arama_terimi . '%';
        }
        
        // Diğer filtreler
        if ($kategori_id) {
            $sql .= " AND b.kategori_id = :kategori_id";
            $params['kategori_id'] = $kategori_id;
        }
        
        if ($tarih_baslangic) {
            $sql .= " AND b.belge_tarihi >= :tarih_baslangic";
            $params['tarih_baslangic'] = $tarih_baslangic;
        }
        
        if ($tarih_bitis) {
            $sql .= " AND b.belge_tarihi <= :tarih_bitis";
            $params['tarih_bitis'] = $tarih_bitis;
        }
        
        if ($gizlilik_seviyesi) {
            $sql .= " AND b.gizlilik_seviyesi = :gizlilik_seviyesi";
            $params['gizlilik_seviyesi'] = $gizlilik_seviyesi;
        }
        
        // Gizlilik kontrolü
        if ($kullanici_id) {
            $sql .= " AND (b.gizlilik_seviyesi = 'genel' OR b.olusturan_id = :kullanici_id 
                      OR EXISTS (SELECT 1 FROM belge_erisim_izinleri bei 
                                WHERE bei.belge_id = b.id AND bei.kullanici_id = :kullanici_id2 AND bei.aktif = 1))";
            $params['kullanici_id'] = $kullanici_id;
            $params['kullanici_id2'] = $kullanici_id;
        }
        
        $sql .= " ORDER BY relevance DESC, b.olusturma_tarihi DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        return [];
    }
}

// Belge istatistikleri
function getBelgeIstatistikleri($kullanici_id = null) {
    global $pdo;
    
    try {
        $stats = [];
        
        // Toplam belge sayısı
        $sql = "SELECT COUNT(*) as toplam FROM belgeler WHERE aktif = 1";
        $params = [];
        
        if ($kullanici_id) {
            $sql .= " AND (gizlilik_seviyesi = 'genel' OR olusturan_id = :kullanici_id)";
            $params['kullanici_id'] = $kullanici_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['toplam_belge'] = $stmt->fetchColumn();
        
        // Kategorilere göre dağılım
        $sql = "SELECT bk.ad, bk.renk, COUNT(*) as sayi 
                FROM belgeler b 
                LEFT JOIN belge_kategorileri bk ON b.kategori_id = bk.id 
                WHERE b.aktif = 1";
        
        if ($kullanici_id) {
            $sql .= " AND (b.gizlilik_seviyesi = 'genel' OR b.olusturan_id = :kullanici_id)";
        }
        
        $sql .= " GROUP BY b.kategori_id, bk.ad, bk.renk ORDER BY sayi DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['kategoriler'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Son yüklenen belgeler
        $sql = "SELECT b.baslik, b.olusturma_tarihi, bk.ad as kategori_adi
                FROM belgeler b
                LEFT JOIN belge_kategorileri bk ON b.kategori_id = bk.id
                WHERE b.aktif = 1";
        
        if ($kullanici_id) {
            $sql .= " AND (b.gizlilik_seviyesi = 'genel' OR b.olusturan_id = :kullanici_id)";
        }
        
        $sql .= " ORDER BY b.olusturma_tarihi DESC LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['son_belgeler'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Dosya boyutu toplamı
        $sql = "SELECT SUM(dosya_boyutu) as toplam_boyut FROM belgeler WHERE aktif = 1";
        
        if ($kullanici_id) {
            $sql .= " AND (gizlilik_seviyesi = 'genel' OR olusturan_id = :kullanici_id)";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['toplam_boyut'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
        
    } catch(PDOException $e) {
        return [
            'toplam_belge' => 0,
            'kategoriler' => [],
            'son_belgeler' => [],
            'toplam_boyut' => 0
        ];
    }
}


// Belge yükleme fonksiyonu
 function belgeYukle($dosya, $kategori_id, $baslik, $kullanici_id, $aciklama = '', $belge_tarihi = null, $etiketler = [], $gizlilik_seviyesi = 'genel') {
    global $pdo;
    
    // Debug için dosya bilgilerini logla
    error_log("belgeYukle çağrıldı - Dosya: " . print_r($dosya, true));
    error_log("Kategori ID: $kategori_id, Başlık: $baslik, Kullanıcı ID: $kullanici_id");
    
    // Dosya upload kontrolü
    if (!isset($dosya['tmp_name']) || empty($dosya['tmp_name'])) {
        return [
            'success' => false,
            'message' => 'Dosya yüklenmedi! tmp_name boş.'
        ];
    }
    
    if (!is_uploaded_file($dosya['tmp_name'])) {
        return [
            'success' => false,
            'message' => 'Güvenlik hatası: Geçersiz dosya yükleme!'
        ];
    }
    
    // Dosya kontrolleri
    $izinli_uzantilar = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'];
    $max_boyut = 50 * 1024 * 1024; // 50MB
    
    $dosya_info = pathinfo($dosya['name']);
    $uzanti = strtolower($dosya_info['extension'] ?? '');
    
    if (empty($uzanti)) {
        return [
            'success' => false,
            'message' => 'Dosya uzantısı bulunamadı!'
        ];
    }
    
    if (!in_array($uzanti, $izinli_uzantilar)) {
        return [
            'success' => false,
            'message' => "Bu dosya türü ($uzanti) desteklenmemektedir!"
        ];
    }
    
    if ($dosya['size'] > $max_boyut) {
        return [
            'success' => false,
            'message' => 'Dosya boyutu 50MB\'dan büyük olamaz! Mevcut boyut: ' . round($dosya['size']/1024/1024, 2) . 'MB'
        ];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Benzersiz dosya adı oluştur
        $belge_id = uniqid();
        $yeni_dosya_adi = $belge_id . '.' . $uzanti;
        $upload_dir = 'uploads/belgeler/';
        
        // Upload klasörü yoksa oluştur
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Upload klasörü oluşturulamadı: ' . $upload_dir);
            }
        }
        
        // Klasör yazılabilir mi kontrol et
        if (!is_writable($upload_dir)) {
            throw new Exception('Upload klasörü yazılabilir değil: ' . $upload_dir);
        }
        
        $target_file = $upload_dir . $yeni_dosya_adi;
        
        // Dosyayı yükle
        if (!move_uploaded_file($dosya['tmp_name'], $target_file)) {
            $upload_error = error_get_last();
            throw new Exception('Dosya yüklenirken hata oluştu! Hedef: ' . $target_file . ' Hata: ' . print_r($upload_error, true));
        }
        
        // Dosya gerçekten oluştu mu kontrol et
        if (!file_exists($target_file)) {
            throw new Exception('Dosya yüklendi ancak hedef konumda bulunamadı: ' . $target_file);
        }
        
        error_log("Dosya başarıyla yüklendi: " . $target_file);
        
        // Veritabanına kaydet
        $sql = "INSERT INTO belgeler (
                    id, kategori_id, baslik, aciklama, dosya_adi, orijinal_dosya_adi, 
                    dosya_uzantisi, dosya_boyutu, mime_type, belge_tarihi, 
                    etiketler, gizlilik_seviyesi, olusturan_id
                ) VALUES (
                    :id, :kategori_id, :baslik, :aciklama, :dosya_adi, :orijinal_dosya_adi,
                    :dosya_uzantisi, :dosya_boyutu, :mime_type, :belge_tarihi,
                    :etiketler, :gizlilik_seviyesi, :olusturan_id
                )";
        
        $stmt = $pdo->prepare($sql);
        
        $data = [
            'id' => $belge_id,
            'kategori_id' => $kategori_id,
            'baslik' => $baslik,
            'aciklama' => $aciklama,
            'dosya_adi' => $yeni_dosya_adi,
            'orijinal_dosya_adi' => $dosya['name'],
            'dosya_uzantisi' => $uzanti,
            'dosya_boyutu' => $dosya['size'],
            'mime_type' => $dosya['type'] ?? 'application/octet-stream',
            'belge_tarihi' => $belge_tarihi ?: date('Y-m-d'),
            'etiketler' => json_encode($etiketler),
            'gizlilik_seviyesi' => $gizlilik_seviyesi,
            'olusturan_id' => $kullanici_id
        ];
        
        error_log("Veritabanı verisi: " . print_r($data, true));
        
        $result = $stmt->execute($data);
        
        if (!$result) {
            $error_info = $stmt->errorInfo();
            throw new Exception('Veritabanı kaydı başarısız! PDO Error: ' . print_r($error_info, true));
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Belge başarıyla yüklendi!',
            'belge_id' => $belge_id
        ];
        
    } catch(Exception $e) {
        $pdo->rollback();
        
        // Yüklenen dosyayı sil
        if (isset($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }
        
        error_log("belgeYukle Hatası: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ];
    }
}


function odaSaatKilitle($room_id, $tarih, $saat, $aciklama = '', $kilit_turu = 'manuel') {
    global $pdo;
    
    try {
        // Önce zaten kilitli mi kontrol et
        if (odaSaatKilitliMi($room_id, $tarih, $saat)) {
            return ['success' => false, 'message' => 'Bu oda ve saat zaten kilitli'];
        }
        
        // O saatte randevu var mı kontrol et
        $randevu_check = $pdo->prepare("SELECT id FROM randevular WHERE room_id = ? AND DATE(randevu_tarihi) = ? AND TIME(randevu_tarihi) = ? AND aktif = 1");
        $randevu_check->execute([$room_id, $tarih, $saat]);
        
        if ($randevu_check->fetch()) {
            return ['success' => false, 'message' => 'Bu saatte aktif randevu bulunmaktadır'];
        }
        
        $sql = "INSERT INTO room_time_locks (room_id, tarih, saat, kilit_turu, aciklama, kilitleyen_kullanici) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$room_id, $tarih, $saat, $kilit_turu, $aciklama, $_SESSION['user_id'] ?? null]);
        
        if ($result) {
            // Log kaydı
            logOdaKilitleme($room_id, $tarih, $saat, 'kilitlendi', $aciklama);
            return ['success' => true, 'message' => 'Oda ve saat başarıyla kilitlendi'];
        }
        
        return ['success' => false, 'message' => 'Kilitleme işlemi başarısız'];
        
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            return ['success' => false, 'message' => 'Bu oda ve saat zaten kilitli'];
        }
        return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
    }
}

/**
 * Oda ve saat kilidini aç
 */
function odaSaatKilidiniAc($room_id, $tarih, $saat) {
    global $pdo;
    
    try {
        $sql = "DELETE FROM room_time_locks WHERE room_id = ? AND tarih = ? AND saat = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$room_id, $tarih, $saat]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log kaydı
            logOdaKilitleme($room_id, $tarih, $saat, 'acildi', 'Kilit açıldı');
            return ['success' => true, 'message' => 'Kilit başarıyla açıldı'];
        }
        
        return ['success' => false, 'message' => 'Açılacak kilit bulunamadı'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
    }
}

/**
 * Oda ve saat kilitli mi kontrol et
 */
function odaSaatKilitliMi($room_id, $tarih, $saat) {
    global $pdo;
    
    $sql = "SELECT id FROM room_time_locks WHERE room_id = ? AND tarih = ? AND saat = ? AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $tarih, $saat]);
    
    return $stmt->fetch() ? true : false;
}

/**
 * Belirli tarih için kilitli saatleri getir
 */
function getKilitliSaatler($room_id, $tarih) {
    global $pdo;
    
    $sql = "SELECT 
                rtl.*,
                r.name as room_name
            FROM room_time_locks rtl
            JOIN rooms r ON rtl.room_id = r.id
            WHERE rtl.room_id = ? AND rtl.tarih = ? AND rtl.aktif = 1
            ORDER BY rtl.saat";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $tarih]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Tüm odalar için belirli tarihte kilitli saatleri getir
 */
function getTumKilitliSaatler($tarih) {
    global $pdo;
    
    $sql = "SELECT 
                rtl.*,
                r.name as room_name
            FROM room_time_locks rtl
            JOIN rooms r ON rtl.room_id = r.id
            WHERE rtl.tarih = ? AND rtl.aktif = 1
            ORDER BY rtl.room_id, rtl.saat";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tarih]);
    
    $locks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Room ID'ye göre grupla
    $grouped = [];
    foreach ($locks as $lock) {
        $grouped[$lock['room_id']][] = $lock;
    }
    
    return $grouped;
}

/**
 * Kilitleme logları
 */
function logOdaKilitleme($room_id, $tarih, $saat, $islem, $aciklama = '') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO room_time_lock_logs (room_id, tarih, saat, islem, aciklama, kullanici_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$room_id, $tarih, $saat, $islem, $aciklama, $_SESSION['user_id'] ?? null]);
    } catch (PDOException $e) {
        error_log("Oda kilitleme log hatası: " . $e->getMessage());
    }
}

/**
 * Toplu kilitleme - birden fazla saat için
 */
function topluOdaKilitle($room_id, $tarih, $saatler, $aciklama = '', $kilit_turu = 'manuel') {
    $basarili = 0;
    $hatali = 0;
    $mesajlar = [];
    
    foreach ($saatler as $saat) {
        $result = odaSaatKilitle($room_id, $tarih, $saat, $aciklama, $kilit_turu);
        if ($result['success']) {
            $basarili++;
        } else {
            $hatali++;
            $mesajlar[] = "Saat $saat: " . $result['message'];
        }
    }
    
    return [
        'success' => $basarili > 0,
        'basarili' => $basarili,
        'hatali' => $hatali,
        'mesajlar' => $mesajlar,
        'message' => "$basarili saat kilitlendi, $hatali saat kilitlenemedi"
    ];
}

/**
 * Randevu ekleme sırasında çakışma kontrolü (kilitli saatler dahil)
 */
function randevuEklemeKontrol($room_id, $randevu_tarihi) {
    $tarih = date('Y-m-d', strtotime($randevu_tarihi));
    $saat = date('H:i:s', strtotime($randevu_tarihi));
    
    // Kilitli mi kontrol et
    if (odaSaatKilitliMi($room_id, $tarih, $saat)) {
        return ['success' => false, 'message' => 'Bu oda ve saat kilitlidir'];
    }
    
    // Normal çakışma kontrolü
    global $pdo;
    $sql = "SELECT id FROM randevular WHERE room_id = ? AND randevu_tarihi = ? AND aktif = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id, $randevu_tarihi]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Bu saatte başka bir randevu bulunmaktadır'];
    }
    
    return ['success' => true, 'message' => 'Randevu eklenebilir'];
}


// Dosya boyutunu formatla
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Belge kategorisi ekleme
function belgeKategorisiEkle($ad, $aciklama, $icon = 'fas fa-file-alt', $renk = '#007bff') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO belge_kategorileri (ad, aciklama, icon, renk) VALUES (:ad, :aciklama, :icon, :renk)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'ad' => $ad,
            'aciklama' => $aciklama,
            'icon' => $icon,
            'renk' => $renk
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

// Hatırlatma ekleme
function belgeHatirlatmaEkle($belge_id, $kullanici_id, $hatirlatma_tarihi, $mesaj, $bildirim_turu = 'sistem') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO belge_hatirlatmalari (belge_id, kullanici_id, hatirlatma_tarihi, mesaj, bildirim_turu)
                VALUES (:belge_id, :kullanici_id, :hatirlatma_tarihi, :mesaj, :bildirim_turu)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'belge_id' => $belge_id,
            'kullanici_id' => $kullanici_id,
            'hatirlatma_tarihi' => $hatirlatma_tarihi,
            'mesaj' => $mesaj,
            'bildirim_turu' => $bildirim_turu
        ]);
    } catch(PDOException $e) {
        return false;
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