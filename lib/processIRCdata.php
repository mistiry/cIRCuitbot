<?php
function processIRCdata($data) {
    $pieces = explode(' ', $data);
    $messagearray = explode(':', $pieces[3]);
    $command = $pieces[0];
    $messagetype = trim($pieces[1]);
    $location = trim($pieces[2]);
    $userpieces1 = explode('@', $pieces[0]);
    $userpieces2 = explode('!', $userpieces1[0]);
    $userpieces3 = explode(':', $userpieces2[0]);
    $userhostname = $userpieces1[1];
    $usernickname = $userpieces3[1];
    $fullmessage = NULL; for ($i = 3; $i < count($pieces); $i++) { $fullmessage .= $pieces[$i] . ' '; }
    $fullmessage = substr($fullmessage, 1);
    $fullmessage = trim($fullmessage);
    $commandargs = NULL; for ($i = 4; $i < count($pieces); $i++) { $commandargs .= $pieces[$i] . ' '; }
    $commandargs = trim($commandargs);
    
    //Quit messages are a bit different in formatting, so adjust accordingly
    if(stristr($messagetype, "QUIT")) {
        $quitpieces = explode(' ', $data);
        $quitmessage = NULL; for ($i = 2; $i < count($quitpieces); $i++) { $quitmessage .= $quitpieces[$i] . ' '; }
        $quitmessage = substr($quitmessage, 1);
        $location = "";
        $fullmessage = trim($quitmessage);
    }

    $return = array(
            'messagearray'  =>      $messagearray,
            'messagetype'   =>      $messagetype,
            'command'       =>      $command,
            'location'      =>      $location,
            'userhostname'  =>      $userhostname,
            'usernickname'  =>      $usernickname,
            'commandargs'   =>      $commandargs,
            'fullmessage'   =>      $fullmessage
    );
    return $return;
}