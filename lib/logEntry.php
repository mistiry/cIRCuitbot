<?php
function logEntry($line, $level = 'INFO') {
    global $config;

    $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARN' => 2, 'ERROR' => 3];
    $configLevel = strtoupper($config['log_level'] ?? 'INFO');
    if (!isset($levels[$configLevel])) $configLevel = 'INFO';
    if (!isset($levels[$level]))       $level       = 'INFO';
    if ($levels[$level] < $levels[$configLevel]) return;

    if (!file_exists($config['log_file'])) {
        touch($config['log_file']);
    }

    $line    = trim(preg_replace('/\s+/', ' ', $line));
    $file    = fopen($config['log_file'], "a");
    $logline = "[" . date("Y-m-d H:i:s T") . "] [" . $level . "] " . $line . "\n";
    fwrite($file, $logline);
    fclose($file);
}
