<?php
$action = $_GET['action'] ?? 'list';
$hata = $_SESSION['hata'] ?? '';
$basari = $_SESSION['basari'] ?? '';

unset($_SESSION['hata'], $_SESSION['basari']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'seans_turu_ekle':
                try {
                    $sql = "INSERT INTO seans_turleri (ad, sure, fiyat, deneme_mi, aciklama, seans_adet) 
                            VALUES (:ad, :sure, :fiyat, :deneme_mi, :aciklama, :seans_adet)";
                    $stmt = $pdo->prepare($sql);
                    $sonuc = $stmt->execute([
                        'ad' => $_POST['ad'],
                        'sure' => $_POST['sure'],
                        'fiyat' => $_POST['fiyat'],
                        'deneme_mi' => isset($_POST['deneme_mi']) ? 1 : 0,
                        'aciklama' => $_POST['aciklama'],
                        'seans_adet' => $_POST['seans_adet']
                    ]);
                    
                    if ($sonuc) {
                        $_SESSION['basari'] = "Seans türü başarıyla eklendi.";
                        header("Location: ?page=seans_turleri");
                        exit;
                    }
                } catch(PDOException $e) {
                    $_SESSION['hata'] = "Seans türü eklenirken bir hata oluştu: " . $e->getMessage();
                }
                break;

            case 'seans_turu_guncelle':
                try {
                    $sql = "UPDATE seans_turleri 
                            SET ad = :ad, sure = :sure, fiyat = :fiyat, 
                                deneme_mi = :deneme_mi, aciklama = :aciklama,
                                seans_adet = :seans_adet 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $sonuc = $stmt->execute([
                        'id' => $_POST['id'],
                        'ad' => $_POST['ad'],
                        'sure' => $_POST['sure'],
                        'fiyat' => $_POST['fiyat'],
                        'deneme_mi' => isset($_POST['deneme_mi']) ? 1 : 0,
                        'aciklama' => $_POST['aciklama'],
                        'seans_adet' => $_POST['seans_adet']
                    ]);
                    
                    if ($sonuc) {
                        $_SESSION['basari'] = "Seans türü başarıyla güncellendi.";
                        header("Location: ?page=seans_turleri");
                        exit;
                    }
                } catch(PDOException $e) {
                    $_SESSION['hata'] = "Seans türü güncellenirken bir hata oluştu: " . $e->getMessage();
                }
                break;
        }
    }
}

// Seans türlerini getir
$sql = "SELECT * FROM seans_turleri ORDER BY ad";
$stmt = $pdo->query($sql);
$seans_turleri = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Düzenleme için seans türü bilgilerini al
$duzenlenecek_seans = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $sql = "SELECT * FROM seans_turleri WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $duzenlenecek_seans = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Silme işlemi
if ($action == 'delete' && isset($_GET['id'])) {
    try {
        $check_sql = "SELECT COUNT(*) as count FROM randevular WHERE seans_turu_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$_GET['id']]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $_SESSION['hata'] = "Bu seans türü randevularda kullanıldığı için silinemiyor. Silmek yerine pasife almayı deneyebilirsiniz.";
        } else {
            $sql = "DELETE FROM seans_turleri WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $sonuc = $stmt->execute([$_GET['id']]);
            
            if ($sonuc) {
                $_SESSION['basari'] = "Seans türü başarıyla silindi.";
            } else {
                $_SESSION['hata'] = "Seans türü silinirken bir hata oluştu.";
            }
        }
    } catch(PDOException $e) {
        if ($e->getCode() == '23000') {
            $_SESSION['hata'] = "Bu seans türü sistemde kullanıldığı için silinemiyor. Silmek yerine pasife almayı deneyebilirsiniz.";
        } else {
            $_SESSION['hata'] = "Seans türü silinirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
        error_log("Seans türü silme hatası: " . $e->getMessage());
    }
    header("Location: ?page=seans_turleri");
    exit;
}

// Aktif/Pasif yapma işlemi
if (($action == 'activate' || $action == 'deactivate') && isset($_GET['id'])) {
    try {
        $aktif = $action == 'activate' ? 1 : 0;
        $sql = "UPDATE seans_turleri SET aktif = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $sonuc = $stmt->execute([$aktif, $_GET['id']]);
        
        if ($sonuc) {
            $_SESSION['basari'] = $aktif ? "Seans türü aktif hale getirildi." : "Seans türü pasif hale getirildi.";
        } else {
            $_SESSION['hata'] = "Durum güncellenirken bir hata oluştu.";
        }
    } catch(PDOException $e) {
        $_SESSION['hata'] = "Durum güncellenirken bir hata oluştu: " . $e->getMessage();
    }
    header("Location: ?page=seans_turleri");
    exit;
}
?>

<div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class='bx bx-error me-2'></i>
            <?php echo $hata; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($basari): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class='bx bx-check me-2'></i>
            <?php echo $basari; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Seans Türleri</h5>
                <a href="?page=seans_turleri&action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Seans Türü
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad</th>
                                <th>Süre (dk)</th>
                                <th>Seans Adeti</th>
                                <th>Fiyat</th>
                                <th>Deneme Seansı</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seans_turleri as $seans): ?>
                            <tr>
                                <td><?php echo $seans['ad']; ?></td>
                                <td><?php echo $seans['sure']; ?></td>
                                <td><?php echo $seans['seans_adet']; ?></td>
                                <td><?php echo number_format($seans['fiyat'], 2, ',', '.') . ' ₺'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $seans['deneme_mi'] ? 'info' : 'secondary'; ?>">
                                        <?php echo $seans['deneme_mi'] ? 'Evet' : 'Hayır'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $seans['aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $seans['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?page=seans_turleri&action=edit&id=<?php echo $seans['id']; ?>" 
                                           class="btn btn-primary" title="Düzenle">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <?php if ($seans['aktif']): ?>
                                            <a href="?page=seans_turleri&action=deactivate&id=<?php echo $seans['id']; ?>" 
                                               class="btn btn-warning" title="Pasife Al"
                                               onclick="return confirm('Bu seans türünü pasife almak istediğinizden emin misiniz?')">
                                                <i class="bx bx-power-off"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?page=seans_turleri&action=activate&id=<?php echo $seans['id']; ?>" 
                                               class="btn btn-success" title="Aktife Al"
                                               onclick="return confirm('Bu seans türünü aktife almak istediğinizden emin misiniz?')">
                                                <i class="bx bx-power-off"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?page=seans_turleri&action=delete&id=<?php echo $seans['id']; ?>" 
                                           class="btn btn-danger" title="Sil"
                                           onclick="return confirm('Bu seans türünü silmek istediğinizden emin misiniz?')">
                                            <i class="bx bx-trash"></i>
                                        </a>
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
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Seans Türü Düzenle' : 'Yeni Seans Türü'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem" 
                           value="<?php echo $action == 'edit' ? 'seans_turu_guncelle' : 'seans_turu_ekle'; ?>">
                    
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_seans['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_seans['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Süre (dakika)</label>
                            <input type="number" name="sure" class="form-control" required min="1"
                                   value="<?php echo $duzenlenecek_seans['sure'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Adeti</label>
                            <input type="number" name="seans_adet" class="form-control" required min="0"
                                   value="<?php echo $duzenlenecek_seans['seans_adet'] ?? '0'; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fiyat (₺)</label>
                            <input type="number" name="fiyat" class="form-control" required min="0" step="0.01"
                                   value="<?php echo $duzenlenecek_seans['fiyat'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="deneme_mi" class="form-check-input" id="deneme_mi"
                                       <?php echo ($duzenlenecek_seans['deneme_mi'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="deneme_mi">Deneme Seansı</label>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"><?php echo $duzenlenecek_seans['aciklama'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=seans_turleri" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
