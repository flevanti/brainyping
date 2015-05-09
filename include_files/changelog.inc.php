<h4>CHANGELOG</h4>


<div class = "todo_list">


    WORKING ON v0.5 (not yet released):<br>
<p class="todo_list done"> User registration</p>
    <p class="todo_list done"> User manage its own hosts</p>
    <p class="todo_list done"> Track choosen hosts with choose interval</p>
    <p class="todo_list done"> Choose port to connect in order to check SMTP server, FTP server and so on</p>
<p class="todo_list"> Keep track of old logs in order to create weekly/monthly reports </p>
    <p class="todo_list"> VPS engine (watchdog) optimization to run more than 100 connections/checks/second </p>
    <p class="todo_list done"> Optimize homepage mini-chart to quickly load page (already rolled-out to production)</p>
    <p class="todo_list done"> Add average response time to homepage charts (already rolled-out to production) </p>
    <p class="todo_list done"> Add outage information on homepage mini-charts (already rolled-out to production) </p>
    <p class="todo_list "> HTTP API support (have to decide if only for reports or also for live data)</p>

    <br><br>
    v0.4 - 01 Sept 2014:
    <p class="todo_list done"> Homepage mini-charts to quickly have an overview of the response time</p>
    <p class="todo_list done"> New clean layout </p>
    <p class="todo_list done"> Added a red stripe on down hosts </p>
    <p class="todo_list fire"> Application logged more than 100,000,000 pings </p>



    <br><br>
    v0.3 - 01 May 2014:
    <p class="todo_list done">Move Watchdog engine to a faster/more secure server</p>
    <p class="todo_list done">Migrate from SQLite to MongoDB database</p>
    <p class="todo_list done">Move to cron so watchdog is run every <i>x</i> seconds</p>
    <p class="todo_list fire"> Application is monitoring more than 800 hosts </p>


    <br><br>
    v0.2 - 01 Feb 2014:
    <p class="todo_list done">Migrate from CLI version to Web interface</p>
    <p class="todo_list done">Added alarms on Critical errors</p>
    <p class="todo_list done">Whatchdog engine and ping engine rewritten for better performance</p>
    <p class="todo_list fire"> Application logged more than 15,000,000 pings </p>


    <br><br>
    v0.1 - Oct  2013:
    <p class="todo_list done">PHP Command Line Interface application released to check hosts availability over internet</p>
    <p class="todo_list done">Whatchdog engine is a PHP application that runs in the background</p>



</div>
<br>

Feel free to submit a request, share ideas or ask details about our project.<br>
<a href="<?php echo $uriobj->URI_gen(["contacts"]); ?>">
<button class="btn btn-large btn-warning "><b>CONTACT</b></button>
</a>


