<?php

//PROCESS NAME
$process_name = "EMAILQUEUE";
require_once 'cli_common.php';
if (check_proc_enabled($process_name) === false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}
//LOG ENABLED
$log_enabled = false;
$r = email_queue::processQueue();
$email_ok = $r["ok"];
$email_failed = $r["failed"];
$email_sent = $email_ok + $email_failed;
//LOG LAST TIME IT WAS EXECUTED.....
$sql = "update bg_proc_last_exec set    date_ts = " . time() . ",
                                        date_str = '" . ts_to_date() . "',
                                        execution_time = " . round((microtime(true) - $ts_boot), 3) . ",
                                        note = 'Email sent: $email_sent\r\nEmail ok: $email_ok\r\nEmail failed: $email_failed',
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
echo "Process terminated\n<br>";