<!-- Fixed navbar -->
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo $uriobj->URI_gen([""]); ?>">BRAINYPING</a>
        </div>

        <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">



                <!--<li><a href="<?php echo $uriobj->URI_gen(["changelog"]); ?>">v0.4</a></li>-->
                <?php
                    if (user::isLogged()===true) {

                        echo "<li><a href='" . $uriobj->URI_gen(["monitored"]) . "'>Dashboard ";

                        if ($host_manager->getUserHostsCount("PERCOK") >= 0) {
                            if ($host_manager->getUserHostsCount("PERCOK")==100) {
                                $badge_class= "badge_green";
                            } else {
                                $badge_class= "badge_red";
                            }
                            echo "<span class=\"badge $badge_class\">" . $host_manager->getUserHostsCount("PERCOK") ."%</span>";
                        }


                        echo "</a></li>";


                        echo "<li><a href='" . $uriobj->URI_gen(["myprofile"]) . "'>My profile</a></li>";
                        echo "<li><a href='" . $uriobj->URI_gen(["logout"]) . "' id='logout_link'>Logout " . user::getLoginEmail()."</a></li>";
                    } else {
                        echo "<li><a href='" . $uriobj->URI_gen(["signin"]) . "'>Sign In</a></li>";
                    }
                ?>
                <li><a href="<?php echo $uriobj->URI_gen(["contacts"]); ?>">Contact Us</a></li>
                <li><a href="<?php echo $uriobj->URI_gen(["stats"]); ?>">Stats</a></li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>

<!--end fixed navbar -->



<script>
    $('body').on('click','a#logout_link', function(event) {
        event.preventDefault();
        result = $.ajax(ajax_calls_home + 'logout/',{dataType:'json'});
        result.done(function (json_obj) {
            if (json_obj.error == false ) {
                toastr['success']('You have been disconnected','LOGOUT REQUEST COMPLETED');
                window.location='/';
            } else {
                toastr['error']('You are still logged in!','LOGOUT REQUEST NOT COMPLETED');
            }
        });
        result.fail(function() {
            toastr['error']('You are still logged in!','LOGOUT REQUEST FAILED');
        })
    }); //END CLICK LOGOUT HANDLER



</script>