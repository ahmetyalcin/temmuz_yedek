<aside class="sidebar show" id="sidebar">
    <div class="sidebar-header">
        <i class='bx bx-plus-medical'></i>
        <span>Thera Vita</span>
    </div>
    <nav class="nav flex-column gap-1">
        <!-- Yönetim -->
        <div class="nav-section mb-2">
            <button class="nav-section-header w-100 text-start border-0 bg-transparent px-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#yonetimMenu">
                <small class="text-gray-400">Yönetim</small>
                <i class='bx bx-chevron-down text-gray-400'></i>
            </button>
            <div class="collapse show" id="yonetimMenu">
                <a href="?page=dashboard" class="nav-link <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class='bx bx-grid-alt'></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Danışan Yönetimi -->
        <div class="nav-section mb-2">
            <button class="nav-section-header w-100 text-start border-0 bg-transparent px-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#danisanMenu">
                <small class="text-gray-400">Danışan Yönetimi</small>
                <i class='bx bx-chevron-down text-gray-400'></i>
            </button>
            <div class="collapse show" id="danisanMenu">
                <a href="?page=danisanlar" class="nav-link <?php echo $active_page == 'danisanlar' ? 'active' : ''; ?>">
                    <i class='bx bx-user'></i>
                    <span>Danışanlar</span>
                </a>
                <a href="?page=randevular" class="nav-link <?php echo $active_page == 'randevular' ? 'active' : ''; ?>">
                    <i class='bx bx-calendar'></i>
                    <span>Randevular</span>
                </a>
                <a href="?page=room_schedule" class="nav-link <?php echo $active_page == 'room_schedule' ? 'active' : ''; ?>">
                    <i class='bx bx-door-open'></i>
                    <span>Odalar</span>
                </a>

            </div>
        </div>

        <!-- Personel Yönetimi -->
        <div class="nav-section mb-2">
            <button class="nav-section-header w-100 text-start border-0 bg-transparent px-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#personelMenu">
                <small class="text-gray-400">Personel Yönetimi</small>
                <i class='bx bx-chevron-down text-gray-400'></i>
            </button>
            <div class="collapse show" id="personelMenu">
                <a href="?page=terapistler" class="nav-link <?php echo $active_page == 'terapistler' ? 'active' : ''; ?>">
                    <i class='bx bx-user-voice'></i>
                    <span>Terapistler</span>
                </a>
            </div>
        </div>

        <!-- Hizmet Yönetimi -->
        <div class="nav-section mb-2">
            <button class="nav-section-header w-100 text-start border-0 bg-transparent px-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#hizmetMenu">
                <small class="text-gray-400">Hizmet Yönetimi</small>
                <i class='bx bx-chevron-down text-gray-400'></i>
            </button>
            <div class="collapse show" id="hizmetMenu">
                <a href="?page=paketler" class="nav-link <?php echo $active_page == 'paketler' ? 'active' : ''; ?>">
                    <i class='bx bx-package'></i>
                    <span>Terapi Paketleri</span>
                </a>
                <a href="?page=uyelikler" class="nav-link <?php echo $active_page == 'uyelikler' ? 'active' : ''; ?>">
                    <i class='bx bx-medal'></i>
                    <span>Üyelik Türleri</span>
                </a>

                <a href="?page=seans_turleri" class="nav-link <?php echo $active_page == 'seans_turleri' ? 'active' : ''; ?>">
                    <i class='bx bx-time'></i>
                    <span>Seans Türleri</span>
                </a>



            </div>
        </div>

        <!-- Finans -->
        <div class="nav-section mb-2">
            <button class="nav-section-header w-100 text-start border-0 bg-transparent px-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#finansMenu">
                <small class="text-gray-400">Finans</small>
                <i class='bx bx-chevron-down text-gray-400'></i>
            </button>
            <div class="collapse show" id="finansMenu">
                <a href="?page=satislar" class="nav-link <?php echo $active_page == 'satislar' ? 'active' : ''; ?>">
                    <i class='bx bx-cart'></i>
                    <span>Satışlar</span>
                </a>
                <a href="?page=sponsorluklar" class="nav-link <?php echo $active_page == 'sponsorluklar' ? 'active' : ''; ?>">
                    <i class='bx bx-building'></i>
                    <span>Kurumsal Anlaşmalar</span>
                </a>
            </div>
        </div>

        <!-- Finans -->
        <div class="nav-section mb-2">
            <button class="nav-section-header w-100 text-start border-0 bg-transparent px-3 py-2 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#ucretMenu">
                <small class="text-gray-400">Gelir Admin</small>
                <i class='bx bx-chevron-down text-gray-400'></i>
            </button>
            <div class="collapse show" id="ucretMenu">
 
            <a href="?page=puantaj" class="nav-link <?php echo $active_page == 'puantaj' ? 'active' : ''; ?>">
                    <i class='bx bx-time'></i>
                    <span>Puantaj</span>
        </a>
        
        <a href="?page=ucret" class="nav-link <?php echo $active_page == 'ucret' ? 'active' : ''; ?>">
                    <i class='bx bx-building'></i>
                    <span>Ücretlendirme</span>
                </a>

        <a href="?page=degerlendirme_liste" class="nav-link <?php echo $active_page == 'degerlendirme_liste' ? 'active' : ''; ?>">
                    <i class='bx bx-building'></i>
                    <span>Değerlendirme</span>
                </a>
            </div>
        </div>

        


    </nav>
    <div class="mt-auto">
        <a href="logout.php" class="nav-link text-danger">
            <i class='bx bx-log-out'></i>
            <span>Çıkış Yap</span>
        </a>
    </div>
</aside>