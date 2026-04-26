<?php
function loadModules() {
    global $config;
    global $modules;

    $modules = array();
    foreach ($config['modules'] as $module) {
        if ($module != "") {
            $validModule = validateModule($module);
            if ($validModule == "valid") {
                $moduleConfig  = parse_ini_file("{$config['addons_dir']}/modules/{$module}/module.conf");
                $modulesArray  = $moduleConfig['module'];
                foreach ($modulesArray as $mod) {
                    $pieces            = explode("||", $mod);
                    $modules[$pieces[0]] = $pieces[1];
                }
                include("{$config['addons_dir']}/modules/{$module}/module.php");
                logEntry("Loaded module: {$module}", 'INFO');
            } else {
                die("Module '{$module}' reports as invalid.\n");
            }
        }
        $validModule  = "";
        $moduleConfig = "";
    }
    return true;
}
