<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Toasts";
    include "partials/title-meta.php" ?>

    <!-- Notification css (Toastr) -->
    <link href="assets/vendor/toastr/build/toastr.min.css" rel="stylesheet" type="text/css"/>

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
            $title = "Toasts";
            include "partials/page-title.php" ?>


            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xl-4 col-lg-6">
                                    <div class="control-group">
                                        <div class="controls">
                                            <label class="control-label">Title</label>
                                            <input id="title" type="text" class="input-large form-control"
                                                   placeholder="Enter a title ..."/>
                                            <label class="control-label mt-3">Message</label>
                                            <textarea class="input-large form-control" id="message" rows="3"
                                                      placeholder="Enter a message ..."></textarea>
                                        </div>
                                    </div>
                                    <div class="control-group mt-4">
                                        <div class="checkbox controls">
                                            <input id="closeButton" type="checkbox" value="checked" class="input-mini"/>
                                            <label for="closeButton">
                                                Close Button
                                            </label>
                                        </div>

                                        <div class="checkbox controls">
                                            <input id="addBehaviorOnToastClick" type="checkbox" value="checked"
                                                   class="input-mini"/>
                                            <label for="addBehaviorOnToastClick">
                                                Add behavior on toast click
                                            </label>
                                        </div>

                                        <div class="checkbox controls">
                                            <input id="debugInfo" type="checkbox" value="checked" class="input-mini"/>
                                            <label for="debugInfo">
                                                Debug
                                            </label>
                                        </div>

                                        <div class="controls checkbox">
                                            <input id="progressBar" type="checkbox" value="checked" class="input-mini"/>
                                            <label for="progressBar">
                                                Progress Bar
                                            </label>
                                        </div>

                                        <div class="checkbox controls">
                                            <input id="preventDuplicates" type="checkbox" value="checked"
                                                   class="input-mini"/>
                                            <label for="preventDuplicates">
                                                Prevent Duplicates
                                            </label>
                                        </div>

                                        <div class="checkbox controls">
                                            <input id="addClear" type="checkbox" value="checked" class="input-mini"/>
                                            <label for="addClear">
                                                Add button to force clearing a toast, ignoring focus
                                            </label>
                                        </div>

                                        <div class="checkbox controls">
                                            <input id="newestOnTop" type="checkbox" value="checked" class="input-mini"/>
                                            <label for="newestOnTop">
                                                Newest on top
                                            </label>
                                        </div>


                                    </div>
                                </div>

                                <div class="col-xl-2 col-lg-6">
                                    <div class="control-group mt-4 mt-lg-0" id="toastTypeGroup">
                                        <div class="controls">
                                            <label>Toast Type</label>
                                            <div class="radio radio-success">
                                                <input type="radio" name="radio" id="radio1" value="success" checked>
                                                <label for="radio1">
                                                    Success
                                                </label>
                                            </div>

                                            <div class="radio radio-info">
                                                <input type="radio" name="radio" id="radio2" value="info">
                                                <label for="radio2">
                                                    Info
                                                </label>
                                            </div>

                                            <div class="radio radio-warning">
                                                <input type="radio" name="radio" id="radio3" value="warning">
                                                <label for="radio3">
                                                    Warning
                                                </label>
                                            </div>

                                            <div class="radio radio-danger">
                                                <input type="radio" name="radio" id="radio4" value="error">
                                                <label for="radio4">
                                                    Error
                                                </label>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="control-group mt-2" id="positionGroup">
                                        <div class="controls">
                                            <label>Position</label>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio5" value="toast-top-right"
                                                       checked/>
                                                <label for="radio5">
                                                    Top Right
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio6"
                                                       value="toast-bottom-right"/>
                                                <label for="radio6">
                                                    Bottom Right
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio7"
                                                       value="toast-bottom-left"/>
                                                <label for="radio7">
                                                    Bottom Left
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio8"
                                                       value="toast-top-left"/>
                                                <label for="radio8">
                                                    Top Left
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio9"
                                                       value="toast-top-full-width"/>
                                                <label for="radio9">
                                                    Top Full Width
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio10"
                                                       value="toast-bottom-full-width"/>
                                                <label for="radio10">
                                                    Bottom Full Width
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio11"
                                                       value="toast-top-center"/>
                                                <label for="radio11">
                                                    Top Center
                                                </label>
                                            </div>

                                            <div class="radio radio-custom">
                                                <input type="radio" name="positions" id="radio12"
                                                       value="toast-bottom-center"/>
                                                <label for="radio12">
                                                    Bottom Center
                                                </label>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-md-6">
                                    <div class="control-group mt-4 mt-xl-0">
                                        <div class="controls">
                                            <div>
                                                <label for="showEasing">Show Easing</label>
                                                <input id="showEasing" type="text" placeholder="swing, linear"
                                                       class="input-mini form-control" value="swing"/>
                                            </div>
                                            <div class="mt-3">
                                                <label for="hideEasing">Hide Easing</label>
                                                <input id="hideEasing" type="text" placeholder="swing, linear"
                                                       class="input-mini form-control" value="linear"/>
                                            </div>
                                            <div class="mt-3">
                                                <label for="showMethod">Show Method</label>
                                                <input id="showMethod" type="text" placeholder="show, fadeIn, slideDown"
                                                       class="input-mini form-control" value="fadeIn"/>
                                            </div>
                                            <div class="mt-3">
                                                <label for="hideMethod" class="m-t-10">Hide Method</label>
                                                <input id="hideMethod" type="text" placeholder="hide, fadeOut, slideUp"
                                                       class="input-mini form-control" value="fadeOut"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-3 col-md-6">
                                    <div class="control-group mt-4 mt-xl-0">
                                        <div class="controls">
                                            <div>
                                                <label for="showDuration">Show Duration</label>
                                                <input id="showDuration" type="text" placeholder="ms"
                                                       class="input-mini form-control" value="300"/>
                                            </div>
                                            <div class="mt-3">
                                                <label for="hideDuration">Hide Duration</label>
                                                <input id="hideDuration" type="text" placeholder="ms"
                                                       class="input-mini form-control" value="1000"/>
                                            </div>
                                            <div class="mt-3">
                                                <label for="timeOut">Time out</label>
                                                <input id="timeOut" type="text" placeholder="ms"
                                                       class="input-mini form-control" value="5000"/>
                                            </div>
                                            <div class="mt-3">
                                                <label for="extendedTimeOut">Extended time out</label>
                                                <input id="extendedTimeOut" type="text" placeholder="ms"
                                                       class="input-mini form-control" value="1000"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- end row -->

                            <div class="row mt-2">
                                <div class="col-12">
                                    <div>
                                        <button type="button" class="btn btn-primary waves-effect waves-light me-1 mt-2"
                                                id="showtoast">Show Toast
                                        </button>
                                        <button type="button" class="btn btn-danger waves-effect waves-light me-1 mt-2"
                                                id="cleartoasts">Clear Toasts
                                        </button>
                                        <button type="button" class="btn btn-danger waves-effect waves-light me-1 mt-2"
                                                id="clearlasttoast">Clear Last Toast
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <pre id='toastrOptions' class="alerts-demo mb-0"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- end col -->
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

<!-- Toastr js -->
<script src="assets/vendor/toastr/build/toastr.min.js"></script>

<script src="assets/js/pages/toastr.js"></script>

</body>

</html>