<?php

class smtp_check implements monitor_interface {

    private $url = false;
    private $port = 21;
    public $last_error;
    public $last_error_code;
    public $details = array();
    private $requests = 0;
    private $success = 0;
    private $ip = "0.0.0.0";
    private $resultCode = "NOK";
    private $time_spent = 0;


    function __construct () {
        $this->details[] = "Object created, waiting for domain and port";
    }


    function setDomain($url) {

        $this->url = $url;

        $this->details[] = "Domain set!";
        return true;

    }


    function setPort ($port) {
        $this->port = $port;
        $this->details[] = "port set!";
        return true;
    }

    function go() {

        $boot_time = microtime(true);
        $res = $this->goForReal();
        $this->time_spent = round((microtime(true) - $boot_time)*1000,2);

        return $res;

    }


    private function goForReal() {

        $this->requests++;
        set_time_limit(120);
        $this->details[] = "Opening connection...";


        @$f = fsockopen($this->url, $this->port,$errno,$errstr,30) ;


        if ($f !== false) { //SOCKET CONNECTION OPEN
            $this->details[] = "Connection open";

            stream_set_timeout($f, 5);

            //CHECK INITIAL CONNECTION CODE
            $res = fread($f, 1024);
            if (strlen($res) > 0 && strpos($res, '220') === 0) {
                //SERER IS READY!
                $this->details[] = "Server is ready";
            } else {
                $this->details[] = "Server reply empty or not 220 code";
                $this->details[] = "Reply: \n" . $res;
                $this->last_error = "Server reply empty or not 220 code: $res";
                return false;
            } //END IF INITIAL CONNECTION CODE

            //SENDING HELO
            $this->details[] = "Sending greetings ... HELO";
            $res = fwrite($f, "HELO brainyping.com\r\n");


            if ($res !== false) {
                $this->details[] = "HELO sent ($res)";
            } else {
                $this->details[] = "HELO failed";
                $this->last_error = "HELO command failed";
                return false;
            }

            $res = fread($f, 1024);

            if (strlen($res) > 0 && strpos($res, '250') === 0) {
                $this->details[] = "Server is pleased to meet us!";
                $this->details[] = "Reply: $res";
                $this->success++;
                $this->resultCode = "OK";
                return true;
            } else {
                $this->details[] = "Could not find 250 code: \n" . $res;
                $this->last_error = "HELO command reply was not OK (no 250 code)";
                return false;
            }

        } else {
            $this->details[] = "Unable to open socket connection";
            $this->details[] = "Err. n.: " . $errno;
            $this->details[] = "Err. description: " . $errstr;
            $this->last_error = "Unable to open socket connection";
            return false;

        }



    }


    function getAverage() {
        return $this->time_spent;
    }


    function getWorst(){
        return $this->time_spent;
    }

    function getBest(){
        return $this->time_spent;
    }

    function getFailed(){
        return $this->requests - $this->success;
    }

    function getSuccess(){
        return $this->success;
    }
    function getRequests(){
        return $this->requests;
    }
    function getResultCode(){
        return $this->resultCode;
    }
    function getResultPerc(){
        if ($this->requests == 0) {
            return 0;
        }
        return ($this->success / $this->requests) * 100;


    }
    function getIP(){
        return $this->ip;
    }
    function getDetails(){
        return $this->details;
    }

    function getDetailsAsString(){
        return multi_implode::go("\n",$this->details);
    }

    function getLastError() {
        return $this->last_error;
    }
    function getLastErrorCode() {
        return $this->last_error_code;
    }
}
