<?php

class web_keyword_check implements monitor_interface {

    private $url = false;
    private $port = 21;
    public $last_error;
    public $last_error_code = "-";
    public $details = array();
    private $requests = 0;
    private $success = 0;
    private $ip = "0.0.0.0";
    private $resultCode = "NOK";
    private $time_spent = 0;

    function __construct() {
    }

    function setDomain($url) {
        //Check if the domain/url has a double slashes
        $double_slash = strpos($url, "//");
        //if not we add them so the url will be parsed as an host not path
        if ($double_slash === false) {
            $url = "//" . $url;
        }
        $this->url = $url;

        return true;
    }

    protected function checkUrl($url) {
        $arr_parsed_url = parse_url($url);
        var_dump($arr_parsed_url);
        if ($arr_parsed_url === false) {
            $this->last_error = "Unable to parse URL (false)";
            $this->details[] = $this->last_error;

            return false;
        }
        if (!isset($arr_parsed_url["host"])) {
            $this->last_error = "Unable to parse URL (Host not found)";
            $this->details[] = $this->last_error;

            return false;
        }
        if (count($arr_parsed_url) > 1) {
            $this->last_error = "Host not valid (too much info!)";
            $this->details[] = $this->last_error;

            return false;
        }
        if (filter_var(gethostbyname($arr_parsed_url["host"]), FILTER_VALIDATE_IP) === false) {
            $this->last_error = "Host not valid (Unable to resolve it)";
            $this->details[] = $this->last_error;

            return false;
        }

        return $arr_parsed_url["host"];
    }

    function setPort($port) {
        if (is_numeric($port) and $port >= 1 and $port <= 65535) {
            $this->port = $port;

            return true;
        }

        return false;
    }

    function go() {
        $this->url = $this->checkUrl($this->url);
        if ($this->url === false) {
            return false;
        }
        $this->requests++;
        set_time_limit(120);
        $boot_time = microtime(true);
        @$f = fsockopen($this->url, $this->port, $errno, $errstr, 20);
        $this->time_spent = round((microtime(true) - $boot_time) * 1000, 3);
        if ($f !== false) { //SOCKET CONNECTION OPEN
            stream_set_timeout($f, 5);
            $res = fread($f, 1024);
            if (strlen($res) > 0 && strpos($res, '220') === 0) {
                $this->success++;
                $this->resultCode = "OK";
                $this->details[] = "Server Ready!\n" . nl2br($res);

                return true;
            } else {
                $this->last_error = "Error (" . $this->url . "): " . $res . "<br>Err.n. " . $errno . "<br>Err.Descr. " . $errstr;
                $this->details[] = $this->last_error;

                return false;
            }
        } else { //SOCKET CONNECTION NOT OPENED
            $this->last_error = "Unable to initilize socket connection!\nErr.n. $errno Err.Descr. $errstr";
            $this->details[] = $this->last_error;

            return false;
        } //END IF
    }

    function getAverage() {
        return $this->time_spent;
    }

    function getWorst() {
        return $this->time_spent;
    }

    function getBest() {
        return $this->time_spent;
    }

    function getFailed() {
        return $this->requests - $this->success;
    }

    function getSuccess() {
        return $this->success;
    }

    function getRequests() {
        return $this->requests;
    }

    function getResultCode() {
        return $this->resultCode;
    }

    function getResultPerc() {
        if ($this->requests == 0) {
            return 0;
        }

        return ($this->success / $this->requests) * 100;
    }

    function getIP() {
        return $this->ip;
    }

    function getDetails() {
    }

    function getDetailsAsString() {
    }

    function getLastError() {
        return $this->last_error;
    }

    function getLastErrorCode() {
        return $this->last_error_code;
    }
}
