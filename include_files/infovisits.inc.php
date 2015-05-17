<?php
echo "<h2>VISITS COUNTER INFO</h2>";

$n_hosts = 10; // number of host to show in the most visited list
$n_hosts_last = 15; //numer of host to show in the last visited list

$sql = "SELECT
                h.host,
                h.check_type,
                h.title,
                h.public,
                h.id_user,
                h.last_check_ts,
                COUNT(*) AS nreq,
                MAX(hvc.ts) AS last_visit_ts
            FROM
                hosts_visits_counter hvc,
                hosts h
            WHERE
                h.id = hvc.id_host
                and (h.public=1 or h.id_user=". (user::getID()===false?0:user::getID()) .")
            GROUP BY h.host , h.check_type , h.title, h.public, h.id_user, h.last_check_ts
            ORDER BY nreq DESC
            LIMIT $n_hosts;";

$rs = $mydbh_web->query($sql)->fetchAll(PDO::FETCH_ASSOC);

//If recordset is empty return....
if ($rs === false || count($rs)==0) {
    echo "STATISTICS NOT AVAILABLE";
    return;
}
echo "<h4>MOST VISITED</h4>";
foreach ($rs as $k=>$row) {
    echo ($row["id_user"]==user::getID()?"<i class='fa fa-user'></i> ":""); //Add an Icon if user is owner
    echo "<b>" . $k . "</b>. " . $row["title"] . " (" . $row["host"] . ") " . $row["check_type"] . " (" . $row["nreq"]. ")<br>";
    echo "Last check: " . ts_to_date($row["last_check_ts"]) . "<br>";
    echo "Last visit: " . ts_to_date($row["last_visit_ts"]) . "<br><br>";
}

$sql = "SELECT
            h.host,
            h.check_type,
            h.title,
            h.public,
            h.id_user,
            h.last_check_ts,
            hvc.ts last_visit_ts
        FROM
            hosts_visits_counter hvc,
            hosts h
        WHERE
            h.id = hvc.id_host
            and (h.public=1 or h.id_user=". (user::getID()===false?0:user::getID()) .")
        ORDER BY ts DESC
        LIMIT $n_hosts_last;";

$rs = $mydbh_web->query($sql)->fetchAll(PDO::FETCH_ASSOC);

//If recordset is empty return....
if ($rs === false || count($rs)==0) {
    return;
}
echo "<h4>LAST VISITED</h4>";
foreach ($rs as $k=>$row) {
    echo ($row["id_user"]==user::getID()?"<i class='fa fa-user'></i> ":""); //Add an Icon if user is owner
    echo $row["title"] . " (" . $row["host"] . ") " . $row["check_type"] . "<br>";
    echo "Last check: " . ts_to_date($row["last_check_ts"]) . "<br>";
    echo "Last visit: " . ts_to_date($row["last_visit_ts"]) . "<br><br>";
}
