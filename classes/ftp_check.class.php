<?php

class ftp_check implements monitor_interface {

    private $url = false;
    private $port = 21;
    public $last_error;
    public $details = array();
    private $requests = 0;
    private $success = 0;
    private $ip = "0.0.0.0";
    private $resultCode = "NOK";
    private $time_spent = 0;
    public $last_error_code = "-";



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

        $this->requests++;
        set_time_limit(120);
        $this->details[] = "Opening connection...";
        $boot_time = microtime(true);
        @$f = fsockopen($this->url, $this->port,$errno,$errstr,20) ;
        $this->time_spent = round((microtime(true)-$boot_time)*1000,3);

        if ($f !== false) { //SOCKET CONNECTION OPEN
            stream_set_timeout($f,5);
            $res = fread($f, 1024) ;
            if (strlen($res) > 0 && strpos($res, '220') === 0) {
                $this->success++;
                $this->resultCode = "OK";
                $this->details[] = "Server Ready!\n" . nl2br($res);
                return true;
            }
            else {
                $this->last_error = "Error (" . $this->url . "): " . $res . "<br>Err.n. " . $errno . "<br>Err.Descr. " . $errstr ;
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
