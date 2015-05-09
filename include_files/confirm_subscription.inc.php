<?php


echo "<h3>ALERT MONITOR</h3><br><br><br>";



$ret = $host_manager->addSubscription($uriobj->getParam(1));

if ($ret === false) {
    echo $host_manager->last_error;
    return;
}

echo "Alert Monitor successfully activated.";

