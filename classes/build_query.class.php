<?php
class build_query {

    static function getStatementBindVars ($arr = array(), $insertOrUpdate="INSERT") {

        $arr_return = array();
        $temp_array = array();

        if ($insertOrUpdate == "INSERT") {

            //CREATE AN ASSOCIATIVE ARRAY WITH 3 ELEMENTS:
            //key 0: fields = 'field1, field2, field3, ...)
            //key 1: bind_names = ':field1, :field2, :field3, ...)
            //key 2: bind_values = associative array with keys = field1, field2, field2, ...
            foreach ($arr as $key=>$value) {
                $temp_array["fields"][] = $key;
                $temp_array["bind_names"][] = ":" . $key;
                $temp_array["bind_values"][$key] = $value;
            } //END FOREACH

            $arr_return["fields"] = implode(", ",$temp_array["fields"]);
            $arr_return["bind_names"] = implode(",",$temp_array["bind_names"]);
            $arr_return["bind_values"] = $temp_array["bind_values"];

            return $arr_return;

        } //END IF "INSERT"


        if ($insertOrUpdate == "UPDATE") {

            //CREATE AN ARRAY WITH 2 ELEMENT:
            //key 0: fields_and_bind_names = 'field1 = :field1, field2 = :field2, field3 = :field3, ...'
            //key 1: bind_values = associative array with keys = field1, field2, field2, ...
            foreach ($arr as $key=>$value) {
                $temp_array["fields_and_bind_names"][] = $key . " = :" . $key ;
                $temp_array["bind_values"][$key] = $value;
            } //END FOREACH

            $arr_return["fields_and_bind_names"] = implode(", ",$temp_array["fields_and_bind_names"]);
            $arr_return["bind_values"] = $temp_array["bind_values"];

            return $arr_return;

        } //END IF "UPDATE"

        //WE SHOULDN'T BE HERE.....  :)
        return false;

    } //END METHOD

} //END CLASS