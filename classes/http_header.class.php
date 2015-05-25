<?php

class http_header implements monitor_interface {
    private $url = false;
    private $url_port = false;
    private $headers = false;
    private $time_spent = false;
    private $timeout = 10;
    public $last_error = "";
    public $last_error_code = "-";
    private $requests = 1;
    private $success = 0;
    private $resultCode = "NOK";
    private $time_result = 0;
    private $details = array();
    private $remoteQueryAddress;
    public $this_is_a_remote_call = false;
    private $cookie_path = "";
    private $cookie_name = "default_cookie.txt";
    private $headers_from_remote = false;

    //Set the flag for the mode
    //HEADER Look for a 200 header code
    //KEYWORD Look for a keyword in the source
    private $object_mode = array("HEADER" => 1, "KEYWORD" => 0);
    private $keyword = false;

    function __construct() {
        $this->remoteQueryAddress = $_SESSION["config"]["_REMOTE_PING_URL_"];
    }


    //THIS method is used to find a keyword in the page source instead of configuring/calling objects method manually
    function findKeyword($url, $port, $keyword) {
        $this->setObjectMode("KEYWORD");
        $this->keyword = $keyword;

        return $this->getHeaders($url, $port);
    }

    function getHeaders($url, $port = false) {
        $this->cookie_path = $this->getCookiesFolder();
        if (!is_dir($this->cookie_path) or $this->cookie_path == "") {
            $this->resultCode = "NOK";
            $this->last_error = "Cookies path does not exist! (" . $this->cookie_path . ")";
            $this->last_error_code = "COOKIEPATH";
            $this->details[] = $this->last_error;

            return false;
        }
        $this->cookie_name = date("YmdHis") . "_" . md5(microtime(true)) . "_cookie.txt";
        $this->setURL($url, $port);
        if ($this->url === false) {
            $this->resultCode = "NOK";
            $this->last_error = "URL parsing error during GetHeaders";
            $this->last_error_code = "URLPARSING";
            $this->details[] = $this->last_error;

            return false;
        }
        ini_set('default_socket_timeout', $this->timeout);
        $time_spent = microtime(true);
        //choose the way to retrieve information...
        switch ($this->requests) {
            case 1:
                //In the first run we usually do not get source code (it's faster and lighter)
                //This is not possible if we are looking for a keyword (so we need source!)..
                //So if mode=KEYWORD we retrieve source....
                $get_source = ($this->object_mode["KEYWORD"] == 1 ? true : false);
                //first run... default way... ONLY HEADERS (ALSO SOURCE IF KEYWORD MODE)
                $this->headers = $this->headersQuery($this->url, $this->url_port, $get_source);
                $this->headers_from_remote = false;
                break;
            case 2:
                //second run...... ... let's wait a little bit... AND THEN HEADERS AND BODY
                sleep(2);
                $this->headers = $this->headersQuery($this->url, $this->url_port, true);
                $this->headers_from_remote = false;
                break;
            case 3:
                //third run... remote way (query another server to perform the same operation and retrieve results)
                //Headers from remote also contains the last error / last error code
                $this->headers = $this->headersQueryRemote($this->url, $this->url_port);
                $this->headers_from_remote = true;
                break;
        }
        $this->time_spent = round((microtime(true) - $time_spent) * 1000, 2);
        if ($this->headers === false) {
            $this->resultCode = "NOK";
            $this->last_error = "Request # " . $this->requests . " HeadersQuery returned false\n" . $this->last_error;
            //last error code should be already populated by the call.... no need to populate here...
            $this->details[] = $this->last_error;
            //WE DO NOT RETURN BECAUSE WA WANT TO COMPLETE THE FUNCTION AND (IF NEEDED) PERFORM A SECOND CALL
            //return false;
        } else {
            //HEADERS REQUEST RETURNED SOMETHING.....
            if ($this->object_mode["HEADER"] == 1) {
                if ($this->headers["last_code_found"] == 200) {
                    $this->resultCode = "OK";
                    $this->success++;
                    $this->time_result = $this->time_spent;
                    $this->details[] = "Request # " . $this->requests . "\n\n";
                    $this->details[] = "Last code found: " . $this->headers["last_code_found"] . "\n\n";

                    //$this->details[] = "RAW:\n" . multi_implode::go("-",$this->headers);
                    return true;
                } else {
                    $this->resultCode = "NOK";
                    $this->time_result = 0;
                    $this->details[] = "Request # " . $this->requests . "\n\n";
                    $this->details[] = "Last code found: " . $this->headers["last_code_found"] . "\n\n";
                    $this->last_error = "Last code found: " . $this->headers["last_code_found"] . " REQUEST # " . $this->requests;
                    $this->last_error_code = "NORMAL";
                    //$this->details[] = "RAW:\n" . multi_implode::go("\n",$this->headers);
                }//END IF
            } //END IF MODE=HEADER
            if ($this->object_mode["KEYWORD"] == 1) {
                if ($this->findInSouce($this->keyword)) {
                    $this->details[] = "Request # " . $this->requests . "\n\n";
                    $this->details[] = "Keyword found";
                    $this->resultCode = "OK";
                    $this->success++;
                    $this->time_result = $this->time_spent;

                    return true;
                } else {
                    $this->details[] = "Request " . $this->requests . " Keyword NOT found";
                    $this->last_error_code = "NORMAL";
                }
            } //END IF MODE = KEYWORD
            //WE DO NOT RETURN BECAUSE WA WANT TO COMPLETE THE FUNCTION AND (IF NEEDED) PERFORM A SECOND CALL
            //return false;
        } //END IF HEADERS RETURNED SOMETHING....
        //PERFORM ANOTHER TRY...
        //WHEN WORKING LOCALLY WE TRY 3 TIMES (The 3rd IS THE REMOTE CALL...)...
        //WHEN ON REMOTE WE DO NOT CALL A REMOTE CALL AGAIN (WE WOULD ENTER A LOOP...)
        //
        //SO IF REMOTE CALL FLAG = true WE DO NOT PERFORM REQUEST #3
        //REQUEST #3 IS THE REMOTE CALL....
        while ($this->resultCode != "OK"
            and (
                ($this->requests < 3 and $this->this_is_a_remote_call === false)
                or ($this->requests < 2 and $this->this_is_a_remote_call === true))
        ) { //START THE WHILE....
            $this->requests++;
            $this->getHeaders($url, $port);
        }

        return true;
    }

    function getCookiesFolder() {
        //Check if cookies folder exists otherwise create it
        if (!is_dir($_SESSION["config"]["_ABS_COOKIES_FOLDER_"])) {
            mkdir($_SESSION["config"]["_ABS_COOKIES_FOLDER_"]);
        }
        return $_SESSION["config"]["_ABS_COOKIES_FOLDER_"];
    }

    function headersQuery($url, $port, $get_source = false) {
        $ch = curl_init(); // create cURL handle (ch)
        if (!$ch) {
            $this->last_error = "Couldn't initialize a cURL handle";

            return false;
        }
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        //$header[] = "Connection: keep-alive";
        //$header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us";
        $header[] = "Pragma: "; // browsers keep this blank.
        // set some cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_path . $this->cookie_name);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_path . $this->cookie_name);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        //curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.62 Safari/537.36');
        if ($get_source === false) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }
        // execute
        $ret = curl_exec($ch);
        if ($ret === false) { //RETURNED FALSE
            // some kind of an error happened (probably timeout, we do not care to investigate)...
            $this->last_error = "False return! from CURL!\n\nErr. number " . curl_errno($ch) . " - " . curl_error($ch) . "\n\n";
            $this->last_error_code = "CURL" . curl_errno($ch);
            curl_close($ch); // close cURL handler
            return false;
        }
        if (empty($ret)) { //NO RETURNED VALUE
            // some kind of an error happened (that's strange!!)
            $this->last_error = "Empty return! from CURL!\n\nErr. number " . curl_errno($ch) . " - " . curl_error($ch) . "\n\n";
            $this->last_error_code = "CURL" . curl_errno($ch);
            curl_close($ch); // close cURL handler
            return false;
        }
        //Retrieve information from the CURL handler
        $this->headers = curl_getinfo($ch);
        //Put source code inside headers array...
        $this->headers["page_source_code"] = $ret;
        curl_close($ch); // close cURL handler
        @unlink($this->cookie_path . $this->cookie_name);
        //NO RESULT CODE
        if (!isset($this->headers['http_code']) or empty($this->headers['http_code'])) {
            $this->last_error = "No result code found";
            $this->last_error_code = "CURL" . curl_errno($ch);

            return false;
        }
        //NO errors....
        $this->last_error_code = "-";
        $this->headers["last_code_found"] = $this->headers['http_code'];

        return $this->headers;
    } //END METHOD

    function headersQueryRemote($url, $port) {
        $code = $this->generateCheckCode($url);
        $url = rawurlencode($url);
        $url_to_query = $this->remoteQueryAddress . "?url=$url&port=$port&code=$code&ppp";
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url_to_query);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Brainyping');
        $content_raw = curl_exec($curl_handle);
        if (empty($content_raw)) {
            $this->last_error = "Remote call returned false (curl returned empty value, error " .
                curl_errno($curl_handle) . " - " . curl_error($curl_handle) . ")";
            $this->last_error_code = "CURL" . curl_errno($curl_handle);

            return false;
        }
        if ($content_raw === false) {
            $this->last_error = "Curl operation returned false";
            $this->last_error_code = "CURL" . curl_errno($curl_handle);

            return false;
        }
        try {
            $content = json_decode($content_raw, 1);
        } catch (Exception $ex) {
            $this->last_error = "Remote call JSON decode fails\n" . $content_raw;
            $this->last_error_code = "JSONDECODE";

            return false;
        }
        if (!is_array($content)) {
            $this->last_error = "Remote call return not an array!\n$content_raw";
            $this->last_error_code = "NOTARRAY";

            return false;
        }
        if (isset($content["result"]) and $content["result"] == false) {
            $this->last_error = "Remote call return false\n" . $content["last error"];
            $this->last_error_code = $content["last_error_code"];

            return false;
        }
        if (isset($content["last_code_found"])) {
            //We are sure the call ended fine....
            $this->headers = $content;
            $this->last_error_code = "-";

            return $this->headers;
        } else {
            $this->last_error = "Remote call does not return a last code\n";
            $this->last_error_code = "NOLASTCODE";

            return false;
        }
    } //END METHOD

    function generateCheckCode($url) {
        $len = strlen($url);
        $len *= 3.5;
        $len = md5($len);

        return $len;
    }

    function getRedirectionsNumber() {
        return $this->headers["redirect_count"];
    }

    function getArrayElements() {
        return $this->headers["array_elements"];
    }

    function getDetails() {
        return $this->details;
    }

    function getDetailsAsString() {
        return multi_implode::go("\n", $this->details);
    }

    function getHeaderCode() {
        if ($this->headers === false) {
            return false;
        }

        return $this->headers["last_code_found"];
    }

    function getResultCode() {
        return $this->resultCode;
    }

    function getWorst() {
        return $this->time_result;
    }

    function getAverage() {
        return $this->time_result;
    }

    function getBest() {
        return $this->time_result;
    }

    function getSuccess() {
        return $this->success;
    }

    function getFailed() {
        return $this->requests - $this->success;
    }

    function getRequests() {
        return $this->requests;
    }

    function getResultPerc() {
        if ($this->requests == 0) {
            return 0;
        }

        return ($this->success / $this->requests) * 100;
    }

    function getIP() {
        return "0.0.0.0";
    }

    private function setURL($url, $port) {
        $checkHostPort = new check_address($url);
        if ($port === false) {
            //No port specified....
            //This could happen with http header request from site, not the monitor....
            $checkHostPort->setUrlPortAutomatically();
        } else {
            $checkHostPort->setUrlPort($port);
        }
        if ($checkHostPort->urlParsedSuccessfully() === false) {
            $this->last_error = "Unable to parse URL";

            return false;
        }
        //Information NOT to be included in the Rebuild process
        //This is because cURL function has its own option for these if needed
        $avoid_fields = array("port" => 1, "user" => 1);
        $this->url = $checkHostPort->rebuildURL($avoid_fields);
        $this->url_port = $checkHostPort->getUrlPort();

        return true;
    }

    function getTimeSpent() {
        return $this->time_spent;
    }

    function queryHeadersArray($element, $key) {
        return $this->headers[$element][$key];
    }

    function getHeadersArray() {
        return $this->headers;
    }

    function getPageSourceCode() {
        return $this->headers["page_source_code"];
    }

    function findInSouce($string) {
        if (strpos($this->headers["page_source_code"], $string) !== false) {
            //String found
            return true;
        }

        //String NOT found
        return false;
    }

    function setObjectMode($mode) {
        //MODE KEY NOT VALID....
        if (!array_key_exists($mode, $this->object_mode)) {
            $this->last_error = "Mode not valid";

            return false;
        }
        $current_mode_key = $this->getObjectMode();
        $this->object_mode[$current_mode_key] = 0;
        $this->object_mode[$mode] = 1;

        return $this->object_mode;
    }

    function getObjectMode() {
        return array_search(1, $this->object_mode);
    }

    function setKeyword($keyword) {
        $this->keyword = $keyword;
    }

    function getLastError() {
        return $this->last_error;
    }

    function getLastErrorCode() {
        return $this->last_error_code;
    }
}