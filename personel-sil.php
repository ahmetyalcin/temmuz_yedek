<?php
session_start();
include 'con/db.php'; // Veritabanı bağlantısı
include 'partials/session.php';
// Silinecek personelin ID'sini al
$personel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($personel_id > 0) {
  // Personeli veritabanından sil
  $stmt = $pdo->prepare("DELETE FROM personel WHERE id = :id");
  $stmt->bindParam(":id", $personel_id);

  if ($stmt->execute()) {
    // Silme işlemi başarılıysa, kullanıcıyı listeleme sayfasına yönlendir
    header("Location: personel-listele.php?success=1");
    exit();
  } else {
    // Hata durumunda hata mesajı ile yönlendir
    header("Location: personel-listele.php?error=1");
    exit();
  }
} else {
  // Geçersiz ID durumunda hata mesajı ile yönlendir
  header("Location: personel-listele.php?error=1");
  exit();
}
?>
