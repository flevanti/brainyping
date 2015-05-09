<?php

if (user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";
    return;
}


set_time_limit(0);
echo "<h4>Aggregation procedure</h4>";
echo "<p class=\"micro_text\"><a href=\"". $uriobj->URI_gen(["aggregatedashboard"])."\">Back to Aggregation Dashboard</a></p><br><br>";


set_time_limit(240);

$aggregate_obj = new aggregate();
$aggregate_obj->setMaxExecutionTime(3);
$aggregate_obj->aggregate();



?>

<div class="alert alert-info">Aggregation process completed or execution time reached</div>

