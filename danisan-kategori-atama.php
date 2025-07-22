<?php
include_once 'functions.php';
$hata = $basari = "";
// URL’den danisan_id alınmalı, yoksa liste sayfasına yönlendir
$danisan_id = $_GET['danisan_id'] ?? null;
if (!$danisan_id) {
    header("Location: ?page=danisanlar");
    exit;
}
// POST ise güncelle
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $secili = $_POST['kategori'] ?? [];
    if (updateDanisanKategorileri($danisan_id, $secili)) {
        $basari = "Kategoriler güncellendi.";
    } else {
        $hata   = "Güncelleme hatası.";
    }
}
// veri çek
$danisan = $pdo->prepare("SELECT ad,soyad FROM danisanlar WHERE id=?");
$danisan->execute([$danisan_id]);
$danisan = $danisan->fetch(PDO::FETCH_ASSOC);
$kategoriler     = getKategoriler();
$mevcut_kategori = getDanisanKategoriIds($danisan_id);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <?php include 'partials/head-css.php'; ?>
</head>
<body>
  <?php include 'partials/sidenav.php'; include 'partials/topbar.php'; ?>
  <div class="page-content">
    <h4>“<?=$danisan['ad']?> <?=$danisan['soyad']?>” için Kategoriler</h4>
    <?php if($hata): ?><div class="alert alert-danger"><?=$hata?></div><?php endif;?>
    <?php if($basari): ?><div class="alert alert-success"><?=$basari?></div><?php endif;?>
    <form method="post">
      <div class="mb-3">
        <?php foreach($kategoriler as $k): ?>
          <div class="form-check form-check-inline">
            <input
              class="form-check-input"
              type="checkbox"
              name="kategori[]"
              id="kat<?=$k['id']?>"
              value="<?=$k['id']?>"
              <?=in_array($k['id'],$mevcut_kategori)?'checked':''?>>
            <label class="form-check-label" for="kat<?=$k['id']?>">
              <?=$k['ad']?>
            </label>
          </div>
        <?php endforeach;?>
      </div>
      <button class="btn btn-primary"><i class="bx bx-save"></i> Kaydet</button>
      <a href="danisan-listele.php" class="btn btn-secondary"><i class="bx bx-arrow-left"></i> Geri</a>
    </form>
  </div>
  <?php include 'partials/footer-scripts.php'; ?>
</body>
</html>
