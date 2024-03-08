<?php
function sendPRIVMSG($location,$message) {
    global $socket;

    $maxLength = 375;

    //If $message is 375 or less:
    if (strlen($message) <= $maxLength) {
        fputs($socket, "PRIVMSG ".$location." :".$message."\n");
    }
    
    // Find the nearest whitespace before the max length
    $lastSpace = strrpos(substr($message, 0, $maxLength), ' ');
    
    // // If there's no whitespace, just split at the max length
    // if ($lastSpace === false) {
    //     return [substr($message, 0, $maxLength), substr($message, $maxLength)];
    // }
    
    // Split the message at the nearest whitespace
    $firstPart = substr($message, 0, $lastSpace);
    $secondPart = substr($message, $lastSpace + 1);
    
    // Recursively split the second part
    $remainingParts = splitString($secondPart, $maxLength);
    
    // Combine the first part with the recursively split parts
    array_unshift($remainingParts, $firstPart);

    //Foreach message in array
    foreach($remainingParts as $message) {
        fputs($socket, "PRIVMSG ".$location." :".$message."\n");
    }

    return true;
}