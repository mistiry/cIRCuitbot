<?php
function formatConsoleString($string, $foregroundColor = null, $backgroundColor = null, $modifier = null) {
    $coloredString = "";

    // Add modifier if provided
    switch ($modifier) {
        case 'bold':
            $coloredString .= "\033[1m";
            break;
        case 'underline':
            $coloredString .= "\033[4m";
            break;
    }

    // Add foreground color if provided
    switch ($foregroundColor) {
        case 'black':
            $coloredString .= "\033[0;30m";
            break;
        case 'red':
            $coloredString .= "\033[0;31m";
            break;
        case 'green':
            $coloredString .= "\033[0;32m";
            break;
        case 'yellow':
            $coloredString .= "\033[0;33m";
            break;
        case 'blue':
            $coloredString .= "\033[0;34m";
            break;
        case 'magenta':
            $coloredString .= "\033[0;35m";
            break;
        case 'cyan':
            $coloredString .= "\033[0;36m";
            break;
        case 'white':
            $coloredString .= "\033[0;37m";
            break;
    }

    // Add background color if provided
    switch ($backgroundColor) {
        case 'black':
            $coloredString .= "\033[40m";
            break;
        case 'red':
            $coloredString .= "\033[41m";
            break;
        case 'green':
            $coloredString .= "\033[42m";
            break;
        case 'yellow':
            $coloredString .= "\033[43m";
            break;
        case 'blue':
            $coloredString .= "\033[44m";
            break;
        case 'magenta':
            $coloredString .= "\033[45m";
            break;
        case 'cyan':
            $coloredString .= "\033[46m";
            break;
        case 'white':
            $coloredString .= "\033[47m";
            break;
    }

    // Add the string and reset formatting
    $coloredString .= $string . "\033[0m";

    return $coloredString;
}
