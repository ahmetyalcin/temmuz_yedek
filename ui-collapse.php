<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Collapse";
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
            $title = "Collapse";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Collapse</h5>
                            <p class="card-subtitle">
                                Bootstrap's collapse provides the way to toggle the visibility of any content or
                                element.
                                Please read the official <a
                                        href="https://getbootstrap.com/docs/5.2/components/collapse/" target="_blank">Bootstrap</a>
                                documentation for a full list of options.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <p>
                                <a class="btn btn-primary" data-bs-toggle="collapse" href="#collapseExample"
                                   aria-expanded="false" aria-controls="collapseExample">
                                    Link with href
                                </a>
                                <button class="btn btn-primary ms-1" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample">
                                    Button with data-bs-target
                                </button>
                            </p>
                            <div class="collapse show" id="collapseExample">
                                <div class="card card-body mb-0">
                                    Anim pariatur cliche reprehenderit, enim eiusmod high life
                                    accusamus terry
                                    richardson ad squid. Nihil anim keffiyeh helvetica, craft beer
                                    labore wes
                                    anderson cred nesciunt sapiente ea proident.
                                </div>
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->


                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Collapse Horizontal</h5>
                            <p class="card-subtitle">The collapse plugin also supports horizontal collapsing. Add the
                                <code>.collapse-horizontal</code> modifier class to transition the <code>width</code>
                                instead of <code>height</code> and set a <code>width</code> on the immediate child
                                element.</p>
                        </div>

                        <div class="card-body pt-2">
                            <p>
                                <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseWidthExample" aria-expanded="false"
                                        aria-controls="collapseWidthExample">
                                    Toggle width collapse
                                </button>
                            </p>
                            <div style="min-height: 105px;">
                                <div class="collapse collapse-horizontal" id="collapseWidthExample">
                                    <div class="card card-body mb-0" style="width: 300px;">
                                        This is some placeholder content for a horizontal collapse. It's hidden by
                                        default and shown when triggered.
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
            <!-- end row-->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Multiple Targets</h5>
                            <p class="card-subtitle">
                                Multiple <code>&lt;button&gt;</code> or <code>&lt;a&gt;</code> can show and hide an
                                element if
                                they each reference it with their <code>href</code> or <code>data-bs-target</code>
                                attribute
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <p>
                                <a class="btn btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1"
                                   role="button" aria-expanded="false" aria-controls="multiCollapseExample1">Toggle
                                    first element</a>
                                <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#multiCollapseExample2" aria-expanded="false"
                                        aria-controls="multiCollapseExample2">Toggle second element
                                </button>
                                <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                                        data-bs-target=".multi-collapse" aria-expanded="false"
                                        aria-controls="multiCollapseExample1 multiCollapseExample2">Toggle both elements
                                </button>
                            </p>
                            <div class="row">
                                <div class="col">
                                    <div class="collapse multi-collapse" id="multiCollapseExample1">
                                        <div class="card card-body mb-0">
                                            Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry
                                            richardson ad squid. Nihil anim keffiyeh helvetica, craft beer labore wes
                                            anderson cred nesciunt sapiente ea proident.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="collapse multi-collapse" id="multiCollapseExample2">
                                        <div class="card card-body mb-0">
                                            Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry
                                            richardson ad squid. Nihil anim keffiyeh helvetica, craft beer labore wes
                                            anderson cred nesciunt sapiente ea proident.
                                        </div>
                                    </div>
                                </div> <!-- end col -->
                            </div> <!-- end row-->
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div> <!-- end row-->


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