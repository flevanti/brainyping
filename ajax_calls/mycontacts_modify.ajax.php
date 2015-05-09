<?php
if (user::getRole() != "USER" and  user::getRole() != "ADMIN" ) {
    echo "Looks like you're not authorized, sorry!";
    return;
}

$arr_result["error"] = true;
$arr_result["error_descr"] = "";
$arr_result["result"] = "";

if ($uriobj->getParam(3)=== false) {
    $arr_result["error_descr"] = "Missing parameters";
    goto fireOutput;
}

$allowed_actions = ["enable", "disable","delete", "unlinkmonitor","unlinksubs"];
if (array_search($uriobj->getParam(2),$allowed_actions)=== false) {
    $arr_result["error_descr"] = "Action not allowed here";
    goto fireOutput;
}


if (!is_numeric($uriobj->getParam(3))) {
    $arr_result["error_descr"] = "Contact ID not correct";
    goto fireOutput;
}



//SO HERE WE ARE !!! ... WE HAVE AN ALLOWED ACTION AND A NUMERIC ID

//ENABLE CONTACT
if ($uriobj->getParam(2)=="enable") {
    $sql = "update user_contacts set enabled=1
                where id = :id
                  and id_user = :id_user
                  and validated = 1;";
    $arr_query = ["id"=>$uriobj->getParam(3), "id_user"=>user::getID()];
}


//DISABLE CONTACT
if ($uriobj->getParam(2)=="disable") {
    $sql = "update user_contacts set enabled=0
                where id = :id
                  and id_user = :id_user
                  and validated = 1;";
    $arr_query = ["id"=>$uriobj->getParam(3), "id_user"=>user::getID()];
}

//DELETE CONTACT
//FOREIGN KEY WILL DELETE CONTACT-HOSTS ASSOCIATIONS, IT HAS CASCADE ATTRIBUTE
if ($uriobj->getParam(2)=="delete") {
    $sql = "delete from user_contacts
                where id = :id
                  and id_user = :id_user;";
    $arr_query = ["id"=>$uriobj->getParam(3), "id_user"=>user::getID()];
}


//UNLINK CONTACT - HOSTS
if ($uriobj->getParam(2)=="unlinkmonitor") {
    $sql = "delete from host_contacts where id_contact in (select id
                                                            from user_contacts
                                                              where id_user=:id_user
                                                                  and id=:id
                                                        );";
    $arr_query = ["id"=>$uriobj->getParam(3), "id_user"=>user::getID()];
}


//UNLINK CONTACT - SUBSCRIPTIONS
if ($uriobj->getParam(2)=="unlinksubs") {
    $sql = "delete from host_subscriptions where id_user_contact in (select id
                                                            from user_contacts
                                                              where id_user=:id_user
                                                                  and id=:id
                                                        );";
    $arr_query = ["id"=>$uriobj->getParam(3), "id_user"=>user::getID()];
}


$stmt = $mydbh->prepare($sql);
$ret = $stmt->execute($arr_query);

if ($ret=== false) {
    $arr_result["error_descr"] = "Statement execution failed (".$uriobj->getParam(2).")";
    goto fireOutput;
}


if ($stmt->rowCount() == 0) {
    $arr_result["error_descr"] = "Looks like the statement did not affect any record! (".$uriobj->getParam(2).")";
    goto fireOutput;
}

$arr_result["error"] = false;




fireOutput:
die(json_encode($arr_result));