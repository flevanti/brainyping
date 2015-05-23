<?php

//this class is used to manage the URL/HOST/DOMAINS passed as a parameter for monitoring requests.
//it is also used to check email address
//There're mainly 3 kind of address/URL
//1. only the domain (for smtp or ftp check for example)
//2. a full URL to check header code
//3. email address
//So the class get the URL/HOST (Called address) and, based on the method used perform needed checks.
//
//$address and $address_port are related to host checks (SMTP/FTP/PING)
//$parsed_address is related to URL (HTTP HEADER/ HTTP KEYWORD)
class check_address {

    private $address_received; //
    private $address = "";  //DOMAIN ONLY
    private $address_port = false; //DOMAIN ONLY
    private $parsed_address = false;  //HTTP URL
    private $email_address; //EMAIL ONLY
    public $last_error = "";
    public $arr_error = array();

    function __construct($address) {
        $this->address_received = trim($address);
        $address = trim($address);
        if ($address == "") {
            $this->last_error = "Empty string";

            return false;
        }
        $this->address = $address;
        $this->parsed_address = parse_url($address);
        $this->email_address = $address;

        return true;
    }

    function splitAddressHostPort() {
        $temp = explode(":", $this->address);
        $this->address = $temp[0];
        if (isset($temp[1])) {
            $this->address_port = $temp[1];
        }

        return true;
    }

    function checkEmailSyntax() {
        return filter_var($this->email_address, FILTER_VALIDATE_EMAIL);
    }

    function urlParsedSuccessfully() {
        if ($this->parsed_address === false) {
            return false;
        }

        return true;
    }

    //This method check if the given address is a valid host (no web URL)....
    function isValidHost() {
        if (filter_var(gethostbyname($this->address), FILTER_VALIDATE_IP) === false) {
            $this->last_error = "Host not valid (Unable to resolve it)";

            return false;
        }

        return true;
    }

    function isUrlDomainValid() {
        if (!isset($this->parsed_address["host"])) {
            $this->last_error = "Host not specified in the URL";

            return false;
        }
        if (filter_var(gethostbyname($this->parsed_address["host"]), FILTER_VALIDATE_IP) === false) {
            $this->last_error = "Host not valid (Unable to resolve it)";

            return false;
        }

        return true;
    }

    function getScheme() {
        if (isset($this->parsed_address["scheme"])) {
            return $this->parsed_address["scheme"];
        }
        $this->last_error = "Scheme not found in address";

        return false;
    }

    function isHttpOrHttps() {
        if ($this->parsed_address === false) {
            $this->last_error = "URL parsing failed";

            return false;
        }
        if (!isset($this->parsed_address["scheme"])) {
            $this->last_error = "URL schema not found";

            return false;
        }
        if ($this->parsed_address["scheme"] != "http" and $this->parsed_address["scheme"] != "https") {
            $this->last_error = "Allowed schemes are HTTP and HTTPS";

            return false;
        }

        return true;
    }

    function isUrlPortSpecified() {
        if (isset($this->parsed_address["port"])) {
            return true;
        }

        return false;
    }

    function isUrlFragmentSpecified() {
        if (isset($this->parsed_address["fragment"])) {
            return true;
        }

        return false;
    }

    function rebuildURL($avoid_fields = array()) {
        $url = "";
        if (isset($this->parsed_address["scheme"])) {
            $url .= $this->parsed_address["scheme"] . "://";
        }
        if (!isset($avoid_fields["user"]) && isset($this->parsed_address["user"])) {
            $url .= $this->parsed_address["user"];
            if (isset($this->parsed_address["pass"])) {
                $url .= ":" . $this->parsed_address["pass"];
            }
            $url .= "@";
        }
        if (isset($this->parsed_address["host"])) {
            $url .= $this->parsed_address["host"];
        }
        if (!isset($avoid_fields["port"]) && isset($this->parsed_address["port"])) {
            $url .= ":" . $this->parsed_address["port"];
        }
        if (isset($this->parsed_address["path"])) {
            $url .= $this->parsed_address["path"];
        }
        if (isset($this->parsed_address["query"])) {
            $url .= "?" . $this->parsed_address["query"];
        }
        if (isset($this->parsed_address["fragment"])) {
            $url .= "#" . $this->parsed_address["fragment"];
        }

        return $url;
    }

    function isUrlTrailigSlashPresent() {
        //WE CAN ACCEPT TRAILING SLASH...SO WE RETURN FALSE
        return false;
        //DEPRECATED....
        /*
        if (substr($this->address,-1) == "/") {
            return true;
        }
        return false;

        */
    }

    function getParsedUrl() {
        return $this->parsed_address;
    }

    function isUrlUserSpecified() {
        if (isset($this->parsed_address["user"])) {
            return true;
        }

        return false;
    }

    function isHostPortDefined() {
        if ($this->address_port === false) {
            return false;
        }

        return true;
    }

    function isHostPortValid() {
        if ($this->isHostPortDefined() === false) {
            $this->last_error = "Host port not defined";

            return false;
        }
        if (!is_numeric($this->address_port)) {
            $this->last_error = "Host port not numeric";

            return false;
        }
        if ($this->address_port < 1 or $this->address_port > 65535) {
            $this->last_error = "Host port out of range";

            return false;
        }

        return true;
    }

    function isUrlPortDefined() {
        if (!isset($this->parsed_address["port"])) {
            return false;
        }

        return true;
    }

    function isUrlPortValid() {
        if ($this->isurlPortDefined() === false) {
            $this->last_error = "URL port not defined";

            return false;
        }
        if (!is_numeric($this->parsed_address["port"])) {
            $this->last_error = "URL port not numeric";

            return false;
        }
        if ($this->parsed_address["port"] < 1 or $this->parsed_address["port"] > 65535) {
            $this->last_error = "URL port out of range";

            return false;
        }

        return true;
    }

    function getHostPort() {
        if ($this->isHostPortValid() !== true) {
            return false;
        }

        return $this->address_port;
    }

    function setHostPort($port) {
        $this->address_port = $port;
    }

    function setUrlPort($port) {
        $this->parsed_address["port"] = $port;
    }

    function getUrlPort() {
        if ($this->isUrlPortValid() !== true) {
            return false;
        }

        return $this->parsed_address["port"];
    }

    function setUrlPortAutomatically() {
        switch ($this->getScheme()) {
            case "http":
                $this->setUrlPort(80);
                break;
            case "https":
                $this->setUrlPort(443);
                break;
            default:
                return false;
        }

        return true;
    }

    function isValidUrlMultiCheck() {
        if ($this->urlParsedSuccessfully() === false) {
            $this->last_error = "Unable to parse URL";

            return false;
        }
        if ($this->isHttpOrHttps() === false) {
            return false;
        }
        if ($this->isUrlDomainValid() === false) {
            $this->last_error = "Domain check failed: " . $this->last_error;

            return false;
        }
        if ($this->isUrlFragmentSpecified() === true) {
            $this->last_error = "Please do not include anchors in your URL (#) ";

            return false;
        }
        if ($this->isUrlPortSpecified() === true) {
            $this->last_error = "Port not allowed here. Web connection works only on default ports (80,443)";

            return false;
        }
        if ($this->isUrlUserSpecified() === true) {
            $this->last_error = "User/Password not allowed in URL";

            return false;
        }
        if ($this->isUrlTrailigSlashPresent() === true) {
            $this->last_error = "Please remove the trailing slash (/)";

            return false;
        }
        if (strtolower($this->address_received) != strtolower($this->rebuildURL())) {
            $this->last_error = "URL rebuild failed! \nYour value:\n" . $this->rebuildURL() . "\nVS\n" . $this->address_received;

            return false;
        }

        return true;
    }
}  //END OF CLASS