<?php

set_time_limit(1200);
echo "<h4>GENERATE CHECKS SCHEDULE</h4>";
$ret = $host_manager->createHostCheckScheduleALL();
if ($ret === false) {
    echo "Found an error:<br>";
    echo $host_manager->last_error;
    email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, _APP_DEFAULT_EMAIL_CONTACTS_RECIPIENT_, "GENCHECKS ERROR", $host_manager->last_error);
}
echo "Process terminated";