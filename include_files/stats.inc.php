<?php
echo "<h3>Stats & Info</h3>";
echo "<h4>Internal use only</h4>";
echo "<br><br>";
echo "<div class=\"row\">";
echo "<div class=\"col-md-3 stats_div_container\">";
echo "<b>PHP</b><br>";
echo "Date and time: " . date("d/m/Y H:i:s") . "<br>";
echo "Timestamp: " . time() . "<br>";
echo "Timezone: " . date("P / e");
echo "</div>";
/*
echo "<div class=\"col-md-3 stats_div_container\">";
        echo "<b>MYSQL ENGINE</b><br>";

        $sql = "select date_format(now(), '%d/%m/%Y %T') as t
                        , UNIX_TIMESTAMP() as ts
                        , @@global.time_zone as glo_tz
                        , @@session.time_zone as ses_tz
                        , @@system_time_zone sys_tz;";

        $row = $mydbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        echo "D/T: " . $row["t"] . "<br>";
        echo "TS: " . $row["ts"] . "<br>";
        echo "Gl. TZ: ". $row["glo_tz"] . "<br>";
        echo "Sess. TZ: ". $row["ses_tz"] . "<br>";
        echo "Sys. TZ: ". $row["sys_tz"];

echo "</div>";
*/
echo "<div class=\"col-md-3 stats_div_container\">";
echo "<b>MYSQL ENGINE</b><br>";
echo "CONNECTION NOT AVAILABLE TO GUEST USERS.";
echo "</div>";
echo "<div class=\"col-md-3 stats_div_container\">";
echo "<b>MYSQL WEB</b><br>";
$sql = "select date_format(now(), '%d/%m/%Y %T') as t
                                                , UNIX_TIMESTAMP() as ts
                                                , @@global.time_zone as glo_tz
                                                , @@session.time_zone as ses_tz
                                                , @@system_time_zone sys_tz;";
$row = $mydbh_web->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "D/T: " . $row["t"] . "<br>";
echo "TS: " . $row["ts"] . "<br>";
echo "Gl. TZ: " . $row["glo_tz"] . "<br>";
echo "Sess. TZ: " . $row["ses_tz"] . "<br>";
echo "Sys. TZ: " . $row["sys_tz"];
echo "</div>";
echo "<div class=\"col-md-3 stats_div_container\">";
echo "<b>BROWSER/LOCAL CLIENT</b><br>";

?>
    <script>
        var currentdate = new Date ();
        var date_ = currentdate.getDate () + "/"
            + (currentdate.getMonth () + 1) + "/"
            + currentdate.getFullYear ();

        var time_ = currentdate.getHours () + ":"
            + currentdate.getMinutes () + ":"
            + currentdate.getSeconds ();

        var ts_ = Date.now () / 1000 | 0; //get only seconds (now() returns value in milliseconds)

        var tz_ = currentdate.getTimezoneOffset ();
        if (tz_ == 0) {
            tz_ = 'GMT';
        }

        document.write ('Date and time: ' + date_ + ' ' + time_ + '<br>');
        document.write ('Timestamp: ' + ts_ + '<br>');
        document.write ('Timezone: ' + tz_ + '<br>');


    </script>

<?php
echo "</div>";
echo "</div>";
$s = new stats_generate($mydbh_web);
if ($uriobj->getKey("generate") !== false) {
    echo "...generating...<br>";
    //echo ($s->genStats_CH_HOUR_FAILURE()===false?$s->last_error:"");
    //echo ($s->genStats_CH_DAY_HOUR_FAILURE()===false?$s->last_error:"");
    //echo ($s->genStats_CH_YESTERDAY_REAL()===false?$s->last_error:"");
    //echo ($s->genStats_CH_24H_REAL()===false?$s->last_error:"");
    //echo ($s->genStats_CH_24H_TIME_SPENT()===false?$s->last_error:"");
    //echo ($s->genStats_CH_24H_REQ_DISTR()===false?$s->last_error:"");
}
$stat_value4 = $s->getStatValue("CH_24H_REAL");
$last_update4 = $s->getStatLastUpdateStr();
$stat_value6 = $s->getStatValue("CH_24H_REQ_DISTR");
$last_update6 = $s->getStatLastUpdateStr();
$stat_value7 = $s->getStatValue("CH_24H_ENG_DISTR");
$last_update7 = $s->getStatLastUpdateStr();
$stat_value8 = $s->getStatValue("CH_BG_PROC_TIMELINE_LONG");
$last_update8 = $s->getStatLastUpdateStr();
$stat_value9 = $s->getStatValue("CH_BG_PROC_TIMELINE_SHORT");
$last_update9 = $s->getStatLastUpdateStr();
$stat_value10 = $s->getStatValue("CH_SCHEDULED_LOAD_SHORT");
$last_update10 = $s->getStatLastUpdateStr();
$stat_value11 = $s->getStatValue("CH_24H_NOK_ENG_DISTR");
$last_update11 = $s->getStatLastUpdateStr();

?>
    <br><br>


    <div class="stats_div_container_chart" id="ex4"></div>
    <br>

    <div class="stats_div_container_chart" id="ex6"></div>
    <br>
    <div class="stats_div_container_chart" id="ex11"></div>
    <br>
    <div class="stats_div_container_chart" id="ex7"></div>
    <br><h4>Timeline Chart 24h</h4>
    <div class="stats_div_container_chart" style="height:300px;" id="ex8"></div>
    <br><h4>Timeline Chart few minutes ago</h4>
    <div class="stats_div_container_chart" style="height:400px;" div id="ex9"></div>
    <br>
    <div class="stats_div_container_chart" id="ex10"></div>


    <script type="text/javascript"
            src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['corechart']}]}"></script>
    <script type="text/javascript"
            src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['timeline']}]}"></script>


    <script>

    function drawChart4 () {

        var dati = [<?php echo $stat_value4; ?>];

        var data = new google.visualization.DataTable ();
        data.addColumn ('string', 'X');
        data.addColumn ('number', 'NOK');

        data.addRows (dati);

        var options = {

            hAxis              : {
                title           : 'NOKs (Generated <?php echo $last_update4; ?>)',
                showTextEvery   : 15,
                slantedText     : true,
                slantedTextAngle: 45,
                textStyle       : {fontSize: 9}

            },
            vAxis              : {
                title         : 'Index',
                minValue      : 0,
                viewWindowMode: 'maximized'
            },
            series             : {
                0: {curveType: 'function'}
            },
            chartArea          : {
                top: 10
            },
            enableInteractivity: true,
            lineWidth          : 1,
            pointSize          : 1

        };

        var chart = new google.visualization.LineChart (document.getElementById ('ex4'));
        chart.draw (data, options);

    } //End DrawChart4

    function drawChart6 () {

        var dati = [<?php echo $stat_value6; ?>];

        var data = new google.visualization.DataTable ();
        data.addColumn ('string', 'X');
        data.addColumn ('number', 'EU TYP1');
        data.addColumn ('number', 'EU TYP2');
        data.addColumn ('number', 'EU TYP3');
        data.addColumn ('number', 'USA TYP1');
        data.addColumn ('number', 'USA TYP2');
        data.addColumn ('number', 'USA TYP3');
        data.addColumn ('number', 'USA2 TYP1');
        data.addColumn ('number', 'USA2 TYP2');
        data.addColumn ('number', 'USA2 TYP3');

        data.addRows (dati);

        var options = {

            hAxis              : {
                title           : 'Requests type (Generated <?php echo $last_update6; ?>)',
                showTextEvery   : 2,
                slantedText     : true,
                slantedTextAngle: 45,
                textStyle       : {fontSize: 9}

            },
            vAxis              : {
                title         : 'Index',
                minValue      : 0,
                viewWindowMode: 'maximized'
            },
            series             : {
                0: {curveType: 'function'},
                1: {curveType: 'function'},
                2: {curveType: 'function'},
                3: {curveType: 'function'},
                4: {curveType: 'function'},
                5: {curveType: 'function'},
                6: {curveType: 'function'},
                7: {curveType: 'function'},
                8: {curveType: 'function'}
            },
            chartArea          : {
                top   : 10,
                height: 320
            },
            legend             : {
                textStyle: {fontSize: 9}
            },
            enableInteractivity: true,
            height             : 400,
            lineWidth          : 1,
            pointSize          : 1

        };

        var chart = new google.visualization.LineChart (document.getElementById ('ex6'));
        chart.draw (data, options);

    } //End DrawChart6

    function drawChart7 () {

        var dati = [<?php echo $stat_value7; ?>];

        var data = new google.visualization.DataTable ();
        data.addColumn ('string', 'X');
        data.addColumn ('number', 'EUROPE');
        data.addColumn ('number', 'USA');
        data.addColumn ('number', 'USA2');

        data.addRows (dati);

        var options = {

            hAxis              : {
                title           : 'Engines Zone Req. Distr. (Generated <?php echo $last_update7; ?>)',
                showTextEvery   : 40,
                slantedText     : true,
                slantedTextAngle: 45,
                textStyle       : {fontSize: 9}

            },
            vAxis              : {
                title         : 'Index',
                viewWindowMode: 'maximized'
            },
            series             : {
                0: {curveType: 'function'},
                1: {curveType: 'function'},
                2: {curveType: 'function'}
            },
            chartArea          : {
                top   : 10,
                height: 320
            },
            enableInteractivity: true,
            lineWidth          : 1,
            pointSize          : 0,
            height             : 400

        };

        var chart = new google.visualization.LineChart (document.getElementById ('ex7'));
        chart.draw (data, options);

    } //End DrawChart7

    function drawChart8 () {
        var container = document.getElementById ('ex8');
        var chart = new google.visualization.Timeline (container);
        var dataTable = new google.visualization.DataTable ();
        dataTable.addColumn ({type: 'string', id: 'Process'});
        dataTable.addColumn ({type: 'date', id: 'Start'});
        dataTable.addColumn ({type: 'date', id: 'End'});

        dataTable.addRows ([<?php echo $stat_value8; ?>]);

        var options = {

            height                   : 300,
            timeline                 : {rowLabelStyle: {fontSize: 9}},
            avoidOverlappingGridLines: false
        };
        chart.draw (dataTable, options);
    } //END draChart 8

    function drawChart9 () {
        var container = document.getElementById ('ex9');
        var chart = new google.visualization.Timeline (container);
        var dataTable = new google.visualization.DataTable ();
        dataTable.addColumn ({type: 'string', id: 'Process'});
        dataTable.addColumn ({type: 'date', id: 'Start'});
        dataTable.addColumn ({type: 'date', id: 'End'});

        dataTable.addRows ([<?php echo $stat_value9; ?>]);

        var options = {
            height                   : 450,
            timeline                 : {rowLabelStyle: {fontSize: 9}},
            avoidOverlappingGridLines: false
        };
        chart.draw (dataTable, options);
    } //END draChart 9

    function drawChart10 () {

        var dati = [<?php echo $stat_value10; ?>];

        var data = new google.visualization.DataTable ();
        data.addColumn ('string', 'X');
        data.addColumn ('number', 'INDEX');

        data.addRows (dati);

        var options = {

            hAxis              : {
                title           : 'Scheduled load  (Generated <?php echo $last_update10; ?>)',
                showTextEvery   : 10,
                slantedText     : true,
                slantedTextAngle: 45,
                textStyle       : {fontSize: 9}

            },
            vAxis              : {
                title         : 'Index',
                viewWindowMode: 'maximized'
            },
            series             : {
                0: {curveType: 'function'}
            },
            chartArea          : {
                top: 10
            },
            enableInteractivity: true,
            lineWidth          : 1,
            pointSize          : 1

        };

        var chart = new google.visualization.LineChart (document.getElementById ('ex10'));
        chart.draw (data, options);

    } //End DrawChart10

    function drawChart11 () {

        var dati = [<?php echo $stat_value11; ?>];

        var data = new google.visualization.DataTable ();
        data.addColumn ('string', 'X');
        data.addColumn ('number', 'EUROPE');
        data.addColumn ('number', 'USA');
        data.addColumn ('number', 'USA2');

        data.addRows (dati);

        var options = {

            hAxis              : {
                title           : 'NOKs Engine Distr. (Generated <?php echo $last_update11; ?>)',
                showTextEvery   : 4,
                slantedText     : true,
                slantedTextAngle: 45,
                textStyle       : {fontSize: 9}

            },
            vAxis              : {
                title         : 'Index',
                viewWindowMode: 'maximized'
            },
            series             : {
                0: {curveType: 'function'},
                1: {curveType: 'function'},
                2: {curveType: 'function'}
            },
            chartArea          : {
                top   : 10,
                height: 320
            },
            enableInteractivity: true,
            lineWidth          : 1,
            pointSize          : 0,
            height             : 400

        };

        var chart = new google.visualization.LineChart (document.getElementById ('ex11'));
        chart.draw (data, options);

    } //End DrawChart11

    function drawChartss () {

        drawChart4 ();
        drawChart6 ();
        drawChart7 ();
        drawChart8 ();
        drawChart9 ();
        drawChart10 ();
        drawChart11 ();

    }

    //GOOGLE, Please draw charts..... :)
    google.load ('visualization', '1', {packages: ['corechart']});
    google.setOnLoadCallback (drawChartss);


    </script>


<?php

