<?php
//PROCESS NAME
$process_name = "GENCHECKS";
require_once 'cli_common.php';
if (check_proc_enabled($process_name) === false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}
//LOG ENABLED
$log_enabled = false;
//Create host object
$host_manager = new host_manager();
$ret = $host_manager->createHostCheckScheduleALL();
if ($ret === false) {
    //CHECK IF THE ERROR IS TOO LONG TO BE SHOWN/SENT BY EMAIL
    //ADD A POSSIBLE CAUSE TO THIS (IT USUALLY OCCURS IN DEV WHEN PROCESS IS RUN MANUALLY WHEN NEEDED
    if (strlen($host_manager->last_error) > 2000) {
        $host_manager->last_error = "ERROR GENERATED IS TOO LONG\n" .
            " THIS IS PROBABLY CAUSED BY THE PROCESS RUN AFTER A LONG PERIOD OF PAUSE, " .
            "TRY TO EMPTY THE TABLE BEFORE RUN THE PROCESS AGAIN\n\n\n" .
            substr($host_manager->last_error, 0, 2000);
    }
    echo "Found an error:<br>";
    echo $host_manager->last_error;
    email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, _APP_DEFAULT_EMAIL_CONTACTS_RECIPIENT_, "GENCHECKS ERROR", $host_manager->last_error);
    die(); //if we found an error die here!
}
echo "Process terminated\n";
//LOG LAST TIME WATCHDOG WAS EXECUTED.....
//WITH AN INSERT / UPDATE....
$sql = "update bg_proc_last_exec set        date_ts = " . time() . ",
                                            date_str = '" . ts_to_date() . "',
                                            execution_time = " . round((microtime(true) - $ts_boot), 3) . ",
                                            alert_sent = 0,
                                            machine_id = '" . _MACHINE_ID_ . "',
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
log_bgproc_execution($mydbh, $process_name, intval($ts_boot), $ts_shutdown);
//////////////////////////////////////////////////////////////////////////////
echo "Process terminated\n";