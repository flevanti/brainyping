<?php
if (user::getRole() != "USER" and  user::getRole() != "ADMIN" ) {
    echo "Looks like you're not authorized, sorry!";
    return;
}


if ($uriobj->getParam("REFRESH")=="Y") {
    $host_manager->updateUserHostNumber();
}

if ($host_manager->getUserHostsCount("ALL") > 0) { //SHOW A STATUS BAR WITH HOST MONITORED
    echo "<h4>";

    //CONTACTS COUNT
    echo <<<LINE
            <span class="label label-default usercontacts " >
            <span class="fa fa-envelope"></span>
            Contacts
            </span>
LINE;

    //ADD HOST
    echo <<<LINE
            <span class="label label-default addnew " >
            <span class="fa fa-plus"></span>
            Add Host
            </span>
LINE;


    //BULK ACTIONS DROPDOWN
    echo <<<LINE
          <div class="btn-group bulk_action">
          <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown">
            Bulk Action: <span class="caret"></span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li>&nbsp;On selected checkboxes...</li>
            <li><a href="#" flag="PAUSE" class="bulk_action"><span class="fa fa-toggle-off opacity_half"></span> Pause Monitorig</a></li>
            <li><a href="#"  flag="RESUME" class="bulk_action" ><span class="fa fa-toggle-on "></span> Resume Monitoring</a></li>
            <li class="divider"></li>
            <li><a href="#" flag="SHARE"  class="bulk_action"><span class="fa fa-globe "></span> Share</a></li>
            <li><a href="#"  flag="UNSHARE" class="bulk_action" ><span class="fa fa-globe opacity_half"></span> Stop sharing</a></li>
            <li class="divider"></li>
            <li><a href="#"  flag="DELETE"  class="bulk_action"><span class="fa fa-trash-o"></span> Move to trash</a></li>
            <li><a href="#"  flag="UNDELETE"  class="bulk_action"><span class="fa fa-trash-o opacity_half"></span> Restore from trash</a></li>
          </ul>
        </div>
LINE;


    //TOTAL HOST LABEL
    echo <<<LINE
            <span class="label label-primary filter filter_active" filter="ALL" >
            <span class="">Σ</span>
            {$host_manager->getUserHostsCount("ALL")}
            <span class="fa fa-filter filter_icon "></span>
            </span>
LINE;


    //MONITORED HOSTS LABEL
    echo <<<LINE
            <span class="label label-primary filter" filter="MONITORED" >
            <span class="fa fa-toggle-on"></span>
            {$host_manager->getUserHostsCount("MONITORED")}
            <span class="fa fa-filter filter_icon "></span>
            </span>
LINE;

    //PAUSED HOSTS LABEL
    echo <<<LINE
            <span class="label label-info filter" filter="PAUSED" >
            <span class="fa fa-toggle-off opacity_half"></span>
            {$host_manager->getUserHostsCount("PAUSED")}
            <span class="fa fa-filter filter_icon "></span>
            </span>

LINE;


    //HOST MONITORED WITH OK STATUS LABEL
    echo <<<LINE
            <span class="label label-success filter" filter="OK" >
            <span class="fa fa-chain"></span>
            {$host_manager->getUserHostsCount("OK")} ({$host_manager->getUserHostsCount("PERCOK")}%)
            <span class="fa fa-filter filter_icon "></span>
            </span>
LINE;

    //HOST MONITORED WITH NOK STATUS LABEL
    if ($host_manager->getUserHostsCount("PERCNOK") > 0) {
        $class_nok = "label-danger";
    } else {
        $class_nok = "label-success";
    }
    echo <<<LINE
             <span class="label $class_nok filter" filter="NOK" >
            <span class="fa fa-chain-broken"></span>
            {$host_manager->getUserHostsCount("NOK")} ({$host_manager->getUserHostsCount("PERCNOK")}%)
            <span  class="fa fa-filter filter_icon "></span>
            </span>
LINE;


    //SHARED HOSTS LABEL
    echo <<<LINE
            <span class="label label-info filter" filter="SHARED" >
            <span class="fa fa-globe"></span>
            {$host_manager->getUserHostsCount("SHARED")}
            <span  class="fa fa-filter filter_icon "></span>
            </span>
LINE;


    //NOT SHARED HOSTS LABEL
    echo <<<LINE
    <span class="label label-info filter" filter="NOTSHARED" >
            <span class="fa fa-globe opacity_half"></span>
            {$host_manager->getUserHostsCount("NOTSHARED")}
            <span  class="fa fa-filter filter_icon "></span>
            </span>
LINE;


    //HOSTS IN DELETE QUEUE LABEL
    if ($host_manager->getUserHostsCount("DELETED")) {
        echo <<<LINE
            <span class="label label-warning filter" filter="DELETED" >
            <span class="fa fa-trash-o"></span>
            {$host_manager->getUserHostsCount("DELETED")}
            <span  class="fa fa-filter filter_icon "></span>
            </span>
LINE;
    } //END IF


    //REFRESH LABEL
    echo <<<LINE
            <span class="label label-default refresh" >
                <span class="fa fa-refresh "></span>
            </span>
LINE;




    echo "<input type=\"\" id=\"monitored_searchbox\"  placeholder='Search'> ";


    echo "</h4>";
} else {  //NO HOSTS FOUND....
    echo "";
}