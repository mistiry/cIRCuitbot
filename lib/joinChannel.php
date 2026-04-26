<?php
function joinChannel($channel) {
    global $socket;
    logEntry("Joining {$channel}", 'INFO');
    fputs($socket, "JOIN {$channel}\r\n");
    return true;
}
