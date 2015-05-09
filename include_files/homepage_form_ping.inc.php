


<script>
    function show_monitor_not_found() {
        $('span#why_monitor_not_listed').fadeOut(500,function(){
            $('span#alert_hp_monitor_not_found').fadeIn(500);
        });


    }

    function hide_monitor_not_found() {
        $('span#alert_hp_monitor_not_found').fadeOut(500,function() {
            $('span#why_monitor_not_listed').fadeIn(500);
        });

    }

    function submit_form () {
        //Get search term from the form
        host = $('#form_ping_request_hp #host').val().trim();
        host_selected = $('#form_ping_request_hp #host_selected').val().trim();
        host_selected_pt = $('#form_ping_request_hp #host_selected_pt').val().trim();

        //Put default value if empty
        //The same used as placeholder....
        if (host == '') {
            show_monitor_not_found();
            return;
        }
        if (host_selected != host) {
            show_monitor_not_found();
           return;
        }

        url_string = '/info/' + host_selected_pt;

        window.location.href = url_string;

    }

    $(function() {
        $("#host").autocomplete({
            source: ajax_calls_home + 'autocompletehost/',
            minLength: 2,
            delay: 500,
            select: function (event, ui) {
                //alert(ui.item ?"Selected: " + ui.item.value + " aka " + ui.item.public_token :"Nothing selected, input was " + this.value);

                $('input#host_selected').val(ui.item.value);
                $('input#host_selected_pt').val(ui.item.public_token);


            }
        });
        //$("#host").autocomplete('disable');
    });



</script>

<div class=""  id="form_ping_request_hp">
    <form class="form-inline" id="form_web_check">
           <input  type="text" class="input_host_ping_hp input-lg form-control  " value=""
                            id="host" placeholder="Monitor name or URL"
                            name = "host" onfocus="this.placeholder='';" onblur="this.placeholder='Monitor name or URL';"
                    >
        <button id="submit_web_check" class="btn btn-warning btn-lg " >FIND MONITOR</button><br>
        <span class="micro_text" id="">&nbsp;
            <span id="alert_hp_monitor_not_found">Monitor not found! <a href="/signup/">Why don't you register and add your own monitors?</a></span>
            <span id="why_monitor_not_listed"><a>Why some URLs are not listed?</a></span>
        </span>
        <span class="micro_text" id="why_monitor_not_listed_text">
            <br>Monitors listed in homepage are the most requested ones. Many others are constantly monitored by our servers but are not public.<br>
            If you want to monitor a new website/service you can easily do it registering and creating your own monitor. It takes 2 minutes!<br>
            If you think we should add a new public monitor just drop us an email and we will be happy to verify it. Thanks.<br>
            <button class="btn-info btn-sm" id="btn_why_monitor_not_listed_got_it">OK, GOT IT!</button>
        </span>


        <input type="hidden" id="host_selected" name="host_selected">
        <input type="hidden" id="host_selected_pt" name="public_token">
    </form>
</div>





<script>
$('button#submit_web_check').click(function(e){
    e.preventDefault();
    submit_form();
});

$('input#host').focusin(function (e) {
    hide_monitor_not_found();
});

$('span#why_monitor_not_listed').click(function(){
    $('span#why_monitor_not_listed_text').fadeIn(500);
});

    $('button#btn_why_monitor_not_listed_got_it').click(function(e){
        e.preventDefault();
        $('span#why_monitor_not_listed_text').fadeOut(500);
    });


</script>

