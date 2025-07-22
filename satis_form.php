<?php
session_start();
require_once 'functions.php';

// Verileri getir
$danisanlar = getDanisanlar();
$paketler    = getSeansPaketleri();
$personel    = getSatisPersoneli();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // Vade tarihi kontrolü
        $vade_tarihi = null;
        if (!empty($_POST['taksitler']) && is_array($_POST['taksitler'])) {
            $son_taksit = end($_POST['taksitler']);
            if (!empty($son_taksit['vade_tarihi'])) {
                $vade_tarihi = $son_taksit['vade_tarihi'];
            }
        }

        // İndirim yüzdesi al
        $indirim_yuzdesi = isset($_POST['indirim_yuzde'])
            ? floatval($_POST['indirim_yuzde'])
            : 0;

        // Seçilen paket fiyatını bul
        $paketFiyati = 0;
        foreach ($paketler as $p) {
            if ($p['id'] == $_POST['hizmet_paketi_id']) {
                $paketFiyati = floatval($p['fiyat']);
                break;
            }
        }

        // İndirim TL tutarı ve net tutar
        $indirim_tutari = ($paketFiyati * $indirim_yuzdesi) / 100;
        $net_tutar      = max(0, $paketFiyati - $indirim_tutari);

        // Satış kaydı
        $satis_id = satisEkle(
            $_POST['danisan_id'],           // danışan
            $_POST['hizmet_paketi_id'],     // paket
            $_POST['personel_id'],          // personel
            $net_tutar,                     // toplam tutar (net)
            floatval($_POST['odenen_tutar']), // peşinat
            $_POST['odeme_tipi'],           // ödeme tipi
            $vade_tarihi,                   // vade tarihi
            intval($_POST['hediye_seans']), // hediye seans
            $indirim_tutari,                // indirim TL
            $indirim_yuzdesi                // indirim %
        );

        // İlk ödeme kaydı
        if (floatval($_POST['odenen_tutar']) > 0) {
            odemeEkle(
                $satis_id,
                floatval($_POST['odenen_tutar']),
                $_POST['odeme_tipi'],
                date('Y-m-d H:i:s')
            );
        }

        // Taksit kayıtları
        if (!empty($_POST['taksitler']) && is_array($_POST['taksitler'])) {
            foreach ($_POST['taksitler'] as $taksit) {
                if (!empty($taksit['tutar']) && !empty($taksit['vade_tarihi'])) {
                    taksitEkle(
                        $satis_id,
                        floatval($taksit['tutar']),
                        $taksit['vade_tarihi']
                    );
                }
            }
        }

        $pdo->commit();
        header("Location: satislar.php");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                $title    = "Satışlar";
                include "partials/page-title.php";
                ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" id="satis_form">
                            <div class="row g-3">

                                <!-- Hizmet Paketi -->
                                <div class="col-12">
                                    <label>Hizmet Paketi</label>
                                    <select name="hizmet_paketi_id" class="form-select searchable-select" required onchange="updatePrice()">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach($paketler as $paket): ?>
                                            <option value="<?= $paket['id'] ?>"
                                                    data-fiyat="<?= $paket['fiyat'] ?>"
                                                    data-seans="<?= $paket['seans_adet'] ?>">
                                                <?= htmlspecialchars($paket['ad']) ?>
                                                (<?= $paket['seans_adet'] ?> Seans - <?= number_format($paket['fiyat'],2,',','.') ?> ₺)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Fiyat Hesaplama -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label>Toplam Tutar (₺)</label>
                                            <input type="number" name="toplam_tutar" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Peşinat (₺)</label>
                                            <input type="number" name="odenen_tutar" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Kalan (₺)</label>
                                            <input type="number" id="kalan_tutar" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-3">
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

                                <!-- Danışan ve Personel -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label>Danışan</label>
                                            <select name="danisan_id" class="form-select searchable-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($danisanlar as $d): ?>
                                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['ad'] . ' ' . $d['soyad']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Satış Personeli</label>
                                            <select name="personel_id" class="form-select searchable-select" required>
                                                <option value="">Seçiniz...</option>
                                                <?php foreach($personel as $p): ?>
                                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ad'] . ' ' . $p['soyad']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- İndirim ve Hediye -->
                                <div class="col-12">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label>İndirim (%)</label>
                                            <input type="number" name="indirim_yuzde" id="indirim_yuzde" class="form-control" value="0" min="0" max="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Hediye Seans Adedi</label>
                                            <input type="number" name="hediye_seans" class="form-control" value="0" min="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Ödeme Planı -->
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Ödeme Planı</h6>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="addTaksit()">+ Taksit Ekle</button>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr><th>Vade Tarihi</th><th>Tutar (₺)</th><th></th></tr>
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
        </div>

        <?php include 'partials/customizer.php'; ?>
        <?php include 'partials/footer-scripts.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            let secilenPaketFiyati = 0;
            document.addEventListener('DOMContentLoaded', function() {
                $('.searchable-select').select2({ width:'100%', placeholder:'Seçiniz...', allowClear:true });
                document.querySelector('[name="hizmet_paketi_id"]').addEventListener('change', updatePrice);
                document.getElementById('indirim_yuzde').addEventListener('input', updateTotalPrice);
                document.querySelector('[name="odenen_tutar"]').addEventListener('input', updateTotalPrice);
            });
            function updatePrice() {
                const sel = document.querySelector('[name="hizmet_paketi_id"]');
                secilenPaketFiyati = sel.value ? parseFloat(sel.selectedOptions[0].dataset.fiyat) : 0;
                updateTotalPrice();
            }
            function updateTotalPrice() {
                const pct = parseFloat(document.getElementById('indirim_yuzde').value) || 0;
                const discount = secilenPaketFiyati * pct / 100;
                const toplam = Math.max(0, secilenPaketFiyati - discount);
                document.querySelector('[name="toplam_tutar"]').value = toplam.toFixed(2);
                const odenen = parseFloat(document.querySelector('[name="odenen_tutar"]').value) || 0;
                document.getElementById('kalan_tutar').value = Math.max(0, toplam - odenen).toFixed(2);
            }
            function addTaksit() {
                const tbody = document.getElementById('taksit_listesi');
                const idx = tbody.children.length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="date" name="taksitler[${idx}][vade_tarihi]" class="form-control" required></td>
                    <td><input type="number" name="taksitler[${idx}][tutar]" class="form-control" step="0.01" required></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
            }
            document.getElementById('satis_form').addEventListener('submit', function(e){
                const form=this; const tot=parseFloat(form.toplam_tutar.value)||0;
                const od=parseFloat(form.odenen_tutar.value)||0;
                if(od>tot){ e.preventDefault(); alert('Ödenen tutar, toplam tutardan büyük olamaz!'); }
                let sum=0;
                form.querySelectorAll('input[name^="taksitler"][name$="[tutar]"]').forEach(i=>sum+=parseFloat(i.value)||0);
                const left=tot-od;
                if(sum>0&&Math.abs(sum-left)>0.01){ e.preventDefault(); alert('Taksit toplamı, kalan tutara eşit olmalı!'); }
            });
        </script>
    </div>
</body>
</html>
