<?php
function setMode($direction,$mode,$appliesto) {
    global $dbconnection;
    global $socket;
    global $ircdata;
    global $config;

    //Confirm requestor has ADMIN rights to run
    $requestor = $ircdata['userhostname'];
    $requestorBotFlags = getBotFlags($requestor);
    if($requestorBotFlags == "A") {
        //user is permitted
        if($appliesto == $config['channel']) {
            $command = "MODE $appliesto $direction$mode";
        } elseif($appliesto == "") {
            $channel = $config['channel'];
            $user = $ircdata['usernickname'];
            $command = "MODE $channel $direction$mode $user";
        } else {
            $appliesto = mysqli_real_escape_string($dbconnection,$appliesto);
            $query = "SELECT id FROM known_users WHERE nick_aliases LIKE '%$appliesto%'";
            $result = mysqli_query($dbconnection,$query);
            if(mysqli_num_rows($result) > 0) {
                $channel = $config['channel'];
                $command = "MODE $channel $direction$mode $appliesto";
            }
        }
        logEntry("Admin user '".$ircdata['usernickname']."' requested '".$direction."".$mode."' for user '".$sppliesto."'");
        fputs($socket,"$command\n");
    } else {
        logEntry("Denied setMode for non-admin user '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
    }
    return true;
}