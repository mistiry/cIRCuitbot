<?php
function loadTriggers() {
    global $config;
    global $triggers;

    $triggers = array();
    foreach($config['triggers'] as $trigger) {
        if($trigger != "") {
            $validTrigger = validateTrigger($trigger);
            if($validTrigger == "valid") {
                $triggerConfig = parse_ini_file("".$config['addons_dir']."/triggers/".$trigger."/trigger.conf");
                $triggersArray = $triggerConfig['trigger'];
                foreach($triggersArray as $trig) {
                    $pieces = explode("||",$trig);
                    $triggerWord = $pieces[0];
                    $triggerFunc = $pieces[1];
                    $triggers[$triggerWord] = $triggerFunc;
                }
                include("".$config['addons_dir']."/triggers/".$trigger."/trigger.php");
            } else {
                die("Trigger '".$trigger."' reports as invalid.\n");
            }
        }
        $validTrigger = "";
        $triggerConfig = "";
    }
    echo "Loaded triggers:\n";
    print_r($triggers);
    return true;
}
