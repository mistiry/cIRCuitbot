<?php
function joinChannel($channel) {
    global $socket;
    fputs($socket,"JOIN ".$channel."\n");
    return true;
}