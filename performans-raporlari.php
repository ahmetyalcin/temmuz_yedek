<?php
// performans-raporlari.php - Kapsamlı performans analizi ve raporlama sistemi
session_start();
require_once 'functions.php';

// Yetki kontrolü
if (!in_array($_SESSION['rol'], ['yonetici', 'ik'])) {
    header('Location: unauthorized.php');
    exit;
}

// Filtreleme parametreleri
$baslangic_tarih = $_GET['baslangic_tarih'] ?? date('Y-m-01');
$bitis_tarih = $_GET['bitis_tarih'] ?? date('Y-m-t');
$personel_id = $_GET['personel_id'] ?? '';
$departman = $_GET['departman'] ?? '';
$rapor_tipi = $_GET['rapor_tipi'] ?? 'genel';

// Rapor verilerini getir
$performans_verileri = getPerformansRaporlari($baslangic_tarih, $bitis_tarih, $personel_id, $departman);
$departman_karsilastirma = getDepartmanKarsilastirma($baslangic_tarih, $bitis_tarih);
$trend_analizi = getTrendAnalizi($baslangic_tarih, $bitis_tarih);
$personel_siralaması = getPerformansRankingList($baslangic_tarih, $bitis_tarih);

$title = 'Performans Raporları';
include __DIR__ . '/partials/header.php';
?>

<style>
.performance-card {
    border-radius: 15px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.performance-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.kpi-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.kpi-widget::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: rotate(45deg);
}

.performance-excellent { border-left: 5px solid #28a745; }
.performance-good { border-left: 5px solid #20c997; }
.performance-average { border-left: 5px solid #ffc107; }
.performance-poor { border-left: 5px solid #dc3545; }

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.ranking-list {
    max-height: 400px;
    overflow-y: auto;
}

.ranking-item {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.ranking-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.ranking-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2em;
}

.rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #856404; }
.rank-2 { background: linear-gradient(135deg, #c0c0c0, #e6e6e6); color: #495057; }
.rank-3 { background: linear-gradient(135deg, #cd7f32, #d4a574); color: #fff; }
.rank-other { background: linear-gradient(135deg, #6c757d, #adb5bd); color: #fff; }

.metric-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1em;
    margin: 0 auto 10px;
}

.filter-panel {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
}

.performance-indicator {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
    overflow: hidden;
    margin: 8px 0;
}

.performance-bar {
    height: 100%;
    transition: width 0.8s ease;
}

.bar-excellent { background: linear-gradient(90deg, #28a745, #20c997); }
.bar-good { background: linear-gradient(90deg, #20c997, #17a2b8); }
.bar-average { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.bar-poor { background: linear-gradient(90deg, #dc3545, #e83e8c); }
</style>

<div class="wrapper">
    <div class="page-content">
        <div class="page-container">
            <?php include __DIR__ . '/partials/page-title.php'; ?>

            <div class="container-fluid">
                
                <!-- Filtre Paneli -->
                <div class="filter-panel">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-dark">📅 Başlangıç</label>
                            <input type="date" name="baslangic_tarih" class="form-control" value="<?= $baslangic_tarih ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-dark">📅 Bitiş</label>
                            <input type="date" name="bitis_tarih" class="form-control" value="<?= $bitis_tarih ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-dark">👤 Personel</label>
                            <select name="personel_id" class="form-select">
                                <option value="">Tüm Personel</option>
                                <?php foreach(getPersoneller() as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $personel_id == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['ad'] . ' ' . $p['soyad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-dark">🏢 Departman</label>
                            <select name="departman" class="form-select">
                                <option value="">Tüm Departmanlar</option>
                                <option value="terapist" <?= $departman == 'terapist' ? 'selected' : '' ?>>Terapist</option>
                                <option value="satis" <?= $departman == 'satis' ? 'selected' : '' ?>>Satış</option>
                                <option value="yonetici" <?= $departman == 'yonetici' ? 'selected' : '' ?>>Yönetici</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold text-dark">📊 Rapor Tipi</label>
                            <select name="rapor_tipi" class="form-select">
                                <option value="genel" <?= $rapor_tipi == 'genel' ? 'selected' : '' ?>>Genel Performans</option>
                                <option value="devam" <?= $rapor_tipi == 'devam' ? 'selected' : '' ?>>Devam Durumu</option>
                                <option value="verimlilik" <?= $rapor_tipi == 'verimlilik' ? 'selected' : '' ?>>Verimlilik</option>
                                <option value="karsilastirma" <?= $rapor_tipi == 'karsilastirma' ? 'selected' : '' ?>>Karşılaştırma</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-dark w-100">
                                <i class="fas fa-chart-line me-1"></i>Analiz Et
                            </button>
                        </div>
                    </form>
                </div>

                <!-- KPI Kartları -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="kpi-widget">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <h3><?= number_format($performans_verileri['genel_skor'], 1) ?>%</h3>
                            <p class="mb-0">Genel Performans Skoru</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white performance-card">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3><?= number_format($performans_verileri['devam_orani'], 1) ?>%</h3>
                                <p class="mb-0">Devam Oranı</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white performance-card">
                            <div class="card-body text-center">
                                <i class="fas fa-tasks fa-2x mb-2"></i>
                                <h3><?= number_format($performans_verileri['verimlilik_skoru'], 1) ?>%</h3>
                                <p class="mb-0">Verimlilik Skoru</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white performance-card">
                            <div class="card-body text-center">
                                <i class="fas fa-star fa-2x mb-2"></i>
                                <h3><?= number_format($performans_verileri['musteri_memnuniyet'], 1) ?>/5</h3>
                                <p class="mb-0">Müşteri Memnuniyeti</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Detaylı Performans Analizi -->
                    <div class="col-lg-8">
                        <div class="card performance-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-analytics text-primary me-2"></i>
                                    Detaylı Performans Analizi
                                </h5>
                            </div>
                            <div class="card-body">
                                
                                <?php if ($rapor_tipi == 'genel' || $rapor_tipi == 'karsilastirma'): ?>
                                    <!-- Genel Performans Tablosu -->
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="performansTable">
                                            <thead>
                                                <tr>
                                                    <th>Personel</th>
                                                    <th>Devam</th>
                                                    <th>Çalışma Saati</th>
                                                    <th>Verimlilik</th>
                                                    <th>Değerlendirme</th>
                                                    <th>Genel Skor</th>
                                                    <th>Durum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($performans_verileri['detay'] as $personel): ?>
                                                    <?php
                                                    $performans_sinif = '';
                                                    if ($personel['genel_skor'] >= 85) $performans_sinif = 'performance-excellent';
                                                    elseif ($personel['genel_skor'] >= 70) $performans_sinif = 'performance-good';
                                                    elseif ($personel['genel_skor'] >= 50) $performans_sinif = 'performance-average';
                                                    else $performans_sinif = 'performance-poor';
                                                    ?>
                                                    <tr class="<?= $performans_sinif ?>">
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="uploads/avatars/<?= $personel['avatar'] ?: 'default.png' ?>" 
                                                                     class="rounded-circle me-2" width="40" height="40" alt="">
                                                                <div>
                                                                    <strong><?= htmlspecialchars($personel['personel_adi']) ?></strong>
                                                                    <br><small class="text-muted"><?= htmlspecialchars($personel['departman']) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="metric-circle bg-<?= $personel['devam_orani'] >= 95 ? 'success' : ($personel['devam_orani'] >= 85 ? 'warning' : 'danger') ?>">
                                                                <?= number_format($personel['devam_orani'], 0) ?>%
                                                            </div>
                                                            <div class="performance-indicator">
                                                                <div class="performance-bar bar-<?= $personel['devam_orani'] >= 95 ? 'excellent' : ($personel['devam_orani'] >= 85 ? 'good' : ($personel['devam_orani'] >= 70 ? 'average' : 'poor')) ?>" 
                                                                     style="width: <?= $personel['devam_orani'] ?>%"></div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <strong><?= number_format($personel['ortalama_calisma_saati'], 1) ?> saat</strong>
                                                            <br><small class="text-muted">
                                                                Hedef: 8.0 saat
                                                                <?php if ($personel['mesai_saati'] > 0): ?>
                                                                    <br>+<?= number_format($personel['mesai_saati'], 1) ?> mesai
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="metric-circle bg-<?= $personel['verimlilik_skoru'] >= 80 ? 'primary' : ($personel['verimlilik_skoru'] >= 60 ? 'info' : 'secondary') ?>">
                                                                <?= number_format($personel['verimlilik_skoru'], 0) ?>%
                                                            </div>
                                                            <small class="text-muted">
                                                                <?php if ($personel['departman'] == 'terapist'): ?>
                                                                    <?= $personel['hasta_sayisi'] ?> hasta
                                                                <?php elseif ($personel['departman'] == 'satis'): ?>
                                                                    <?= $personel['satis_sayisi'] ?> satış
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($personel['son_degerlendirme_skoru']): ?>
                                                                <span class="badge bg-<?= $personel['son_degerlendirme_skoru'] >= 8 ? 'success' : ($personel['son_degerlendirme_skoru'] >= 6 ? 'warning' : 'danger') ?>">
                                                                    <?= number_format($personel['son_degerlendirme_skoru'], 1) ?>/10
                                                                </span>
                                                                <br><small class="text-muted">
                                                                    <?= date('d.m.Y', strtotime($personel['son_degerlendirme_tarihi'])) ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <span class="text-muted">Değerlendirme yok</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="metric-circle bg-<?= $personel['genel_skor'] >= 85 ? 'success' : ($personel['genel_skor'] >= 70 ? 'warning' : 'danger') ?>">
                                                                <?= number_format($personel['genel_skor'], 0) ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($personel['genel_skor'] >= 85): ?>
                                                                <span class="badge bg-success">🌟 Mükemmel</span>
                                                            <?php elseif ($personel['genel_skor'] >= 70): ?>
                                                                <span class="badge bg-info">👍 İyi</span>
                                                            <?php elseif ($personel['genel_skor'] >= 50): ?>
                                                                <span class="badge bg-warning">⚠️ Orta</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">🚨 Geliştirilmeli</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <?php if ($rapor_tipi == 'devam'): ?>
                                    <!-- Devam Durumu Detayı -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="devamChart" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="gecikmeChart" width="400" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($rapor_tipi == 'verimlilik'): ?>
                                    <!-- Verimlilik Analizi -->
                                    <div class="chart-container">
                                        <canvas id="verimlilikChart" width="800" height="400"></canvas>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <!-- Sağ Panel -->
                    <div class="col-lg-4">
                        <!-- Performans Sıralaması -->
                        <div class="card performance-card">
                            <div class="card-header">
                                <h6 class="mb-0">🏆 Performans Sıralaması</h6>
                            </div>
                            <div class="card-body ranking-list">
                                <?php foreach($personel_siralaması as $index => $personel): ?>
                                    <div class="ranking-item">
                                        <div class="d-flex align-items-center">
                                            <div class="ranking-badge <?= $index < 3 ? 'rank-' . ($index + 1) : 'rank-other' ?>">
                                                <?= $index + 1 ?>
                                            </div>
                                            <div class="ms-3 flex-grow-1">
                                                <strong><?= htmlspecialchars($personel['personel_adi']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($personel['departman']) ?></small>
                                            </div>
                                            <div class="text-end">
                                                <strong class="text-primary"><?= number_format($personel['genel_skor'], 1) ?>%</strong>
                                                <br><small class="text-muted">
                                                    <?= $personel['trend'] > 0 ? '📈' : ($personel['trend'] < 0 ? '📉' : '➡️') ?>
                                                    <?= abs($personel['trend']) ?>%
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Departman Karşılaştırması -->
                        <div class="card performance-card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">📊 Departman Karşılaştırması</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach($departman_karsilastirma as $dept): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-bold"><?= ucfirst($dept['departman']) ?></span>
                                            <span class="text-primary"><?= number_format($dept['ortalama_skor'], 1) ?>%</span>
                                        </div>
                                        <div class="performance-indicator">
                                            <div class="performance-bar bar-<?= $dept['ortalama_skor'] >= 80 ? 'excellent' : ($dept['ortalama_skor'] >= 60 ? 'good' : 'average') ?>" 
                                                 style="width: <?= $dept['ortalama_skor'] ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $dept['personel_sayisi'] ?> personel</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="card performance-card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">⚡ Hızlı İşlemler</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="exportPerformansRaporu()">
                                        <i class="fas fa-file-pdf me-2"></i>PDF Rapor
                                    </button>
                                    <button class="btn btn-outline-success" onclick="exportExcelRaporu()">
                                        <i class="fas fa-file-excel me-2"></i>Excel Rapor
                                    </button>
                                    <button class="btn btn-outline-info" onclick="emailRaporu()">
                                        <i class="fas fa-envelope me-2"></i>E-posta Gönder
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="performansToplantiPlan()">
                                        <i class="fas fa-calendar me-2"></i>Toplantı Planla
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Trend Analizi -->
                        <div class="card performance-card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">📈 Trend Analizi</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="trendChart" width="300" height="200"></canvas>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Genel Eğilim:</span>
                                        <span class="fw-bold <?= $trend_analizi['genel_trend'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $trend_analizi['genel_trend'] > 0 ? '↗️ Yükseliş' : '↘️ Düşüş' ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        Son 30 güne göre <?= abs($trend_analizi['genel_trend']) ?>% değişim
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer-scripts.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    $('#performansTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json"
        },
        "order": [[5, "desc"]], // Genel skora göre sırala
        "pageLength": 15
    });

    // Grafikleri yükle
    initializeCharts();
});

function initializeCharts() {
    // Trend Chart
    const trendData = <?= json_encode($trend_analizi['grafikler']) ?>;
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.tarih),
            datasets: [{
                label: 'Genel Performans',
                data: trendData.map(item => item.performans_skoru),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }, {
                label: 'Devam Oranı',
                data: trendData.map(item => item.devam_orani),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4
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
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    <?php if ($rapor_tipi == 'devam'): ?>
    // Devam Chart
    const devamCtx = document.getElementById('devamChart').getContext('2d');
    new Chart(devamCtx, {
        type: 'doughnut',
        data: {
            labels: ['Zamanında', 'Gecikme', 'Devamsızlık'],
            datasets: [{
                data: [
                    <?= $calisma_istatistikleri['zamaninda_gelen'] ?>,
                    <?= $calisma_istatistikleri['geciken'] ?>,
                    <?= $calisma_istatistikleri['devamsiz'] ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 0
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

    // Gecikme Chart
    const gecikmeCtx = document.getElementById('gecikmeChart').getContext('2d');
    new Chart(gecikmeCtx, {
        type: 'bar',
        data: {
            labels: ['0-5 dk', '5-15 dk', '15-30 dk', '30+ dk'],
            datasets: [{
                label: 'Gecikme Dağılımı',
                data: [15, 8, 4, 2], // Örnek veri
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(255, 152, 0, 0.8)',
                    'rgba(255, 87, 34, 0.8)',
                    'rgba(244, 67, 54, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

    <?php if ($rapor_tipi == 'verimlilik'): ?>
    // Verimlilik Chart
    const verimlilikCtx = document.getElementById('verimlilikChart').getContext('2d');
    new Chart(verimlilikCtx, {
        type: 'radar',
        data: {
            labels: [
                'Devam Durumu',
                'Çalışma Saati',
                'Kalite Skoru',
                'Müşteri Memnuniyeti',
                'Hedefe Ulaşma',
                'Takım Çalışması'
            ],
            datasets: [{
                label: 'Ortalama Performans',
                data: [85, 78, 92, 88, 76, 83],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                borderWidth: 2
            }, {
                label: 'Hedef',
                data: [90, 85, 95, 90, 85, 90],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5]
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
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20
                    }
                }
            }
        }
    });
    <?php endif; ?>
}

// Export fonksiyonları
function exportPerformansRaporu() {
    const params = new URLSearchParams(window.location.search);
    window.open(`performans_pdf_export.php?${params.toString()}`, '_blank');
    showToast('PDF raporu hazırlanıyor...', 'info');
}

function exportExcelRaporu() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = `performans_excel_export.php?${params.toString()}`;
    showToast('Excel raporu indiriliyor...', 'info');
}

function emailRaporu() {
    if (confirm('Performans raporu yöneticilere e-posta ile gönderilecek. Onaylıyor musunuz?')) {
        const params = new URLSearchParams(window.location.search);
        
        $.post('ajax/performans_email_gonder.php', {
            baslangic_tarih: '<?= $baslangic_tarih ?>',
            bitis_tarih: '<?= $bitis_tarih ?>',
            rapor_tipi: '<?= $rapor_tipi ?>'
        }, function(response) {
            if (response.success) {
                showToast(`Rapor ${response.gonderilen} kişiye gönderildi`, 'success');
            } else {
                showToast('E-posta gönderilirken hata oluştu', 'error');
            }
        }, 'json');
    }
}

function performansToplantiPlan() {
    // Düşük performanslı personeller için toplantı planlama
    const dusukPerformanslilar = <?= json_encode(array_filter($performans_verileri['detay'], function($p) { return $p['genel_skor'] < 70; })) ?>;
    
    if (dusukPerformanslilar.length === 0) {
        showToast('Toplantı gerektirecek düşük performanslı personel yok', 'info');
        return;
    }
    
    const mesaj = `${dusukPerformanslilar.length} personel için performans toplantısı planlanacak. Devam edilsin mi?`;
    
    if (confirm(mesaj)) {
        $.post('ajax/performans_toplanti_plan.php', {
            personel_listesi: dusukPerformanslilar.map(p => p.personel_id),
            baslangic_tarih: '<?= $baslangic_tarih ?>',
            bitis_tarih: '<?= $bitis_tarih ?>'
        }, function(response) {
            if (response.success) {
                showToast('Performans toplantıları planlandı', 'success');
            } else {
                showToast('Toplantı planlanırken hata oluştu', 'error');
            }
        }, 'json');
    }
}

// Performans detayı görüntüleme
function personelDetayGoster(personelId) {
    window.open(`personel-performans-detay.php?id=${personelId}&baslangic=${encodeURIComponent('<?= $baslangic_tarih ?>')}&bitis=${encodeURIComponent('<?= $bitis_tarih ?>')}`, '_blank');
}

// Gerçek zamanlı performans güncelleme
function performansGuncelle() {
    $.get('ajax/performans_guncelle.php', {
        baslangic_tarih: '<?= $baslangic_tarih ?>',
        bitis_tarih: '<?= $bitis_tarih ?>'
    }, function(response) {
        if (response.success) {
            // Sayfayı yenile veya verileri güncelle
            location.reload();
        }
    }, 'json');
}

// Otomatik güncelleme (her 5 dakikada bir)
setInterval(performansGuncelle, 300000);

// Toast mesajları
function showToast(message, type = 'info') {
    const toastClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    
    const toastHTML = `
        <div class="toast align-items-center text-white ${toastClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${icon} ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    if (!$('#toast-container').length) {
        $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>');
    }
    
    const toastElement = $(toastHTML).appendTo('#toast-container');
    const toast = new bootstrap.Toast(toastElement[0], {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    toastElement.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Performans kartlarına tıklama olayları
$(document).on('click', '.performance-card', function() {
    $(this).toggleClass('shadow-lg');
});

// Sayfa yüklendiğinde animasyonlar
$(document).ready(function() {
    $('.performance-card').each(function(index) {
        $(this).delay(index * 100).animate({
            opacity: 1,
            transform: 'translateY(0)'
        }, 500);
    });
    
    // Performans barlarını animasyonla göster
    $('.performance-bar').each(function() {
        const width = $(this).css('width');
        $(this).css('width', '0').animate({
            width: width
        }, 1000);
    });
});
</script>

<style>
.performance-card {
    opacity: 0;
    transform: translateY(20px);
}

.ranking-item:nth-child(1) { animation-delay: 0.1s; }
.ranking-item:nth-child(2) { animation-delay: 0.2s; }
.ranking-item:nth-child(3) { animation-delay: 0.3s; }

@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.ranking-item {
    animation: slideInFromRight 0.5s ease forwards;
}

/* Responsive tasarım iyileştirmeleri */
@media (max-width: 768px) {
    .kpi-widget {
        margin-bottom: 15px;
    }
    
    .metric-circle {
        width: 60px;
        height: 60px;
        font-size: 0.9em;
    }
    
    .filter-panel .row {
        gap: 10px;
    }
    
    .chart-container {
        margin-bottom: 20px;
    }
}

/* Print stilleri */
@media print {
    .btn, .filter-panel, .card-header .btn {
        display: none !important;
    }
    
    .performance-card {
        break-inside: avoid;
        margin-bottom: 20px;
    }
    
    body {
        background: white !important;
    }
}
</style>

<?php include 'partials/footer.php'; ?>