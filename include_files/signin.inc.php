<?php
$redir = $uriobj->getParam("redir");
if ($redir === false) {
    $redir = "/monitored";
} else {
    $redir = urldecode(urldecode($redir));
}
?>







<h3 class="form-signin-heading">Sign in</h3>

<?php

echo user::getLoginFormHTML();



?>

<script>

    var redir = '<?php echo $redir; ?>';

    $('#form_signin').submit(function (event){

        //prevent default form submit
        event.preventDefault();

        //get form data
        post_data = $(this).serialize();

        //perform ajax call....
        result = $.ajax(ajax_calls_home + 'signin',{type: "POST",data: post_data, dataType: "json"});
        result.done(function (json_obj) {
            if (json_obj.error === false) {
                toastr['success']("Request completed, thanks","LOGIN REQUEST");
                window.location = redir;
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


    });


</script>








