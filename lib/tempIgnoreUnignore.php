<?php
function tempIgnoreUnignore($who,$type) {
    global $ircdata;
    global $ignoredUsers;
    global $config;

    $requestor = $ircdata['userhostname'];
    $requestorBotFlags = getBotFlags($requestor);
    if($requestorBotFlags == "A") {
        if($who == $config['bot_owner_hostname']) {
            sendPRIVMSG($ircdata['location'],"".$ircdata['usernickname'].": I would never ignore my owner.");
            return true;
        }
        if($type == "ignore") {
            array_push($ignoredUsers,$who);
            logEntry("Admin user '".$ircdata['usernickname']."' requested to ignore '".$who."'");
            sendPRIVMSG($ircdata['location'],"Added hostname '".$who."' to temporary ignore list.");
        } elseif($type == "unignore") {
            if( ($key = array_search($who,$ignoredUsers)) !== false) {
                unset($ignoredUsers[$key]);
                logEntry("Admin user '".$ircdata['usernickname']."' requested to unignore '".$who."'");
                sendPRIVMSG($ircdata['location'],"Removed hostname '".$who."' from temporary ignore list.");
            }
        }
        logEntry("Current ignore list:");
        foreach($ignoredUsers as $ignoredUser) {
            logEntry("        - ".$ignoredUser."");
        }
    } else {
        logEntry("Denied '".$type."' for non-admin user '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
    }
    return true;
}