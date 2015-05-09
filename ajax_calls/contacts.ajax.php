<?php

//WE USE GOTO because _in this case_ is very easy to read, code is short and page is an ajax call

$arr_result["error"] = true;
$arr_result["error_descr"] = "Unknown error, default error message";


//CHECK NAME ////////////////////////////////////////
if (!isset($_POST["name"])) {  //NOT POSTED (very strange!!)
    $arr_result["error_descr"] = "Name not found";
    goto output_json;
}

$_POST["name"] = trim($_POST["name"]);  //name trimmed

if ($_POST["name"] == "") {  //name empty
    $arr_result["error_descr"] = "Name cannot be empty";
    goto output_json;
}

//Check if name contains chars not valid
$allowed_chars = [" ","_","-", "."];  //array of allowed chars in the name (in addition to alnum default chars)
if (ctype_alnum(str_replace($allowed_chars,"",$_POST["name"])) == false) {
    $arr_result["error_descr"] = "Name contains invalid chars";
    goto output_json;
}

//CHECK EMAIL //////////////////////////////////////
if (!isset($_POST["email"])) {  // NOT POSTED (very strange!!)
    $arr_result["error_descr"] = "Email not found";
    goto output_json;
}

$_POST["email"] = trim($_POST["email"]);  // trimmed

$check_email = new check_address($_POST["email"]);

if (!$check_email->checkEmailSyntax()) {
    $arr_result["error_descr"] = "Email is not valid";
    goto output_json;
}

//CHECK SUBJECT ///////////////////////////////////////////////////////////////
if (!isset($_POST["subject"])) {  // NOT POSTED (very strange!!)
    $arr_result["error_descr"] = "Subject not found";
    goto output_json;
}


$allowed_subject["general"] = "GENERAL INFORMATION";
$allowed_subject["suggestions"] = "SUGGESTION/FEEDBACK";
$allowed_subject["techsupport"] = "TECHNICAL SUPPORT";

if (!isset($allowed_subject[$_POST["subject"]])) {
    $arr_result["error_descr"] = "Subject not allowed";
    goto output_json;
}




//CHECK MESSAGE //////////////////////////////////////////
if (!isset($_POST["message"])) {  // NOT POSTED (very strange!!)
    $arr_result["error_descr"] = "Message not found";
    goto output_json;
}

$_POST["message"] = trim($_POST["message"]);

if ($_POST["message"] == "") {
    $arr_result["error_descr"] = "Message is empty";
    goto output_json;
}

if (strlen($_POST["message"]) > 1400) {
    $arr_result["error_descr"] = "Message is too long";
    goto output_json;
}




//FORM POST DATA ARE FINE.....

$_POST["message"] = "MESSAGE FROM " . $_POST["name"] . " - EMAIL " . $_POST["email"] . "\n\n" . $_POST["message"];



$result = email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,_APP_DEFAULT_EMAIL_CONTACTS_RECIPIENT_,$allowed_subject[$_POST["subject"]],$_POST["message"],"");

if ($result === true) {
    $arr_result["error"] = false;
} else {
    $arr_result["error_descr"] = "OOOPS! Email queue not available";
}




//OUTPUT
output_json:
die(json_encode($arr_result));