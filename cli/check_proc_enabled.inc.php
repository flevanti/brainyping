<?php
function check_proc_enabled ($proc_name) {

    global $mydbh;

    $sql = "select proc_name from bg_proc_last_exec where proc_name = '$proc_name' and enabled=1;";

    $rs = $mydbh->query($sql);

    if ($rs===false or $rs->rowCount()==0) {
        return false;
    }

    return true;

}