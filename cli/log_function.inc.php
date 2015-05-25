<?php

//LOG BG PROC EXECUTION
function log_bgproc_execution($dbh, $proc_name, $ts_start, $ts_stop) {
    $sql = "insert into bg_proc_logs (proc_name,
                                          ts_start,
                                          ts_stop,
                                          machine_id,
                                          db_id
                                          )
                                          VALUES
                                          (
                                          '$proc_name',
                                          $ts_start,
                                          $ts_stop,
                                          '" . _MACHINE_ID_ . "',
                                          @db_id);";
    //LOG! :)
    $t = $dbh->query($sql);
    if ($t === false) {
        return false;
    }

    return true;
} //END FUNCTION
//function to log information
//Function receives 2 paramaters
//log_note -> text of the log
//level -> type of log
function log_it($log_note, $level = "INFO") {
    //DB HANDLER and CMD PARAMETERS
    //ARE OUT OF THE FUNCTION SCOPE SO.....
    global $mydbh, $process_name, $log_enabled;
    //IF LOG IS DISABLE AND LOG LEVEL IS NOT CRITICAL
    //WE SKIP LOGGING.....
    if ($log_enabled === false and $level != "CRITICAL") {
        return false;
    }
    //STATIC SEQUENTIAL COUNTER FOR LOG
    //USEFUL FOR UNDERSTAND ORDER OF LOGS 
    //WHEN TIME IS THE SAME...
    static $log_seq = 0;
    //LEVEL UPPERCASE
    $level = strtoupper($level);
    $sql = "INSERT INTO logs (process,
                              date_ts,
                              date_str,
                              log_note,
                              log_level,
                              session_id,
                              log_seq,
                              machine_id,
                              db_id
                              )
                              VALUES
                              (
                              :process,
                              :date_ts,
                              :date_str,
                              :log_note,
                              :log_level,
                              :session_id,
                              :log_seq,
                              :machine_id,
                              @db_id
                              );";
    //LOG THE LOG! :)
    $log["process"] = $process_name;
    $log["date_ts"] = time();
    $log["date_str"] = ts_to_date();
    $log["log_note"] = $log_note;
    $log["log_level"] = $level;
    $log["session_id"] = session_id();
    $log["log_seq"] = $log_seq++;
    $log["machine_id"] = _MACHINE_ID_;
    $stmt = $mydbh->prepare($sql);
    $t = $stmt->execute($log);
    if ($t === false) {
        email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"], $_SESSION["config"]["_APP_DEFAULT_EMAIL_"], 'LOG ERROR', 'ERROR WHILE LOGGING\n' . implode("\n", $stmt->errorInfo()) . "\n$sql\n\n" . array_to_string($log));

        return false;
    }

    return true;
} //END OF LOG FUNCTION!

 

