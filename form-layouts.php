<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Form Layout";
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
            $title = "Form Layout";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Basic Example</h4>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Here’s a quick example to demonstrate Bootstrap’s form styles. Keep
                                reading for documentation on required classes, form layout, and more.</p>

                            <form>
                                <div class="mb-3">
                                    <label for="exampleInputEmail1" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="exampleInputEmail1"
                                           aria-describedby="emailHelp" placeholder="Enter email">
                                    <small id="emailHelp" class="form-text text-muted">We'll never share your email with
                                        anyone else.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="exampleInputPassword1" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="exampleInputPassword1"
                                           placeholder="Password">
                                </div>
                                <div class=" mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="checkmeout0">
                                        <label class="form-check-label" for="checkmeout0">Check me out !</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div>
                <!-- end col -->

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Horizontal form</h4>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Create horizontal forms with the grid by adding the <code>.row</code>
                                class to form groups and using the <code>.col-*-*</code> classes to specify the width of
                                your labels and controls. Be sure to add <code>.col-form-label</code> to your <code>&lt;label&gt;</code>s
                                as well so they’re vertically centered with their associated form controls.</p>

                            <form class="form-horizontal">
                                <div class="row mb-3">
                                    <label for="inputEmail3" class="col-3 col-form-label">Email</label>
                                    <div class="col-9">
                                        <input type="email" class="form-control" id="inputEmail3" placeholder="Email">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputPassword3" class="col-3 col-form-label">Password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" id="inputPassword3"
                                               placeholder="Password">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="inputPassword5" class="col-3 col-form-label">Re Password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" id="inputPassword5"
                                               placeholder="Retype Password">
                                    </div>
                                </div>
                                <div class="row mb-3 justify-content-end">
                                    <div class="col-9">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="checkmeout">
                                            <label class="form-check-label" for="checkmeout">Check me out !</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="justify-content-end row">
                                    <div class="col-9">
                                        <button type="submit" class="btn btn-info">Sign in</button>
                                    </div>
                                </div>
                            </form>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
            </div>
            <!-- end row -->


            <!-- Inline Form -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Inline Form</h4>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">
                                Use the <code>.row-cols-lg-auto</code>, <code>.g-3</code> &
                                <code>.align-items-center</code> class to display a series of labels, form controls, and
                                buttons on a single horizontal row. Form controls within inline forms vary slightly from
                                their default states. Controls only appear inline in viewports that are at least 576px
                                wide to account for narrow viewports on mobile devices.
                            </p>
                            <form class="row row-cols-lg-auto g-3 align-items-center">
                                <div class="col-12">
                                    <label for="staticEmail2" class="visually-hidden">Email</label>
                                    <input type="text" readonly class="form-control-plaintext" id="staticEmail2"
                                           value="email@example.com">
                                </div>
                                <div class="col-12">
                                    <label for="inputPassword2" class="visually-hidden">Password</label>
                                    <input type="password" class="form-control" id="inputPassword2"
                                           placeholder="Password">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Confirm identity</button>
                                </div>
                            </form>

                            <h6 class="font-13 mt-3">Auto-sizing</h6>
                            <form>
                                <div class="row gy-2 gx-2 align-items-center">
                                    <div class="col-auto">
                                        <label class="visually-hidden" for="inlineFormInput">Name</label>
                                        <input type="text" class="form-control mb-2" id="inlineFormInput"
                                               placeholder="Jane Doe">
                                    </div>
                                    <div class="col-auto">
                                        <label class="visually-hidden" for="inlineFormInputGroup">Username</label>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">@</div>
                                            <input type="text" class="form-control" id="inlineFormInputGroup"
                                                   placeholder="Username">
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="autoSizingCheck">
                                            <label class="form-check-label" for="autoSizingCheck">Remember me</label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary mb-2">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Horizontal form label sizing</h4>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">Be sure to use <code>.col-form-label-sm</code> or <code>.col-form-label-lg</code>
                                to your <code>&lt;label&gt;</code>s or <code>&lt;legend&gt;</code>s to correctly follow
                                the size of <code>.form-control-lg</code> and <code>.form-control-sm</code>.</p>

                            <form>
                                <div class="mb-2 row">
                                    <label for="colFormLabelSm"
                                           class="col-sm-2 col-form-label col-form-label-sm">Email</label>
                                    <div class="col-sm-10">
                                        <input type="email" class="form-control form-control-sm" id="colFormLabelSm"
                                               placeholder="col-form-label-sm">
                                    </div>
                                </div>
                                <div class="mb-2 row">
                                    <label for="colFormLabel" class="col-sm-2 col-form-label">Email</label>
                                    <div class="col-sm-10">
                                        <input type="email" class="form-control" id="colFormLabel"
                                               placeholder="col-form-label">
                                    </div>
                                </div>
                                <div class="row">
                                    <label for="colFormLabelLg"
                                           class="col-sm-2 col-form-label col-form-label-lg">Email</label>
                                    <div class="col-sm-10">
                                        <input type="email" class="form-control form-control-lg" id="colFormLabelLg"
                                               placeholder="col-form-label-lg">
                                    </div>
                                </div>
                            </form>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <!-- Form row -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-bottom border-dashed d-flex align-items-center">
                            <h4 class="header-title">Form Row</h4>
                        </div>

                        <div class="card-body">
                            <p class="text-muted">
                                By adding <code>.row</code> & <code>.g-2</code>, you can have control over the gutter
                                width in as well the inline as block direction.
                            </p>
                            <form>
                                <div class="row g-2">
                                    <div class="mb-3 col-md-6">
                                        <label for="inputEmail4" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="inputEmail4" placeholder="Email">
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="inputPassword4" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="inputPassword4"
                                               placeholder="Password">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="inputAddress" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="inputAddress"
                                           placeholder="1234 Main St">
                                </div>

                                <div class="mb-3">
                                    <label for="inputAddress2" class="form-label">Address 2</label>
                                    <input type="text" class="form-control" id="inputAddress2"
                                           placeholder="Apartment, studio, or floor">
                                </div>

                                <div class="row g-2">
                                    <div class="mb-3 col-md-6">
                                        <label for="inputCity" class="form-label">City</label>
                                        <input type="text" class="form-control" id="inputCity">
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <label for="inputState" class="form-label">State</label>
                                        <select id="inputState" class="form-select">
                                            <option>Choose</option>
                                            <option>Option 1</option>
                                            <option>Option 2</option>
                                            <option>Option 3</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-2">
                                        <label for="inputZip" class="form-label">Zip</label>
                                        <input type="text" class="form-control" id="inputZip">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input font-15" id="customCheck11">
                                        <label class="form-check-label" for="customCheck11">Check this custom
                                            checkbox</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Sign in</button>
                            </form>
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