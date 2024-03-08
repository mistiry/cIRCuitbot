<?php
function sendPRIVMSG($location,$message) {
    global $socket;

    $maxLength = 375;

    //If $message is 375 or less:
    if (strlen($message) <= $maxLength) {
        fputs($socket, "PRIVMSG ".$location." :".$message."\n");
    } else {
        $messages = splitString($message);
        foreach($messages as $msgPart) {
            fputs($socket, "PRIVMSG ".$location." :".$msgPart."\n");
        }

    }
    
}

function splitString($string, $maxLength = 375) {
    // If the string is already shorter than the max length, return it as is
    if (strlen($string) <= $maxLength) {
        return [$string];
    }
    
    // Find the nearest whitespace before the max length
    $lastSpace = strrpos(substr($string, 0, $maxLength), ' ');
    
    // If there's no whitespace, just split at the max length
    if ($lastSpace === false) {
        return [substr($string, 0, $maxLength), substr($string, $maxLength)];
    }
    
    // Split the string at the nearest whitespace
    $firstPart = substr($string, 0, $lastSpace);
    $secondPart = substr($string, $lastSpace + 1);
    
    // Recursively split the second part
    $remainingParts = splitString($secondPart, $maxLength);
    
    // Combine the first part with the recursively split parts
    array_unshift($remainingParts, $firstPart);
    
    return $remainingParts;
}

// Example usage:
$string = "Your string here...";
$parts = splitString($string);

// Now $parts will contain an array of strings, each with a maximum length of 375 characters