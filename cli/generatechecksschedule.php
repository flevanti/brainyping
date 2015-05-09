<?php
//PROCESS NAME
$process_name = "GENCHECKS";

require_once 'cli_common.php';


if (check_proc_enabled($process_name)===false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}


//LOG ENABLED
$log_enabled = false;


//Create host object
$host_manager = new host_manager();

$ret = $host_manager->createHostCheckScheduleALL();

if ($ret === false) {
    echo "Found an error:<br>";
    echo $host_manager->last_error;
    email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,_APP_DEFAULT_EMAIL_CONTACTS_RECIPIENT_,"GENCHECKS ERROR",$host_manager->last_error);
}


echo "Process terminated\n";
//LOG LAST TIME WATCHDOG WAS EXECUTED.....
//WITH AN INSERT / UPDATE....

$sql = "update bg_proc_last_exec set        date_ts = " . time() . ",
                                            date_str = '" . ts_to_date() . "',
                                            execution_time = " . round((microtime(true)-$ts_boot),3) .",
                                            alert_sent = 0,
                                            machine_id = '"._MACHINE_ID_."',
                                            db_id = @db_id
                    where proc_name = '$process_name' limit 1;";

$t = $mydbh->query($sql);

echo "background process table updated\n";

///////////////////////////////////////////////////////////////////////////////
//LOG PROCESS EXECUTION
//IF PROCESS EXECUTION TIME IS LESS THAN 1 SEC WE ADD 1 SEC (FOR CHARTING PURPOSE)
$ts_shutdown = time();

if (intval($ts_boot) == $ts_shutdown) {
    $ts_shutdown++;
}
log_bgproc_execution($mydbh,$process_name,intval($ts_boot),$ts_shutdown);

//////////////////////////////////////////////////////////////////////////////

echo "Process terminated\n";