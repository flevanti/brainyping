<?php
//Default values
$result["result"] = false;
$result["error_descr"] = "Unknown error (default)";
if (!isset($_POST["pt"])) {
    $result["result"] = false;
    $result["error_descr"] = "Unable to find POST values";
    echo json_encode($result);
    exit;
}
try {
    $pt = json_decode($_POST["pt"], true);
} catch (Exception $Ex) {
    $result["error_descr"] = "Error decoding Public Tokens JSON on server";
    echo json_encode($result);
    exit;
}
if (!is_array($pt)) {
    $result["error_descr"] = "Public Tokens are not an array on server";
    echo json_encode($result);
    exit;
}
//So we have an array of alphanumeric string elements...
//We need to check that string are fine because we are going to use them in the query
//this to avoid SQL inj.
//we will check them and add ' at the beginning/end of each element so we could use them in the query later...
foreach ($pt as $key => $value) {
    //Check if the element is alnum only
    if (ctype_alnum($value) === false) {
        $result["error_descr"] = "Public tokens contain invalid characters";
        echo json_encode($result);
        exit;
    }
    //check ok...
    //Add '
    $pt[$key] = "'" . $value . "'";
} //END FOREACH
//So..... array is ok, values are ok....
//Create a string to be used in the query
$pt_string = implode(",", $pt);
//Create the query....
//FIND HOST TO SHOW.....
$sql = "select h.*, ct.descr check_descr, ct.fa_icon check_icon from hosts h, check_types ct
                where h.check_type = ct.id
                  and h.homepage = 1
                    and h.enabled = 1
                      and h.check_running = 0
                      and public_token not in ($pt_string)
                  order by h.check_started_ts desc limit 1;";
//echo $sql;
$host_hp = $mydbh_web->query($sql);
if ($host_hp === false) {
    $result["error_descr"] = "New tile query failed";
    echo json_encode($result);
    exit;
}
//IF NO RECORDS FOUND,.... BYE BYE
if ($host_hp->rowCount() == 0) {
    $result["result"] = true;
    $result["script"] = "NOTFOUND";
    echo json_encode($result);
    exit;
}
$value = $host_hp->fetch(PDO::FETCH_ASSOC);
$result["result"] = true;
$result["script"] = hp_tile::get_script($value["host"],
                                        $value["public_token"],
                                        $value["title"],
                                        $value["check_icon"],
                                        $value["check_descr"],
                                        $value["check_started_ts"],
                                        $value["last_check_avg"],
                                        $value["check_result"],
                                        $value["latest_results"]);
echo json_encode($result);
exit;
