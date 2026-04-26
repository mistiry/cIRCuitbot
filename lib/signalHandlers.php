<?php
function handleShutdown() {
    global $socket;
    if (isset($socket) && is_resource($socket)) {
        fputs($socket, "QUIT :Shutting down\r\n");
        sleep(1);
        fclose($socket);
    }
    exit(0);
}

function registerSignalHandlers() {
    if (!function_exists('pcntl_async_signals')) {
        logEntry("pcntl extension not available; graceful shutdown on SIGTERM/SIGINT disabled", 'WARN');
        return;
    }
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, 'handleShutdown');
    pcntl_signal(SIGINT,  'handleShutdown');
    logEntry("Signal handlers registered (SIGTERM, SIGINT)", 'DEBUG');
}
