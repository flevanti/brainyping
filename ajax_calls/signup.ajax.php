<?php

if ($_POST) {
    $new_user = new user();
    $new_user->setNewUserPOST();
    $r = $new_user->saveNewUser();
    if ($r == true) {
        $arr_response["error"] = false;
    } else {
        $arr_response["error"] = true;
        $arr_response["error_descr"] = $new_user->getValidationErrors();
    }
    echo json_encode($arr_response);
}