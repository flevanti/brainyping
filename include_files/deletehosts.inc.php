<?php


echo "REMOVE DELETED HOSTS<br><br>";
$arr_failed = $host_manager->removeDeletedHosts();
if (count($arr_failed) > 0) {
    echo "The following HOST couldn't be removed:<br>";
    foreach ($arr_failed as $value) {
        echo "HOST # " . $value . "<br>";
    }
} else {
    echo "All hosts in the queue has been removed";
}



