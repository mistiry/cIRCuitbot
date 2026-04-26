<?php
function connectToServer() {
    global $config;
    global $socket;
    global $connectionAlive;

    logEntry("Connecting to {$config['server']}:{$config['port']} as {$config['nickname']}...", 'INFO');
    $socket = fsockopen($config['server'], $config['port']);
    stream_set_blocking($socket, false);
    fputs($socket, "USER {$config['nickname']} {$config['nickname']} {$config['nickname']} {$config['nickname']} :{$config['nickname']}\r\n");
    if ($config['password'] != "") {
        fputs($socket, "PASS {$config['password']}\r\n");
    }
    fputs($socket, "NICK {$config['nickname']}\r\n");
    sleep(1);
    joinChannel($config['channel']);
    heartbeatUpdate();
    $connectionAlive = true;
    logEntry("Connected and joined {$config['channel']}. Listening for commands.", 'INFO');
    return true;
}
