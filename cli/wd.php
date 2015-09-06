<?php

//TS_BOOT is the timestamp of the process, when it has been started.
$ts_boot = microtime(TRUE);
//TEMP FILE NAME
//THIS IS USED ONLY IN CASE WE NEED TO LOG IT BEFORE GET THE TRUE PROCESS NAME
$process_name = "WD_TEMP";
require_once 'cli_common.php';
//PROCESS NAME
$process_name = $_SESSION["config"]["_WD_PROCESS_NAME_"];
echo "PROCESS NAME DETECTED: " . $process_name . "\n";
if (check_proc_enabled($process_name) === FALSE) {
  die("PROCESS DISABLE. CHECK CONFIG");
}
//INITIAL LOOP-COUNTER VALUE
$loop_run = 0;
//TRANSACTION MAX ATTEMPTS
$transaction_max_repeat_on_fails = 10;
//Transaction current attempt
$transaction_try = 0;
//Transaction went fine flag
$transaction_ok = FALSE;
if ($cli === FALSE and !isset($_GET["ppp"])) {
  die('ONLI CLI SUPPORTED, SORRY');
}
//RUN PROCESS EVERY x SECONDS (IF NOT SCHEDULED ON CRON)
//LOG ENABLED
$log_enabled = FALSE;
//CMD PARAMETER
$cmd_param["ver"] = 0; //VERBOSE: show commands used to run ping processes
$cmd_param["sil"] = 0; //SILENT: php-win.exe used instead of php.exe. php-win has no window interface,
//WD is executed by the cron, at the end of the run it simply ends.
$cmd_param["sim"] = 0; //SIM: do not execute any other script, script execution is skipped
$cmd_param["limit1"] = 0; //Limit query records to 1
print "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
//PARAMETERS CHECK (IF ON CLI)
if ($cli === TRUE && $argc > 0) {
  print "Checking parameters...\n";
  //We start to check parameters from key element 1 not 0
  //because element 0 is the script filename....
  //We compare parameters with $cmd_param array keys
  //If key exists we activate the parameter
  for ($i = 1; $i < $argc; $i++) {
    if (array_key_exists($argv[$i], $cmd_param)) {
      $cmd_param[$argv[$i]] = 1;
    }
    else {
      print $argv[$i] . " : parameter unknown\n";
      exit();
    }
  } //END FOR - LOOP IN CMD PARAMETERS
} //END IF - $ARGC > 0
///////////////////////////////////////////////////
//WE NOW HAVE A DB CONNECTION!! YEAH!!
//SO WE COULD START TO LOG THINGS SERIUOUSLY!
//LOG START!
log_it("Watchdog process started", "BOOT");
//SHOW PARAMETER TO USER
//WE ALSO CREATE A STRING VARIABLE WITH THEM
//SO WE COULD LOG THEM...
$cmd_param_str = array_to_string($cmd_param);
print "Process variables:\n";
print $cmd_param_str;
print "\n\n\n";
//LOG CMD PARAMETERS
log_it("CMD PARAMETERS:\n " . $cmd_param_str);
$loop_run++;
//Create an unique reservation_code in order to query host only once even if watchdog is run multiple times
$reservation_code = session_id() . "_" . $loop_run;
$reservation_ts = time();
//CURRENT TIMESTAMP
$now = time();
$now_str = date("d/m/Y H:i:s");
log_it("CURRENT TIMESTAMP: $now ($now_str)");
prnt("CURRENT TIMESTAMP: $now ($now_str)\n");
log_it("Here we go! Starting loop #$loop_run \n\n");
//IF LIMIT1 parameter is present limit query result to 1
if ($cmd_param["limit1"] == 1) {
  $query_limit = 1;
} else {
  $query_limit = $_SESSION["config"]["_WD_ROWS_LIMIT_"];
}
//WE USE DB TIMESTAMP INSTEAD OF NOW TO HAVE A UNIQUE TIME ACROSS ENGINES.....
$sql = "update hosts set check_reservation_code = '$reservation_code',
                              check_reservation_ts = $reservation_ts,
                              check_running = 1,
                              check_started_ts = unix_timestamp(),
                              check_started_str = '$now_str'
                        WHERE  next_check_ts <= unix_timestamp()
                              AND (check_running = 0  or (hosts.check_running = 1
                                                              and check_reservation_ts <= (unix_timestamp() - 120)))
                        ORDER BY next_check_ts
                               LIMIT " . $query_limit . ";";
//IN ORDER TO PERFORM THIS OPERATION ON A SINGLE TABLE
$mydbh->query("set autocommit=0;");
do {
  $mydbh->query("lock tables hosts write;");
  $transaction_try++;
  prnt("Try transaction #$transaction_try\n");
  //If we are on the 2nd transaction (or more) we wait a little bit... be good!
  if ($transaction_try > 1) {
    log_it("Let's try again! - Try #" . $transaction_try, "CRITICAL");
    sleep(1);
  }
  prnt("Execute sql statement\n");
  $r = $mydbh->query($sql);
  if ($r === FALSE) {
    prnt("Execution failed\n");
    log_it("WatchDog FAILED to reserve hosts to be monitored....", "CRITICAL");
    log_it("Try #" . $transaction_try, "CRITICAL");
    log_it("ERROR: " . implode("\n", $mydbh->errorInfo()), "CRITICAL");
    //email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"],$_SESSION["config"]["_APP_DEFAULT_EMAIL_"],"WD FAILED","WD FAILED\n\n" . implode("\n",$stmt->errorInfo()));
    $mydbh->query("unlock tables;");
    //Next please.....
    continue;
  }
  prnt("Execution OK\n");
  //If we are here means the transaction went fine...
  //Activate the flag to exit the do while loop
  $transaction_ok = TRUE;
  $mydbh->query("commit;");
  $mydbh->query("unlock tables;");
  $mydbh->query("set autocommit=1;");
} while ($transaction_try < $transaction_max_repeat_on_fails and $transaction_ok === FALSE);
prnt("End of transaction try\n");
//We are out of the do/while loop.... let's understand if transaction has been committed or not...
//TRANSACTION FAILED
if ($transaction_ok === FALSE) {
  log_it("WD Host reservation FAILED after " . $transaction_try . " attempts. We give up!", "CRITICAL");
  prnt("Transaction failed after attempts\n");
  exit;
}
prnt("Transaction success\n");
////TRANSACTION OK! We log it only if we made more than 1 attempt
if ($transaction_ok === TRUE and $transaction_try > 1) {
  log_it("WD Host reservation OK after " . $transaction_try . " attempts. Let's go!", "CRITICAL");
}
//GET HOSTS LIST TO BE CHECKED
print " \n";
print "Retrieving 'hosts' that need to be checked...";
log_it("Query HOSTS to be checked");
try {
  $sql = "select id, host,minutes,next_check_ts,log_enabled, check_type
              from hosts where check_reservation_code = '$reservation_code'
              ORDER BY next_check_ts;";
  $hosts = $mydbh->query($sql);
  if ($hosts === FALSE) {
    log_it("WatchDog FAILED to read reserved hosts....", "CRITICAL");
    log_it(implode("\n", $mydbh->errorInfo()), "CRITICAL");
    email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"], $_SESSION["config"]["_APP_DEFAULT_EMAIL_"], "WD FAILED", "WD FAILED\n\n" . implode("\n", $stmt->errorInfo()));
    exit;
  }
  $hosts = $hosts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  print "EXCEPTION!!! Error during query!\n\n";
  print "Please check log file for details\n\n";
  print "Sending an email ....";
  //EXCEPTION ON DB SO IT'S NOT POSSIBLE TO WRITE ON IT THE LOG...
  if (mail($_SESSION["config"]["_APP_DEFAULT_EMAIL_"], "WatchDog Exception", "Finding hosts:\r\n$e", "From:" . $_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"] . "\r\n")) {
    print "OK\n";
  }
  else {
    print "FAIL!!\n\n";
  };
  print "writing log on disk....";
  $filename = "WD_EXCEPTION_" . date("Ymd_His") . ".txt";
  //Check if log folder exists
  if (file_exists("LOG") == FALSE) {
    mkdir("LOG");
  }
  $myfile = fopen("LOG\\$filename", "w") or die("Unable to open file!");
  $txt = "$e";
  fwrite($myfile, $txt);
  fclose($myfile);
  print "OK";
  exit();
}
$num_hosts = count($hosts);
print "$num_hosts found\n";
log_it("$num_hosts waiting to be checked!");
print "Execution time: " . ts_to_date() . "\n";
log_it("Hosts check begins - Looping the hosts list");
//LOOP FOR THE HOST!!
for ($i = 0; $i < $num_hosts; $i++) {

  if ($_SESSION["config"]["_UNIX_OR_WINDOWS_"] == "WINDWOS") { //WINDOWS ENVIRONMENT
    //WE CREATE THE COMMAND STRING TO BE EXECUTED
    $cmd_string = "start  ";
    //ADD WINDOWS TITLE
    $cmd_string .= "\"" . $hosts[$i]['host'] . "\" ";
    //CHECK FOR SILENT/BACKGROUND MODE
    //IF SILENT REQUESTED WE USE ANOTHER PHP INTERPRETER
    //THAT'S THE ONLY DIFFERENCE....
    //I STUMBLED ON IT ONE NIGHT.... :-/
    if ($cmd_param["sil"] == 1) {
      //ADD SILENT PHP INTERPRETER
      $cmd_string .= $_SESSION["config"]["_ABS_PATH_PHP_EXEC_"] . "php-win.exe ";
    }
    else {
      //ADD NORMAL PHP INTERPRETER
      $cmd_string .= $_SESSION["config"]["_ABS_PATH_PHP_EXEC_"] . "php.exe ";
    }
    //ADD PHP SCRIPT TO CALL
    $cmd_string .= "-f \"" . $_SESSION["config"]["_ABS_CLI_ROOT_"] . "ping.php\" ";
    //ADD HOST ID
    $cmd_string .= $hosts[$i]['id'] . " ";
    //ADD TIMESTAMP - THIS IS USEFUL FOR MONITOR PROCESS EASILY
    $cmd_string .= "*TS*" . time() . "*TS* ";
    log_it("COMMAND LINE READY TO BE FIRED:\n$cmd_string");
    //pclose/popen used to create standalone process
    //this means that the main process (this one) do not need to wait for them....
    //In other words this is an asynchronous call or a Fire and Forget Call
    if ($cmd_param["sim"] == 0) {
      pclose(popen($cmd_string, "r"));
    }
    else {
      print "SIMULATION - script execution skipped\n";
    }
  }
  else { //*NIX ENVIRONMENT
    $cmd_string = $_SESSION["config"]["_ABS_PATH_PHP_EXEC_"] . "php -f \"" .
      $_SESSION["config"]["_ABS_CLI_ROOT_"] . "ping.php\"  " .
      $hosts[$i]['id'] . "     *TS*" . time() . "*TS*   > /dev/null &";
    log_it("COMMAND LINE READY TO BE FIRED:\n$cmd_string");
    if ($cmd_param["sim"] == 0) {
      exec($cmd_string);
    }
    else {
      print "SIMULATION - script execution skipped\n";
    }
  }

  if ($cmd_param["ver"] == 1) {
    print $cmd_string . "\n";
  }
  else {
    print ".";
  }


  //Be a good boy... lets leave somo time/cpu/rest to resources/others...
  //usleeps use microseconds (1/1million) so 200,000 is 0.2seconds
  usleep(200000);
} //END FOR (LOOP HOSTS...)
log_it("Hosts recordset - loop completed");
print "\n\n";
//RESET PING RUNNING IF MORE THAT XX SECONDS
//REDUNDANT
//print "RESET PING RUNNING FLAG/CHECK RESERVATION IF STUCK FOR MORE THAT XX SECS.....\n";
//log_it("RESET PING RUNNING FLAG IF STUCK FOR MORE THAT XX SECS.....");
//$sql = "update hosts set check_running=0, check_reservation_ts = 0, check_reservation_code = ''
//            where check_running=1
//                and check_started_ts < " . (time()-120) . ";";
// $mydbh->query($sql); //DEPRECATED.... INCLUDED IN THE INITIAL SELECT HOSTS...
//LOG LAST TIME WATCHDOG WAS EXECUTED.....
$sql = "update bg_proc_last_exec set    date_ts = " . time() . ",
                                            date_str = '" . ts_to_date() . "',
                                            date_ts_db = unix_timestamp(),
                                            execution_time = " . round((microtime(TRUE) - $ts_boot), 3) . ",
                                            hosts = $num_hosts,
                                            alert_sent = 0,
                                            machine_id = '" . _MACHINE_ID_ . "',
                                            db_id = @db_id
                    where proc_name = '$process_name' limit 1;";
$t = $mydbh->query($sql);
///////////////////////////////////////////////////////////////////////////////
//LOG PROCESS EXECUTION
//IF PROCESS EXECUTION TIME IS LESS THAN 1 SEC WE ADD 1 SEC (FOR CHARTING PURPOSE)
$ts_shutdown = time();
if (intval($ts_boot) == $ts_shutdown) {
  $ts_shutdown++;
}
log_bgproc_execution($mydbh, $process_name, intval($ts_boot), $ts_shutdown);
//////////////////////////////////////////////////////////////////////////////
if ($cli === FALSE) {
  //LOOKS LIKE WE RUN THE WATCHDOG FROM A WEBPAGE..... (you nasty boy....)
  print "Looks like WatchDog is running in a webpage...<br>";
  print "So the execution is ended....Bye bye...";
  log_it("WatchDog is running in a webpage....");
  exit();
}
print "See you very soon! Bye";
log_it("See you very soon, bye bye!");
exit();

