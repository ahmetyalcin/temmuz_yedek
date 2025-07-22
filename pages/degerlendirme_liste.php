<?php



// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil'])) {
    try {
        $sql = "DELETE FROM personel_degerlendirme WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $_POST['id']]);
        
        header("Location: ?page=degerlendirme_liste&mesaj=" . urlencode("Değerlendirme başarıyla silindi."));
        exit;
    } catch(PDOException $e) {
        error_log("Değerlendirme silme hatası: " . $e->getMessage());
        $hata = "Değerlendirme silinemedi.";
    }
}

$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');

// Get all active personnel with pagination
$page = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM personel WHERE aktif = 1 AND rol = 'terapist'";
    $total = $pdo->query($count_sql)->fetchColumn();
    $total_pages = ceil($total / $limit);
    
    // Get personnel list
    $sql = "SELECT p.*, CONCAT(p.ad, ' ', p.soyad) as ad_soyad,
            (SELECT COUNT(*) FROM personel_degerlendirme pd 
             WHERE pd.personel_id = p.id AND pd.ay = :ay AND pd.yil = :yil) as degerlendirildi
            FROM personel p
            WHERE p.aktif = 1 AND p.rol = 'terapist'
            ORDER BY p.ad, p.soyad
            LIMIT :limit OFFSET :offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':ay', $ay, PDO::PARAM_INT);
    $stmt->bindValue(':yil', $yil, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $personeller = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Personel listesi getirme hatası: " . $e->getMessage());
    $personeller = [];
}

// Get evaluations with criteria scores
try {
    $sql = "SELECT d.*, 
            CONCAT(p.ad, ' ', p.soyad) as personel_adi,
            p.sicil_no,
            k.kriter_adi,
            dk.puan
            FROM personel_degerlendirme d
            JOIN personel p ON p.id = d.personel_id
            JOIN degerlendirme_kriterleri dk ON dk.degerlendirme_id = d.id
            JOIN kriterler k ON k.id = dk.kriter_id
            WHERE d.ay = :ay AND d.yil = :yil
            ORDER BY d.olusturma_tarihi DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ay' => $ay, ':yil' => $yil]);
    $degerlendirmeler = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Değerlendirme listesi getirme hatası: " . $e->getMessage());
    $degerlendirmeler = [];
}

// Get evaluation criteria
try {
    $sql = "SELECT * FROM kriterler WHERE aktif = 1 ORDER BY sira";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $kriterler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Kriter listesi getirme hatası: " . $e->getMessage());
    $kriterler = [];
}

// Get average scores
try {
    $sql = "SELECT 
                p.id,
                CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                COUNT(DISTINCT d.id) as degerlendirme_sayisi,
                AVG(d.c_skoru) as ortalama_c_skoru,
                k.kriter_adi,
                AVG(dk.puan) as ortalama_kriter_puani
            FROM personel p
            LEFT JOIN personel_degerlendirme d ON d.personel_id = p.id
            LEFT JOIN degerlendirme_kriterleri dk ON dk.degerlendirme_id = d.id
            LEFT JOIN kriterler k ON k.id = dk.kriter_id
            WHERE p.aktif = 1 AND p.rol = 'terapist'
            GROUP BY p.id, p.ad, p.soyad, k.kriter_adi
            ORDER BY ortalama_c_skoru DESC NULLS LAST";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ortalama_skorlar = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Ortalama skor hesaplama hatası: " . $e->getMessage());
    $ortalama_skorlar = [];
}

function getTurkishMonthName($month) {
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    return $months[(int)$month];
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $personeller = array_filter($personeller, function($p) use ($search) {
        return stripos($p['ad_soyad'], $search) !== false || 
               stripos($p['sicil_no'], $search) !== false;
    });
}
?>


    <style>
        .search-box {
            position: relative;
        }
        .search-box .clear-search {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .personnel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        .personnel-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .personnel-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .personnel-card.evaluated {
            background-color: #f8f9fa;
            border-color: #6c757d;
        }
        .score-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        .criteria-scores {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .criteria-score {
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            background-color: #e9ecef;
        }
        .pagination-container {
            margin-top: 2rem;
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Personel Değerlendirme Sistemi</h2>
            <div class="d-flex gap-2">
                <a href="?page=kriterler" class="btn btn-outline-primary">
                    <i class="bi bi-list-check"></i> Kriterler
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['mesaj'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['mesaj']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="degerlendirme_liste">
                    
                    <div class="col-md-3">
                        <label class="form-label">Personel Ara</label>
                        <div class="search-box">
                            <input type="text" name="search" class="form-control" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Ad, soyad veya sicil no...">
                            <?php if ($search): ?>
                                <span class="clear-search" onclick="clearSearch()">×</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Ay</label>
                        <select name="ay" class="form-select">
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" 
                                        <?= $ay == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                                    <?= getTurkishMonthName($i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Yıl</label>
                        <select name="yil" class="form-select">
                            <?php for($i = date('Y')-1; $i <= date('Y')+1; $i++): ?>
                                <option value="<?= $i ?>" <?= $yil == $i ? 'selected' : '' ?>>
                                    <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrele
                        </button>
                        <?php if ($search || $ay != date('m') || $yil != date('Y')): ?>
                            <a href="?page=degerlendirme_liste" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Temizle
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Personnel Grid -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="personnel-grid">
                    <?php foreach ($personeller as $p): ?>
                        <div class="personnel-card <?= $p['degerlendirildi'] ? 'evaluated' : '' ?>"
                             onclick="selectPersonnel(<?= $p['id'] ?>, '<?= $p['degerlendirildi'] ?>')">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($p['ad_soyad']) ?></h5>
                                    <div class="text-muted small">
                                        Sicil No: <?= htmlspecialchars($p['sicil_no']) ?>
                                    </div>
                                </div>
                                <?php if ($p['degerlendirildi']): ?>
                                    <span class="badge bg-success">Değerlendirildi</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($ortalama_skorlar[$p['id']])): ?>
                                <div class="criteria-scores">
                                    <?php foreach ($ortalama_skorlar[$p['id']] as $skor): ?>
                                        <?php if ($skor['kriter_adi']): ?>
                                            <span class="criteria-score" title="<?= $skor['kriter_adi'] ?>">
                                                <?= number_format($skor['ortalama_kriter_puani'], 1) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=degerlendirme_liste&sayfa=<?= $page-1 ?>&ay=<?= $ay ?>&yil=<?= $yil ?>&search=<?= urlencode($search) ?>">
                                        Önceki
                                    </a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=degerlendirme_liste&sayfa=<?= $i ?>&ay=<?= $ay ?>&yil=<?= $yil ?>&search=<?= urlencode($search) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=degerlendirme_liste&sayfa=<?= $page+1 ?>&ay=<?= $ay ?>&yil=<?= $yil ?>&search=<?= urlencode($search) ?>">
                                        Sonraki
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation History -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Değerlendirme Geçmişi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="min-width: 200px">Personel</th>
                                <th scope="col" style="min-width: 120px">Sicil No</th>
                                <th scope="col" style="min-width: 150px">Tarih</th>
                                <?php foreach ($kriterler as $k): ?>
                                    <th scope="col" class="text-center" style="min-width: 120px">
                                        <?= htmlspecialchars($k['kriter_adi']) ?>
                                    </th>
                                <?php endforeach; ?>
                                <th scope="col" class="text-center" style="min-width: 100px">C Skoru</th>
                                <th scope="col" class="text-center" style="min-width: 120px">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($degerlendirmeler)): ?>
                                <tr>
                                    <td colspan="<?= count($kriterler) + 5 ?>" class="text-center py-4">
                                        Henüz değerlendirme yapılmamış.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($degerlendirmeler as $degerlendirme_id => $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d[0]['personel_adi']) ?></td>
                                        <td><?= htmlspecialchars($d[0]['sicil_no']) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($d[0]['olusturma_tarihi'])) ?></td>
                                        <?php foreach ($kriterler as $k): ?>
                                            <td class="text-center">
                                                <?php
                                                $kriter_puan = '-';
                                                foreach ($d as $dk) {
                                                    if ($dk['kriter_adi'] == $k['kriter_adi']) {
                                                        $kriter_puan = $dk['puan'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <span class="badge bg-light text-dark">
                                                    <?= $kriter_puan ?>
                                                </span>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-center">
                                            <span class="badge bg-primary">
                                                <?= number_format($d[0]['c_skoru'], 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmDelete(<?= $degerlendirme_id ?>)"
                                                        title="Sil">
                                                    Sil
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Silme Onayı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Bu değerlendirmeyi silmek istediğinizden emin misiniz?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="sil" class="btn btn-danger">Sil</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function selectPersonnel(id, evaluated) {
        if (evaluated == '1') {
            alert('Bu personel için bu ay değerlendirme yapılmış.');
            return;
        }
        window.location.href = `?page=degerlendirme_form&personel_id=${id}&ay=<?= $ay ?>&yil=<?= $yil ?>`;
    }

    function confirmDelete(id) {
        document.getElementById('deleteId').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function clearSearch() {
        window.location.href = '?page=degerlendirme_liste&ay=<?= $ay ?>&yil=<?= $yil ?>';
    }
    </script>
