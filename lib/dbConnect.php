<?php
function dbConnect() {
    global $config;
    global $dbconnection; 

    $dbconnection = mysqli_connect($config['dbserver'],$config['dbuser'],$config['dbpass'],$config['db']);
    if(!$dbconnection) {
        die("Unable to connect to database; error: " . mysqli_connect_error());
    }
    if(!mysqli_set_charset($dbconnection, "utf8mb4")) {
        die("Unable to set database character set to UTF-8mb4");
    }
    return true;
}
