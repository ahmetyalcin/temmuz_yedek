<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Ratings";
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
            $title = "Ratings";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-lg-4">
                                    <div>
                                        <h4 class="header-title">Default</h4>
                                        <p class="sub-header">
                                            You need just to have a <code>div</code> to build the Raty.
                                        </p>
                                        <div id="default" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4 mt-lg-0">
                                        <h4 class="header-title">Score</h4>
                                        <p class="sub-header">
                                            Used when we want to start with a saved rating.
                                        </p>
                                        <div id="score" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4 mt-lg-0">
                                        <h4 class="header-title">Score callback</h4>
                                        <p class="sub-header">
                                            If you need to start you score depending of a dynamic value, you can to
                                            use callback for it.
                                            You can pass any value for it, not necessarily a data- value. You can
                                            use a field value for example.
                                        </p>
                                        <div id="score-callback" class="rating-star" data-score="1"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Score Name</h4>
                                        <p class="sub-header">
                                            Changes the name of the hidden score field.
                                        </p>
                                        <div id="scoreName" class="rating-star"></div>
                                    </div>

                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Number</h4>
                                        <p class="sub-header">
                                            Changes the number of stars.
                                        </p>
                                        <div id="number" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Number callback</h4>
                                        <p class="sub-header">
                                            You can receive the number of stars dynamic using callback to set it.
                                        </p>
                                        <div id="number-callback" class="rating-star" data-number="3"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Number Max</h4>
                                        <p class="sub-header">
                                            Change the maximum of start that can be created.
                                        </p>
                                        <div id="numberMax" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Read Only</h4>
                                        <p class="sub-header">
                                            You can prevent users to vote. It can be applied with or without score
                                            and all stars will receives the hint corresponding of the selected star.
                                            Stop the mouse over the stars to see:
                                        </p>
                                        <div id="readOnly" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Read Only callback</h4>
                                        <p class="sub-header">
                                            You can decide if the rating will be readOnly dynamically returning true of
                                            false on callback.
                                        </p>
                                        <div id="readOnly-callback" class="rating-star" data-number="3"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->


                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">No Rated Message</h4>
                                        <p class="sub-header">
                                            If readOnly is enabled and there is no score, the hint "Not rated yet!"
                                            will be shown for all stars. But you can change it.
                                            Stop the mouse over the star to see:
                                        </p>
                                        <div id="noRatedMsg" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Half Show</h4>
                                        <p class="sub-header">
                                            You can represent a float score as a half star icon.
                                        </p>
                                        <div id="halfShow-true" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Half Show <small>Disabled</small></h4>
                                        <p class="sub-header">
                                            You can decide if the rating will be readOnly dynamically returning true of
                                            false on callback.
                                        </p>
                                        <div id="halfShow-false" class="rating-star"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Round</h4>
                                        <p class="sub-header">
                                            We changed the default interval [x.25 .. x.76], now x.26 will round down
                                            instead of to be a half star.
                                            Remember that the full attribute is used only when halfShow is disabled.
                                        </p>
                                        <div id="round" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Half</h4>
                                        <p class="sub-header">
                                            Enables the half star mouseover to be possible vote with half values.
                                        </p>
                                        <div id="half" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Star Half</h4>
                                        <p class="sub-header">
                                            Changes the name of the half star.
                                        </p>
                                        <div id="starHalf" class="rating-star"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Click</h4>
                                        <p class="sub-header">
                                            Callback to handle the score and the click event on click action.
                                            You can mension the Raty element (DOM) itself using this.
                                        </p>
                                        <div id="click" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Hints</h4>
                                        <p class="sub-header">
                                            Changes the hint for each star by it position on array.
                                        </p>
                                        <div id="hints" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Star Off and Star On</h4>
                                        <p class="sub-header">
                                            Changes the name of the star on and star off.
                                        </p>
                                        <div id="star-off-and-star-on" class="rating-star"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->

                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Cancel</h4>
                                        <p class="sub-header">
                                            Add a cancel button on the left side of the stars to cacel the score.
                                            Inside the click callback the argument code receives the value null when we
                                            click on cancel button.
                                        </p>
                                        <div id="cancel" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Cancel Hint</h4>
                                        <p class="sub-header">
                                            Like the stars, the cancel button have a hint too, and you can change it.
                                        </p>
                                        <div id="cancelHint" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Cancel Place</h4>
                                        <p class="sub-header">
                                            Changes the cancel button to the right side.
                                        </p>
                                        <div id="cancelPlace" class="rating-star"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->


                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Cancel off and Cancel On</h4>
                                        <p class="sub-header">
                                            Changes the on and off icon of the cancel button.
                                        </p>
                                        <div id="cancel-off-and-cancel-on" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Icon Range</h4>
                                        <p class="sub-header">
                                            It's an array of objects where each one represents a custom icon.
                                            The range attribute is until wich position the icon will be displayed.
                                        </p>
                                        <div id="iconRange" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Size</h4>
                                        <p class="sub-header">
                                            The size of the icons are controlled by the css property fs-size as
                                            all icons are text. The plugin tries to calculate the font size
                                            automatically, but should that fail, you can provide a size (in pixels)
                                            with the size option.
                                        </p>
                                        <div id="size-md" class="rating-md rating-star"></div>

                                        <div id="size-lg" class="rating-lg rating-star mt-3"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->


                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Target</h4>
                                        <p class="sub-header">
                                            Some place to display the hints or the cancelHint.<b
                                                    id="target-div-hint"></b>
                                        </p>
                                        <div id="target-div" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Target Type</h4>
                                        <p class="sub-header">
                                            You have the option hint or score to chosse. <b id="targetType-hint"
                                                                                            class="badge badge-success ms-1"></b>
                                        </p>
                                        <div id="targetType" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Target Format</h4>
                                        <p class="sub-header">
                                            You can choose a template to be merged with your hints and displayed on
                                            target.
                                        </p>

                                        <div class="text-center">
                                            <div id="targetFormat" class="rating-md rating-star"></div>

                                            <input type="text" class="form-control mt-3" id="targetFormat-hint"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->


                            <div class="row mt-4">
                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Mouseover</h4>
                                        <p class="sub-header">
                                            You can handle the action on mouseover.
                                        </p>
                                        <div id="mouseover" class="rating-star"></div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="mt-4">
                                        <h4 class="header-title">Mouseout</h4>
                                        <p class="sub-header">
                                            You can handle the action on mouseout.
                                        </p>
                                        <div id="mouseout" class="rating-star"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- end row -->

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


<!-- Raty fa plugin -->
<script src="assets/vendor/admin-resources/ratings/jquery.raty-fa.js"></script>

<script src="assets/js/pages/extended-rating.js"></script>

</body>

</html>