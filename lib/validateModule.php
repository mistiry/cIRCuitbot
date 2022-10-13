<?php
function validateModule($module) {
    //This function really could use some work to better validate the modules being loaded
    //meet the standards and such

    //If the module's directory exists
    if(file_exists("./modules/".$module."/")) {
        //If the module's config file exists
        if(file_exists("./".$module."/module.conf")) {
            //If the module's PHP file exists
            if(file_exists("./".$module."/module.php")) {
                return true;
            } else {
                logEntry("PHP file not found for module '".$module."'");
                return false;
            }
        } else {
            logEntry("Config file not found for module '".$module."'");
            return false;
        }
    } else {
        logEntry("Directory not found for module '".$module."'");
        return false;
    }
    //Should NEVER get to this return, but if somehow you do, return false to be safe
    return false;
}