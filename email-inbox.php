<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = "Inbox";
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
            $subtitle = "Email";
            $title = "Inbox";
            include "partials/page-title.php" ?>

            <div class="row">

                <!-- Right Sidebar -->
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Left sidebar -->
                            <div class="inbox-leftbar">

                                <a href="email-compose.php"
                                   class="btn btn-danger btn-block waves-effect w-100 waves-light">Compose</a>

                                <div class="mail-list mt-3">
                                    <a href="#" class="list-group-item border-0 text-danger"><i
                                                class="mdi mdi-inbox font-18 align-middle me-2"></i>Inbox<span
                                                class="badge bg-danger float-end ms-2 mt-1">8</span></a>
                                    <a href="#" class="list-group-item border-0"><i
                                                class="mdi mdi-star font-18 align-middle me-2"></i>Starred</a>
                                    <a href="#" class="list-group-item border-0"><i
                                                class="mdi mdi-file-code font-18 align-middle me-2"></i>Draft<span
                                                class="badge bg-info float-end ms-2 mt-1">32</span></a>
                                    <a href="#" class="list-group-item border-0"><i
                                                class="mdi mdi-send font-18 align-middle me-2"></i>Sent Mail</a>
                                    <a href="#" class="list-group-item border-0"><i
                                                class="mdi mdi-delete font-18 align-middle me-2"></i>Trash</a>
                                </div>

                                <h5 class="mt-4">Labels</h5>

                                <div class="list-group b-0 mail-list mt-4">
                                    <a href="#" class="list-group-item border-0"><span
                                                class="fa fa-circle text-info me-2"></span>Web App</a>
                                    <a href="#" class="list-group-item border-0"><span
                                                class="fa fa-circle text-warning me-2"></span>Recharge</a>
                                    <a href="#" class="list-group-item border-0"><span
                                                class="fa fa-circle text-purple me-2"></span>Wallet Balance</a>
                                    <a href="#" class="list-group-item border-0"><span
                                                class="fa fa-circle text-pink me-2"></span>Friends</a>
                                    <a href="#" class="list-group-item border-0"><span
                                                class="fa fa-circle text-success me-2"></span>Family</a>
                                </div>

                            </div>
                            <!-- End Left sidebar -->

                            <div class="inbox-rightbar">

                                <div class="" role="toolbar">
                                    <div class="btn-group me-1">
                                        <button type="button" class="btn btn-sm btn-light waves-effect"><i
                                                    class="mdi mdi-archive font-18 vertical-middle"></i></button>
                                        <button type="button" class="btn btn-sm btn-light waves-effect"><i
                                                    class="mdi mdi-alert-octagon font-18 vertical-middle"></i></button>
                                        <button type="button" class="btn btn-sm btn-light waves-effect"><i
                                                    class="mdi mdi-delete-variant font-18 vertical-middle"></i></button>
                                    </div>
                                    <div class="btn-group me-1">
                                        <button type="button"
                                                class="btn btn-sm btn-light dropdown-toggle waves-effect drop-arrow-none"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-folder font-18 align-middle"></i>
                                            <i class="mdi mdi-chevron-down"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <span class="dropdown-header">Move to</span>
                                            <a class="dropdown-item" href="javascript: void(0);">Social</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Promotions</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Updates</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Forums</a>
                                        </div>
                                    </div>
                                    <div class="btn-group me-1">
                                        <button type="button"
                                                class="btn btn-sm btn-light dropdown-toggle waves-effect drop-arrow-none"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-label font-18 align-middle"></i>
                                            <i class="mdi mdi-chevron-down"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <span class="dropdown-header">Label as:</span>
                                            <a class="dropdown-item" href="javascript: void(0);">Updates</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Social</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Promotions</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Forums</a>
                                        </div>
                                    </div>

                                    <div class="btn-group me-1">
                                        <button type="button"
                                                class="btn btn-sm btn-light dropdown-toggle waves-effect drop-arrow-none"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="mdi mdi-dots-horizontal font-18 align-middle me-1"></i> More <i
                                                    class="mdi mdi-chevron-down"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <span class="dropdown-header">More Option :</span>
                                            <a class="dropdown-item" href="javascript: void(0);">Mark as Unread</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Add to Tasks</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Add Star</a>
                                            <a class="dropdown-item" href="javascript: void(0);">Mute</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="">
                                    <div class="mt-3">
                                        <div class="">
                                            <ul class="message-list">
                                                <li class="unread">
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk1">
                                                            <label for="chk1" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Lucas Kriebel (via Twitter)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Lucas Kriebel (@LucasKriebel) has
                                                            sent
                                                            you a direct message on Twitter! &nbsp;–&nbsp;
                                                            <span class="teaser">@LucasKriebel - Very cool :) Nicklas, You have a new direct message.</span>
                                                        </a>
                                                        <div class="date">11:49 am</div>
                                                    </div>
                                                </li>

                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk3">
                                                            <label for="chk3" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Randy, me (5)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Last pic over my village &nbsp;–&nbsp;
                                                            <span class="teaser">Yeah i'd like that! Do you remember the video you showed me of your train ride between Colombo and Kandy? The one with the mountain view? I would love to see that one again!</span>
                                                        </a>
                                                        <div class="date">5:01 am</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk4">
                                                            <label for="chk4" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Andrew Zimmer</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Mochila Beta: Subscription Confirmed
                                                            &nbsp;–&nbsp; <span class="teaser">You've been confirmed! Welcome to the ruling class of the inbox. For your records, here is a copy of the information you submitted to us...</span>
                                                        </a>
                                                        <div class="date">Mar 8</div>
                                                    </div>
                                                </li>
                                                <li class="unread">
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk5">
                                                            <label for="chk5" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Infinity HR</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Sveriges Hetaste sommarjobb &nbsp;–&nbsp;
                                                            <span class="teaser">Hej Nicklas Sandell! Vi vill bjuda in dig till "First tour 2014", ett rekryteringsevent som erbjuder jobb på 16 semesterorter iSverige.</span>
                                                        </a>
                                                        <div class="date">Mar 8</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk6">
                                                            <label for="chk6" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Web Support Dennis</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Re: New mail settings &nbsp;–&nbsp;
                                                            <span class="teaser">Will you answer him asap?</span>
                                                        </a>
                                                        <div class="date">Mar 7</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk7">
                                                            <label for="chk7" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">me, Peter (2)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Off on Thursday &nbsp;–&nbsp;
                                                            <span class="teaser">Eff that place, you might as well stay here with us instead! Sent from my iPhone 4 &gt; 4 mar 2014 at 5:55 pm</span>
                                                        </a>
                                                        <div class="date">Mar 4</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk8">
                                                            <label for="chk8" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Medium</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">This Week's Top Stories &nbsp;–&nbsp;
                                                            <span class="teaser">Our top pick for you on Medium this week The Man Who Destroyed America’s Ego</span>
                                                        </a>
                                                        <div class="date">Feb 28</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk9">
                                                            <label for="chk9" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Death to Stock</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Montly High-Res Photos &nbsp;–&nbsp;
                                                            <span class="teaser">To create this month's pack, we hosted a party with local musician Jared Mahone here in Columbus, Ohio.</span>
                                                        </a>
                                                        <div class="date">Feb 28</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk10">
                                                            <label for="chk10" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Revibe</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Weekend on Revibe &nbsp;–&nbsp;
                                                            <span class="teaser">Today's Friday and we thought maybe you want some music inspiration for the weekend. Here are some trending tracks and playlists we think you should give a listen!</span>
                                                        </a>
                                                        <div class="date">Feb 27</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk11">
                                                            <label for="chk11" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Erik, me (5)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Regarding our meeting &nbsp;–&nbsp;
                                                            <span class="teaser">That's great, see you on Thursday!</span>
                                                        </a>
                                                        <div class="date">Feb 24</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk12">
                                                            <label for="chk12" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">KanbanFlow</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Task assigned: Clone ARP's website
                                                            &nbsp;–&nbsp; <span class="teaser">You have been assigned a task by Alex@Work on the board Web.</span>
                                                        </a>
                                                        <div class="date">Feb 24</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk13">
                                                            <label for="chk13" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Tobias Berggren</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Let's go fishing! &nbsp;–&nbsp;
                                                            <span class="teaser">Hey, You wanna join me and Fred at the lake tomorrow? It'll be awesome.</span>
                                                        </a>
                                                        <div class="date">Feb 23</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk14">
                                                            <label for="chk14" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Charukaw, me (7)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Hey man &nbsp;–&nbsp; <span
                                                                    class="teaser">Nah man sorry i don't. Should i get it?</span>
                                                        </a>
                                                        <div class="date">Feb 23</div>
                                                    </div>
                                                </li>
                                                <li class="unread">
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk15">
                                                            <label for="chk15" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">me, Peter (5)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Home again! &nbsp;–&nbsp; <span
                                                                    class="teaser">That's just perfect! See you tomorrow.</span>
                                                        </a>
                                                        <div class="date">Feb 21</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk16">
                                                            <label for="chk16" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Stack Exchange</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">1 new items in your Stackexchange
                                                            inbox
                                                            &nbsp;–&nbsp; <span class="teaser">The following items were added to your Stack Exchange global inbox since you last checked it.</span>
                                                        </a>
                                                        <div class="date">Feb 21</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk17">
                                                            <label for="chk17" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Google Drive Team</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">You can now use your storage in
                                                            Google
                                                            Drive &nbsp;–&nbsp; <span class="teaser">Hey Nicklas Sandell! Thank you for purchasing extra storage space in Google Drive.</span>
                                                        </a>
                                                        <div class="date">Feb 20</div>
                                                    </div>
                                                </li>
                                                <li class="unread">
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk18">
                                                            <label for="chk18" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">me, Susanna (11)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Train/Bus &nbsp;–&nbsp; <span
                                                                    class="teaser">Yes ok, great! I'm not stuck in Stockholm anymore, we're making progress.</span>
                                                        </a>
                                                        <div class="date">Feb 19</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk19">
                                                            <label for="chk19" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">Peter, me (3)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Hello &nbsp;–&nbsp; <span
                                                                    class="teaser">Trip home from Colombo has been arranged, then Jenna will come get me from Stockholm. :)</span>
                                                        </a>
                                                        <div class="date">Mar. 6</div>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="col-mail col-mail-1">
                                                        <div class="checkbox-wrapper-mail">
                                                            <input type="checkbox" id="chk20">
                                                            <label for="chk20" class="toggle"></label>
                                                        </div>
                                                        <a href="#" class="title">me, Susanna (7)</a><span
                                                                class="star-toggle far fa-star"></span>
                                                    </div>
                                                    <div class="col-mail col-mail-2">
                                                        <a href="#" class="subject">Since you asked... and i'm
                                                            inconceivably bored at the train station &nbsp;–&nbsp;
                                                            <span class="teaser">Alright thanks. I'll have to re-book that somehow, i'll get back to you.</span>
                                                        </a>
                                                        <div class="date">Mar. 6</div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>

                                    </div> <!-- card body -->
                                </div> <!-- card -->

                                <div class="row">
                                    <div class="col-7">
                                        Showing 1 - 20 of 289
                                    </div>
                                    <div class="col-5">
                                        <div class="btn-group float-end">
                                            <button type="button"
                                                    class="btn btn-primary waves-light waves-effect btn-sm"><i
                                                        class="fa fa-chevron-left"></i></button>
                                            <button type="button"
                                                    class="btn btn-primary waves-effect waves-light btn-sm"><i
                                                        class="fa fa-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="clearfix"></div>
                        </div>
                    </div>

                </div> <!-- end Col -->

            </div>

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