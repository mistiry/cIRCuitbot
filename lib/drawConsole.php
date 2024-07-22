<?php
//This file is reponsible for drawing the console output

function drawConsole() {
    global $config;
    global $modules;
    global $triggers;

    echo "\033[H\033[J";
    drawHeader();
    drawLineSplit();

    //Loaded Commands
    $titleCommands = formatConsoleString("                                --- COMMANDS ---                                ", "magenta", NULL, "bold");
    echo $titleCommands . "\n";
    printColumnizedKeys($modules);

    drawLineSplit();

    //Loaded Triggers
    $titleTriggers = formatConsoleString("                                --- TRIGGERS ---                                ", "magenta", NULL, "bold");
    echo $titleTriggers . "\n";
    printColumnizedKeys($triggers);

    drawLineSplit();

    //Log file
    printLastLogLines($config['log_file']);
}

function drawHeader() {
    global $connectionAlive;
    global $config;

    //Logo Colorizing - logo is 46 chars wide
    $logo1 = "       _______  _____     _ __  __        __  ";
    $logo2 = "  ____/  _/ _ \/ ___/_ __(_) /_/ /  ___  / /_ ";
    $logo3 = " / __// // , _/ /__/ // / / __/ _ \/ _ \/ __/ ";
    $logo4 = " \__/___/_/|_|\___/\_,_/_/\__/_.__/\___/\__/  ";
    $logoLine1 = formatConsoleString($logo1, "red", NULL, "bold");
    $logoLine2 = formatConsoleString($logo2, "red", NULL, "bold");
    $logoLine3 = formatConsoleString($logo3, "red", NULL, "bold");
    $logoLine4 = formatConsoleString($logo4, "red", NULL, "bold");

    //Status
    if($connectionAlive === false) {
        $status = formatConsoleString("OFFLINE", "red", NULL, "bold");
    } elseif($connectionAlive === true) {
        $status = formatConsoleString("ONLINE", "green", NULL, "bold");
    } else {
        $status = formatConsoleString("UNKNOWN", "yellow", NULL, "bold");
    }

    //Header Info
    $headerLine1 = "".$logoLine1."                    ".formatConsoleString("  Server: ", "yellow", NULL, "bold")." ".formatConsoleString($config['server'], NULL, NULL, "underline")."";
    $headerLine2 = "".$logoLine2."                    ".formatConsoleString(" Channel: ", "yellow", NULL, "bold")." ".formatConsoleString($config['channel'], NULL, NULL, "underline")."";
    $headerLine3 = "".$logoLine3."                    ".formatConsoleString("Nickname: ", "yellow", NULL, "bold")." ".formatConsoleString($config['nickname'], NULL, NULL, "underline")."";
    $headerLine4 = "".$logoLine4."                    ".formatConsoleString("  Status: ", "yellow", NULL, "bold")." ".$status."";

    echo $headerLine1 . "\n";
    echo $headerLine2 . "\n";
    echo $headerLine3 . "\n";
    echo $headerLine4 . "\n";
}

function drawLineSplit() {
    //Line split
    $lineSplit = formatConsoleString("--------------------------------------------------------------------------------", "white");
    echo $lineSplit . "\n";
}

function printColumnizedKeys($array) {
    $keys = array_keys($array);
    $numKeys = count($keys);
    $numColumns = 4;
    $maxLineWidth = 80;
    
    // Calculate the maximum width for each column
    $maxColWidth = floor($maxLineWidth / $numColumns);
    
    // Split keys into rows
    $rows = array_chunk($keys, $numColumns);

    foreach ($rows as $row) {
        // Create the formatted line for the current row
        $line = '';
        foreach ($row as $key) {
            // Center the key within the column width
            $paddedKey = str_pad($key, $maxColWidth, ' ', STR_PAD_BOTH);
            $line .= $paddedKey;
        }
        // Trim any extra spaces and print the line
        echo rtrim($line) . "\n";
    }
}

function printLastLogLines($logfile) {
    $maxLineWidth = 80;
    $lines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lastLines = array_slice($lines, -10);

    foreach ($lastLines as $line) {
        if (strlen($line) > $maxLineWidth) {
            // Truncate and add ellipsis
            $line = substr($line, 0, $maxLineWidth - 3) . '...';
        }
        echo $line . "\n";
    }
}
