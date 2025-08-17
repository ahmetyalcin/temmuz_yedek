<?php
// personel-aylik-rapor.php - Personel Aylık Çalışma Raporu
session_start();
require_once 'con/db.php';
require_once 'functions.php';

// Yetki kontrolü
if (!in_array($_SESSION['rol'], ['yonetici', 'ik', 'sekreter'])) {
    header('Location: unauthorized.php');
    exit;
}

$title = 'Personel Aylık Rapor';

// Filtreleme parametreleri
$ay = $_GET['ay'] ?? date('m');
$yil = $_GET['yil'] ?? date('Y');
$personel_id = $_GET['personel_id'] ?? '';
$departman = $_GET['departman'] ?? '';

// Verileri getir
$personeller = getAktifPersoneller();
$aylik_rapor = getAylikPersonelRaporu($ay, $yil, $personel_id, $departman);
$ay_istatistikleri = getAyIstatistikleri($ay, $yil);
$departmanlar = getDepartmanlar();

include 'partials/header.php';
?>

<style>
.report-card {
    border-radius: 15px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
}

.performance-bar {
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
    overflow: hidden;
    margin-top: 8px;
}

.performance-progress {
    height: 100%;
    transition: width 0.3s ease;
}

.progress-excellent { background: linear-gradient(90deg, #28a745 0%, #20c997 100%); }
.progress-good { background: linear-gradient(90deg, #17a2b8 0%, #007bff 100%); }
.progress-average { background: linear-gradient(90deg, #ffc107 0%, #fd7e14 100%); }
.progress-poor { background: linear-gradient(90deg, #dc3545 0%, #e83e8c 100%); }

.attendance-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-excellent { background: #d4edda; color: #155724; }
.status-good { background: #d1ecf1; color: #0c5460; }
.status-average { background: #fff3cd; color: #856404; }
.status-poor { background: #f8d7da; color: #721c24; }

.monthly-chart {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.personel-avatar-lg {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.working-hours-display {
    font-family: 'Courier New', monospace;
    font-size: 1.1em;
    font-weight: bold;
    color: #2c3e50;
}

.table-modern {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.table-modern thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
</style>

<div class="page-content">
    <div class="container-fluid">
        
        <!-- Sayfa Başlığı -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 font-size-18">
                        <i class="fas fa-chart-bar me-2"></i>Personel Aylık Rapor
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="personel-giris-cikis.php">Giriş-Çıkış</a></li>
                            <li class="breadcrumb-item active">Aylık Rapor</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aylık İstatistikler -->
        <div class="row">
            <div class="col-12">
                <div class="stats-card">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h3 class="mb-1"><?= $ay_istatistikleri['toplam_personel'] ?></h3>
                            <p class="mb-0 opacity-75">Toplam Personel</p>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1"><?= number_format($ay_istatistikleri['ortalama_calisma_saati'], 1) ?></h3>
                            <p class="mb-0 opacity-75">Ortalama Çalışma Saati</p>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1"><?= number_format($ay_istatistikleri['toplam_mesai_saati'], 1) ?></h3>
                            <p class="mb-0 opacity-75">Toplam Mesai Saati</p>
                        </div>
                        <div class="col-md-3">
                            <h3 class="mb-1">%<?= number_format($ay_istatistikleri['devam_orani'], 1) ?></h3>
                            <p class="mb-0 opacity-75">Ortalama Devam Oranı</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="row">
            <div class="col-12">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Rapor Filtreleri
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filterForm">
                            <div class="row align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label">Yıl</label>
                                    <select name="yil" class="form-select">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?= $y ?>" <?= $yil == $y ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Ay</label>
                                    <select name="ay" class="form-select">
                                        <?php
                                        $aylar = [
                                            '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                                            '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                                            '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
                                        ];
                                        foreach ($aylar as $key => $value):
                                        ?>
                                            <option value="<?= $key ?>" <?= $ay == $key ? 'selected' : '' ?>><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Personel</label>
                                    <select name="personel_id" class="form-select">
                                        <option value="">Tüm Personel</option>
                                        <?php foreach ($personeller as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $personel_id == $p['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['ad'] . ' ' . $p['soyad']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Departman</label>
                                    <select name="departman" class="form-select">
                                        <option value="">Tüm Departmanlar</option>
                                        <option value="terapist" <?= $departman == 'terapist' ? 'selected' : '' ?>>Terapist</option>
                                        <option value="yonetici" <?= $departman == 'yonetici' ? 'selected' : '' ?>>Yönetici</option>
                                        <option value="sekreter" <?= $departman == 'sekreter' ? 'selected' : '' ?>>Sekreter</option>
                                        <option value="satis" <?= $departman == 'satis' ? 'selected' : '' ?>>Satış</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-1"></i>Filtrele
                                    </button>
                                    <button type="button" class="btn btn-success me-2" onclick="exportExcel()">
                                        <i class="fas fa-file-excel me-1"></i>Excel
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="exportPDF()">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aylık Rapor Tablosu -->
        <div class="row">
            <div class="col-12">
                <div class="card table-modern">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <?= $aylar[$ay] ?> <?= $yil ?> Aylık Rapor
                                </h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    Toplam <?= count($aylik_rapor) ?> personel
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="aylikRaporTable">
                                <thead>
                                    <tr>
                                        <th>Personel</th>
                                        <th>Çalışılan Gün</th>
                                        <th>Toplam Saat</th>
                                        <th>Ortalama/Gün</th>
                                        <th>Mesai Saati</th>
                                        <th>Gecikme</th>
                                        <th>Devamsızlık</th>
                                        <th>Performans</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($aylik_rapor)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Bu ay için rapor bulunamadı</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($aylik_rapor as $rapor): ?>
                                            <?php
                                            // Performans hesaplama
                                            $expected_days = cal_days_in_month(CAL_GREGORIAN, $ay, $yil);
                                            $working_days = $expected_days - 8; // Hafta sonları çıkarılır (yaklaşık)
                                            $performance_percentage = ($rapor['calisilan_gun'] / $working_days) * 100;
                                            
                                            $performance_class = 'poor';
                                            $performance_text = 'Zayıf';
                                            if ($performance_percentage >= 95) {
                                                $performance_class = 'excellent';
                                                $performance_text = 'Mükemmel';
                                            } elseif ($performance_percentage >= 85) {
                                                $performance_class = 'good';
                                                $performance_text = 'İyi';
                                            } elseif ($performance_percentage >= 75) {
                                                $performance_class = 'average';
                                                $performance_text = 'Orta';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= $rapor['avatar'] ?: 'assets/images/default-avatar.png' ?>" 
                                                             class="personel-avatar-lg me-3" alt="Avatar">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($rapor['personel_adi']) ?></h6>
                                                            <small class="text-muted">
                                                                <?= $rapor['sicil_no'] ?> | <?= ucfirst($rapor['rol']) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="working-hours-display"><?= $rapor['calisilan_gun'] ?></span>
                                                    <small class="text-muted d-block"><?= $working_days ?> gün beklenen</small>
                                                </td>
                                                <td>
                                                    <span class="working-hours-display"><?= number_format($rapor['toplam_saat'], 1) ?></span>
                                                    <small class="text-muted d-block">saat</small>
                                                </td>
                                                <td>
                                                    <span class="working-hours-display"><?= number_format($rapor['ortalama_saat'], 1) ?></span>
                                                    <small class="text-muted d-block">saat/gün</small>
                                                </td>
                                                <td>
                                                    <span class="working-hours-display text-primary"><?= number_format($rapor['toplam_mesai'], 1) ?></span>
                                                    <small class="text-muted d-block">mesai saati</small>
                                                </td>
                                                <td>
                                                    <?php if ($rapor['gecikme_sayisi'] > 0): ?>
                                                        <span class="badge bg-warning"><?= $rapor['gecikme_sayisi'] ?> gün</span>
                                                        <small class="text-muted d-block"><?= $rapor['toplam_gecikme'] ?> dk</small>
                                                    <?php else: ?>
                                                        <span class="text-success">Yok</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($rapor['devamsizlik_sayisi'] > 0): ?>
                                                        <span class="badge bg-danger"><?= $rapor['devamsizlik_sayisi'] ?> gün</span>
                                                    <?php else: ?>
                                                        <span class="text-success">Yok</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="attendance-status status-<?= $performance_class ?>">
                                                        <?= $performance_text ?>
                                                    </span>
                                                    <div class="performance-bar">
                                                        <div class="performance-progress progress-<?= $performance_class ?>" 
                                                             style="width: <?= min(100, $performance_percentage) ?>%"></div>
                                                    </div>
                                                    <small class="text-muted">%<?= number_format($performance_percentage, 1) ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                onclick="detayliRapor('<?= $rapor['personel_id'] ?>', '<?= $ay ?>', '<?= $yil ?>')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-success" 
                                                                onclick="gunlukDetay('<?= $rapor['personel_id'] ?>', '<?= $ay ?>', '<?= $yil ?>')">
                                                            <i class="fas fa-calendar-day"></i>
                                                        </button>
                                                        <button class="btn btn-outline-info" 
                                                                onclick="mesajGonder('<?= $rapor['personel_id'] ?>')">
                                                            <i class="fas fa-sms"></i>
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
        </div>

        <!-- Performans Analizi -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Performans Dağılımı
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Aylık Trend
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Özet Kartlar -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card report-card text-center">
                    <div class="card-body">
                        <i class="fas fa-trophy fa-2x text-warning mb-3"></i>
                        <h4><?= getEnIyiPersonel($aylik_rapor) ?></h4>
                        <p class="text-muted mb-0">En İyi Performans</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card report-card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                        <h4><?= number_format($ay_istatistikleri['en_fazla_mesai'], 1) ?>h</h4>
                        <p class="text-muted mb-0">En Fazla Mesai</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card report-card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-check fa-2x text-success mb-3"></i>
                        <h4><?= $ay_istatistikleri['tam_devam_eden'] ?></h4>
                        <p class="text-muted mb-0">Tam Devam Eden</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card report-card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                        <h4><?= $ay_istatistikleri['uyari_gereken'] ?></h4>
                        <p class="text-muted mb-0">Uyarı Gereken</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Detaylı Rapor Modal -->
<div class="modal fade" id="detayliRaporModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detaylı Personel Raporu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detayliRaporContent">
                <!-- AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<!-- Günlük Detay Modal -->
<div class="modal fade" id="gunlukDetayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Günlük Çalışma Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="gunlukDetayContent">
                <!-- AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer-scripts.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// DataTable
$(document).ready(function() {
    $('#aylikRaporTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[7, "desc"], [2, "desc"]], // Performansa göre sırala
        "pageLength": 25,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": 8 }
        ]
    });
    
    // Grafikleri çiz
    drawPerformanceChart();
    drawTrendChart();
});

// Performans grafiği
function drawPerformanceChart() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // PHP'den veri al
    const performanceData = <?= json_encode(getPerformanceDagilimiData($aylik_rapor)) ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Mükemmel', 'İyi', 'Orta', 'Zayıf'],
            datasets: [{
                data: [
                    performanceData.mukemmel,
                    performanceData.iyi,
                    performanceData.orta,
                    performanceData.zayif
                ],
                backgroundColor: [
                    '#28a745',
                    '#17a2b8',
                    '#ffc107',
                    '#dc3545'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Trend grafiği
function drawTrendChart() {
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    // Son 6 aylık veri
    const trendData = <?= json_encode(getTrendData($ay, $yil)) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.labels,
            datasets: [{
                label: 'Ortalama Çalışma Saati',
                data: trendData.calisma_saatleri,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true
            }, {
                label: 'Devam Oranı (%)',
                data: trendData.devam_oranlari,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Detaylı rapor göster
function detayliRapor(personelId, ay, yil) {
    $('#detayliRaporModal').modal('show');
    $('#detayliRaporContent').html('<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Rapor hazırlanıyor...</p></div>');
    
    $.get('ajax/personel_detayli_rapor.php', {
        personel_id: personelId,
        ay: ay,
        yil: yil
    }, function(data) {
        $('#detayliRaporContent').html(data);
    }).fail(function() {
        $('#detayliRaporContent').html('<div class="alert alert-danger">Rapor yüklenemedi</div>');
    });
}

// Günlük detay göster
function gunlukDetay(personelId, ay, yil) {
    $('#gunlukDetayModal').modal('show');
    $('#gunlukDetayContent').html('<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Detaylar yükleniyor...</p></div>');
    
    $.get('ajax/personel_gunluk_detay.php', {
        personel_id: personelId,
        ay: ay,
        yil: yil
    }, function(data) {
        $('#gunlukDetayContent').html(data);
    }).fail(function() {
        $('#gunlukDetayContent').html('<div class="alert alert-danger">Detaylar yüklenemedi</div>');
    });
}

// Mesaj gönder
function mesajGonder(personelId) {
    // SMS/WhatsApp gönderme sayfasına yönlendir
    window.open(`personel-mesaj-gonder.php?personel_id=${personelId}`, '_blank');
}

// Excel export
function exportExcel() {
    const ay = '<?= $ay ?>';
    const yil = '<?= $yil ?>';
    const personelId = '<?= $personel_id ?>';
    const departman = '<?= $departman ?>';
    
    let url = `excel/aylik_rapor_export.php?ay=${ay}&yil=${yil}`;
    if (personelId) url += `&personel_id=${personelId}`;
    if (departman) url += `&departman=${departman}`;
    
    window.location.href = url;
    showToast('Excel raporu hazırlanıyor...', 'info');
}

// PDF export
function exportPDF() {
    const ay = '<?= $ay ?>';
    const yil = '<?= $yil ?>';
    const personelId = '<?= $personel_id ?>';
    const departman = '<?= $departman ?>';
    
    let url = `pdf/aylik_rapor_pdf.php?ay=${ay}&yil=${yil}`;
    if (personelId) url += `&personel_id=${personelId}`;
    if (departman) url += `&departman=${departman}`;
    
    window.open(url, '_blank');
    showToast('PDF raporu hazırlanıyor...', 'info');
}

// Toast mesajları
function showToast(message, type = 'info') {
    const toastClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-info';
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
    
    const toast = $(`
        <div class="toast align-items-center text-white ${toastClass} border-0 position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${icon} ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    $('body').append(toast);
    const toastElement = new bootstrap.Toast(toast[0]);
    toastElement.show();
    
    setTimeout(() => toast.remove(), 5000);
}
</script>

<?php include 'partials/footer.php'; ?>

<?php
// === FONKSİYONLAR ===

function getAylikPersonelRaporu($ay, $yil, $personel_id = null, $departman = null) {
    global $pdo;
    try {
        $where_conditions = ["MONTH(pgc.tarih) = ? AND YEAR(pgc.tarih) = ?"];
        $params = [$ay, $yil];
        
        if ($personel_id) {
            $where_conditions[] = "p.id = ?";
            $params[] = $personel_id;
        }
        
        if ($departman) {
            $where_conditions[] = "p.rol = ?";
            $params[] = $departman;
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        
        $sql = "SELECT 
                    p.id as personel_id,
                    CONCAT(p.ad, ' ', p.soyad) as personel_adi,
                    p.sicil_no,
                    p.avatar,
                    p.rol,
                    COUNT(DISTINCT pgc.tarih) as calisilan_gun,
                    COALESCE(SUM(pgc.toplam_calisma_saati), 0) as toplam_saat,
                    COALESCE(AVG(pgc.toplam_calisma_saati), 0) as ortalama_saat,
                    COALESCE(SUM(pgc.mesai_saati), 0) as toplam_mesai,
                    COUNT(CASE WHEN pgc.durum = 'gecikme' THEN 1 END) as gecikme_sayisi,
                    COALESCE(SUM(pgc.gecikme_dakika), 0) as toplam_gecikme,
                    COUNT(CASE WHEN pgc.durum = 'devamsizlik' THEN 1 END) as devamsizlik_sayisi
                FROM personel p
                LEFT JOIN personel_giris_cikis pgc ON pgc.personel_id = p.id AND MONTH(pgc.tarih) = ? AND YEAR(pgc.tarih) = ?
                WHERE p.aktif = 1" . ($departman ? " AND p.rol = ?" : "") . ($personel_id ? " AND p.id = ?" : "") . "
                GROUP BY p.id
                ORDER BY toplam_saat DESC, calisilan_gun DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Aylık personel raporu hatası: " . $e->getMessage());
        return [];
    }
}

function getAyIstatistikleri($ay, $yil) {
    global $pdo;
    try {
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as toplam_personel,
                    AVG(pgc.toplam_calisma_saati) as ortalama_calisma_saati,
                    SUM(pgc.mesai_saati) as toplam_mesai_saati,
                    MAX(pgc.mesai_saati) as en_fazla_mesai,
                    (COUNT(DISTINCT pgc.personel_id) / COUNT(DISTINCT p.id)) * 100 as devam_orani,
                    COUNT(CASE WHEN aylik.calisilan_gun >= 20 THEN 1 END) as tam_devam_eden,
                    COUNT(CASE WHEN aylik.gecikme_sayisi > 3 OR aylik.devamsizlik_sayisi > 2 THEN 1 END) as uyari_gereken
                FROM personel p
                LEFT JOIN personel_giris_cikis pgc ON pgc.personel_id = p.id AND MONTH(pgc.tarih) = ? AND YEAR(pgc.tarih) = ?
                LEFT JOIN (
                    SELECT 
                        personel_id,
                        COUNT(DISTINCT tarih) as calisilan_gun,
                        COUNT(CASE WHEN durum = 'gecikme' THEN 1 END) as gecikme_sayisi,
                        COUNT(CASE WHEN durum = 'devamsizlik' THEN 1 END) as devamsizlik_sayisi
                    FROM personel_giris_cikis 
                    WHERE MONTH(tarih) = ? AND YEAR(tarih) = ?
                    GROUP BY personel_id
                ) aylik ON aylik.personel_id = p.id
                WHERE p.aktif = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ay, $yil, $ay, $yil]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Ay istatistikleri hatası: " . $e->getMessage());
        return [
            'toplam_personel' => 0,
            'ortalama_calisma_saati' => 0,
            'toplam_mesai_saati' => 0,
            'en_fazla_mesai' => 0,
            'devam_orani' => 0,
            'tam_devam_eden' => 0,
            'uyari_gereken' => 0
        ];
    }
}

function getDepartmanlar() {
    return ['terapist', 'yonetici', 'sekreter', 'satis'];
}

function getEnIyiPersonel($aylik_rapor) {
    if (empty($aylik_rapor)) return 'Yok';
    
    $en_iyi = $aylik_rapor[0];
    foreach ($aylik_rapor as $rapor) {
        if ($rapor['toplam_saat'] > $en_iyi['toplam_saat'] && $rapor['gecikme_sayisi'] < 3) {
            $en_iyi = $rapor;
        }
    }
    
    return $en_iyi['personel_adi'];
}

function getPerformanceDagilimiData($aylik_rapor) {
    $dagitim = ['mukemmel' => 0, 'iyi' => 0, 'orta' => 0, 'zayif' => 0];
    
    foreach ($aylik_rapor as $rapor) {
        $working_days = 22; // Ortalama çalışma günü
        $performance_percentage = ($rapor['calisilan_gun'] / $working_days) * 100;
        
        if ($performance_percentage >= 95) {
            $dagitim['mukemmel']++;
        } elseif ($performance_percentage >= 85) {
            $dagitim['iyi']++;
        } elseif ($performance_percentage >= 75) {
            $dagitim['orta']++;
        } else {
            $dagitim['zayif']++;
        }
    }
    
    return $dagitim;
}

function getTrendData($ay, $yil) {
    global $pdo;
    
    $labels = [];
    $calisma_saatleri = [];
    $devam_oranlari = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $tarih = date('Y-m', mktime(0, 0, 0, $ay - $i, 1, $yil));
        $ay_num = date('m', mktime(0, 0, 0, $ay - $i, 1, $yil));
        $yil_num = date('Y', mktime(0, 0, 0, $ay - $i, 1, $yil));
        
        $labels[] = date('M Y', mktime(0, 0, 0, $ay - $i, 1, $yil));
        
        try {
            $sql = "SELECT 
                        AVG(toplam_calisma_saati) as ort_saat,
                        (COUNT(DISTINCT personel_id) / (SELECT COUNT(*) FROM personel WHERE aktif = 1)) * 100 as devam_orani
                    FROM personel_giris_cikis 
                    WHERE MONTH(tarih) = ? AND YEAR(tarih) = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ay_num, $yil_num]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $calisma_saatleri[] = round($result['ort_saat'] ?? 0, 1);
            $devam_oranlari[] = round($result['devam_orani'] ?? 0, 1);
        } catch(PDOException $e) {
            $calisma_saatleri[] = 0;
            $devam_oranlari[] = 0;
        }
    }
    
    return [
        'labels' => $labels,
        'calisma_saatleri' => $calisma_saatleri,
        'devam_oranlari' => $devam_oranlari
    ];
}
?>