<?php

//PROCESS NAME
$process_name = "CLEARDELETEDQUEUE";
require_once 'cli_common.php';
if (check_proc_enabled($process_name) === false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}
//LOG ENABLED
$log_enabled = false;
//Create a new aggregate object, delete queued host method is here.... :)
$r = new host_manager();
$ret = $r->removeDeletedHosts();
if (count($ret) > 0) {
    log_it("Some hosts couldn't be removed at this time", "CRITICAL");
    log_it("ID HOST NOT REMOVED: " . implode(" - ", $ret), "CRITICAL");
}
//LOG LAST TIME IT WAS EXECUTED.....
$sql = "update bg_proc_last_exec set date_ts = " . time() . ",
                                    date_str = '" . ts_to_date() . "',
                                    execution_time = " . round((microtime(true) - $ts_boot), 3) . ",
                                    note = '',
                                    alert_sent = 0,
                                    machine_id = '" . _MACHINE_ID_ . "',
                                    db_id = @db_id
                    where proc_name = '$process_name' limit 1;";
$stmt = $mydbh->prepare($sql);
$stmt->execute();
echo "BG Process Table Updated ";
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