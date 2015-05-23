<script>
    function show_form (obj) {
        obj.style.display = 'none';
        document.getElementById ('form_search').style.display = 'block';
        document.getElementById ('host').focus ();
    }

</script>

<button class="btn btn-sm btn-info btn_another_check" onclick="show_form(this);">CHECK ANOTHER DOMAIN</button>
<div style="display:none;" id="form_search">
    <?php require_once _INCLUDE_FILES_PATH_ . "homepage_form_ping.inc.php"; ?>
</div>
<?php

$max_request_per_hour = 250;

//RETRIEVE PARAMETERS TO PERFORM PING....
if ($uriobj->getParam(1) === false or $uriobj->getParam(1) == "") {
    $host_to_ping = "http://www.google.com";
    $port_to_ping = "80";
} else {
    $host_to_ping = rawurldecode(rawurldecode($uriobj->getParam(1)));
}


$ip = get_remote_ip('client');
$timerange = generate_time_range();
$limit_reached = check_request_limit_reached($ip, $max_request_per_hour, $timerange);

if ($limit_reached == true) {
    echo "<h4>OOOOOPS, TOO MANY REQUESTS!</h4>";

    return;
}

add_request_per_hour($ip, $timerange);

$host_searched = new http_header();

$r = $host_searched->getHeaders($host_to_ping);


if ($r === false) {
    //INVALID HOST.....
    echo "<h4>OOOOOPS</h4>";
    echo "Looks like something is wrong!<br>";
    echo t2v($host_searched->last_error) . "<br>";
    require_once _INCLUDE_FILES_PATH_ . "homepage_form_ping.inc.php";

    return;
}

//SAVE THE REQUEST TO DB TO KEEP TRACK OF REQUESTED HOSTS...
add_host_searched_by_user($host_to_ping, "");


//Check if the host status is already monitored publicly
//return false or the host object (array)
//$host_public = check_host_public($host_to_ping,$port_to_ping);
$host_public = false;


?>

<div class=\"realtime_status_check\">

    <p class="user_search_domain_title"> <?php echo strtoupper(t2v($host_to_ping)); ?></p>

    <p class="micro_text">Real Time Check</p>
    <table class="table table-condensed table-striped table_user_search">

        <tbody>
        <tr>
            <td>
                URL
            </td>
            <td>
                <?php
                echo $host_to_ping;
                ?>
            </td>
        </tr>

        <tr>
            <td>Reply time</td>
            <td><?php
                echo $host_searched->getAverage() . " ms";
                ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td><?php
                if ($host_searched->getResultCode() == "OK") {
                    echo "<span class=\"label label-success\">SERVICE UP</span>";
                } else {
                    echo "<span class=\"label label-danger\">SERVICE DOWN</span >";
                }

                ?></td>
        </tr>
        </tbody>
    </table>







    <?php


    if ($host_public === false) {
        //echo "HOST NOT PUBLIC";
        echo "Add this domain to your profile to monitor uptime and be alerted everytime it's offline!";
    } else {
        if ($host_public["latest_results"] != "") {
            echo "<script>";
            echo chart_small_line::getLineChartSettings();
            echo chart_small_line::getGlobalSettings();
            echo "</script>";
            $chart = new chart_small_line();
            $chart->setIDChart('dummyID1');
            $chart->setWidth(280);
            $chart->setHeight(100);
            $chart->setPointsNumber(60);
            $chart->setRawResults($host_public["latest_results"]);
            $chart->generateScript();
            echo "<div class=\"chart_small\">";
            echo "<p class=\"micro_text\">";
            echo "Last 2 hours<br>Average " . round($chart->getAverage(), 2) . "ms. - ";
            echo "Last check " . (time() - $host_public["last_check_ts"]) . " seconds ago</p>";
            echo $chart->getScript();
            echo "</div>";
        }
        //var_dump($host_public);
    }


    //var_dump($host_searched);
    ?>

</div>










