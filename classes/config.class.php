<?php

class config {
    private $dbhandler;
    public $last_error = "";

    function __construct(&$dbconn = false) {
        if ($dbconn === false) {
            global $mydbh;
            $this->dbhandler = $mydbh;
        } else {
            $this->dbhandler = $dbconn;
        }
    }

    /**
     * This method creates a copy of the configuration for a new machine.
     * If no parameter is passed, GLOBAL settings will be used
     * @param        $newLabel      the new machine id configuration we want to create
     * @param string $copyLabel     the configuration we want to copy (GLOBAL is default)
     * @return bool
     */
    function addNewMachine($newLabel, $copyLabel = "GLOBAL") {
        //prepare the SQL statement
        $sql = "INSERT INTO config(
                                  var_key,
                                  var_value,
                                  last_modified_ts,
                                  machine_id,
                                  description,
                                  scope,
                                  force_global_value,
                                  weight) (SELECT
                                                    var_key,
                                                    var_value,
                                                    unix_timestamp() AS last_modified_ts,
                                                    :newLabel AS machine_id,
                                                    description,
                                                    scope,
                                                    force_global_value,
                                                    '1' AS weight
                                                FROM config
                                                    WHERE machine_id = :copyLabel
                                                          AND force_global_value = 0);";
        $stmt = $this->dbhandler->prepare($sql); //prepare SQL statement on MYSQL server
        $ret = $stmt->execute(array(":newLabel" => $newLabel, ":copyLabel" => $copyLabel));
        if ($ret !== true) {
            $this->last_error = implode(" - ", $stmt->errorInfo());

            return false;
        }

        return true;
    } //end addNewMachine method

    /**
     *  This method load configuration from DB
     *  We retrieve GLOBAL+MACHINE ID config
     *  if machine id config is present it will overwrite the global config
     * @param string $machine_id the id of the machine we want to retrieve configuration
     * @return bool
     */
    function loadConfig($machine_id = 'GLOBAL') {
        //Prepare SQL statement to query DB
        $sql = "SELECT machine_id,
                    var_key,
                    var_value,
                    weight
                FROM config
                  WHERE machine_id IN ('GLOBAL', :machine_id)
                    ORDER BY var_key, weight;";
        $stmt = $this->dbhandler->prepare($sql); //prepare the statement on MYSQL server
        $ret = $stmt->execute(array(":machine_id" => $machine_id)); //execute the statement using the named parameter
        if ($ret === false) {
            $this->last_error = implode(" - ", $stmt->errorInfo()); //sql execution failed
            return false;
        }
        //SQL execution OK...configuration retrieved, start building the configuration
        unset($_SESSION["config"]); //remove previous session configuration if present
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { //Loop every row to save the configuration in session vars
            $_SESSION["config"][$row["var_key"]] = $row["var_value"];
        }
        $this->loadConfigPath();

        return true;
    } //end loadConfig Method

    /**
     * Load some configuration related to path using local server variables
     * No need to store them in the DB
     * @return bool
     */
    function loadConfigPath() {
        //Try to get the root folder for HTML, CLI and COOKIES FOLDER
        //When calling pages using the web server is ok to use $_SERVER variable...
        //When dealing with the CLI interface we cannot use $_SERVER so we use the file folder name...
        if ($_SERVER["DOCUMENT_ROOT"] != "") { //if on web server
            $_SESSION["config"]["_ABS_DOC_ROOT_"] = str_replace("\\", "/", $_SERVER["DOCUMENT_ROOT"]); //get the web server document root
        } else { //if on CLI
            $temp_root = str_replace("\\", "/", dirname(__FILE__)); //get the file path
            $_SESSION["config"]["_ABS_DOC_ROOT_"] = strstr($temp_root, "/classes", true); //return the initial part of the path without the 'classes' final forlder
        } //end if
        if (substr($_SESSION["config"]["_ABS_DOC_ROOT_"], 1) != "/") { //if there's not slash at the end of the path
            $_SESSION["config"]["_ABS_DOC_ROOT_"] .= "/"; //we add it
        } //end if
        $_SESSION["config"]["_ABS_CLI_ROOT_"] = $_SESSION["config"]["_ABS_DOC_ROOT_"] . "cli/"; //CLI folder
        $_SESSION["config"]["_ABS_COOKIES_FOLDER_"] = $_SESSION["config"]["_ABS_DOC_ROOT_"] . "cli_cookies/"; //cookies folder
        $_SESSION["config"]["loaded"] = true; //set the flag to true, config is now loaded
        return true;
    }
} //END CLASS