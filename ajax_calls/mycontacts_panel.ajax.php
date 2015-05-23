<?php

if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}
$arr_result["error"] = true;
$arr_result["error_descr"] = "";
$arr_result["result"] = "";
if ($uriobj->getParam(2) === false or $uriobj->getParam(2) == "") {
    $arr_result["error_descr"] = "Could not find panel parameter";
    goto fireOutput;
}
//FORM TO ADD NEW CONTACT
if ($uriobj->getParam(2) == "addnew") {
    $script = "";
    $sql = "SELECT * FROM user_contact_types WHERE enabled=1 ORDER BY name;";
    $rs_type = $mydbh->query($sql);
    if ($rs_type->rowCount() == 0) {
        $arr_result["error_descr"] = "Contact Type not defined";
        goto fireOutput;
    }
    $script .= "<h3>ADD NEW CONTACT</h3>";
    $script .= "<form id=\"form_new_contact\"  >";
    $script .= "<div class=\"form-group\">";
    $script .= "<label for=\"contact_type\">Contact type</label>";
    $script .= "<select class=\"form-control\" name=\"contact_type\" id=\"contact_type\">";
    while ($row = $rs_type->fetch(PDO::FETCH_ASSOC)) {
        $script .= "<option value=\"" . $row["id"] . "\">" . $row["name"] . "</option>";
    }
    $script .= "</select>";
    $script .= "</div>";
    $script .= "<div class=\"form-group\">
                    <label for=\"title\">Friendly name</label>
                    <input type=\"text\" class=\"form-control\" id=\"friendly\" name=\"friendly\" placeholder=\"Friendly name\" value=\"\">
                  </div>";
    $script .= "<div class=\"form-group\">
                    <label for=\"email\">Email</label>
                    <input type=\"text\" class=\"form-control\" id=\"email\" name=\"email\" placeholder=\"Email\" value=\"\">
                  </div>";
    $script .= "<button type=\"button\" class=\"btn btn-primary\" id=\"btn_add\">ADD</button>
                <button type=\"button\" class=\"btn btn-default\" id=\"btn_cancel\">CANCEL</button>";
    $script .= "</form>";
    $arr_result["error"] = false;
    $arr_result["result"] = $script;
}
if ($arr_result["error"] === true) {
    $arr_result["error_descr"] = "Could not find panel parameter";
}
//GOTO DESTINATION :)
fireOutput:
die(json_encode($arr_result));


