<?php

if (user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}


echo "<h4>Aggregate Dashboard</h4>";


$today_code = date("Ymd");


//Look for results that need to be aggregated...
$sql = "SELECT   daycode, count(*) AS num_results
            FROM     results
            WHERE    aggregated = 0
            GROUP BY daycode
            ORDER BY daycode ASC;";

$rs = $mydbh->query($sql);

$num_rec = $rs->rowCount();

echo "$num_rec days found waiting to be aggregated:<br><br>";

while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
    echo "daycode " . $row["daycode"] . " has " . $row["num_results"] . " results ";
    if ($row["daycode"] != $today_code) {
        echo "<a href=\"" . $uriobj->URI_gen(["aggregatedashboard", $row["daycode"], time()]) . "\">Aggregate now</a>";
    } else {
        echo "(Current day)";
    }
    echo "<br>";
}

?>
<br><br><br>
<div class="alert alert-info">Please be aware that aggregation requires resources and time, please be patient</div>




