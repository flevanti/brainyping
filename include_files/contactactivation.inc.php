<?php


echo "<h3>CONTACT ACTIVATION</h3>";
echo "<br><br>";

$user = new user();

$token = $uriobj->getParam(1);

if ($token === false or $token=="") {
    echo "Unable to validate contact, token not found";
} else {

    $ret = $user->activateContact($token);

    if ($ret === false) {
        echo "Unable to validate contact<br><br>";
        echo $user->last_error;
    } else {
        echo "Contact activated successfully!<br>You can now use it to receive alerts.";
    }


}





?>

<script>
    $('body').on('submit','form#form_useractivation',function(event) {
        event.preventDefault();

        post_data = $(this).serialize();

        response = $.ajax(ajax_calls_home + 'useractivation',{type:'POST',dataType:'json', data: post_data, cache: false});
        response.done(function(json_obj){
            if (json_obj.error == false) {
                toastr["success"]('Your account has been activated!<br>You can now login.', 'ACCOUNT ACTIVATION COMPLETED');
                window.location = '/signin/';
            } else {
                toastr["warning"]('Email or activation code are not correct.', 'ACCOUNT ACTIVATION ERROR');
            } //END IF
        }); //END .DONE HANDLER
        response.fail(function (){
            toastr["error"]('Something went wrong, sorry.', 'ACCOUNT ACTIVATION ERROR');
        });

    }); //END SUBMIT HANDLER



</script>