<?php



if ($_POST) {

    $user = new user();

    $user->setLoginPOST();

    $r = $user->checkLoginCredentials();

    if ($r == true) {
        die (json_encode(["error"=>false]));

    } else {

        switch ($user->error_code) {
            case "CREDENTIALS":
                $arr_response["error"]=true;
                $arr_response["error_code"] = "CREDENTIALS";
                $arr_response["error_descr"] = $user->getValidationErrors();
                break;
            case "NOTENABLED":
                $arr_response["error"]=true;
                $arr_response["error_code"] = "NOTENABLED";
                $arr_response["error_descr"] = $user->getValidationErrors();
                break;
            case "EMAILNOTVERIFIED":
                $arr_response["error"]=true;
                $arr_response["error_code"] = "EMAILNOTVERIFIED";
                $arr_response["error_descr"] = $user->getValidationErrors();
                break;
            case "EMAILSYNTAX":
                $arr_response["error"]=true;
                $arr_response["error_code"] = "EMAILSYNTAX";
                $arr_response["error_descr"] = $user->getValidationErrors();
                break;
            default:
                $arr_response["error"]=true;
                $arr_response["error_code"] = "UNKNOWN";
                $arr_response["error_descr"] = "Unknown login error, sorry.";
        } //END SWITCH


        die (json_encode($arr_response));

    } //END IF ELSE
}// END POST