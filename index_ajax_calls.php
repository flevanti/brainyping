<?php

session_start();
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
function die_json($txt) {
    die(json_encode(["error" => true, "error_descr" => $txt]));
}

//CONFIG FILE WITH CONFIGURATION SPECIFIC FOR THIS ENVIRONMENT
//THIS FILE IS NOT USUALLY UPDATED WITH THE PROJECT AND COULD NEED MANUAL UPDATE
require_once '../env_config.php';
$mydbh_web = db_connect::connect($db["WEB"]);
if ($mydbh_web === false) {
    die_json("Unable to connect to the engine database");
}
$config_manager = new config($mydbh_web);
$ret = $config_manager->loadConfig(_MACHINE_ID_);
if ($ret === false) {
    die('Unable to load configuration');
}
//SET PHP TIMEZONE
require_once 'include_files/set_timezone.inc.php';
//PASSWORD LIB (PHP VER < 5.6 DO NOT HAVE password hash functions... this cover the gap)
require_once "include_files/password_lib.inc.php";
//Autoload classes
function __autoload($class) {
    $file_path = "classes/$class.class.php";
    if (file_exists($file_path)) {
        require_once "$file_path";
    } else {
        die_json("Unable to load class $class ($file_path)");
        exit;
    }
}

//Check too many connections
$too_many_requests = new check_too_many_requests($mydbh_web);
$too_many_requests->check(true);
//GENERIC FUNCTIONS
require_once "include_files/generic_functions.inc.php";
//URI object to manage URL and retrieve parameters / generate URL
$uriobj = new URI_manager();
//Parse URI
$uriobj->parseURI();
// CHECK IF DB CONNECTION TO ENGINE IS NEEDED......
$db_connection_pages = ["contacts"
    , "edithost"
    , "logout"
    , "monitored_bar___________"
    , "monitored_bulk_action"
    , "monitored_list_________"
    , "mycontacts_addnew"
    , "mycontacts_list"
    , "mycontacts_modify"
    , "mycontacts_panel"
    , "signin"
    , "signup"
    , "subscription_modal_form"
    , "subscription_save"
    , "user_activation"
    , "changepwd"
    , "edithostattrib"];
if (array_search($uriobj->getParam(1), $db_connection_pages) !== false) {
    //DB CONNECTION
    $mydbh = db_connect::connect($db["ENGINE"]);
    if ($mydbh === false) {
        die_json("Unable to connect to the engine database");
    }
    //Host manager object
    $host_manager = new host_manager($mydbh);
} else {
    //Host manager object
    $host_manager = new host_manager($mydbh_web);
}
//create filename
//FIRST ELEMENT (INDEX KEY = 0) OF THE URI PARAM ARRAY IS THE SCRIPT FILE SO WE DO NOT CONSIDER IT
//THE SECOND PARAMETER (INDEX KEY = 1) IS THE INITIAL PART OF THE FILE NAME REQUESTED TO HANDLE AJAX CALL...
$filename = "ajax_calls/" . $uriobj->getParam(1) . ".ajax.php";
if (file_exists($filename)) {
    require_once $filename;
} else {
    die_json("Unable to locate ajax calls handler file.");
}

?>