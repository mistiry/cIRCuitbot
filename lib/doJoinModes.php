<?php
function doJoinModes($userhostname,$usernickname) {
    global $dbconnection;
    global $timestamp;
    global $ircdata;

    $query = "SELECT join_modes FROM known_users WHERE hostname = '".$userhostname."'";
    $result = mysqli_query($dbconnection,$query);
    if(mysqli_num_rows($result) == 0) {
        return $true;
    }
    if(mysqli_num_rows($result) == 1) {
        while($row = mysqli_fetch_assoc($result)) {
            $joinmodes = $row['join_modes'];
            if($joinmodes != NULL) {
                logEntry("Setting join modes '+".$joinmodes."' for '".$usernickname."@".$userhostname."'");
                $allmodes = str_split($joinmodes);
                foreach($allmodes as $mode) {
                    setMode("+",trim($mode),trim($usernickname));
                }
            } else {
                continue;
            }
        }
    } else {
        logEntry("Error processing doJoinModes function for user '".$usernickname."@".$userhostname."'");
    }
    return true;
}