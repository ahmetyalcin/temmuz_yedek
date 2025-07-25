<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Alerts";
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
            $title = "Alerts";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Default Alert</h5>
                            <p class="card-subtitle">
                                For proper styling, use one of the eight
                                <strong>required</strong> contextual classes (e.g.,
                                <code>.alert-success</code>). For background color use class
                                <code>.bg-* </code>, <code>.text-white </code>
                            </p>
                        </div>
                        <div class="card-body pt-2">
                            <div class="alert alert-primary d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:bell-bing-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Primary - </strong> A simple primary alert — check
                                    it out!
                                </div>
                            </div>
                            <div class="alert alert-secondary d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:bicycling-round-bold-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Secondary - </strong> A simple secondary alert —
                                    check it out!
                                </div>
                            </div>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:check-read-line-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Success - </strong> A simple success alert — check
                                    it out!
                                </div>
                            </div>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:danger-triangle-bold-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Error - </strong> A simple danger alert — check it
                                    out!
                                </div>
                            </div>
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:shield-warning-line-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Warning - </strong> A simple warning alert—check
                                    it out!
                                </div>
                            </div>
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:info-circle-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Info - </strong> A simple info alert—check it out!
                                </div>
                            </div>
                            <div class="alert alert-light d-flex align-items-center" role="alert">
                                <iconify-icon icon="solar:atom-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Light - </strong> A simple light alert—check it
                                    out!
                                </div>
                            </div>
                            <div class="alert alert-dark d-flex align-items-center mb-0" role="alert">
                                <iconify-icon icon="solar:balloon-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Dark - </strong> A simple dark alert—check it out!
                                </div>
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Dismissing Alert</h5>
                            <p class="card-subtitle">
                                Add a dismiss button and the <code>.alert-dismissible</code> class, which adds
                                extra padding to the right of the alert
                                and positions the <code>.btn-close</code> button.
                            </p>
                        </div>
                        <div class="card-body pt-2">

                            <div class="alert alert-primary text-bg-primary alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:bell-bing-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Primary - </strong> A simple primary alert — check
                                    it out!
                                </div>
                            </div>

                            <div class="alert alert-secondary text-bg-secondary alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:bicycling-round-bold-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Secondary - </strong> A simple secondary alert —
                                    check it out!
                                </div>
                            </div>

                            <div class="alert alert-success text-bg-success alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:check-read-line-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Success - </strong> A simple success alert — check
                                    it out!
                                </div>
                            </div>

                            <div class="alert alert-danger text-bg-danger alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:danger-triangle-bold-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Error - </strong> A simple danger alert — check it
                                    out!
                                </div>
                            </div>

                            <div class="alert alert-warning text-bg-warning alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:shield-warning-line-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Warning - </strong> A simple warning alert—check
                                    it out!
                                </div>
                            </div>

                            <div class="alert alert-info text-bg-info alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:info-circle-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Info - </strong> A simple info alert—check it out!
                                </div>
                            </div>

                            <div class="alert alert-light text-bg-light alert-dismissible d-flex align-items-center"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:atom-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Light - </strong> A simple light alert—check it
                                    out!
                                </div>
                            </div>

                            <div class="alert alert-dark text-bg-dark alert-dismissible d-flex align-items-center mb-0"
                                 role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:balloon-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Dark - </strong> A simple dark alert—check it out!
                                </div>
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Link Color</h5>
                            <p class="card-subtitle">
                                Use the <code>.alert-link</code> utility class to quickly provide matching
                                colored links within any alert.
                            </p>
                        </div>
                        <div class="card-body pt-2">

                            <div class="alert alert-primary" role="alert">
                                A simple primary alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-secondary" role="alert">
                                A simple secondary alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-success" role="alert">
                                A simple success alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-danger" role="alert">
                                A simple danger alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-warning" role="alert">
                                A simple warning alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-info" role="alert">
                                A simple info alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-light" role="alert">
                                A simple light alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                            <div class="alert alert-dark" role="alert">
                                A simple dark alert with <a href="#" class="alert-link">an example
                                    link</a>. Give it a click if you like.
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Custom Alerts</h5>
                            <p class="card-subtitle">
                                Display alert with transparent background and with contextual text color. Use
                                classes
                                <code>.bg-white</code>, and <code>.text-*</code>. E.g. <code>bg-white
                                    text-primary</code>.
                            </p>
                        </div>
                        <div class="card-body pt-2">

                            <div class="alert alert-primary alert-dismissible d-flex align-items-center border-2 border border-primary"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:bell-bing-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Primary - </strong> A simple primary alert — check
                                    it out!
                                </div>
                            </div>
                            <div class="alert alert-secondary alert-dismissible d-flex align-items-center border-2 border border-secondary"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:bicycling-round-bold-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Secondary - </strong> A simple secondary alert —
                                    check it out!
                                </div>
                            </div>
                            <div class="alert alert-success alert-dismissible d-flex align-items-center border-2 border border-success"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:check-read-line-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Success - </strong> A simple success alert — check
                                    it out!
                                </div>
                            </div>
                            <div class="alert alert-danger alert-dismissible d-flex align-items-center border-2 border border-danger"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:danger-triangle-bold-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Error - </strong> A simple danger alert — check it
                                    out!
                                </div>
                            </div>
                            <div class="alert alert-warning alert-dismissible d-flex align-items-center border border-warning"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:shield-warning-line-duotone"
                                              class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Warning - </strong> A simple warning alert—check
                                    it out!
                                </div>
                            </div>
                            <div class="alert alert-info alert-dismissible d-flex align-items-center border border-info"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:info-circle-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Info - </strong> A simple info alert—check it out!
                                </div>
                            </div>
                            <div class="alert alert-light alert-dismissible d-flex align-items-center border border-light"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:atom-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Light - </strong> A simple light alert—check it
                                    out!
                                </div>
                            </div>
                            <div class="alert alert-dark alert-dismissible d-flex align-items-center border border-dark mb-0"
                                 role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                <iconify-icon icon="solar:balloon-bold-duotone" class="font-20 me-1"></iconify-icon>
                                <div class="lh-1"><strong>Dark - </strong> A simple dark alert—check it out!
                                </div>
                            </div>

                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Additional Content</h5>
                            <p class="card-subtitle">
                                Alerts can also contain additional HTML elements like headings, paragraphs and
                                dividers.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <div class="alert alert-success p-3" role="alert">
                                <h4 class="alert-heading">Well done!</h4>
                                <p>Aww yeah, you successfully read this important alert message. This
                                    example text is going to run a bit longer so that you can see how
                                    spacing within an alert works with this kind of content.</p>
                                <hr class="border-success border-opacity-25">
                                <p class="mb-0">Whenever you need to, be sure to use margin utilities to
                                    keep things nice and tidy.</p>
                            </div>

                            <div class="alert alert-secondary p-3 d-flex" role="alert">
                                <iconify-icon icon="solar:bell-bing-bold-duotone" class="fs-1 me-2"></iconify-icon>
                                <div>
                                    <h4 class="alert-heading">Well done!</h4>
                                    <p>Aww yeah, you successfully read this important alert message. This
                                        example text is going to run a bit longer so that you can see how
                                        spacing within an alert works with this kind of content.</p>
                                    <hr class="border-secondary border-opacity-25">
                                    <p class="mb-0">Whenever you need to, be sure to use margin utilities to
                                        keep things nice and tidy.</p>
                                </div>
                            </div>

                            <div class="alert alert-primary d-flex p-3 mb-0" role="alert">
                                <iconify-icon icon="solar:atom-bold-duotone" class="fs-1 me-2"></iconify-icon>
                                <div>
                                    <h4 class="alert-heading">Thank you!</h4>
                                    <p>Aww yeah, you successfully read this important alert message. This
                                        example text is going to run a bit longer so that you can see how
                                        spacing within an alert works with this kind of content.</p>
                                    <button type="button" class="btn btn-primary btn-sm">Close</button>
                                </div>
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Live Alert</h5>
                            <p class="card-subtitle">Click the button below to show an alert (hidden with inline styles
                                to start), then dismiss (and destroy) it with the built-in close button.</p>
                        </div>

                        <div class="card-body pt-2">
                            <div id="liveAlertPlaceholder"></div>
                            <button type="button" class="btn btn-primary" id="liveAlertBtn">Show live alert</button>
                        </div>
                    </div>
                </div>
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