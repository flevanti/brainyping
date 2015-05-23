<?php
echo "Date creation:<br><br>";
$d = new DateTime();
$tz = $d->getTimezone();
echo "timezone: " . $tz->getName() . " " . ($tz->getOffset($d) / 3600) . "<br>";
echo "timestamp: " . ts_to_date(time()) . " - " . time() . "<br><br>";
echo "set timezone to America/New_York<br><br>";
date_default_timezone_set('America/New_York');
$d = new DateTime();
$tz = $d->getTimezone();
echo "timezone: " . $tz->getName() . " " . ($tz->getOffset($d) / 3600) . "<br>";
echo "timestamp: " . ts_to_date(time()) . " - " . time() . "<br><br>";


