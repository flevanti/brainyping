<?php
echo "<h3>EMAIL QUEUE PROCESSING</h3>";



$r = email_queue::processQueue();

echo "<b>" . ($r["ok"] + $r["failed"]) . "</b> emails waiting to be processed<br>";
echo "<b>" . $r["ok"] . "</b> emails sent, <b>" . $r["failed"] . "</b> emails failed";


