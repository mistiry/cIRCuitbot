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

    // If channelModeration is loaded, check for an active quiet before sending generic NickServ notice
    if (function_exists('channelModeration_getActiveAction')) {
        $action = channelModeration_getActiveAction($sender, 'quiet');
        if ($action) {
            global $channelModeration_rateLimits;
            if (!is_array($channelModeration_rateLimits)) {
                $channelModeration_rateLimits = [];
            }
            $cooldown = !empty($config['moderation_quiet_notice_cooldown']) ? (int)$config['moderation_quiet_notice_cooldown'] : 60;
            $lastSent = $channelModeration_rateLimits[$sender] ?? 0;
            if ((time() - $lastSent) < $cooldown) {
                logEntry("Rate-limited quiet notice for {$sender}", 'DEBUG');
                return;
            }
            $channelModeration_rateLimits[$sender] = time();
            $extra = '';
            if (!empty($action['expires_at'])) {
                $secs = strtotime($action['expires_at']) - time();
                if ($secs > 0) {
                    $extra = ' Time remaining: ' . channelModeration_formatDuration($secs) . '.';
                }
            }
            fputs($socket, "NOTICE {$sender} :You are muted in this channel.{$extra}\r\n");
            logEntry("Sent quiet notice to {$sender}", 'DEBUG');
            return;
        }
    }

    $message = !empty($config['opnotice_message'])
        ? $config['opnotice_message']
        : "Your message was only seen by channel operators. This channel requires NickServ identification to chat. Please register and identify your nickname to participate.";

    logEntry("Sending opnotice to {$sender}", 'DEBUG');
    fputs($socket, "NOTICE {$sender} :{$message}\r\n");
}
