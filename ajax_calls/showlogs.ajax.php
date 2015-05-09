<?php


if ( user::getRole() != "ADMIN" ) {
    //echo "Looks like you're not authorized, sorry!";
    //return;
}

$result_arr["result"] = false;
$result_arr["error_descr"] = "";


$sid = $uriobj->getParam(2);

if ($sid === false) {
    $result_arr["error_descr"] = "Parameter not found in URL";
    die(json_encode($result_arr));
}

$sql = "select * from logs where session_id = :sid order by log_seq;";

$stmt = $mydbh_web->prepare($sql);
$r = $stmt->execute(["sid"=>$sid]);

if ($r===false) {
    $result_arr["error_descr"] = "Query failed!";
    die(json_encode($result_arr));
}

$result_arr["logs"] = "LOG ENTRIES:<br>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $result_arr["logs"] .= date("d/m/Y H:i:s",$row["date_ts"]) . " - " . t2v($row["log_note"]) . "<br>";
}

$result_arr["logs"] .= "----------------------------------<br>";
$result_arr["logs"] .= "End of log<br>";


$result_arr["result"] = true;

die(json_encode($result_arr));