<?php


    echo "<p class=\"text-muted\">";
    echo "Page served id " . round((microtime(true) - $boot_time),4) . " seconds. ";
    echo "Server time " . date("d M Y H:i:s") . " - (" . time() . ")<br>";
    echo "Developer: Francesco Levanti <a href=\"/bgmonitor\">System Services Status</a> <a href=\"/stats\">Stats</a>";
    echo "<br>";
    echo "DB CONNECTIONS: ";
    if (isset($mydbh)) {
        echo "[engine] ";
    }
    if (isset($mydbh_web)) {
        echo "[web] ";
    }
    echo "</p>";
