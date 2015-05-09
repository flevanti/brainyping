<?php
class db_connect {

    static function connect($conn_settings = array()) {

        $charset = "utf8mb4";

        try {
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset;",
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::MYSQL_ATTR_COMPRESS => true
            );
            $mydbh = new PDO("mysql:host=" . $conn_settings["host"] . ";port=".$conn_settings["port"].";dbname=".$conn_settings["dbname"].";charset=$charset",$conn_settings["user"],$conn_settings["pwd"],$options);
            //STRICT MODE TO BE SURE ON EVERY INSERT/UPDATE....
            $mydbh->query("set sql_mode = 'STRICT_ALL_TABLES';");
            //CHARSET
            $mydbh->query("SET NAMES '".$charset."';");
            $mydbh->query("SET CHARACTER SET '".$charset."';");
            $mydbh->query("SET time_zone = '+1:00';");
            $mydbh->query("SET @db_id = '". $conn_settings["db_id"] ."'");

        } catch (Exception $ex) {

            $mydbh = false;
            echo  $ex->getMessage();
        } //END OF TRY/CATCH

        return $mydbh;

    }


}