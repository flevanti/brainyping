<?php
if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}
//PROGRESS BAR (CHART LATEST RESULTS) CONFIGURATION
$points = 60;
$filter = $uriobj->getParam("FILTER");
$sort = $uriobj->getParam("SORT");
//RETRIEVE REQUESTED RECORDS (BASED ON FILTER AND SORTED)
$rs = $host_manager->getUserHostsList($filter, $sort);
if ($rs->rowCount() == 0) {
    return;
    exit;
}
echo <<<TABLEHEADER
     <table class="table table-striped table-condensed text_left table-hover tablesorter table_monitored" id="table_hosts_list">
        <thead>
         <tr>
             <!--<th><input type="checkbox" id="selectallcheckbox"></th>-->
             <th>-</th>
             <th>STATUS</th>
             <th>TITLE</th>
             <th></th>
             <th>TYPE</th>
             <th>HOST</th>
             <th>PORT</th>
             <th>ENABLED</th>
             <th>INT.</th>
             <th>&nbsp;</th>
         </tr>
        </thead>
TABLEHEADER;
//SMALL FUNCTION TO WRITE TD ELEMENT.... JUST TO SAVE SOME TIME... NOTHING SPECIAL...
function write_td($txt, $class = "", $colspan = 1) {
    echo "<td " . ($class != "" ? "class=\"$class\"" : "") . " colspan=\"$colspan\">$txt</td>";
}

echo "<tbody>";
while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr id=\"" . $row["public_token"] . "\">";
    //CHECKBOX DEPRECATED (FOR THE MOMENT)...
    //$txt = "<input type=\"checkbox\" name=\"public_tokens[]\" value=\"".$row["public_token"]."\" id=\"cb_".$row["public_token"]."\" class=\"host_checkbox\">";
    $txt = "-";
    write_td($txt);
    //STATUS AND TITLE COLUMN
    switch (true) {
        case ($row["delete_queue"] != null): //DELETED HOST
            $class_label = " label-warning ";
            $txt_label = "DELETED";
            $txt_hidden_for_search = "#DELETED #ALL";
            $txt = $row["title"] . " --- This host is in the <b>DELETE QUEUE</b>, it will be delete in the next hours ";
            $colspan = 100;
            break;
        case ($row["enabled"] == 0): //PAUSED HOST
            $class_label = " label-info ";
            $txt_label = "PAUSED";
            $txt_hidden_for_search = "#PAUSED #ALL";
            $txt = $row["title"];
            $colspan = 1;
            break;
        case ($row["enabled"] == 1 and $row["check_result"] == "OK"): //MONITORING ACTIVE AND LAST RESULT OK!
            $class_label = " label-success ";
            $txt_label = "OK";
            $txt_hidden_for_search = "#MONITORED #OK #ALL";
            $txt = $row["title"];
            $colspan = 1;
            break;
        case ($row["enabled"] == 1 and $row["check_result"] == "NOK"): //MONITORING ACTIVE AND LAST RESULT OK!
            $class_label = " label-danger ";
            $txt_label = "FAILED";
            $txt_hidden_for_search = "#MONITORED #NOK #ALL";
            $txt = $row["title"];
            $colspan = 1;
            break;
        default:
            $class_label = " label-danger ";
            $txt_label = "UNKNOWN";
            $txt_hidden_for_search = " #ALL";
            $txt = $row["title"];
            $colspan = 1;
            break;
    }
    //STATUS
    write_td(" <span class=\"label $class_label\">$txt_label</span>
                <span class=\"hidden\">$txt_hidden_for_search</span>");
    //TITLE
    write_td($txt, "", $colspan);
    if ($colspan > 1) { //IF COLSPAN > 1 MEANS WE WROTE THE FULL LINE FOR A LONG MESSAGE, SKIP NEXT INFO...
        continue; //NEXT RECORD PLEASE....
    }
    if (user::getRole() == "ADMIN") { //ADMIN USER
        if ($row["homepage"] == 1) {
            $txt_hidden_homepage = "<span class=\"hidden\" >#HP</span>";
            $span_homepage = "<span class=\"fa fa-home hp_icon shared\" host_public_token=\"" . $row["public_token"] . "\"></span> ";
        } else {
            $txt_hidden_homepage = "<span class=\"hidden\">#NOHP</span>";
            $span_homepage = "<span class=\"fa fa-home hp_icon unshared\" host_public_token=\"" . $row["public_token"] . "\"></span> ";
        }
    } else { //NOT ADMIN
        $txt_hidden_homepage = "";
        $span_homepage = "";
    }
    //CHECK IF HOST IS PUBLIC / SHARED (TO SHOW GLOBE)
    switch (true) {
        case ($row["public"] == 1):
            $txt = "<span class=\"fa fa-globe share_icon shared\" title=\"Shared\"  host_public_token=\"" . $row["public_token"] . "\" id=\"shared_icon_" . $row["public_token"] . "\"></span>";
            $txt_hidden_for_search = " <span class=\"hidden\">#SHARED</span>";
            break;
        case ($row["public"] == 0):
            $txt = "<span class=\"fa fa-globe share_icon unshared\" title=\"Not shared\" host_public_token=\"" . $row["public_token"] . "\" id=\"shared_icon_" . $row["public_token"] . "\"></span>";
            $txt_hidden_for_search = " <span class=\"hidden\">#NOTSHARED</span>";
            break;
    }
    write_td($span_homepage . $txt . $txt_hidden_for_search . $txt_hidden_homepage);
    write_td($row["check_type"]);  //WRITE CHECK TYPE
    write_td($row["host"]); //WRITE HOST
    write_td($row["port"], " text_center"); //WRITE PORT
    //ENABLED
    if ($row["enabled"] == 1) {
        $txt = "<span class=\"fa fa-toggle-on enabled_icon monitoring\" title=\"Monitoring\" host_public_token=\"" . $row["public_token"] . "\" id=\"enabled_icon_" . $row["public_token"] . "\"><span class=\"hidden\">1</span></span>";
    } else {
        $txt = "<span class=\"fa fa-toggle-on enabled_icon paused \" title=\"Paused\" host_public_token=\"" . $row["public_token"] . "\" id=\"enabled_icon_" . $row["public_token"] . "\"><span class=\"hidden\">0</span></span>";
    }
    write_td($txt, " text_center"); //WRITE ENABLED
    write_td($row["minutes"] . "'", " text_center");   //MINUTES
    //GEARS ICON - EDIT
    $txt = "";
    $txt .= "<span class=\"fa fa-bar-chart info_monitored_icon\" host_public_token=\"" . $row["public_token"] . "\" title=\"" . $row["public_token"] . "\"></span>";
    $txt .= "&nbsp;";
    $txt .= "<span class=\"fa fa-pencil edit_monitored_icon\" host_public_token=\"" . $row["public_token"] . "\" title=\"" . $row["public_token"] . "\"></span>";
    $txt .= "&nbsp;&nbsp;&nbsp;";
    $txt .= "<span class=\"fa fa-trash delete_monitored_icon\" host_public_token=\"" . $row["public_token"] . "\" title=\"" . $row["public_token"] . "\"></span>";
    write_td($txt);
    echo "</tr>";
} //END WHILE
echo "</tbody>";
echo "</table>";
echo "</div>";
