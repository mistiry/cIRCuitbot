<?php
function joinChannel($channel) {
    global $socket;
    logEntry("Sending JOIN for {$channel}", 'DEBUG');
    fputs($socket, "JOIN {$channel}\r\n");
    return true;
}
