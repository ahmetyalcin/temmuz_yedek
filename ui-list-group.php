<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "List Group";
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
            $title = "List Group";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Basic example</h5>
                            <p class="card-subtitle">The most basic list group is an unordered list with
                                list items and the proper classes.
                                Build upon it with the options that follow, or with your own CSS as needed.
                            </p>
                        </div>

                        <div class="card-body pt-2">


                            <ul class="list-group">
                                <li class="list-group-item"><i
                                            class="ti ti-brand-google-drive me-1 align-middle font-18"></i>
                                    Google Drive
                                </li>
                                <li class="list-group-item"><i
                                            class="ti ti-brand-messenger me-1 align-middle font-18"></i>
                                    Facebook Messenger
                                </li>
                                <li class="list-group-item"><i class="ti ti-brand-apple me-1 align-middle font-18"></i>
                                    Apple
                                    Technology Company
                                </li>
                                <li class="list-group-item"><i
                                            class="ti ti-brand-intercom me-1 align-middle font-18"></i> Intercom
                                    Support System
                                </li>
                                <li class="list-group-item"><i class="ti ti-brand-paypal me-1 align-middle font-18"></i>
                                    Paypal
                                    Payment Gateway
                                </li>
                            </ul>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Active items</h5>
                            <p class="card-subtitle">Add <code>.active</code> to a
                                <code>.list-group-item</code> to indicate the current active selection.
                            </p>
                        </div>

                        <div class="card-body pt-2">
                            <ul class="list-group">
                                <li class="list-group-item active"><i
                                            class="ti ti-brand-google-drive me-1 align-middle font-18"></i> Google Drive
                                </li>
                                <li class="list-group-item"><i
                                            class="ti ti-brand-messenger me-1 align-middle font-18"></i>
                                    Facebook Messenger
                                </li>
                                <li class="list-group-item"><i class="ti ti-brand-apple me-1 align-middle font-18"></i>
                                    Apple
                                    Technology Company
                                </li>
                                <li class="list-group-item"><i
                                            class="ti ti-brand-intercom me-1 align-middle font-18"></i> Intercom
                                    Support System
                                </li>
                                <li class="list-group-item"><i class="ti ti-brand-paypal me-1 align-middle font-18"></i>
                                    Paypal
                                    Payment Gateway
                                </li>
                            </ul>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Disabled items</h5>
                            <p class="card-subtitle">Add <code>.disabled</code> to a <code>.list-group-item</code> to
                                make it
                                <em>appear</em> disabled.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <ul class="list-group">
                                <li class="list-group-item disabled" aria-disabled="true"><i
                                            class="ti ti-brand-google-drive me-1 align-middle font-18"></i> Google Drive
                                </li>
                                <li class="list-group-item"><i
                                            class="ti ti-brand-messenger me-1 align-middle font-18"></i>
                                    Facebook Messenger
                                </li>
                                <li class="list-group-item"><i class="ti ti-brand-apple me-1 align-middle font-18"></i>
                                    Apple
                                    Technology Company
                                </li>
                                <li class="list-group-item"><i
                                            class="ti ti-brand-intercom me-1 align-middle font-18"></i> Intercom
                                    Support System
                                </li>
                                <li class="list-group-item"><i class="ti ti-brand-paypal me-1 align-middle font-18"></i>
                                    Paypal
                                    Payment Gateway
                                </li>
                            </ul>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Links and Buttons</h5>
                            <p class="card-subtitle">Use <code>&lt;a&gt;</code>s or
                                <code>&lt;button&gt;</code>s to create <em>actionable</em> list group items with
                                hover, disabled, and active states by adding
                                <code>.list-group-item-action</code>.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action active">
                                    Paypal Payment Gateway
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">Google
                                    Drive</a>
                                <button type="button" class="list-group-item list-group-item-action">Facebook
                                    Messenger
                                </button>
                                <button type="button" class="list-group-item list-group-item-action">Apple Technology
                                    Company
                                </button>
                                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1"
                                   aria-disabled="true">Intercom Support System</a>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Flush</h5>
                            <p class="card-subtitle">Add <code>.list-group-flush</code> to remove some
                                borders and rounded corners to render list group items edge-to-edge in a parent
                                container (e.g., cards).</p>
                        </div>

                        <div class="card-body pt-2">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Google Drive</li>
                                <li class="list-group-item">Facebook Messenger</li>
                                <li class="list-group-item">Apple Technology Company</li>
                                <li class="list-group-item">Intercom Support System</li>
                                <li class="list-group-item">Paypal Payment Gateway</li>
                            </ul>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Horizontal</h5>
                            <p class="card-subtitle">Add <code>.list-group-horizontal</code> to change the
                                layout of list group items from vertical to horizontal across all breakpoints.
                                Alternatively, choose a responsive variant
                                <code>.list-group-horizontal-{sm|md|lg|xl}</code> to make a list group
                                horizontal starting at that breakpoint’s <code>min-width</code>.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <ul class="list-group list-group-horizontal mb-3">
                                <li class="list-group-item">Google</li>
                                <li class="list-group-item">Whatsapp</li>
                                <li class="list-group-item">Facebook</li>
                            </ul>

                            <ul class="list-group list-group-horizontal-sm mb-3">
                                <li class="list-group-item">Apple</li>
                                <li class="list-group-item">PayPal</li>
                                <li class="list-group-item">Intercom</li>
                            </ul>

                            <ul class="list-group list-group-horizontal-md">
                                <li class="list-group-item">Google</li>
                                <li class="list-group-item">Whatsapp</li>
                                <li class="list-group-item">Facebook</li>
                            </ul>

                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->
            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Contextual classes</h5>
                            <p class="card-subtitle">Use contextual classes to style list items with a
                                stateful background and color.</p>
                        </div>

                        <div class="card-body pt-2">

                            <ul class="list-group">
                                <li class="list-group-item">Dapibus ac facilisis in</li>
                                <li class="list-group-item list-group-item-primary">A simple primary
                                    list group item
                                </li>
                                <li class="list-group-item list-group-item-secondary">A simple secondary
                                    list group item
                                </li>
                                <li class="list-group-item list-group-item-success">A simple success
                                    list group item
                                </li>
                                <li class="list-group-item list-group-item-danger">A simple danger list
                                    group item
                                </li>
                                <li class="list-group-item list-group-item-warning">A simple warning
                                    list group item
                                </li>
                                <li class="list-group-item list-group-item-info">A simple info list
                                    group item
                                </li>
                                <li class="list-group-item list-group-item-light">A simple light list
                                    group item
                                </li>
                                <li class="list-group-item list-group-item-dark">A simple dark list
                                    group item
                                </li>
                            </ul>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Contextual classes with Link</h5>
                            <p class="card-subtitle">Use contextual classes to style list items with a
                                stateful background and color.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">Darius ac facilities in</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-primary">A
                                    simple primary list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-secondary">A
                                    simple secondary list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-success">A
                                    simple success list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-danger">A
                                    simple danger list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-warning">A
                                    simple warning list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-info">A simple
                                    info list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-light">A
                                    simple light list group item</a>
                                <a href="#" class="list-group-item list-group-item-action list-group-item-dark">A simple
                                    dark list group item</a>
                            </div>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Custom content</h5>
                            <p class="card-subtitle">Add nearly any HTML within, even for linked list
                                groups like the one below, with the help of flexbox utilities.</p>
                        </div>

                        <div class="card-body pt-2">

                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action active">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">List group item heading</h5>
                                        <small>3 days ago</small>
                                    </div>
                                    <p class="mb-1">Donec id elit non mi porta gravida at eget metus.
                                        Maecenas sed diam eget risus varius blandit.</p>
                                    <small>Donec id elit non mi porta.</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">List group item heading</h5>
                                        <small class="text-muted">3 days ago</small>
                                    </div>
                                    <p class="mb-1">Donec id elit non mi porta gravida at eget metus.
                                        Maecenas sed diam eget risus varius blandit.</p>
                                    <small class="text-muted">Donec id elit non mi porta.</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">List group item heading</h5>
                                        <small class="text-muted">3 days ago</small>
                                    </div>
                                    <p class="mb-1">Donec id elit non mi porta gravida at eget metus.
                                        Maecenas sed diam eget risus varius blandit.</p>
                                    <small class="text-muted">Donec id elit non mi porta.</small>
                                </a>
                            </div>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

            </div>
            <!-- end row -->

            <div class="row">
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">With badges</h5>
                            <p class="card-subtitle">Add badges to any list group item to show unread
                                counts, activity, and more with the help of some utilities.</p>
                        </div>

                        <div class="card-body pt-2">

                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Gmail Emails
                                    <span class="badge bg-primary rounded-pill">14</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Pending Payments
                                    <span class="badge bg-success rounded-pill">2</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Action Needed
                                    <span class="badge bg-danger rounded-pill">99+</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Payments Done
                                    <span class="badge bg-success rounded-pill">20+</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Pending Payments
                                    <span class="badge bg-warning rounded-pill">12</span>
                                </li>
                            </ul>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Checkboxes and radios</h5>
                            <p class="card-subtitle">Place Bootstrap’s checkboxes and radios within list
                                group items and customize as needed. You can use them without
                                <code>&lt;label&gt;</code>s, but please remember to include an
                                <code>aria-label</code> attribute and value for accessibility.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <ul class="list-group">
                                <li class="list-group-item">
                                    <input class="form-check-input me-1" type="checkbox" value="" id="firstCheckbox">
                                    <label class="form-check-label" for="firstCheckbox">First
                                        checkbox</label>
                                </li>
                                <li class="list-group-item">
                                    <input class="form-check-input me-1" type="checkbox" value="" id="secondCheckbox">
                                    <label class="form-check-label" for="secondCheckbox">Second
                                        checkbox</label>
                                </li>
                            </ul>

                            <ul class="list-group mt-2">
                                <li class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="listGroupRadio" value=""
                                           id="firstRadio" checked>
                                    <label class="form-check-label" for="firstRadio">First radio</label>
                                </li>
                                <li class="list-group-item">
                                    <input class="form-check-input me-1" type="radio" name="listGroupRadio" value=""
                                           id="secondRadio">
                                    <label class="form-check-label" for="secondRadio">Second
                                        radio</label>
                                </li>
                            </ul>
                        </div> <!-- end card-body -->
                    </div> <!-- end card-->
                </div> <!-- end col -->

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="header-title">Numbered</h5>
                            <p class="card-subtitle">Numbers are generated by <code>counter-reset</code> on
                                the <code>&lt;ol&gt;</code>, and then styled and placed with a
                                <code>::before</code> psuedo-element on the <code>&lt;li&gt;</code> with
                                <code>counter-increment</code> and <code>content</code>.
                            </p>
                        </div>

                        <div class="card-body pt-2">

                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">Paces Admin</div>
                                        Paces Admin
                                    </div>
                                    <span class="badge bg-primary rounded-pill">865</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">Paces React Admin</div>
                                        Paces React Admin
                                    </div>
                                    <span class="badge bg-primary rounded-pill">140</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">Angular Version</div>
                                        Angular Version
                                    </div>
                                    <span class="badge bg-primary rounded-pill">85</span>
                                </li>
                            </ol>

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