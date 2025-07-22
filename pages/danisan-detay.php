<?php
$danisan_id = $_GET['id'] ?? 0;
$active_tab = $_GET['tab'] ?? 'ilk-kayit';
$hata = '';
$basari = '';

// Get client details
$danisan = getDanisanDetay($danisan_id);
if (!$danisan) {
    header("Location: ?page=danisanlar");
    exit;
}


// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax_action'] == 'sil') {
        $hedef_id = $_POST['hedef_id'] ?? 0;
        
        if (hedefSil($hedef_id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Hedef başarıyla silindi.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Hedef silinirken bir hata oluştu.'
            ]);
        }
        exit;
    }
}


// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'hedef_ekle':
                $hedef_turleri = $_POST['hedef_turleri'] ?? [];
                $notlar = $_POST['notlar'] ?? [];
                
                $basarili = true;
                foreach ($hedef_turleri as $hedef_turu_id) {
                    if (!empty($hedef_turu_id)) {
                        $sonuc = hedefEkle(
                            $danisan_id,
                            $hedef_turu_id,
                            0, // hedef değer
                            0, // başlangıç değer
                            null, // bitiş tarihi
                            $notlar[$hedef_turu_id] ?? null
                        );
                        if (!$sonuc) {
                            $basarili = false;
                        }
                    }
                }
                
                if ($basarili) {
                    $basari = "Hedefler başarıyla eklendi.";
                } else {
                    $hata = "Hedefler eklenirken bir hata oluştu.";
                }
                break;

            case 'ilk_kayit_ekle':
                $sonuc = ilkKayitEkle($danisan_id, $_POST['tespit']);
                if ($sonuc) {
                    $basari = "İlk kayıt tespiti başarıyla eklendi.";
                } else {
                    $hata = "İlk kayıt tespiti eklenirken bir hata oluştu.";
                }
                break;

            case 'olcum_ekle':
                $sonuc = olcumEkle(
                    $danisan_id,
                    $_POST['yag'],
                    $_POST['kas'],
                    $_POST['kilo'],
                    $_POST['posturel_analiz']
                );
                if ($sonuc) {
                    $basari = "Ölçüm değerleri başarıyla eklendi.";
                } else {
                    $hata = "Ölçüm değerleri eklenirken bir hata oluştu.";
                }
                break;

            case 'rapor_ekle':
                $upload = handleFileUpload($_FILES['rapor'], 'reports');
                if ($upload['success']) {
                    $sonuc = raporEkle($danisan_id, $upload['filename']);
                    if ($sonuc) {
                        $basari = "Doktor raporu başarıyla yüklendi.";
                    } else {
                        $hata = "Doktor raporu kaydedilirken bir hata oluştu.";
                    }
                } else {
                    $hata = $upload['message'];
                }
                break;

            case 'beslenme_ekle':
                $sonuc = beslenmeListesiEkle($danisan_id, $_POST['liste']);
                if ($sonuc) {
                    $basari = "Beslenme listesi başarıyla eklendi.";
                } else {
                    $hata = "Beslenme listesi eklenirken bir hata oluştu.";
                }
                break;

            case 'talep_ekle':
                $sonuc = talepEkle($danisan_id, $_POST['talep']);
                if ($sonuc) {
                    $basari = "Talep başarıyla eklendi.";
                } else {
                    $hata = "Talep eklenirken bir hata oluştu.";
                }
                break;

            case 'aciklama_ekle':
                $sonuc = aciklamaEkle($danisan_id, $_POST['aciklama']);
                if ($sonuc) {
                    $basari = "Açıklama başarıyla eklendi.";
                } else {
                    $hata = "Açıklama eklenirken bir hata oluştu.";
                }
                break;

            case 'iletisim_ekle':
                $sonuc = iletisimEkle(
                    $danisan_id,
                    $_POST['arama_turu'],
                    $_SESSION['user_id'],
                    $_POST['notlar']
                );
                if ($sonuc) {
                    $basari = "İletişim kaydı başarıyla eklendi.";
                } else {
                    $hata = "İletişim kaydı eklenirken bir hata oluştu.";
                }
                break;
        }
    }
} 


// Get all records including goals
$hedef_turleri = getHedefTurleri();
$aktif_hedefler = getDanisanHedefleri($danisan_id);
$ilk_kayitlar = getIlkKayitlar($danisan_id);
$olcumler = getOlcumler($danisan_id);
$raporlar = getRaporlar($danisan_id);
$beslenme_listeleri = getBeslenmeListe($danisan_id);
$talepler = getTalepler($danisan_id);
$aciklamalar = getAciklamalar($danisan_id);
$iletisim_kayitlari = getIletisimKayitlari($danisan_id);





?>

<div class="container-fluid">
<div id="alert-container" class="mt-3"></div>
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <!-- Danışan Başlık Bilgileri -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1"><?php echo $danisan['ad'] . ' ' . $danisan['soyad']; ?></h4>
                    <p class="text-muted mb-0">
                        <?php echo $danisan['email']; ?> | <?php echo $danisan['telefon']; ?>
                    </p>
                </div>
                <a href="?page=danisanlar" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Geri Dön
                </a>
            </div>
        </div>
    </div>

    <!-- Tab Menüsü -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'ilk-kayit' ? 'active' : ''; ?>" 
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=ilk-kayit">
                İlk Kayıt Tespitleri
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'olcum' ? 'active' : ''; ?>"
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=olcum">
                Ölçüm Değerleri
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'rapor' ? 'active' : ''; ?>"
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=rapor">
                Doktor Raporları
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'beslenme' ? 'active' : ''; ?>"
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=beslenme">
                Beslenme Listeleri
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'talep' ? 'active' : ''; ?>"
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=talep">
                Hedefler
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'aciklama' ? 'active' : ''; ?>"
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=aciklama">
                Açıklamalar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'iletisim' ? 'active' : ''; ?>"
               href="?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=iletisim">
                İletişim Kayıtları
            </a>
        </li>
    </ul>

    <!-- Tab İçerikleri -->
    <div class="tab-content">
        <?php if ($active_tab == 'ilk-kayit'): ?>
            <!-- İlk Kayıt Tespitleri -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">İlk Kayıt Tespitleri</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ilkKayitModal">
                        <i class="bx bx-plus"></i> Yeni Tespit
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tespit</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ilk_kayitlar as $kayit): ?>
                                <tr>
                                    <td><?php echo $kayit['tespit']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($kayit['tarih'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu tespiti silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=ilk-kayit&action=sil&kayit_id=<?php echo $kayit['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab == 'olcum'): ?>
            <!-- Ölçüm Değerleri -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ölçüm Değerleri</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#olcumModal">
                        <i class="bx bx-plus"></i> Yeni Ölçüm
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Yağ (%)</th>
                                    <th>Kas (%)</th>
                                    <th>Kilo (kg)</th>
                                    <th>Postürel Analiz</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($olcumler as $olcum): ?>
                                <tr>
                                    <td><?php echo $olcum['yag']; ?></td>
                                    <td><?php echo $olcum['kas']; ?></td>
                                    <td><?php echo $olcum['kilo']; ?></td>
                                    <td><?php echo $olcum['posturel_analiz']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($olcum['tarih'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu ölçümü silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=olcum&action=sil&olcum_id=<?php echo $olcum['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab == 'rapor'): ?>
            <!-- Doktor Raporları -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Doktor Raporları</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#raporModal">
                        <i class="bx bx-plus"></i> Yeni Rapor
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rapor</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($raporlar as $rapor): ?>
                                <tr>
                                    <td>
                                        <a href="uploads/reports/<?php echo $rapor['rapor']; ?>" 
                                           target="_blank" class="btn btn-sm btn-info">
                                            <i class="bx bx-file"></i> Görüntüle
                                        </a>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($rapor['tarih'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu raporu silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=rapor&action=sil&rapor_id=<?php echo $rapor['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab == 'beslenme'): ?>
            <!-- Beslenme Listeleri -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Beslenme Listeleri</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#beslenmeModal">
                        <i class="bx bx-plus"></i> Yeni Liste
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Liste</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beslenme_listeleri as $liste): ?>
                                <tr>
                                    <td><?php echo $liste['liste']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($liste['tarih'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu listeyi silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=beslenme&action=sil&liste_id=<?php echo $liste['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab == 'talep'): ?>
            <!-- Talepler -->
     <!-- Hedefler -->
     <!-- Hedefler -->
     <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Hedefler</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hedefModal">
                <i class="bx bx-plus"></i> Yeni Hedef
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hedef Türü</th>
                            <th>Notlar</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="hedefler-tbody">
                        <?php foreach ($aktif_hedefler as $hedef): ?>
                        <tr id="hedef-row-<?php echo $hedef['id']; ?>">
                            <td><?php echo $hedef['hedef_adi']; ?></td>
                            <td>
                                <?php if ($hedef['notlar']): ?>
                                    <?php echo nl2br(htmlspecialchars($hedef['notlar'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getHedefDurumClass($hedef['durum']); ?>">
                                    <?php echo ucfirst($hedef['durum']); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="hedefSil('<?php echo $hedef['id']; ?>')">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($aktif_hedefler)): ?>
                        <tr id="no-goals-row">
                            <td colspan="4" class="text-center">Henüz hedef belirlenmemiş.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
    
        <?php elseif ($active_tab == 'aciklama'): ?>
            <!-- Açıklamalar -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Açıklamalar</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aciklamaModal">
                        <i class="bx bx-plus"></i> Yeni Açıklama
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Açıklama</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aciklamalar as $aciklama): ?>
                                <tr>
                                    <td><?php echo $aciklama['aciklama']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($aciklama['tarih'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu açıklamayı silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=aciklama&action=sil&aciklama_id=<?php echo $aciklama['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($active_tab == 'iletisim'): ?>
            <!-- İletişim Kayıtları -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">İletişim Kayıtları</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#iletisimModal">
                        <i class="bx bx-plus"></i> Yeni Kayıt
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Arama Türü</th>
                                    <th>Personel</th>
                                    <th>Notlar</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($iletisim_kayitlari as $kayit): ?>
                                <tr>
                                    <td><?php echo $kayit['arama_turu']; ?></td>
                                    <td><?php echo $kayit['personel_adi']; ?></td>
                                    <td><?php echo $kayit['notlar']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($kayit['tarih'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="if(confirm('Bu kaydı silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=iletisim&action=sil&kayit_id=<?php echo $kayit['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modals -->
<!-- İlk Kayıt Modal -->
<div class="modal fade" id="ilkKayitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="ilk_kayit_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni İlk Kayıt Tespiti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tespit</label>
                        <textarea name="tespit" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Rapor Modal -->
<div class="modal fade" id="raporModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="islem" value="rapor_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Doktor Raporu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rapor Dosyası</label>
                        <input type="file" name="rapor" class="form-control" required>
                        <small class="text-muted">PDF, JPG veya PNG formatında dosya yükleyebilirsiniz.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Yükle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Beslenme Modal -->
<div class="modal fade" id="beslenmeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="beslenme_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Beslenme Listesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Liste</label>
                        <textarea name="liste" class="form-control" rows="10" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Talep Modal -->
<div class="modal fade" id="talepModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="talep_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Talep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Talep</label>
                        <textarea name="talep" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Açıklama Modal -->
<div class="modal fade" id="aciklamaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="aciklama_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Açıklama</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ölçüm Modal -->
<div class="modal fade" id="olcumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="olcum_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Ölçüm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Yağ (%)</label>
                            <input type="number" name="yag" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kas (%)</label>
                            <input type="number" name="kas" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kilo (kg)</label>
                            <input type="number" name="kilo" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Postürel Analiz</label>
                        <textarea name="posturel_analiz" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Hedef Ekleme Modal -->
<!-- Hedef Ekleme Modal -->
<div class="modal fade" id="hedefModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="hedef_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Hedef Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <?php foreach ($hedef_turleri as $hedef): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input hedef-check" 
                                               id="hedef_<?php echo $hedef['id']; ?>"
                                               name="hedef_turleri[]" 
                                               value="<?php echo $hedef['id']; ?>">
                                        <label class="form-check-label" for="hedef_<?php echo $hedef['id']; ?>">
                                            <?php echo $hedef['ad']; ?>
                                        </label>
                                    </div>
                                    
                                    <div class="hedef-inputs" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Notlar</label>
                                            <textarea name="notlar[<?php echo $hedef['id']; ?>]" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>




<!-- İletişim Modal -->
<div class="modal fade" id="iletisimModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="islem" value="iletisim_ekle">
                
                <div class="modal-header">
                    <h5 class="modal-title">Yeni İletişim Kaydı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Arama Türü</label>
                        <select name="arama_turu" class="form-select" required>
                            <option value="">Seçiniz</option>
                            <option value="Yeni Danışan İlk Temas Fizyo">Yeni Danışan İlk Temas Fizyo</option>
                            <option value="Yeni Danışan İlk Temas Diyet">Yeni Danışan İlk Temas Diyet</option>
                            <option value="Mevcut Danışanlar Fizyo">Mevcut Danışanlar Fizyo</option>
                            <option value="Mevcut Danışanlar Diyet">Mevcut Danışanlar Diyet</option>
                            <option value="Online Diyet Danışanları">Online Diyet Danışanları</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea name="notlar" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hedef seçimi kontrolü
    const hedefChecks = document.querySelectorAll('.hedef-check');
    hedefChecks.forEach(check => {
        check.addEventListener('change', function() {
            const inputs = this.closest('.card-body').querySelector('.hedef-inputs');
            inputs.style.display = this.checked ? 'block' : 'none';
            
            if (!this.checked) {
                inputs.querySelectorAll('textarea').forEach(input => {
                    input.value = '';
                });
            }
        });
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hedef seçimi kontrolü
    const hedefChecks = document.querySelectorAll('.hedef-check');
    hedefChecks.forEach(check => {
        check.addEventListener('change', function() {
            const inputs = this.closest('.card-body').querySelector('.hedef-inputs');
            inputs.style.display = this.checked ? 'block' : 'none';
            
            if (!this.checked) {
                inputs.querySelectorAll('textarea').forEach(input => {
                    input.value = '';
                });
            }
        });
    });
});

// Show alert function
function showAlert(message, type) {
    const alertContainer = document.getElementById('alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    alertContainer.appendChild(alertDiv);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// AJAX delete function
function hedefSil(hedefId) {
    if (confirm('Bu hedefi silmek istediğinizden emin misiniz?')) {
        const formData = new FormData();
        formData.append('ajax_action', 'sil');
        formData.append('hedef_id', hedefId);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);

                if (data.success) {
                    // Silme başarılıysa sayfayı yenile
                    window.location.href = "?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=talep";
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                window.location.href = "?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=talep";
                console.error("JSON parse hatası:", error);
                console.log("Gelen veri:", text);
                showAlert('Bir hata oluştu.', 'danger');
            }
        })
        .catch(error => {
          //  showAlert('Bir hata oluştu.', 'danger');
           // console.error('Fetch hatası:', error);
           window.location.href = "?page=danisan-detay&id=<?php echo $danisan_id; ?>&tab=talep";
        });
    }
}
</script>