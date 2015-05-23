<?php

//PROCESS NAME
$process_name = "SYNCTABLES";
require_once 'cli_common.php';
if (check_proc_enabled($process_name) === false) {
    die("PROCESS DISABLE. CHECK CONFIG\n\n");
}
echo "SYNC OBJECT CREATION\n\n";
if (isset($argv[1])) {
    $profile = $argv[1];
    echo "PROFILE FOUND IN ARGUMENT: " . $profile . "\n";
} else {
    $profile = false;
}
//Create a new sync object and give it the "SOURCE/INSIDE" connection
//Every sync profile has its own DB "DESTINATION/EXTERNAL" settings or LOCAL DB flag.
$sync = new sync($mydbh);
$sync->verbose = true;
$sync->verbose_newline = "\n";
$ret = $sync->sync($profile);
if ($ret === true) {
    echo "Looks like completed!\n";
} else {
    echo "Error found:\n";
    echo $sync->last_error . "\n";
    echo $sync->last_error_tech;
}
//LOG LAST TIME WATCHDOG WAS EXECUTED.....
$sql = "update bg_proc_last_exec set    date_ts = " . time() . ",
                                            date_str = '" . ts_to_date() . "',
                                            date_ts_db = unix_timestamp(),
                                            execution_time = " . round((microtime(true) - $ts_boot), 3) . ",
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