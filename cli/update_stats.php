<?php
//PROCESS NAME
$process_name = "STATSUPDATE";

require_once 'cli_common.php';


if (check_proc_enabled($process_name)===false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}


//TS_BOOT is the timestamp of the process, when it has been started.
$ts_boot = microtime(true);

//LOG ENABLED
$log_enabled = false;


//check if some stats need to be updated...
$sql = "select * from stats_data
            where (generated_ts+update_interval_minutes*60) < UNIX_TIMESTAMP()
              and enabled = 1;";

$res = $mydbh->query($sql);

//Create a new aggregate object, delete queued host method is here.... :)
$r = new  stats_generate();
$note = "";
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $note .= "Update stats: " . $row["id_stat"] . "\n";

    //CREATE METHOD NAME DINAMICALLY
    $method_name = "genStats_" . $row["id_stat"];

    echo "Calling $method_name\n";
    //CALL METHOD NAME
    $ret = $r->$method_name($row["id_stat"]);

    if ($ret === false) {
        echo "Returned false: " . $r->last_error . "\n";
    }

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