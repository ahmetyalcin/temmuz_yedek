<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once 'functions.php';

$uyelikTurleri = getUyelikTurleri();
$kategoriler = getKategoriler();
$action = $_GET['action'] ?? 'list';
$hata = '';
$basari = '';

// Arama ve Paging Ayarları
$arama = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';
$sayfa = isset($_GET['s']) ? max(1, (int)$_GET['s']) : 1;
$sayfa_basi = 50;
$offset = ($sayfa - 1) * $sayfa_basi;
$toplam_danisan = getDanisanlarCountFiltered($arama, $kategori_id);
$danisanlar = getDanisanlarPagingFiltered($arama, $sayfa_basi, $offset, $kategori_id);
$toplam_sayfa = max(1, ceil($toplam_danisan / $sayfa_basi));

// Form işlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['islem'])) {
        switch ($_POST['islem']) {
            case 'danisan_ekle':
                $sonuc = danisanEkle(
                    $_POST['ad'],
                    $_POST['soyad'],
                    $_POST['email'],
                    $_POST['telefon'],
                    $_POST['adres'],
                    $_POST['yas'],
                    $_POST['meslek']
                );
                if ($sonuc) {
                    $basari = "Danışan başarıyla eklendi.";
                    header("Location: ?page=danisanlar");
                    exit;
                } else {
                    $hata = "Danışan eklenirken bir hata oluştu.";
                }
                break;

            case 'danisan_guncelle':
                $sonuc = danisanGuncelle(
                    $_POST['id'],
                    $_POST['ad'],
                    $_POST['soyad'],
                    $_POST['email'],
                    $_POST['telefon'],
                    $_POST['adres'],
                    $_POST['yas'],
                    $_POST['meslek'],
                    $_POST['uyelik_turu_id']
                );
                if ($sonuc) {
                    $basari = "Danışan başarıyla güncellendi.";
                    header("Location: ?page=danisanlar");
                    exit;
                } else {
                    $hata = "Danışan güncellenirken bir hata oluştu.";
                }
                break;
        }
    }
}

// Düzenleme için danışan bilgileri
$duzenlenecek_danisan = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $tumdanisanlar = getDanisanlarPagingFiltered('', 10000, 0);
    foreach ($tumdanisanlar as $danisan) {
        if ($danisan['id'] == $_GET['id']) {
            $duzenlenecek_danisan = $danisan;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php
    $title = "Danışan Listele";
    include "partials/title-meta.php";
    ?>
    <link rel="stylesheet" href="assets/vendor/dropify/css/dropify.min.css" type="text/css" />
    <?php include 'partials/session.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
    .modal-blur {
      opacity: 0.4 !important;
      filter: blur(1.5px) grayscale(60%);
      pointer-events: none;
    }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'partials/sidenav.php'; ?>
        <?php include 'partials/topbar.php'; ?>
        <div class="page-content">
            <div class="page-container">
                <?php
                $subtitle = "Kullanıcı Yönetimi";
                $title = "Danışan Listele";
                include "partials/page-title.php";
                ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                      <div class="container-fluid">
    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo $hata; ?></div>
    <?php endif; ?>
    <?php if ($basari): ?>
        <div class="alert alert-success"><?php echo $basari; ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"></h5>
                <a href="?action=new" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Yeni Danışan
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">

        <!-- Arama + Kategori Filtresi -->
        <form method="get" class="mb-3 d-flex justify-content-end">
          <input type="hidden" name="page" value="danisanlar">
          <input type="text" name="arama" value="<?php echo htmlspecialchars($arama); ?>" class="form-control w-auto" placeholder="Ara..." style="margin-right:10px;max-width:200px;">
          <select name="kategori_id" class="form-select w-auto" style="margin-right:10px;max-width:200px;">
            <option value="">Tüm Kategoriler</option>
            <?php foreach($kategoriler as $kat): ?>
              <option value="<?php echo $kat['id']; ?>" <?php echo $kategori_id == $kat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($kat['ad']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary" type="submit">Filtrele</button>
        </form>

        <table id="datatable" class="table table-bordered dt-responsive nowrap"
               style="border-collapse: collapse; border-spacing: 0; width: 100%;">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Yaş</th>
                    <th>Meslek</th>
                    <th>Kategoriler</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($danisanlar as $danisan): ?>
                <tr>
                    <td><?php echo htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']); ?></td>
                    <td><?php echo htmlspecialchars($danisan['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($danisan['telefon'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($danisan['yas'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($danisan['meslek'] ?? ''); ?></td>
                    <td>

          <span class="badge bg-info">
                <?php echo htmlspecialchars($danisan['kategoriler'] ?? '-'); ?>
                        </span>

                     
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="#"
                                class="btn btn-secondary btn-sm notlar-btn"
                                data-danisan-id="<?php echo $danisan['id']; ?>"
                                data-danisan-ad="<?php echo htmlspecialchars($danisan['ad'] . ' ' . $danisan['soyad']); ?>">
                                <i class="fa fa-sticky-note"></i> Notlar
                            </a>
                            <a href="danisan-detay.php?id=<?php echo $danisan['id']; ?>" class="btn btn-info">
                                <i class="fa fa-eye"></i> Detay
                            </a>
                            <a href="?page=danisanlar&action=edit&id=<?php echo $danisan['id']; ?>" class="btn btn-primary">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="?page=randevular&danisan_id=<?php echo $danisan['id']; ?>" class="btn btn-info">
                                <i class="fa fa-calendar-alt"></i>
                            </a>
                            <button type="button" class="btn btn-danger"
                                onclick="if(confirm('Bu danışanı silmek istediğinizden emin misiniz?')) window.location.href='danisan-listele.php?action=delete&id=<?php echo $danisan['id']; ?>'">
                                <i class="fa fa-trash"></i>
                            </button>
                            <a href="danisan-kategori-atama.php?danisan_id=<?php echo $danisan['id']; ?>" class="btn btn-sm btn-outline-primary">
                                Kategori Ata
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($toplam_sayfa > 1): ?>
        <nav>
            <ul class="pagination">
                <li class="page-item <?php if ($sayfa <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=danisanlar&s=<?php echo $sayfa-1; ?>&arama=<?php echo urlencode($arama); ?>&kategori_id=<?php echo $kategori_id; ?>">«</a>
                </li>
                <?php
                $max_show = 2; $start = max(1, $sayfa - $max_show); $end = min($toplam_sayfa, $sayfa + $max_show);
                if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=danisanlar&s=1&arama='.urlencode($arama).'&kategori_id='.$kategori_id.'">1</a></li>';
                    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                for ($i = $start; $i <= $end; $i++) {
                    echo '<li class="page-item '.($i == $sayfa ? 'active' : '').'"><a class="page-link" href="?page=danisanlar&s='.$i.'&arama='.urlencode($arama).'&kategori_id='.$kategori_id.'">'.$i.'</a></li>';
                }
                if ($end < $toplam_sayfa) {
                    if ($end < $toplam_sayfa - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item"><a class="page-link" href="?page=danisanlar&s='.$toplam_sayfa.'&arama='.urlencode($arama).'&kategori_id='.$kategori_id.'">'.$toplam_sayfa.'</a></li>';
                }
                ?>
                <li class="page-item <?php if ($sayfa >= $toplam_sayfa) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=danisanlar&s=<?php echo $sayfa+1; ?>&arama=<?php echo urlencode($arama); ?>&kategori_id=<?php echo $kategori_id; ?>">»</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Danışan Formu -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action == 'edit' ? 'Danışan Düzenle' : 'Yeni Danışan Ekle'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="islem"
                           value="<?php echo $action == 'edit' ? 'danisan_guncelle' : 'danisan_ekle'; ?>">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $duzenlenecek_danisan['id']; ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" name="ad" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['ad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Soyad</label>
                            <input type="text" name="soyad" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['soyad'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['email'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="telefon" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['telefon'] ?? ''; ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="3"><?php echo $duzenlenecek_danisan['adres'] ?? ''; ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yaş</label>
                            <input type="number" name="yas" class="form-control" required min="0"
                                   value="<?php echo $duzenlenecek_danisan['yas'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Meslek</label>
                            <input type="text" name="meslek" class="form-control" required
                                   value="<?php echo $duzenlenecek_danisan['meslek'] ?? ''; ?>">
                        </div>
                        <?php if ($action == 'edit'): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Üyelik Türü</label>
                            <select name="uyelik_turu_id" class="form-select">
                                <option value="">Seçiniz</option>
                                <?php foreach ($uyelikTurleri as $uyelik): ?>
                                    <option value="<?php echo $uyelik['id']; ?>"
                                            <?php echo ($duzenlenecek_danisan['uyelik_turu_id'] == $uyelik['id']) ? 'selected' : ''; ?>>
                                        <?php echo $uyelik['ad']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action == 'edit' ? 'Güncelle' : 'Kaydet'; ?>
                        </button>
                        <a href="?page=danisanlar" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- NOTLAR MODALI -->
<div class="modal fade" id="notlarModal" tabindex="-1" aria-labelledby="notlarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notlarModalLabel">Danışan Notları</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-end mb-2">
          <button class="btn btn-success" id="yeniNotBtn" type="button"><i class="fa fa-plus"></i> Yeni Not Ekle</button>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="notlarTable">
            <thead>
              <tr>
                <th>Tarih</th>
                <th>Notu Yazan</th>
                <th>Not İçeriği</th>
                <th>İşlem</th>
              </tr>
            </thead>
            <tbody>
              <!-- AJAX ile gelecek -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>

<!-- YENİ NOT EKLE MODALI -->
<div class="modal fade" id="yeniNotModal" tabindex="-1" aria-labelledby="yeniNotModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="yeniNotForm" autocomplete="off">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="yeniNotModalLabel">Yeni Not Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="not_tarihi" class="form-label">Tarih</label>
            <input type="date" class="form-control" name="not_tarihi" id="not_tarihi" required value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="mb-3">
            <label for="icerik" class="form-label">Not İçeriği</label>
            <textarea name="icerik" id="icerik" class="form-control" rows="4" required></textarea>
          </div>
          <input type="hidden" name="danisan_id" id="yeni_not_danisan_id" value="">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Kaydet</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        </div>
      </div>
    </form>
  </div>
</div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'partials/customizer.php' ?>
    <?php include 'partials/footer-scripts.php' ?>

<script>
window.GIRIS_YAPAN_PERSONEL_ID = <?php echo (int)$_SESSION['personel_id']; ?>;
</script>

<script>
let aktifDanisanId = null;
let aktifDanisanAd = '';

function notlariGetir(danisanId, danisanAd) {
  aktifDanisanId = danisanId;
  aktifDanisanAd = danisanAd;
  $('#notlarModalLabel').text(danisanAd + ' Notları');
  $('#yeni_not_danisan_id').val(danisanId);
  $('#notlarTable tbody').empty();

  $.getJSON('ajax/get-danisan-notlar.php', { danisan_id: danisanId }, function(res) {
    if (res.success === false) {
      $('#notlarTable tbody').append(
        '<tr><td colspan="4" class="text-danger">' + res.message + '</td></tr>'
      );
    } else if (Array.isArray(res.data) && res.data.length) {
      res.data.forEach(function(n) {
        var personelIsmi = (n.personel_ad || '') + ' ' + (n.personel_soyad || '');
        var silBtn = '';
        if (parseInt(n.personel_id) === parseInt(window.GIRIS_YAPAN_PERSONEL_ID)) {
          silBtn = `<button class="btn btn-danger btn-sm sil-not-btn" data-not-id="${n.id}">Sil</button>`;
        }
        $('#notlarTable tbody').append(
          `<tr>
            <td>${turkceTarihFormatla(n.not_tarihi)}</td>
            <td>${personelIsmi.trim()}</td>
            <td>${n.icerik}</td>
            <td>${silBtn}</td>
          </tr>`
        );
      });
    } else {
      $('#notlarTable tbody').append('<tr><td colspan="4">Hiç not bulunamadı.</td></tr>');
    }
  });
}


$(document).on('click', '.notlar-btn', function(e) {
  e.preventDefault();
  let danisanId = $(this).data('danisan-id');
  let danisanAd = $(this).data('danisan-ad');
  notlariGetir(danisanId, danisanAd);
  $('#notlarModal').modal('show');
});

$(document).on('click', '#yeniNotBtn', function() {
  $('#yeniNotModal').modal('show');
  $('#yeniNotForm')[0].reset();
  $('#notlarModal').addClass('modal-blur');
  $('#not_tarihi').val(new Date().toISOString().slice(0, 10));
});

$(document).on('submit', '#yeniNotForm', function(e) {
  e.preventDefault();
  let formData = $(this).serialize();
  $.post('ajax/add-danisan-not.php', formData, function(res) {
    if (res.success) {
      $('#yeniNotModal').modal('hide');
      notlariGetir(aktifDanisanId, aktifDanisanAd);
      $('#notlarModal').removeClass('modal-blur');
    } else {
      alert('Kayıt başarısız: ' + (res.message || 'Bilinmeyen hata!'));
    }
  }, 'json');
});

$('#yeniNotModal').on('hidden.bs.modal', function () {
  $('#notlarModal').removeClass('modal-blur');
});

$(document).on('click', '.sil-not-btn', function() {
  const notId = $(this).data('not-id');

  if (confirm('Bu notu silmek istediğinize emin misiniz?')) {
    $.post('ajax/delete-danisan-not.php', { id: notId }, function(res) {
      if (res.success) {
        notlariGetir(aktifDanisanId, aktifDanisanAd); // Listeyi güncelle
      } else {
        alert('Silinemedi: ' + (res.message || 'Hata!'));
      }
    }, 'json');
  }
});


function turkceTarihFormatla(isoTarih) {
  if (!isoTarih) return '';
  const d = new Date(isoTarih);
  if (isNaN(d)) return isoTarih; // Tarih çözülemezse geleni bas
  // Gün ve ayı iki haneli olarak yazdır
  return [
    ('0' + d.getDate()).slice(-2),
    ('0' + (d.getMonth() + 1)).slice(-2),
    d.getFullYear()
  ].join('.');
}

</script>

</body>
</html>
