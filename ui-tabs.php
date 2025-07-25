<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Tabs";
    include "partials/title-meta.php" ?>

    <?php include 'partials/head-css.php' ?>
</head>

<body>
<!-- Begin page -->
<div class="wrapper">

    <?php include 'partials/sidenav.php' ?>

    <?php include 'partials/topbar.php' ?>

    <!-- ============================================================== -->
    <!-- Start Page Content here -->
    <!-- ============================================================== -->

    <div class="page-content">

        <div class="page-container">

            <?php
            $subtitle = "Base UI";
            $title = "Tabs";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Default Tabs</h5>
                            <p class="card-subtitle">Simple widget of tabbable panes of local content.</p>
                        </div>

                        <div class="card-body pt-2">
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <a href="#home" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Home
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link active">
                                        Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#settings" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Settings
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" data-bs-toggle="tab" aria-expanded="false" class="nav-link disabled">
                                        Disabled
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="home">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                        to our website! We are dedicated to providing you with the best products and
                                        services to enhance your home. Whether you're looking to spruce up your living
                                        space with stylish furniture, create a cozy atmosphere with our selection of
                                        home decor, or tackle those DIY projects with our range of tools and supplies.
                                    </p>
                                </div>
                                <div class="tab-pane show active" id="profile">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>
                                        "Hi there! I'm a passionate individual who loves to explore new ideas and
                                        connect with like-minded people. My interests span a wide range of topics
                                        including technology, literature, travel, and fitness. I believe in the power of
                                        continuous learning and enjoy challenging myself to grow both personally and
                                        professionally.</p>
                                </div>
                                <div class="tab-pane" id="settings">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                        the heart of a bustling city lies a quaint little cafe, nestled between towering
                                        skyscrapers and historic buildings. Its cozy interior boasts warm, earthy tones
                                        accented with splashes of vibrant colors, creating a welcoming atmosphere that
                                        beckons passersby to step inside.</p>
                                </div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Tabs Justified</h5>
                            <p class="card-subtitle">Using class <code>.nav-justified</code>, you can force your <code>tab
                                    menu items</code> to use the full available width.</p>
                        </div>

                        <div class="card-body pt-2">

                            <ul class="nav nav-pills bg-nav-pills nav-justified mb-3">
                                <li class="nav-item">
                                    <a href="#home1" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link rounded-0">
                                        Home
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile1" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link rounded-0 active">
                                        Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#settings1" data-bs-toggle="tab" aria-expanded="false"
                                       class="nav-link rounded-0">
                                        Settings
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="home1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                        to our website! We are dedicated to providing you with the best products and
                                        services to enhance your home. Whether you're looking to spruce up your living
                                        space with stylish furniture, create a cozy atmosphere with our selection of
                                        home decor, or tackle those DIY projects with our range of tools and supplies.
                                    </p>
                                </div>
                                <div class="tab-pane show active" id="profile1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>
                                        "Hi there! I'm a passionate individual who loves to explore new ideas and
                                        connect with like-minded people. My interests span a wide range of topics
                                        including technology, literature, travel, and fitness. I believe in the power of
                                        continuous learning and enjoy challenging myself to grow both personally and
                                        professionally.</p>
                                </div>
                                <div class="tab-pane" id="settings1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                        the heart of a bustling city lies a quaint little cafe, nestled between towering
                                        skyscrapers and historic buildings. Its cozy interior boasts warm, earthy tones
                                        accented with splashes of vibrant colors, creating a welcoming atmosphere that
                                        beckons passersby to step inside.</p>
                                </div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Tabs Vertical Left</h5>
                            <p class="card-subtitle">
                                You can stack your navigation by changing the flex item direction with the <code>.flex-column</code>
                                utility.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-sm-3 mb-2 mb-sm-0">
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist"
                                         aria-orientation="vertical">
                                        <a class="nav-link active show" id="v-pills-home-tab" data-bs-toggle="pill"
                                           href="#v-pills-home" role="tab" aria-controls="v-pills-home"
                                           aria-selected="true">
                                            <i class="ti ti-home font-18 me-1"></i> Home
                                        </a>
                                        <a class="nav-link" id="v-pills-profile-tab" data-bs-toggle="pill"
                                           href="#v-pills-profile" role="tab" aria-controls="v-pills-profile"
                                           aria-selected="false">
                                            <i class="ti ti-user-circle font-18 me-1"></i> Profile
                                        </a>
                                        <a class="nav-link" id="v-pills-settings-tab" data-bs-toggle="pill"
                                           href="#v-pills-settings" role="tab" aria-controls="v-pills-settings"
                                           aria-selected="false">
                                            <i class="ti ti-settings font-18 me-1"></i> Settings
                                        </a>
                                    </div>
                                </div> <!-- end col-->

                                <div class="col-sm-9">
                                    <div class="tab-content" id="v-pills-tabContent">
                                        <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel"
                                             aria-labelledby="v-pills-home-tab">
                                            <p class="mb-0"><span
                                                        class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                                to our website! We are dedicated to providing you with the best products
                                                and services to enhance your home. Whether you're looking to spruce up
                                                your living space with stylish furniture, create a cozy atmosphere with
                                                our selection of home decor, or tackle those DIY projects with our range
                                                of tools and supplies. Explore our wide variety of products and find
                                                exactly what you need to make your house feel like a home. With our
                                                affordable prices and high-quality items.</p>
                                        </div>
                                        <div class="tab-pane fade" id="v-pills-profile" role="tabpanel"
                                             aria-labelledby="v-pills-profile-tab">
                                            <p class="mb-0"><span
                                                        class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>Hi
                                                there! I'm a passionate individual who loves to explore new ideas and
                                                connect with like-minded people. My interests span a wide range of
                                                topics including technology, literature, travel, and fitness. I believe
                                                in the power of continuous learning and enjoy challenging myself to grow
                                                both personally and professionally. Outside of my pursuits, you can
                                                often find me immersed in a good book, exploring the outdoors, or
                                                experimenting in the kitchen.</p>
                                        </div>
                                        <div class="tab-pane fade" id="v-pills-settings" role="tabpanel"
                                             aria-labelledby="v-pills-settings-tab">
                                            <p class="mb-0"><span
                                                        class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                                the heart of a bustling city lies a quaint little cafe, nestled between
                                                towering skyscrapers and historic buildings. Its cozy interior boasts
                                                warm, earthy tones accented with splashes of vibrant colors, creating a
                                                welcoming atmosphere that beckons passersby to step inside.</p>
                                        </div>
                                    </div> <!-- end tab-content-->
                                </div> <!-- end col-->
                            </div>
                            <!-- end row-->

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Tabs Vertical Right</h5>
                            <p class="card-subtitle">
                                You can stack your navigation by changing the flex item direction with the <code>.flex-column</code>
                                utility.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-sm-9">
                                    <div class="tab-content" id="v-pills-tabContent-right">
                                        <div class="tab-pane fade active show" id="v-pills-home2" role="tabpanel"
                                             aria-labelledby="v-pills-home-tab">
                                            <p class="mb-0"><span
                                                        class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                                to our website! We are dedicated to providing you with the best products
                                                and services to enhance your home. Whether you're looking to spruce up
                                                your living space with stylish furniture, create a cozy atmosphere with
                                                our selection of home decor, or tackle those DIY projects with our range
                                                of tools and supplies. Explore our wide variety of products and find
                                                exactly what you need to make your house feel like a home. With our
                                                affordable prices and high-quality items.</p>
                                        </div>
                                        <div class="tab-pane fade" id="v-pills-profile2" role="tabpanel"
                                             aria-labelledby="v-pills-profile-tab">
                                            <p class="mb-0"><span
                                                        class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>Hi
                                                there! I'm a passionate individual who loves to explore new ideas and
                                                connect with like-minded people. My interests span a wide range of
                                                topics including technology, literature, travel, and fitness. I believe
                                                in the power of continuous learning and enjoy challenging myself to grow
                                                both personally and professionally. Outside of my pursuits, you can
                                                often find me immersed in a good book, exploring the outdoors, or
                                                experimenting in the kitchen.</p>
                                        </div>
                                        <div class="tab-pane fade" id="v-pills-settings2" role="tabpanel"
                                             aria-labelledby="v-pills-settings-tab">
                                            <p class="mb-0"><span
                                                        class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                                the heart of a bustling city lies a quaint little cafe, nestled between
                                                towering skyscrapers and historic buildings. Its cozy interior boasts
                                                warm, earthy tones accented with splashes of vibrant colors, creating a
                                                welcoming atmosphere that beckons passersby to step inside.</p>
                                        </div>
                                    </div> <!-- end tabcontent-->
                                </div> <!-- end col-->

                                <div class="col-sm-3 mt-2 mt-sm-0">
                                    <div class="nav flex-column nav-pills nav-pills-secondary" id="v-pills-tab2"
                                         role="tablist" aria-orientation="vertical">
                                        <a class="nav-link active show" id="v-pills-home-tab2" data-bs-toggle="pill"
                                           href="#v-pills-home2" role="tab" aria-controls="v-pills-home2"
                                           aria-selected="true">
                                            <i class="ti ti-home font-18 me-1"></i>
                                            <span class="d-none d-md-inline-block">Home</span>
                                        </a>
                                        <a class="nav-link" id="v-pills-profile-tab2" data-bs-toggle="pill"
                                           href="#v-pills-profile2" role="tab" aria-controls="v-pills-profile2"
                                           aria-selected="false">
                                            <i class="ti ti-user-circle font-18 me-1"></i>
                                            <span class="d-none d-md-inline-block">Profile</span>
                                        </a>
                                        <a class="nav-link" id="v-pills-settings-tab2" data-bs-toggle="pill"
                                           href="#v-pills-settings2" role="tab" aria-controls="v-pills-settings2"
                                           aria-selected="false">
                                            <i class="ti ti-settings font-18 me-1"></i>
                                            <span class="d-none d-md-inline-block">Settings</span>
                                        </a>
                                    </div>
                                </div> <!-- end col-->
                            </div> <!-- end row-->

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Tabs Bordered</h5>
                            <p class="card-subtitle">
                                The navigation item can have a simple bottom border as well. Just specify the class
                                <code>.nav-bordered</code>.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <ul class="nav nav-tabs nav-bordered mb-3">
                                <li class="nav-item">
                                    <a href="#home-b1" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="ti ti-home font-18 me-md-1"></i>
                                        <span class="d-none d-md-inline-block">Home</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile-b1" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link active">
                                        <i class="ti ti-user-circle font-18 me-md-1"></i>
                                        <span class="d-none d-md-inline-block">Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#settings-b1" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="ti ti-settings font-18 me-md-1"></i>
                                        <span class="d-none d-md-inline-block">Settings</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="home-b1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                        to our website! We are dedicated to providing you with the best products and
                                        services to enhance your home. Whether you're looking to spruce up your living
                                        space with stylish furniture, create a cozy atmosphere with our selection of
                                        home decor, or tackle those DIY projects with our range of tools and supplies.
                                    </p>
                                </div>
                                <div class="tab-pane show active" id="profile-b1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>
                                        "Hi there! I'm a passionate individual who loves to explore new ideas and
                                        connect with like-minded people. My interests span a wide range of topics
                                        including technology, literature, travel, and fitness. I believe in the power of
                                        continuous learning and enjoy challenging myself to grow both personally and
                                        professionally.</p>
                                </div>
                                <div class="tab-pane" id="settings-b1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                        the heart of a bustling city lies a quaint little cafe, nestled between towering
                                        skyscrapers and historic buildings. Its cozy interior boasts warm, earthy tones
                                        accented with splashes of vibrant colors, creating a welcoming atmosphere that
                                        beckons passersby to step inside.</p>
                                </div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Tabs Bordered Justified</h5>
                            <p class="card-subtitle">
                                The navigation item with a simple bottom border and justified</code>
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <ul class="nav nav-tabs nav-justified nav-bordered nav-bordered-danger mb-3">
                                <li class="nav-item">
                                    <a href="#home-b2" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="ti ti-home font-18 me-md-1"></i>
                                        <span class="d-none d-md-inline-block">Home</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile-b2" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link active">
                                        <i class="ti ti-user-circle font-18 me-md-1"></i>
                                        <span class="d-none d-md-inline-block">Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#settings-b2" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="ti ti-settings font-18 me-md-1"></i>
                                        <span class="d-none d-md-inline-block">Settings</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="home-b2">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                        to our website! We are dedicated to providing you with the best products and
                                        services to enhance your home. Whether you're looking to spruce up your living
                                        space with stylish furniture, create a cozy atmosphere with our selection of
                                        home decor, or tackle those DIY projects with our range of tools and supplies.
                                    </p>
                                </div>
                                <div class="tab-pane show active" id="profile-b2">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>
                                        "Hi there! I'm a passionate individual who loves to explore new ideas and
                                        connect with like-minded people. My interests span a wide range of topics
                                        including technology, literature, travel, and fitness. I believe in the power of
                                        continuous learning and enjoy challenging myself to grow both personally and
                                        professionally.</p>
                                </div>
                                <div class="tab-pane" id="settings-b2">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                        the heart of a bustling city lies a quaint little cafe, nestled between towering
                                        skyscrapers and historic buildings. Its cozy interior boasts warm, earthy tones
                                        accented with splashes of vibrant colors, creating a welcoming atmosphere that
                                        beckons passersby to step inside.</p>
                                </div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Icons Tabs</h5>
                            <p class="card-subtitle">
                                The navigation item can have a simple bottom border as well. Just specify the class
                                <code>.nav-bordered</code>.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <ul class="nav nav-tabs nav-bordered nav-bordered-success mb-3">
                                <li class="nav-item">
                                    <a href="#home-i1" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <iconify-icon icon="solar:home-2-bold-duotone"
                                                      class="fs-24 align-middle"></iconify-icon>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile-i1" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link active">
                                        <iconify-icon icon="solar:user-id-bold-duotone"
                                                      class="fs-24 align-middle"></iconify-icon>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#settings-i1" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <iconify-icon icon="solar:settings-bold-duotone"
                                                      class="fs-24 align-middle"></iconify-icon>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="home-i1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                        to our website! We are dedicated to providing you with the best products and
                                        services to enhance your home. Whether you're looking to spruce up your living
                                        space with stylish furniture, create a cozy atmosphere with our selection of
                                        home decor, or tackle those DIY projects with our range of tools and supplies.
                                    </p>
                                </div>
                                <div class="tab-pane show active" id="profile-i1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>
                                        "Hi there! I'm a passionate individual who loves to explore new ideas and
                                        connect with like-minded people. My interests span a wide range of topics
                                        including technology, literature, travel, and fitness. I believe in the power of
                                        continuous learning and enjoy challenging myself to grow both personally and
                                        professionally.</p>
                                </div>
                                <div class="tab-pane" id="settings-i1">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                        the heart of a bustling city lies a quaint little cafe, nestled between towering
                                        skyscrapers and historic buildings. Its cozy interior boasts warm, earthy tones
                                        accented with splashes of vibrant colors, creating a welcoming atmosphere that
                                        beckons passersby to step inside.</p>
                                </div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header card-tabs d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="header-title">Card with Tabs</h5>
                            </div>
                            <ul class="nav nav-tabs nav-justified card-header-tabs nav-bordered">
                                <li class="nav-item">
                                    <a href="#home-ct" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="ti ti-home d-md-none d-block"></i>
                                        <span class="d-none d-md-block">Home</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile-ct" data-bs-toggle="tab" aria-expanded="true"
                                       class="nav-link active">
                                        <i class="ti ti-user-circle d-md-none d-block"></i>
                                        <span class="d-none d-md-block">Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#settings-ct" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        <i class="ti ti-settings d-md-none d-block"></i>
                                        <span class="d-none d-md-block">Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body pt-2">
                            <div class="tab-content">
                                <div class="tab-pane" id="home-ct">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-info-subtle text-info float-start">H</span>Welcome
                                        to our website! We are dedicated to providing you with the best products and
                                        services to enhance your home. Whether you're looking to spruce up your living
                                        space with stylish furniture, create a cozy atmosphere with our selection of
                                        home decor, or tackle those DIY projects with our range of tools and supplies.
                                    </p>
                                </div>
                                <div class="tab-pane show active" id="profile-ct">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-danger-subtle text-danger float-start">P</span>
                                        "Hi there! I'm a passionate individual who loves to explore new ideas and
                                        connect with like-minded people. My interests span a wide range of topics
                                        including technology, literature, travel, and fitness. I believe in the power of
                                        continuous learning and enjoy challenging myself to grow both personally and
                                        professionally.</p>
                                </div>
                                <div class="tab-pane" id="settings-ct">
                                    <p class="mb-0"><span
                                                class="px-1 rounded me-1 fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start">S</span>In
                                        the heart of a bustling city lies a quaint little cafe, nestled between towering
                                        skyscrapers and historic buildings. Its cozy interior boasts warm, earthy tones
                                        accented with splashes of vibrant colors, creating a welcoming atmosphere that
                                        beckons passersby to step inside.</p>
                                </div>
                            </div>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

        </div> <!-- container -->


        <?php include 'partials/footer.php' ?>

    </div>

    <!-- ============================================================== -->
    <!-- End Page content -->
    <!-- ============================================================== -->

</div>
<!-- END wrapper -->

<?php include 'partials/customizer.php' ?>

<?php include 'partials/footer-scripts.php' ?>

</body>

</html>