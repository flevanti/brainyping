<?php

if (user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";
    return;
}

echo "<h4>TOOLS TO UPDATE FAVICONS</h4>";
echo "This tool update favicons for hosts with port 80 configured (HTTP)<br><br>";

if ($uriobj->getParam(1)===false or $uriobj->getParam(1) != "go") {

        echo "<a href=\"".$uriobj->URI_gen([$uriobj->getParam(0),"go"])."\">GENERATE FAVICONS</a>";
        return;

}



$favicons_folder = "favicons";

if (!file_exists($favicons_folder)) {
    mkdir($favicons_folder);
    echo "'favicons' folder not found - created<br>";
} else {
    echo "'favicons' folder found<br>";
}

echo "Query DB for hosts: ";

$sql = "select host from hosts where enabled = 1 and port=80;";
$rs = $mydbh->query($sql);

$n_hosts= $rs->rowCount();

echo  "$n_hosts found<br>";

set_time_limit(500);
$i=0;
while ($value = $rs->fetch(PDO::FETCH_ASSOC)) {
    $i++;

    $host_link = "http://www.google.com/s2/favicons?domain=" . $value["host"];
    $filename = $favicons_folder . "/favicon." . $value["host"] . ".png";

    echo "Working on host " . $value["host"] ." ($i/$n_hosts) - ";
    echo " filename: " . $filename . " - downloading ... ";

    $result = DownloadFile($host_link,$filename);

    if ($result===true) {
        echo "<span class=\"label label-success\">OK</span>";
    } else {
        echo "<span class=\"label label-danger\">FAILED</span> ($result)";
    }

    echo "<br>";
    flush();

} //END WHILE....




