<?php
return;
try {
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;',
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        PDO::MYSQL_ATTR_COMPRESS     => true
    );
    $mydbh_web = new PDO("mysql:host=" . _MYSQL_HOST_WEB_ . ";port=" . _MYSQL_PORT_WEB_ . ";dbname=" . _MYSQL_DBNAME_WEB_ . ";charset=utf8", _MYSQL_USER_WEB_, _MYSQL_PWD_WEB_, $options);
    //STRICT MODE TO BE SURE ON EVERY INSERT/UPDATE....
    $mydbh_web->query("set sql_mode = 'STRICT_ALL_TABLES';");
    //CHARSET
    $mydbh_web->query("SET NAMES 'utf8';");
    $mydbh_web->query("SET CHARACTER SET 'utf8';");
    $mydbh_web->query("SET time_zone = '+1:00';");
    $mydbh_web->query("SET @db_id = '" . _MYSQL_DB_ID_WEB_ . "';");
} catch (Exception $ex) {
    $mydbh_web = false;
    $mydbh_web_ex = $ex;

    return;
} //END OF TRY/CATCH


