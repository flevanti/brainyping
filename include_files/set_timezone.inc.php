<?php

//Because this operation (timezone) is very important we put it in the config file
//To be sure il will be executed by every script
if (!date_default_timezone_set("Europe/Berlin")) {
    echo "UNABLE TO SET TIMEZONE";
    exit;
};