<?php


class aggregate {
    private $daycode_today;
    private $max_minute_run = 5;  //MAX EXECUTION TIME PER EVERY RUN
    private $log = array();
    private $dbhandler;
    private $errors = array();
    public  $last_error = "";
    private $stmt_retrieve_results;
    private $stmt_insert_aggregated_data;
    private $stmt_set_results_aggregated;
    public $max_time_reached = false;
    public $records_found = 0;
    public $records_processed = 0;


    function __construct(&$dbconn = false) {
        if ($dbconn === false) {
            global $mydbh;
            $this->dbhandler = &$mydbh;
        } else {
            $this->dbhandler = $dbconn;
        }

        $this->daycode_today = date("Ymd");
    }






    function aggregate() {

        //Check if some errors are present
        if (count($this->errors) > 0 ) {
            return false;
        }

        $start_time = time();


        //Recover host/day/planned checks to aggregate
        $sql = "select id_host, daycode, count(*) n_checks
                    from host_checks_schedule
                    where aggregated = 0 and daycode < ". $this->daycode_today . "
                        group by id_host, daycode
                        order by daycode, id_host;";


        $stmt_host = $this->dbhandler->prepare($sql);
        $stmt_host->execute();

        $this->records_found = $stmt_host->rowCount();
        //We didn't find any host/day to aggregate
        if ($this->records_found==0) {
            return true;
        }

        //We are ready to loop
        //Prepare the DB SQL statements that are going to be used in the loop
        $this->prepareDbStatements();

        //SECONDS IN A DAY
        $seconds_in_a_day = (60*60*24);


        //Loop host/day
        while ($row_host = $stmt_host->fetch(PDO::FETCH_ASSOC)) {

            //Check if execution reaches the time limit set by the user....
            if ((time()-$start_time) > ($this->max_minute_run * 60)) {
                //if yes.....
                $this->max_time_reached = true;
                return true; //////////////////////////////////////////////////////////////////////
            }

            $this->records_processed++;

            //Prepare array for query bind parameters...
            $arr_query = array();
            $arr_query["id_host"] = $row_host["id_host"];
            $arr_query["daycode"] = $row_host["daycode"];

            //Retrieve all the planned checks  with their results
            $this->stmt_retrieve_results->execute($arr_query);

            //Calculate the timestamp for midnight
            $day = substr($row_host["daycode"],6,2);
            $month = substr($row_host["daycode"],4,2);
            $year = substr($row_host["daycode"],0,4);
            $midnight_ts = mktime(0,0,0,$month,$day,$year);


            //We set che last check at midnight so we do not miss the gap between midnight and the first check
            $last_check_ts = $midnight_ts;

            //Se set last status = UNKNOWN (could be anything) so in case no results are found time will be added to
            //the unknown element in the array
            $last_status = "UNKNOWN";

            //CALCULATE NEXT MIDNIGHT TIMESTAMP
            $midnight_ts_next = $midnight_ts + $seconds_in_a_day;

            //Aggregated data array
            $agg = array();
            $agg["UP"]=0;
            $agg["DOWN"]=0;
            $agg["UNKNOWN"]=0;


            //We calculate how many checks have been performed..
            $checks_performed = 0;

                    //WE CHECK FOR EACH ROW FOR THE STATUS OF THE RESULT
                    while ($row_results = $this->stmt_retrieve_results->fetch(PDO::FETCH_ASSOC)){


                        //Calculate the interval since last check....
                        $interval = $row_results["ts_check"] - $last_check_ts;


                        //we calculate up/down times based on the previous result
                        //(the result field is the current result and it is valid from now on....)
                        if ($row_results["result_was"] == "OK") {
                            $agg["UP"] += $interval;
                            $checks_performed++;
                        } elseif ($row_results["result_was"] == "NOK") {
                            $agg["DOWN"] += $interval;
                            $checks_performed++;
                        } else {
                            $agg["UNKNOWN"] += $interval;
                        }


                        $last_status = $row_results["result_was"];
                        $last_check_ts = $row_results["ts_check"];

                    } //END WHILE RESULTS


            //AGGREGATE THE REMAINING TIME TILL MIDNIGHT....
            $seconds_to_midnight = $midnight_ts_next - $last_check_ts;
            if ($last_status == "OK") {
                $agg["UP"] += $seconds_to_midnight;
            } elseif ($last_status == "NOK") {
                $agg["DOWN"] += $seconds_to_midnight;
            } else {
                $agg["UNKNOWN"] += $seconds_to_midnight;
            }



            $agg["TOTAL"] = $agg["UP"] + $agg["DOWN"] + $agg["UNKNOWN"];

            $this->dbhandler->beginTransaction();

            $arr_query = array();
            $arr_query["daycode"] = $row_host["daycode"];
            $arr_query["id_host"] = $row_host["id_host"];
            $arr_query["uptime"] = $agg["UP"];
            $arr_query["downtime"] = $agg["DOWN"];
            $arr_query["unknowntime"] = $agg["UNKNOWN"];
            $arr_query["generated_ts"] = time();
            $arr_query["planned_checks"] = $row_host["n_checks"]; //Scheduled checks
            $arr_query["completed_checks"] = $checks_performed; //results found
            $arr_query["day_details"] = "";
            $arr_query["log"] = implode("\n",$this->getLog());

            $ret = $this->stmt_insert_aggregated_data->execute($arr_query);

            if ($ret === false) {
                $this->last_error =  "AGGREGATED DATA FAILED TO SAVE!";
                return false;
            }


            $arr_query = array();
            $arr_query["daycode"] = $row_host["daycode"];
            $arr_query["id_host"] = $row_host["id_host"];

            $ret = $this->stmt_set_results_aggregated->execute($arr_query);

            if ($ret === false) {
                $this->last_error = "FAILED TO SET RESULTS AS AGGREGATED!";
                return false;
            }

            $this->dbhandler->commit();

        } //END Loop host/day

        return true;

    } //END AGGREGATE METHOD


    function getLog () {
        return $this->log;
    }


    private function prepareDbStatements () {

        //PREPARE SQL STATEMENT FOR AGGREGATED RESULTS
        $sql = "insert into results_daily (daycode,
                                        id_host,
                                        uptime,
                                        downtime,
                                        unknowntime,
                                        generated_ts,
                                        planned_checks,
                                        completed_checks,
                                        day_details,
                                        log
                                        )
                                        VALUES
                                        (:daycode,
                                        :id_host,
                                        :uptime,
                                        :downtime,
                                        :unknowntime,
                                        :generated_ts,
                                        :planned_checks,
                                        :completed_checks,
                                        :day_details,
                                        :log);";
        $this->stmt_insert_aggregated_data = $this->dbhandler->prepare($sql);

        //Prepare statement to retrieve results to aggregate...
        $sql = "select * from vw_checks_schedule_with_results
                    WHERE id_host = :id_host AND daycode = :daycode
                    ORDER BY ts_check;";
        $this->stmt_retrieve_results = $this->dbhandler->prepare($sql);


        //PREPARE SQL STATEMENT FOR FLAG CHECKS SCHEDULED AS AGGREGATED
        $sql = "update host_checks_schedule
          set aggregated = 1
              where daycode = :daycode
                and id_host = :id_host;";
        $this->stmt_set_results_aggregated = $this->dbhandler->prepare($sql);

    } //END METHOD prepareDBstatements


    function getErrors() {
        return $this->errors;
    }

    function setMaxExecutionTime ($minute = 1000) {
        if (!is_numeric($minute)) {
            $this->errors[] = "Max execution time should be a number";
            return false;
        }

        if ($minute == 0) {
            $this->errors[] = "Max execution time should be > 0";
            return false;
        }

        if ($minute > 1000) {
            $this->errors[] = "Max execution time max value 1000";
            return false;
        }

        $this->max_minute_run = $minute;

        return true;

    }

    //VERY EASY METHOD TO AGGREGATE LATEST 24H (AGGREGATE GRANULARITY 9999 secs)
    function aggregate_latest_24h () {

        //DELETE OLD DATA
        $sql = "delete from results_latest_24h
                      where ts_trunc < concat(left(unix_timestamp()-24*60*60,7),'000');";
        $this->dbhandler->query($sql);

        //INSTEAD OF TRUNCATING AND RE-CREATING THE WHOLE TABLE WE GET THE MAX TIMESTAMP
        //AND WE CONTINUE FROM THAT POINT
        //IF NULL IS RETURNED WE GET THE TS-24h TRUNCATED TO AGGREGATE DATA IN BIGGER PERIOD THAN EVERY SEC
        $sql = "SELECT
                        IFNULL(MAX(ts_trunc),
                                CONCAT(LEFT(UNIX_TIMESTAMP()-24*60*60, 7), '000')) AS max_ts,
                                CONCAT(LEFT(UNIX_TIMESTAMP(), 7), '000') AS current_ts
                    FROM
                        results_latest_24h;";
        $row = $this->dbhandler->query($sql)->fetch(PDO::FETCH_ASSOC);
        $max_ts = $row["max_ts"];
        $current_ts = $row["current_ts"];


        if ($max_ts == $current_ts) {
            //NOTHING TO AGGREGATE FOR THE MOMENT....
            return true;
        }


        //Populate table
        $sql = "INSERT INTO results_latest_24h (id_host, ts_trunc,reply_avg,NOK,TOT,generated_ts)
                        SELECT
                            id_host,
                            (LEFT(ts_check_triggered, 7) * 1000) AS ts_trunc,
                            ROUND(AVG(reply_average), 3) AS reply_avg,
                            SUM(IF(result = 'NOK', 1, 0)) AS 'NOK',
                            SUM(1) AS 'TOT',
                            UNIX_TIMESTAMP() AS generated_ts
                        FROM
                            results_temp
                        WHERE
                            (LEFT(ts_check_triggered, 7) * 1000) > $max_ts
                              and (LEFT(ts_check_triggered, 7) * 1000) < $current_ts
                        GROUP BY id_host , ts_trunc;";
        $r = $this->dbhandler->query($sql);
        if ($r=== false) {
            $this->last_error = implode("\n",$this->dbhandler->errorInfo());
            return false;
        }
        $this->records_found = $r->rowCount();



        return true;


    }


}