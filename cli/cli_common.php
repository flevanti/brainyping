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
//SET PHP TIMEZONE
require_once _ABS_DOC_ROOT_ . 'include_files/set_timezone.inc.php';


//Autoload classes
function __autoload ($class) {
    $file_path =  _ABS_DOC_ROOT_ . "classes/$class.class.php";
    if (file_exists($file_path)) {
        require_once "$file_path";
    } else {
        die ("Unable to load class $class ($file_path)");
    }
}


require_once  _ABS_DOC_ROOT_ . "include_files/generic_functions.inc.php";
require_once _ABS_CLI_ROOT_ . 'log_function.inc.php';
require_once  _ABS_CLI_ROOT_ . 'check_proc_enabled.inc.php';

//Detect if we are on a web request or command line (cli)
$cli = detect_cli();


prnt ("Try to connect to DB\n");
//CONNECTION TO THE DB.....

$mydbh = db_connect::connect($db["ENGINE1_2"]);


if ($mydbh == false) {
    prnt ("FAILED TO CONNECT TO DB!!!!!\n");
    prnt ("THAT'S AN IMPORTANT ERROR!!!!!\n");
    error_log("$process_name  \nUnable to connect to db\n" . date("d/m/Y H:i:s"),3,_ABS_LOG_FOLDER_ . $process_name . "_" . date("YmdHis") . "_error.txt");
    exit();
}

prnt ("DB connection OK\n");
