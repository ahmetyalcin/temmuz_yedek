<?php
// partials/topbar.php (veya sizin header dosyanız)
// Oturum açıldı mı kontrolü, en üstte
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$adSoyad = htmlspecialchars($_SESSION['ad_soyad'] ?? '');
$avatar  = htmlspecialchars($_SESSION['avatar'] ?? 'uploads/avatars/default_avatar.png');
?>

<header class="app-topbar">
  <div class="page-container topbar-menu">
    <div class="d-flex align-items-center gap-1">
      <!-- Sidebar Toggle -->
      <button class="sidenav-toggle-button px-2">
        <i data-lucide="menu" class="font-22"></i>
      </button>
      <!-- Horizontal Toggle -->
      <button class="topnav-toggle-button px-2" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
        <i data-lucide="menu" class="font-22"></i>
      </button>
    </div>

    <div class="d-flex align-items-center gap-1">
      <!-- Karanlık/aydınlık modu -->
      <div class="topbar-item">
        <button id="light-dark-mode" class="topbar-link" type="button">
          <i data-lucide="moon"  class="font-22 light-mode"></i>
          <i data-lucide="sun"   class="font-22 dark-mode"></i>
        </button>
      </div>

      <!-- Kullanıcı Dropdown -->
      <div class="topbar-item nav-user">
        <div class="dropdown">
          <a class="topbar-link dropdown-toggle px-0" 
             data-bs-toggle="dropdown" aria-expanded="false">
            <!-- Avatar -->
            <img src="<?= $avatar ?>" width="32" class="rounded-circle me-2" alt="user-image">
            <!-- İsim soyisim (her boyutta gözüksün) -->
            <span class="d-flex align-items-center">
              <span class="me-1"><?= $adSoyad ?></span>
              <i data-lucide="chevron-down" height="12"></i>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php">
              <i data-lucide="user" class="me-1"></i> Profil
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">
              <i data-lucide="log-out" class="me-1"></i> Çıkış Yap
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- footer ya da en sona Lucide JS -->
<script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
<script>lucide.replace()</script>
