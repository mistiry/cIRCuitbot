<?php
function sendPRIVMSG($location,$message) {
    global $socket;
    fputs($socket, "PRIVMSG ".$location." :".$message."\n");
    return;
}