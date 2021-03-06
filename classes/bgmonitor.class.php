<?php

class bgmonitor {

    protected $dbhandler;
    public $last_error = "";
    public $last_error_tech = "";

    function __construct(&$dbconn = false) {
        if ($dbconn === false) {
            global $mydbh;
            $this->dbhandler = $mydbh;
        } else {
            $this->dbhandler = $dbconn;
        }
    }

    function getMonitorList() {
        $sql = "SELECT * FROM bg_proc_last_exec WHERE enabled = 1 ORDER BY proc_name;";
        $rs = $this->dbhandler->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while retrieving bg proc. list";
            $this->last_error_tech = implode("\n", $this->dbhandler->errorInfo());

            return false;
        }

        return $rs;
    }
}