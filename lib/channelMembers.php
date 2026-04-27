<?php
function addChannelMember($nick, $hostname, $modes = [], $account = '') {
    global $channelMembers;
    $channelMembers[$nick] = [
        'hostname' => $hostname,
        'modes'    => $modes,
        'account'  => $account,
    ];
}

function removeChannelMember($nick) {
    global $channelMembers;
    unset($channelMembers[$nick]);
}

function renameChannelMember($oldnick, $newnick) {
    global $channelMembers;
    if (isset($channelMembers[$oldnick])) {
        $channelMembers[$newnick] = $channelMembers[$oldnick];
        unset($channelMembers[$oldnick]);
    }
}

function updateChannelMemberAccount($nick, $account) {
    global $channelMembers;
    if (isset($channelMembers[$nick])) {
        $channelMembers[$nick]['account'] = ($account === '*') ? '' : $account;
    }
}

function parseNamesEntry($entry) {
    $modes = [];
    $entry = ltrim(trim($entry), ':');
    $prefixMap = ['@' => 'o', '+' => 'v'];
    while ($entry !== '' && isset($prefixMap[$entry[0]])) {
        $modes[] = $prefixMap[$entry[0]];
        $entry = substr($entry, 1);
    }
    return ['nick' => $entry, 'modes' => $modes];
}

function applyChannelModeChange($modestring, $modeargs) {
    global $channelMembers;

    // Modes that consume an argument in both + and - direction
    $paramModes     = ['o', 'v', 'h', 'b', 'e', 'I', 'q', 'k'];
    // Modes that consume an argument only when +
    $paramPlusModes = ['l'];
    // Modes whose argument we actually store
    $trackedModes   = ['o', 'v'];

    $direction = '+';
    $argIndex  = 0;

    for ($i = 0; $i < strlen($modestring); $i++) {
        $char = $modestring[$i];
        if ($char === '+') { $direction = '+'; continue; }
        if ($char === '-') { $direction = '-'; continue; }

        $takesParam = in_array($char, $paramModes)
            || ($direction === '+' && in_array($char, $paramPlusModes));

        if ($takesParam) {
            $target = $modeargs[$argIndex] ?? null;
            $argIndex++;

            if (in_array($char, $trackedModes) && $target !== null && isset($channelMembers[$target])) {
                if ($direction === '+') {
                    if (!in_array($char, $channelMembers[$target]['modes'])) {
                        $channelMembers[$target]['modes'][] = $char;
                    }
                } else {
                    $modeChar = $char;
                    $channelMembers[$target]['modes'] = array_values(
                        array_filter($channelMembers[$target]['modes'], function($m) use ($modeChar) {
                            return $m !== $modeChar;
                        })
                    );
                }
            }
        }
        // Modes without params: nothing to consume, just skip
    }
}

function isChannelOp($nick) {
    global $channelMembers;
    return isset($channelMembers[$nick]) && in_array('o', $channelMembers[$nick]['modes']);
}

function isChannelVoiced($nick) {
    global $channelMembers;
    return isset($channelMembers[$nick]) && in_array('v', $channelMembers[$nick]['modes']);
}

function isBotOwnerOrAdmin($ircdata) {
    global $config;
    if (!empty($config['bot_owner_hostname']) && $ircdata['userhostname'] === $config['bot_owner_hostname']) {
        return true;
    }
    $flags = getBotFlags($ircdata['userhostname']);
    return strpos($flags, 'A') !== false || strpos($flags, 'O') !== false;
}

function dumpChannelMembers($replyNick = null) {
    global $channelMembers, $socket;
    $count   = count($channelMembers);
    $ops     = [];
    $voiced  = [];
    $noHost  = 0;

    foreach ($channelMembers as $nick => $data) {
        $modes   = empty($data['modes'])   ? '-'              : implode('', $data['modes']);
        $host    = empty($data['hostname']) ? '(unknown)'     : $data['hostname'];
        $account = empty($data['account']) ? '(unidentified)' : $data['account'];
        if (in_array('o', $data['modes'])) $ops[]    = $nick;
        if (in_array('v', $data['modes'])) $voiced[] = $nick;
        if (empty($data['hostname']))       $noHost++;
        logEntry("  {$nick}  modes={$modes}  host={$host}  account={$account}", 'INFO');
    }

    $opsStr    = count($ops)    ? implode(', ', $ops)    : '(none)';
    $voiceStr  = count($voiced) ? implode(', ', $voiced) : '(none)';
    $noHostStr = $noHost > 0    ? " | {$noHost} missing hostname" : '';

    logEntry("=== channelMembers dump ({$count} entries) ===", 'INFO');
    logEntry("=== end channelMembers dump ===", 'INFO');

    if ($replyNick !== null) {
        fputs($socket, "NOTICE {$replyNick} :Channel members: {$count} total{$noHostStr}\r\n");
        usleep(200000);
        fputs($socket, "NOTICE {$replyNick} :Ops: {$opsStr}\r\n");
        usleep(200000);
        fputs($socket, "NOTICE {$replyNick} :Voiced: {$voiceStr}\r\n");
        usleep(200000);
        fputs($socket, "NOTICE {$replyNick} :Full member list written to log at INFO level.\r\n");
    }
}
