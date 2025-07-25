<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Progress";
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
            $title = "Progress";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Examples</h5>
                            <p class="card-subtitle">A progress bar can be used to show a user how far along he/she is
                                in a process.</p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0"
                                     aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" style="width: 50%" aria-valuenow="50"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" style="width: 75%" aria-valuenow="75"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Height</h5>
                            <p class="card-subtitle">We only set a <code>height</code> value on the
                                <code>.progress</code>, so if you change that value the inner <code>.progress-bar</code>
                                will automatically resize accordingly.
                                Use <code>.progress-sm</code>,<code>.progress-md</code>,<code>.progress-lg</code>,<code>.progress-xl</code>
                                classes.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress mb-2" style="height: 1px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 25%;"
                                     aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2" style="height: 3px;">
                                <div class="progress-bar" role="progressbar" style="width: 25%; height: 20px;"
                                     aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2 progress-sm">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 25%"
                                     aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2 progress-md">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 50%"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-lg mb-2">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 75%"
                                     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-xl">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 38%"
                                     aria-valuenow="38" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Multiple Bars</h5>
                            <p class="card-subtitle">Include multiple progress bars in a progress component if you
                                need.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 15%" aria-valuenow="15"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar bg-success" role="progressbar" style="width: 30%"
                                     aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar bg-info" role="progressbar" style="width: 20%"
                                     aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Animated Stripes</h5>
                            <p class="card-subtitle">The striped gradient can also be animated. Add <code>.progress-bar-animated</code>
                                to <code>.progress-bar</code> to animate the stripes right to left via CSS3 animations.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Steps</h5>
                            <p class="card-subtitle">Add <code>.progress-bar-striped</code> to any
                                <code>.progress-bar</code> to apply a stripe via CSS gradient over the progress bar’s
                                background color.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="position-relative m-4">
                                <div class="progress" style="height: 2px;">
                                    <div class="progress-bar" role="progressbar" style="width: 50%;" aria-valuenow="25"
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <button type="button"
                                        class="position-absolute top-0 start-0 translate-middle btn btn-icon btn-primary rounded-pill">
                                    1
                                </button>
                                <button type="button"
                                        class="position-absolute top-0 start-50 translate-middle btn btn-icon btn-primary rounded-pill">
                                    2
                                </button>
                                <button type="button"
                                        class="position-absolute top-0 start-100 translate-middle btn btn-icon btn-light rounded-pill">
                                    3
                                </button>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Labels</h5>
                            <p class="card-subtitle">Add labels to your progress bars by placing text within the <code>.progress-bar</code>.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25"
                                     aria-valuemin="0" aria-valuemax="100">25%
                                </div>
                            </div>
                            <div class="progress" role="progressbar" aria-label="Example with label" aria-valuenow="10"
                                 aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar overflow-visible text-dark" style="width: 10%">Long label text
                                    for the progress bar, set to a dark color
                                </div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Backgrounds</h5>
                            <p class="card-subtitle">Use background utility classes to change the appearance of
                                individual progress bars.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 25%"
                                     aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 50%"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 75%"
                                     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"
                                     aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-dark" role="progressbar" style="width: 65%"
                                     aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 50%"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Backgrounds (Custom)</h5>
                            <p class="card-subtitle">Use background utility classes to change the appearance of
                                individual progress bars.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress progress-soft mb-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 25%"
                                     aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-soft mb-2">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 50%"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-soft mb-2">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 75%"
                                     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-soft mb-2">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"
                                     aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-soft mb-2">
                                <div class="progress-bar bg-dark" role="progressbar" style="width: 65%"
                                     aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress progress-soft">
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 50%"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->

                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Striped</h5>
                            <p class="card-subtitle">Add <code>.progress-bar-striped</code> to any
                                <code>.progress-bar</code> to apply a stripe via CSS gradient over the progress bar’s
                                background color.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped" role="progressbar" style="width: 10%"
                                     aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped bg-success" role="progressbar"
                                     style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped bg-info" role="progressbar"
                                     style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-striped bg-warning" role="progressbar"
                                     style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped bg-danger" role="progressbar"
                                     style="width: 100%" aria-valuenow="100" aria-valuemin="0"
                                     aria-valuemax="100"></div>
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