<?php
if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}
$arr_result["error"] = true;
$arr_result["error_descr"] = "";
$arr_result["result"] = "";
$arr_data = array();
//CONTACT TYPE
if (!isset($_POST["contact_type"])) {
    $arr_result["error_descr"] = "Contact type not found";
    goto fireOutput;
}
$arr_data["contact_type_id"] = $_POST["contact_type"];
//FRIENDLY NAME CHECK
$allowed_chars = [" ", "-", "_", ".", "@"];
if (!isset($_POST["friendly"])
    or ctype_alnum(str_replace($allowed_chars, "", $_POST["friendly"])) === false
) {
    $arr_result["error_descr"] = "Friendly name invalid char found or empty";
    goto fireOutput;
}
$arr_data["friendly_name"] = trim($_POST["friendly"]);
//EMAIL CHECK
if (!isset($_POST["email"]) or filter_var($_POST["email"], FILTER_VALIDATE_EMAIL) === false) {
    $arr_result["error_descr"] = "Email not valid";
    goto fireOutput;
}
$arr_data["contact"] = strtolower(trim($_POST["email"]));
//if we are here.... datas is ok...
//perform some more checks:
$user = new user();
//Check if user already has this contact in his profile....
if ($user->checkMyContactsIsDouble($arr_data["contact"]) === true) {
    $arr_result["error_descr"] = "Contact already registered";
    goto fireOutput;
}
if ($user->addNewMyContacts($arr_data) === false) {
    $arr_result["error_descr"] = $user->last_error;
    goto fireOutput;
}
$arr_result["error"] = false;
fireOutput:
die(json_encode($arr_result));