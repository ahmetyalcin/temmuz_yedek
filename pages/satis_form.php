<?php
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Vade tarihi kontrolü
        $vade_tarihi = null;
        if (isset($_POST['taksitler']) && is_array($_POST['taksitler'])) {
            $son_taksit = end($_POST['taksitler']);
            if (!empty($son_taksit['vade_tarihi'])) {
                $vade_tarihi = $son_taksit['vade_tarihi'];
            }
        }
        
        // İndirim hesaplama
        $indirim_tutari = !empty($_POST['indirim_tutari']) ? $_POST['indirim_tutari'] : 0;
        $indirim_yuzdesi = null;
        if ($indirim_tutari > 0 && $_POST['toplam_tutar'] > 0) {
            $indirim_yuzdesi = ($indirim_tutari / $_POST['toplam_tutar']) * 100;
        }
        
        // Satış kaydı
        $satis_id = satisEkle(
            $_POST['danisan_id'],
            $_POST['hizmet_paketi_id'],
            $_POST['personel_id'],
            $_POST['toplam_tutar'],
            $_POST['odenen_tutar'],
            $_POST['odeme_tipi'],
            $vade_tarihi,
            $_POST['hediye_seans'],
            $indirim_tutari,
            $indirim_yuzdesi
        );

        // İlk ödeme kaydı
        if ($_POST['odenen_tutar'] > 0) {
            odemeEkle(
                $satis_id,
                $_POST['odenen_tutar'],
                $_POST['odeme_tipi'],
                date('Y-m-d H:i:s')
            );
        }

        // Taksit kayıtları
        if (isset($_POST['taksitler']) && is_array($_POST['taksitler'])) {
            foreach ($_POST['taksitler'] as $taksit) {
                if (!empty($taksit['tutar']) && !empty($taksit['vade_tarihi'])) {
                    taksitEkle(
                        $satis_id,
                        $taksit['tutar'],
                        $taksit['vade_tarihi']
                    );
                }
            }
        }

        // Commit transaction
        $pdo->commit();
        header("Location: ?page=satislar");
        exit;
    } catch(Exception $e) {
        // Only rollback if a transaction is active
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$danisanlar = getDanisanlar();
$paketler = getSeansPaketleri();
$personel = getSatisPersoneli();
?>

<div class="container-fluid">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Yeni Satış</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="satis_form">
                <div class="row g-3">
                    <!-- Üst Bilgi Alanı -->
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Toplam Tutar (₺)</label>
                                    <input type="number" name="toplam_tutar" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Peşinat (₺)</label>
                                    <input type="number" name="odenen_tutar" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Kalan (₺)</label>
                                    <input type="number" class="form-control" id="kalan_tutar" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ödeme Tipi</label>
                                    <select name="odeme_tipi" class="form-select" required>
                                        <option value="nakit">Nakit</option>
                                        <option value="kredi_karti">Kredi Kartı</option>
                                        <option value="havale">Havale</option>
                                        <option value="eft">EFT</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ana Bilgiler -->
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Danışan</label>
                                    <select name="danisan_id" class="form-select" required>
                                        <option value="">Seçiniz...</option>
                                        <?php foreach($danisanlar as $danisan): ?>
                                            <option value="<?= $danisan['id'] ?>">
                                                <?= htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hizmet Paketi</label>
                                    <select name="hizmet_paketi_id" class="form-select" required onchange="updatePrice()">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach($paketler as $paket): ?>
                                            <option value="<?= $paket['id'] ?>" 
                                                    data-fiyat="<?= $paket['fiyat'] ?>"
                                                    data-seans="<?= $paket['seans_adet'] ?>">
                                                <?= htmlspecialchars($paket['ad']) ?> 
                                                (<?= $paket['seans_adet'] ?> Seans - <?= number_format($paket['fiyat'], 2, ',', '.') ?> ₺)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Satış Personeli</label>
                                    <select name="personel_id" class="form-select" required>
                                        <option value="">Seçiniz...</option>
                                        <?php foreach($personel as $p): ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= htmlspecialchars($p['ad'] . ' ' . $p['soyad']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- İndirim ve Hediye -->
                    <div class="col-12">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>İndirim Tutarı (₺)</label>
                                    <input type="number" name="indirim_tutari" class="form-control" value="0" min="0" onchange="updateTotalPrice()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hediye Seans Adedi</label>
                                    <input type="number" name="hediye_seans" class="form-control" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ödeme Planı -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Ödeme Planı</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addTaksit()">
                                    + Taksit Ekle
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="taksitler" class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Vade Tarihi</th>
                                                <th>Tutar (₺)</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="taksit_listesi"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                    <a href="?page=satislar" class="btn btn-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let taksitSayisi = 0;
let secilenPaketFiyati = 0;

function updatePrice() {
    const select = document.querySelector('[name="hizmet_paketi_id"]');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        secilenPaketFiyati = parseFloat(option.dataset.fiyat) || 0;
        updateTotalPrice();
    }
}

function updateTotalPrice() {
    const indirimTutari = parseFloat(document.querySelector('[name="indirim_tutari"]').value) || 0;
    const toplamTutar = Math.max(0, secilenPaketFiyati - indirimTutari);
    document.querySelector('[name="toplam_tutar"]').value = toplamTutar.toFixed(2);
    
    const odenenTutar = parseFloat(document.querySelector('[name="odenen_tutar"]').value) || 0;
    document.getElementById('kalan_tutar').value = Math.max(0, toplamTutar - odenenTutar).toFixed(2);
}

function addTaksit() {
    const tbody = document.getElementById('taksit_listesi');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="date" name="taksitler[${taksitSayisi}][vade_tarihi]" 
                   class="form-control" required>
        </td>
        <td>
            <input type="number" name="taksitler[${taksitSayisi}][tutar]" 
                   class="form-control" step="0.01" required>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" 
                    onclick="this.closest('tr').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    taksitSayisi++;
}

// Event listeners
document.querySelector('[name="odenen_tutar"]').addEventListener('input', updateTotalPrice);
document.querySelector('[name="indirim_tutari"]').addEventListener('input', updateTotalPrice);

// Form validation
document.getElementById('satis_form').addEventListener('submit', function(e) {
    const form = this;
    const toplamTutar = parseFloat(form.toplam_tutar.value) || 0;
    const odenenTutar = parseFloat(form.odenen_tutar.value) || 0;
    
    if (odenenTutar > toplamTutar) {
        e.preventDefault();
        alert('Ödenen tutar, toplam tutardan büyük olamaz!');
        return;
    }
    
    // Taksit toplamı kontrolü
    let taksitToplam = 0;
    const taksitInputs = form.querySelectorAll('input[name^="taksitler"][name$="[tutar]"]');
    taksitInputs.forEach(input => {
        taksitToplam += parseFloat(input.value) || 0;
    });
    
    const kalanTutar = toplamTutar - odenenTutar;
    if (taksitToplam > 0 && Math.abs(taksitToplam - kalanTutar) > 0.01) {
        e.preventDefault();
        alert('Taksit toplamı, kalan tutara eşit olmalıdır!');
        return;
    }
});
</script>