<?php
echo "<div class=\"footer\"><div class=\"container\"><p class=\"text-muted\">";
echo "Page served id " . round((microtime(true) - $boot_time), 4) . " seconds. ";
echo "Server time " . date("d M Y H:i:s") . " - (" . time() . ")<br>";
echo "Developer: Francesco Levanti <a href=\"/bgmonitor\">System Services Status</a> <a href=\"/stats\">Stats</a> <a href=\"/infovisits\">Visits</a>";
echo "&nbsp;&nbsp;&nbsp;";
echo "<a href=\"https://github.com/flevanti/brainyping\" target='_blank' title=\"Brainyping on GitHub\" alt=\"Brainyping on GitHub\"><img class=\"github_footer_logo\" src=\"imgs/github12x12.png\"></a>";
echo "<br>";
echo "DB CONNECTIONS: ";
if (isset($mydbh)) {
    echo "[engine] ";
}
if (isset($mydbh_web)) {
    echo "[web] ";
}



echo "</p></div></div>";
