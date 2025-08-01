<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Form Select";
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
            $subtitle = "Forms";
            $title = "Form Select";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed">
                            <h4 class="header-title mb-2">Select2</h4>
                            <p class="text-muted font-14 mb-0">Select2 gives you a customizable select box with support
                                for searching, tagging, remote data sets, infinite scrolling, and many other highly used
                                options.</p>
                        </div><!-- end card header -->


                        <div class="card-body">

                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <p class="mb-1 fw-bold text-muted">Single Select</p>
                                    <p class="text-muted font-14">
                                        Select2 can take a regular select box like this...
                                    </p>

                                    <select class="form-control select2" data-toggle="select2">
                                        <option>Select</option>
                                        <optgroup label="Alaskan/Hawaiian Time Zone">
                                            <option value="AK">Alaska</option>
                                            <option value="HI">Hawaii</option>
                                        </optgroup>
                                        <optgroup label="Pacific Time Zone">
                                            <option value="CA">California</option>
                                            <option value="NV">Nevada</option>
                                            <option value="OR">Oregon</option>
                                            <option value="WA">Washington</option>
                                        </optgroup>
                                        <optgroup label="Mountain Time Zone">
                                            <option value="AZ">Arizona</option>
                                            <option value="CO">Colorado</option>
                                            <option value="ID">Idaho</option>
                                            <option value="MT">Montana</option>
                                            <option value="NE">Nebraska</option>
                                            <option value="NM">New Mexico</option>
                                            <option value="ND">North Dakota</option>
                                            <option value="UT">Utah</option>
                                            <option value="WY">Wyoming</option>
                                        </optgroup>
                                        <optgroup label="Central Time Zone">
                                            <option value="AL">Alabama</option>
                                            <option value="AR">Arkansas</option>
                                            <option value="IL">Illinois</option>
                                            <option value="IA">Iowa</option>
                                            <option value="KS">Kansas</option>
                                            <option value="KY">Kentucky</option>
                                            <option value="LA">Louisiana</option>
                                            <option value="MN">Minnesota</option>
                                            <option value="MS">Mississippi</option>
                                            <option value="MO">Missouri</option>
                                            <option value="OK">Oklahoma</option>
                                            <option value="SD">South Dakota</option>
                                            <option value="TX">Texas</option>
                                            <option value="TN">Tennessee</option>
                                            <option value="WI">Wisconsin</option>
                                        </optgroup>
                                        <optgroup label="Eastern Time Zone">
                                            <option value="CT">Connecticut</option>
                                            <option value="DE">Delaware</option>
                                            <option value="FL">Florida</option>
                                            <option value="GA">Georgia</option>
                                            <option value="IN">Indiana</option>
                                            <option value="ME">Maine</option>
                                            <option value="MD">Maryland</option>
                                            <option value="MA">Massachusetts</option>
                                            <option value="MI">Michigan</option>
                                            <option value="NH">New Hampshire</option>
                                            <option value="NJ">New Jersey</option>
                                            <option value="NY">New York</option>
                                            <option value="NC">North Carolina</option>
                                            <option value="OH">Ohio</option>
                                            <option value="PA">Pennsylvania</option>
                                            <option value="RI">Rhode Island</option>
                                            <option value="SC">South Carolina</option>
                                            <option value="VT">Vermont</option>
                                            <option value="VA">Virginia</option>
                                            <option value="WV">West Virginia</option>
                                        </optgroup>
                                    </select>
                                </div> <!-- end col -->

                                <div class="col-lg-6">
                                    <p class="mb-1 fw-bold text-muted">Multiple Select</p>
                                    <p class="text-muted font-14">
                                        Select2 can take a regular select box like this...
                                    </p>

                                    <select class="select2 form-control select2-multiple" data-toggle="select2"
                                            multiple="multiple" data-placeholder="Choose ...">
                                        <optgroup label="Alaskan/Hawaiian Time Zone">
                                            <option value="AK">Alaska</option>
                                            <option value="HI">Hawaii</option>
                                        </optgroup>
                                        <optgroup label="Pacific Time Zone">
                                            <option value="CA">California</option>
                                            <option value="NV">Nevada</option>
                                            <option value="OR">Oregon</option>
                                            <option value="WA">Washington</option>
                                        </optgroup>
                                        <optgroup label="Mountain Time Zone">
                                            <option value="AZ">Arizona</option>
                                            <option value="CO">Colorado</option>
                                            <option value="ID">Idaho</option>
                                            <option value="MT">Montana</option>
                                            <option value="NE">Nebraska</option>
                                            <option value="NM">New Mexico</option>
                                            <option value="ND">North Dakota</option>
                                            <option value="UT">Utah</option>
                                            <option value="WY">Wyoming</option>
                                        </optgroup>
                                        <optgroup label="Central Time Zone">
                                            <option value="AL">Alabama</option>
                                            <option value="AR">Arkansas</option>
                                            <option value="IL">Illinois</option>
                                            <option value="IA">Iowa</option>
                                            <option value="KS">Kansas</option>
                                            <option value="KY">Kentucky</option>
                                            <option value="LA">Louisiana</option>
                                            <option value="MN">Minnesota</option>
                                            <option value="MS">Mississippi</option>
                                            <option value="MO">Missouri</option>
                                            <option value="OK">Oklahoma</option>
                                            <option value="SD">South Dakota</option>
                                            <option value="TX">Texas</option>
                                            <option value="TN">Tennessee</option>
                                            <option value="WI">Wisconsin</option>
                                        </optgroup>
                                        <optgroup label="Eastern Time Zone">
                                            <option value="CT">Connecticut</option>
                                            <option value="DE">Delaware</option>
                                            <option value="FL">Florida</option>
                                            <option value="GA">Georgia</option>
                                            <option value="IN">Indiana</option>
                                            <option value="ME">Maine</option>
                                            <option value="MD">Maryland</option>
                                            <option value="MA">Massachusetts</option>
                                            <option value="MI">Michigan</option>
                                            <option value="NH">New Hampshire</option>
                                            <option value="NJ">New Jersey</option>
                                            <option value="NY">New York</option>
                                            <option value="NC">North Carolina</option>
                                            <option value="OH">Ohio</option>
                                            <option value="PA">Pennsylvania</option>
                                            <option value="RI">Rhode Island</option>
                                            <option value="SC">South Carolina</option>
                                            <option value="VT">Vermont</option>
                                            <option value="VA">Virginia</option>
                                            <option value="WV">West Virginia</option>
                                        </optgroup>
                                    </select>
                                </div> <!-- end col -->
                            </div> <!-- end row -->

                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
            <!-- end row-->


            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed">
                            <h4 class="header-title mb-0">Choices</h4>
                        </div><!-- end card header -->

                        <div class="card-body">
                            <div>
                                <h5 class="font-14 mb-2">Single select input Example</h5>

                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-single-default"
                                                   class="form-label text-muted">Default</label>
                                            <p class="text-muted">Set <code>data-choices</code> attribute to set a
                                                default single select.</p>
                                            <select class="form-control" data-choices name="choices-single-default"
                                                    id="choices-single-default">
                                                <option value="">This is a placeholder</option>
                                                <option value="Choice 1">Choice 1</option>
                                                <option value="Choice 2">Choice 2</option>
                                                <option value="Choice 3">Choice 3</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-single-groups" class="form-label text-muted">Option
                                                Groups</label>
                                            <p class="text-muted">Set <code>data-choices data-choices-groups</code>
                                                attribute to set option group</p>
                                            <select class="form-control" id="choices-single-groups" data-choices
                                                    data-choices-groups data-placeholder="Select City"
                                                    name="choices-single-groups">
                                                <option value="">Choose a city</option>
                                                <optgroup label="UK">
                                                    <option value="London">London</option>
                                                    <option value="Manchester">Manchester</option>
                                                    <option value="Liverpool">Liverpool</option>
                                                </optgroup>
                                                <optgroup label="FR">
                                                    <option value="Paris">Paris</option>
                                                    <option value="Lyon">Lyon</option>
                                                    <option value="Marseille">Marseille</option>
                                                </optgroup>
                                                <optgroup label="DE" disabled>
                                                    <option value="Hamburg">Hamburg</option>
                                                    <option value="Munich">Munich</option>
                                                    <option value="Berlin">Berlin</option>
                                                </optgroup>
                                                <optgroup label="US">
                                                    <option value="New York">New York</option>
                                                    <option value="Washington" disabled>Washington</option>
                                                    <option value="Michigan">Michigan</option>
                                                </optgroup>
                                                <optgroup label="SP">
                                                    <option value="Madrid">Madrid</option>
                                                    <option value="Barcelona">Barcelona</option>
                                                    <option value="Malaga">Malaga</option>
                                                </optgroup>
                                                <optgroup label="CA">
                                                    <option value="Montreal">Montreal</option>
                                                    <option value="Toronto">Toronto</option>
                                                    <option value="Vancouver">Vancouver</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-single-no-search" class="form-label text-muted">Options
                                                added via config with no search</label>
                                            <p class="text-muted">Set <code>data-choices data-choices-search-false
                                                    data-choices-removeItem</code></p>
                                            <select class="form-control" id="choices-single-no-search"
                                                    name="choices-single-no-search" data-choices
                                                    data-choices-search-false data-choices-removeItem>
                                                <option value="Zero">Zero</option>
                                                <option value="One">One</option>
                                                <option value="Two">Two</option>
                                                <option value="Three">Three</option>
                                                <option value="Four">Four</option>
                                                <option value="Five">Five</option>
                                                <option value="Six">Six</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-single-no-sorting" class="form-label text-muted">Options
                                                added via config with no sorting</label>
                                            <p class="text-muted">Set <code>data-choices
                                                    data-choices-sorting-false</code> attribute.</p>
                                            <select class="form-control" id="choices-single-no-sorting"
                                                    name="choices-single-no-sorting" data-choices
                                                    data-choices-sorting-false>
                                                <option value="Madrid">Madrid</option>
                                                <option value="Toronto">Toronto</option>
                                                <option value="Vancouver">Vancouver</option>
                                                <option value="London">London</option>
                                                <option value="Manchester">Manchester</option>
                                                <option value="Liverpool">Liverpool</option>
                                                <option value="Paris">Paris</option>
                                                <option value="Malaga">Malaga</option>
                                                <option value="Washington" disabled>Washington</option>
                                                <option value="Lyon">Lyon</option>
                                                <option value="Marseille">Marseille</option>
                                                <option value="Hamburg">Hamburg</option>
                                                <option value="Munich">Munich</option>
                                                <option value="Barcelona">Barcelona</option>
                                                <option value="Berlin">Berlin</option>
                                                <option value="Montreal">Montreal</option>
                                                <option value="New York">New York</option>
                                                <option value="Michigan">Michigan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- end row -->
                            </div>
                            <!-- Single select input Example -->

                            <div class="mt-4">
                                <h5 class="font-14 mb-3">Multiple select input</h5>

                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-multiple-default"
                                                   class="form-label text-muted">Default</label>
                                            <p class="text-muted">Set <code>data-choices multiple</code> attribute.</p>
                                            <select class="form-control" id="choices-multiple-default" data-choices
                                                    name="choices-multiple-default" multiple>
                                                <option value="Choice 1" selected>Choice 1</option>
                                                <option value="Choice 2">Choice 2</option>
                                                <option value="Choice 3">Choice 3</option>
                                                <option value="Choice 4" disabled>Choice 4</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-multiple-remove-button" class="form-label text-muted">With
                                                remove button</label>
                                            <p class="text-muted">Set <code>data-choices data-choices-removeItem
                                                    multiple</code> attribute.</p>
                                            <select class="form-control" id="choices-multiple-remove-button"
                                                    data-choices data-choices-removeItem
                                                    name="choices-multiple-remove-button" multiple>
                                                <option value="Choice 1" selected>Choice 1</option>
                                                <option value="Choice 2">Choice 2</option>
                                                <option value="Choice 3">Choice 3</option>
                                                <option value="Choice 4">Choice 4</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-multiple-groups" class="form-label text-muted">Option
                                                groups</label>
                                            <p class="text-muted">Set <code>data-choices
                                                    data-choices-multiple-groups="true" multiple</code> attribute. </p>
                                            <select class="form-control" id="choices-multiple-groups"
                                                    name="choices-multiple-groups" data-choices
                                                    data-choices-multiple-groups="true" multiple>
                                                <option value="">Choose a city</option>
                                                <optgroup label="UK">
                                                    <option value="London">London</option>
                                                    <option value="Manchester">Manchester</option>
                                                    <option value="Liverpool">Liverpool</option>
                                                </optgroup>
                                                <optgroup label="FR">
                                                    <option value="Paris">Paris</option>
                                                    <option value="Lyon">Lyon</option>
                                                    <option value="Marseille">Marseille</option>
                                                </optgroup>
                                                <optgroup label="DE" disabled>
                                                    <option value="Hamburg">Hamburg</option>
                                                    <option value="Munich">Munich</option>
                                                    <option value="Berlin">Berlin</option>
                                                </optgroup>
                                                <optgroup label="US">
                                                    <option value="New York">New York</option>
                                                    <option value="Washington" disabled>Washington</option>
                                                    <option value="Michigan">Michigan</option>
                                                </optgroup>
                                                <optgroup label="SP">
                                                    <option value="Madrid">Madrid</option>
                                                    <option value="Barcelona">Barcelona</option>
                                                    <option value="Malaga">Malaga</option>
                                                </optgroup>
                                                <optgroup label="CA">
                                                    <option value="Montreal">Montreal</option>
                                                    <option value="Toronto">Toronto</option>
                                                    <option value="Vancouver">Vancouver</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                                <!-- end row -->
                            </div>
                            <!-- multi select input Example -->

                            <div class="mt-4">
                                <h5 class="font-14 mb-3">Text inputs</h5>

                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-text-remove-button" class="form-label text-muted">Set
                                                limit values with remove button</label>
                                            <p class="text-muted">Set <code>data-choices data-choices-limit="Required
                                                    Limit" data-choices-removeItem</code> attribute.</p>
                                            <input class="form-control" id="choices-text-remove-button" data-choices
                                                   data-choices-limit="3" data-choices-removeItem type="text"
                                                   value="Task-1"/>
                                        </div>
                                    </div>
                                    <!-- end col -->

                                    <div class="col-lg-4 col-md-6">
                                        <div class="mb-3">
                                            <label for="choices-text-unique-values" class="form-label text-muted">Unique
                                                values only, no pasting</label>
                                            <p class="text-muted">Set <code>data-choices
                                                    data-choices-text-unique-true</code> attribute.</p>
                                            <input class="form-control" id="choices-text-unique-values" data-choices
                                                   data-choices-text-unique-true type="text"
                                                   value="Project-A, Project-B"/>
                                        </div>
                                    </div>
                                    <!-- end col -->
                                </div>
                                <!-- end row -->

                                <div>
                                    <label for="choices-text-disabled" class="form-label text-muted">Disabled</label>
                                    <p class="text-muted">Set <code>data-choices data-choices-text-disabled-true</code>
                                        attribute.</p>
                                    <input class="form-control" id="choices-text-disabled" data-choices
                                           data-choices-text-disabled-true type="text"
                                           value="josh@joshuajohnson.co.uk, joe@bloggs.co.uk"/>
                                </div>
                            </div>
                        </div><!-- end card-body -->
                    </div><!-- end card -->
                </div>
                <!-- end col -->
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