<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Avatars";
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
            $title = "Avatars";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Sizing - Images</h5>
                            <p class="card-subtitle">
                                Create and group avatars of different sizes and shapes with the css classes.
                                Using Bootstrap's naming convention, you can control size of avatar including
                                standard avatar, or scale it up to different sizes.
                            </p>
                        </div>
                        <div class="card-body pt-2">

                            <div class="row">
                                <div class="col-md-3">
                                    <img src="assets/images/users/avatar-2.jpg" alt="image"
                                         class="img-fluid avatar-xs rounded">
                                    <p>
                                        <code>.avatar-xs</code>
                                    </p>
                                    <img src="assets/images/users/avatar-3.jpg" alt="image"
                                         class="img-fluid avatar-sm rounded mt-2">
                                    <p class="mb-2 mb-sm-0">
                                        <code>.avatar-sm</code>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <img src="assets/images/users/avatar-4.jpg" alt="image"
                                         class="img-fluid avatar-md rounded"/>
                                    <p>
                                        <code>.avatar-md</code>
                                    </p>
                                </div>

                                <div class="col-md-3">
                                    <img src="assets/images/users/avatar-5.jpg" alt="image"
                                         class="img-fluid avatar-lg rounded"/>
                                    <p>
                                        <code>.avatar-lg</code>
                                    </p>
                                </div>

                                <div class="col-md-3">
                                    <img src="assets/images/users/avatar-6.jpg" alt="image"
                                         class="img-fluid avatar-xl rounded"/>
                                    <p class="mb-0">
                                        <code>.avatar-xl</code>
                                    </p>
                                </div>
                            </div> <!-- end row-->
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Sizing - Background Color</h5>
                            <p class="card-subtitle">
                                Using utilities classes of background e.g. <code>bg-*</code> allows you to have any
                                background color as well.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="avatar-xs">
                                            <span class="avatar-title bg-primary rounded">
                                                xs
                                            </span>
                                    </div>
                                    <p class="mb-2 mt-1">
                                        Using <code>.avatar-xs</code>
                                    </p>

                                    <div class="avatar-sm mt-3">
                                            <span class="avatar-title bg-success rounded">
                                                sm
                                            </span>
                                    </div>

                                    <p class="mb-0 mt-1">
                                        Using <code>.avatar-sm</code>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <div class="avatar-md">
                                            <span class="avatar-title bg-danger-subtle text-danger font-18 rounded">
                                                MD
                                            </span>
                                    </div>

                                    <p class="mb-0 mt-1">
                                        Using <code>.avatar-md</code>
                                    </p>
                                </div>

                                <div class="col-md-3">
                                    <div class="avatar-lg">
                                            <span class="avatar-title bg-info font-22 rounded">
                                                LG
                                            </span>
                                    </div>

                                    <p class="mb-0 font-14 mt-1">
                                        Using <code>.avatar-lg</code>
                                    </p>
                                </div>

                                <div class="col-md-3">
                                    <div class="avatar-xl">
                                            <span class="avatar-title bg-warning-subtle text-warning font-24 rounded">
                                                XL
                                            </span>
                                    </div>

                                    <p class="mb-0 mt-1">
                                        Using <code>.avatar-xl</code>
                                    </p>
                                </div>
                            </div> <!-- end row-->
                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Rounded Circle</h5>
                            <p class="card-subtitle">
                                Using an additional class <code>.rounded-circle</code> in <code>&lt;img&gt;</code>
                                element creates the rounded avatar.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="assets/images/users/avatar-7.jpg" alt="image"
                                         class="img-fluid avatar-md rounded-circle"/>
                                    <p class="mt-1">
                                        <code>.avatar-md .rounded-circle</code>
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <img src="assets/images/users/avatar-8.jpg" alt="image"
                                         class="img-fluid avatar-lg rounded-circle"/>
                                    <p>
                                        <code>.avatar-lg .rounded-circle</code>
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <img src="assets/images/users/avatar-9.jpg" alt="image"
                                         class="img-fluid avatar-xl rounded-circle"/>
                                    <p class="mb-0">
                                        <code>.avatar-xl .rounded-circle</code>
                                    </p>
                                </div>
                            </div> <!-- end row-->
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Rounded Circle Background</h5>
                            <p class="card-subtitle">
                                Using an additional class <code>.rounded-circle</code> in <code>&lt;img&gt;</code>
                                element creates the rounded avatar.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="avatar-md">
                                            <span class="avatar-title bg-secondary-subtle text-secondary font-16 rounded-circle">
                                                MD
                                            </span>
                                    </div>

                                    <p class="mb-0 mt-1">
                                        <code>.avatar-md .rounded-circle</code>
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <div class="avatar-lg">
                                            <span class="avatar-title bg-light text-dark font-22 rounded-circle">
                                                LG
                                            </span>
                                    </div>

                                    <p class="mb-0 mt-1">
                                        <code>.avatar-lg .rounded-circle</code>
                                    </p>
                                </div>

                                <div class="col-md-4">
                                    <div class="avatar-xl">
                                            <span class="avatar-title bg-primary-subtle text-primary font-24 rounded-circle">
                                                XL
                                            </span>
                                    </div>

                                    <p class="mb-0">
                                        <code>.avatar-xl .rounded-circle</code>
                                    </p>
                                </div>
                            </div> <!-- end row-->
                        </div>
                    </div>
                </div>
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Avatar Group</h5>
                            <p class="card-subtitle">
                                Create and group avatars of different sizes and shapes with the css classes.
                                Using Bootstrap's naming convention, you can control size of avatar including
                                standard avatar, or scale it up to different sizes.
                            </p>
                        </div>
                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-xl-3">
                                    <!-- Default Group -->
                                    <div class="avatar-group">
                                        <div class="avatar">
                                            <img src="assets/images/users/avatar-4.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar">
                                            <img src="assets/images/users/avatar-5.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar">
                                            <img src="assets/images/users/avatar-3.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar">
                                            <img src="assets/images/users/avatar-8.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar">
                                            <img src="assets/images/users/avatar-2.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                    </div>
                                </div> <!-- end col-->
                                <div class="col-xl-3">
                                    <!-- Default Group (Soft) -->
                                    <div class="avatar-group">
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-success rounded-circle fw-bold">
                                                    D
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-primary rounded-circle fw-bold">
                                                    K
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-secondary rounded-circle fw-bold">
                                                    H
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-warning rounded-circle fw-bold">
                                                    L
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-info rounded-circle fw-bold">
                                                    G
                                                </span>
                                        </div>
                                    </div>
                                </div> <!-- end col-->
                                <div class="col-xl-3">
                                    <!-- Default Group (Soft) -->
                                    <div class="avatar-group">
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-success-subtle text-success rounded-circle fw-bold shadow">
                                                    D
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold shadow">
                                                    K
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fw-bold shadow">
                                                    H
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fw-bold shadow">
                                                    L
                                                </span>
                                        </div>
                                        <div class="avatar avatar-sm">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fw-bold shadow">
                                                    G
                                                </span>
                                        </div>
                                    </div>
                                </div> <!-- end col-->
                                <div class="col-xl-3">
                                    <!-- Default Group (Soft) -->
                                    <div class="avatar-group">
                                        <div class="avatar" data-bs-toggle="tooltip"
                                             data-bs-custom-class="tooltip-secondary" data-bs-placement="top"
                                             title="Vicki">
                                            <img src="assets/images/users/avatar-10.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar avatar-sm" data-bs-toggle="tooltip" data-bs-placement="top"
                                             title="Thomas">
                                                <span class="avatar-title bg-dark rounded-circle fw-bold">
                                                    T
                                                </span>
                                        </div>
                                        <div class="avatar" data-bs-toggle="tooltip"
                                             data-bs-custom-class="tooltip-warning" data-bs-placement="top"
                                             title="Kevin">
                                            <img src="assets/images/users/avatar-7.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar" data-bs-toggle="tooltip" data-bs-custom-class="tooltip-info"
                                             data-bs-placement="top" title="Chris">
                                            <img src="assets/images/users/avatar-1.jpg" alt=""
                                                 class="rounded-circle avatar-sm">
                                        </div>
                                        <div class="avatar avatar-sm" data-bs-toggle="tooltip"
                                             data-bs-custom-class="tooltip-danger" data-bs-placement="top"
                                             title="15 more Users">
                                                <span class="avatar-title bg-danger rounded-circle fw-bold">
                                                    9+
                                                </span>
                                        </div>
                                    </div>
                                </div> <!-- end col-->
                            </div> <!-- end row-->
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Images Shapes</h5>
                            <p class="card-subtitle">
                                Avatars with different sizes and shapes.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="row">
                                <div class="col-sm-2">
                                    <img src="assets/images/small/img-1.jpg" alt="image" class="img-fluid rounded"
                                         width="200"/>
                                    <p class="mb-0">
                                        <code>.rounded</code>
                                    </p>
                                </div>

                                <div class="col-sm-2 text-center">
                                    <img src="assets/images/users/avatar-2.jpg" alt="image" class="img-fluid rounded"
                                         width="120"/>
                                    <p class="mb-0">
                                        <code>.rounded</code>
                                    </p>
                                </div>

                                <div class="col-sm-2 text-center">
                                    <img src="assets/images/users/avatar-7.jpg" alt="image"
                                         class="img-fluid rounded-circle" width="120"/>
                                    <p class="mb-0">
                                        <code>.rounded-circle</code>
                                    </p>
                                </div>

                                <div class="col-sm-2">
                                    <img src="assets/images/small/img-2.jpg" alt="image" class="img-fluid img-thumbnail"
                                         width="200"/>
                                    <p class="mb-0">
                                        <code>.img-thumbnail</code>
                                    </p>
                                </div>
                                <div class="col-sm-2">
                                    <img src="assets/images/users/avatar-8.jpg" alt="image"
                                         class="img-fluid rounded-circle img-thumbnail" width="120"/>
                                    <p class="mb-0">
                                        <code>.rounded-circle .img-thumbnail</code>
                                    </p>
                                </div>
                            </div> <!-- end row-->
                        </div>
                    </div>
                </div>
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