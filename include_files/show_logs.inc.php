<?php
if (user::getRole() != "ADMIN" ) {
    //echo "Looks like you're not authorized, sorry!";
    //return;
}

echo "<h3>LOGS</h3>";



$sql = "SELECT
            process,
            session_id,
            MIN(date_ts) AS date_ts_first_log,
            COUNT(*) AS n_rows
        FROM
            logs
            where date_ts > (unix_timestamp() - 48*60*60)
        GROUP BY process , session_id
        ORDER BY date_ts_first_log DESC;";

$rs = $mydbh_web->query($sql);

echo "<div class=\"row\">";

        echo "<div class=\"col-md-4 log_list\" style='overflow:auto ;  min-height: 150px; max-height: 350px;'>";
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                echo "<p class=\"log_list\" session_id=\"" . $row["session_id"] . "\">" . date("d/m/Y H:i:s", $row["date_ts_first_log"]) . " - " . $row["process"] . " -  Rows: " . $row["n_rows"] . "</p>";
            }
        echo "</div>";

        echo "<div class=\"col-md-8 log_list\" id=\"log_list_details\" style=\"text-align: left; font-size:0.8em;\">";
            echo "Select a log row to view details....";
        echo "</div>";
echo "</div>";



?>

<script>


    $('p.log_list').click(function(){
        sid = $(this).attr('session_id');
        $('div#log_list_details').html('<img src="imgs/preloader2.gif">');
        $.ajax({
            type: "GET",
            url: ajax_calls_home + 'showlogs/' + sid + '/',
            cache: false
        })
            .done(function( result ) {
                try {
                    result = $.parseJSON(result);
                } catch (err) {
                    toastr['error']('OOOPPS! Something went terribly wrong during JSON parsing!',"SHOW LOGS");
                    return false;
                }

                if (result["result"]==true) {
                    $('div#log_list_details').html(result["logs"]);

                } else {
                    if (result["error_descr"] != undefined) {
                        error_descr = "<br>" + result["error_descr"] + "<br>";
                    } else {
                        error_descr = "Unknown error, looks like ajax response description is not found";
                    }
                    toastr['error']('OOOPPS!' + error_descr,"SHOW LOGS");
                }
            });

    });





</script>
