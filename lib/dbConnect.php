<?php
function dbConnect() {
    global $config;
    global $dbconnection;

    // Check if the connection is already established and is still valid
    if ($dbconnection && mysqli_ping($dbconnection)) {
        return true;
    } else {
        // Attempt to establish a new connection
        $dbconnection = mysqli_connect($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['db']);
        if (!$dbconnection) {
            die("Unable to connect to database; error: " . mysqli_connect_error());
        }

        // Set the character set to utf8mb4
        if (!mysqli_set_charset($dbconnection, "utf8mb4")) {
            die("Unable to set database character set to UTF-8mb4");
        }

        return true;
    }
    
    return true;
}