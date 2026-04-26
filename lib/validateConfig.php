<?php
function validateConfig($config) {
    //This function does some basic validation of the configuration file.
    $server = $config['server'];
    $port = $config['port'];
    $channel = $config['channel'];
    $nickname = $config['nickname'];
    $errors = array();

    if(empty($server)) {
        array_push($errors,"server must not be empty");
    } else {
        if(!filter_var(gethostbyname($server), FILTER_VALIDATE_IP)) {
            array_push($errors,"unable to validate server");
        }
    }

    if(empty($port)) {
        array_push($errors,"port must not be empty");
    } else {
        if(!is_numeric($port)) {
            array_push($errors,"port must be a number");
        }
    }

    if(empty($channel)) {
        array_push($errors,"channel must not be empty");
    } else {
        if(substr($channel, 0, 1) != "#") {
            array_push($errors,"channel must start with #");
        }
    }

    if(empty($nickname)) {
        array_push($errors,"nickname must not be empty");
    } else {
        if(strlen($nickname)>15) {
            array_push($errors,"nickname must be 15 characters or less");
        }
    }

    $validLogLevels = ['DEBUG', 'INFO', 'WARN', 'ERROR'];
    if (!empty($config['log_level']) && !in_array(strtoupper($config['log_level']), $validLogLevels)) {
        array_push($errors, "log_level must be one of: DEBUG, INFO, WARN, ERROR");
    }

    $warnings = array();
    if (!empty($config['tls_enabled']) && $config['tls_enabled'] == true) {
        if (isset($config['tls_verify_peer']) && $config['tls_verify_peer'] != true) {
            array_push($warnings, "tls_verify_peer is disabled — connection will not verify the server certificate");
        }
    }

    if (!empty($config['sasl_enabled']) && $config['sasl_enabled'] == true) {
        if (empty($config['password'])) {
            array_push($warnings, "sasl_enabled is true but no password is set — SASL authentication will be skipped");
        }
    }

    if (empty($errors)) {
        echo "Configuration has passed validation checks.\n";
        foreach ($warnings as $warning) {
            echo "\tWARNING: $warning\n";
        }
        return true;
    } else {
        echo "Configuration has FAILED validation checks with the following errors:\n";
        foreach ($errors as $error) {
            echo "\t$error\n";
        }
        return false;
    }
}