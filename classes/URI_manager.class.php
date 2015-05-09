<?php
class URI_manager {
    private $uri;
    private $uri_splitter = "/";
    private $uri_param = array();
    private $empty_parameters = true;

    function __construct () {
        $this->uri_param["Xdebug"] = "";
        $this->uri = $_SERVER["REQUEST_URI"];
    }


    public function URI_gen ($param=array()) {

        $uri = "";

        if (count($param) > 0) {
            foreach ($param as $value) {
                $uri .= $this->uri_splitter . $value;
            }
        }

        if ($this->uri_param["Xdebug"] != "") {
            if (substr($uri,-1)== $this->uri_splitter) {
                $uri .= $this->uri_param["Xdebug"];
            } else {
                $uri .= $this->uri_splitter . $this->uri_param["Xdebug"];
            }

        }


        return $uri;
    } //END METHOD URI GENERATOR

// This method get the URL information, parse it and check for variable...
//It creates an array $URIParam with the parameters found
//there's also a special element Xdebug if we found a debug session....
// It works together with .htaccess rewrite rules like following:
//
//RewriteEngine on
//RewriteBase /
//RewriteCond %{REQUEST_FILENAME} !-f
//RewriteCond %{REQUEST_FILENAME} !-d
//RewriteRule .* / [QSA,L]
//

    public function parseURI () {
        $this->URI_parser($this->uri);
    }

    private function URI_parser (&$uri) {

        //STRIP THE FIRST SLASH IF URI STRING IS LONGER THEN 1 CHAR
        //IF IT'S JUST A SLASH SET URI TO ""
        if (strlen($uri)==1) {
            $uri = "";
        } else {
            //STRIP THE FIRST SLASH TO AVOID FIRST PARAMETER TO BE EMPTY....
            $uri = substr($uri,1);
        }

        //If URI is empty means user requested homepage...
        //If URI is not empty we parse the string....
        //slash is the splitter /
        if ($uri != "") {
            //URI IS NOT EMPTY

            $arr_parameters = explode($this->uri_splitter,$uri);

            //FOR SAFETY REASONS INSTEAD OF USING FOREACH KEY WE CREATE OUR INDEX
            $i=0;

            foreach ($arr_parameters as $value) {
                //CHECK IF PARAMETER IS A XDEBUG SESSION....
                //IF YES WE REGISTER IT...AND SKIP PARAMETERES ASSOCIATIONS
                if (stristr($value,'?XDEBUG')) {
                    $this->uri_param["Xdebug"] = $value;
                    //NEXT ITERATION PLEASE...
                    continue;
                }

                //IF WE ARE HERE.... THE PARAMETER IS NOT A DEBUGGING SESSION
                if (trim($value) != "" or $this->empty_parameters==true) {
                    //CREATE PARAMETER ELEMENT...
                    $this->uri_param[$i] = $value;
                    //NEXT KEY PLEASE...
                    $i++;
                }
            } //END FOREACH
        } else {
            //URI IS EMPTY
            //Set the first parameter empty by default
            $this->uri_param[0]="";
        }

        return true;




    } //END URI_parser method


    //RETURN PARAM VALUE
    //IF KEY IS A NUMBER USE IT AS AN INDEX
    //IF KEY IS NOT A NUMBER TRY TO RECOVER PARAM VALUE FROM URL
    //(ex.: URL .../page/12 -> key "page" -> return 12)
    public function getParam($key) {
        if (is_numeric($key)) { //NUMERIC KEY
            if (array_key_exists($key, $this->uri_param)) {
                return $this->uri_param[$key];
            } else {
                return false;
            }
        }  //END IF NUMERIC

        //NON NUMERIC KEY //TRY TO RECOVER THE PARAMETER
        $key_position = $this->getKey($key);
        if ($key_position===false) {
            return false;
        }
        //NOW WE HAVE A NUMERIC KEY, VALUE SHOULD BE THE NEXT POSITION SO..
        //WE ADD ONE TO THE KEY AND CALL OURSELF (WITH A NUMERIC KEY) YEAHHH...

        $key_position++;
        return $this->getParam($key_position);


    }

    //Given a value , it returns the key (pay attention of key 0 !=  false!!!!)
    public function getKey($value) {
        return array_search($value,$this->uri_param);

    }

    public function getParamArray() {
        return $this->uri_param;
    }

    public function getParamDebug () {
        return $this->uri_param["Xdebug"];
    }






} //END CLASS URI MANAGER