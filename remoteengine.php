<?php
error_reporting(E_ALL);
//phpinfo();
require_once '../env_config.php';
require_once '../env_config_' . _MACHINE_ID_ . '.php';
//Autoload classes
function __autoload($class) {
    $file_path = "classes/$class.class.php";
    if (file_exists($file_path)) {
        require_once "$file_path";
    } else {
        die ("Unable to load class $class ($file_path)");
    }
}

$r = new http_header();
if (isset($_GET["ppp"])) {
    $ppp = true;
} else {
    $ppp = false;
}
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
//IF NO pass par toi (PPP) present perform some checks....
if ($ppp === false) {
    $allowed_ip[] = "127.0.0.1";
    $allowed_ip[] = "213.136.94.14";
    $allowed_ip[] = "23.92.61.110";
    if (isset($_SERVER["REMOTE_ADDR"])) {
        $server_ip = $_SERVER["REMOTE_ADDR"];
    } else {
        $server_ip = "xxx.xxx.xxx.xxx";
    }
    if (array_search($server_ip, $allowed_ip) === false) {
        $result["result"] = false;
        $result["last_error"] = "IP not allowed here";
        $result["last_error_code"] = "IPNOTALLOWED";
        die(json_encode($result));
    }
    if (isset($_GET["code"])) {
        $code = $_GET["code"];
    } else {
        $result["result"] = false;
        $result["last_error"] = "Code not found";
        $result["last_error_code"] = "CODENOTFOUND";
        die(json_encode($result));
    }
    $code2 = $r->generateCheckCode($url);
    if ($code != $code2) {
        $result["result"] = false;
        $result["last_error"] = "Code not valid";
        $result["last_error_code"] = "CODENOTVALID";
        die(json_encode($result));
    }
}
set_time_limit(600);
$result = array();
$r->this_is_a_remote_call = true;
$r->getHeaders($url, $port);
if ($r->getHeaderCode() === false) {
    $result["result"] = false;
    $result["last_error"] = $r->last_error;
    $result["last_error_code"] = $r->last_error_code;
    //var_dump(json_decode(json_encode($result),1));
    die(json_encode($result));
} else {
    //var_dump(json_decode(json_encode($r->getHeadersArray()),1));
    $temp = $r->getHeadersArray();
    $result["result"] = true;
    $temp["last_error"] = $r->last_error;
    $temp["last_error_code"] = $r->last_error_code;
    die(json_encode($temp));
}








