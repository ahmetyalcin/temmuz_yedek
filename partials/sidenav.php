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

               <?php if (isTherapist()): ?>
            <!-- Dashboard (herkes görür) -->
            <li class="side-nav-item">
                <a href="dashboard_terapist.php" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="home"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>
                 <?php endif; ?>

            <?php if (isAdmin()): ?>
            <!-- Personel Yönetimi (sadece admin) -->
                  <li class="side-nav-item">
                <a href="dashboard.php" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="home"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>



                    <?php if (isAdmin()): ?>
        <!-- SMS/WhatsApp Yönetimi menü item -->
        <li class="side-nav-item">
            <a href="sms_whatsapp_yonetimi.php" class="side-nav-link">
                <span class="menu-icon"><i data-lucide="message-circle"></i></span>
                <span class="menu-text">SMS/WhatsApp</span>
                <span class="badge bg-info ms-2">YENİ</span>
            </a>
        </li>
        <?php endif; ?>

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
                            <a href="room_schedule.php" class="side-nav-link">
                                <span class="menu-text">Randevular</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="room_lock_management.php" class="side-nav-link">
                                <span class="menu-text">Oda Kapatma</span>
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





            <?php if (isAdmin()): ?>
            <!-- Rapor (sadece admin) -->
            
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarTanımlamalarMuh" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="bar-chart-2"></i></span>
                    <span class="menu-text">Genel Muhasebe</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarTanımlamalarMuh">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="gelir-raporu.php" class="side-nav-link">
                                <span class="menu-text">Gelir İşlemler</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="gider-listesi.php" class="side-nav-link">
                                <span class="menu-text">Gider İşlemler</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="kar-zarar-analizi.php" class="side-nav-link">
                                <span class="menu-text">Kar-Zarar Analizi</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>



 <?php if (isAdmin()): ?>
            <!-- İzin Yönetimi (sadece admin) -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarIzinYonetimi" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="calendar-check"></i></span>
                    <span class="menu-text">İzin Yönetimi</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarIzinYonetimi">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="izin-yonetimi.php" class="side-nav-link">
                                <span class="menu-text">İzin Talepleri</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="yillik-izin-hesaplama.php" class="side-nav-link">
                                <span class="menu-text">Yıllık İzin Hesaplama</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Bordro & Maaş Yönetimi (sadece admin) -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarBordroYonetimi" class="side-nav-link">
                    <span class="menu-icon"><i data-lucide="calculator"></i></span>
                    <span class="menu-text">Bordro Yönetimi</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarBordroYonetimi">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="bordro-yonetimi.php" class="side-nav-link">
                                <span class="menu-text">Bordro İşlemleri</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="personel_maas_ayarlari.php" class="side-nav-link">
                                <span class="menu-text">Maaş Ayarları</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

<li>

            <?php endif; ?>


   

                  <?php if (isAdmin()): ?>
       
        <li class="side-nav-item">
            <a href="belgeler.php" class="side-nav-link">
                <span class="menu-icon"><i data-lucide="message-circle"></i></span>
                <span class="menu-text">Belge Yönetimi</span>
         
            </a>
        </li>
        <?php endif; ?>

       <?php if (isAdmin()): ?>
  <!-- Alternatif olarak, basit menü yapısı -->
<li class="side-nav-title side-nav-item">Raporlar</li>

<li class="side-nav-item">
    <a href="paket_kullanim_raporu.php" class="side-nav-link">
        <i class="uil-package"></i>
        <span>Paket Kullanım Raporu</span>
    </a>
</li>

<li class="side-nav-item">
    <a href="danisan_aktivite_raporu.php" class="side-nav-link">
        <i class="uil-user-check"></i>
        <span>Danışan Aktivite Raporu</span>
    </a>
</li>


<li class="side-nav-item">
    <a href="toplu-seans-raporu.php" class="side-nav-link">
        <i class="uil-user-check"></i>
        <span>Toplu Seans Raporu</span>
    </a>
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
