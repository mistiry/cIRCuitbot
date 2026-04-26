<?php
function setMode($direction,$mode,$user) {
    global $dbconnection;
    global $socket;
    global $ircdata;
    global $config;

    $user = mysqli_real_escape_string($dbconnection,$user);
    $userLike = str_replace(['%', '_'], ['\\%', '\\_'], $user);
    $query = "SELECT id FROM known_users WHERE nick_aliases LIKE '%$userLike%'";
    $result = mysqli_query($dbconnection,$query);
    if(mysqli_num_rows($result) > 0) {
        $command = "MODE ".$config['channel']." ".$direction."".$mode." ".$user."";
        logEntry("setMode called to '".$direction."".$mode." ".$user."'");
        fputs($socket,"$command\n");
    }
    return true;
}