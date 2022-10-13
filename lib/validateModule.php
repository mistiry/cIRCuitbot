<?php
function validateModule($module) {
    //This function really could use some work to better validate the modules being loaded
    //meet the standards and such

    $return = "invalid";

    //If the module's directory exists
    $modulePath = "./modules/".$module."";
    if(file_exists("".$modulePath."/")) {
        //If the module's config file exists
        if(file_exists("".$modulePath."/module.conf")) {
            //If the module's PHP file exists
            if(file_exists("".$modulePath."/module.php")) {
                $return = "valid";
            } else {
                logEntry("PHP file not found for module '".$module."'");
                $return = "invalid";
            }
        } else {
            logEntry("Config file not found for module '".$module."'");
            $return = "invalid";
        }
    } else {
        logEntry("Directory not found for module '".$module."'");
        $return = "invalid";
    }
    return $return;
}