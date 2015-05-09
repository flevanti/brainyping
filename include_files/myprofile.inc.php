<?php

if (user::isLogged() !== true) {
    echo "Looks like you're not authorized, sorry!";
    return;
}



echo "<h4>MY PROFILE</h4>";

echo "<div class=\"row\" style=\"text-align: left;\">";

            //EMAIL AND PASSWORD
            ////////////////////////////////////
            echo "<div class=\"col-md-4\">";
                    echo "<b>EMAIL / USERID</b><br>";
                    echo user::getLoginEmail() . "<br><br><br>";
                    echo "<span style=\"cursor: pointer;\" id=\"change_pwd_link\"><b>CHANGE PASSWORD</b></span><br>";
                    echo "<br>";
                    echo "<form class=\"form-newpwd\" style=\"width: 250px; display: none;\" role=\"form\" method=\"post\" id=\"form_newpwd\">";
                    echo "<label for=\"currentpwd\">Current password</label>";
                    echo "<input type=\"password\" class=\"form-control input-sm\" placeholder=\"Current password\"  id=\"currentpwd\"  name=\"currentpwd\"  autofocus>";
                    echo "<label for=\"newpwd\">New password</label>";
                    echo "<input type=\"password\" class=\"form-control input-sm\" placeholder=\"New password\"  id=\"newpwd\"  name=\"newpwd\"  autofocus>";
                    echo "<input type=\"password\" class=\"form-control input-sm\" placeholder=\"Repeat new password\"  id=\"newpwd2\"  name=\"newpwd2\"  autofocus>";
                    echo "<input type=\"hidden\" value=\"".user::getToken()."\" name=\"token\">";
                    echo "<br>";
                    echo "<button type=\"button\" class=\"btn btn-sm btn-primary\" id=\"submit_newpwd\">Change Password</button>";
                    echo " &nbsp;";
                    echo "<button type=\"button\" class=\"btn btn-sm btn-default\" id=\"cancel_newpwd\">Discard changes</button>";
                    echo "</form>";
                    echo "<br><br><br>";
            echo "</div>";

            //CONTACTS
            ////////////////////////////////
            echo "<div class=\"col-md-4\">";
                    echo "<b>CONTACTS LIST</b><br>";

                    $sql = "select count(*) n from user_contacts where id_user = " . user::getID() . ";";
                    $rs = $mydbh->query($sql);
                    $n = $rs->fetch(PDO::FETCH_ASSOC)["n"];
                    echo "You have <b>$n</b> contacts configured.<br>";
                    echo "<button type=\"button\" class=\"btn btn-sm btn-primary\" id=\"manage_contacts\">Manage Contacts</button>";

                    echo "<br><br>";
                    echo "<b>MONITOR SUBSCRIPTIONS</b><br>";

                    $sql = "select count(*) n from host_subscriptions where id_user = " . user::getID() . ";";
                    $rs = $mydbh->query($sql);
                    $n = $rs->fetch(PDO::FETCH_ASSOC)["n"];
                    echo "You have <b>$n</b> subscriptions to public monitors.<br>";
                    echo "<button type=\"button\" class=\"btn btn-sm btn-primary\" id=\"manage_subscriptions\">Manage Subscriptions</button>";
                    echo "<br><br><br>";
            echo "</div>";


            //HOSTS
            ////////////////////////////
            echo "<div class=\"col-md-4\">";
                echo "<b>MY MONITORS</b><br>";
                $sql = "select count(*) n from hosts where id_user = " . user::getID() . ";";
                $rs = $mydbh->query($sql);
                $n = $rs->fetch(PDO::FETCH_ASSOC)["n"];
                echo "You have <b>$n</b> monitors configured.<br>";
                echo "<button type=\"button\" class=\"btn btn-sm btn-primary\" id=\"manage_monitors\">Manage Monitors</button>";
                echo "<br><br><br>";
            echo "</div>";




echo "</div>";


?>


<script>
    var newpwdform = false;

    $('#change_pwd_link').click(function () {
        toggle_newpwd_form();
    });

    $('#cancel_newpwd').click(function(){
        $('#form_newpwd')[0].reset();
        toggle_newpwd_form();
    });

    $('#submit_newpwd').click(function(){
        submit_newpwd($('#form_newpwd'));
    });

    function submit_newpwd(Jobj) {

        postdata = Jobj.serialize();

        //perform ajax call....
        result = $.ajax(ajax_calls_home + 'changepwd',{type: "POST",data: postdata, dataType: "json"});
        result.done(function (json_obj) {
            if (json_obj.result === true) {
                toastr['success']("Request completed, thanks","CHANGE PASSWORD");
                $('#cancel_newpwd').trigger('click');
            } else {
                toastr['warning'](json_obj.error_descr,"LOGIN REQUEST");
            }

        }); //END .DONE HANDLER
        result.fail (function () {
            toastr['error']("Something went wrong","LOGIN REQUEST ERROR");
        }); //END .FAIL HANDLER
        result.error(function () {
            toastr['error']("Unknown error during request, sorry.","LOGIN REQUEST");
        });

        return false;




    }

    function toggle_newpwd_form () {
        if (newpwdform == true) {
            $('#form_newpwd').slideUp();
            newpwdform = false;
        } else {
            $('#form_newpwd').slideDown();
            newpwdform = true;
        }
    }


    $('button#manage_monitors').click(function(){
       window.location = '/monitored/';
    });

    $('button#manage_contacts').click(function(){
        window.location = '/mycontacts/';
    });

    $('button#manage_subscriptions').click(function(){
        alert('Not yet implemented');
    });

</script>