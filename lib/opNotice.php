<?php
function handleOpNotice($ircdata) {
    global $config, $socket;

    if (empty($config['opnotice_enabled']) || $config['opnotice_enabled'] != true) {
        return;
    }

    $sender = $ircdata['usernickname'];

    // Actual ops messaging other ops — leave it alone
    if (isChannelOp($sender)) {
        logEntry("Op-targeted message from op {$sender}; not responding", 'DEBUG');
        return;
    }

    $message = !empty($config['opnotice_message'])
        ? $config['opnotice_message']
        : "Your message was only seen by channel operators. This channel requires NickServ identification to chat. Please register and identify your nickname to participate.";

    logEntry("Sending opnotice to {$sender}", 'DEBUG');
    fputs($socket, "NOTICE {$sender} :{$message}\r\n");
}
