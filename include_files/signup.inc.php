<?php





echo "<h3>SIGNUP</h3>";

echo user::getNewUserFormHTML();

echo "<h4>This is a Beta service, options, configuration or subscription could be deleted anytime</h4>";



?>

<script>

    $ ('body').on ('submit', 'form#form_signup', function (event) {

        event.preventDefault ();

        post_data = $ (this).serialize ();

        response = $.ajax (ajax_calls_home + 'signup', {type: 'POST', data: post_data, dataType: 'json'});
        response.done (function (json_obj) {
            if (json_obj.error == false) {
                toastr["success"] ('Your account has been created!<br>Check your email to validate it. Thanks', 'ACCOUNT CREATION COMPLETED');
                window.location = '/useractivation/'
            }
            else {
                toastr["warning"] (json_obj.error_descr.join ('<br>'), 'ACCOUNT CREATION ERROR');
            }

        })// END DONE METHOD

    }) //END FORM SUBMIT HANDLER


</script>

