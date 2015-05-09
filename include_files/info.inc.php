<?php
//CHECK IF HOST PUBLIC TOKEN IS PRESENT
if ($uriobj->getParam(1) == false) {
    echo "<br><br>We don't know what you want to see! <br>:)";
    return;
}

//TOKEN PRESENT!!!

//LET'S RETRIEVE HOST
$sql = "select h.*, ct.descr as mon_descr, ct.long_descr as mon_long_descr, ct.fa_icon as mon_fa_icon
          from hosts h, check_types ct
            where h.check_type = ct.id
              and h.public_token = :public_token;";
$stmt = $mydbh_web->prepare($sql);
$stmt->execute(["public_token"=>$uriobj->getParam(1)]);

if ($stmt->rowCount() == 0) {
    echo "<br><br>OOOPSS! We couldn't find what you're looking for...<br>Sorry.";
    return;
}

//LOOKS LIKE PUBLIC TOKEN EXISTS! THAT'S GOOD!

//FETCH IT
$host = $stmt->fetch(PDO::FETCH_ASSOC);

//NOW WE HAVE TO CHECK IF IT'S PUBLICLY AVAILABLE AND/OR USER IS THE OWNER
$user_is_owner = false;
$host_is_public = false;

if ($host["id_user"] == user::getID()) {
    $user_is_owner = true;
}

if ($host["public"]==1) {
    $host_is_public = true;
}

if ($user_is_owner == false and $host_is_public == false) {
    echo "<br><br>The information you requested are not publicly available.<br>
                    If you're the owner of such information please Sign In";
    return;
}


//HERE WE ARE ... LET'S GO...

echo "<h2>" . $host["title"]
        . " &nbsp;&nbsp;&nbsp;<span id=\"star_subscribe\" data-toggle=\"tooltip\" data-placement=\"right\" data-original-title=\"SEND ME AN EMAIL WHEN DOWN!\" class=\"star_subscribe fa fa-star\" host_public_token = \"" . $host["public_token"] . "\"></span>";

        if ($user_is_owner === true) {
            echo " &nbsp;<span id=\"edit_host\" data-toggle=\"tooltip\" data-placement=\"right\" data-original-title=\"EDIT MONITOR DETAILS\" class=\"edit_host_from_info fa fa-pencil\" host_public_token = \"" . $host["public_token"] . "\"></span>";
        }

echo "</h2>";

echo "<div  style=\"border-radius: 5px;
                        border: 1px solid lightgray;
                            background-color: white;\">";

    echo "<p>";
            echo "<b>MONITOR TYPE</b>: " . $host["check_type"] . " ";// . $host["mon_long_descr"] . " ";
            echo "<b>EVERY</b>: " . $host["minutes"] . " minutes ";

            if ($host["check_type"] == "CHECKCONN"
                    or $host["check_type"] == "FTPCONN"
                    or $host["check_type"] == "SMTPCONN")
            {

                        echo "<b>HOST</b>: " . $host["host"] . " ";
                        echo "<b>PORT</b>: " . $host["port"] . " ";
            }

            if ($host["check_type"] == "HTTPHEADER") {
                echo "<b>URL</b>: " . $host["host"] . " ";
                echo "<b>PORT</b>: " . $host["port"] . " ";
            }

            if ($host["check_type"] == "WEBKEYWORD") {
                echo "<b>URL</b>: " . $host["host"] . " ";
                echo "<b>PORT</b>: " . $host["port"] . " ";
                echo "<b>KEYWORD</b>: " . $host["keyword"] . " ";
            }
    echo "</p>";

    echo "<p>";
            echo "<b>LAST CHECK PERFORMED</b>: " . $host["last_check_str"] . " ";
            echo "<b>RESULT</b>: " . ($host["check_result"]=="OK"?"OK":"FAILED") . " ";
            echo "<b>RESPONSE</b>: " . round($host["last_check_avg"]/1000,2) . "s. ";
    echo "</p>";

echo "</div>";

echo "<br>";

echo "<div class=\"row\">";  //DIV ROW

    echo "<div class=\"col-md-7\">"; //DIV CHARTS

            //DIV CHART FEW MOMENTS
            echo "<div style=\"border-radius:5px;
                                    border:1px solid lightgray;
                                    background-color: white;
                                    padding:5px;\">";
                if ($host["latest_results"] != "") {
                    echo "FEW MOMENTS AGO<br>";
                    //Retrieve xx results from the "latest result" string
                    $values = host_manager::getLatestResultsArray($host["latest_results"],100);
                    //Create the response time chart (PUT THE VALUES... THE CHART WILL BE RENDERED BY JS)
                    echo "<span class=\"peity_response_wrapper\"><span class=\"peity_response waiting_render\">". implode(",",$values)."</span></span>";
                } else {
                    echo "No details available for detailed chart.";
                }
            echo "</div>"; //END DIV FEW MOMENTS


            echo "<br>";

            //DIV CHART 24H
            echo "<div id=\"chart_div\" style=\"border-radius:5px;
                                                    border:1px solid lightgray;
                                                    background-color: white;
                                                    padding:5px;\">";
                //RETRIEVE RESULT FOR 24H chart
                //Results will be used in the JS script .. not here....

                $chart24_data = "";
                $chart24_data_temp = array();

                $sql = "SELECT * FROM results_latest_24h
                              where id_host = " . $host["id"] ."
                                    order by  ts_trunc;";
                $rs = $mydbh_web->query($sql);

                var_dump($mydbh_web->errorInfo());

                while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                    //CREATE A ROW LIKE: ['timestamp', value, point_style]
                    $chart24_data_temp[] = "['" . date("H:i",$row["ts_trunc"]) . "', " . round($row["reply_avg"]/1000,2) . ", " . ($row["NOK"]>0?"NOK_point":"null") . "]";
                }

                //THIS VARIABLE WILL BE USED TO POPULATE JS VARIABLE.....
                $chart24_data =  implode(",",$chart24_data_temp);

            echo "</div>"; //END DIV CHART 24H

            echo "<br>";
////////////////////////////////////////////////

            //DIV CHART 30DAYS
            echo "<div id=\"chart_div2\" style=\"border-radius:5px;
                                                                border:1px solid lightgray;
                                                                background-color: white;
                                                                padding:5px;\">";
            //RETRIEVE RESULT FOR 31DAYS chart
            //Results will be used in the JS script .. not here....

            $chart31d_data = "";
            $chart31d_data_temp = array();

            $daycode_start = date("Ymd",(time()-(30*24*60*60)));

            //3600 means we rapresent value in hours (60*60)
            $seconds = 3600;

            $sql = "SELECT daycode, round((uptime/$seconds),2) as OK,
                                    round((downtime/$seconds),2) as NOK,
                                    round((unknowntime/$seconds),2) as UNK
                                    FROM results_daily
                                          where id_host = " . $host["id"] ."
                                                and daycode >= $daycode_start
                                                order by  daycode;";
            $rs = $mydbh_web->query($sql);

//var_dump($rs->fetchAll(PDO::FETCH_ASSOC));exit;

            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                //CREATE A ROW LIKE: ['day', OK value, NOK value, UNK value, '']
                $chart31d_data_temp[] = "['" . substr($row["daycode"],-2) . "', " .
                                                $row["OK"] . ", " .
                                                $row["NOK"] . ", " .
                                                $row["UNK"] . ", " .
                                                "'']";
            }

            //THIS VARIABLE WILL BE USED TO POPULATE JS VARIABLE.....
            $chart31d_data =  implode(",",$chart31d_data_temp);

            echo "</div>"; //END DIV CHART 31 DAYS

    echo "</div>"; //END ROW DIV








//LOG











    echo "<div class=\"col-md-5\" style=\"overflow-y:auto; min-height:150px; max-height:500px; border-radius:5px; border:1px solid lightgray; background-color: white;\">";
        $sql = "select * from host_logs
                    where public_token = '".$uriobj->getParam(1)."'
                        order by ts desc;";

        $rs = $mydbh_web->query($sql);

        if ($rs->rowCount() == 0) {
            echo "NO LOGS AVAILABLE...";
        } else {
            echo "<table class=\"table table-condensed\" style=\"text-align:left;width:100%;\">
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Duration</th>
                        </tr>";

            $last_event = time();
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                    echo "<td >";
                    switch ($row["event"]) {
                        case "OK":
                            echo "<span class=\"label label-success micro_text\"> ONLINE</span>";
                            break;
                        case "NOK":
                            echo "<span class=\"label label-danger\"> OFFLINE</span>";
                            break;
                        default:
                            echo "<span class=\"label label-info\"> " . $row["event"] ."</span>";
                    }
                    echo "</td>";
                    echo "<td class=\"micro_text\">" . date("d M Y H:i:s",$row["ts"]) . "</td>";
                    echo "<td class=\"micro_text\">" . calculate_time($last_event-$row["ts"],"STRING")."</td>";
                    $last_event = $row["ts"];
                echo "</tr>";
            } //END WHILE

            echo "</table>";
        } //END IF


    echo "</div>";

echo "</div>";




?>


<script type="text/javascript" src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1','packages':['corechart']}]}"></script>
<script>


    function drawChart() {
        var NOK_point = 'point { size: 3; shape-type: circle; fill-color: #FF0000';
        var data = google.visualization.arrayToDataTable
        ([['Time', 'Sec.', {'type': 'string', 'role': 'style'}],
            <?php echo $chart24_data; ?>
        ]);

        var options = {
            legend: 'none',
            vAxis: {
                viewWindowMode: 'maximized'
            },
            hAxis: {
                slantedText: true,
                slantedTextAngle: 45,
                textStyle: {fontSize: 9}

            },
            chartArea: {
                left: 40,
                width: '100%'
            },
            curveType: 'function',
            pointSize: 1,
            lineWidth: 1,
            height: 150,
            width: '100%',
            title: 'Average Response Time',

            backgroundColor: 'transparent'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
    }  //END OF DRAWCHART


    function drawChart2() {

        var data = google.visualization.arrayToDataTable([
            ['YEAR','ONLINE', 'OFFLINE', 'UNKNOWN', { role: 'annotation' } ],
            <?php echo $chart31d_data; ?>
        ]);

        var options = {

            height: 150,
            legend: { position: 'none' },
            bar: { groupWidth: '75%' },
            isStacked: true,
            series: {
                        0: {color: '#4d89f9'},
                        1: {color: '#B80000'},
                        2: {color: '#E0E0E0'}
                    },
            hAxis: {
                title: '30 Days Rolling',
                showTextEvery:1,
                slantedText: true,
                slantedTextAngle: 45,
                textStyle: {fontSize: 9}
            },
            vAxis: {
                title: 'Hours',
                viewWindowMode: 'maximized'
            },
            chartArea: {
                left: 40,
                top:10,
                width: '100%'
            }
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div2'));
        chart.draw(data, options);
    }  //END OF DRAWCHART2



    function drawGoogleChartssss() {
        drawChart();
        drawChart2();
    }


    google.setOnLoadCallback(drawGoogleChartssss);

    //END GOOGLE CHART....





    $.fn.peity.defaults.line = {
        delimiter: ",",
        fill: "#ECF3FF",
        height: 60,
        max: null,
        min: 0,
        stroke: "#4d89f9",
        strokeWidth: 0.5,
        width: "100%"
    };

    function render_peity_chart_waiting () {
        objs = $(".peity_response.waiting_render");
        objs.peity("line");
        objs.removeClass('waiting_render');
    }



    render_peity_chart_waiting();


    $('span#edit_host').click(function() {
        window.location = '/edit/' + $(this).attr('host_public_token') +'/';
    });


    //WE NEED TO WAIT FOR DOCUMENT TO BE LOADED TO ACTIVATE TOOLTIP....
    $( document ).ready(function() {
        //ACTIVATE TOOLTIP ON THE LITTLE STAR SUBSCRIBE
        $('span').tooltip();
    });

</script>





