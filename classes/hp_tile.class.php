<?php

class hp_tile {

    static $down_ribbon = '<img style="margin:5px; position: absolute; bottom: 0; right: 0; border: 0; z-index: 1000;"
                            src="/imgs/down_ribbon.png" > ';

    static $star_subscribe = '';

    static function get_script($host, $public_token, $title, $check_icon, $check_descr, $check_started_ts, $last_check_avg, $check_result, $latest_results) {
        $title_in_link = urlencode(str_replace(" ", "-", $title));
        $title = (strlen($title) > 20 ? substr($title, 0, 18) . ".." : $title);
        $script = "";
        $script .= "<div class=\"container_host_hp_interno\" host=\"" . $host . "\" host_public_token = \"" . $public_token . "\" onClick=\"host_info(this)\">";
        $script .= "<h3>";
        $script .= "<img src='https://www.google.com/s2/favicons?domain=" . $host . "' alt='' class='favico' /> ";
        $script .= "<a href=\"/info/" . $public_token . "/" . $title_in_link . "/\">" . t2v($title) . "</a>";
        $script .= "</h3>";
        $script .= "<p class='last_time_checked'>";
        $script .= "<span class =\"fa " . $check_icon . "\" title=\"" . $check_descr . "\"></span> ";
        $script .= "<span class=\"elapsed_seconds\">" . (time() - $check_started_ts) . "</span>s ago - ";
        $script .= "Reply " . $last_check_avg . "ms";
        $script .= "</p>";
        //CHECK IF THE HOST IS DOWN...
        if ($check_result == "NOK") {
            //Show DOWN ribbon
            $script .= self::$down_ribbon;
        }
        $script .= "<p data-toggle=\"tooltip\" data-placement=\"top\" data-original-title=\"SEND ME AN EMAIL WHEN DOWN!\" class=\"tile_star_subscribe fa fa-star activate_tooltip\" host_public_token = \"" . $public_token . "\"></p>";
        if ($latest_results != "") {
            //Retrieve xx results from the "latest result" string
            $values = host_manager::getLatestResultsArray($latest_results, 75);
            //Create the response time chart
            $script .= "<span class=\"peity_hp_response_wrapper\"><span class=\"peity_hp_response waiting_render\">" . implode(",", $values) . "</span></span>";
            //Retrieve xx results from the "latest result" string converted to 1 or -1 (UP or DOWN)
            //$values = $host_manager->getLatestReultsArrayOnOff($value["latest_results"],75);
            //Create the status chart
            //$script .= "<span class=\"peity_hp_status_wrapper\"><span class=\"peity_hp_status\">". implode(",",$values)."</span></span>";
        }
        $script .= "</div>";

        return $script;
    }

    static function get_script_NOK_host($host, $title, $public_token, $check_icon) {
        $title_in_link = urlencode(str_replace(" ", "-", $title));
        $script = "";
        $script .= "<span >";
        $script .= "<img src='https://www.google.com/s2/favicons?domain=" . $host . "' alt='' class='favico' /> ";
        $script .= "<a href=\"/info/$public_token/" . $title_in_link . "\">" . t2v($title) . "</a>";
        $script .= "</span> &nbsp;";

        return $script;
    }
}