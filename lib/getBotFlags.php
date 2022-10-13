<?php
function getBotFlags($hostname) {
    global $dbconnection;

    //bot_flags:
    //O - owner
    //A - admin
    //U - user
    //G = guest
    $hostname = mysqli_real_escape_string($dbconnection,$hostname);
    $query = "SELECT bot_flags FROM known_users WHERE hostname = '$hostname' LIMIT 1";
    $result = mysqli_query($dbconnection,$query);
    if(mysqli_num_rows($result) ==1) {
        while($row = mysqli_fetch_assoc($result)) {
            $botflags = $row['bot_flags'];
        }
    } else {
        $botflags = "G";
    }
    return $botflags;
}