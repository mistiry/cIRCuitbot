<?php
function heartbeatUpdate() {
    global $heartbeat;
    $heartbeat = time();
    return true;
}

function heartbeatCheck() {
    global $heartbeat;
    global $connectionAlive;
    
    $now = time();
    $diff = $now - $heartbeat;
    if($diff > "300") {
        logEntry("Heartbeat check FAILED! Time since last successful heartbeat: ".$diff." seconds. Attempting reconnect.");
        $connectionAlive = false;
        connectToServer();
        return true;
    } else {
        $connectionAlive = true;
        return true;
    }
}