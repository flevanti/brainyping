<?php


//FUNCTION TO CONVERT AN ARRAY TO A STRING WITH IT'S OWN KEYS....
//A STRING IS RETURNED IF ARRAY IS PASSED
//OTHERWISE WE GIVE BACK WHAT WE RECEIVED WITHOUT TOUCHING IT....
//FUNCTION IS RECURSIVE SO WE CAN GO DEEPER AND LOOK FOR NESTED ARRAY
function array_to_string($array, $indent = "") {
    $str = "";
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $str .= "$indent [$key] => [array]\n";
                $str .= array_to_string($value, $indent . " [$key]");
            } else {
                $str .= "$indent [$key] => $value\n";
            }
        }

        return $str;
    } else {
        return $array;
    }
}//END OF FUNCTION ARRAY TO STRING
function calculate_time($seconds_interval, $return_type = "ARRAY") {
    $seconds["y"] = 31556926;
    $seconds["m"] = 2629743;
    $seconds["w"] = 604800;
    $seconds["d"] = 86400;
    $seconds["h"] = 3600;
    $seconds["min"] = 60;
    $seconds["sec"] = 1;
    $arr_delay = ["y" => 0, "m" => 0, "w" => 0, "d" => 0, "h" => 0, "min" => 0, "sec" => 0];
    if ($seconds_interval <= 0) {
        return $arr_delay;
    }
    foreach ($seconds as $key => $value) {
        if ($seconds_interval >= $value) {
            $arr_delay[$key] = intval($seconds_interval / $value);
            $seconds_interval = $seconds_interval % $value;
        }
    }
    if ($return_type == "ARRAY") {
        return $arr_delay;
    }
    if ($return_type == "STRING") {
        $string = "";
        foreach ($arr_delay as $key => $value) {
            if ($value > 0) {
                $string .= "$value$key ";
            }
        } //END FOREACH
        return $string;
    }
    if ($return_type == "FULLSTRING") {
        $string = "";
        foreach ($arr_delay as $key => $value) {
            $string .= "$value$key ";
        } //END FOREACH
        return $string;
    }

    return false;
} //END FUNCTION
//FUNCTION TO CONVERT A TIMESTAMP IN DATE TIME
function ts_to_date($ts = 0) {
    if ($ts == 0) {
        $ts = time();
    }

    return date("d M Y H:i:s", $ts);
}

//FUNCTION TO DETECT IF SCRIPT IS RUNNING ON CLI OR WEB
function detect_cli() {
    if (php_sapi_name() == "cli") {
        return true;
    }
    if (strstr(php_sapi_name(), "apache")) {
        return false;
    }

    return false;
}

//This function is used to print text on screen or command line.
function prnt($txt) {
    print $txt;
}

//FUNCTION TO DOWNLOAD FILE TO A SPECIFIED LOCATION
function DownloadFile($reportDownloadUrl, $downloadPath) {
    $ch = curl_init($reportDownloadUrl);
    $fh = fopen($downloadPath, 'ab');
    if ($fh === false) {
        //throw new Exception('Failed to open ' . $downloadPath);
        return "Failed to open destination file/folder";
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FILE, $fh); // file handle to write to
    $result = curl_exec($ch);
    if ($result === false) {// it's important to check the contents of curl_error if the request fails
        //throw new Exception('Unable to perform the request : ' . curl_error($ch));
        return curl_error($ch);
    }

    return true;
}

//FUNCTION TO GET REMOTE ADDRESS FROM CONNECTION INFO
//WE ALSO CHECK FOR PROXY FORWARDED REQUESTS...
//We return proxy and client address
function get_remote_ip($x = "") {
    //CHECK IF CONNECTION IS FORWARDED BY A PROXY....
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        //PROXY FOUND
        $arr["proxy_addr"] = $_SERVER["REMOTE_ADDR"];
        $arr["client_addr"] = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } else {
        //PROXY NOT FOUND
        $arr["proxy_addr"] = "";
        $arr["client_addr"] = $_SERVER["REMOTE_ADDR"];
    }
    if ($x == "client") {
        return $arr["client_addr"];
    }
    if ($x == "proxy") {
        return $arr["proxy_addr"];
    }

    return $arr;
}

//CHECK IF USER HAS PERFORMED TOO MUCH REQUESTS... (MONGODB DB)
function check_request_limit_reached($ip, $hourlimit, $timerange) {
    //CONNECTION HANDLER
    global $mydbh;
    $sql = "select * from requests_by_ip where timerange = $timerange AND remote_ip = '$ip' AND requests_by_ip.requests > $hourlimit";
    $rs = $mydbh->query($sql);
    if ($rs->rowCount() > 0) {
        return true;
    }

    return false;
}

//ADD A REQUESTO TO THE HOUR TIMERANGE COLLECTING THE IP... (MONGODB)
function add_request_per_hour($ip, $timerange) {
    global $mydbh;
    $sql = "insert into requests_by_ip (remote_ip, timerange, requests)
                    VALUES ('$ip',$timerange,1)
                ON DUPLICATE KEY UPDATE requests=requests+1";
    $mydbh->query($sql);
}

//ADD HOST SEARCHED BY USER TO KEEP TRACK OF REQUESTS
function add_host_searched_by_user($host, $port) {
    global $mydbh;
    $sql = "INSERT INTO hosts_user_search (host, port, requests)
                    VALUES ( :host , :port , 1)
                ON DUPLICATE KEY UPDATE requests=requests+1";
    $stmt = $mydbh->prepare($sql);
    $stmt->execute(["host" => $host, "port" => $port]);
}

function check_host_public($host, $port) {
    global $mydbh;
    $sql = "SELECT * FROM hosts WHERE host = :host AND port= :port AND public = 1 AND enabled=1";
    $stmt = $mydbh->prepare($sql);
    $t = $stmt->execute(["host" => $host, "port" => $port]);
    if ($t === false) {
        //QUERY FAILS!!!!!
        return false;
    }
    //QUERY SUCCEEDED , check if is public or not...
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rs["public"] == 0) {
        return false;
    } else {
        return $rs;
    }
}

function generate_time_range() {
    return date("YmdH");
}

function get_value(&$handler, $key) {
    if (isset($handler[$key])) {
        return $handler[$key];
    } else {
        return "";
    }
}

//encode text to  be used in an HTML page...
function t2v($txt) {
    return str_replace(" ", "&nbsp;", htmlspecialchars($txt));
}

//encode text to  be used in an HTML form...
function t2f($txt) {
    return htmlspecialchars($txt);
}

//decode text coming from URL
function url2t($txt) {
    return urldecode($txt);
}













