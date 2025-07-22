<?php

session_start();
require_once 'functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $sql = "INSERT INTO kriterler (kriter_adi, aciklama, sira) 
                           VALUES (:kriter_adi, :aciklama, :sira)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':kriter_adi' => $_POST['kriter_adi'],
                        ':aciklama' => $_POST['aciklama'],
                        ':sira' => $_POST['sira']
                    ]);
                    $mesaj = "Kriter başarıyla eklendi.";
                    break;

                case 'edit':
                    $sql = "UPDATE kriterler 
                           SET kriter_adi = :kriter_adi, 
                               aciklama = :aciklama, 
                               sira = :sira,
                               aktif = :aktif
                           WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':id' => $_POST['id'],
                        ':kriter_adi' => $_POST['kriter_adi'],
                        ':aciklama' => $_POST['aciklama'],
                        ':sira' => $_POST['sira'],
                        ':aktif' => isset($_POST['aktif']) ? 1 : 0
                    ]);
                    $mesaj = "Kriter başarıyla güncellendi.";
                    break;

                case 'delete':
                    // First check if criteria is used in any evaluation
                    $check_sql = "SELECT COUNT(*) FROM degerlendirme_kriterleri WHERE kriter_id = :id";
                    $check_stmt = $pdo->prepare($check_sql);
                    $check_stmt->execute([':id' => $_POST['id']]);
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        // Just deactivate if used
                        $sql = "UPDATE kriterler SET aktif = 0 WHERE id = :id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':id' => $_POST['id']]);
                        $mesaj = "Kriter pasif duruma alındı.";
                    } else {
                        // Delete if never used
                        $sql = "DELETE FROM kriterler WHERE id = :id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':id' => $_POST['id']]);
                        $mesaj = "Kriter başarıyla silindi.";
                    }
                    break;
            }
        } catch(PDOException $e) {
            error_log("Kriter işlem hatası: " . $e->getMessage());
            $hata = "İşlem başarısız oldu.";
        }
    }
}

// Get criteria list
try {
    $sql = "SELECT * FROM kriterler ORDER BY sira, kriter_adi";
    $kriterler = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Kriter listesi getirme hatası: " . $e->getMessage());
    $kriterler = [];
}
?>



<!DOCTYPE html>
<html lang="tr">

<head>
    <?php
    $title = "Satışlar Listele";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>

        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Finans";
                $title = "Satışlar";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Değerlendirme Kriterleri</h2>
            <div class="d-flex gap-2">
                <a href="degerlendirme_liste.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Geri Dön
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg"></i> Yeni Kriter
                </button>
            </div>
        </div>

        <?php if (isset($mesaj)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $mesaj ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($hata)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $hata ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Kriter</th>
                                <th>Açıklama</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kriterler as $k): ?>
                                <tr>
                                    <td><?= $k['sira'] ?></td>
                                    <td><?= htmlspecialchars($k['kriter_adi']) ?></td>
                                    <td><?= htmlspecialchars($k['aciklama']) ?></td>
                                    <td>
                                        <span class="badge <?= $k['aktif'] ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $k['aktif'] ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info"
                                                    onclick="editCriteria(<?= htmlspecialchars(json_encode($k)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="deleteCriteria(<?= $k['id'] ?>)">
                                                <i class="bi bi-trash"></i>
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
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Kriter Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kriter Adı</label>
                            <input type="text" name="kriter_adi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sıra</label>
                            <input type="number" name="sira" class="form-control" required>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title">Kriter Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kriter Adı</label>
                            <input type="text" name="kriter_adi" id="editKriterAdi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" id="editAciklama" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sıra</label>
                            <input type="number" name="sira" id="editSira" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="aktif" id="editAktif" class="form-check-input">
                                <label class="form-check-label">Aktif</label>
                            </div>
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

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <div class="modal-header">
                        <h5 class="modal-title">Kriter Sil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bu kriteri silmek istediğinizden emin misiniz?</p>
                        <p class="text-muted small">Not: Eğer bu kriter herhangi bir değerlendirmede kullanıldıysa silinmeyecek, pasif duruma alınacaktır.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger">Sil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script>
    function editCriteria(criteria) {
        document.getElementById('editId').value = criteria.id;
        document.getElementById('editKriterAdi').value = criteria.kriter_adi;
        document.getElementById('editAciklama').value = criteria.aciklama;
        document.getElementById('editSira').value = criteria.sira;
        document.getElementById('editAktif').checked = criteria.aktif == 1;
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function deleteCriteria(id) {
        document.getElementById('deleteId').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
                            

                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/customizer.php' ?>

    <?php include 'partials/footer-scripts.php' ?>


</body>

</html>