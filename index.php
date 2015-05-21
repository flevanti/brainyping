<?php

$boot_time = microtime(true);
session_start();


//CONFIG FILE WITH CONFIGURATION SPECIFIC FOR THIS ENVIRONMENT
//THIS FILE IS NOT USUALLY UPDATED WITH THE PROJECT AND COULD NEED MANUAL UPDATE
require_once '../env_config.php';
require_once '../env_config_' . _MACHINE_ID_ . '.php';


//Config file with common configuration settings
//Later we will load specific configuration for DEV / PRD environment.
require_once 'include_files/set_timezone.inc.php';

//Autoload classes
function __autoload ($class) {
    $file_path = _CLASSES_PATH_ . "$class.class.php";
    if (file_exists($file_path)) {
        require_once "$file_path";
     } else {
        die ("Unable to load class $class ($file_path)");
    }
}

//Initialize user session if needed
user::sessionInitialize();


//PASSWORD LIB (PHP VER < 5.6 DO NOT HAVE password hash functions... this cover the gap)
require_once   _INCLUDE_FILES_PATH_ . "password_lib.inc.php";


//GENERIC FUNCTIONS
require_once    _INCLUDE_FILES_PATH_ . "generic_functions.inc.php";


$mydbh_web = db_connect::connect($db["WEB"]);
if ($mydbh_web === false) {
    die("Unable to connect to the database");
}

$too_many_requests = new check_too_many_requests($mydbh_web);
$too_many_requests->check(true);


//URI object to manage URL and retrieve parameters / generate URL
$uriobj = new URI_manager();

//Parse URI
$uriobj->parseURI();

$page_renderer = new page_renderer(); //Create page renderer option
$page_renderer->pageTitle =  _HTML_TITLE_; //default page title
$page_renderer->scriptsFromLocal = _EXT_SCRIPTS_FROM_LOCAL_; //load scripts from application or remote CDN source
//SEND HEADER WITH CHARSET - USED ALSO BY PHP
$page_renderer->sendPageHeader();

        //DEFAULT DB CONNECTION TO ENGINE DISABLED
        $conn_engine_db = false;

        $file_to_include = array();
        $file_to_include[] =  "navbar_fixed.inc.php";

        switch ($uriobj->getParam(0)) {
            case "":
                $file_to_include[] =  "homepage_form_ping.inc.php";
                $file_to_include[] =  "homepage.inc.php";
                $file_to_include[] =  "host_subscription_modal.inc.php";
                break;
            case "changelog":
                $file_to_include[] =  "changelog.inc.php";
                break;
            case "ping":
                //$file_to_include[] =  "ping_user_request.inc.php";
               // require_once    _INCLUDE_FILES_PATH_ . "homepage.inc.php";
                break;
            case "signin":
                $file_to_include[] = "signin.inc.php";
                break;
            case "signup":
                $file_to_include[] =  "signup.inc.php";
                break;
            case "useractivation":
                $file_to_include[] =  "useractivation.inc.php";
                $conn_engine_db = true;
                break;
            case "aggregate":
                $file_to_include[] = "aggregate.inc.php";
                $conn_engine_db = true;
                break;
            case "aggregatedashboard":
                $file_to_include[] = "aggregate_dashboard.inc.php";
                $conn_engine_db = true;
                break;
            case "monitored":
                $file_to_include[] = "monitored.inc.php";
                //$conn_engine_db = true;
                break;
            case "generatechecksschedule":
                $file_to_include[] = "generatechecksschedule.inc.php";
                $conn_engine_db = true;
                break;
            case "edit":
                $file_to_include[] = "edithost.inc.php";
                $conn_engine_db = true;
                break;
            case "timezone":
                $file_to_include[] = "timezone.inc.php";
                $conn_engine_db = true;
                break;
            case "bgmonitor":
                $file_to_include[] = "bgmonitor.inc.php";
                break;
            case "emailqueue":
                $file_to_include[] = "emailqueue.inc.php";
                $conn_engine_db = true;
                break;
            case "mycontacts":
                $file_to_include[] = "mycontacts.inc.php";
                $conn_engine_db = true;
                break;
            case "contacts":
                $file_to_include[] = "contacts.inc.php";
                break;
            case "stats":
                $file_to_include[] = "stats.inc.php";
                //$conn_engine_db = true;
                break;
            case "infoaboutphp":
                $file_to_include[] = "infoaboutphp.inc.php";
                break;
            case "infovisits":
                $file_to_include[] = "infovisits.inc.php";
                break;
            case "info":
                $file_to_include[] = "info.inc.php";
                $file_to_include[] =  "host_subscription_modal.inc.php";
                break;
            case "showlogs":
                $file_to_include[] = "show_logs.inc.php";
                break;
            case "contactactivation":
                $file_to_include[] = "contactactivation.inc.php";
                $conn_engine_db = true;
                break;
            case "temp":
                $file_to_include[] = "temp.inc.php";
                $conn_engine_db = true;
                break;
            case "confirmsubscription":
                $file_to_include[] = "confirm_subscription.inc.php";
                $conn_engine_db = true;
                break;
            case "cancelsubscription":
                $file_to_include[] = "cancel_subscription.inc.php";
                $conn_engine_db = true;
                break;
            case "fedsync":
                $file_to_include[] = "fedsync.inc.php";
                $conn_engine_db = true;
                break;
            case "myprofile":
                $file_to_include[] = "myprofile.inc.php";
                $conn_engine_db = true;
                break;
        } //END SWITCH



        //CONNECT TO ENGINE DB IF NEEDED
        if ($conn_engine_db === true) { //ENGINE CENTRAL DB
            $mydbh = db_connect::connect($db["ENGINE1_2"]);
            if ($mydbh === false) {
                die("Unable to connect to the database");
            }
            //Host manager object
            $host_manager = new host_manager($mydbh);
        } else {
            //Host manager object
            $host_manager = new host_manager($mydbh_web);
        }


        if ($uriobj->getParam(0) == "info") { //If requested page is host information
            //Try to retrieve host information to create ad-hoc web page title
            $host_temp_info = $host_manager->getHostByToken($uriobj->getParam(1));
            if ($host_temp_info != false) {
                $page_renderer->pageTitle = $host_temp_info["title"] . " Server Status";
                $page_renderer->metaDescription = $host_temp_info["title"] .
                                                        " Server Status. Last check performed on " .
                                                        ts_to_date($host_temp_info["last_check_ts"]) .
                                                        ", the server was " . ($host_temp_info["check_result"]=="OK"?"UP":"DOWN") . ".";
            }

        }


        //Try to render the page calling methods to build it....
        echo $page_renderer->htmlHtmlInit();
        echo $page_renderer->htmlHead();
        echo $page_renderer->htmlBodyInit();
        echo $page_renderer->bodyContainer();

        //JS variable for ajax calls
        echo "
              <script>
                  var ajax_calls_home = '" .  _AJAX_CALLS_INDEX_ ."';
              </script>
        ";

        //INCLUDE REQUESTED FILES
        foreach ($file_to_include as $value) {
            if (file_exists( _INCLUDE_FILES_PATH_ . $value)) {
                require_once  _INCLUDE_FILES_PATH_ . $value;
            } else {
                require_once  _INCLUDE_FILES_PATH_ . "page_not_found.inc.php";
                break;
            }
        } //END FOREACH

echo $page_renderer->bodyContainerEnd();

require_once    _INCLUDE_FILES_PATH_ . "footer.inc.php";

echo $page_renderer->htmlBodyEnd();
echo $page_renderer->htmlHtmlEnd();
