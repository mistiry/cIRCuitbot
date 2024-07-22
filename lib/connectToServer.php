<?php
function connectToServer() {
    global $config;
    global $socket;
    global $connectionAlive;

    $socket = fsockopen($config['server'], $config['port']);
    stream_set_blocking($socket, false);
    fputs($socket,"USER ".$config['nickname']." ".$config['nickname']." ".$config['nickname']." ".$config['nickname']." :".$config['nickname']."\n");
    if($config['password'] != "") {
        fputs($socket,"PASS ".$config['password']."\n");
    }
    fputs($socket,"NICK ".$config['nickname']."\n");
    sleep(1);
    heartbeatUpdate();
    $connectionAlive = true;
    return true;
}
