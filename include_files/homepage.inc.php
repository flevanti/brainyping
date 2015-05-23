<?php
//FIND HOST TO SHOW.....
$query_limit = 12;
$sql = "select h.*, ct.descr check_descr, ct.fa_icon check_icon
            from hosts h, check_types ct
                where h.check_type = ct.id
                  and h.homepage = 1
                    and h.enabled = 1
                      and h.check_running = 0
                  order by h.check_started_ts desc limit  $query_limit";

$hosts_hp = $mydbh_web->query($sql);

//IF NO RECORDS FOUND,.... BYE BYE
if ($hosts_hp->rowCount() == 0) {
    return;
}

//RECORDS FOUND
echo "<span class=\"micro_text\" id=\"tile_refreshing_microtext\">[ TILES REFRESHING ON ]</span>";

echo "<div class='row' id=\"tiles_container\">";


while ($value = $hosts_hp->fetch(PDO::FETCH_ASSOC)) {
    echo "<div class=\"container_host_hp  col-lg-3 col-md-4 col-sm-6 col-xs-12\">";
    //PREPARE TILE SCRIPT....AND ECHO IT...
    echo hp_tile::get_script($value["host"],
                             $value["public_token"],
                             $value["title"],
                             $value["check_icon"],
                             $value["check_descr"],
                             $value["check_started_ts"],
                             $value["last_check_avg"],
                             $value["check_result"],
                             $value["latest_results"]);
    echo "</div>";
} //WHILE END
echo "</div>";


//SHOW LAST MONITOR FAILED.... IF ANY
$sql = "SELECT h.*, ct.descr check_descr, ct.fa_icon check_icon
            FROM hosts h, check_types ct
                WHERE h.check_type = ct.id
                  AND h.homepage = 1
                    AND h.enabled = 1
                    AND h.check_result = 'NOK'
                      AND h.check_running = 0
                  ORDER BY h.check_started_ts DESC;";

$rs = $mydbh_web->query($sql);

if ($rs !== false and $rs->rowCount() > 0) {
    echo "<div id=\"hp_failing_list\">";
    echo "<h5>MONITOR FAILING RIGHT NOW:</h5>";
    echo "<p >";
    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
        echo hp_tile::get_script_NOK_host($row["host"], $row["title"], $row["public_token"], $row["check_icon"]);
    }
    echo "</p>";
    echo "</div>";
}


echo "<div>";


echo "</div>"




?>
<script>


refresh_tiles_flag = true;
refresh_seconds_flag = true;
seconds_refresh = 6;

pt = [];

function tilesTokensInArray () {
    return $ (".container_host_hp_interno").map (function () {
        return $ (this).attr ("host_public_token");
    }).get ();
}

$ ('div#tiles_container').hover (function () {
    refresh_tiles_flag = false;
    $ ('span#tile_refreshing_microtext').html ('[ TILES REFRESHING OFF ]');
}, function () {
    refresh_tiles_flag = true;
    $ ('span#tile_refreshing_microtext').html ('[ TILES REFRESHING ON ]');
});

function refresh_tiles () {

    if (refresh_seconds_flag) {
        //console.log('add seconds flag true');
        //console.log('add seconds process finished');
        addSeconds ();
    }

    if (refresh_tiles_flag) {
        //console.log('new tile flag true');
        //console.log('set timer to add new tile in a very short time.');
        //console.log('new tile request timer set, bye.');
        setTimeout (new_tile_please, 2000);
    }

}

function addSeconds () {
    var i = 0;
    objs = $ ('span.elapsed_seconds');
    objs.fadeTo (250, 0.5, function () {
        $ (this).each (function () {
            i++;
            obj = $ (this);
            //console.log('obj--------------------' + i);
            //console.log('seconds found: ' + $(obj).text());
            //console.log('seconds after: ' + $(obj).text());
            $ (obj).text (parseInt ($ (obj).text ()) + seconds_refresh);

        });
    });
    objs.fadeTo (750, 1);

    return true;
}

function tile_slide (script) {

    //ready to shift

    //First of all we disable tooltips (IMPORTANT!)
    //This is to avoid that copied tooltips will lose their handler link and will remain shown forever...
    $ ('p').tooltip ('hide');

    //Then.... the new tile code is put in the variable used in the loop....
    code_to_move = script;

    var i = 0;
    $ ('.container_host_hp').each (function () {
        i++;
        //console.log('sliding tile #' + i );
        //Put the tile code in a temp variable...
        temp = $ (this).html ();
        //We create a FadeIN effect on the first tile to give the idea of adding a new one...
        //frills.... :)
        if (i == 1) {
            $ (this).fadeTo (0, 0).html (code_to_move).fadeTo (1000, 1);
        }
        else {
            $ (this).html (code_to_move);
        }

        code_to_move = temp;
    });

    //console.log('new chart rendered');
    render_peity_chart_waiting ();
    activate_tooltip_on_subscribe_star ();
}

function new_tile_please () {


    //Get Public tokens array (shown + previous) and POST it so we always have new hosts to show.......
    pt_json = JSON.stringify (pt);

    $.ajax ({
        type : "POST",
        data : {pt: pt_json},
        url  : ajax_calls_home + 'gettile/',
        cache: false
    })
        .done (function (result) {
            //console.log('new tile request done');
            try {
                result = $.parseJSON (result);
            } catch (err) {
                toastr['error'] ('OOOPPS! Failed to parse new tile JSON script', "TILES UPDATE");
                return false;
            }

            if ('result' in result == false) {
                toastr['error'] ('OOOPPS! Returned JSON is missing result status', "TILES UPDATE");
                return false;
            }

            if (result['result'] == true) {

                //GOOD!
                //Now we check if the script returned is a special code...
                //This is used to tell something instead of showing a new tile

                //No new tile found, probably show all tiles available....
                //reset the array with public tokens
                if (result['script'] == 'NOTFOUND') {
                    //refresh pt
                    pt = tilesTokensInArray ();

                    //return (on the next loop should be work again and found new tiles......)
                    return true;
                }

                //Returns the script to add to the tile....
                //HERE CALL SLIDE!!!!
                //console.log('tile slide request firend');
                tile_slide (result['script']);
                //console.log(result['script']);

                //update public token array
                pt.push ($ (".container_host_hp_interno").attr ("host_public_token"));
                return true;
            }
            else {
                toastr['error'] ('OOOPPS! ' + result['error_descr'], "TILES UPDATE");
                return false;
            }
        });

}

$.fn.peity.defaults.line = {
    delimiter  : ",",
    fill       : "#ECF3FF",
    height     : 60,
    max        : null,
    min        : 0,
    stroke     : "#4d89f9",
    strokeWidth: 0.5,
    width      : 220
};

$.fn.peity.defaults.bar = {
    delimiter: ",",
    fill     : function (value) {

        if (value < 0) {
            return "#EB9999";
        }
        else {
            return "#DAF6DA";
        }
    },
    height   : 60,
    max      : null,
    min      : 0,
    padding  : 0,
    width    : 220
};

function render_peity_chart_waiting () {
    objs = $ (".peity_hp_response.waiting_render");
    objs.peity ("line");
    objs.removeClass ('waiting_render');
} //END FUNCTION

function activate_tooltip_on_subscribe_star () {
    //THIS IS NECESSARY BECAUSE TOOLTIP NEED TO BE INITIALIZED...
    //SO WE CANNT BIND AN EVENT...
    //WE ACTIVATE THEM ON START AND THEN EVERY TIME A NEW TILE IS LOADING...
    //TO AVOID ACTIVATING THEM ALL AGAIN WE USE A CLASS TO SELECT ONLY THOSE WHO NEED TO BE ACTIVATED
    //THEN WE REMOVE THE FAKE CLASS....
    objs = $ ('p.activate_tooltip');
    objs.tooltip ();

    //WE DO NOT REMOVE THE CLASS BECAUSE WE NEED IT IN THE NEXT CALLS
    //TOOLTIP ACTIVATION IS NOT KEPT ON TILE SLIDES....
    //SO WE NEED TO ACTIVATE THEM AGAIN....

    //objs.removeClass('activate_tooltip');

} //END FUNCTION

$ ('body').on ('click', 'div.container_host_hp_interno', function () {
    window.location = '/info/' + $ (this).attr ('host_public_token');
});

render_peity_chart_waiting ();

//WE NEED TO WAIT FOR DOCUMENT TO BE LOADED TO ACTIVATE TOOLTIP....
$ (document).ready (function () {
    activate_tooltip_on_subscribe_star ();
    pt = tilesTokensInArray ();
});

setInterval (refresh_tiles, seconds_refresh * 1000);
</script>