<?php



if ($_POST) {
    $user = new user();
    $user->setUserValidationPOST();
    $r = $user->activateUser();
    //var_dump($r);
    //var_dump($user);
    if ($r == true) {
        echo json_encode(["error" => false]);
    } else {
        echo json_encode(["error" => true, "error_descr" => $user->getValidationErrors()]);
    }
} // END IF