<?php
session_start();
require_once 'functions.php';

$hata = '';
$basari = '';

// Filtreler
$tarih_baslangic = $_GET['tarih_baslangic'] ?? null;
$tarih_bitis = $_GET['tarih_bitis'] ?? null;
$kategori_id = $_GET['kategori_id'] ?? null;
$harcama_turu_id = $_GET['harcama_turu_id'] ?? null;
$durum = $_GET['durum'] ?? null;

// Dropdown verileri
$gider_kategorileri = getGiderKategorileri();
$harcama_turleri = getHarcamaTurleri();

// Giderleri getir
$giderler = getGiderler($tarih_baslangic, $tarih_bitis, $kategori_id, $harcama_turu_id, $durum);

// Toplamları hesapla
$toplam_tutar = array_sum(array_column($giderler, 'tutar'));
$toplam_odenen = array_sum(array_column($giderler, 'odenen_tutar'));
$toplam_kalan = $toplam_tutar - $toplam_odenen;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Gider Listesi";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
                $subtitle = "Muhasebe";
                $title = "Gider Listesi";
                include "partials/page-title.php";
                ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list text-danger me-2"></i>
                                    Gider Kayıtları
                                </h5>
                                <a href="gider-ekle.php" class="btn btn-danger">
                                    <i class="fas fa-plus me-2"></i>Yeni Gider
                                </a>
                            </div>
                            <div class="card-body">

                                <!-- Filtreler -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Başlangıç Tarihi</label>
                                                <input type="date" name="tarih_baslangic" class="form-control" 
                                                       value="<?= htmlspecialchars($tarih_baslangic ?? '') ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Bitiş Tarihi</label>
                                                <input type="date" name="tarih_bitis" class="form-control" 
                                                       value="<?= htmlspecialchars($tarih_bitis ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Kategori</label>
                                                <select name="kategori_id" class="form-select">
                                                    <option value="">Tümü</option>
                                                    <?php foreach($gider_kategorileri as $kategori): ?>
                                                        <option value="<?= $kategori['id'] ?>" 
                                                                <?= $kategori_id == $kategori['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($kategori['ad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Harcama Türü</label>
                                                <select name="harcama_turu_id" class="form-select">
                                                    <option value="">Tümü</option>
                                                    <?php foreach($harcama_turleri as $tur): ?>
                                                        <option value="<?= $tur['id'] ?>" 
                                                                <?= $harcama_turu_id == $tur['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tur['ad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="fas fa-search"></i> Filtrele
                                                </button>
                                                <a href="gider-listesi.php" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Özet Kartları -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card bg-danger text-white">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Toplam Gider</h5>
                                                <h3><?= number_format($toplam_tutar, 2) ?> ₺</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Ödenen</h5>
                                                <h3><?= number_format($toplam_odenen, 2) ?> ₺</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Kalan</h5>
                                                <h3><?= number_format($toplam_kalan, 2) ?> ₺</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gider Tablosu -->
                                <div class="table-responsive">
                                    <table id="giderTable" class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Kategori</th>
                                                <th>Açıklama</th>
                                                <th>Tutar</th>
                                                <th>Harcama Türü</th>
                                                <th>Durum</th>
                                                <th>Ödenen</th>
                                                <th>Kalan</th>
                                                <th>Kayıt Yapan</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($giderler as $gider): ?>
                                                <tr>
                                                    <td><?= date('d.m.Y', strtotime($gider['tarih'])) ?></td>
                                                    <td><?= htmlspecialchars($gider['kategori_adi']) ?></td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;" 
                                                             title="<?= htmlspecialchars($gider['aciklama']) ?>">
                                                            <?= htmlspecialchars($gider['aciklama']) ?>
                                                        </div>
                                                        <?php if($gider['fatura_no']): ?>
                                                            <small class="text-muted">Fatura: <?= htmlspecialchars($gider['fatura_no']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        <?= number_format($gider['tutar'], 2) ?> ₺
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $gider['harcama_turu_adi'] == 'İşletme' ? 'primary' : 'info' ?>">
                                                            <?= htmlspecialchars($gider['harcama_turu_adi']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badge_class = '';
                                                        $durum_text = '';
                                                        switch($gider['durum']) {
                                                            case 'odendi':
                                                                $badge_class = 'success';
                                                                $durum_text = 'Ödendi';
                                                                break;
                                                            case 'kismi_odendi':
                                                                $badge_class = 'warning';
                                                                $durum_text = 'Kısmi Ödendi';
                                                                break;
                                                            default:
                                                                $badge_class = 'danger';
                                                                $durum_text = 'Beklemede';
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?= $badge_class ?>"><?= $durum_text ?></span>
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        <?= number_format($gider['odenen_tutar'] ?? 0, 2) ?> ₺
                                                    </td>
                                                    <td class="text-end text-danger fw-bold">
                                                        <?= number_format($gider['odenmemis_kalan'], 2) ?> ₺
                                                    </td>
                                                    <td><?= htmlspecialchars($gider['kayit_yapan_adi'] ?? 'Sistem') ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="gider-detay.php?id=<?= $gider['id'] ?>" 
                                                               class="btn btn-sm btn-outline-primary" title="Detay">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="gider-duzenle.php?id=<?= $gider['id'] ?>" 
                                                               class="btn btn-sm btn-outline-secondary" title="Düzenle">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if($gider['odenmemis_kalan'] > 0): ?>
                                                                <button class="btn btn-sm btn-outline-success odeme-ekle-btn" 
                                                                        data-gider-id="<?= $gider['id'] ?>"
                                                                        data-kalan="<?= $gider['odenmemis_kalan'] ?>" 
                                                                        title="Ödeme Ekle">
                                                                    <i class="fas fa-credit-card"></i>
                                                                </button>
                                                            <?php endif; ?>
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
                </div>
            </div>
        </div>

        <!-- Ödeme Ekleme Modal -->
        <div class="modal fade" id="odemeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="odemeForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Ödeme Ekle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="gider_id" name="gider_id">
                            
                            <div class="mb-3">
                                <label class="form-label">Ödeme Tarihi</label>
                                <input type="date" name="odeme_tarihi" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tutar (₺)</label>
                                <input type="number" name="tutar" class="form-control" 
                                       step="0.01" min="0.01" required>
                                <div class="form-text">Kalan tutar: <span id="kalan_tutar"></span> ₺</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ödeme Yöntemi</label>
                                <select name="odeme_yontemi" class="form-select" required>
                                    <option value="nakit">Nakit</option>
                                    <option value="havale">Havale</option>
                                    <option value="kredi_karti">Kredi Kartı</option>
                                    <option value="cek">Çek</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="2" 
                                          placeholder="Ödeme açıklaması..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-success">Ödeme Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
        <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

        <script>
            $(document).ready(function() {
                // DataTable başlat
                $('#giderTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/tr.json"
                    },
                    "order": [[ 0, "desc" ]],
                    "pageLength": 25,
                    "columnDefs": [
                        { "orderable": false, "targets": [9] }
                    ]
                });

                // Ödeme ekleme modal
                $('.odeme-ekle-btn').click(function() {
                    const giderId = $(this).data('gider-id');
                    const kalanTutar = $(this).data('kalan');
                    
                    $('#gider_id').val(giderId);
                    $('#kalan_tutar').text(kalanTutar);
                    $('input[name="tutar"]').attr('max', kalanTutar).val(kalanTutar);
                    
                    $('#odemeModal').modal('show');
                });

                // Ödeme formu gönderme
                $('#odemeForm').submit(function(e) {
                    e.preventDefault();
                    
                    const formData = $(this).serialize();
                    
                    $.ajax({
                        url: 'ajax/gider-odeme-ekle.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert('Ödeme başarıyla kaydedildi!');
                                location.reload();
                            } else {
                                alert('Hata: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Bir hata oluştu!');
                        }
                    });
                });
            });
        </script>
    </div>
</body>
</html>