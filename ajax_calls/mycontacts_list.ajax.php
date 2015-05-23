<?php

if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}
$user = new user();
$contact_list = $user->getUserContactsList(user::getID());
$sql = "SELECT
                uc.id,
                uc.contact,
                uc.validated,
                uc.contact_type_id,
                uc.primary_contact,
                uc.enabled,
                uc.friendly_name,
                (SELECT
                        COUNT(*)
                    FROM
                        host_contacts hc
                    WHERE
                        hc.id_contact = uc.id) AS nrec,
                    (SELECT
                        COUNT(*)
                    FROM
                        host_subscriptions hs
                    WHERE
                        hs.id_user_contact = uc.id) AS nrec_subs
            FROM
                user_contacts uc
            WHERE
                uc.id_user = " . user::getID() . "
            ORDER BY uc.primary_contact DESC , uc.friendly_name;";
$rs = $mydbh->query($sql);
$page_source = "";
$arr_result["error"] = true;
$arr_result["error_descr"] = "";
$arr_result["result"] = "";
if ($rs === false) { //IF NO RECORDS FOUND....
    $arr_result["error"] = false;
    $arr_result["result"] = "No contacts found";
    die(json_encode($arr_result));
}
while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
    //CHOOSE ICON
    switch ($row["contact_type_id"]) {
        case "EMAIL":
            $icon_class = "fa fa-envelope-o";
            break;
        default:
            $icon_class = "";
    } //END SWITCH
    //GET A STATUS FLAG SO IT'S EASIER FOR JS TO UNDERSTAND CONTACT DETAILS....
    if ($row["validated"] == 1) {
        if ($row["enabled"] == 1) {
            $status = "ENABLED";
        } else {
            $status = "DISABLED";
        }
    } else {
        $status = "WAITING";
    }
    $page_source .= "<p style=\"cursor:pointer;\" nrec=\"" . $row["nrec"] . "\"   nrec_subs=\"" . $row["nrec_subs"] . "\" contact=\"" . $row["friendly_name"] . " - " . $row["contact"] . "\" id_user=\"" . $row["id"] . "\" status=\"$status\">";
    $page_source .= "<span class=\"$icon_class\"></span> " . $row["friendly_name"] . " - " . $row["contact"];
    if ($row["primary_contact"] == 1) {
        $page_source .= " <span class=\"fa fa-lock\" title=\"Primary email\"></span> ";
    }
    switch ($status) {
        case "ENABLED":
            $page_source .= " <span class=\"label label-success micro_text\" title=\"Enabled\">ENABLED</span> ";
            break;
        case "DISABLED":
            $page_source .= " <span class=\"label label-warning micro_text \" title=\"Enabled\">DISABLED</span> ";
            break;
        case "WAITING":
            $page_source .= " <span class=\"label label-danger micro_text \" title=\"Waiting activation\">WAITING ACTIVATION</span> ";
            break;
    }
    $page_source .= "<span class=\"badge micro_text\" title=\"Monitor linked\">" . $row["nrec"] . "</span>";
    $page_source .= "&nbsp;";
    $page_source .= "<span class=\"badge micro_text\" title=\"Subscriptions\">" . $row["nrec_subs"] . "</span>";
    $page_source .= "</p>";
} //END WHILE
$arr_result["error"] = false;
$arr_result["result"] = $page_source;
echo json_encode($arr_result);
