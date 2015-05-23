<?php

echo "<h3>SYSTEM MONITOR</h3>";
$bgmonitor = new bgmonitor($mydbh_web);
$rs = $bgmonitor->getMonitorList();
if ($rs === false) {
    echo $bgmonitor->last_error;
    echo "<br>";
    echo $bgmonitor->last_error_tech;

    return;
}
while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
    //Check if the process is in delay....
    $delay = ($row["minutes_alert"] * 60) + $row["date_ts"] < time() ? 1 : 0;
    $last_execution = calculate_time(time() - $row["date_ts"], "STRING");
    echo "<div class=\"sysmon_container delay_$delay col-lg-3 col-md-4 col-sm-6 col-xs-12\">";
    echo "<h4>" . $row["proc_name"] . "</h4>";
    echo "Last execution: " . $row["date_str"] . "<br>";
    echo "<span class=\"micro_text\">(";
    if ($last_execution == "") {
        echo "Right now";
    } else {
        echo $last_execution . "ago";
    }
    echo ")</span><br>";
    echo "Execution time: " . $row["execution_time"] . " seconds<br>";
    $arr_alert_delay = calculate_time($row["minutes_alert"] * 60);
    echo "Alert delay:  ";
    foreach ($arr_alert_delay as $key => $value) {
        if ($value > 0) {
            echo "$value$key ";
        }
    } //END FOREACH
    echo "<br>";
    echo "Alert ";
    if ($row["alert_sent"] == 0) {
        echo "not sent";
    } else {
        $arr_alert_sent = calculate_time(time() - $row["alert_sent"]);
        echo "sent ";
        foreach ($arr_alert_sent as $key => $value) {
            if ($value > 0) {
                echo "$value$key ";
            }
        } //END FOREACH
        echo "ago";
    }
    echo "</div>";
}



