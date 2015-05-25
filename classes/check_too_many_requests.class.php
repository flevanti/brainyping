<?php

class check_too_many_requests {
    private $dbhandler = false;
    private $proxy_address = "";
    private $client_address = "";
    private $limit_timerange = 15;
    public $last_error = "";
    public $last_error_tech = "";
    private $ajax_connection = false;
    public $limits_exceeded = false;

    function __construct($dbhandler) {
        $this->dbhandler = $dbhandler;
    }

    function check($stop_if_too_many = false) {
        $current_timerange = $this->getCurrentTimeRange();
        $this->setRemoteIP();
        $sql = "select * from requests_by_ip
                    where remote_ip=:ip
                            and timerange = $current_timerange;";
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Error while preparing statement for IP connections";
            $this->last_error_tech = "IP: " . $this->client_address . "  TIMERANGE: " . $current_timerange . "\n"
                . implode("\n", $this->dbhandler->errorInfo());

            return false;
        }
        $ret = $stmt->execute(["ip" => $this->client_address]);
        if ($ret === false) {
            $this->last_error = "Error while retrieving IP connections";
            $this->last_error_tech = "IP: " . $this->client_address . "  TIMERANGE: " . $current_timerange . "\n"
                . implode("\n", $stmt->errorInfo());

            return false;
        }
        if ($stmt->rowCount() == 0) {
            //FIRST CONNECTION!
            $ret = $this->addConnection($this->client_address, $current_timerange, $this->ajax_connection, true);
            if ($ret === true) {
                return true;
            }

            return false;
        }
        //ADD CURRENT CONNECTION TO ALREADY EXISTING RECORD
        //WE DO IT NOW BECAUSE WE WANT TO LOG HOW MANY CONNECTIONS WE RECEIVED EVEN IF LIMITS ARE EXCEEDED
        $ret = $this->addConnection($this->client_address, $current_timerange, $this->ajax_connection, false);
        if ($ret === false) {
            return false;
        }
        //HERE WE ARE.... PERFORM THE REAL CHECK.....
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (($row["requests"] + $row["requests_ajax"]) > $this->limit_timerange) {
            $this->last_error = "Connection limits!";
            $this->limits_exceeded = true;
            if ($this->limits_exceeded === true and $stop_if_too_many === true) {
                $this->stopIt();
            }

            return true;
        }

        return true;
    }

    private function stopIt() {
        header("HTTP/1.0 503 Service Unavailable");
        require_once $_SESSION["config"]["_ABS_DOC_ROOT_"] . '503_too_many_requests.php';
        exit;
    }

    function addConnection($ip, $timerange, $ajax_connection, $first_time) {
        if ($first_time === true) {
            $requests = 0;
            $requests_ajax = 0;
            $ajax_connection === true ? $requests_ajax++ : $requests++;
            $sql = "INSERT INTO requests_by_ip (remote_ip
                                                , timerange
                                                , requests
                                                , requests_ajax)
                                                VALUES (
                                                :ip
                                                ,:timerange
                                                ,:requests
                                                ,:requests_ajax);";
            $stmt = $this->dbhandler->prepare($sql);
            if ($stmt === false) {
                $this->last_error = "Error while preparing statement for saving IP connections (FIRST RECORD)";
                $this->last_error_tech = "IP: " . $ip . "  TIMERANGE: " . $timerange . "\n"
                    . implode("\n", $this->dbhandler->errorInfo());

                return false;
            }
            $ret = $stmt->execute(["ip" => $ip, "timerange" => $timerange, "requests" => $requests, "requests_ajax" => $requests_ajax]);
            if ($ret === false) {
                $this->last_error = "Error while saving IP connections (FIRST CONNECTION)";
                $this->last_error_tech = "IP: " . $ip . "  TIMERANGE: " . $timerange . "\n"
                    . implode("\n", $stmt->errorInfo());

                return false;
            }

            return true;
        } else {
            $requests = 0;
            $requests_ajax = 0;
            $ajax_connection === true ? $requests_ajax++ : $requests++;
            $sql = "update requests_by_ip set requests=requests+$requests
                                              , requests_ajax=requests_ajax+$requests_ajax
                        where remote_ip =:ip and timerange=:timerange;";
            $stmt = $this->dbhandler->prepare($sql);
            if ($stmt === false) {
                $this->last_error = "Error while preparing statement for updating IP connections";
                $this->last_error_tech = "IP: " . $ip . "  TIMERANGE: " . $timerange . "\n"
                    . implode("\n", $this->dbhandler->errorInfo());

                return false;
            }
            $ret = $stmt->execute(["ip" => $ip, "timerange" => $timerange]);
            if ($ret === false) {
                $this->last_error = "Error while updating IP connections";
                $this->last_error_tech = "IP: " . $ip . "  TIMERANGE: " . $timerange . "\n"
                    . implode("\n", $stmt->errorInfo());

                return false;
            }

            return true;
        }
    }

    function getCurrentTimeRange() {
        return intval(time() / 10) . "0";
    }

    function setConnectionFromAjax($flag = true) {
        $this->ajax_connection = $flag;
    }

    private function setRemoteIP() {
        //CHECK IF CONNECTION IS FORWARDED BY A PROXY....
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            //PROXY FOUND
            $this->proxy_address = $_SERVER["REMOTE_ADDR"];
            $this->client_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            //PROXY NOT FOUND
            $this->proxy_address = "";
            $this->client_address = $_SERVER["REMOTE_ADDR"];
        }

        return true;
    }
}