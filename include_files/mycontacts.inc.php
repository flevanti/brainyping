<?php
if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}

echo "<h3>MY CONTACTS</h3>";
echo "<span class=\"micro_text\" id=\"link_back_to_dashboard\">Back to dashboard</span><br><br>";

echo "<div class=\"row\">";
echo "<div class=\"col-sm-8 mycontacts_wrapper\" id=\"mycontacts_list_wrapper\">";
//AJAX CALL...
echo "</div>";
echo "<div class=\"col-sm-4 mycontacts_wrapper\" id=\"mycontacts_form_wrapper\">";
//AJAX CALL....
echo "</div>";

echo "</div>";


?>

<script>

    $ ('span#link_back_to_dashboard').click (function () {
        window.location = '/monitored/';
    }).css ('cursor', 'pointer');

    function show_addnew_button () {
        $ ("#mycontacts_form_wrapper").html ('<button id="button_new_contact" class="button btn-lg btn-primary">NEW CONTACT</button>');
    }

    function show_list () {
        show_preloader ($ ("#mycontacts_list_wrapper"));

        $.ajax ({
            url  : ajax_calls_home + 'mycontacts_list/',
            cache: false
        })
            .done (function (result) {
                result = $.parseJSON (result);
                if (result['error'] == true) {
                    $ ("#mycontacts_list_wrapper").html (result['error_descr']);
                    return;
                }
                $ ("#mycontacts_list_wrapper").html (result['result']);
            });
    }

    function show_form_panel (panel) {
        show_preloader ($ ("#mycontacts_form_wrapper"));

        $.ajax ({
            url  : ajax_calls_home + 'mycontacts_panel/' + panel + '/',
            cache: false
        })
            .done (function (result) {
                result = $.parseJSON (result);
                if (result['error'] == true) {
                    $ ("#mycontacts_form_wrapper").html (result['error_descr']);
                    return;
                }
                $ ("#mycontacts_form_wrapper").html (result['result']);
            });
    }

    function modify_contact (action, id_contact) {

        $.ajax ({
            url  : ajax_calls_home + 'mycontacts_modify/' + action + '/' + id_contact + '/',
            cache: false
        })
            .done (function (result) {
                result = $.parseJSON (result);
                if (result['error'] == true) {
                    toastr['error'] (result["error_descr"], "MY CONTACTS");
                    return;
                }
                toastr['success'] ('Operation completed successfully!', "MY CONTACTS");
                show_list ();
                show_addnew_button ();
            });
    }

    //"NEW CONTACT" BUTTON HANDLER (THE BIG BUTTON THAT SHOW THE FORM!)
    $ ('body').on ('click', 'button#button_new_contact', function () {
        show_form_panel ('addnew');
    });

    // HANDLER FOR "ADD" BUTTON IN THE FORM (THE SUBMIT!)
    $ ('body').on ('click', '#form_new_contact #btn_add', function () {
        form_data = $ ('#form_new_contact').serialize ();

        $.ajax ({
            type : "POST",
            data : form_data,
            url  : ajax_calls_home + 'mycontacts_addnew/',
            cache: false
        })
            .done (function (result) {
                result = $.parseJSON (result);
                if (result['error'] == true) {
                    toastr['error'] (result["error_descr"], "NEW CONTACT<br>OOOPS");
                    return;
                }
                toastr['success'] ('Operation completed successfully', "NEW CONTACT");
            });

    });

    // HANDLER FOR "CANCEL" BUTTON IN THE FORM
    $ ('body').on ('click', '#form_new_contact #btn_cancel', function () {
        show_addnew_button ();
    });

    //HANDLER FOR "VALIDATE" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.validate_contact', function () {
        validate_contact_message ();
    });

    //HANDLER FOR "NO ACTIONS" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.no_action', function () {
        show_addnew_button ();
    });

    //HANDLER FOR "ENABLE" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.enable_contact', function () {
        modify_contact ('enable', $ ('input#contact_id').val ())
        //alert('enable' + $('input#contact_id').val());
    });

    //HANDLER FOR "UNLINK MONITORS" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.unlink_contact_monitors', function () {
        modify_contact ('unlinkmonitor', $ ('input#contact_id').val ())
        //alert('enable' + $('input#contact_id').val());
    });

    //HANDLER FOR "UNLINK SUBSCRIPTIONS" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.unlink_contact_subscriptions', function () {
        modify_contact ('unlinksubs', $ ('input#contact_id').val ())
        //alert('enable' + $('input#contact_id').val());
    });
    //HANDLER FOR "DISABLE" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.disable_contact', function () {
        modify_contact ('disable', $ ('input#contact_id').val ())
    });

    //HANDLER FOR "DELETE" BUTTON IN CONTACT DETAILS PANEL
    $ ('body').on ('click', 'button.delete_contact', function () {
        txt = 'ARE YOU SURE YOU WANT TO REMOVE THIS CONTACT?\n\n' +
        '-Every association with hosts will be removed\n' +
        '-You will need to activate it again if you want ' +
        'to use it in the future\n\n(If you want you can disable it ' +
        'instead of removing it.\n\nREMOVE IT?';
        if (confirm (txt)) {
            modify_contact ('delete', $ ('input#contact_id').val ())
        }

    });

    //HANDLER FOR CONTACTS ROW WHEN CLICKED
    $ ('body').on ('click', ('div#mycontacts_list_wrapper p'), function () {
        //alert($(this).attr('id_user'));

        var txt = '';
        var status = $ (this).attr ('status');

        txt += 'CONTACT:<br><b>' + $ (this).attr ('contact') + '</b><br><br>';
        txt += 'STATUS: ' + status + '<br>MONITOR LINKED: ' + $ (this).attr ('nrec') + '<br>SUBSCRIPTIONS: ' + $ (this).attr ('nrec_subs') + '<br><br>';

        switch (status) {
            case 'ENABLED':
                txt += '<button class="btn btn-warning btn-sm disable_contact" title="DISABLE">DISABLE </button><br>';
                txt += '<button class="btn btn-warning btn-sm unlink_contact_monitors" title="UNLINK MONITORS">UNLINK MONITORS </button><br>';
                txt += '<button class="btn btn-warning btn-sm unlink_contact_subscriptions" title="UNLINK SUBSCRIPTIONS">UNLINK SUBSCRIPTIONS </button><br>';
                break;
            case 'DISABLED':
                txt += '<button class="btn btn-success btn-sm enable_contact" title="ENABLE">ENABLE </button><br>';
                txt += '<button class="btn btn-warning btn-sm unlink_contact_monitors" title="UNLINK MONITORS">UNLINK MONITORS </button>&nbsp;';
                txt += '<button class="btn btn-warning btn-sm unlink_contact_subscriptions" title="UNLINK SUBSCRIPTIONS">UNLINK SUBSCRIPTIONS </button>&nbsp;';
                break;
            case 'WAITING':
                txt += '<button class="btn btn-danger btn-sm validate_contact" title="VALIDATE">VALIDATE </button><br>';
                break;
        } //END SWITCH

        txt += '<button class="btn btn-info btn-sm no_action" title="NOACTIONS">NO ACTIONS</button> <br><br>';
        txt += '<button class="btn btn-danger btn-sm delete_contact" title="DELETE">DELETE </button>&nbsp;';

        txt += '<input type="hidden" id="contact_id" value="' + $ (this).attr ('id_user') + '">';

        $ ("#mycontacts_form_wrapper").html (txt);

    });

    //VALIDATE BUTTON HANDLER (JUST A MESSAGE)
    function validate_contact_message () {
        alert ('VALIDATE CONTACT:\n\nIn order to validate a contact ' +
        'you need to follow the instructions you received by ' +
        'email or during the initial process.\nIf you cannot find them, ' +
        'delete the contact and add it again.');
    }

    function show_preloader (JQobj) {
        JQobj.html ("<img src=\"/imgs/preloader2.gif\">");
    }

    show_list ();
    show_addnew_button ();

</script>



