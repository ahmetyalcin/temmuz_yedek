<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Badges";
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
            $title = "Badges";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Default</h5>
                            <p class="card-subtitle ">
                                A simple labeling component. Badges scale to match the size of the immediate parent
                                element by using relative font sizing and <code>em</code> units.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <h1>h1.Example heading <span class="badge bg-secondary-subtle text-secondary">New</span>
                            </h1>
                            <h2>h2.Example heading <span class="badge badge-soft-success">New</span></h2>
                            <h3>h2.Example heading <span class="badge bg-primary">New</span></h3>
                            <h4>h4.Example heading <a href="#" class="badge badge-soft-info">Info Link</a></h4>
                            <h5>h5.Example heading <span class="badge badge-outline-warning">New</span></h5>
                            <h6>h6.Example heading <span class="badge bg-danger">New</span></h6>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Pill Badges</h5>
                            <p class="card-subtitle ">
                                Use the <code>.rounded-pill</code> modifier class to make badges more rounded.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <span class="badge bg-primary rounded-pill">Primary</span>
                            <span class="badge text-bg-secondary rounded-pill">Secondary</span>
                            <span class="badge bg-success rounded-pill">Success</span>
                            <span class="badge bg-danger rounded-pill">Danger</span>
                            <span class="badge bg-warning rounded-pill">Warning</span>
                            <span class="badge bg-info rounded-pill">Info</span>
                            <span class="badge bg-light text-dark rounded-pill">Light</span>
                            <span class="badge bg-dark text-light rounded-pill">Dark</span>

                            <h5 class="mt-4">Lighten Badges</h5>
                            <p class="card-subtitle  mb-2">
                                Use the <code>.badgesoft--*</code> modifier class to make badges lighten.
                            </p>

                            <span class="badge badge-soft-primary rounded-pill">Primary</span>
                            <span class="badge badge-soft-secondary rounded-pill">Secondary</span>
                            <span class="badge badge-soft-success rounded-pill">Success</span>
                            <span class="badge badge-soft-danger rounded-pill">Danger</span>
                            <span class="badge badge-soft-warning rounded-pill">Warning</span>
                            <span class="badge badge-soft-info rounded-pill">Info</span>
                            <span class="badge badge-soft-dark rounded-pill">Dark</span>

                            <h5 class="mt-4">Outline Badges</h5>
                            <p class="card-subtitle  mb-2">
                                Using the <code>.badge-outline-*</code> to quickly create a bordered badges.
                            </p>

                            <span class="badge badge-outline-primary rounded-pill">Primary</span>
                            <span class="badge badge-outline-secondary rounded-pill">Secondary</span>
                            <span class="badge badge-outline-success rounded-pill">Success</span>
                            <span class="badge badge-outline-danger rounded-pill">Danger</span>
                            <span class="badge badge-outline-warning rounded-pill">Warning</span>
                            <span class="badge badge-outline-info rounded-pill">Info</span>
                            <span class="badge badge-outline-dark rounded-pill">Dark</span>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col-->


                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Contextual variations</h5>
                            <p class="card-subtitle ">
                                Add any of the below mentioned modifier classes to change the appearance of a badge.
                                Badge can be more contextual as well. Just use regular convention e.g. <code>badge-*color</code>,
                                <code>bg-primary</code>
                                to have badge with different background.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <span class="badge bg-primary">Primary</span>
                            <span class="badge text-bg-secondary">Secondary</span>
                            <span class="badge bg-success">Success</span>
                            <span class="badge bg-danger">Danger</span>
                            <span class="badge bg-warning">Warning</span>
                            <span class="badge bg-info">Info</span>
                            <span class="badge bg-light text-dark">Light</span>
                            <span class="badge bg-dark text-light">Dark</span>

                            <h5 class="mt-4">Lighten Badges</h5>
                            <p class="card-subtitle  mb-2">
                                Using the <code>.badgesoft--*</code> modifier class, you can have more soften variation.
                            </p>

                            <span class="badge badge-soft-primary">Primary</span>
                            <span class="badge badge-soft-secondary">Secondary</span>
                            <span class="badge badge-soft-success">Success</span>
                            <span class="badge badge-soft-danger">Danger</span>
                            <span class="badge badge-soft-warning">Warning</span>
                            <span class="badge badge-soft-info">Info</span>
                            <span class="badge badge-soft-dark">Dark</span>

                            <h5 class="mt-4">Outline Badges</h5>
                            <p class="card-subtitle  mb-2">
                                Using the <code>.badge-outline-*</code> to quickly create a bordered badges.
                            </p>

                            <span class="badge badge-outline-primary">Primary</span>
                            <span class="badge badge-outline-secondary">Secondary</span>
                            <span class="badge badge-outline-success">Success</span>
                            <span class="badge badge-outline-danger">Danger</span>
                            <span class="badge badge-outline-warning">Warning</span>
                            <span class="badge badge-outline-info">Info</span>
                            <span class="badge badge-outline-dark">Dark</span>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Badge Positioned</h5>
                            <p class="card-subtitle ">
                                Use utilities to modify a <code>.badge</code> and position it in the corner of a
                                link or button.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-primary position-relative">
                                        Inbox
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                99+
                                                <span class="visually-hidden">unread messages</span>
                                            </span>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-primary position-relative">
                                        Profile
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                                <span class="visually-hidden">New alerts</span>
                                            </span>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-success mt-4">
                                        Notifications <span class="badge bg-light text-dark ms-1">4</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

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