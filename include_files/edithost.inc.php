<?php
if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";
    return;
}

if ($uriobj->getParam(1)=="new") {
    $title = "NEW MONITORED HOST";
    $id_host = "new";
} else {
    $title = "EDIT MONITORED HOST";
    $id_host = $uriobj->getParam(1);
};


//Prepare host to edit (load it in memory if edit or create a dummy empty record if add new
//if false id host error (not owned by user or wrong)
if ($host_manager->editHost($id_host) === false) {
    echo "Unable to prepare host for the requested operation";
    return;
};

echo "<h4>$title</h4>";



?>


<form class="text_left" style="margin-left: auto; margin-right: auto; width: 450px;" role="form" method="POST" action="" id="formedithost">
    <div class="form-group">
        <label for="title">Monitor friendly name</label>
        <input type="text" class="form-control" id="title" name="title" placeholder="Monitor friendly name" value="<?php echo t2f($host_manager->editedHostGetInfo("title"));?>">
    </div>

    <!--MONITOR TYPE AND INTERVAL CONFIGURATION-->

    <div style="float:left; width:80%;">
        <div class="form-group">
            <label for="check_type">Monitor type</label>
            <select id="check_type" name="check_type" class="form-control"  <?php echo ($id_host != "new"?"disabled=\"disabled\"":""); ?> >
                <?php
                foreach ($host_manager->getCheckTypes() as $value) {
                    //Check if checktype is the same of the host in edit
                    if ($host_manager->editedHostGetInfo("checktype") == $value["id"]) {
                        $checktype_selected = "selected";
                    } else {
                        $checktype_selected = "";
                    } //END IF
                    echo "<option value=\"". $value["id"] ."\" $checktype_selected >" . t2v($value["descr"]) . "</option>";
                } //END FOREACH CHECKTYPE
                ?>
            </select>

            <input type="hidden" name="check_type_old" id="check_type_old" value="<?php echo t2f($host_manager->editedHostGetInfo("checktype"));?>">

        </div>
    </div>

    <div style="float:left; width:20%; padding-left: 15px;">
        <div class="form-group" >
            <label for="interval">Interval</label>
            <input type="text" class="form-control" name="interval" id="interval" placeholder="Minutes" value="<?php echo t2v($host_manager->editedHostGetInfo("interval"));?>">
            <input type="hidden" name="interval_old" id="interval_old"  value="<?php echo t2v($host_manager->editedHostGetInfo("interval"));?>">
        </div>
    </div>
    <!--END MONITOR TYPE AND INTERVAL CONFIGURATION-->

    <!--Show text field for host and port-->
    <div id="host_port_section" class="checktype_section" >
        <p class="micro_text CHECKCONN">This monitor try to connect to the specified host/port to verify that a
            service is up and running and replies to the request.</p>
        <p class="micro_text FTPCONN">This monitor try to connect to the specified FTP server to verify that
            service is up and running and replies to the request.</p>
        <p class="micro_text SMTPCONN">This monitor try to connect to the specified SMTP server to verify that
            service is up and running and replies to the request.</p>
            <!--HOST AND PORT -->
            <div style="float:left; width:80%;">
                <div class="form-group">
                    <label for="host_address">Host Domain / IP</label>
                    <input type="text" class="form-control" id="host_address" name="host_address" placeholder="Host domain or IP" value="<?php echo t2v($host_manager->editedHostGetInfo("host"));?>">
                </div>
            </div>

            <div style="float:left; width:20%; padding-left: 15px;">
                <div class="form-group" >
                    <label for="host_port">Port</label>
                    <input type="text" class="form-control" name="host_port" id="host_port" placeholder="Port" value="<?php echo t2v($host_manager->editedHostGetInfo("port"));?>">
                </div>
            </div>
            <!-- END HOST AND PORT-->
    </div>
    <!--END CHECKCONN SECTION-->

    <!--Show text field for URL -->
    <div id="url_only_section" class="checktype_section" >
        <p class="micro_text HTTPHEADER" >This monitor verifies if the HTTP connection status is OK (code 200).</p>
        <p class="micro_text WEBKEYWORD" >This monitor verifies if a word is present (or not using # as first char) in a webpage</p>
            <div class="form-group">
                <label for="host_url">URL to check (Please specify HTTP:// or HTTPS://)</label>
                <input type="text" class="form-control" id="host_url" name="host_url" placeholder="URL" value="<?php echo t2v($host_manager->editedHostGetInfo("host"));?>">
            </div>

        <div class="form-group checktype_additional_fields WEBKEYWORD">
            <label for="host_url_keyword">Word to look for (# to invert search result)</label>
            <input type="text" class="form-control" id="host_url_keyword" name="host_url_keyword" placeholder="Keyword" value="<?php echo t2v($host_manager->editedHostGetInfo("keyword"));?>">
        </div>



    </div>


    <!--END HTTPHEADER SECTION-->



    <!-- ALERT CONTACTS-->
    <div class="form-group" >
        <label>Alert contacts</label>
        <!--ALERT CONTACT WRAPPER-->
        <div style="width:100%;border:1px solid lightgray; background-color: #ffffff; max-height: 100px; overflow-y: auto; border-radius: 5px; padding-left:5px;" >

            <?php
                if (count($host_manager->editedHostGetInfo("contacts")) == 0) {
                    echo "No contacts available.<br>Please visit Contacts section in the main Dashboard page to add one";
                } else {
                    foreach ($host_manager->editedHostGetInfo("contacts") as $value) {
                        //Decide contacts type
                        switch ($value["contact_type_id"]) {
                            case ("EMAIL"):
                                $class = "fa fa-envelope-o";
                                break;
                            default:
                                $class = "fa fa-external-link";
                                break;
                        } //END SWITCH CONTACT TYPE

                        //Check if contact is selected (Only for edit host....)
                        if ($id_host != "new" and is_numeric($value["id_contact"]) ) {
                            $checked = " checked ";
                        } else {
                            $checked = "";
                        }
                        echo "<div class=\"checkbox\">";
                        echo "<label>";
                        echo "<input type=\"checkbox\" name=\"contacts[]\" value=\"" . $value["id"] ."\" $checked> <span class=\"$class\"></span> " . $value["contact"] . " ";
                        echo "</label>";
                        echo "</div>";

                    } //END FOREACH CONTACT

                } //END IF

            ?>


        </div> <!-- END ALERT CONTACTS WRAPPER-->
    </div> <!-- END ALERT CONTACTS-->


    <div class="clearfix"></div>


    <!--submit & cancel-->
    <button type="submit" class="btn btn-primary">Save</button>
    <button type="button" class="btn btn-default" id="cancel_edit">Cancel</button>
    <input type="hidden" name="public_token" value="<?php echo ($id_host=="new"?"new":$host_manager->editedHostGetInfo("public_token"));?>">
</form>




<script>

    $('form#formedithost').submit(function(event){
        event.preventDefault();

        var post_data = $(this).serialize();

        var error_descr;

        console.log(post_data);

        $.ajax({
            type: "POST",
            data: post_data,
            url: ajax_calls_home + 'edithost/',
            cache: false
        })
            .done(function( result ) {
                try {
                    result = $.parseJSON(result);
                } catch (err) {
                    toastr['error']('OOOPPS! Something went terribly wrong during JSON parsing!',"SAVE FORM");
                    return false;
                }

                if (result["result"]==true) {
                    toastr['success']('Operation completed successfully',"SAVE FORM");

                } else {
                    if (result["error_descr"] != undefined) {
                        error_descr = "<br>" + result["error_descr"].join("<br>");
                    } else {
                        error_descr = "";
                    }
                    toastr['error']('OOOPPS!' + error_descr,"SAVE FORM");
                }
            });




        //remove unload handler to leave the page!
        $(window).unbind('beforeunload');
    });

    $('button#cancel_edit').click(function(){
            window.location='/monitored';
    });





    $('select#check_type').change(function(){
        var checktype = $(this).val();
        //HIDE ALL THE FIELDS RELATED TO SPECIFIED CHECK TYPES
        $('div.checktype_section').hide();
        //SHOW FILED RELATED ONLY TO THE CHECK TYPE SELECTED
        if (checktype == 'CHECKCONN' || checktype=='SMTPCONN' || checktype=='FTPCONN') {
            $('div.checktype_section#host_port_section').show();
            $('div.checktype_section#host_port_section p.micro_text').hide();
            $('div.checktype_section#host_port_section p.micro_text.' + checktype).show();

        }
        if (checktype == 'HTTPHEADER' || checktype=='WEBKEYWORD' ) {
            $('div.checktype_section#url_only_section').show();
            $('div.checktype_section#url_only_section p.micro_text').hide();
            $('div.checktype_section#url_only_section p.micro_text.' + checktype).show();

        }

        //Additional fields
        $('div.checktype_section#url_only_section div.checktype_additional_fields').hide();
        $('div.checktype_section#url_only_section div.checktype_additional_fields.' + checktype).show();

    });

    //Trigger a dummy change on select element to show fields related to the right check type
    $('select#check_type').trigger('change');



    $(document).ready(function(){
        //IF A CHANGE IS DETECTED IN THE FORM WE CREATE AN UNLOAD/BEFOREUNLOAD HANDLER TO CONFIRM BEFORE LEAVING THE PAGE....
        $('form#formedithost').change(function(){
            ev = $._data(window,'events');
            if (ev.beforeunload == undefined) {
                //unload event handler not found, we add it....
                //if changes detected we create an unload handler...
                $(window).bind('beforeunload', function(){
                    return '>>> All unsaved changes will be lost! <<<';
                });
            } else {
                //Event handler already present - just return
                return;
            }
        });
    });


</script>



