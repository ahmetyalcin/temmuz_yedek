<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Checkbox & Radio";
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
            $title = "Checkbox and Radio";
            include "partials/page-title.php" ?>
            <!-- CHECKBOX -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4 col-md-6">
                                    <div>
                                        <h4 class="header-title">Basic</h4>

                                        <p class="sub-header">
                                            Supports bootstrap brand colors: <code>.checkbox-primary</code>, <code>.checkbox-info</code>
                                            etc.
                                        </p>
                                        <div class="checkbox">
                                            <input id="checkbox0" type="checkbox">
                                            <label for="checkbox0">
                                                Default
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-primary">
                                            <input id="checkbox2" type="checkbox" checked>
                                            <label for="checkbox2">
                                                Primary
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-success">
                                            <input id="checkbox3" type="checkbox">
                                            <label for="checkbox3">
                                                Success
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-info">
                                            <input id="checkbox4" type="checkbox">
                                            <label for="checkbox4">
                                                Info
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-warning">
                                            <input id="checkbox5" type="checkbox" checked>
                                            <label for="checkbox5">
                                                Warning
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-danger">
                                            <input id="checkbox6" type="checkbox" checked>
                                            <label for="checkbox6">
                                                Danger
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-purple">
                                            <input id="checkbox6a" type="checkbox">
                                            <label for="checkbox6a">
                                                Purple
                                            </label>
                                        </div>

                                        <div class="checkbox checkbox-pink">
                                            <input id="checkbox6b" type="checkbox" checked>
                                            <label for="checkbox6b">
                                                Pink
                                            </label>
                                        </div>

                                        <div class="checkbox checkbox-dark">
                                            <input id="checkbox6c" type="checkbox">
                                            <label for="checkbox6c">
                                                Dark
                                            </label>
                                        </div>
                                    </div>


                                    <p class="sub-header mt-3">Checkboxes without label text
                                        <code>.checkbox-single</code></p>
                                    <div class="checkbox checkbox-single">
                                        <input type="checkbox" id="singleCheckbox1" value="option1"
                                               aria-label="Single checkbox One">
                                        <label></label>
                                    </div>
                                    <div class="checkbox checkbox-primary checkbox-single">
                                        <input type="checkbox" id="singleCheckbox2" value="option2" checked
                                               aria-label="Single checkbox Two">
                                        <label></label>
                                    </div>


                                    <p class="sub-header mt-3">Inline checkboxes</p>
                                    <div class="checkbox form-check-inline">
                                        <input type="checkbox" id="inlineCheckbox1" value="option1">
                                        <label for="inlineCheckbox1"> Inline One </label>
                                    </div>
                                    <div class="checkbox checkbox-success form-check-inline">
                                        <input type="checkbox" id="inlineCheckbox2" value="option1" checked>
                                        <label for="inlineCheckbox2"> Inline Two </label>
                                    </div>
                                    <div class="checkbox checkbox-pink form-check-inline">
                                        <input type="checkbox" id="inlineCheckbox3" value="option1">
                                        <label for="inlineCheckbox3"> Inline Three </label>
                                    </div>
                                </div>


                                <div class="col-xl-4 col-md-6">
                                    <div class="mt-4 mt-md-0">
                                        <h4 class="header-title">Circled</h4>

                                        <p class="sub-header">
                                            <code>.checkbox-circle</code> for roundness.
                                        </p>
                                        <div class="checkbox checkbox-circle">
                                            <input id="checkbox7" type="checkbox">
                                            <label for="checkbox7">
                                                Simply Rounded
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-info checkbox-circle">
                                            <input id="checkbox8" type="checkbox" checked>
                                            <label for="checkbox8">
                                                Info
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-primary checkbox-circle">
                                            <input id="checkbox-9" type="checkbox">
                                            <label for="checkbox-9">
                                                Primary
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-success checkbox-circle">
                                            <input id="checkbox-10" type="checkbox" checked>
                                            <label for="checkbox-10">
                                                Success
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-warning checkbox-circle">
                                            <input id="checkbox-11" type="checkbox">
                                            <label for="checkbox-11">
                                                Warning
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-danger checkbox-circle">
                                            <input id="checkbox-12" type="checkbox" checked>
                                            <label for="checkbox-12">
                                                Danger
                                            </label>
                                        </div>

                                        <div class="checkbox checkbox-purple checkbox-circle">
                                            <input id="checkbox-13" type="checkbox" checked>
                                            <label for="checkbox-13">
                                                Purple
                                            </label>
                                        </div>

                                        <div class="checkbox checkbox-pink checkbox-circle">
                                            <input id="checkbox-14" type="checkbox">
                                            <label for="checkbox-14">
                                                Pink
                                            </label>
                                        </div>

                                        <div class="checkbox checkbox-dark checkbox-circle">
                                            <input id="checkbox-15" type="checkbox" checked>
                                            <label for="checkbox-15">
                                                Dark
                                            </label>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-lg-4 col-md-6">
                                    <div class="mt-4 mt-lg-0">
                                        <h4 class="header-title">Disabled</h4>

                                        <p class="sub-header">
                                            Disabled state also supported.
                                        </p>
                                        <div class="checkbox">
                                            <input id="checkbox9" type="checkbox" disabled>
                                            <label for="checkbox9">
                                                Can't check this
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-primary">
                                            <input id="checkbox10" type="checkbox" disabled checked>
                                            <label for="checkbox10">
                                                This too
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-warning checkbox-circle">
                                            <input id="checkbox110" type="checkbox" disabled checked>
                                            <label for="checkbox110">
                                                And this
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-info">
                                            <input id="checkbox12" type="checkbox" disabled checked>
                                            <label for="checkbox12">
                                                Can't check this
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-pink">
                                            <input id="checkbox13" type="checkbox" disabled checked>
                                            <label for="checkbox13">
                                                This too
                                            </label>
                                        </div>
                                        <div class="checkbox checkbox-purple checkbox-circle">
                                            <input id="checkbox14" type="checkbox" disabled checked>
                                            <label for="checkbox14">
                                                And this
                                            </label>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- end row -->


            <!-- RADIOS -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4 col-md-6">
                                    <h4 class="header-title">Basic</h4>

                                    <p class="sub-header">
                                        Supports bootstrap brand colors: <code>.radio-primary</code>, <code>.radio-danger</code>
                                        etc.
                                    </p>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="radio">
                                                <input type="radio" name="radio" id="radio1" value="option1" checked>
                                                <label for="radio1">
                                                    Default
                                                </label>
                                            </div>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="radio" id="radio3" value="option3">
                                                <label for="radio3">
                                                    Primary
                                                </label>
                                            </div>
                                            <div class="radio radio-success">
                                                <input type="radio" name="radio" id="radio4" value="option4">
                                                <label for="radio4">
                                                    Success
                                                </label>
                                            </div>
                                            <div class="radio radio-info">
                                                <input type="radio" name="radio" id="radio5" value="option5">
                                                <label for="radio5">
                                                    Info
                                                </label>
                                            </div>
                                            <div class="radio radio-danger">
                                                <input type="radio" name="radio" id="radio6" value="option6">
                                                <label for="radio6">
                                                    Danger
                                                </label>
                                            </div>
                                            <div class="radio radio-warning">
                                                <input type="radio" name="radio" id="radio7" value="option7">
                                                <label for="radio7">
                                                    Warning
                                                </label>
                                            </div>
                                            <div class="radio radio-purple">
                                                <input type="radio" name="radio" id="radio8" value="option8">
                                                <label for="radio8">
                                                    Purple
                                                </label>
                                            </div>
                                            <div class="radio radio-pink">
                                                <input type="radio" name="radio" id="radio9" value="option9">
                                                <label for="radio9">
                                                    Pink
                                                </label>
                                            </div>

                                        </div>

                                        <div class="col-sm-6">
                                            <div class="radio">
                                                <input type="radio" name="radio1" id="radio11" value="option1" checked>
                                                <label for="radio11">
                                                    Default
                                                </label>
                                            </div>
                                            <div class="radio radio-primary">
                                                <input type="radio" name="radio3" id="radio13" value="option3">
                                                <label for="radio13">
                                                    Primary
                                                </label>
                                            </div>
                                            <div class="radio radio-success">
                                                <input type="radio" name="radio4" id="radio14" value="option4" checked>
                                                <label for="radio14">
                                                    Success
                                                </label>
                                            </div>
                                            <div class="radio radio-info">
                                                <input type="radio" name="radio5" id="radio15" value="option5" checked>
                                                <label for="radio15">
                                                    Info
                                                </label>
                                            </div>
                                            <div class="radio radio-danger">
                                                <input type="radio" name="radio6" id="radio16" value="option6">
                                                <label for="radio16">
                                                    Danger
                                                </label>
                                            </div>
                                            <div class="radio radio-warning">
                                                <input type="radio" name="radio7" id="radio17" value="option7" checked>
                                                <label for="radio17">
                                                    Warning
                                                </label>
                                            </div>
                                            <div class="radio radio-purple">
                                                <input type="radio" name="radio8" id="radio18" value="option8">
                                                <label for="radio18">
                                                    Purple
                                                </label>
                                            </div>
                                            <div class="radio radio-pink">
                                                <input type="radio" name="radio9" id="radio19" value="option9" checked>
                                                <label for="radio19">
                                                    Pink
                                                </label>
                                            </div>

                                        </div>

                                    </div>


                                    <p class="sub-header mt-4">Radios without label text <code>.radio-single</code></p>
                                    <div class="radio radio-single">
                                        <input type="radio" id="singleRadio1" value="option1.1" name="radioSingle1"
                                               aria-label="Single radio One">
                                        <label></label>
                                    </div>
                                    <div class="radio radio-success radio-single">
                                        <input type="radio" id="singleRadio2" value="option2.1" name="radioSingle1"
                                               checked aria-label="Single radio Two">
                                        <label></label>
                                    </div>


                                    <p class="sub-header mt-4">Inline radios</p>
                                    <div class="radio radio-info form-check-inline">
                                        <input type="radio" id="inlineRadio1" value="option1" name="radioInline"
                                               checked>
                                        <label for="inlineRadio1"> Inline One </label>
                                    </div>
                                    <div class="radio form-check-inline">
                                        <input type="radio" id="inlineRadio2" value="option2" name="radioInline">
                                        <label for="inlineRadio2"> Inline Two </label>
                                    </div>
                                </div>


                                <div class="col-lg-4 col-md-6">
                                    <div class="mt-4 mt-md-0">
                                        <h4 class="header-title">Disabled</h4>

                                        <p class="sub-header">
                                            Disabled state also supported.
                                        </p>
                                        <div class="radio radio-danger">
                                            <input type="radio" name="radio31" id="radio51" value="option1" checked
                                                   disabled>
                                            <label for="radio51">
                                                Next
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <input type="radio" name="radio41" id="radio61" value="option2" checked
                                                   disabled>
                                            <label for="radio61">
                                                One
                                            </label>
                                        </div>
                                        <div class="radio radio-primary">
                                            <input type="radio" name="radio51" id="radio71" value="option3" checked
                                                   disabled>
                                            <label for="radio71">
                                                Next
                                            </label>
                                        </div>
                                        <div class="radio radio-pink">
                                            <input type="radio" name="radio61" id="radio81" value="option4" checked
                                                   disabled>
                                            <label for="radio81">
                                                One
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- end row -->


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