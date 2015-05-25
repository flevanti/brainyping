<?php
//TEMP PROCESS NAME
//USED ONLY IF WE NEED TO LOG IT BEFORE GETTING THE REAL ONE...
$process_name = "PING_TEMP";
require_once 'cli_common.php';
//PROCESS NAME
$process_name = $_SESSION["config"]["_PING_PROCESS_NAME_"];
//TS_BOOT is the timestamp of the process, when it has been started.
$ts_boot = microtime(true);
//LOG ENABLED
$log_enabled = false;
//SET THE CURRENT TIMESTAMP IN ORDER TO HAVE THE SAME "NOW" DURING THE PROCESS....
$time_now = time();
//Create host object
$host_manager_result = new host_manager_results();
$host_manager_result->setTimeNow($time_now);
$average = 0;
$best = 9999999;
$worst = 0;
$latest_results_to_keep_as_string = 100;
//RETRIEVE PARAMETERS FROM COMMAND LINE....
if ($cli == true) {
    //RETRIEVE HOST ID FROM CLI PARAMETERS
    //SECOND PARAMETER (ID KEY = 1) SHOULD BE THE ID
    //SO...
    if (isset($argv[1])) {
        $id_host = intval($argv[1]);
    } else {
        prnt("ID HOST NOT FOUND IN CLI PARAMETERS\n");
        exit;
    }
} else {
    //RETRIEVE HOST ID FROM URL PARAMETERS
    if (isset($_GET["id_host"])) {
        $id_host = intval($_GET["id_host"]);
    } else {
        prnt("ID HOST NOT FOUND IN URL PARAMETERS\n");
        exit;
    }
}
//CONNECTION OK!
//LET'S START TO LOG SOMETHING...
log_it("#$id_host - Ping process started");
//RETRIEVE HOST INFORMATION FROM DB
prnt("Query DB for host information...\n");
log_it("Query DB for host information");
$host = $host_manager_result->getHostInformation($id_host);
if ($host === false) {
    prnt("No row found for host $id_host");
    log_it("no row found - ID HOST " . $id_host, "");
    log_it("#$id_host - No Host information found for host", "CRITICAL");
    exit();
}
//HOST CHECK SCHEDULED NOT FOUND....
if (is_null($host["ts_check_scheduled"])) {
    prnt("No scheduled time returned for id host $id_host");
    log_it("#$id_host - No scheduled time returned", "CRITICAL");
    exit();
}
//HOST CHECK SCHEDULED ALREADY PROCESSED...
if ($host["ts_check_scheduled"] == $host["last_check_ts"]) {
    prnt("Check scheduled alredy processed for id host $id_host");
    log_it("Check scheduled alredy processed - ID HOST " . $id_host, "CRITICAL");
    exit();
}
prnt("OK\n");
//Check if the scheduled time is the same of the one retrieved from the scheduled checks..
//if not this could means that we skipped a check.... (engine fault or similar)....
if ($host["next_check_ts"] != $host["ts_check_scheduled"]) {
    prnt("ID HOST $id_host !!! Host cheduled time " . $host["next_check_ts"] . " is different from the one found on scheduled plan " . $host["ts_check_scheduled"] . "!!!\n\n");
    log_it("ID HOST $id_host Scheduled check time  " . $host["next_check_ts"] . " is different from the one retrieved from the scheduled checks" . $host["ts_check_scheduled"] . " !", "CRITICAL");
    //WE RE-ALIGN SCHEDULED CHECKS PLAN WITH THE CURRENT CHECK.
    //WE'RE TELLING THE SYSTEM THAT THE CHECK THAT IS PERFORMING IS THE ONE WITH THE CORRECT TIMESTAMP...
    $host["next_check_ts"] = $host["ts_check_scheduled"];
}
//CALCULATE NEXT RUN
$ts_next = $host["ts_check_scheduled"] + ($host["minutes"] * 60);
//CHECK IF HOST IS PAUSED.... IF PAUSED WE JUST UPDATE CHECKS SCHEDULE AND HOST INFORMATION...
if ($host["enabled"] == 0) {
    prnt("HOST PAUSED.....\n");
    $host_manager_result->setHostUpdate("last_check_str", ts_to_date($host["next_check_ts"]));
    $host_manager_result->setHostUpdate("last_check_ts", $host["next_check_ts"]);
    $host_manager_result->setHostUpdate("next_check_ts", $ts_next);
    $host_manager_result->setHostUpdate("next_check_str", ts_to_date($ts_next));
    $host_manager_result->setHostUpdate("check_result", 'PAUSED');
    $host_manager_result->setHostUpdate("check_running", 0);
    $host_manager_result->setHostUpdate("latest_results", '');
    //UPDATE HOST
    $r = $host_manager_result->updateHost($id_host);
    if ($r === false) {
        prnt("FAILED!\n");
        prnt($host_manager_result->last_error . "\n");
        prnt(implode("\n", $host_manager_result->getHostUpdateInformationArray()) . "\n");
        var_dump($host_manager_result->getHostUpdateInformationArray());
        log_it("HOST UPDATE FAILED!", "CRITICAL");
        log_it($host_manager_result->last_error, "CRITICAL");
    } else {
        prnt("OK\n");
        prnt("Next run scheduled in " . $host["minutes"] * 60 . " seconds confirmed\n");
    }
    prnt("Update scheduled checks table!\n");
    $r = $host_manager_result->updateChecksSchedule($id_host, $host["ts_check_scheduled"], "PAUSED");
    //UPDATE SCHEDULED CHECKS TABLE.....
    if ($r === false) {
        prnt("FAILED!\n");
        prnt($host_manager_result->last_error);
        log_it("UPDATE FAILED!", "CRITICAL");
        log_it($host_manager_result->last_error, "CRITICAL");
    } else {
        prnt("OK!\n");
        log_it("OK!");
    }
    exit;
}
//We check how many seconds of delay we have on the scheduled time
//>30 seconds is a very important delay
$seconds_delay = $time_now - $host["next_check_ts"];
if ($seconds_delay > 45) {
    log_it("ID HOST: $id_host - Delay on scheduled: $seconds_delay seconds", "CRITICAL");
} else {
    log_it("ID HOST: $id_host - Delay on scheduled: $seconds_delay seconds");
}
prnt("!!! Delay on scheduled time: $seconds_delay seconds\n\n");
prnt("MONITOR TYPE: " . $host["check_type"] . "\n\n");
//PING
if ($host["check_type"] == "CHECKCONN") {
    prnt("\n\nReady to ping... Pinging... \n\n\n");
    log_it("Ready to ping... Pinging...");
    //CONFIG PARAMETERS
    $config_arr["pingRequestsTimeout"] = 5;
    $config_arr["pingRequestsWait"] = 1;
    $config_arr["pingRequests"] = 3;
    log_it("CONFIG PARAMETERS:\n " . array_to_string($config_arr));
    //CREATE PING OBJECT
    $monitor = new ping_request();
    $monitor->setHost($host["host"]);
    $monitor->setPort($host["port"]);
    $monitor->setRequests($config_arr["pingRequests"]);
    $monitor->setWait($config_arr["pingRequestsWait"]);
    $monitor->setTimeout($config_arr["pingRequestsTimeout"]);
    //SET PARTIAL OK (POK) RESULT TO OK (OK).....
    $monitor->POKisOK(true);
    $monitor->go();
} //END IF PING
//HTTP HEADER
if ($host["check_type"] == "HTTPHEADER") {
    $monitor = new http_header();
    $monitor->getHeaders($host["host"], $host["port"]);
} //END HTTP HEADER
//FTP CHECK
if ($host["check_type"] == "FTPCONN") {
    $monitor = new ftp_check();
    $monitor->setDomain($host["host"]);
    $monitor->setPort($host["port"]);
    $monitor->go();
}
//SMTP CHECK
if ($host["check_type"] == "SMTPCONN") {
    $monitor = new smtp_check();
    $monitor->setDomain($host["host"]);
    $monitor->setPort($host["port"]);
    $monitor->go();
}
//WEB KEYWORD
//USE THE SAME CLASS OF HTTP HEADER
//SET MODE TO KEYWORD
//SET THE KEYWORD
//CALL THE NORMAL FUNCTION
if ($host["check_type"] == "WEBKEYWORD") {
    $monitor = new http_header();
    $monitor->findKeyword($host["host"], $host["port"], $host["keyword"]);
}
prnt("Monitor ended, checking results\n");
log_it("Monitor ended, checking results");
//CHECK RESULTS
prnt($monitor->getResultCode() . "\n");
prnt($monitor->getSuccess() . "/" . $monitor->getRequests() . " requests OK   (" . $monitor->getResultPerc() . "%)\n");
prnt("Average response time " . $monitor->getAverage() . " ms\n");
log_it($monitor->getResultCode() . "\n");
log_it($monitor->getSuccess() . "/" . $monitor->getRequests() . " requests OK   (" . $monitor->getResultPerc() . ")\n");
log_it("Average response time " . $monitor->getAverage() . " ms\n");
prnt("Previous result:" . $host["check_result"] . "\n");
//CHECK IF THIS IS THE FIRST ROUND......
if ($monitor->getResultCode() == "FIRST") {
    $first_round = true;
    prnt("First Round!!! Welcome to the Jungle dear Monitor!\n");
    log_it("First Round!!! Welcome to the Jungle dear Monitor!");
} else {
    $first_round = false;
    prnt("Not the first Round!!! \n");
    log_it("Not the first Round!!! ");
}
//CHECK IF RESULT IS DIFFERENT SINCE LAST TIME
//WE ALSO CHECK IF THIS IS THE FIRST ROUND...
//WE WANT TO ALERT USERS IF:
//- This is a first round and we found a NOK
//or
//-actual result is different from the last one and this is not a first round
if (($monitor->getResultCode() != $host["check_result"] and $first_round == false)
    or ($monitor->getResultCode() == "NOK" and $first_round == true)
) {
    $last_result_changed = true;
    prnt("Result code changed since previous check\n");
    log_it("Result code changed since previous check");
} else {
    $last_result_changed = false;
    prnt("Same result code as previous check\n");
    log_it("Same result code as previous check");
}
//IMPORT LATEST RESULTS IN ARRAY
$host_manager_result->setArrayLatestResults($host["latest_results"]);
log_it('latest results  imported');
prnt("latest results imported....\n");
//ADD RESULT TO LATEST RESULTS ARRAY
//CHECK IF LATEST RESULTS ARRAY IS FULL
//IF YES WE REMOVE AN ELEMENT BEFORE STORING THE NEW ONE....
$host_manager_result->addResultToLatestResultsArray($monitor->getAverage(), $latest_results_to_keep_as_string);
//PREPARE LATET RESULT STRING...
$latest_results = $host_manager_result->getLatestResultsArrayToString();
prnt("Preparing results\n");
log_it("Preparing results to be saved");
//ALMOST FINISHED!! :)
//TRY TO WRITE RESULTS ON DB
//
//PREPARE RESULTS - RESULTS TABLE
$host_manager_result->setResult("result", $monitor->getResultCode()); ////////////////////
$host_manager_result->setResult("result_was", $host["check_result"]); ////////////////////
$host_manager_result->setResult("requests", $monitor->getRequests()); ///////////////////
$host_manager_result->setResult("requests_ok", $monitor->getSuccess()); ////////////////////
$host_manager_result->setResult("reply_best", round($monitor->getBest(), 3)); ////////////////////
$host_manager_result->setResult("reply_average", round($monitor->getAverage(), 3)); ////////////////////
$host_manager_result->setResult("reply_worst", round($monitor->getWorst(), 3)); ////////////////////
$host_manager_result->setResult("ip", $monitor->getIP()); ////////////////////
$host_manager_result->setResult("details", $monitor->getDetailsAsString()); ////////////////////
$host_manager_result->setResult("error_code", $monitor->getLastErrorCode()); ////////////////////
$host_manager_result->setResult("id_host", $id_host);
$host_manager_result->setResult("ts_check_triggered", $host["next_check_ts"]);
$host_manager_result->setResult("ts_check_delay", $seconds_delay);
$host_manager_result->setResult("session_id", session_id());
$host_manager_result->setResult("host", $host["host"]);
$host_manager_result->setResult("port", $host["port"]);
$host_manager_result->setResult("source", $process_name);
$host_manager_result->setResult("public", $host['public']);
$host_manager_result->setResult("seconds_previous_result", $time_now - $host["last_check_ts"]);
$host_manager_result->setResult("current_date_ts", $time_now);
$host_manager_result->setResult("current_date_ts_00", substr($time_now, 0, 8) . "00");
$host_manager_result->setResult("current_date_ts_000", substr($time_now, 0, 7) . "000");
$host_manager_result->setResult("current_date_str", $host_manager_result->ts_to_date($time_now));
$host_manager_result->setResult("daycode", date("Ymd", $time_now));
$host_manager_result->setResult("time_spent", round((microtime(true) - $ts_boot), 3));
$host_manager_result->setResult("machine_id", _MACHINE_ID_);
//PREPARE UPDATES FOR MAIN HOST TABLE
log_it("Prepare updated information for hosts table");
prnt("Prepare updated information for hosts table\n");
$host_manager_result->setHostUpdate("last_check_str", ts_to_date($host["next_check_ts"]));
$host_manager_result->setHostUpdate("last_check_ts", $host["next_check_ts"]);
$host_manager_result->setHostUpdate("next_check_ts", $ts_next);
$host_manager_result->setHostUpdate("next_check_str", ts_to_date($ts_next));
$host_manager_result->setHostUpdate("check_result", $monitor->getResultCode());  ////////////////////
$host_manager_result->setHostUpdate("last_check_avg", $monitor->getAverage()); ////////////////////
$host_manager_result->setHostUpdate("check_running", 0);
$host_manager_result->setHostUpdate("latest_results", $host_manager_result->getLatestResultsArrayToString());
$host_manager_result->setHostUpdate("edited_ts", time());
//IF RESULT CHANGED SINCE LAST TIME WE WRITE DOWN THE CHANGE
if ($last_result_changed === true) {
    $host_manager_result->setHostUpdate("check_result_since_ts", $time_now);
    $host_manager_result->setHostUpdate("check_result_since_str", ts_to_date($time_now));
} else {
    $host_manager_result->setHostUpdate("check_result_since_ts", $host["check_result_since_ts"]);
    $host_manager_result->setHostUpdate("check_result_since_str", $host["check_result_since_str"]);
}
/////////////////////////START WRITING ON DB..............
//WE CREATE A DO LOOP IN ORDER TO RETRY THE WHOLE TRANSACTION JUST IN CASE SOME SQL FAILS
//THIS COULD HAPPEN WHEN UPDATING/WRITING MULTIPLE TABLES IN ONE TRANSACTION AND THERE'S A LOT OF STUFF
//GOING ON ON THE DB
//DEAD LOCKS WHERE FOUND (1-2/DAY) SOMETIMES... IT WERE NOT A PROBLEM, HOSTS HAS BEEN PROCESSED AGAIN
//BUT WE WANT TO MANAGE SITUATION.....
//
$transaction_max_repeat_on_fails = 10; //MAX REPEATS
$transaction_try = 0; //CURRENT TRY
$transaction_ok = false; //DEFAULT STATE - REPEAT TRANSACTION
do {
    $transaction_try++;
    if ($transaction_try > 1) {
        //Log the start of the transaction if not the first try
        log_it("Transaction repeated (try # " . $transaction_try . ")", "CRITICAL");
        //Be good ... sleep 1 sec...
        //sleep(1);
    }
    //START TRANSACTION!!!
    $host_manager_result->beginTransaction();
    //UPDATE MAIN HOST Table
    $r = $host_manager_result->updateHost($id_host);
    prnt("Update main host table...");
    if ($r === false) {
        prnt("FAILED!\n");
        prnt($host_manager_result->last_error . "\n");
        prnt(implode("\n", $host_manager_result->getHostUpdateInformationArray()) . "\n");
        var_dump($host_manager_result->getHostUpdateInformationArray());
        log_it("HOST $id_host : HOST UPDATE FAILED!", "CRITICAL");
        log_it("Transaction try: " . $transaction_try, "CRITICAL");
        log_it($host_manager_result->last_error, "CRITICAL");
        $host_manager_result->rollbackTransaction();
        continue; //TRY ANOTHER TIME TRANSACTION
    } else {
        prnt("OK\n");
        prnt("Next run scheduled in " . $host["minutes"] * 60 . " seconds confirmed\n");
        log_it("Next run scheduled in " . $host["minutes"] * 60 . " seconds confirmed, host updated");
    }
    //INSERT RESULTS
    prnt("Writing results on DB...");
    if ($host_manager_result->saveResult() === false) {
        prnt("FAILED!\n");
        prnt($host_manager_result->last_error);
        log_it("HOST $id_host : failed to save results on DB", "CRITICAL");
        log_it("Transaction try: " . $transaction_try, "CRITICAL");
        log_it($host_manager_result->last_error, "CRITICAL");
        $host_manager_result->rollbackTransaction();
        continue;
    } else {
        prnt("OK\n");
        log_it("results saved");
    }
    //UPDATE SCHEDULED CHECKS TABLE.....
    prnt("Update scheduled checks table!...");
    $r = $host_manager_result->updateChecksSchedule($id_host, $host["ts_check_scheduled"]);
    if ($r === false) {
        prnt("FAILED!\n");
        prnt($host_manager_result->last_error);
        log_it("UPDATE FAILED!", "CRITICAL");
        log_it("Transaction try: " . $transaction_try, "CRITICAL");
        log_it($host_manager_result->last_error, "CRITICAL");
        $host_manager_result->rollbackTransaction();
        continue; //TRY ANOTHER TIME TRANSACTION
    } else {
        prnt("OK!\n");
        log_it("OK!");
    }
    //SEND MESSAGE TO ALERT THAT MONITOR IS OFFLINE
    //WE SEND ALERTS.....
    //- As soon as the check fails for the first time
    //- if the check still fails every hour
    if ($last_result_changed === true and $monitor->getResultCode() == "NOK") {
        prnt("Monitor result changed to NOK\n");
        $r = $host_manager_result->sendAlertMonitorFailed($id_host, $host["title"]);
        if ($r === false) {
            prnt("ALERT MONITOR MAIL FAILED!\n");
            prnt($host_manager_result->last_error . "\n");
            log_it("HOST $id_host : ALERT MONITOR FAILED!", "CRITICAL");
            log_it("Transaction try: " . $transaction_try, "CRITICAL");
            log_it($host_manager_result->last_error, "CRITICAL");
            $host_manager_result->rollbackTransaction();
            continue; //TRY ANOTHER TIME TRANSACTION
        }
    }
    //SEND MESSAGE TO ALERT THAT MONITOR IS BACK ONLINE
    if ($last_result_changed === true and $monitor->getResultCode() == "OK") {
        prnt("Monitor result changed to OK\n");
        $ret = $host_manager_result->sendAlertMonitorRestored($id_host, $host["check_result_since_ts"], $host["title"]);
    }
    //WRITE HOST LOG TO KEEP TRACK OF CHANGES...
    //THIS COULD HAPPEN IN 2 SITUATION... RESULT CHANGED SINCE LAST TIME OR THIS IS A FIRST ROUND....
    if ($last_result_changed === true or $first_round === true) {
        $host_manager_result->logHostEvent($host["public_token"], $monitor->getResultCode());
    }
    $host_manager_result->commitTransaction();
    ///// END OF TRANSACTION
    //IF WE ARE HERE MEANS THAT EVERYTHNG WENT FINE.....
    //WE UNLOCK THE FLAG IN ORDER TO EXIT THE LOOP....
    $transaction_ok = true;
// DO WHILE CONDITIONS........
} while ($transaction_try < $transaction_max_repeat_on_fails and $transaction_ok === false);
if ($transaction_ok === false) {
    log_it("HOST $id_host : UPDATE FAILED AFTER $transaction_try ATTEMPTS!", "CRITICAL");
    exit;
}
if ($transaction_try > 1 and $transaction_ok === true) {
    log_it("HOST $id_host : UPDATE OK AFTER $transaction_try ATTEMPTS!", "CRITICAL");
}
///////////////////////////////////////////////////////////////////////////////
//LOG PROCESS EXECUTION
//IF PROCESS EXECUTION TIME IS LESS THAN 1 SEC WE ADD 1 SEC (FOR CHARTING PURPOSE)
//PING IS NOT GOING TO BE MONITORED AS A BACKGROUND PROCESS AS IT IS SO FREQUENT...
//WE CAN CHECK RESULTS NUMBER TO VERIFY IT'S EXECUTION COUNT INSTEAD...
//THERE'RE THOUSANDS OF PING PROCESS EVERY FEW MINUTES - IT'S USELESS TO CHART THEM THIS WAY
//NO BG PROC LOG THEN.... :)
//////////////////////////////////////////////////////////////////////////////
log_it("Process completed");
log_it("Duration (server side): " . (microtime(true) - $ts_boot) . " seconds");
log_it("Ping process finished");
prnt("Process completed in " . round((microtime(true) - $ts_boot), 3) . " seconds (server side)    --  This is the last line of the process\n\n");
exit();


