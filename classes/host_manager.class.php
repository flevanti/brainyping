<?php
class host_manager {
    protected $dbhandler;
    protected $host_in_edit;
    public $last_error;
    public $last_exception;
    protected $checks_schedule_days_future = 0.5; //This should be an integer, 0.5 is ok cause means half. Do not use other non-integer value.
    public $checks_scheduled_generated = 0;

    
    function __construct (&$dbconn = false) {
        if ($dbconn === false) {
            global $mydbh;
            $this->dbhandler = $mydbh;
        } else {
            $this->dbhandler = $dbconn;
        }

    }

    function validHostOrIP ($host) {
        if(filter_var(gethostbyname($host), FILTER_VALIDATE_IP) !== false) {
            return true;
        } else {
            return false;
        }

    }
    function getUserHostsCount($mode="ALL") {
        if (!isset($_SESSION["user"]["host_monitored"])) {
            $this->updateUserHostNumber();
        }
        switch ($mode) {
            case "ALL":
                return $_SESSION["user"]["host_total"];
                break;
            case "MONITORED":
                return $_SESSION["user"]["host_monitored"];
                break;
            case "PAUSED":
                return $_SESSION["user"]["host_paused"];
                break;
            case "NOK":
                return $_SESSION["user"]["host_monitored_nok"];
                break;
            case "OK":
                return $_SESSION["user"]["host_monitored_ok"];
                break;
            case "PERCOK":
                return $_SESSION["user"]["host_monitored_ok_perc"];
                break;
            case "PERCNOK":
                return $_SESSION["user"]["host_monitored_nok_perc"];
                break;
            case "SHARED":
                return $_SESSION["user"]["host_shared"];
                break;
            case "NOTSHARED":
                return $_SESSION["user"]["host_not_shared"];
                break;
            case "DELETED":
                return $_SESSION["user"]["host_deleted"];
                break;

            default:
                return false;
                break;
        }
    } //END METHOD getUserHostsCount


    function editHost($id_host) {
        if ($id_host != "new") {
            $sql = "select * from hosts where id_user = " . user::getID() . " and public_token = ?;";
            $stmt = $this->dbhandler->prepare($sql);
            $stmt->execute([$id_host]);
            if($stmt===false) {
                $this->last_error = "Statement execution error";
                return false;
            }
            if ($stmt->rowCount() == 0) {
                $this->last_error = "No records found";
               return false;
            }
            $this->host_in_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            //This means no host in edit so new host
            $this->host_in_edit = false;
        }
        //Return true, operation completed successfully, host prepared for edit....
        return true;

    }


    function createHostCheckScheduleALL () {
        $sql = "select id, minutes from hosts";
        $rs = $this->dbhandler->query($sql);
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $res = $this->createHostCheckSchedule($row["id"],$row["minutes"]);
            if ($res === false) {
                email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_,_APP_DEFAULT_EMAIL_CONTACTS_RECIPIENT_,"CHECK SCHEDULE GENERATION",$this->last_error);
                return false;
            }
        }
        return true;
    }



    function createHostCheckSchedule ($id_host, $interval, $reset_from_ts = false) {

        $future_limit = time() + ($this->checks_schedule_days_future * 24 * 60 *60);
        //convert interval from minutes to seconds...
        $interval_sec = $interval * 60;

        if ($reset_from_ts !== false) {
            $sql = "delete from host_checks_schedule where id_host = $id_host and ts_check > $reset_from_ts;";
            $this->dbhandler->query($sql);
        }


        //retrieve last check scheduled
        $sql = "select max(ts_check) as ts_check from host_checks_schedule where id_host = $id_host;";
        $rs = $this->dbhandler->query($sql);
        $rs = $rs->fetch(PDO::FETCH_ASSOC);
        if ( is_null($rs["ts_check"])) { //NO RECORDS FOUND
            if ($reset_from_ts === false) { //NO RESET ASKED
                //GET THE TIME() AS STARTING POINT
                $last_check_scheduled = time();
            } else { //RESET ASKED
                //GET THE RESET TIMESTAMP AS STARTING POINT...
                $last_check_scheduled = $reset_from_ts;
            }

        } else { //RECORDS FOUND, GET THE LATEST CHECK SCHEDULED
            $last_check_scheduled = $rs["ts_check"];
        }

        //If scheduled checks are already present and enough to reach future limit we return...
        if ($last_check_scheduled >= $future_limit) {
            return true;
        }

        //WE CREATE A MULTIPLE INSERT STATEMENT TO QUICKLY INSERT ROWS
        //OLDER SCRIPT WAS A SINGLE STATEMENT INSERT, VERY SLOWWW!

        //PREPARE SQL
        $sql = "insert into host_checks_schedule (id_host,
                                                    ts_check,
                                                    date_check,
                                                    status,
                                                    daycode,
                                                    interval_minutes)
                                                    values ";



        $sql_arr = array();
        while ($last_check_scheduled < $future_limit) {
            $this->checks_scheduled_generated++;

            //CALCULATE NEXT SCHEDULE
            $last_check_scheduled = $last_check_scheduled + $interval_sec;

            $daycode = date("Ymd",$last_check_scheduled);

            //CREATE SCRIPT WITH VALUES AND PUT IT IN AN ARRAY
            $sql_arr[] = "($id_host, $last_check_scheduled, '".ts_to_date($last_check_scheduled)."', 'PENDING', $daycode, $interval) ";


        }

        //CREATE THE MULTIPLE INSERT STATEMENT
        $sql .= implode(",", $sql_arr);

        //ADD ; AT THE END OF THE STATEMENT
        $sql .= ";";

        $res = $this->dbhandler->query($sql);

        if ($res === false) {
            $this->last_error = "HOST #$id_host, INTERVAL: $interval min., RESET:  "
                                    . ($reset_from_ts===false?"'false'":$reset_from_ts)
                                    . " \n\n" . implode("\n",$this->dbhandler->errorInfo()) . "\n\n$sql";
            return false;
        }
        return true;

    }


    function editedHostGetInfo ($key) {
        switch($key) {
            case "title":
                return $this->host_in_edit["title"];
                break;
            case "checktype":
                return $this->host_in_edit["check_type"];
                break;
            case "interval":
                return $this->host_in_edit["minutes"];
                break;
            case "host":
                return $this->host_in_edit["host"];
                break;
            case "port":
                return $this->host_in_edit["port"];
                break;
            case "public_token":
                return $this->host_in_edit["public_token"];
                break;
            case "enabled":
                return $this->host_in_edit["enabled"];
                break;
            case "public":
                return $this->host_in_edit["public"];
                break;
            case "homepage":
                return $this->host_in_edit["homepage"];
                break;
            case "keyword":
                return $this->host_in_edit["keyword"];
                break;
            case "contacts":
                if ($this->host_in_edit === false) { //NEW HOST .... ALL CONTACTS SELECTED
                    $sql = "SELECT uc.*
                            FROM user_contacts uc
                            WHERE uc.id_user = " . user::getID() . "
                                and uc.validated = 1
                            order by contact_type_id, contact;";
                } else { //HOST IN EDIT
                    //Select all contacts with a left join to populate fields for the contacts associated to the host
                    $sql = "SELECT uc.*, hc.*
                            FROM user_contacts uc
                              LEFT JOIN (SELECT id_contact
                                            FROM host_contacts
                                              WHERE id_host = " . $this->host_in_edit["id"] . ") hc
                            ON uc.id = hc.id_contact
                            WHERE uc.id_user = " . user::getID() . "
                                and uc.validated = 1
                            order by contact_type_id, contact;";
                } //END IF
                $stmt = $this->dbhandler->prepare($sql);
                $stmt->execute();
                if ($stmt === false) {
                    return false;
                } else {
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                break; //END CONTACTS CASE

        } //END SWITCH
        return false;
    }


    function getCheckTypes ($id_to_include = "") {
        $sql = "select * from check_types where enabled = 1 or id = ? order by id;";
        $stmt = $this->dbhandler->prepare($sql);
        $stmt->execute([$id_to_include]);
        if ($stmt === false) {
            $this->last_error = "Error on statement execution while retrieving check types";
            return false;
        }
        //Return array whith check types....
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getCommonPorts () {
        $sql = "select * from common_ports where enabled = 1 order by port;";
        $stmt = $this->dbhandler->prepare($sql);
        $stmt->execute();
        if ($stmt === false) {
            $this->last_error = "Error on statement execution while retrieving common ports";
            return false;
        }
        //Return array whith check types....
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function updateUserHostNumber () {


        $sql = "select  sum(  if(enabled=1 and delete_queue is  null,1,0) ) as host_monitored,
                        sum(  if(enabled=1 and check_result <> 'OK' and delete_queue is null,1,0)  ) as host_monitored_nok,
                        sum(  if(public=1 and delete_queue is null,1,0)  ) as host_shared,
                        sum(  if(delete_queue is not null,1,0)  ) as host_deleted,
                        sum(1) as host_total
                 from hosts where id_user=" . user::getID() . ";";

        $rs = $this->dbhandler->query($sql);
        $rs = $rs->fetch(PDO::FETCH_ASSOC);
        $_SESSION["user"]["host_monitored"] = $rs["host_monitored"];
        $_SESSION["user"]["host_monitored_nok"] = $rs["host_monitored_nok"];
        $_SESSION["user"]["host_shared"] = $rs["host_shared"];
        $_SESSION["user"]["host_deleted"] = $rs["host_deleted"];
        $_SESSION["user"]["host_total"] = $rs["host_total"];

        $_SESSION["user"]["host_paused"] = $_SESSION["user"]["host_total"] - $_SESSION["user"]["host_monitored"]-$_SESSION["user"]["host_deleted"];
        $_SESSION["user"]["host_monitored_ok"] = $_SESSION["user"]["host_monitored"] - $_SESSION["user"]["host_monitored_nok"];
        $_SESSION["user"]["host_not_shared"] = $_SESSION["user"]["host_total"] - $_SESSION["user"]["host_shared"] - $_SESSION["user"]["host_deleted"];

        //PERCENTAGE NOK
        if ($_SESSION["user"]["host_monitored"] > 0) {
            $_SESSION["user"]["host_monitored_nok_perc"] = round((($_SESSION["user"]["host_monitored_nok"] / $_SESSION["user"]["host_monitored"]) * 100),2);
            $_SESSION["user"]["host_monitored_ok_perc"] = 100-$_SESSION["user"]["host_monitored_nok_perc"];
        } else {
            $_SESSION["user"]["host_monitored_nok_perc"] = -1;
            $_SESSION["user"]["host_monitored_ok_perc"]= -1;
        }

    } //END METHOD UPDATEUSERHOSTNUMBER

    function getUserHostsList ($filter = "ALL",$sort="TITLE") {


        $sql_inj_filter = "  ";
        $sql_inj_sort = "  ";



        //DEPRECATED - SORT AND FILTER MADE ON BROWSER SIDE....
        /*
        if ($filter=="") {
            $filter = "ALL";
        }

        //FILTER SQL GENERATION
        switch ($filter) {
            case "ALL":
                $sql_inj_filter = "  ";
                break;
            case "MONITORED":
                $sql_inj_filter = " and enabled = 1 and delete_queue is null ";
                break;
            case "PAUSED":
                $sql_inj_filter = " and enabled = 0 and delete_queue is null ";
                break;
            case "NOK":
                $sql_inj_filter = " and enabled = 1 and check_result = 'NOK' and delete_queue is null ";
                break;
            case "OK":
                $sql_inj_filter = " and enabled= 1 and check_result = 'OK' and delete_queue is null ";
                break;
            case "SHARED":
                $sql_inj_filter = "  and public = 1 and delete_queue is null ";
                break;
            case "NOTSHARED":
                $sql_inj_filter = "  and public = 0 and delete_queue is null ";
                break;
            case "DELETED":
                $sql_inj_filter = " and delete_queue is not null ";
                break;
            default:
                $sql_inj_filter = "";
                break;
        }

        //ORDER SQL GENERATION
        switch ($sort) {
            case "TITLE":
                $sql_inj_sort = " order by title ";
                break;
            case "PUBLIC":
                $sql_inj_sort = " order by public ";
                break;
            case "CHECKTYPE":
                $sql_inj_sort = " order by check_type ";
                break;
            case "HOST":
                $sql_inj_sort = " order by host ";
                break;
            case "PORT":
                $sql_inj_sort = " order by port ";
                break;
            case "ENABLED":
                $sql_inj_sort = " order by enabled ";
                break;
            case "INTERVAL":
                $sql_inj_sort = " order by minutes ";
                break;
            default:
                $sql_inj_sort = " order by title ";
                break;
        } //END SWITCH SORT

        */

        //SQL GENERATION
        $sql = "select * from hosts
          where id_user = " . user::getID() . "
          $sql_inj_filter
          $sql_inj_sort
          ;";

        return $this->dbhandler->query($sql);
    } //END METHOD getUserHostsList


    //RECEIVE AN OBJECT WITH 2 ELEMENTS, flag (str) and public_tokens (array)
    function bulkAction (&$data) {

        $sql = "---";
        $event_type = "";

        if (!isset($data["public_tokens"]) or count($data["public_tokens"]) == 0) {
            return true;
        };

        //Create SQL string using IN clause ....
        $bind_question_marks = array_fill(0,count($data["public_tokens"]),"?");
        $bind_question_marks = implode(",",$bind_question_marks);

        //FLAG PAUSE MONITORING
        if ($data["flag"]=="PAUSE") {
            $sql = "update hosts set enabled=0 where id_user = " . user::getID() . "
                    and delete_queue is null and public_token in ($bind_question_marks);";
            $event_type ="PAUSED";
        }

        //FLAG RESUME MONITORING
        if ($data["flag"]=="RESUME") {
            $sql = "update hosts set enabled=1 where id_user = " . user::getID() . "
                and delete_queue is null and public_token in ($bind_question_marks);";
            $event_type ="RESUMED";
        }

        //DELETE FLAG - MOVE TO TRASH / DELETE QUEUE
        if ($data["flag"]=="DELETE") {
            $sql = "update hosts set delete_queue = " . time() . ", enabled = 0, public = 0
                            where id_user = " . user::getID() . "
                and public_token in ($bind_question_marks);";
            $event_type ="DELETED";
        }


        //UNDELETE FLAG - RETRIEVE FROM TRASH - HOSTS ARE NOT ENABLED, MONITORING IS STILL PAUSED...
        if ($data["flag"]=="UNDELETE") {
            $sql = "update hosts set delete_queue = NULL , enabled = 0 where id_user = " . user::getID() . "
                and delete_queue is not null and public_token in ($bind_question_marks);";
            $event_type ="UNDELETE";
        }


        //SHARE FLAG
        if ($data["flag"]=="SHARE") {
            $sql = "update hosts set public = 1 where id_user = " . user::getID() . "
                    and enabled = 1 and delete_queue is null and public_token in ($bind_question_marks);";
        }

        //UNSHARE FLAG
        if ($data["flag"]=="UNSHARE") {
            $sql = "update hosts set public = 0 where id_user = " . user::getID() . "
                    and delete_queue is null and public_token in ($bind_question_marks);";
        }

        //Prepare the statement
        $stmt = $this->dbhandler->prepare($sql);
        //execute the statement using public tokens array to bind parameters...
        if ($stmt->execute($data["public_tokens"]) !== true) {
            return $stmt->errorInfo();
        }

        //record Host event
        if ($event_type != "") {
            if ($this->logHostEvent($data["public_tokens"],$event_type) === false) {
                return "Error during logging hosts event";
            }
        }



        $this->updateUserHostNumber();
        return true;




    }


    static function getLatestResultsArray ($latest_result_string, $points=50) {
        if ($latest_result_string == "" or is_null($latest_result_string)) {
            //IF NO RESULTS PRESENT RETURN FALSE
            return false;
        }

        //EXPLODE THE STRING TO ARRAY
        $arr_results = explode("@",$latest_result_string);

        if (count($arr_results) < $points) {
            $points = count($arr_results);
        } else {
            //KEEP ONLY THE n POINTS REQUESTED
            $arr_results = array_slice($arr_results,-($points));
        }

        return $arr_results;
    }

    static function getLatestReultsArrayOnOff ($latest_result_string, $points=50) {

        if ($latest_result_string == "" or is_null($latest_result_string)) {
            //IF NO RESULTS PRESENT RETURN FALSE
            return false;
        }

        $arr_results = self::getLatestResultsArray($latest_result_string,$points);

        foreach ($arr_results as $key=>$element_value) {
            $element_value == 0 ? $arr_results[$key] = -1:$arr_results[$key]=1;
        }

        return $arr_results;

    } //END METHOD



    public function checkEmailSyntax ($email) {

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        return true;
    }


    public function logHostEvent ($public_token, $event) {

        $now = time();

        if (is_array($public_token)) {
            //PUBLIC TOKEN IS AN ARRAY

            //create the multiple records insert statement
            $sql = "insert into host_logs (public_token,ts,event,machine_id, db_id) values ";

            $arr_multiple_records_sql = array();
            for ($i=0;$i<count($public_token);$i++) {
                $arr_multiple_records_sql[] = "(?,$now,'$event','"._MACHINE_ID_."', @db_id)";
            }

            $sql .= implode(",",$arr_multiple_records_sql) . ";";

            //prepare the statement
            $stmt = $this->dbhandler->prepare($sql);
            //execute the statement
            $ret = $stmt->execute($public_token);

        } else {
            //PUBLIC TOKEN IS A STRING
            $sql = "insert into host_logs (public_token,ts,event,machine_id, db_id) values (:public_token,:ts,:event,:machine_id, @db_id);";
            $arr_query["public_token"] = $public_token;
            $arr_query["ts"] = $now;
            $arr_query["event"] = $event;
            $arr_query["machine_id"] = _MACHINE_ID_;

            $stmt = $this->dbhandler->prepare($sql);
            $ret = $stmt->execute($arr_query);
        }

        if ($ret===false) {
            $this->last_error = "Log host event failed:\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;


    }


    public function ts_to_date ($ts=0) {
        if ($ts == 0) {
            $ts = time();
        }
        return date("d M Y H:i:s", $ts);

    }

    function generateRandomPublicToken($length=10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        $random_max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $random_max)];
        }
        return $randomString;
    }


    function addSubscription($validation_token) {

        if ($validation_token===false) {
            $this->last_error =  "Activation code not found";
            return false;
        }

        //ACTIVATE SUBSCRIPTION
        $sql = "UPDATE host_subscriptions set validated=1,
                                        validated_ts=unix_timestamp()
                      where validation_token = :vt
                            and added_ts > unix_timestamp()-7*24*60*60
                              and validated = 0;";


        $stmt = $this->dbhandler->prepare($sql);

        $ret = $stmt->execute(["vt"=>$validation_token]);

        if ($ret === false) {
            $this->last_error = "Error during subscription process";
            return false;
        }

        if ($stmt->rowCount() == 0) {
            $this->last_error = "Subscription already activated or not found";
            return false;
        }

        return true;
    }

    function cancelSubscription($validation_token) {

        if ($validation_token===false) {
            $this->last_error =  "Activation code not found";
            return false;
        }

        //CANCEL SUBSCRIPTION
        $sql = "UPDATE host_subscriptions set validated=-1,
                                        validated_ts=unix_timestamp()
                      where validation_token = :vt
                            and validated > -1;";


        $stmt = $this->dbhandler->prepare($sql);

        $ret = $stmt->execute(["vt"=>$validation_token]);

        if ($ret === false) {
            $this->last_error = "Unable to remove subscription, internal error.";
            return false;
        }

        if ($stmt->rowCount() == 0) {
            $this->last_error = "Subscription already removed or not found";
            return false;
        }

        return true;

    }

} //END CLASS HOST_MANAGER