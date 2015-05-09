<?php

//PROCESS NAME
$process_name = "CHECKTIMEALIGNMENT";

require_once 'cli_common.php';



if (check_proc_enabled($process_name)===false) {
    die("PROCESS DISABLE. CHECK CONFIG");
}



//LOG ENABLED
$log_enabled = false;



//Select process used for time alignment that are off alignment....
$sql = "SELECT t.proc_name, (date_ts-date_ts_db) as sec_diff
              FROM bg_proc_last_exec t
             WHERE t.check_time_alignment = 1 having ABS(sec_diff) > ". _SEC_ALIGNMENT_ALERT_ .";";

$rs = $mydbh->query($sql);

if ($rs == false) {
    echo implode("\n",$mydbh->errorInfo());
    exit;
}

$records = $rs->rowCount();
echo "Found $records records off alignment\n<br>";

//If we found some records we need to alert admins.... so we retrieve emails
if ($records > 0) {


    //RETRIEVE EMAILS....
    $sql = "select email from users where enabled=1 and role='ADMIN';";
    $rs_email = $mydbh->query($sql);
    $emails =array();
    while  ($rowemail = $rs_email->fetch(PDO::FETCH_ASSOC)) {
        $emails[] = $rowemail["email"] ;
    }
    $emails = implode(",",$emails);
    echo "Emails: $emails\n<br>";

    echo "--------------------------------\n<br>";

    //SEND EMAILS....
    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
        echo "Process: " . $row["proc_name"] . "\n<br>";
        $subject = "BRAINYPING ALERT - " . $row["proc_name"] . " is not time-aligned!";
        $message = "The process is not time-aligned\n\n" .
            "It was " . $row["sec_diff"] . " seconds away from main server\n\n" .
            "Minutes alert: " . $row["minutes_alert"] . "\n\n" .
            "Brainyping Team\n\nThis is an automatic mailer please do not reply";
        $r = email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,$emails,$subject,$message,"");
        if ($r) {
            echo "Email queued\n<br>";
        } else {
            echo "Email queue failed\n<br>";
        }
        echo "------------------------------------\n<br>";
    } //END WHILE


} //END IF $RECORDS > 0






//LOG LAST TIME IT WAS EXECUTED.....

$note = "Found $records records not time-aligned";

$sql = "update bg_proc_last_exec set  date_ts = " . time() . ",
                                            date_str = '" . ts_to_date() . "',
                                            execution_time = " . round((microtime(true)-$ts_boot),2) .",
                                            note = '$note',
                                            alert_sent = 0,
                                            machine_id = '"._MACHINE_ID_."',
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
log_bgproc_execution($mydbh,$process_name,intval($ts_boot),$ts_shutdown);

//////////////////////////////////////////////////////////////////////////////



echo "Process terminated\n<br>";