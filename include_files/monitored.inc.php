<?php

if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}


//Get filter from URL if present
$filter = $uriobj->getParam("filter");
//Get sort from URL if present
$sort = $uriobj->getParam("sort");

//Get refresh from URL if present
if ($uriobj->getParam('REFRESH') == "Y") {
    $host_manager->updateUserHostNumber();
}

//echo "<br>";


echo "<h4>DASHBOARD</h4>";


echo "<div id=\"monitored_panel\">"; //MONITORED HOST WRAPPER
echo "<div id=\"hosts_list_bar\"></div>";
echo "<button id=\"button_start_from_here\" class=\"button btn-lg btn-primary\">START FROM HERE!</button>";
echo "<div class=\"table_scroll\" id=\"hosts_list_wrapper\"></div>";


echo "</div>"; //END MONITORED HOST WRAPPER
echo "<span class=\"micro_text\">THESE INFORMATION ARE REFRESHED EVERY FEW MINUTES. CHANGES ARE APPLIED IMMEDIATELY BUT VISIBLE AFTER A SHORT PERIOD.</span>";





?>

<script>


//filter
var filter = '<?php echo $filter;?>';
//refresh
var refresh = 'REFRESH/Y/';

$ ('button#button_start_from_here').hide ();

//ADDNEW (START FROM HERE) BUTTON HANDLER
$ ('body').on ('click', 'button#button_start_from_here', function () {
    window.location = '/edit/new/';
});

//ADDNEW LINK HANDLER
$ ('body').on ('click', 'span.addnew', function () {
    window.location = '/edit/new/';
});

//USERCONTACTS LNK HANDLER
$ ('body').on ('click', 'span.usercontacts', function () {
    window.location = '/mycontacts/';
});

function show_preloader (JQobj) {
    JQobj.html ("<img src=\"/imgs/preloader2.gif\">");
}

function get_checkbox_values () {
    arr_public_tokens = $ (".host_checkbox:checked").map (function () {
        return this.value;
    }).get ();
    return arr_public_tokens;
}

function bulk_action (flag) {

    //this is used to perform AJAX CALLS.... NOT NOW...
    arr_public_tokens = get_checkbox_values ();
    if (arr_public_tokens.length == 0) {
        return false;
    }

    txt = "";

    if (flag == 'RESUME') {
        txt = "Are you sure you want to resume monitoring the selected hosts?";
    }
    if (flag == 'PAUSE') {
        txt = "Are you sure you want to pause monitoring the selected hosts?";
    }
    if (flag == 'DELETE') {
        txt = "WARNING MESSAGE!\n\nARE YOU SURE YOU WANT TO DELETE SELECTED HOSTS?\n\nCOLLECTED RESULTS WILL BE DELETED PERMANENTLY.\n\n\nARE YOU SURE?";
    }
    if (flag == 'UNDELETE') {
        txt = "Restore selected hosts? \n\nPlease note that hosts will need to be enabled to start monitoring again.\n\nRestore?";
    }
    if (flag == 'SHARE') {
        txt = "Share selected hosts? \n\nSelected host will be shown in the Homepage\n\nShare?";
    }
    if (flag == 'UNSHARE') {
        txt = "Stop sharing selected hosts? \n\nSelected host will not be shown in the Homepage anymore\n\nStop sharing?";
    }

    if (txt == "") {
        alert ("Something went wrong, sorry");
        return false;
    }

    user_confirm = confirm (txt);

    if (user_confirm === true) {
        bulk_action_execute (flag, arr_public_tokens);
    }
} //END FUNCTION BULK ACTION

function bulk_action_execute (flag, arr_public_tokens) {

    show_preloader ($ ("#hosts_list_wrapper"));

    arr_data = new Object ();
    arr_data["flag"] = flag;
    arr_data["public_tokens"] = arr_public_tokens;

    $.ajax ({
        type : "POST",
        data : arr_data,
        url  : ajax_calls_home + 'monitored_bulk_action/',
        cache: false
    })
        .done (function (result) {
            result = $.parseJSON (result);
            if (result["result"] == true) {
                toastr['success'] ('Operation completed successfully', "'" + flag + "' BULK ACTION");
                refresh_bar (refresh);
                refresh_list ();
            }
            else {
                toastr['error'] ('OOOPPS! Something went terribly wrong!', "'" + flag + "' BULK ACTION");
            }
        });

}

//FILTERS HANDLER
$ ('body').on ('click', 'span.filter', function () {
    filter = $ (this).attr ('filter');
    $ ('#monitored_searchbox').val ('#' + filter);
    $ ('#monitored_searchbox').trigger ('keyup');
});

//BULK ACTION HANDLER
$ ('body').on ('click', 'a.bulk_action', function () {
    flag = $ (this).attr ('flag');
    bulk_action (flag);

});

//REFRESH HANDLER
$ ('body').on ('click', 'span.refresh', function () {
    filter = "ALL";
    refresh_bar (refresh);
    refresh_list ();

});

//SELECT ALL CHECKBOX HANDLER
$ ('body').on ('click', 'input#selectallcheckbox', function () {
    $ ('tr:visible .host_checkbox').prop ('checked', this.checked);
});

//CHECKBOX CLICK HANDLER
$ ('body').on ('click', 'input.host_checkbox', function (event) {
    //Avoid propagation of the click event because we're monitoring also row
    //This to avoid multiple click events triggering
    event.stopPropagation ();
});

//EDIT HOST ICON
$ ('body').on ('click', 'span.edit_monitored_icon', function () {
    pt = $ (this).attr ('host_public_token');
    if (pt == "") {
        return false;
    }
    window.location.href = "/edit/" + $ (this).attr ('host_public_token') + '/';
});

//INFO CHART HOST ICON
$ ('body').on ('click', 'span.info_monitored_icon', function () {
    pt = $ (this).attr ('host_public_token');
    if (pt == "") {
        return false;
    }
    window.location.href = "/info/" + $ (this).attr ('host_public_token') + '/';
});

//TABLE ROWS CLICKABLE TO SELECT RECORD HANDLER
/*
 $('body').on('click','table#table_hosts_list tr',function() {
 cb = $('input#cb_' + this.id);
 if (cb.prop('checked')==true) {
 cb.prop('checked',false);
 } else {
 cb.prop('checked',true);
 }
 });
 */

function refresh_list () {

    show_preloader ($ ("#hosts_list_wrapper"));

    $.ajax ({
        url  : ajax_calls_home + 'monitored_list/',
        cache: false
    })
        .done (function (html) {
            if (html == "") {
                $ ("#hosts_list_wrapper").html ("");
                $ ('button#button_start_from_here').show ();
            }
            else {
                $ ("#hosts_list_wrapper").html (html);
                //$(".peity_status").peity("bar");
                table_sort ();
                if ($ ('#monitored_searchbox').val ().trim () != '') {
                    $ ('#monitored_searchbox').trigger ('keyup');
                }
            }
        });
}

function refresh_bar (refresh_flag) {
    show_preloader ($ ("#hosts_list_bar"));
    $.ajax ({
        url  : ajax_calls_home + 'monitored_bar/' + refresh_flag,
        cache: false
    })
        .done (function (html) {
            $ ("#hosts_list_bar").html (html);
            $ ('span.filter[filter=\'' + filter + '\']').addClass ('filter_active');
        });
}

function table_sort () {
    $ ('#table_hosts_list').tablesorter ({
        headers: {
            0: {sorter: false},
            1: {sorter: false},
            3: {sorter: false},
            7: {sorter: false},
            9: {sorter: false}
        }
    });
}

$ (document).ready (function () {
    refresh_bar ('');
    refresh_list ();
});

$ ('body').on ('click', 'span.hp_icon', function () {
    //alert($(this).hasClass('shared'));

    $ (this).toggleClass ('fa-spin');

    if ($ (this).hasClass ('shared')) {
        new_value = 0;
    }
    else {
        new_value = 1;
    }

    $.ajax ({
        url    : ajax_calls_home + 'edithostattrib/' + $ (this).attr ('host_public_token') + '/hp/' + new_value + '/',
        cache  : false,
        obj_ref: this
    })
        .done (function (result) {
            $ (this.obj_ref).toggleClass ('fa-spin');
            result = $.parseJSON (result);
            if (result["error"] == false) {
                toastr['success'] ('Operation completed successfully!', "UPDATE MONITOR");
                $ (this.obj_ref).toggleClass ('shared');
                $ (this.obj_ref).toggleClass ('unshared');
            }
            else {
                toastr['error'] ('OOOPS! ' + result["error_descr"], "UPDATE MONITOR");
            }
        });
});

$ ('body').on ('click', 'span.share_icon', function () {
    //alert($(this).hasClass('shared'));

    $ (this).toggleClass ('fa-spin');

    if ($ (this).hasClass ('shared')) {
        new_value = 0;
    }
    else {
        new_value = 1;
    }

    $.ajax ({
        url    : ajax_calls_home + 'edithostattrib/' + $ (this).attr ('host_public_token') + '/public/' + new_value + '/',
        cache  : false,
        obj_ref: this
    })
        .done (function (result) {
            $ (this.obj_ref).toggleClass ('fa-spin');
            result = $.parseJSON (result);
            if (result["error"] == false) {
                toastr['success'] ('Operation completed successfully!', "UPDATE MONITOR");
                $ (this.obj_ref).toggleClass ('shared');
                $ (this.obj_ref).toggleClass ('unshared');
            }
            else {
                toastr['error'] ('OOOPS! ' + result["error_descr"], "UPDATE MONITOR");
            }
        });
});

$ ('body').on ('click', 'span.enabled_icon', function () {
    //alert($(this).hasClass('shared'));

    $ (this).toggleClass ('fa-spin');

    if ($ (this).hasClass ('monitoring')) {
        new_value = 0;
    }
    else {
        new_value = 1;
    }

    $.ajax ({
        url    : ajax_calls_home + 'edithostattrib/' + $ (this).attr ('host_public_token') + '/enabled/' + new_value + '/',
        cache  : false,
        obj_ref: this
    })
        .done (function (result) {
            $ (this.obj_ref).toggleClass ('fa-spin');
            result = $.parseJSON (result);
            if (result["error"] == false) {
                toastr['success'] ('Operation completed successfully!', "UPDATE MONITOR");
                $ (this.obj_ref).toggleClass ('monitoring');
                $ (this.obj_ref).toggleClass ('paused');
            }
            else {
                toastr['error'] ('OOOPS! ' + result["error_descr"], "UPDATE MONITOR");
            }
        });
});

$ ('body').on ('click', 'span.delete_monitored_icon', function () {

    ret = confirm ('Are you sure you want to remove this monitor?\n\nAll information related will be deleted!\n\nPROCEED?');

    if (ret == false) {
        return;
    }
    $ (this).toggleClass ('fa-spin');

    $.ajax ({
        url    : ajax_calls_home + 'edithostattrib/' + $ (this).attr ('host_public_token') + '/delete/1/',
        cache  : false,
        obj_ref: this
    })
        .done (function (result) {
            $ (this.obj_ref).toggleClass ('fa-spin');
            result = $.parseJSON (result);
            if (result["error"] == false) {
                toastr['success'] ('Operation completed successfully!', "DELETE MONITOR");
                $ (this.obj_ref).toggleClass ('red_text_deleted_icon');
            }
            else {
                toastr['error'] ('OOOPS! ' + result["error_descr"], "DELETE MONITOR");
            }
        });
});

$.fn.peity.defaults.bar = {
    delimiter: ",",
    fill     : function (value) {

        if (value < 0) {
            return "red";
        }
        else {
            return "#DAF6DA";
        }
    },
    height   : 15,
    max      : null,
    min      : -1,
    padding  : 0,
    width    : 80
};

// Write on keyup event of keyword input element
$ ('body').on ("keyup", "#monitored_searchbox", function () {
    _this = this;
    // Show only matching TR, hide rest of them
    $.each ($ ("#table_hosts_list tbody").find ("tr"), function () {
        //console.log($(this).text());
        if ($ (this).text ().toLowerCase ().indexOf ($ (_this).val ().toLowerCase ()) == -1)
            $ (this).hide ();
        else
            $ (this).show ();
    });
});


</script>



