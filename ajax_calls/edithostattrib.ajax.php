<?php
if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}
$arr_result["error"] = true;
$arr_result["error_descr"] = "Unknown error, default error message";
$now = time();
//CHECK IF THE REQUESTED ATTRIBUTE IS ALLOWED
$allowed_attribs = ["hp", "public", "enabled", "delete"];
if (array_search($uriobj->getParam(3), $allowed_attribs) === false) {
    echo "Attrib not allowed here";

    return;
}
//CHECK IF TOKEN EXISTS AND USER IS THE OWNER
$sql = "SELECT * FROM hosts WHERE public_token = :public_token AND id_user = :id_user;";
$stmt = $mydbh->prepare($sql);
if ($stmt === false) {
    $arr_result["error_descr"] = "Unable to prepare statement to verify ownership";
    die (json_encode($arr_result));
}
$ret = $stmt->execute(["public_token" => $uriobj->getParam(2), "id_user" => user::getID()]);
if ($ret === false) {
    $arr_result["error_descr"] = "Unable to execute statement to verify ownership";
    die (json_encode($arr_result));
}
if ($stmt->rowCount() == 0) {
    $arr_result["error_descr"] = "Monitor not found";
    die (json_encode($arr_result));
}
$row_host = $stmt->fetch(PDO::FETCH_ASSOC);
//OK!!! LET'S GO....
if ($uriobj->getParam(3) == "hp") {
    $new_value = $uriobj->getParam(4);
    if ($new_value < 0 or $new_value > 1) {
        $arr_result["error_descr"] = "Value passed is not correct";
        die (json_encode($arr_result));
    }
    if ($row_host["homepage"] == $new_value) {
        $arr_result["error"] = false;
        $arr_result["error_descr"] = "No need to change!";
        die (json_encode($arr_result));
    }
    $sql = "update hosts set homepage = $new_value, edited_ts = $now where id = " . $row_host["id"] . " limit 1;";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while updating value";
        die (json_encode($arr_result));
    }
    $arr_result["error"] = false;
    die (json_encode($arr_result));
} //END OF HP ATTRIB
if ($uriobj->getParam(3) == "public") {
    $new_value = $uriobj->getParam(4);
    if ($new_value < 0 or $new_value > 1) {
        $arr_result["error_descr"] = "Value passed is not correct";
        die (json_encode($arr_result));
    }
    if ($row_host["public"] == $new_value) {
        $arr_result["error"] = false;
        $arr_result["error_descr"] = "No need to change!";
        die (json_encode($arr_result));
    }
    $sql = "update hosts set public = $new_value, edited_ts = $now  where id = " . $row_host["id"] . " limit 1;";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while updating value";
        die (json_encode($arr_result));
    }
    $arr_result["error"] = false;
    die (json_encode($arr_result));
} //END OF PUBLIC ATTRIB
if ($uriobj->getParam(3) == "delete") {
    //CHECK IF HOST IS RUNNING
    if ($row_host["check_running"] == 1) {
        $arr_result["error_descr"] = "Monitor is running please wait few seconds and try again.";
        die (json_encode($arr_result));
    }
    //BEGIN TRANSACTION
    $mydbh->beginTransaction();
    //DISABLE HOST BEFORE DELETING
    if ($row_host["enabled"] == 1) {
        $sql = "UPDATE hosts SET enabled=0 WHERE id= " . $row_host["id"] . " AND check_running = 0 LIMIT 1;";
        $ret = $mydbh->query($sql);
        if ($ret === false) {
            $arr_result["error_descr"] = "Error while trying to disable host before deleting";
            die (json_encode($arr_result));
        }
        if ($ret->rowCount() == 0) {
            $arr_result["error_descr"] = "Unable to disable host before deleting.";
            die (json_encode($arr_result));
        }
    }
    //PROCEED TO DELETE
    $sql = "DELETE FROM host_checks_schedule WHERE id_host = " . $row_host["id"] . "; ";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while deleting host checks schedule";
        die (json_encode($arr_result));
    }
    $sql = "DELETE FROM results_latest_24h WHERE id_host = " . $row_host["id"] . "; ";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while deleting results latest 24h";
        die (json_encode($arr_result));
    }
    $sql = "DELETE FROM results_daily WHERE id_host = " . $row_host["id"] . "; ";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while deleting results daily";
        die (json_encode($arr_result));
    }
    $sql = "DELETE FROM results WHERE id_host =  " . $row_host["id"] . "; ";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while deleting host results";
        die (json_encode($arr_result));
    }
    $sql = "DELETE FROM hosts WHERE id = " . $row_host["id"] . " LIMIT 1;";
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $arr_result["error_descr"] = "Error while deleting host";
        die (json_encode($arr_result));
    }
    //COMMIT TRANSACTION
    $mydbh->commit();
    $arr_result["error"] = false;
    die (json_encode($arr_result));
} //END OF PUBLIC ATTRIB
if ($uriobj->getParam(3) == "enabled") {
    if ($row_host["enabled"] == $uriobj->getParam(4)) {
        $arr_result["error"] = false;
        $arr_result["error_descr"] = "No need to change!";
        die (json_encode($arr_result));
    }
    $new_status = intval($uriobj->getParam(4));
    if ($new_status < 0 or $new_status > 1) {
        $arr_result["error_descr"] = "New status in not valid";
        die (json_encode($arr_result));
    }
    if ($new_status == 1) {
        $new_status_txt = "RESUME";
    } else {
        $new_status_txt = "PAUSE";
    }
    $sql = "update hosts set enabled = $new_status where id = " . $row_host["id"] . "; ";
    //Create host object
    $host = new host_manager($mydbh);
    $mydbh->beginTransaction();
    $ret = $mydbh->query($sql);
    if ($ret === false) {
        $mydbh->rollBack();
        $arr_result["error_descr"] = "Error during status update";
        die (json_encode($arr_result));
    }
    $ret = $host->logHostEvent($uriobj->getParam(2), $new_status_txt);
    if ($ret === false) {
        $mydbh->rollBack();
        $arr_result["error_descr"] = "Error during host log creation";
        die (json_encode($arr_result));
    }
    //COMMIT TRANSACTION
    $mydbh->commit();
    $arr_result["error"] = false;
    die (json_encode($arr_result));
} //END OF ENABLED ATTRIB



