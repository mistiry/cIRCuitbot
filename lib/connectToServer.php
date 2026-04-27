<?php
function connectToServer() {
    global $config, $socket, $connectionAlive;

    $useTLS     = !empty($config['tls_enabled'])     && $config['tls_enabled']     == true;
    $verifyPeer = !isset($config['tls_verify_peer']) || $config['tls_verify_peer'] == true;
    $useSASL    = !empty($config['sasl_enabled'])    && $config['sasl_enabled']    == true;
    $useIRCv3   = !empty($config['ircv3_enabled'])   && $config['ircv3_enabled']   == true;
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

    $doCapNeg = $useSASL || $useIRCv3;

    if ($doCapNeg) {
        // Keep socket in blocking mode with a timeout for the handshake
        stream_set_timeout($socket, 15);
        fputs($socket, "CAP LS 302\r\n");
        // PASS must arrive before registration completes; skip if SASL handles auth
        if (!$useSASL && $config['password'] != "") {
            fputs($socket, "PASS {$config['password']}\r\n");
        }
        fputs($socket, "NICK {$config['nickname']}\r\n");
        fputs($socket, "USER {$config['nickname']} 0 * :{$config['nickname']}\r\n");
        _capNegotiate($useSASL, $useIRCv3);
        stream_set_blocking($socket, false);
    } else {
        stream_set_blocking($socket, false);
        fputs($socket, "USER {$config['nickname']} 0 * :{$config['nickname']}\r\n");
        if ($config['password'] != "") {
            fputs($socket, "PASS {$config['password']}\r\n");
        }
        fputs($socket, "NICK {$config['nickname']}\r\n");
    }

    sleep(1);
    joinChannel($config['channel']);
    heartbeatUpdate();
    $connectionAlive = true;
    return true;
}

function _capNegotiate($useSASL, $useIRCv3) {
    global $config, $socket;

    $wantedCaps = [];
    if ($useSASL) {
        $wantedCaps[] = 'sasl';
    }
    if ($useIRCv3 && !empty($config['ircv3_caps'])) {
        foreach (explode(' ', trim($config['ircv3_caps'])) as $cap) {
            $cap = trim($cap);
            if ($cap !== '' && $cap !== 'sasl') {
                $wantedCaps[] = $cap;
            }
        }
    }

    if (empty($wantedCaps)) {
        logEntry("No capabilities configured; sending CAP END", 'DEBUG');
        fputs($socket, "CAP END\r\n");
        return;
    }

    // Phase 1: read CAP LS to discover what the server supports
    $serverCaps = [];
    $deadline   = time() + 15;

    while (time() < $deadline) {
        $line = fgets($socket);
        if ($line === false) {
            break;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (strncmp($line, 'PING ', 5) === 0) {
            fputs($socket, 'PONG ' . substr($line, 5) . "\r\n");
            continue;
        }
        logEntry("CAP << {$line}", 'DEBUG');
        // :server CAP * LS :cap1 cap2          (final line)
        // :server CAP * LS * :cap1 cap2         (multiline, more follows)
        if (preg_match('/^:\S+ CAP \S+ LS( \*)? :(.*)$/', $line, $m)) {
            $multiline = ($m[1] === ' *');
            foreach (explode(' ', trim($m[2])) as $cap) {
                // CAP 302 may advertise "capname=value"; store only the name
                $capName = trim(explode('=', $cap)[0]);
                if ($capName !== '') {
                    $serverCaps[] = $capName;
                }
            }
            if (!$multiline) {
                break;
            }
        }
    }

    logEntry("Server advertises caps: " . (empty($serverCaps) ? '(none)' : implode(', ', $serverCaps)), 'DEBUG');

    $capsToRequest = array_values(array_intersect($wantedCaps, $serverCaps));

    if (empty($capsToRequest)) {
        logEntry("None of the requested caps are supported by this server; sending CAP END", 'WARN');
        fputs($socket, "CAP END\r\n");
        return;
    }

    // Phase 2: request caps and wait for ACK/NAK
    fputs($socket, "CAP REQ :" . implode(' ', $capsToRequest) . "\r\n");
    logEntry("CAP REQ: " . implode(', ', $capsToRequest), 'DEBUG');

    $ackedCaps = [];
    $deadline  = time() + 15;

    while (time() < $deadline) {
        $line = fgets($socket);
        if ($line === false) {
            break;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (strncmp($line, 'PING ', 5) === 0) {
            fputs($socket, 'PONG ' . substr($line, 5) . "\r\n");
            continue;
        }
        logEntry("CAP << {$line}", 'DEBUG');
        if (preg_match('/^:\S+ CAP \S+ ACK :(.+)$/', $line, $m)) {
            $ackedCaps = array_values(array_filter(array_map('trim', explode(' ', $m[1]))));
            logEntry("CAP ACK: " . implode(', ', $ackedCaps), 'DEBUG');
            break;
        }
        if (preg_match('/^:\S+ CAP \S+ NAK :(.+)$/', $line, $m)) {
            logEntry("CAP NAK for: " . trim($m[1]) . "; continuing without requested caps", 'WARN');
            fputs($socket, "CAP END\r\n");
            return;
        }
    }

    // Phase 3: SASL PLAIN exchange (only if sasl was acknowledged)
    if ($useSASL && in_array('sasl', $ackedCaps)) {
        if ($config['password'] == "") {
            logEntry("SASL enabled but no password is configured; skipping SASL", 'WARN');
        } else {
            fputs($socket, "AUTHENTICATE PLAIN\r\n");
            logEntry("SASL PLAIN authentication initiated", 'DEBUG');

            $saslComplete = false;
            $deadline     = time() + 15;

            while (time() < $deadline) {
                $line = fgets($socket);
                if ($line === false) {
                    break;
                }
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (strncmp($line, 'PING ', 5) === 0) {
                    fputs($socket, 'PONG ' . substr($line, 5) . "\r\n");
                    continue;
                }
                logEntry("SASL << {$line}", 'DEBUG');
                // Server ready for credentials: AUTHENTICATE +
                if ($line === 'AUTHENTICATE +') {
                    $account = ($config['sasl_account'] != "")
                        ? $config['sasl_account']
                        : $config['nickname'];
                    $payload = base64_encode("\0{$account}\0{$config['password']}");
                    fputs($socket, "AUTHENTICATE {$payload}\r\n");
                    logEntry("SASL credentials sent (account: {$account})", 'DEBUG');
                    continue;
                }
                // 900 RPL_LOGGEDIN
                if (preg_match('/^:\S+ 900 /', $line)) {
                    $parts = explode(' ', $line);
                    logEntry("SASL: logged in as " . ($parts[4] ?? '?'), 'INFO');
                    continue;
                }
                // 903 RPL_SASLSUCCESS
                if (preg_match('/^:\S+ 903 /', $line)) {
                    logEntry("SASL authentication successful", 'INFO');
                    $saslComplete = true;
                    break;
                }
                // 902 ERR_NICKLOCKED, 904 ERR_SASLFAIL, 905 ERR_SASLTOOLONG, 906 ERR_SASLABORTED
                if (preg_match('/^:\S+ (902|904|905|906) /', $line, $errm)) {
                    logEntry("SASL authentication failed (numeric {$errm[1]}); continuing without authentication", 'WARN');
                    $saslComplete = true;
                    break;
                }
            }

            if (!$saslComplete) {
                logEntry("SASL authentication timed out; continuing", 'WARN');
            }
        }
    } elseif ($useSASL) {
        logEntry("SASL requested but not acknowledged by server; continuing without SASL", 'WARN');
    }

    $capList = empty($ackedCaps) ? '(none)' : implode(', ', $ackedCaps);
    logEntry("CAP negotiation complete; active caps: {$capList}", 'INFO');
    fputs($socket, "CAP END\r\n");
}
