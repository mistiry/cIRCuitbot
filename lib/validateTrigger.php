<?php
function validateTrigger($trigger) {
    //This function really could use some work to better validate the triggers being loaded
    //meet the standards and such

    echo "Validating ".$trigger."\n";
    $return = "invalid";

    //If the trigger's directory exists
    if(file_exists("./triggers/".$trigger."/")) {
        //If the trigger's config file exists
        if(file_exists("./".$trigger."/trigger.conf")) {
            //If the trigger's PHP file exists
            if(file_exists("./".$trigger."/trigger.php")) {
                $return = "valid";
            } else {
                logEntry("PHP file not found for trigger '".$trigger."'");
                $return = "invalid";
            }
        } else {
            logEntry("Config file not found for trigger '".$trigger."'");
            $return = "invalid";
        }
    } else {
        logEntry("Directory not found for trigger '".$trigger."'");
        $return = "invalid";
    }
    return $return;
}