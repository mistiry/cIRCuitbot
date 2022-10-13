<?php
function validateTrigger($trigger) {
    //This function really could use some work to better validate the triggers being loaded
    //meet the standards and such

    echo "Validating ".$trigger."\n";

    //If the trigger's directory exists
    if(file_exists("./trigger/".$trigger."/")) {
        //If the trigger's config file exists
        if(file_exists("./".$trigger."/trigger.conf")) {
            //If the trigger's PHP file exists
            if(file_exists("./".$trigger."/trigger.php")) {
                return true;
            } else {
                logEntry("PHP file not found for trigger '".$trigger."'");
                return false;
            }
        } else {
            logEntry("Config file not found for trigger '".$trigger."'");
            return false;
        }
    } else {
        logEntry("Directory not found for trigger '".$trigger."'");
        return false;
    } else {
        logEntry("Unable to find the trigger's directory at ./triggers/".$trigger"");
    }
    //Should NEVER get to this return, but if somehow you do, return false to be safe
    return false;
}