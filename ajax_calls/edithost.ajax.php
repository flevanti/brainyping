<?php

if (user::getRole() != "USER" and user::getRole() != "ADMIN") {
    echo "Looks like you're not authorized, sorry!";

    return;
}

$host_manager_edit = new host_manager_edit();

$host_manager_edit->saveData();

$host_manager_edit->writeJsonResult();

?>
