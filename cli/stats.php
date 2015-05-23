<?php
//THIS IS A SIMPLE FILE JUST TO PERFORM SOME TIMESTAMP CHECK WHEN CREATING A NEW ENGINE...
//NO PRODUCTION USE IS PLANNED.... :)
$process_name = "CLISTATS";
require_once 'cli_common.php';
echo "\n\n\n\n";
echo "PHP\n";
echo "Date and time: " . date("d/m/Y H:i:s") . "\n";
echo "Timestamp: " . time() . "\n";
echo "Timezone: " . date("P / e") . "\n";
echo "MYSQL\n";
$sql = "select date_format(now(), '%d/%m/%Y %T') as t
                                , UNIX_TIMESTAMP() as ts
                                , @@global.time_zone as glo_tz
                                , @@session.time_zone as ses_tz
                                , @@system_time_zone sys_tz;";
$row = $mydbh->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "Date and time: " . $row["t"] . "\n";
echo "Timestamp: " . $row["ts"] . "\n";
echo "Global timezone: " . $row["glo_tz"] . "\n";
echo "Session timezone: " . $row["ses_tz"] . "\n";
echo "System timezone: " . $row["sys_tz"] . "\n";

