<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Dashboard-2";
    include "partials/title-meta.php" ?>

    <!-- C3 Chart css -->
    <link href="assets/vendor/c3/c3.min.css" rel="stylesheet" type="text/css"/>


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
            $subtitle = "Dashboard";
            $title = "Dashboard 2";
            include "partials/page-title.php" ?>

            <div class="row">
                <div class="col-xl-8">
                    <div class="row">
                        <div class="col-xl-4 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="text-truncate">
                                            <h4 class="mt-0 font-15 fw-medium mb-1 text-overflow" title="Total Revenue">
                                                Total Revenue</h4>
                                            <p class="fs-secondary text-muted">Jan - Apr 2019</p>
                                            <h3 class="mb-0 mt-2 fw-semibold"><span>$</span> <span
                                                        data-plugin="counterup">52,548</span></h3>
                                        </div>
                                        <div id="dashboard-1" class=""></div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- end col -->

                        <div class="col-xl-4 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="text-truncate">
                                            <h4 class="mt-0 font-15 mb-1 fw-medium text-overflow text-truncate"
                                                title="Total Unique Visitors">Total Unique Visitors</h4>
                                            <p class="fs-secondary text-muted">Jan - Apr 2019</p>
                                            <h3 class="mb-0 mt-2 fw-semibold"><span>$</span> <span
                                                        data-plugin="counterup">65,241</span></h3>
                                        </div>
                                        <div id="dashboard-2" class="widget-box-four-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- end col -->

                        <div class="col-xl-4 col-sm-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="text-truncate">
                                            <h4 class="mt-0 font-15 mb-1 fw-medium text-overflow text-truncate"
                                                title="Number of Transactions">Number of Transactions</h4>
                                            <p class="fs-secondary text-muted">Jan - Apr 2019</p>
                                            <h3 class="mb-0 mt-2 fw-semibold"><span>$</span> <span
                                                        data-plugin="counterup">28,5960</span></h3>
                                        </div>
                                        <div id="dashboard-3" class="widget-box-four-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- end col -->

                    </div>
                    <!-- end row -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="header-title mb-0">Sales Revenue</h4>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="text-center">
                                        <div class="row">
                                            <div class="col-4">
                                                <div class="mt-3 mb-3">
                                                    <h3 class="mb-2 fw-semibold">2563</h3>
                                                    <p class="text-uppercase mb-1 font-13 fw-normal">Lifetime total
                                                        sales</p>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mt-3 mb-3">
                                                    <h3 class="mb-2 fw-semibold">6952</h3>
                                                    <p class="text-uppercase mb-1 font-13 fw-normal">Income amounts</p>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mt-3 mb-3">
                                                    <h3 class="mb-2 fw-semibold">1125</h3>
                                                    <p class="text-uppercase mb-1 font-13 fw-normal">Total visits</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="morris-bar-stacked" style="height: 310px;" class="morris-charts"></div>
                                </div>

                            </div>

                        </div><!-- end col -->

                    </div>
                    <!-- end row -->

                </div><!-- end col -->


                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center">
                                <h5 class="fw-normal text-muted">Lifetime Sales</h5>
                                <h3 class="mb-4 fw-semibold"><i
                                            class="mdi mdi-arrow-up-bold-hexagon-outline text-success"></i> 48948
                                    <small>USD</small></h3>
                            </div>

                            <div id="morris-line-example" style="height: 180px;" class="morris-charts"></div>
                        </div>

                    </div>


                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title mb-0">Recent Notifications</h4>
                        </div>
                        <div class="card-body pt-2">
                            <div class="mb-1">
                                <p><span class="float-end text-dark">Mark Loyerdn</span> <span class="badge bg-primary">Visitor</span>
                                </p>
                                <p class="mb-2">Praesent libero. Nunc nec dui vitae urna cursus lacinia. In venenatis
                                    eget justo in dictum. Vestibulum auctor raesent quisnm.</p>
                                <p><i>2 Min ago</i></p>
                            </div>

                            <div class="">
                                <p><span class="float-end text-dark">Mark Loyerdn</span> <span class="badge bg-success">Seller</span>
                                </p>
                                <p class="mb-1">Praesent libero. Nunc nec dui vitae urna cursus lacinia. In venenatis
                                    eget justo in dictum. Vestibulum auctor raesent quisnm.</p>
                                <p class="mb-0"><i>5 Hours ago</i></p>
                            </div>
                        </div>
                    </div>
                </div> <!-- end col -->

            </div>
            <!-- end row -->


            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="header-title">Recent Users</h4>
                            <p class="card-subtitle">
                                Your awesome text goes here.
                            </p>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table m-0 table-hover">
                                    <thead class="bg-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Office</th>
                                        <th>Age</th>
                                        <th>Start date</th>
                                        <th>Salary</th>
                                    </tr>
                                    </thead>


                                    <tbody>
                                    <tr>
                                        <td>Tiger Nixon</td>
                                        <td>System Architect</td>
                                        <td>Edinburgh</td>
                                        <td>61</td>
                                        <td>2011/04/25</td>
                                        <td>$320,800</td>
                                    </tr>
                                    <tr>
                                        <td>Garrett Winters</td>
                                        <td>Accountant</td>
                                        <td>Tokyo</td>
                                        <td>63</td>
                                        <td>2011/07/25</td>
                                        <td>$170,750</td>
                                    </tr>
                                    <tr>
                                        <td>Ashton Cox</td>
                                        <td>Junior Technical Author</td>
                                        <td>San Francisco</td>
                                        <td>66</td>
                                        <td>2009/01/12</td>
                                        <td>$86,000</td>
                                    </tr>
                                    <tr>
                                        <td>Cedric Kelly</td>
                                        <td>Senior Javascript Developer</td>
                                        <td>Edinburgh</td>
                                        <td>22</td>
                                        <td>2012/03/29</td>
                                        <td>$433,060</td>
                                    </tr>
                                    <tr>
                                        <td>Airi Satou</td>
                                        <td>Accountant</td>
                                        <td>Tokyo</td>
                                        <td>33</td>
                                        <td>2008/11/28</td>
                                        <td>$162,700</td>
                                    </tr>
                                    <tr>
                                        <td>Brielle Williamson</td>
                                        <td>Integration Specialist</td>
                                        <td>New York</td>
                                        <td>61</td>
                                        <td>2012/12/02</td>
                                        <td>$372,000</td>
                                    </tr>
                                    <tr>
                                        <td>Herrod Chandler</td>
                                        <td>Sales Assistant</td>
                                        <td>San Francisco</td>
                                        <td>59</td>
                                        <td>2012/08/06</td>
                                        <td>$137,500</td>
                                    </tr>
                                    <tr>
                                        <td>Rhona Davidson</td>
                                        <td>Integration Specialist</td>
                                        <td>Tokyo</td>
                                        <td>55</td>
                                        <td>2010/10/14</td>
                                        <td>$327,900</td>
                                    </tr>
                                    <tr>
                                        <td>Colleen Hurst</td>
                                        <td>Javascript Developer</td>
                                        <td>San Francisco</td>
                                        <td>39</td>
                                        <td>2009/09/15</td>
                                        <td>$205,500</td>
                                    </tr>
                                    <tr>
                                        <td>Sonya Frost</td>
                                        <td>Software Engineer</td>
                                        <td>Edinburgh</td>
                                        <td>23</td>
                                        <td>2008/12/13</td>
                                        <td>$103,600</td>
                                    </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end row -->

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


<script src="assets/vendor/morris.js/morris.min.js"></script>
<script src="assets/vendor/raphael/raphael.min.js"></script>
<script src="assets/vendor/jquery-knob/jquery.knob.min.js"></script>

<script src="assets/vendor/jquery-sparkline/jquery.sparkline.min.js"></script>

<!-- Projects Analytics Dashboard App js -->
<script src="assets/js/pages/dashboard-2.js"></script>

</body>

</html>