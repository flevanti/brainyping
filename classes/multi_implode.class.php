<?php

/**
 * Class multi_implode
 *
 * This class is used to implode nested arrays
 * We could now use array_map but it is here, it works..
 */
class multi_implode {

    static function go($glue, $array, $include_key = false) {
        $ret = '';
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $ret .= self::go($glue, $item, $include_key) . $glue;
            } else {
                $ret .= ($include_key === true ? $key . ":" : "") . $item . $glue;
            }
        }
        $ret = substr($ret, 0, 0 - strlen($glue));

        return $ret;
    }
}