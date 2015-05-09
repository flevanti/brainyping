<?php
return;
try {
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;',
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
        PDO::MYSQL_ATTR_COMPRESS => true
    );
    $mydbh = new PDO("mysql:host=" . _MYSQL_HOST_ . ";port="._MYSQL_PORT_.";dbname="._MYSQL_DBNAME_.";charset=utf8",_MYSQL_USER_,_MYSQL_PWD_,$options);
    //STRICT MODE TO BE SURE ON EVERY INSERT/UPDATE....
    $mydbh->query("set sql_mode = 'STRICT_ALL_TABLES';");
    //CHARSET
    $mydbh->query("SET NAMES 'utf8';");
    $mydbh->query("SET CHARACTER SET 'utf8';");
    $mydbh->query("SET time_zone = '+1:00';");
    $mydbh->query("SET @db_id = '". _MYSQL_DB_ID_ ."'");

} catch (Exception $ex) {

    $mydbh = false;
    $mydbh_ex = $ex;
    return;
} //END OF TRY/CATCH


