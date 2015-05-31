<?php
/**
 * FILE DEPENDENCIES:
 *  classes/config.class.php
 *  classes/monitor_interface.class.php
 *  classes/http_header.class.php
 *  classes/multi_implode.class.php
 */
function __autoload($class) { //Autoload classes
    $file_path = "classes/$class.class.php";
    if (file_exists($file_path)) {
        require_once "$file_path";
    } else {
        die ("Unable to load class $class ($file_path)");
    }
}

header('Content-type: text/html; charset=utf-8');
error_reporting(E_ALL);
/**
 * $allowed_ip array   each element is an IP address. If 0.0.0.0 is present all IPs are allowed
 */
$allowed_ip = array();
$allowed_ip[] = "0.0.0.0"; //remove if you want to perform IPs checks
$config_manager = new config();
$config_manager->loadConfigPath();
$r = new http_header();
if (isset($_GET["url"])) {
    $url = $_GET["url"];
} else {
    $result["result"] = false;
    $result["last_error"] = "URL not found";
    $result["last_error_code"] = "URLNOTFOUND";
    die(json_encode($result));
}
if (isset($_GET["port"])) {
    $port = (int)$_GET["port"];
} else {
    $result["result"] = false;
    $result["last_error"] = "PORT not found";
    $result["last_error_code"] = "PORTNOTFOUND";
    die(json_encode($result));
}
if (isset($_SERVER["REMOTE_ADDR"])) { //if remote server IP is present
    $server_ip = $_SERVER["REMOTE_ADDR"]; //use it...
} else {
    $server_ip = "xxx.xxx.xxx.xxx"; //otherwise get a fake invalid IP
}
if (array_search($server_ip, $allowed_ip) === false and array_search("0.0.0.0", $allowed_ip) === false) { //check if the remote server IP is allowed or we have the allow all IP in the array
    $result["result"] = false;
    $result["last_error"] = "IP not allowed here";
    $result["last_error_code"] = "IPNOTALLOWED";
    die(json_encode($result));
}
set_time_limit(600);
$result = array();
$r->this_is_a_remote_call = true;
$r->setCookiesFolder($_SESSION["config"]["_ABS_COOKIES_FOLDER_"]);
$r->getHeaders($url, $port);
if ($r->getHeaderCode() === false) {
    $result["result"] = false;
    $result["last_error"] = $r->last_error;
    $result["last_error_code"] = $r->last_error_code;
    die(json_encode($result));
} else {
    $temp = $r->getHeadersArray();
    $result["result"] = true;
    $temp["last_error"] = $r->last_error;
    $temp["last_error_code"] = $r->last_error_code;
    //encode array elements to UTF8 to be sure json_encode won't fail...
    foreach ($temp as $k => &$v) { //note $v as a reference...
        $v = is_string($v) ? utf8_encode($v) : $v; //if $v is a string encode to utf8
    }
    $jsonstring = json_encode($temp);
    echo $jsonstring;
    exit;
}








