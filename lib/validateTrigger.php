<?php
function validateTrigger($trigger) {
    //This function really could use some work to better validate the triggers being loaded
    //meet the standards and such

    global $config;

    $return = "invalid";

    //If the trigger's directory exists
    $triggerPath = "".$config['addons_dir']."/triggers/".$trigger."";
    if(file_exists("".$triggerPath."/")) {
        //If the trigger's config file exists
        if(file_exists("".$triggerPath."/trigger.conf")) {
            //If the trigger's PHP file exists
            if(file_exists("".$triggerPath."/trigger.php")) {
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