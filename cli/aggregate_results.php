<?php
//PROCESS NAME
$process_name = "AGGREGATERESULTS";

require_once 'cli_common.php';

if (check_proc_enabled($process_name)===false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}


//LOG ENABLED
$log_enabled = false;

//Create a new aggregate object, delete queued host method is here.... :)
$r = new aggregate();
if ($r->aggregate() === false) {
    email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,_APP_DEFAULT_EMAIL_,"AGGREGATE RESULT FAILED",$r->last_error);
    $note = "ERROR";
} else {
    $note = "Records found: " . $r->records_found . "\n";
    $note .= "Records processed: " . $r->records_processed . "\n";
    $note .= "Max execution time reached: " . ($r->max_time_reached===true?"True":"False");
}

if ($r->max_time_reached ) {
    email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,_APP_DEFAULT_EMAIL_,"AGGREGATE RESULT TIME LIMIT REACHED",$r->last_error);
}

$sync_flag_id = "RESULTSDAILY";
$sql = "update sync_tables_config set force_run = 1 where friendly_name = 'RESULTSDAILY';";

$ret = $mydbh->query($sql);

if ($ret === false) {
    email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,_APP_DEFAULT_EMAIL_,"AGGREGATE RESULTS DAILY FAILED SYNC FLAG",implode("\n",$mydbh->errorInfo()));
    echo "Unable to activate sync flag $sync_flag_id\n";
}



//LOG LAST TIME IT WAS EXECUTED.....

$sql = "update bg_proc_last_exec set date_ts = " . time() . ",
                                    date_str = '" . ts_to_date() . "',
                                    execution_time = " . round((microtime(true)-$ts_boot),3) .",
                                    note = :note,
                                    alert_sent = 0,
                                    machine_id = '"._MACHINE_ID_."',
                                    db_id = @db_id
                    where proc_name = '$process_name' limit 1;";


$stmt = $mydbh->prepare($sql);
$stmt->execute(["note"=>$note]);

prnt ("BG Process Table Updated \n");


///////////////////////////////////////////////////////////////////////////////
//LOG PROCESS EXECUTION
//IF PROCESS EXECUTION TIME IS LESS THAN 1 SEC WE ADD 1 SEC (FOR CHARTING PURPOSE)
$ts_shutdown = time();

if (intval($ts_boot) == $ts_shutdown) {
    $ts_shutdown++;
}
log_bgproc_execution($mydbh,$process_name,intval($ts_boot),$ts_shutdown);

//////////////////////////////////////////////////////////////////////////////



prnt ("Process terminated\n");