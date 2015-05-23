<?php

class host_manager_results extends host_manager {
    protected $check_already_running = false;
    protected $time_now;
    protected $arr_latest_results;
    protected $str_latest_results;
    protected $last_result_changed;
    protected $arr_results = array();
    protected $arr_host_update = array();
    protected $days_before_deleting_results = 5;
    public $deleted_records = 0;
    public $delete_records_loops = 0;

    function __construct(&$dbconn = false) {
        parent::__construct($dbconn);
        $this->initResultArray();
        $this->initHostUpdateArray();
    }

    function beginTransaction() {
        return $this->dbhandler->beginTransaction();
    }

    function commitTransaction() {
        return $this->dbhandler->commit();
    }

    function rollbackTransaction() {
        return $this->dbhandler->rollBack();
    }

    function inTransaction() {
        return $this->dbhandler->inTransaction();
    }

    function saveResult() {
        $fields = "";
        $bind_vars = "";
        //Create sql statement
        foreach ($this->arr_results as $field => $value) {
            $fields .= $field . ",";
            $bind_vars .= ":" . $field . ",";
        }
        //add db_id field
        $fields .= "db_id";
        //add db_id value (it's not a binded variable it's a mysql variable)
        $bind_vars .= "@db_id";
        try {
            $sql = "insert into results ($fields) VALUES ($bind_vars);";
            $stmt = $this->dbhandler->prepare($sql);
            if ($stmt->execute($this->arr_results) === false) {
                $this->last_error = implode("\n", $stmt->errorInfo());

                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            $this->last_error = "Exception raised while saving results\n" . $e->getMessage();

            return false;
        }
    } //END SAVE RESULT METHOD

    protected function initResultArray() {
        $this->arr_results = array();
    }

    protected function initHostUpdateArray() {
        $this->arr_host_update = array();
    }

    function getNOKConfirmedCode_($previous_result, $current_result) {
        //WE ARE NOK FOR THE FIRST TIME...
        if ($current_result == "NOK" and $previous_result == 'OK') {
            //THIS VALUE WILL BE USED TO SCHEDULE THE NEXT RUN OF THE HOST IMMEDIATELY....
            return "FIRSTNOK";
        }
        //WE ARE NOK ONCE AGAIN....
        if ($current_result == "NOK" and $previous_result == 'NOK') {
            return "STILLOFFLINE";
        }
        //WE ARE NOT A NOK (PING OK!) AFTER A NOK....
        if ($current_result == "OK" and $previous_result == 'NOK') {
            return "BACKONLINE";
        }
        //WE ARE NOT A NOK (PING OK!) AFTER ANOTHER OK.... (VERY GOOD)
        if ($current_result == "OK" and $previous_result == 'OK') {
            return "SOFARSOGOOD";
        }
        if ($previous_result == 'PAUSED') {
            return "UNPAUSED";
        }

        return "UNKNOWN";
    }

    function addResultToLatestResultsArray($result, $max_array_element) {
        $this->arr_latest_results[] = $result;
        if (count($this->arr_latest_results) > $max_array_element) {
            log_it("First element of latest result unset (limit reached)");
            prnt("First element of latest result unset (limit reached)\n");
            unset($this->arr_latest_results[0]);
        }

        return true;
    }

    function getLatestResultsArrayToString() {
        return implode("@", $this->arr_latest_results);
    }

    function setArrayLatestResults($results_str) {
        //If no results are present
        if ($results_str == "" or is_null($results_str)) {
            //array results is empty
            $this->arr_latest_results = array();
        } else { //otherwise
            //array results created
            $this->arr_latest_results = explode("@", $results_str);
            //remove null or empty values using array_filter and strlen function.... yeah!
            $this->arr_latest_results = array_filter($this->arr_latest_results, 'strlen');
        }
    }

    function getHostInformation($idhost) {
        $sql = "SELECT h.*,
                   (SELECT ts_check
                      FROM host_checks_schedule
                     WHERE     id_host = h.id
                           AND ts_check <= $this->time_now
                    ORDER BY ts_check DESC
                     LIMIT 1)
                      ts_check_scheduled
              FROM hosts h
             WHERE h.id = $idhost and h.next_check_ts <= $this->time_now;";
        $host = $this->dbhandler->query($sql);
        $host = $host->fetch(PDO::FETCH_ASSOC); //return false if no records....
        return $host;
    } //END getHostInformation

    function setTimeNow($time) {
        $this->time_now = $time;
    }

    function getCheckAlreadyRunning() {
        return $this->check_already_running;
    }

    function getResultArray() {
        return $this->arr_results;
    }

    function getHostUpdateInformationArray() {
        return $this->arr_host_update;
    }

    function setResult($key, $value) {
        $this->arr_results[$key] = $value;
    }

    function setHostUpdate($key, $value) {
        $this->arr_host_update[$key] = $value;
    }

    function updateHost($id_host) {
        $fields = "";
        //Create sql statement
        foreach ($this->arr_host_update as $field => $value) {
            $fields = $fields . $field . " =  :" . $field . ",";
        }
        //remove last char (that is a "," not needed
        $fields = substr($fields, 0, -1);
        try {
            $sql = "update hosts set $fields where id = $id_host;";
            $stmt = $this->dbhandler->prepare($sql);
            if ($stmt === false) {
                $this->last_error = "Error during stmt prepare operation!!!\n" . implode("\n", $this->dbhandler->errorInfo());

                return false;
            }
            $t = $stmt->execute($this->arr_host_update);
            if ($t === false or $stmt->rowCount() == 0) {
                $this->last_error = "Error during host update!!!\n" . implode("\n", $stmt->errorInfo());

                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            $this->last_error = "(Exception) Error during host update\n" . $e->getMessage();

            return false;
        } //END TRY CATCH
    } //END METHOD UPDATE HOST

    function updateChecksSchedule($id_host, $ts, $status = "COMPLETED") {
        $sql = "update host_checks_schedule set status = '$status'
                    where id_host= $id_host and ts_check = $ts;";
        try {
            $result = $this->dbhandler->query($sql);
            if ($result == false) {
                $this->last_error = "Error while updating checks schedule table\n" . implode("\n", $result->errorInfo());

                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            $this->last_error = "(EXCEPTION) Error while updating checks schedule table\n$e";

            return false;
        }
    }

    function sendAlertMonitorFailed($id_host, $monitor_title) {
        //USERS CONTACTS
        ///////////////////////////////////////////////////////////////
        $sql = "SELECT uc.contact, uc.contact_type_id
                    FROM host_contacts hc, user_contacts uc
                    WHERE hc.id_contact = uc.id
                          AND hc.id_host = $id_host
                            AND uc.enabled = 1;";
        $res = $this->dbhandler->query($sql);
        if ($res === false) {
            $message = "Error during contact retrieving for Host #$id_host\n\n\n";
            email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, _APP_DEFAULT_EMAIL_, "ERROR RETRIEVING CONTACTS ON MONITOR FAILED", $message);

            return false;
        }
        $title = "BRAINYPING MONITOR FAILED - $monitor_title";
        $message = "Your monitor titled " . $monitor_title . " failed.\n\n\n";
        $message .= "This message date/time: " . date("d m Y H:i:s") . "\n\n";
        $message .= "Brainyping Staff";
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            if ($row["contact_type_id"] == "EMAIL") {
                email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, $row["contact"], $title, $message);
            }
        } //END WHILE
        /*
        //update host last alert timestamp...
        $sql = "update hosts set last_alert_ts = " . time() . " where id = " . $id_host . ";";
        $r = $this->dbhandler->query($sql);

        if ($r === false) {
            $this->last_error = implode("\n", $this->dbhandler->errorInfo());
            return false;
        }
        */
        //SUBSCRIBER
        ////////////////////////////////////////////////////////////////
        $sql = "select * from host_subscriptions
                      where id_host = $id_host and validated=1;";
        $res = $this->dbhandler->query($sql);
        //WE WRITE A NEW MESSAGE... IT's A LITTLE BIT DIFFERENT FROM THE ONE ABOVE
        $message = "The monitor titled " . $monitor_title . " failed.\n\n\n";
        $message .= "This message date/time: " . date("d m Y H:i:s") . "\n\n";
        $message .= "Brainyping Staff\n\n\n";
        $message .= "You reveived this message because you subscribed to this monitor\n";
        $message .= "If you no longer want to receive this message for this monitor please click the following link:\n";
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            //COMPLETE THE MESSAGE WITH PERSONALISED LINK TO UNSUBSCRIBE
            $email_message = $message . _APP_ROOT_URL_ . "cancelsubscription/" . $row["validation_token"] . "\n\n";
            if ($row["id_contact_type"] == "EMAIL") {
                email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, $row["contact"], $title, $email_message);
            }
        } //END WHILE
        return true;
    } //END METHOD

    function sendAlertMonitorRestored($id_host, $check_result_since_ts, $monitor_title) {
        //USERS
        /////////////////////////////////////////
        $sql = "SELECT uc.contact, uc.contact_type_id
                    FROM host_contacts hc, user_contacts uc
                    WHERE hc.id_contact = uc.id AND hc.id_host = $id_host;";
        $res = $this->dbhandler->query($sql);
        if ($res === false) {
            $message = "Error during contact retrieving for Host #$id_host\n\n\n";
            email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, _APP_DEFAULT_EMAIL_, "ERROR RETRIEVING CONTACTS ON MONITOR RESTORED", $message);
        }
        $duration = (time() - $check_result_since_ts);
        $title = "BRAINYPING MONITOR RESTORED - $monitor_title";
        $message = "Your monitor titled " . $monitor_title . " is restored.\n\n";
        $message .= "Failure duration: " . calculate_time($duration, "STRING") . "\n\n\n";
        $message .= "This message date/time: " . date("d m Y H:i:s") . "\n\n";
        $message .= "Brainyping Staff";
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            if ($row["contact_type_id"] == "EMAIL") {
                email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, $row["contact"], $title, $message);
            } //end if
        } //END WHILE
        //SUBSCRIBER
        ////////////////////////////////////////////////////////////////
        $sql = "select * from host_subscriptions
                      where id_host = $id_host and validated=1;";
        $res = $this->dbhandler->query($sql);
        //WE WRITE A NEW MESSAGE... IT's A LITTLE BIT DIFFERENT FROM THE ONE ABOVE
        $message = "The monitor titled " . $monitor_title . " is restored.\n\n\n";
        $message .= "Failure duration: " . calculate_time($duration, "STRING") . "\n\n\n";
        $message .= "This message date/time: " . date("d m Y H:i:s") . "\n\n";
        $message .= "Brainyping Staff\n\n\n";
        $message .= "You reveived this message because you subscribed to this monitor\n";
        $message .= "If you no longer want to receive this message for this monitor please click the following link:\n";
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            //COMPLETE THE MESSAGE WITH PERSONALISED LINK TO UNSUBSCRIBE
            $email_message = $message . _APP_ROOT_URL_ . "cancelsubscription/" . $row["validation_token"] . "\n\n";
            if ($row["id_contact_type"] == "EMAIL") {
                email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, $row["contact"], $title, $email_message);
            }
        } //END WHILE
        return true;
    } //END METHOD

    function deleteOldResults() {
        $ts_limit = intval(time() - ($this->days_before_deleting_results * 24 * 60 * 60));
        $sql = "delete from host_checks_schedule
                      where aggregated=1 and ts_check < $ts_limit
                          limit 10000;";
        $stmt = $this->dbhandler->prepare($sql);
        $continue_loop = true;
        $this->deleted_records = 0;
        //FIRST WE DELETE SCHEDULES....
        //We create a loop to delete records in chunks.....be good boy!
        while ($continue_loop === true) {
            $stmt->execute();
            $deleted_records_partial = $stmt->rowCount();
            $this->deleted_records += $deleted_records_partial;
            $this->delete_records_loops++;
            if ($deleted_records_partial == 0) {
                $continue_loop = false;
            }
        }
        //WE NOW NEED TO DELETE ACTUAL RESULTS....
        //WE RETRIEVE EACH HOST LAST DATE SCHEDULED (IN THE PAST) AS A DATE LIMIT....
        $sql = "SELECT hcs.id_host, min(hcs.ts_check) ts_limit
                    FROM host_checks_schedule hcs
                      GROUP BY hcs.id_host;";
        $host_list = $this->dbhandler->query($sql);
        while ($row = $host_list->fetch(PDO::FETCH_ASSOC)) {
            ////CONSIDERING THAT WE ARE DELETEING EACH HOST SEPARATELY
            //WE JUST DELETE ITS RECORD IN A SINGLE TRANSACTION
            //WITHOUT SPLITTING IN CHUNKS...
            //CONSIDER THIS: EVEN IF A HOST IS SCHEDULED EVERY MINUTE WE WILL HAVE 1440 RECORDS...VERY FEW.
            //SO...
            $sql = "DELETE FROM results
                            WHERE id_host = " . $row["id_host"] . "
                                    AND ts_check_triggered < " . $row["ts_limit"] . ";";
            $this->dbhandler->query($sql);
        } //END WHILE LOOP ON HOSTS LIST
        return true;
    }
} //END CLASS