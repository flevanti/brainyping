<?php


echo "<h3>USER VALIDATION</h3>";

$user = new user();

$token = $uriobj->getParam(1);
echo $user->getUserValidationFormHTML($token);



?>

<script>
    $ ('body').on ('submit', 'form#form_useractivation', function (event) {
        event.preventDefault ();

        post_data = $ (this).serialize ();

        response = $.ajax (ajax_calls_home + 'useractivation', {
            type    : 'POST',
            dataType: 'json',
            data    : post_data,
            cache   : false
        });
        response.done (function (json_obj) {
            if (json_obj.error == false) {
                toastr["success"] ('Your account has been activated!<br>You can now login.', 'ACCOUNT ACTIVATION COMPLETED');
                window.location = '/signin/';
            }
            else {
                toastr["warning"] ('Email or activation code are not correct.', 'ACCOUNT ACTIVATION ERROR');
            } //END IF
        }); //END .DONE HANDLER
        response.fail (function () {
            toastr["error"] ('Something went wrong, sorry.', 'ACCOUNT ACTIVATION ERROR');
        });

    }); //END SUBMIT HANDLER


</script>