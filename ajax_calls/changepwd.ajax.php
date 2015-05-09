<?php

if (user::isLogged()===false ) {
    echo "Looks like you're not authorized, sorry!";
    return;
}

//Default values
$result["result"] = false;
$result["error_descr"] = "Unknown error (default)";


$userobj = new user($mydbh);

$ret = $userobj->changepwd($_POST["currentpwd"],
                                $_POST["newpwd"],
                                $_POST["newpwd2"],
                                $_POST["token"],
                                $userobj->getID());


if ($ret === false) {
    $result["error_descr"] = $userobj->last_error;
    die(json_encode($result));
}

$result["result"] = true;
die(json_encode($result));