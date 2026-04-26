<?php
function loadTriggers() {
    global $config;
    global $triggers;

    $triggers = array();
    foreach ($config['triggers'] as $trigger) {
        if ($trigger != "") {
            $validTrigger = validateTrigger($trigger);
            if ($validTrigger == "valid") {
                $triggerConfig  = parse_ini_file("{$config['addons_dir']}/triggers/{$trigger}/trigger.conf");
                $triggersArray  = $triggerConfig['trigger'];
                foreach ($triggersArray as $trig) {
                    $pieces                  = explode("||", $trig);
                    $triggers[$pieces[0]]    = $pieces[1];
                }
                include("{$config['addons_dir']}/triggers/{$trigger}/trigger.php");
                logEntry("Loaded trigger: {$trigger}", 'INFO');
            } else {
                die("Trigger '{$trigger}' reports as invalid.\n");
            }
        }
        $validTrigger  = "";
        $triggerConfig = "";
    }
    return true;
}
