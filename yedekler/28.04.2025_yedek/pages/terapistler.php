<?php
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'sifre_degistir':
                if ($_POST['yeni_sifre'] !== $_POST['yeni_sifre_tekrar']) {
                    $hata = "Yeni şifreler eşleşmiyor!";
                    break;
                }
                
                $sonuc = sifreDegistir(
                    $_POST['id'],
                    $_POST['eski_sifre'],
                    $_POST['yeni_sifre']
                );
                if ($sonuc['success']) {
                    $basari = $sonuc['message'];
                } else {
                    $hata = $sonuc['message'];
                }
                break;

            case 'terapist_guncelle':
                $updateData = [
                    'ad' => $_POST['ad'] ?? '',
                    'soyad' => $_POST['soyad'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'cinsiyet' => $_POST['cinsiyet'] ?? null,
                    'mezuniyet' => $_POST['mezuniyet'] ?? null,
                    'rol' => $_POST['rol'] ?? 'terapist'
                ];

                // Avatar yükleme işlemi
                if (!empty($_FILES['avatar']['name'])) {
                    $avatar = handleAvatarUpload($_FILES['avatar']);
                    if ($avatar['success']) {
                        $updateData['avatar'] = $avatar['filename'];
                    } else {
                        $hata = $avatar['message'];
                        break;
                    }
                }

                $sonuc = personelGuncelle($_POST['id'], $updateData);
                if ($sonuc) {
                    $basari = "Personel başarıyla güncellendi.";
                } else {
                    $hata = "Personel güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Get therapist details for editing
$duzenlenecek_terapist = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $terapistler = getTerapistler(false);
    foreach ($terapistler as $terapist) {
        if ($terapist['id'] == $_GET['id']) {
            $duzenlenecek_terapist = $terapist;
            break;
        }
    }
}

// Handle activation/deactivation
if ($action == 'activate' || $action == 'deactivate') {
    $id = $_GET['id'] ?? 0;
    $aktif = $action == 'activate' ? 1 : 0;
    $sonuc = personelDurumGuncelle($id, $aktif);
    if ($sonuc) {
        header("Location: ?page=terapistler");
        exit;
    }
}

// Handle deletion
if ($action == 'delete') {
    $id = $_GET['id'] ?? 0;
    $sonuc = personelSil($id);
    if ($sonuc) {
        header("Location: ?page=terapistler");
        exit;
    }
}
?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <!-- Terapist Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Terapistler</h5>
                    <small class="text-muted">Terapi merkezi personel listesi</small>
                </div>
                <div>
                    <a href="?page=personel-kayit" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Yeni Personel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>Ad Soyad</th>
                                <th>TC No</th>
                                <th>Sicil No</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th style="width: 150px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $terapistler = getTerapistler(false);
                            foreach ($terapistler as $terapist): 
                            ?>
                            <tr>
                                <td>
                                    <div class="avatar">
                                        <img src="<?php echo $terapist['avatar'] ? 'uploads/avatars/' . $terapist['avatar'] : 'assets/img/default-avatar.png'; ?>" 
                                             alt="<?php echo $terapist['ad'] . ' ' . $terapist['soyad']; ?>"
                                             class="rounded-circle"
                                             width="40" height="40">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"><?php echo $terapist['ad'] . ' ' . $terapist['soyad']; ?></span>
                                        <small class="text-muted"><?php echo $terapist['email']; ?></small>
                                    </div>
                                </td>
                                <td><?php echo $terapist['tc_no']; ?></td>
                                <td><?php echo $terapist['sicil_no']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getRoleBadgeClass($terapist['rol']); ?>">
                                        <?php echo ucfirst($terapist['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $terapist['aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $terapist['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=terapist-detay&id=<?php echo $terapist['id']; ?>" 
                                           class="btn btn-info" title="Detay">
                                            <i class="bx bx-detail"></i>
                                        </a>
                                        <a href="?page=terapistler&action=edit&id=<?php echo $terapist['id']; ?>" 
                                           class="btn btn-primary" title="Düzenle">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <?php if ($terapist['aktif']): ?>
                                            <button type="button" class="btn btn-warning" title="Pasife Al"
                                                    onclick="if(confirm('Bu personeli pasife almak istediğinizden emin misiniz?'))
                                                    window.location.href='?page=terapistler&action=deactivate&id=<?php echo $terapist['id']; ?>'">
                                                <i class="bx bx-power-off"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success" title="Aktife Al"
                                                    onclick="if(confirm('Bu personeli aktife almak istediğinizden emin misiniz?'))
                                                    window.location.href='?page=terapistler&action=activate&id=<?php echo $terapist['id']; ?>'">
                                                <i class="bx bx-power-off"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-danger" title="Sil"
                                                onclick="if(confirm('Bu personeli silmek istediğinizden emin misiniz?'))
                                                window.location.href='?page=terapistler&action=delete&id=<?php echo $terapist['id']; ?>'">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Personel Düzenleme Formu -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Personel Düzenle</h5>
                        <small class="text-muted">Personel bilgilerini güncelle</small>
                    </div>
                    <a href="?page=terapistler" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Geri Dön
                    </a>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#bilgiler">Genel Bilgiler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sifre">Şifre Değiştir</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Genel Bilgiler -->
                    <div class="tab-pane fade show active" id="bilgiler">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="islem" value="terapist_guncelle">
                            <input type="hidden" name="id" value="<?php echo $duzenlenecek_terapist['id']; ?>">

                            <div class="row">
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <img src="<?php echo $duzenlenecek_terapist['avatar'] ? 'uploads/avatars/' . $duzenlenecek_terapist['avatar'] : 'assets/img/default-avatar.png'; ?>" 
                                             alt="<?php echo $duzenlenecek_terapist['ad'] . ' ' . $duzenlenecek_terapist['soyad']; ?>"
                                             class="rounded-circle img-thumbnail mb-3"
                                             width="150" height="150">
                                        <div>
                                            <label class="btn btn-outline-primary btn-sm">
                                                <i class="bx bx-upload"></i> Fotoğraf Yükle
                                                <input type="file" name="avatar" class="d-none" accept="image/*">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Ad</label>
                                            <input type="text" name="ad" class="form-control" required
                                                   value="<?php echo $duzenlenecek_terapist['ad']; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Soyad</label>
                                            <input type="text" name="soyad" class="form-control" required
                                                   value="<?php echo $duzenlenecek_terapist['soyad']; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">E-posta</label>
                                            <input type="email" name="email" class="form-control" required
                                                   value="<?php echo $duzenlenecek_terapist['email']; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TC No</label>
                                            <input type="text" class="form-control" value="<?php echo $duzenlenecek_terapist['tc_no']; ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sicil No</label>
                                            <input type="text" class="form-control" value="<?php echo $duzenlenecek_terapist['sicil_no']; ?>" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Cinsiyet</label>
                                            <select name="cinsiyet" class="form-select" required>
                                                <option value="">Seçiniz</option>
                                                <option value="Erkek" <?php echo $duzenlenecek_terapist['cinsiyet'] == 'Erkek' ? 'selected' : ''; ?>>Erkek</option>
                                                <option value="Kadın" <?php echo $duzenlenecek_terapist['cinsiyet'] == 'Kadın' ? 'selected' : ''; ?>>Kadın</option>
                                                <option value="Diğer" <?php echo $duzenlenecek_terapist['cinsiyet'] == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mezuniyet</label>
                                            <select name="mezuniyet" class="form-select" required>
                                                <option value="">Seçiniz</option>
                                                <option value="Ön Lisans" <?php echo $duzenlenecek_terapist['mezuniyet'] == 'Ön Lisans' ? 'selected' : ''; ?>>Ön Lisans</option>
                                                <option value="Lisans" <?php echo $duzenlenecek_terapist['mezuniyet'] == 'Lisans' ? 'selected' : ''; ?>>Lisans</option>
                                                <option value="Yüksek Lisans" <?php echo $duzenlenecek_terapist['mezuniyet'] == 'Yüksek Lisans' ? 'selected' : ''; ?>>Yüksek Lisans</option>
                                                <option value="Doktora" <?php echo $duzenlenecek_terapist['mezuniyet'] == 'Doktora' ? 'selected' : ''; ?>>Doktora</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Rol</label>
                                            <select name="rol" class="form-select" required>
                                                <option value="terapist" <?php echo $duzenlenecek_terapist['rol'] == 'terapist' ? 'selected' : ''; ?>>Terapist</option>
                                                <option value="yonetici" <?php echo $duzenlenecek_terapist['rol'] == 'yonetici' ? 'selected' : ''; ?>>Yönetici</option>
                                                <option value="satis" <?php echo $duzenlenecek_terapist['rol'] == 'satis' ? 'selected' : ''; ?>>Satış</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save"></i> Güncelle
                                </button>
                                <a href="?page=terapistler" class="btn btn-secondary">
                                    <i class="bx bx-x"></i> İptal
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Şifre Değiştir -->
                    <div class="tab-pane fade" id="sifre">
                        <form method="POST" action="">
                            <input type="hidden" name="islem" value="sifre_degistir">
                            <input type="hidden" name="id" value="<?php echo $duzenlenecek_terapist['id']; ?>">

                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mevcut Şifre</label>
                                        <input type="password" name="eski_sifre" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Yeni Şifre</label>
                                        <input type="password" name="yeni_sifre" class="form-control" required minlength="6">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                                        <input type="password" name="yeni_sifre_tekrar" class="form-control" required minlength="6">
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-lock"></i> Şifreyi Değiştir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
