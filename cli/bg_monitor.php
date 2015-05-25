<?php

//PROCESS NAME
$process_name = "BGMONITOR";
require_once 'cli_common.php';
if (check_proc_enabled($process_name) === false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}
//MINUTES ALERT
$minutes_alert = 5;
//LOG ENABLED
$log_enabled = false;
//Select Background process that are not been executed as planned
$sql = "SELECT t.*
              FROM bg_proc_last_exec t
             WHERE ((t.minutes_alert * 60) + t.date_ts) < UNIX_TIMESTAMP()
                AND enabled = 1;";
$rs = $mydbh->query($sql);
$records = $rs->rowCount();
echo "Found $records records\n<br>";
//If we found some records we need to alert admins.... so we retrieve emails
if ($records > 0) {
    $sql = "SELECT email FROM users WHERE enabled=1 AND role='ADMIN';";
    $rs_email = $mydbh->query($sql);
    $emails = array();
    while ($rowemail = $rs_email->fetch(PDO::FETCH_ASSOC)) {
        $emails[] = $rowemail["email"];
    }
    $emails = implode(",", $emails);
    echo "Emails: $emails\n<br>";
}
echo "--------------------------------\n<br>";
while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
    echo "Process: " . $row["proc_name"] . "\n<br>";
    $subject = "BRAINYPING ALERT - " . $row["proc_name"] . " process failed!";
    $message = "The process does not run as planned!\n\n" .
        "Last time run: " . $row["date_str"] . " (" . (time() - $row["date_ts"]) . " seconds ago) \n\n" .
        "Minutes alert: " . $row["minutes_alert"] . "\n\n" .
        "Brainyping Team\n\nThis is an automatic mailer please do not reply";
    $r = email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"], $emails, $subject, $message, "");
    if ($r) {
        echo "Email queued\n<br>";
        $sql = "UPDATE bg_proc_last_exec SET alert_sent = " . time() . " WHERE proc_name = '" . $row["proc_name"] . "';";
        $mydbh->query($sql);
    } else {
        echo "Emal queue failed\n<br>";
    }
    echo "------------------------------------\n<br>";
} //END WHILE
//LOG LAST TIME WATCHDOG WAS EXECUTED.....
$sql = "update bg_proc_last_exec set  date_ts = " . time() . ",
                                            date_str = '" . ts_to_date() . "',
                                            execution_time = " . round((microtime(true) - $ts_boot), 2) . ",
                                            hosts = $records,
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