<?php
function getUserRecord($hostname) {
    global $dbconnection;
    $hostname = mysqli_real_escape_string($dbconnection, $hostname);
    $result = mysqli_query($dbconnection, "SELECT * FROM known_users WHERE hostname = '{$hostname}' LIMIT 1");
    if($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}
