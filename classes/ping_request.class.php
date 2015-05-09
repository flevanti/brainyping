<?php

class ping_request implements monitor_interface {

        private $best = 999999;
        private $average = 0;
        private $worst = 0;

        private $success = 0;
        private $failed = 0;
        private $result_request_details = [];

        private $valid_host = "";
        private $valid_IP = "";

        public $last_error = "";
        public $last_error_code = "-";

        private $details = array();

        private $reserved_ports = array(9=>"WOL",21=>"FTP",22=>"SSH",23=>"Telnet",25=>"SMTP",80=>"HTTP",110=>"POP3",143=>"IMAP",443=>"HTTPS",465=>"SMTPS");

        //This flag is used if we want to return OK result code on POK condition
        private $POKisOK = false;

        private $host, $port,$pingRequests,$pingRequestWait,$pingRequestTimeout;


        public function  __construct ($host="",$port="80",$pingRequests=3,$pingRequestWait=1,$pingRequestTimeout=3) {
            $this->setHost($host);
            $this->setPort($port);
            $this->setRequests($pingRequests);
            $this->setWait($pingRequestWait);
            $this->setTimeout($pingRequestTimeout);


        }

        function setHost ($host) {
            $this->host = $host;
        }

        function setPort ($port) {
            $this->port = $port;
        }

        function setRequests($pingRequests) {
            $this->pingRequests = $pingRequests;
        }
        function setWait ($wait) {
            $this->pingRequestWait = $wait;
        }

        function setTimeout ($timeout) {
            $this->pingRequestTimeout = $timeout;
        }


        function POKisOK ($flag) {
            $this->POKisOK = $flag;
        }

        function getDetails () {
            return $this->details;
        }

        function getDetailsAsString () {
            return multi_implode::go("\n",$this->details);
        }

        //PERFORM PING!
        function go() {
            $errCode = "";
            $errStr = "";
            for ($i=0; $i < $this->pingRequests ; $i++) {
                $t = microtime(true);



                //WE SUPPRESS THE CALL WITH @ - IT IS USUALLY NOT  GOOD BUT IN THIS CASE
                //A WARNING LEVEL ERROR MEANS TIMEOUT / NO CONNECTION
                //IF SOMETHING IS NOT GOING WELL, RETURN IS FALSE AND WE MANAGE IT...

                @$fp = fsockopen($this->host ,$this->port,$errCode,$errStr,$this->pingRequestTimeout);

                restore_error_handler();
                if ($fp !== false) {
                    $t = (microtime(true) - $t) * 1000;
                    $this->average += $t;

                    //CHECK THE WORST TIME
                    if ($t > $this->worst) {
                        $this->worst = $t;
                    }
                    //CHECK THE BEST TIME
                    if ($t < $this->best) {
                        $this->best = $t;
                    }

                    $this->result_request_details[$i]["result"] = true;
                    $this->result_request_details[$i]["response_time"] = $t;
                    fclose($fp);
                    $this->success++;

                } else {
                    $this->result_request_details[$i]["result"] = false;
                    $this->result_request_details[$i]["errCode"] = $errCode;
                    $this->result_request_details[$i]["errStr"] = $errStr;
                    $this->failed ++;
                } //END $FP !== false

                sleep($this->pingRequestWait);
            } //END FOR CYCLE - PING REQUESTS ENDED

            if ($this->success > 0) {
                $this->average = ($this->average / $this->success);
            }

            return true;

        } //END GO() METHOD


    function getAverage () {
        return round($this->average,2);
    }

    function getBest () {
        return round($this->best,2);
    }

    function getWorst () {
        return round($this->worst,2);
    }

    function getFailed () {
        return $this->failed;
    }

    function getSuccess () {
        return $this->success;
    }

    function getRequests () {
        return $this->pingRequests;
    }
    function getPOKisOK () {
        return $this->POKisOK;
    }

    function getRequestsMade () {

    }

    function getResultCode () {

        if ($this->success == 0) {
            //ALL REQUESTS FAILED
            return "NOK";
        } elseif ($this->failed == 0) {
            //ALL REQUESTS OK
            return "OK";
        } else {
            //REQUESTS PARTIALLY OK
            if ($this->POKisOK == true) {
                //if POKisOK is true we return OK
                return "OK";
            } else {
                //if POKisOK is not true we return POK
                return "POK";
            }
        }
    } //END METHOD GetResultCode

    function getResultPerc () {
        if ($this->success > 0) {
            return round((($this->success/$this->pingRequests)*100),2);
        } else {
            return 0;
        }
    }


    function getRequestDetail($i) {
        if (array_key_exists($i,$this->result_request_details) ) {
            return $this->result_request_details[$i];
        } else {
            return false;
        }
    }

    function getIP () {
        //CONVERT HOST TO IP
        return gethostbyname($this->host);

    }

    function checkValidHost () {
        if ($this->valid_host=="") {
            if ($this->host == gethostbyname($this->host)) {
                $this->valid_host = false;
                if (filter_var($this->host,FILTER_VALIDATE_IP)) {
                    $this->valid_IP = true;
                } else {
                    $this->valid_IP = false;
                }
            } else {
                $this->valid_host = true;
                $this->valid_IP = false;
            }
        }

        if ($this->valid_host === true or $this->valid_IP===true) {
            return true;
        }
        return false;
    }

    function checkValidPort () {
        if ($this->port < 1 or $this->port > 65535) {
            return false;
        } else {
            return true;
        }
    }

    function isItDomainOrIP () {
        if ($this->valid_host===true) {
            return "DOMAIN";
        }
        if ($this->valid_IP===true) {
            return "IP";
        }
        return false;
    }

    function getPortService () {
        if (array_key_exists($this->port,$this->reserved_ports)) {
            return $this->reserved_ports[$this->port];
        }
        return false;
    }

    function getLastError() {
        return $this->last_error;
    }
    function getLastErrorCode() {
        return $this->last_error_code;
    }

} //END CLASS PING REQUEST