<?php
function checkTimersForExpiry() {
    global $timerArray;
    global $ircdata;

    $currentEpoch = time();
    foreach($timerArray as $function => $expiry) {
        if($expiry <= $currentEpoch) {
            //timer is expired!
            logEntry("A timer has expired. Expiry was '".$expiry."' and current epoch is '".$currentEpoch."'. Calling function '".$function."'");
            //call_user_func($function,$ircdata);
            $timerArray[$function] = NULL;
            return true;
        } else {
            //timer not expired
            return false;
        }
    }
}