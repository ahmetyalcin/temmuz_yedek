<?php
include_once 'functions.php';
$hata = $basari = "";
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['ad'])) {
    if (addKategori($_POST['ad'], $_POST['aciklama'])) {
        $basari = "Kategori eklendi.";
    } else {
        $hata   = "Ekleme sırasında hata.";
    }
}
$kategoriler = getKategoriler();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include 'partials/head-css.php'; ?>
</head>
<body>
  <?php include 'partials/sidenav.php'; include 'partials/topbar.php'; ?>
  <div class="page-content">
    <h4>Kategoriler</h4>
    <?php if($hata): ?><div class="alert alert-danger"><?=$hata?></div><?php endif;?>
    <?php if($basari): ?><div class="alert alert-success"><?=$basari?></div><?php endif;?>
    <form method="post" class="row g-2 mb-4">
      <div class="col-md-4">
        <input type="text" name="ad" class="form-control" placeholder="Kategori Adı" required>
      </div>
      <div class="col-md-5">
        <input type="text" name="aciklama" class="form-control" placeholder="Açıklama (opsiyonel)">
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary">Ekle</button>
      </div>
    </form>
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Ad</th><th>Açıklama</th></tr></thead>
      <tbody>
        <?php foreach($kategoriler as $k): ?>
        <tr>
          <td><?=$k['id']?></td>
          <td><?=$k['ad']?></td>
          <td><?=$k['aciklama']?></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>
  <?php include 'partials/footer-scripts.php'; ?>
</body>
</html>
