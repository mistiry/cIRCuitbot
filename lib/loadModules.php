<?php
function loadModules() {
    global $config;
    global $modules;
    
    $modules = array();
    foreach($config['modules'] as $module) {
        if($module != "") {
            $validModule = validateModule($module);
            if($validModule == "valid") {
                $moduleConfig = parse_ini_file("".$config['addons_dir']."/modules/".$module."/module.conf");
                $modulesArray = $moduleConfig['module'];
                foreach($modulesArray as $mod) {
                    $pieces = explode("||",$mod);
                    $moduleCmd = $pieces[0];
                    $moduleFunc = $pieces[1];
                    $modules[$moduleCmd] = $moduleFunc;
                }
                include("".$config['addons_dir']."/modules/".$module."/module.php");
            } else {
                die("Module '".$module."' reports as invalid.\n");
            }
        }
        $validModule = "";
        $moduleConfig = "";
    }
    return true;
}
