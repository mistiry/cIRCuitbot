<?php
function connectToServer() {
    global $config;
    global $socket;
    global $connectionAlive;

    $useTLS     = !empty($config['tls_enabled'])     && $config['tls_enabled']     == true;
    $verifyPeer = !isset($config['tls_verify_peer']) || $config['tls_verify_peer'] == true;
    $errno      = 0;
    $errstr     = '';

    $tlsNote = '';
    if ($useTLS) {
        $tlsNote = $verifyPeer ? ' (TLS)' : ' (TLS, peer verification disabled)';
    }
    logEntry("Connecting to {$config['server']}:{$config['port']} as {$config['nickname']}...{$tlsNote}", 'INFO');

    if ($useTLS) {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => $verifyPeer,
                'verify_peer_name' => $verifyPeer,
            ]
        ]);
        $socket = stream_socket_client(
            "ssl://{$config['server']}:{$config['port']}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
    } else {
        $socket = fsockopen($config['server'], $config['port'], $errno, $errstr, 30);
    }

    if (!$socket) {
        logEntry("Failed to connect to {$config['server']}:{$config['port']}: {$errstr} ({$errno})", 'ERROR');
        return false;
    }

    stream_set_blocking($socket, false);
    fputs($socket, "USER {$config['nickname']} {$config['nickname']} {$config['nickname']} {$config['nickname']} :{$config['nickname']}\r\n");
    if ($config['password'] != "") {
        fputs($socket, "PASS {$config['password']}\r\n");
    }
    fputs($socket, "NICK {$config['nickname']}\r\n");
    sleep(1);
    joinChannel($config['channel']);
    heartbeatUpdate();
    $connectionAlive = true;
    logEntry("Connected and joined {$config['channel']}. Listening for commands.", 'INFO');
    return true;
}
