<?php


// Rol yardımcı fonksiyonları
function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'yonetici';
}
function isTherapist() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'terapist';
}
?>

<!-- Sidenav Menu Start -->
<div class="sidenav-menu">
    <!-- Brand Logo vs... -->

    <div data-simplebar>

        <ul class="side-nav">

            <!-- Dashboard (herkes görür) -->
            <li class="side-nav-item">
                <a href="dashboard.php" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="home"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <?php if (isAdmin()): ?>
            <!-- Personel Yönetimi (sadece admin) -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPersonel" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="users"></i></span>
                    <span class="menu-text">Personel</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPersonel">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="personel-kayit.php" class="side-nav-link">
                                <span class="menu-text">Personel Kayıt</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="personel-listele.php" class="side-nav-link">
                                <span class="menu-text">Personel İşlemler</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (isAdmin() || isTherapist()): ?>
            <!-- Danışan Yönetimi (admin + terapist) -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#randevularDashboard" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="user-check"></i></span>
                    <span class="menu-text">Danışan Yönetimi</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="randevularDashboard">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="danisan-listele.php" class="side-nav-link">
                                <span class="menu-text">Danışanlar</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="apps-calendar.php" class="side-nav-link">
                                <span class="menu-text">Randevular</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="room_schedule.php" class="side-nav-link">
                                <span class="menu-text">Odalar</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <!-- Hizmet Yönetimi (sadece admin) -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarDanisan" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="package"></i></span>
                    <span class="menu-text">Hizmet Yönetimi</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarDanisan">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="paketler.php" class="side-nav-link">
                                <span class="menu-text">Terapi Paketleri</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="uyelikler.php" class="side-nav-link">
                                <span class="menu-text">Üyelik Türleri</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="seans_turleri.php" class="side-nav-link">
                                <span class="menu-text">Seans Türleri</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (isAdmin() || isTherapist()): ?>
            <!-- Finans (admin + terapist) -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarHizmet" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="dollar-sign"></i></span>
                    <span class="menu-text">Finans</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarHizmet">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="satislar.php" class="side-nav-link">
                                <span class="menu-text">Satışlar</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="sponsorluklar.php" class="side-nav-link">
                                <span class="menu-text">Kurumsal Anlaşmalar</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <!-- Rapor (sadece admin) -->
            <li class="side-nav-title">Rapor</li>
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarTanımlamalar" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="bar-chart-2"></i></span>
                    <span class="menu-text">Gelir Admin</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarTanımlamalar">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="puantaj.php" class="side-nav-link">
                                <span class="menu-text">Puantaj</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="degerlendirme_liste.php" class="side-nav-link">
                                <span class="menu-text">Değerlendirme</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="sponsorluklar_listesi.php" class="side-nav-link">
                                <span class="menu-text">Ücretlendirme</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>


   
    <li class="side-nav-item mt-3">
        <a href="logout.php" class="side-nav-link text-danger">
            <span class="menu-icon"><i data-lucide="log-out"></i></span>
            <span class="menu-text">Çıkış Yap</span>
        </a>
    </li>
   

        </ul>

        <div class="clearfix"></div>
    </div>
</div>
<!-- Sidenav Menu End -->
