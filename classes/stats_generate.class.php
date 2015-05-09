<?php
class stats_generate {

    private $today_daycode;
    private $dbhandler;
    public  $last_error = "";
    private $stat_last_update;


    function __construct(&$dbconn=false) {

        if ($dbconn===false) {
            global $mydbh;
            $this->dbhandler = $mydbh;
        } else {
            $this->dbhandler = $dbconn;
        }

        set_time_limit(180);
        $this->today_daycode = date("Ymd");


    }


    function genStats_CH_24H_NOK_ENG_DISTR ($stat_id_key) {

        $time_spent = microtime(true);

        $last_ts_trunc = substr(time() - (24*60*60),0,-3) . "000";
        $stat_value = array();

        $ts_limit_now = substr(time(),0,-3) . "000";

        $sql = "select  left(current_date_ts,7) as trunc_ts
                         , SUM( IF(result =  'NOK' and result_was = 'OK' and source='ENG1', 1, 0) ) AS 'NOKENG1'
                         , SUM( IF(result =  'NOK' and result_was = 'OK' and source='ENG2', 1, 0) ) AS 'NOKENG2'
                         , SUM( IF(result =  'NOK' and result_was = 'OK' and source='ENG3', 1, 0) ) AS 'NOKENG3'
                         , SUM( IF(source='ENG1', 1, 0) ) AS 'TOTENG1'
                         , SUM( IF(source='ENG2', 1, 0) ) AS 'TOTENG2'
                         , SUM( IF(source='ENG3', 1, 0) ) AS 'TOTENG3'
                         , SUM(1) as 'TOT'
                    from vw_checks_schedule_with_results_temp
                    where ts_check > $last_ts_trunc
                          and ts_check < $ts_limit_now
                    group by trunc_ts;";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }

        $perc1 = 0;
        $perc2 = 0;
        $perc3 = 0;

        while ($row = $raw_res->fetch(PDO::FETCH_ASSOC)) {

            $perc1 = $perc1 + (($row["TOTENG1"]==0?0:($row["NOKENG1"]/$row["TOTENG1"])*100));
            $perc2 = $perc2 + (($row["TOTENG2"]==0?0:($row["NOKENG2"]/$row["TOTENG2"])*100));
            $perc3 = $perc3 + (($row["TOTENG3"]==0?0:($row["NOKENG3"]/$row["TOTENG3"])*100));

            $stat_value[] = "['". date("H:i",$row["trunc_ts"] . "000") ."',   $perc1 , $perc2, $perc3]";

        }


        //convert array to string for js
        $stat_value  =  implode("@",$stat_value) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                                    , last_ts_trunc = $ts_limit_now
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;


    } //END CH_24H_NOK_ENG_DISTR


    function genStats_CH_24H_REAL ($stat_id_key) {



        $time_spent = microtime(true);


        $last_ts_trunc = substr(time() - (24*60*60),0,-3) . "000";


        $ts_limit_now = substr(time(),0,-3) . "000";

        $stat_value = array();



        $sql = "select  left(current_date_ts,7) as trunc_ts
                         , SUM( IF(result =  'NOK' and result_was = 'OK', 1, 0) ) AS 'NOK'
                         , SUM(1) as 'TOT'
                    from vw_checks_schedule_with_results_temp
                    where ts_check > $last_ts_trunc
                          and ts_check < $ts_limit_now
                    group by trunc_ts;";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }

        $nok = 0;

        while ($row = $raw_res->fetch(PDO::FETCH_ASSOC)) {


            $nok = $nok + $row["NOK"];


            $stat_value[] = "['". date("H:i",($row["trunc_ts"] . "000")) ."', " .  $nok . "]";


        }

        //convert array to string for js
        $stat_value  =  implode("@",$stat_value) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                                    , last_ts_trunc = $ts_limit_now
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;


    } //END CH_24H_REAL



    function genStats_CH_24H_REQ_DISTR ($stat_id_key) {



        $time_spent = microtime(true);


            $last_ts_trunc = substr(time() - (24*60*60),0,-3) . "000";
            $stat_value = array();



        $ts_limit_now = substr(time(),0,-3) . "000";


        if ($last_ts_trunc == $ts_limit_now) {
            return true;
        }


        $sql = "select  current_date_ts_000 as trunc_ts
                         , SUM( IF(source='ENG1' and requests =  1, 1, 0) ) AS 'EUREQ1'
                         , SUM( IF(source='ENG1' and requests =  2, 1, 0) ) AS 'EUREQ2'
                         , SUM( IF(source='ENG1' and requests =  3, 1, 0) ) AS 'EUREQ3'
                         , SUM( IF(source='ENG2' and requests =  1, 1, 0) ) AS 'USAREQ1'
                         , SUM( IF(source='ENG2' and requests =  2, 1, 0) ) AS 'USAREQ2'
                         , SUM( IF(source='ENG2' and requests =  3, 1, 0) ) AS 'USAREQ3'
                         , SUM( IF(source='ENG3' and requests =  1, 1, 0) ) AS 'USA2REQ1'
                         , SUM( IF(source='ENG3' and requests =  2, 1, 0) ) AS 'USA2REQ2'
                         , SUM( IF(source='ENG3' and requests =  3, 1, 0) ) AS 'USA2REQ3'
                         , SUM(1) as 'TOT'
                    from results_temp
                    where current_date_ts_000 >= $last_ts_trunc and current_date_ts_000 < $ts_limit_now
                              and (port=80 or port=443)
                    group by trunc_ts
                    order by trunc_ts;";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }


        while ($row = $raw_res->fetch(PDO::FETCH_ASSOC)) {

            $perc1 = round(($row["EUREQ1"] / $row["TOT"]) * 100,3);
            $perc2 = round(($row["EUREQ2"] / $row["TOT"]) * 100,3);
            $perc3 = round(($row["EUREQ3"] / $row["TOT"]) * 100,3);
            $perc4 = round(($row["USAREQ1"] / $row["TOT"]) * 100,3);
            $perc5 = round(($row["USAREQ2"] / $row["TOT"]) * 100,3);
            $perc6 = round(($row["USAREQ3"] / $row["TOT"]) * 100,3);
            $perc7 = round(($row["USA2REQ1"] / $row["TOT"]) * 100,3);
            $perc8 = round(($row["USA2REQ2"] / $row["TOT"]) * 100,3);
            $perc9 = round(($row["USA2REQ3"] / $row["TOT"]) * 100,3);

            $stat_value[] = "['". date("H:i",$row["trunc_ts"]) ."',$perc1 ,$perc2 , $perc3, $perc4, $perc5,$perc6, $perc7, $perc8,$perc9]";


        }

        //convert array to string for js
        $stat_value  =  implode("@",$stat_value) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                                    , last_ts_trunc = $last_ts_trunc
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;
    }



    function genStats_CH_24H_ENG_DISTR ($stat_id_key) {

        $time_spent = microtime(true);

            $last_ts_trunc = substr(time() - (24*60*60),0,-2) . "00";
            $stat_value = array();


        $ts_limit_now = substr(time(),0,-2) . "00";


        if ($last_ts_trunc == $ts_limit_now) {
            return true;
        }


        $sql = "SELECT current_date_ts_00 as ts_trunc,
                        SUM(IF(source = 'PING' or source='ENG1', 1, 0)) AS ENG1,
                        SUM(IF(source = 'PING2' or source='ENG2', 1, 0)) AS ENG2,
                        SUM(IF(source = 'PING3' or source='ENG3', 1, 0)) AS ENG3,
                        SUM(1) AS TOT
                    FROM
                        results_temp
                    WHERE
                        current_date_ts_00 >= $last_ts_trunc
                          and current_date_ts_00 < $ts_limit_now
                        group by ts_trunc
                        order by ts_trunc;";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }



        while ($row = $raw_res->fetch(PDO::FETCH_ASSOC)) {

            $perc1 = round(($row["ENG1"] / $row["TOT"]) * 100,3);
            $perc2 = round(($row["ENG2"] / $row["TOT"]) * 100,3);
            $perc3 = round(($row["ENG3"] / $row["TOT"]) * 100,3);
            $stat_value[] = "['". date("H:i",$row["ts_trunc"]) ."',$perc1 ,$perc2, $perc3 ]";


        }

        //convert array to string for js
        $stat_value  =  implode("@",$stat_value) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                                    , last_ts_trunc = $last_ts_trunc
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;


    }



    function genStats_CH_BG_PROC_TIMELINE_LONG ($stat_id_key) {

        $time_spent = microtime(true);


        $last_ts_trunc = time() - (24*60*60);

        $ts_limit_now = time();



        //FIRST WE CREATE THE SINGLE POINTS ..... AND RECORD THEM IN THE DB....
        $this->genTimelineChartValue();


        $sql = "SELECT chart_value
                        FROM
                            bg_proc_logs a,
                            bg_proc_last_exec b
                        WHERE
                            a.proc_name = b.proc_name
                                AND b.timeline_chart_long = 1
                                AND a.chart_value_generated = 1
                                and a.ts_stop > $last_ts_trunc and a.ts_stop < $ts_limit_now";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }

        //FETCH ALL THE RECORDSET - WE USE FETCH COLUMN TO FETCH THE COLUMN RETRIEVED BY THE QUERY IN A SINGLE ARRAY
        $raw_res = $raw_res->fetchAll(PDO::FETCH_COLUMN);



        //convert the FETCHED RECORDSET to string for js
        $stat_value  =  implode("@",$raw_res) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                                    , last_ts_trunc = $ts_limit_now
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;

    }


    function genStats_CH_BG_PROC_TIMELINE_SHORT ($stat_id_key) {

        $time_spent = microtime(true);



        $last_ts_trunc = time() - (15*60);


        $ts_limit_now = time();





        //FIRST WE CREATE/POPULATE THE SINGLE POINTS IN THE DB ......
        $this->genTimelineChartValue();

        //THEN WE SELECT THEM TO CREATE THE CHART SCRIPT

        $sql = "SELECT chart_value
                        FROM
                            bg_proc_logs a,
                            bg_proc_last_exec b
                        WHERE
                            a.proc_name = b.proc_name
                                AND b.timeline_chart_short = 1
                                AND a.chart_value_generated = 1
                                and a.ts_stop > $last_ts_trunc and a.ts_stop < $ts_limit_now;";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }

        //FETCH ALL THE RECORDSET - WE USE FETCH COLUMN TO FETCH THE COLUMN RETRIEVED BY THE QUERY IN A SINGLE ARRAY
        $raw_res = $raw_res->fetchAll(PDO::FETCH_COLUMN);

        //convert the FETCHED RECORDSET to string for js
        $stat_value  =  implode("@",$raw_res) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                                    , last_ts_trunc = $ts_limit_now
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;

    }

    function genStats_CH_SCHEDULED_LOAD_SHORT ($stat_id_key) {


        $time_spent = microtime(true);



        $sql = "SELECT
                        CONCAT(LEFT(ts_check, 9), '0') AS ts_trunc, COUNT(*) nrec
                    FROM
                        host_checks_schedule
                    WHERE
                        ts_check > CONCAT(LEFT(UNIX_TIMESTAMP(), 9), '0')
                            AND ts_check < CONCAT(LEFT(UNIX_TIMESTAMP() + 60 * 60, 9), '0')
                    GROUP BY ts_trunc
                    ORDER BY ts_trunc;";

        $raw_res = $this->dbhandler->query($sql);

        if ($raw_res === false) {
            $this->last_error = "Error retrieving data (id_stat: $stat_id_key)\n" . implode("\n", $this->dbhandler->errorInfo());
            return false;
        }

        $temp = array();
        while ($row = $raw_res->fetch(PDO::FETCH_ASSOC)) {
            //INSTEAD OF GIVING THE REAL REC NUMBER WE DIVIDE IT BY 10 TO GIVE A KIND OF INDEX. NO TRICKS.
            $temp[] = "['" . date("H:i:s", $row["ts_trunc"]) . "', " . $row["nrec"] / 10 ."]";
        }

        //convert the array to string for js
        $stat_value  =  implode("@",$temp) ;



        $sql = "update stats_data set stat_value = :value
                                    , generated_ts = :ts
                                    , generated_str = :date_str
                                    , generated_time_spent = :time_spent
                        where id_stat = :id_stat
                        limit 1;";

        $stmt = $this->dbhandler->prepare($sql);

        $time_spent = round(((microtime(true) - $time_spent) ),3);

        $arr_query["value"] = $stat_value;
        $arr_query["ts"] = time();
        $arr_query["date_str"] = date("d/m/Y H:i:s");
        $arr_query["time_spent"] = $time_spent;
        $arr_query["id_stat"] = $stat_id_key;

        $ret = $stmt->execute($arr_query);

        if ($ret === false) {
            $this->last_error = "Error during update query (id_stat: $stat_id_key)\n\n" . implode("\n",$stmt->errorInfo());
            return false;
        }
        return true;

    }


    function genTimelineChartValue () {

        //THIS QUERY CREATE A STRING ON EVERY ROW LIKE:
        // ['PROC_NAME', new Date(0,1,25,15,57,28), new Date(0,1,25,15,57,43)]
        // This is JS
        // It will be used to create a full set of data to be used by Timeline charts
        $sql = "UPDATE bg_proc_logs
                    SET
                        chart_value = CONCAT('[\'',
                                proc_name,
                                '\', new Date(',YEAR(FROM_UNIXTIME(ts_start)),',',
                                MONTH(FROM_UNIXTIME(ts_start))-1,
                                ',',
                                DAY(FROM_UNIXTIME(ts_start)),
                                ',',
                                HOUR(FROM_UNIXTIME(ts_start)),
                                ',',
                                MINUTE(FROM_UNIXTIME(ts_start)),
                                ',',
                                SECOND(FROM_UNIXTIME(ts_start)),
                                '), new Date(',YEAR(FROM_UNIXTIME(ts_start)),',',
                                MONTH(FROM_UNIXTIME(ts_stop))-1,
                                ',',
                                DAY(FROM_UNIXTIME(ts_stop)),
                                ',',
                                HOUR(FROM_UNIXTIME(ts_stop)),
                                ',',
                                MINUTE(FROM_UNIXTIME(ts_stop)),
                                ',',
                                SECOND(FROM_UNIXTIME(ts_stop)),
                                ')]'),
                        chart_value_generated = 1 where chart_value_generated = 0 ;";

        $this->dbhandler->query($sql);

        return true;


    }



    function getStatValue ($id_stat) {


        $sql = "select * from stats_data where id_stat = '$id_stat';";
        $rs = $this->dbhandler->query($sql);

        if ($rs === false) {
            $this->last_error = "error while retrieving stats value";
            $this->last_error_tech = implode("\n",$this->dbhandler->errorInfo());
            return false;
        }

        $row = $rs->fetch(PDO::FETCH_ASSOC);

        if(isset($row["stat_value"])) {
            $this->stat_last_update = $row["generated_str"];
            return str_replace("@",",",$row["stat_value"]);
        }
        $this->stat_last_update = "";
        return false;

    }

    function getStatLastUpdateStr() {
        return $this->stat_last_update;
    }



}