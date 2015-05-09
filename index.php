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



//SEND HEADER WITH CHARSET - USED ALSO BY PHP


header('Content-Type: text/html; charset=utf-8');


?>

<!DOCTYPE html>
<html lang="en">
  <head>

    <!--FAVICON-->
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!--<link rel="icon" href="favicon.ico">-->

    <title><?php echo _HTML_TITLE_; ?></title>

      <?php
      if (_EXT_SCRIPTS_FROM_LOCAL_ === true) { //DEV
          ?>
          <script src="/js/jquery-1.11.1.min.js"></script> <!--JQUERY 1.11.1-->
          <link href="/css/normalize.css" rel="stylesheet"> <!--NORMALIZE CSS-->
          <link href="/css/font-awesome.min.css" rel="stylesheet"> <!--FONT AWESOME-->
          <link href="/css/bootstrap.min.css" rel="stylesheet"> <!--BOOTSTRAP 3.2.0-->
          <script src="/js/bootstrap.min.js"></script>

      <?php
      } else { //PRD
          ?>
          <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script><!--JQUERY 1.11.1-->
          <link  href="https://normalize-css.googlecode.com/svn/trunk/normalize.css" rel="stylesheet"/> <!--NORMALIZE CSS-->
          <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet"> <!--FONT AWESOME-->
          <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet"> <!--BOOTSTRAP 3.2.0-->
          <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
      <?php
      }


      ?>

      <script src="/js/jquery-ui.min.js"></script>
      <link href="/css/jquery-ui.min.css" rel="stylesheet">
      <link href="/css/jquery-ui.structure.min.css" rel="stylesheet">
      <link href="/css/jquery-ui.theme.min.css" rel="stylesheet">


      <!--PEITY CHARTS-->
      <script src="/js/jquery.peity.js"></script>

        <!--tablesorter JQUERY plugin-->
      <script src="/js/jquery.tablesorter.min.js"></script>
      <!--sticky footer and navbar-->
      <link href="/css/sticky-footer-navbar.css" rel="stylesheet" >
      <!-- Custom styles for this template -->
      <link href="/css/general.css" rel="stylesheet">
      <!--JSCHART.ORG -->
      <script src="/js/Chart.min.js"></script>
      <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!--[if lt IE 9]>
      <script src="/js/html5shiv.min.js"></script>
      <script src="/js/respond.min.js"></script>
      <![endif]-->

        <!--toastr CSS alert box-->
      <link href="/css/toastr.min.css" rel="stylesheet">


  </head>

  <body>

    <script>
        var ajax_calls_home = '<?php echo _AJAX_CALLS_INDEX_; ?>';
    </script>



    <!-- Begin page content -->
    <div class="container">

        <?php

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

        //INCLUDE REQUESTED FILES
        foreach ($file_to_include as $value) {
            if (file_exists( _INCLUDE_FILES_PATH_ . $value)) {
                require_once  _INCLUDE_FILES_PATH_ . $value;
            } else {
                require_once  _INCLUDE_FILES_PATH_ . "page_not_found.inc.php";
                break;
            }
        } //END FOREACH


        ?>

    </div>






    <div class="footer">
      <div class="container">
          <?php
            require_once    _INCLUDE_FILES_PATH_ . "footer.inc.php";
          ?>
      </div>
    </div>



    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->

    <?php

    ?>

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="/js/ie10-viewport-bug-workaround.js"></script>
    <!-- toastr alertbox-->
    <script src="/js/toastr.min.js"></script>

  </body>
</html>
