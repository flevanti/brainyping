<?php
if (user::getRole() != "USER" and  user::getRole() != "ADMIN" ) {
    echo "Looks like you're not authorized, sorry!";
    return;
}



if ($_POST) {
    $result = $host_manager->bulkAction($_POST);
    if ( $result === true) {
        echo json_encode(["result"=>true]);
    } else {
        //OPERATION WENT WRONG
        $result["result"] = false;
        echo json_encode($result);
    }
}

