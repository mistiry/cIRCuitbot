<?php
function logEntry($line) {
    global $config;
    global $timestamp;

    if(!file_exists($config['log_file'])) {
        system("touch ".$config['log_file']."");
    }

    $line = trim(preg_replace("'/\s*/m'","",$line));
    $file = fopen($config['log_file'], "a");
    $logline = "[".$timestamp."] ".$line."\n";
    fwrite($file,$logline);
    fclose($file);
    return;
}