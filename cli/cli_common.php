<?php

//TS_BOOT is the timestamp of the process, when it has been started.
$ts_boot = microtime(true);
error_reporting(E_ALL);
session_start();
set_time_limit(600);
//CONFIG FILE WITH CONFIGURATION SPECIFIC FOR THIS ENVIRONMENT
//THIS FILE IS NOT USUALLY UPDATED WITH THE PROJECT AND COULD NEED MANUAL UPDATE
require_once '../../env_config.php';
require_once '../../env_config_' . _MACHINE_ID_ . '.php';
echo "MACHINE_ID: " . _MACHINE_ID_ . "\n\n";
//Autoload classes
function __autoload($class) {
    $file_path = "../classes/$class.class.php";
    if (file_exists($file_path)) {
        require_once "$file_path";
    } else {
        die ("Unable to load class $class ($file_path)");
    }
}

echo "Try to connect to DB\n";
//CONNECTION TO THE DB.....
$mydbh = db_connect::connect($db["ENGINE"]);
if ($mydbh == false) {
    prnt("FAILED TO CONNECT TO DB!!!!!\n");
    prnt("THAT'S AN IMPORTANT ERROR!!!!!\n");
    mail($_SESSION["config"]["_APP_DEFAULT_EMAIL_"], 'Unable to connect to db', "$process_name  \nUnable to connect to db\n" . date("d/m/Y H:i:s"), "From: " . $_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"]);
    exit();
}
echo "DB connection OK\n";
//LOAD CONFIG FROM DB
//LOAD CONFIGURATION FROM DB
if (!isset($_SESSION["config"]["loaded"])) {
    $config_manager = new config($mydbh);
    $ret = $config_manager->loadConfig(_MACHINE_ID_);
    if ($ret !== true) {
        die ("Error while loading configuration...<br>" . $config_manager->last_error);
    }
} else {
    echo "session already loaded\n";
}
echo "CONFIG LOADED\n";
//SET PHP TIMEZONE
require_once $_SESSION["config"]["_ABS_DOC_ROOT_"] . 'include_files/set_timezone.inc.php';
require_once $_SESSION["config"]["_ABS_DOC_ROOT_"] . "include_files/generic_functions.inc.php";
require_once $_SESSION["config"]["_ABS_CLI_ROOT_"] . 'log_function.inc.php';
require_once $_SESSION["config"]["_ABS_CLI_ROOT_"] . 'check_proc_enabled.inc.php';
//Detect if we are on a web request or command line (cli)
$cli = detect_cli();

