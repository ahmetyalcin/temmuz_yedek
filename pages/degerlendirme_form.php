<?php
$personel_id = isset($_GET['personel_id']) ? $_GET['personel_id'] : null;
$ay = isset($_GET['ay']) ? $_GET['ay'] : date('m');
$yil = isset($_GET['yil']) ? $_GET['yil'] : date('Y');
$personel = null;

// Get personnel details
if ($personel_id) {
    try {
        $sql = "SELECT id, CONCAT(ad, ' ', soyad) as ad_soyad, sicil_no 
                FROM personel 
                WHERE id = :personel_id AND aktif = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':personel_id' => $personel_id]);
        $personel = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$personel) {
            header("Location: ?page=degerlendirme_liste&mesaj=" . urlencode("Personel bulunamadı."));
            exit;
        }
    } catch(PDOException $e) {
        error_log("Personel bilgisi getirme hatası: " . $e->getMessage());
        die("Personel bilgisi alınamadı");
    }
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

// Check if evaluation already exists for this month
if ($personel_id) {
    try {
        $sql = "SELECT id FROM personel_degerlendirme 
                WHERE personel_id = :personel_id 
                AND ay = :ay AND yil = :yil";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':personel_id' => $personel_id,
            ':ay' => $ay,
            ':yil' => $yil
        ]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: ?page=degerlendirme_liste&mesaj=" . 
                   urlencode("Bu personel için seçilen ayda zaten değerlendirme yapılmış."));
            exit;
        }
    } catch(PDOException $e) {
        error_log("Değerlendirme kontrolü hatası: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = [];
    foreach ($kriterler as $k) {
        if (!isset($_POST['kriter_' . $k['id']])) {
            $required_fields[] = $k['kriter_adi'];
        }
    }
    
    if (!empty($required_fields)) {
        $hata = "Lütfen tüm kriterleri değerlendirin. (Eksik kriterler: " . implode(", ", $required_fields) . ")";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calculate C score - FIXED FORMULA
            $toplam_puan = 0;
            foreach ($kriterler as $k) {
                $toplam_puan += (int)$_POST['kriter_' . $k['id']];
            }
            $c_skoru = ($toplam_puan / 10) * 2 / 100; // Changed formula here
            
            // Insert evaluation
            $sql = "INSERT INTO personel_degerlendirme 
                    (personel_id, ay, yil, c_skoru, notlar) 
                    VALUES 
                    (:personel_id, :ay, :yil, :c_skoru, :notlar)";
                     
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':personel_id' => $personel_id,
                ':ay' => $ay,
                ':yil' => $yil,
                ':c_skoru' => $c_skoru,
                ':notlar' => $_POST['notlar'] ?? null
            ]);
            
            $degerlendirme_id = $pdo->lastInsertId();
            
            // Insert criteria scores
            $sql = "INSERT INTO degerlendirme_kriterleri 
                    (degerlendirme_id, kriter_id, puan) 
                    VALUES 
                    (:degerlendirme_id, :kriter_id, :puan)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($kriterler as $k) {
                $stmt->execute([
                    ':degerlendirme_id' => $degerlendirme_id,
                    ':kriter_id' => $k['id'],
                    ':puan' => $_POST['kriter_' . $k['id']]
                ]);
            }
            
            $pdo->commit();
            header("Location: ?page=degerlendirme_liste&mesaj=" . urlencode("Değerlendirme başarıyla kaydedildi."));
            exit;
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            error_log("Değerlendirme kayıt hatası: " . $e->getMessage());
            $hata = "Değerlendirme kaydedilemedi.";
        }
    }
}

function getTurkishMonthName($month) {
    $months = [
        1 => 'Ocak',
        2 => 'Şubat',
        3 => 'Mart',
        4 => 'Nisan',
        5 => 'Mayıs',
        6 => 'Haziran',
        7 => 'Temmuz',
        8 => 'Ağustos',
        9 => 'Eylül',
        10 => 'Ekim',
        11 => 'Kasım',
        12 => 'Aralık'
    ];
    return $months[(int)$month];
}
?>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">THERAVİTA AYLIK DEĞERLENDİRME FORMU</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($hata)): ?>
                        <div class="alert alert-danger"><?= $hata ?></div>
                    <?php endif; ?>
                    
                    <?php if ($personel): ?>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <h5>Çalışan Bilgileri</h5>
                                <p class="mb-1"><strong>Ad Soyad:</strong> <?= $personel['ad_soyad'] ?></p>
                                <p class="mb-1"><strong>Sicil No:</strong> <?= $personel['sicil_no'] ?></p>
                                <p><strong>Değerlendirme Dönemi:</strong> <?= getTurkishMonthName($ay) ?> <?= $yil ?></p>
                            </div>

                            <div class="alert alert-info">
                                <strong>Değerlendirme Ölçeği:</strong><br>
                                1 = Kesinlikle Katılmıyorum<br>
                                2 = Katılmıyorum<br>
                                3 = Kararsızım<br>
                                4 = Katılıyorum<br>
                                5 = Kesinlikle Katılıyorum
                            </div>

                            <?php foreach ($kriterler as $k): ?>
                                <div class="question-card">
                                    <div class="question-title">
                                        <?= $k['sira'] ?>. <?= htmlspecialchars($k['kriter_adi']) ?>
                                    </div>
                                    <div class="question-description">
                                        <?= htmlspecialchars($k['aciklama']) ?>
                                    </div>
                                    <div class="rating-container">
                                        <div class="rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="kriter_<?= $k['id'] ?>" value="<?= $i ?>" 
                                                       id="kriter_<?= $k['id'] ?>_<?= $i ?>" required>
                                                <label for="kriter_<?= $k['id'] ?>_<?= $i ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-value"></div>
                                    </div>
                                    <div class="invalid-feedback">
                                        Lütfen bir değerlendirme seçin
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="mb-4">
                                <label for="notlar" class="form-label">Ek Notlar</label>
                                <textarea name="notlar" id="notlar" rows="3" class="form-control"></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="?page=degerlendirme_liste" class="btn btn-secondary">
                                    Geri Dön
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    Kaydet ve Hesapla
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Lütfen değerlendirme yapılacak personeli seçin.
                        </div>
                        <a href="?page=degerlendirme_liste" class="btn btn-primary">
                            Personel Listesine Dön
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1rem 0;
}

.rating {
    display: flex;
    flex-direction: row-reverse;
    gap: 0.5rem;
}

.rating > input {
    display: none;
}

.rating > label {
    cursor: pointer;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #ddd;
    transition: all 0.2s ease;
}

.rating > label:hover,
.rating > label:hover ~ label,
.rating > input:checked ~ label {
    color: #ffd700;
}

.rating-value {
    font-weight: bold;
    min-width: 2rem;
}

.question-card {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.question-title {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #2c3e50;
}

.question-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}
</style>

<script>
(() => {
    'use strict';

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Rating functionality
    document.querySelectorAll('.rating').forEach(ratingGroup => {
        const inputs = ratingGroup.querySelectorAll('input');
        const valueDisplay = ratingGroup.parentElement.querySelector('.rating-value');

        inputs.forEach(input => {
            input.addEventListener('change', () => {
                if (input.checked) {
                    valueDisplay.textContent = input.value + ' puan';
                }
            });
        });
    });
})();
</script>