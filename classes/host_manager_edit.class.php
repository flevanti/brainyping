<?php

class host_manager_edit extends host_manager {

    protected $arr_error = array();

    protected $edit_new = false;
    protected $interval_changed = false;

    protected $id_host = false;

    protected $arr_data = array();
    protected $arr_contacts = array();

    protected $string_to_find = "";

    protected $next_check_ts;

    function __construct(&$dbconn = false) {
        parent::__construct($dbconn);
        //$this->initArrayInfo();
    }

    public function saveData() {
        $this->checkFormData();
        $this->writeDataToDB();
        if (count($this->arr_error) > 0) {
            return false;
        }

        return true;
    }


    //TRY TO GET THE BEST MOMENT IN THE FUTURE ($interval minutes) to start monitor
    //IT COMPARE THE SCHEDULED MONITORS IN THE NEXT $INTERVAL MINUTES
    //AND
    //ALSO CONSIDER NOW (OTHERWISE WE COULD NOT HAVE ANY NEW SCHEDULE IN SECONDS WHERE
    //IT'S NOT ALREADY PLANNED SOMETHING
    private function getBestMonitorTS($interval = 10) {
        $now = time();
        $sql = "SELECT
                        *
                    FROM
                        (SELECT
                            ts_check, COUNT(*) nrec
                        FROM
                            host_checks_schedule
                        WHERE
                            ts_check >= $now
                                AND ts_check <= $now + ($interval * 60)
                        GROUP BY ts_check
                        ORDER BY nrec , ts_check
                        LIMIT 1) AS q1,
                        (SELECT
                            '$now' AS ts_now, COUNT(*) nrec_now
                        FROM
                            host_checks_schedule
                        WHERE
                            ts_check = $now) AS  q2
                    GROUP BY ts_now;";
        try {
            $row = $this->dbhandler->query($sql)->fetch(PDO::FETCH_ASSOC);
            if ($row["nrec"] < $row["nrec_now"]) {
                return $row["ts_check"];
            }

            return $row["ts_now"];
        } catch (Exception $e) {
            return time();
        }
    }

    private function writeDataToDB() {
        if (count($this->arr_error) > 0) {
            return false;
        }
        //Just to be sure to use the same timestamp we put timestamp in a variable
        $now = time();
        //We check what's the best (LESS POPULATED) moment to start monitor,
        //this is to have a balanced distribution of checks across time....
        $best_ts = $this->getBestMonitorTS();
        //BEGIN DB TRANSACTION
        $this->dbhandler->beginTransaction();
        if ($this->edit_new === true) { //INSERT NEW RECORD
            $this->arr_data["public_token"] == $this->generateRandomPublicToken(20);
            $this->arr_data["added_ts"] = $now;
            $this->arr_data["next_check_ts"] = $best_ts; //$now + ($this->arr_data["minutes"] * 60);
            $this->arr_data["next_check_str"] = ts_to_date($best_ts); //ts_to_date($this->arr_data["next_check_ts"]);
            //DEFAULT RESULT FOR NEW HOST='FIRST' ...SO WE COULD UNDERSTAND WHEN PERFORMING THE FIRST CHECK THAT IT'S THE FIRST ROUND! :)
            $this->arr_data["check_result"] = "FIRST";
            $arr_bind = build_query::getStatementBindVars($this->arr_data, "INSERT");
            $sql = "insert into hosts (  {$arr_bind["fields"]}  ) values (  {$arr_bind["bind_names"]} );";
            $stmt = $this->dbhandler->prepare($sql);
            $r = $stmt->execute($arr_bind["bind_values"]);
            if ($r === false) {
                $this->arr_error[] = "Error during insert query";

                return false;
            }
            $this->id_host = $this->dbhandler->lastInsertId();
            //CREATE CHECKS SCHEDULE
            //BEING A NEW HOST WE WANT THE FIRST CHECK TO BE RIGHT NOW (NOT IN XX MINUTES)
            //SO WE PASS THE TIMESTAMP-INTERVAL TO THE FUNCTION TO CREATE CHECKS SCHEDULE
            $this->createHostCheckSchedule($this->id_host
                , $this->arr_data["minutes"]
                , ($best_ts - ($this->arr_data["minutes"] * 60)));
            //CREATE CONTACTS
            foreach ($this->arr_contacts as $id_contact) {
                $id_contact = intval($id_contact);
                $sql = "insert into host_contacts (id_host, id_contact) values ($this->id_host,$id_contact); ";
                $r = $this->dbhandler->query($sql);
                if ($r === false) {
                    $this->arr_error[] = "Error during query on contacts saving";

                    return false;
                }
            } //END FOREACH CONTACTS
            //CREATE HOST EVENT LOG
            $this->logHostEvent($this->arr_data["public_token"], "CREATED");
        } else { //MODIFY/UPDATE RECORD
            //ADD EDITED TIMESTAMP
            $this->arr_data["edited_ts"] = $now;
            $arr_bind = build_query::getStatementBindVars($this->arr_data, "UPDATE");
            $sql = "update hosts set {$arr_bind["fields_and_bind_names"]} where id = {$this->id_host}  ;";
            $stmt = $this->dbhandler->prepare($sql);
            $r = $stmt->execute($arr_bind["bind_values"]);
            if ($r === false) {
                $this->arr_error[] = "Error during update query";
                if (check_server_env::getEnv() == "DEV") {
                    $this->arr_error[] = "error code: " . $stmt->errorCode();
                    $this->arr_error[] = "error: " . implode("\n", $stmt->errorInfo());
                    $this->arr_error[] = $sql;
                    $this->arr_error[] = print_r($arr_bind["bind_values"], 1);
                }

                return false;
            }
            if ($this->interval_changed === true) {
                //CREATE CHECKS SCHEDULE
                $this->createHostCheckSchedule($this->id_host
                    , $this->arr_data["minutes"]
                    , $this->next_check_ts);
            }
            //UPDATE CONTACTS
            //WE REMOVE ALL OF THEM...
            $sql = "DELETE FROM host_contacts WHERE id_host = " . $this->id_host . ";";
            $this->dbhandler->query($sql);
            //AND THEN WE ADD THE CHOOSEN ONES
            foreach ($this->arr_contacts as $id_contact) {
                $id_contact = intval($id_contact);
                $sql = "insert into host_contacts (id_host, id_contact) values ($this->id_host,$id_contact); ";
                $r = $this->dbhandler->query($sql);
                if ($r === false) {
                    $this->arr_error[] = "Error during query on contacts saving";

                    return false;
                }
            } //END FOREACH CONTACTS
        } //END IF
        //COMMIT DB TRANSACTION
        $this->dbhandler->commit();

        return true;
    }

    function writeJsonResult() {
        if (count($this->arr_error) > 0) {
            echo json_encode(["result" => false, "error_descr" => $this->arr_error]);
        } else {
            echo json_encode(["result" => true]);
        }
    }

    private function checkFormData() {
        //PUT THE USER ID IN THE DATA ARRAY
        $this->arr_data["id_user"] = user::getID();
        //PUBLIC TOKEN CHECK
        if (!isset($_POST["public_token"])
            or ctype_alnum($_POST["public_token"]) === false
        ) {
            $this->arr_error[] = "Invalid public token";

            return false;
        } else {
            if ($_POST["public_token"] == "new") {
                $this->edit_new = true;
                $this->arr_data["public_token"] = $this->generateRandomPublicToken(20);
            } else {
                $this->edit_new = false;
                $this->arr_data["public_token"] = $_POST["public_token"];
            }
        }
        //Check host ownership if edit
        //We also retrieve the host ID and the next_check ts
        if ($this->edit_new === false) {
            $sql = "SELECT id, next_check_ts FROM hosts WHERE id_user = " . user::getID() . " AND public_token = :public_token;";
            $stmt = $this->dbhandler->prepare($sql);
            $r = $stmt->execute(["public_token" => $this->arr_data["public_token"]]);
            if ($r === false) {
                $this->arr_error[] = "Error during host ownership query execution";

                return false;
            }
            if ($stmt->rowCount() == 0) {
                $this->arr_error[] = "Host ownership error";

                return false;
            } else {
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                //ID IS USED FOR UPDATE
                $this->id_host = $rs["id"];
                //NEXT CHECK TS IS USED IF INTERVAL IS CHANGED TO
                //CREATE NEW CHECKS SCHEDULE
                $this->next_check_ts = $rs["next_check_ts"];
            }
        }
        //FRIENDLY NAME CHECK
        //We remove some chars from the title just to ckeck it with
        //and easier function "ctype_alnum" instead of regex
        $allowed_chars = [" ", "-", "_", "."];
        if (!isset($_POST["title"])
            or ctype_alnum(str_replace($allowed_chars, "", $_POST["title"])) === false
        ) {
            $this->arr_error[] = "Friendly name invalid char found";
        } else {
            $this->arr_data["title"] = trim($_POST["title"]);
        }
        //PREVIOUS INTERVAL CHECK
        if ($this->edit_new === true) {
            $previous_interval = -1;
        } else {
            if (!isset($_POST["interval_old"])) {
                $this->arr_error[] = "Unable to find previous interval value";

                return false;
            } else {
                $previous_interval = intval($_POST["interval_old"]);
            }
        }
        //INTERVAL CHECK
        if (!isset($_POST["interval"])
            or is_numeric(trim($_POST["interval"])) === false
            or intval($_POST["interval"]) < 1
            or intval($_POST["interval"]) > 120
        ) {
            $this->arr_error[] = "Interval is not valid (range 1-120)";
        } else {
            $this->arr_data["minutes"] = intval($_POST["interval"]);
            if ($previous_interval == $this->arr_data["minutes"]) {
                $this->interval_changed = false;
            } else {
                $this->interval_changed = true;
            }
        }
        //CHECK MONITOR TYPE
        if (($this->edit_new == true and !isset($_POST["check_type"])) ||
            ($this->edit_new == false and !isset($_POST["check_type_old"]))
        ) {
            $this->arr_error[] = "Monitor type not found";

            //IF MONITOR TYPE IS NOT FOUND NO NEED TO PROCEED TO NEXT STEPS...
            return false;
        }
        $check_type_posted = ($this->edit_new == true ? $_POST["check_type"] : $_POST["check_type_old"]);
        $check_types = ["CHECKCONN", "HTTPHEADER", "WEBKEYWORD", "FTPCONN", "SMTPCONN"];
        if (array_search($check_type_posted, $check_types) === false) {
            $this->arr_error[] = "Monitor type is not valid";

            //IF MONITOR TYPE IS NOT VALID NO NEED TO PROCEED TO NEXT STEPS...
            return false;
        } else {
            $this->arr_data["check_type"] = $check_type_posted;
        }
        //CHECK CONTACTS
        //AT LEAST ONE CONTACT IS NEEDED - DEPRECATED...USERS COULD DECIDE JUST TO MONITOR
        //IF NO CONTACTS, WE CREATE AN EMPTY ARRAY...
        if (!isset($_POST["contacts"])) {
            //    $this->arr_error[] = "At least 1 contact is needed";
            //    return false;
            $_POST["contacts"] = array();
        }
        if ($this->checkFormContactsOwnership($_POST["contacts"]) === false) {
            $this->arr_error[] = "Contacts ownership error";

            return false;
        } else {
            $this->arr_contacts = $_POST["contacts"];
        }
        //IF CHECKCONN MONITOR TYPE SELECTED, CHECK HOST AND PORT....
        if ($this->arr_data["check_type"] == "CHECKCONN" or $this->arr_data["check_type"] == "FTPCONN" or $this->arr_data["check_type"] == "SMTPCONN") {
            if (!isset($_POST["host_address"])) {
                $this->arr_error[] = "Host information not found";

                return false;
            }
            $checkHostPort = new check_address($_POST["host_address"]);
            //HOST CHECK
            if ($checkHostPort->isValidHost() === false) {
                $this->arr_error[] = "Invalid host: " . $checkHostPort->last_error;

                return false;
            } else {
                $this->arr_data["host"] = $_POST["host_address"];
            }
            $checkHostPort->setHostPort($_POST["host_port"]);
            //PORT CHECK
            if ($checkHostPort->isHostPortValid() === false) {
                $this->arr_error[] = "Port number is not valid: " . $checkHostPort->last_error;

                return false;
            } else {
                $this->arr_data["port"] = intval($_POST["host_port"]);
            }
        } //END IF MONITOR TYPE = CHECKCONN
        //IF HTTPHEADER / KEYWORD MONITOR SELECTED , CHECK URL
        if ($this->arr_data["check_type"] == "HTTPHEADER" or $this->arr_data["check_type"] == "WEBKEYWORD") {
            if (!isset($_POST["host_url"])) {
                $this->arr_error[] = "Host information not found";

                return false;
            }
            //ADD AUTOMATICALLY HTTP:// IF NOT FOUND.....
            //IF WE CANNOT FIND HTTP:// or HTTPS:// we add it by default
            //WE ADD HTTP://
            if (stripos($_POST["host_url"], "http://") === false and stripos($_POST["host_url"], "https://") === false) {
                $_POST["host_url"] = "http://" . $_POST["host_url"];
            }
            $checkHostPort = new check_address($_POST["host_url"]);
            if ($checkHostPort->isValidUrlMultiCheck() === false) {
                $this->arr_error[] = $checkHostPort->last_error;

                return false;
            }
            //Put the value in the array for DB saving process
            $this->arr_data["host"] = $_POST["host_url"];
            //USERS CANNOT DEFINE PORT FOR WEB CHECKS SO WE "GET" THE PORT BY OURSELVES
            //HTTP -> 80
            //HTTPS -> 443
            if ($checkHostPort->setUrlPortAutomatically() === false) {
                $this->arr_error[] = $checkHostPort->last_error;

                return false;
            }
            //We retrieve the port in order to save it...
            $this->arr_data["port"] = $checkHostPort->getUrlPort();
        } //END IF MONITOR TYPE = HTTP HEADER /KEYWORD
        //CHECK KEYWORD IF MONITOR TYPE IS WEB KEYWORD.....
        if ($this->arr_data["check_type"] == "WEBKEYWORD") {
            if (!isset($_POST["host_url_keyword"]) or trim($_POST["host_url_keyword"]) == "") {
                $this->arr_error[] = "keyword not found or empty";

                return false;
            } else {
                $this->arr_data["keyword"] = $_POST["host_url_keyword"];
            }
        }
        //ON inserting a new record,
        if ($this->edit_new === true) {
            //Verify if the user has already  the same record.....
            $ret = $this->checkDoubleHost($this->arr_data["check_type"], $this->arr_data["host"], $this->arr_data["port"], user::getID());
            if ($ret === true) { //Double record found.....
                $this->arr_error[] = "Unable to proceed duplicate monitor type/host/port found in your list";

                return false;
            }
            if ($ret == -1) { //Error on retrieving records....
                return false;
            }
            //If we are here means everything went fine... continue....
        }
        //ON inserting new record for a NON-ADMIN, we check if the record is already present and available to subscribe
        if ($this->edit_new === true and user::getRole() != "ADMIN") {
            //Verify if the host is already available for subscription.....
            $ret = $this->checkHostAvailableSubscription($this->arr_data["check_type"], $this->arr_data["host"], $this->arr_data["port"]);
            if ($ret === true) { //Host available for subscription found.....
                $this->arr_error[] = "This host is available for subscription, unable to save";

                return false;
            }
            if ($ret == -1) { //Error on retrieving records....
                return false;
            }
            //If we are here means everything went fine... continue....
        }

        //All checks performed are ok!
        return true;
    }


    //Check if user has the same monitor type/host/port in his/her list...
    //True = double record found
    //False = no double record found
    //-1 = error on retrieving information
    private function checkDoubleHost($check_type, $host, $port, $id_user) {
        $sql = "SELECT count(*) AS n FROM hosts WHERE check_type = :check AND host = :host AND port = :port AND id_user = :id_user;";
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->arr_error[] = "Statement preparation error on checkDoubleHost function";

            return -1;
        }
        $ret = $stmt->execute(array("check" => $check_type, "host" => $host, "port" => $port, "id_user" => $id_user));
        if ($ret === false) {
            $this->arr_error[] = "Statement execution error on checkDoubleHost function";

            return -1;
        }
        if ($stmt->fetch(PDO::FETCH_ASSOC)["n"] > 0) {
            return true;
        }

        return false;
    }


    //Check if  the same monitor type/host/port in already available for subscription...
    //True = record found
    //False = no record found
    //-1 = error on retrieving information
    private function checkHostAvailableSubscription($check_type, $host, $port) {
        $sql = "SELECT count(*) AS n FROM hosts WHERE check_type = :check
                                                  AND host = :host
                                                  AND port = :port AND homepage = 1;";
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->arr_error[] = "Statement preparation error on checkDoubleHost function";

            return -1;
        }
        $ret = $stmt->execute(array("check" => $check_type, "host" => $host, "port" => $port));
        if ($ret === false) {
            $this->arr_error[] = "Statement execution error on checkHostAvailableSubscription function";

            return -1;
        }
        if ($stmt->fetch(PDO::FETCH_ASSOC)["n"] > 0) {
            return true;
        }

        return false;
    }

    private function checkFormContactsOwnership($arr_contacts) {
        $num_contacts = count($arr_contacts);
        if ($num_contacts == 0) {
            return true;
        }
        //SANITIZE ARRAY :)
        foreach ($arr_contacts as $key => $value) {
            $arr_contacts[$key] = intval($value);
        }
        $string_contacts = implode(",", $arr_contacts);
        $sql = "select count(*) as num_contacts
                          from user_contacts
                              where id_user = " . user::getID() . "
                              and validated = 1
                              and id in ($string_contacts);";
        $rs = $this->dbhandler->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($rs["num_contacts"] == $num_contacts) {
            return true;
        } else {
            return false;
        }
    } //END METHOD checkFormContactsOwnership
} //END CLASS