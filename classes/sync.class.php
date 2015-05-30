<?php

class sync {
    private $dbhandler; //default connection to query DB for tasks it is also the 'int' connection
    public $last_error = "";
    public $last_error_tech = "";
    private $nrec = 0;
    private $nrec_update = 0;
    private $nrec_insert = 0;
    private $nrec_delete = 0;
    private $dbhandler_ext;
    private $dbhandler_int;
    public $verbose = false;
    public $verbose_newline = "\n";
    public $time_spent = 0;

    function __construct(&$dbconn_1, &$dbconn_2) {

        $this->dbhandler = $dbconn_1;

        $this->dbhandler_int = $dbconn_1;
        $this->dbhandler_ext = $dbconn_2;
    }

    private function verbose_output($txt) {
        if ($this->verbose === true) {
            echo $txt . $this->verbose_newline;
        }

        return true;
    }

    public function setSyncRunning($proc_name) {
        $sql = "update bg_proc_last_exec
                set start_run_ts = unix_timestamp()
                    where proc_name = '$proc_name' and start_run_ts < unix_timestamp()-120 limit 1;";
        $ret = $this->dbhandler->query($sql);
        if ($ret->rowCount() > 0) {
            return true;
        }
        $this->last_error = "Unable to sync, sync is still running from a previous session";

        return false;
    }

    public function setSyncNotRunning($proc_name) {
        $sql = "update bg_proc_last_exec
                set start_run_ts = 0
                    where proc_name = '$proc_name' limit 1;";
        $ret = $this->dbhandler->query($sql);

        return true;
    }

    public function sync($profile = false) {
        $this->verbose_output("VERBOSE MODE IS ON");
        $this->verbose_output("SYNC MAIN FUNCTION STARTED");
        $step = "SYNC MAIN METHOD";
        if ($profile === false) {
            $this->verbose_output("NO PROFILE SPECIFIED - SELECT *");
            $sql = "SELECT * FROM sync_tables_config
                        WHERE enabled=1
                            AND (force_run=1
                                    OR (sync_interval > 0
                                          AND last_sync_ts < unix_timestamp()-sync_interval*60));";
        } else {
            $this->verbose_output("PROFILE SPECIFIED: $profile");
            $sql = "select * from sync_tables_config where friendly_name = '$profile' and enabled=1;";
        }
        $rs = $this->dbhandler->query($sql);
        if ($rs === false) {
            $this->last_error = "error during profiles query (profile: " . ($profile === false ? "false" : $profile) . ")";
            $this->verbose_output($this->last_error);

            return false;
        }
        if ($rs->rowCount() == 0) {
            $this->last_error = "Profile not found, already executed or disabled";
            $this->verbose_output($this->last_error);

            return false;
        }
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $this->verbose_output("SYNC PROFILE: " . $row["friendly_name"]);
            $this->nrec = 0;
            $this->nrec_insert = 0;
            $this->nrec_update = 0;
            $this->nrec_delete = 0;
            $start_time = microtime(true);
            $ret = true;
            //VERY IMPORTANT CHECK FOR UPDATE BEFORE INSERT
            //OTHERWISE UPDATE CURSOR (THAT IS EXTRACTED ON THE FLY DURING EXECUTION) WILL RETURN A VALUE THAT IS
            //JUST ENTERED (BY INSERT) AND UPDATE WILL NOT PROCESS ANY RECORD. :)
            if ($row["local_sync"] == 0) {
                if ($row["update_"] == 1 and $ret === true) {
                    $ret = $this->updateExternal($row);
                }
                if ($row["insert_"] == 1 and $ret === true) {
                    $ret = $this->insertExternal($row);
                }
                if ($row["delete_by_cursor"] == 1 and $ret === true) {
                    $ret = $this->deleteByCursorExternal($row);
                }
                if ($row["delete_by_lookup"] == 1 and $ret === true) {
                    $ret = $this->deleteByLookupExternal($row);
                }
            } else {
                if ($row["update_"] == 1 and $ret === true) {
                    //$ret = $this->updateLocal($row);
                }
                if ($row["insert_"] == 1 and $ret === true) {
                    $ret = $this->insertLocal($row);
                }
                if ($row["delete_by_cursor"] == 1 and $ret === true) {
                    $ret = $this->deleteByCursorLocal($row);
                }
                if ($row["delete_by_lookup"] == 1 and $ret === true) {
                    //$ret = $this->deleteByLookupLocal($row);
                }
            }
            $time_spent = (microtime(true) - $start_time);
            if ($ret === false) {
                $this->last_error = "[" . $row["friendly_name"] . "] " . $this->last_error;
                $this->last_error_tech = "[" . $row["friendly_name"] . "] " . $this->last_error_tech;
                $this->verbose_output($this->last_error);
                $this->writeLog($row, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

                return false;
            }
            //UPDATE LAST EXECUTION OF THE SYNC
            $ret = $this->updateSyncLastExecution($row["id"], $time_spent);
            if ($ret === false) {
                $this->last_error .= "[" . $row["friendly_name"] . "] " . $this->last_error . " - " . $this->last_error_tech;
                $this->verbose_output($this->last_error);
                $this->writeLog($row, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

                return false;
            }
            $this->writeLog($row, $step, $time_spent, "OK", "OK");
        } //END WHILE
        return true;
    }

    private function insertExternal($row_sync) {
        $this->verbose_output("INSERT EXTERNAL");
        $step = "INSERT_EXTERNAL";
        //DEFINE FROM WHERE TO WHERE .... :)
        if ($row_sync["sync_reverse"] == 0) {
            $source_table = $row_sync["int_table"];
            $source_schema = $row_sync["int_schema"];
            $dest_table = $row_sync["ext_table"];
            $dest_schema = $row_sync["ext_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_ext;
        } else {
            $source_table = $row_sync["ext_table"];
            $source_schema = $row_sync["ext_schema"];
            $dest_table = $row_sync["int_table"];
            $dest_schema = $row_sync["int_schema"];
            $source_dbconn = $this->dbhandler_ext;
            $dest_dbconn = $this->dbhandler_int;
        }
        $lookup_value = $this->getLastLookupValue($dest_dbconn, $dest_schema, $dest_table, $row_sync["lookup_field"]);
        if ($lookup_value === false) {
            $this->last_error = "Error while retrieving last lookup value from dest.table";
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        //SELECT RECORDS FROM THE SOURCE TABLE...
        $sql = "select * from $source_schema.$source_table where {$row_sync["lookup_field"]} > $lookup_value;";
        $rs = $source_dbconn->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while retrieving new records from source table";
            $this->last_error_tech = "SQL: $sql\nERROR: " . implode("\n", $source_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        //NO NEW RECORDS...... BYE BYE
        if ($rs->rowCount() == 0) {
            return true;
        }
        $this->nrec_insert = $rs->rowCount();
        $sql_string_bind_values = $this->createSqlInsertFields($source_dbconn, $source_schema, $source_table);
        $sql = "insert into $dest_schema.$dest_table " . $sql_string_bind_values;
        $stmt = $dest_dbconn->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Error while preparing statement for inserting new records";
            $this->verbose_output($this->last_error);
            $this->last_error_tech = "SQL: $sql\nERROR: " . implode("\n", $dest_dbconn->errorInfo());
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        //RECORDS FOUND....LOOP AND IMPORT THEM....
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $ret = $stmt->execute($row);
            if ($ret === false) {
                $this->last_error = "Error while inserting new record";
                $this->last_error_tech = "SQL: $sql\nVALUES: " . array_to_string($row) . "\n\nERROR: " . implode("\n", $stmt->errorInfo());
                $this->verbose_output($this->last_error);
                $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

                return false;
            }
        } //END WHILE
        return true;
    } //END METHOD

    private function insertLocal($row_sync) {
        $this->verbose_output("INSERT LOCAL");
        $step = "INSERT_LOCAL";

        //DEFINE FROM WHERE TO WHERE .... :)
        if ($row_sync["sync_reverse"] == 0) {
            $source_table = $row_sync["int_table"];
            $source_schema = $row_sync["int_schema"];
            $dest_table = $row_sync["ext_table"];
            $dest_schema = $row_sync["ext_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_int;
        } else {
            $source_table = $row_sync["ext_table"];
            $source_schema = $row_sync["ext_schema"];
            $dest_table = $row_sync["int_table"];
            $dest_schema = $row_sync["int_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_int;
        }
        $lookup_value = $this->getLastLookupValue($dest_dbconn, $dest_schema, $dest_table, $row_sync["lookup_field"]);
        if ($lookup_value === false) {
            $this->last_error = "Error while retrieving last lookup value from dest.table";
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $sql = "insert into $dest_schema.$dest_table (select * from $source_schema.$source_table where {$row_sync["lookup_field"]} > $lookup_value);";
        $rs = $dest_dbconn->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while inserting new records to destination table";
            $this->last_error_tech = "SQL: $sql\nERROR: " . implode("\n", $dest_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        //NO NEW RECORDS...... BYE BYE
        if ($rs->rowCount() == 0) {
            echo "no records....\n\n";

            return true;
        }
        $this->nrec_insert = $rs->rowCount();

        return true;
    } //END METHOD

    private function createSqlInsertFields($dbconn, $schema, $table) {
        $sql = "show columns from $schema.$table;";
        $rs = $dbconn->query($sql);
        $sql_fields = array();
        $sql_bind = array();
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $sql_fields[] = $row["Field"];
            $sql_bind[] = ":" . $row["Field"];
        }
        $sql_string = " (" . implode(", ", $sql_fields) . ") VALUES (" . implode(", ", $sql_bind) . ") ";

        return $sql_string;
    }
    /**
    private function connectExternalDB($row_sync) {
        return;
        try {
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $row_sync["ext_charset"] . ';',
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
                PDO::MYSQL_ATTR_COMPRESS     => true
            );
            $this->dbhandler_ext = new PDO("mysql:host=" . $row_sync["ext_host"] . ";port=" . $row_sync["ext_port"] . ";dbname=" . $row_sync["ext_schema"] . ";charset=" . $row_sync["ext_charset"] . ";", $row_sync["ext_user"], $row_sync["ext_pwd"], $options);
            //STRICT MODE TO BE SURE ON EVERY INSERT/UPDATE....
            $this->dbhandler_ext->query("set sql_mode = 'STRICT_ALL_TABLES';");
            //CHARSET
            $this->dbhandler_ext->query("SET NAMES '{$row_sync["ext_charset"]}';");
            $this->dbhandler_ext->query("SET CHARACTER SET '{$row_sync["ext_charset"]}';");
            $this->dbhandler_ext->query("SET time_zone = '{$row_sync["ext_timezone_offset"]}';");
        } catch (Exception $ex) {
            $this->dbhandler_ext = false;
            $this->last_error_tech = $ex;

            return false;
        } //END OF TRY/CATCH
        return true;
    }
*/
    private function updateExternal($row_sync) {
        $this->verbose_output("UPDATE EXTERNAL");
        $step = "UPDATE_EXTERNAL";

        //DEFINE FROM WHERE TO WHERE .... :)
        if ($row_sync["sync_reverse"] == 0) {
            $source_table = $row_sync["int_table"];
            $source_schema = $row_sync["int_schema"];
            $dest_table = $row_sync["ext_table"];
            $dest_schema = $row_sync["ext_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_ext;
        } else {
            $source_table = $row_sync["ext_table"];
            $source_schema = $row_sync["ext_schema"];
            $dest_table = $row_sync["int_table"];
            $dest_schema = $row_sync["int_schema"];
            $source_dbconn = $this->dbhandler_ext;
            $dest_dbconn = $this->dbhandler_int;
        }
        $this->verbose_output("GET LOOKUP FIELD VALUE");
        $lookup_value = $this->getLastLookupValue($dest_dbconn, $dest_schema, $dest_table, $row_sync["lookup_field"]);
        $this->verbose_output("LOOKUP FIELD VALUE: " . $lookup_value);
        if ($lookup_value === false) {
            $this->last_error = "Error while retrieving last lookup value from dest. table";
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $this->verbose_output("GET CURSOR FIELD VALUE");
        $cursor_value = $this->getLastCursorValue($dest_dbconn, $dest_schema, $dest_table, $row_sync["update_cursor_field"]);
        $this->verbose_output("CURSOR VALUE: " . $cursor_value);
        if ($cursor_value === false) {
            $this->last_error = "Error while retrieving last cursor value from dest. table";
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        if ($row_sync["update_cursor_value_included"] == 1 and $cursor_value > 0) {
            $cursor_value--;
            $this->verbose_output("CURSOR VALUE SHOULD BE INCLUDED");
            $this->verbose_output("NEW CURSOR VALUE: " . $cursor_value);
        }
        $sql = "select * from $source_schema.$source_table
                    where {$row_sync["lookup_field"]} <= $lookup_value
                                and {$row_sync["update_cursor_field"]} > $cursor_value;";
        $rs = $source_dbconn->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while retrieving update records from source table";
            $this->last_error_tech = "SQL: $sql\nERROR: " . implode("\n", $source_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        //NO RECORDS...... BYE BYE
        if ($rs->rowCount() == 0) {
            $this->verbose_output("NO RECORDS TO PROCESS FOUND");

            return true;
        }
        $this->nrec_update = $rs->rowCount();
        $this->verbose_output("RECORDS FOUND TO BE PROCESSED: " . $this->nrec_update);
        $sql_bind_var = $this->createSqlUpdateFields($source_dbconn, $source_schema, $source_table);
        if ($sql_bind_var === false) {
            $this->last_error = "Error while preparing sql update fields";
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $sql = "update $dest_schema.$dest_table
                      set " . $sql_bind_var . "
                            where {$row_sync["lookup_field"]} = :{$row_sync["lookup_field"]};";
        $stmt = $dest_dbconn->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Error while preparing statement for updating records";
            $this->last_error_tech = "SQL: $sql\n" . implode("\n", $dest_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $ret = $stmt->execute($row);
            if ($ret === false) {
                $this->last_error = "Error while executing statement for updating record";
                $this->last_error_tech = "SQL: $sql\nVALUES: " . array_to_string($row) . "\nERROR:" . implode("\n", $dest_dbconn->errorInfo());
                $this->verbose_output($this->last_error);
                $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

                return false;
            }
        } //END WHILE
        return true;
    } //END METHOD UPDATE EXTERNAL

    private function deleteByLookupExternal($row_sync) {
        $this->verbose_output("DELETE BY LOOKUP EXTERNAL");
        $step = "DELETE_BYLOOKUP_EXTERNAL";
        //DEFINE FROM WHERE TO WHERE .... :)
        if ($row_sync["sync_reverse"] == 0) {
            $source_table = $row_sync["int_table"];
            $source_schema = $row_sync["int_schema"];
            $dest_table = $row_sync["ext_table"];
            $dest_schema = $row_sync["ext_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_ext;
        } else {
            $source_table = $row_sync["ext_table"];
            $source_schema = $row_sync["ext_schema"];
            $dest_table = $row_sync["int_table"];
            $dest_schema = $row_sync["int_schema"];
            $source_dbconn = $this->dbhandler_ext;
            $dest_dbconn = $this->dbhandler_int;
        }
        //Retrieves lookup field values
        $sql = "select {$row_sync["lookup_field"]} from $source_schema.$source_table;";
        $rs = $source_dbconn->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while retrieving lookup field values from source table";
            $this->last_error_tech = "SQL: $sql\n" . array_to_string($source_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        if ($rs->rowCount() == 0) {
            //source table is empty!!!  wow!!
            $sql = "delete from $dest_schema.$dest_table;";
        } else {
            $values_string = implode(",", $rs->fetchAll(PDO::FETCH_COLUMN));
            $sql = "delete from $dest_schema.$dest_table where {$row_sync["lookup_field"]} NOT IN ($values_string);";
        }
        $stmt = $dest_dbconn->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Error while deleting records in destination table";
            $this->last_error_tech = "SQL: $sql\nERROR: " . array_to_string($dest_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $stmt->execute();
        //Because we have 2 delete process that could be used together, we sum the previous value
        $this->nrec_delete = $this->nrec_delete + $stmt->rowCount();

        return true;
    }

    private function deleteByCursorLocal($row_sync) {
        $this->verbose_output("DELETE BY CURSOR LOCAL");
        $step = "DELETE_BYCURSOR_LOCAL";
        //DEFINE FROM WHERE TO WHERE .... :)
        if ($row_sync["sync_reverse"] == 0) {
            $source_table = $row_sync["int_table"];
            $source_schema = $row_sync["int_schema"];
            $dest_table = $row_sync["ext_table"];
            $dest_schema = $row_sync["ext_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_int;
        } else {
            $source_table = $row_sync["ext_table"];
            $source_schema = $row_sync["ext_schema"];
            $dest_table = $row_sync["int_table"];
            $dest_schema = $row_sync["int_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_int;
        }
        //Retrieves cursor value for delete from the source table....
        $sql = "select ifnull(MIN({$row_sync["delete_cursor_field"]}),'NULL') as cursor_value from $source_schema.$source_table;";
        $rs = $source_dbconn->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while retrieving cursor field values from source table";
            $this->last_error_tech = "SQL: $sql\n" . array_to_string($source_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $cursor_value = $rs->fetch(PDO::FETCH_ASSOC)["cursor_value"];
        //CURSOR VALUE NOT FOUND (table is empty.....)
        if ($cursor_value == "NULL") {
            //source table is empty!!!  wow!!
            $sql = "delete from $dest_schema.$dest_table;";
        } else {
            $sql = "delete from $dest_schema.$dest_table where {$row_sync["delete_cursor_field"]} < $cursor_value;";
        }
        $stmt = $dest_dbconn->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Error while deleting records in destination table";
            $this->last_error_tech = "SQL: $sql\nERROR: " . array_to_string($dest_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $stmt->execute();
        //Because we have 2 delete process that could be used together, we sum the previous value
        $this->nrec_delete = $this->nrec_delete + $stmt->rowCount();

        return true;
    } //END METHOD

    private function deleteByCursorExternal($row_sync) {
        $this->verbose_output("DELETE BY CURSO EXTERNAL");
        $step = "DELETE_BYCURSOR_EXTERNAL";
        //DEFINE FROM WHERE TO WHERE .... :)
        if ($row_sync["sync_reverse"] == 0) {
            $source_table = $row_sync["int_table"];
            $source_schema = $row_sync["int_schema"];
            $dest_table = $row_sync["ext_table"];
            $dest_schema = $row_sync["ext_schema"];
            $source_dbconn = $this->dbhandler_int;
            $dest_dbconn = $this->dbhandler_ext;
        } else {
            $source_table = $row_sync["ext_table"];
            $source_schema = $row_sync["ext_schema"];
            $dest_table = $row_sync["int_table"];
            $dest_schema = $row_sync["int_schema"];
            $source_dbconn = $this->dbhandler_ext;
            $dest_dbconn = $this->dbhandler_int;
        }
        //Retrieves cursor value for delete from the source table....
        $sql = "select ifnull(MIN({$row_sync["delete_cursor_field"]}),'NULL') as cursor_value from $source_schema.$source_table;";
        $rs = $source_dbconn->query($sql);
        if ($rs === false) {
            $this->last_error = "Error while retrieving cursor field values from source table";
            $this->last_error_tech = "SQL: $sql\n" . array_to_string($source_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $cursor_value = $rs->fetch(PDO::FETCH_ASSOC)["cursor_value"];
        //CURSOR VALUE NOT FOUND (table is empty.....)
        if ($cursor_value == "NULL") {
            //source table is empty!!!  wow!!
            $sql = "delete from $dest_schema.$dest_table;";
        } else {
            $sql = "delete from $dest_schema.$dest_table where {$row_sync["delete_cursor_field"]} < $cursor_value;";
        }
        $stmt = $dest_dbconn->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Error while deleting records in destination table";
            $this->last_error_tech = "SQL: $sql\nERROR: " . array_to_string($dest_dbconn->errorInfo());
            $this->verbose_output($this->last_error);
            $this->writeLog($row_sync, $step, 0, "ERROR", $this->last_error . "\n" . $this->last_error_tech);

            return false;
        }
        $stmt->execute();
        //Because we have 2 delete process that could be used together, we sum the previous value
        $this->nrec_delete = $this->nrec_delete + $stmt->rowCount();

        return true;
    } //END METHOD

    private function createSqlUpdateFields($dbconn, $schema, $table) {
        $this->verbose_output("CREATE SQL UPDATE FIELD");
        $this->verbose_output("PERFORM SHOW COLUMNS ON $schema.$table");
        $sql = "show columns from $schema.$table;";
        $rs = $dbconn->query($sql);
        if ($rs === false) {
            $this->verbose_output("REQUEST FAILED");
            $this->last_error_tech = implode("\n", $dbconn->errorInfo());

            return false;
        }
        $sql = array();
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $sql[] = " " . $row["Field"] . " = :" . $row["Field"];
        }

        return implode(",", $sql);
    }

    private function getLastLookupValue($dbconn, $schema, $table, $field) {
        //GET THE LAST VALUE FOR THE LOOKUP FIELD IN THE DESTINATION TABLE. THAT'S THE LAST RECORD INSERTED....
        $sql = "select ifnull(max($field),0) as lookup_value from $schema.$table;";
        $rs = $dbconn->query($sql);
        if ($rs === false) {
            $this->last_error_tech = "SQL: $sql\nERROR: " . implode("\n", $dbconn->errorInfo());
            $this->verbose_output($this->last_error);

            return false;
        }

        return $rs->fetch(PDO::FETCH_ASSOC)["lookup_value"];
    }

    private function getLastCursorValue($dbconn, $schema, $table, $field) {
        //GET THE LAST VALUE FOR THE LOOKUP FIELD IN THE DESTINATION TABLE. THAT'S THE LAST RECORD INSERTED....
        $sql = "select ifnull(max($field),0) as cursor_value from $schema.$table;";
        $rs = $dbconn->query($sql);
        if ($rs === false) {
            $this->last_error_tech = "SQL: $sql\nERROR: " . implode("\n", $dbconn->errorInfo());

            return false;
        }

        return $rs->fetch(PDO::FETCH_ASSOC)["cursor_value"];
    }

    private function updateSyncLastExecution($id, $time_spent) {
        $sql = "update sync_tables_config set last_sync_ts = " . time()
            . ", last_sync_str ='" . date("d/m/Y H:i:s") . "'"
            . ", nrecs = " . $this->nrec
            . ", nrec_insert = " . $this->nrec_insert
            . ", nrec_update = " . $this->nrec_update
            . ", nrec_delete = " . $this->nrec_delete
            . ", time_spent = " . round($time_spent, 3)
            . ", force_run = 0 "
            . " where id = $id;";
        $ret = $this->dbhandler->query($sql);
        if ($ret === false) {
            $this->last_error = "Error while updating sync config table";
            $this->last_error_tech = implode("\n", $this->dbhandler->errorInfo());
            $this->verbose_output($this->last_error);

            return false;
        }

        return true;
    }

    private function writeLog($row_sync, $step, $time_spent, $return_code, $return_descr) {
        $sql = "INSERT INTO sync_tables_logs (date_ts,
                                                date_str,
                                                step_,
                                                friendly_name,
                                                friendly_group_name,
                                                group_seq,
                                                time_spent,
                                                nrec_insert,
                                                nrec_update,
                                                nrec_delete,
                                                return_code,
                                                return_descr,
                                                machine_id,
                                                db_id) VALUES (
                                                :date_ts,
                                                :date_str,
                                                :step_,
                                                :friendly_name,
                                                :friendly_group_name,
                                                :group_seq,
                                                :time_spent,
                                                :nrec_insert,
                                                :nrec_update,
                                                :nrec_delete,
                                                :return_code,
                                                :return_descr,
                                                :machine_id,
                                                @db_id
                                                );";
        $arr_query = ["date_ts"             => time(),
                      "date_str"            => date("d/m/Y H:i:s"),
                      "step_"               => $step,
                      "friendly_name"       => $row_sync["friendly_name"],
                      "friendly_group_name" => "",
                      "group_seq"           => 0,
                      "time_spent"          => $time_spent,
                      "nrec_insert"         => $this->nrec_insert,
                      "nrec_update"         => $this->nrec_update,
                      "nrec_delete"         => $this->nrec_delete,
                      "return_code"         => $return_code,
                      "return_descr"        => $return_descr,
                      "machine_id"          => _MACHINE_ID_];
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->verbose_output("LOG STMT PREPARATION ERROR!!!!!");
            email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"], $_SESSION["config"]["_APP_DEFAULT_EMAIL_"],
                                    "ERROR LOGGING SYNC EVENT",
                                    "ERROR WHILE PREPARING STATEMENT.......\n\nSQL: $sql\n\nVALUES: " . array_to_string($arr_query) .
                                    "\n\nERROR:" . implode("\n", $stmt->errorInfo()));

            return false;
        }
        $ret = $stmt->execute($arr_query);
        if ($ret === false) {
            $this->verbose_output("LOG WRITING ERROR!!!!!");
            email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"], $_SESSION["config"]["_APP_DEFAULT_EMAIL_"],
                                    "ERROR LOGGING SYNC EVENT",
                                    "ERROR WHILE EXECUTING STATEMENT.......\n\nSQL: $sql\n\nVALUES: " . array_to_string($arr_query) .
                                    "\n\nERROR:" . implode("\n", $stmt->errorInfo()));

            return false;
        }

        return true;
    }
} //END CLASS


