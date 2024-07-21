<?php
//Configuration - The bot should be ran with the '-c' flag, pointed
//to a configuration file with valid values. See the included 
//example.conf file to create your bot config.

function loadConfig() {
    global $config;

    $configfile = getopt("c:");
    if(file_exists($configfile['c'])) {
        $config = parse_ini_file($configfile['c']);
        $validation = validateConfig($config);
        if($validation == false) {
            die("Configuration failed to pass validation checks.\n");
        }
        echo "Running with configuration:\n";
        print_r($config);
    } else {
        die("Unable to use '$configfile' - does it exist and have correct permissions?\n");
    }
}

function reloadConfig() {
    global $config;

    $configfile = getopt("c:");
    if(file_exists($configfile['c'])) {
        $newConfig = parse_ini_file($configfile['c']);
        $validation = validateConfig($newConfig);
        if($validation == false) {
            echo "Validation of new configuration failed, NOT RELOADING!";
        } else {
            $config = $newConfig;
        }
    } else {
        echo "Unable to read specified configuration file.";
    }
}