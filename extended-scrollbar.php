<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Scrollbar";
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
            $subtitle = "Extended UI";
            $title = "Scrollbar";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Default Scroll</h5>
                            <p class="card-subtitle">Just use data attribute <code>data-simplebar</code>
                                and add <code>max-height: **px</code> oh fix height</p>
                        </div>


                        <div class="card-body pt-2" data-simplebar style="max-height: 250px;">
                            SimpleBar does only one thing: replace the browser's default scrollbar
                            with a custom CSS-styled one without losing performances.
                            Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                            Javascript, causing janks and strange scrolling behaviours...
                            You keep the awesomeness of native scrolling...with a custom scrollbar!
                            <p>SimpleBar <strong>does NOT implement a custom scroll
                                    behaviour</strong>. It keeps the <strong>native</strong>
                                <code>overflow: auto</code> scroll and <strong>only</strong> replace
                                the scrollbar visual appearance.
                            </p>
                            <h5>Design it as you want</h5>
                            <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                                customize it as you want! Or even have multiple style on the same
                                page...or just keep the default style ("Mac OS" scrollbar style).
                            </p>
                            <h5>Lightweight and performant</h5>
                            <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                                scrolling. You keep the performances/behaviours of the native
                                scroll.</p>
                            <h5>Supported everywhere</h5>
                            <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome,
                                Firefox,
                                Safari, Edge, IE11.</p>
                        </div>

                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">RTL Position</h5>
                            <p class="card-subtitle">Just use data attribute
                                <code>data-simplebar data-simplebar-direction='rtl'</code>
                                and add <code>max-height: **px</code> oh fix height
                            </p>
                        </div>

                        <div class="card-body pt-2" data-simplebar data-simplebar-direction='rtl'
                             style="max-height: 250px;">
                            SimpleBar does only one thing: replace the browser's default scrollbar
                            with a custom CSS-styled one without losing performances.
                            Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                            Javascript, causing janks and strange scrolling behaviours...
                            You keep the awesomeness of native scrolling...with a custom scrollbar!
                            <p>SimpleBar <strong>does NOT implement a custom scroll
                                    behaviour</strong>. It keeps the <strong>native</strong>
                                <code>overflow: auto</code> scroll and <strong>only</strong> replace
                                the scrollbar visual appearance.
                            </p>
                            <h5>Design it as you want</h5>
                            <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                                customize it as you want! Or even have multiple style on the same
                                page...or just keep the default style ("Mac OS" scrollbar style).
                            </p>
                            <h5>Lightweight and performant</h5>
                            <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                                scrolling. You keep the performances/behaviours of the native
                                scroll.</p>
                            <h5>Supported everywhere</h5>
                            <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome,
                                Firefox,
                                Safari, Edge, IE11.</p>
                        </div>
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Scroll Size</h5>
                            <p class="card-subtitle">Just use data attribute <code>data-simplebar</code>
                                and add <code>max-height: **px</code> oh fix height</p>
                        </div>

                        <div class="card-body pt-2" data-simplebar data-simplebar-lg style="max-height: 250px;">
                            SimpleBar does only one thing: replace the browser's default scrollbar
                            with a custom CSS-styled one without losing performances.
                            Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                            Javascript, causing janks and strange scrolling behaviours...
                            You keep the awesomeness of native scrolling...with a custom scrollbar!
                            <p>SimpleBar <strong>does NOT implement a custom scroll
                                    behaviour</strong>. It keeps the <strong>native</strong>
                                <code>overflow: auto</code> scroll and <strong>only</strong> replace
                                the scrollbar visual appearance.
                            </p>
                            <h5>Design it as you want</h5>
                            <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                                customize it as you want! Or even have multiple style on the same
                                page...or just keep the default style ("Mac OS" scrollbar style).
                            </p>
                            <h5>Lightweight and performant</h5>
                            <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                                scrolling. You keep the performances/behaviours of the native
                                scroll.</p>
                            <h5>Supported everywhere</h5>
                            <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome,
                                Firefox,
                                Safari, Edge, IE11.</p>
                        </div>

                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Scroll Color</h5>
                            <p class="card-subtitle">Just use data attribute
                                <code>data-simplebar data-simplebar-primary</code>
                                and add <code>max-height: **px</code> oh fix height
                            </p>
                        </div>

                        <div class="card-body pt-2" data-simplebar data-simplebar-primary style="max-height: 250px;">
                            SimpleBar does only one thing: replace the browser's default scrollbar
                            with a custom CSS-styled one without losing performances.
                            Unlike some popular plugins, SimpleBar doesn't mimic scroll with
                            Javascript, causing janks and strange scrolling behaviours...
                            You keep the awesomeness of native scrolling...with a custom scrollbar!
                            <p>SimpleBar <strong>does NOT implement a custom scroll
                                    behaviour</strong>. It keeps the <strong>native</strong>
                                <code>overflow: auto</code> scroll and <strong>only</strong> replace
                                the scrollbar visual appearance.
                            </p>
                            <h5>Design it as you want</h5>
                            <p>SimpleBar uses pure CSS to style the scrollbar. You can easily
                                customize it as you want! Or even have multiple style on the same
                                page...or just keep the default style ("Mac OS" scrollbar style).
                            </p>
                            <h5>Lightweight and performant</h5>
                            <p>Only 6kb minified. SimpleBar doesn't use Javascript to handle
                                scrolling. You keep the performances/behaviours of the native
                                scroll.</p>
                            <h5>Supported everywhere</h5>
                            <p class="mb-0">SimpleBar has been tested on the following browsers: Chrome,
                                Firefox,
                                Safari, Edge, IE11.</p>
                        </div>

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