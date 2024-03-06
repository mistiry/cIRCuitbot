<?php
//Load Library Files - load the files in ./lib/ which contain
//the necessary functions and code to run the core of the bot.
//NOTE: DO NOT PUT MODULES INTO THIS DIRECTORY!!
system("clear");
$libraryFiles = scandir("./lib");
foreach($libraryFiles as $libraryFile) {
    if($libraryFile == "." || $libraryFile == "..") {
        continue;
    } else {
        require("./lib/".$libraryFile."");
    }
}


//Configuration - The bot should be ran with the '-c' flag, pointed
//to a configuration file with valid values. See the included 
//example.conf file to create your bot config.
$configfile = getopt("c:");
if(file_exists($configfile['c'])) {
    $config = parse_ini_file($configfile['c']);
    $validation = validateConfig($config);
    if($validation == false) {
        die("Configuration failed to pass validation checks.\n");
    }
    echo "Running with configuration:\n";
    print_r($config);
} else {
    die("Unable to use '$configfile' - does it exist and have correct permissions?\n");
}

//PHP Runtime Options - These control various PHP settings like the time limit,  
//which must be 0 to allow the bot to run indefinitely.
set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set($config['timezone']);


//Database - Set up and establish the database connection.
$dbconnection = mysqli_connect($config['dbserver'],$config['dbuser'],$config['dbpass'],$config['db']);
if(!$dbconnection) {
    die("Unable to connect to database; error: " . mysqli_connect_error());
}


//Triggers - Load triggers defined in the conf file
$triggers = array();
foreach($config['triggers'] as $trigger) {
    if($trigger != "") {
        $validTrigger = validateTrigger($trigger);
        if($validTrigger == "valid") {
            $triggerConfig = parse_ini_file("./triggers/".$trigger."/trigger.conf");
            $triggersArray = $triggerConfig['trigger'];
            foreach($triggersArray as $trig) {
                $pieces = explode("||",$trig);
                $triggerWord = $pieces[0];
                $triggerFunc = $pieces[1];
                $triggers[$triggerWord] = $triggerFunc;
            }
            include("./triggers/".$trigger."/trigger.php");
        } else {
            die("Trigger '".$trigger."' reports as invalid.\n");
        }
    }
    $validTrigger = "";
    $triggerConfig = "";
}
echo "Loaded triggers:\n";
print_r($triggers);


//Modules - Load modules defined in the conf file
$modules = array();
foreach($config['modules'] as $module) {
    if($module != "") {
        $validModule = validateModule($module);
        if($validModule == "valid") {
            $moduleConfig = parse_ini_file("./modules/".$module."/module.conf");
            $modulesArray = $moduleConfig['module'];
            foreach($modulesArray as $mod) {
                $pieces = explode("||",$mod);
                $moduleCmd = $pieces[0];
                $moduleFunc = $pieces[1];
                $modules[$moduleCmd] = $moduleFunc;
            }
            include("./modules/".$module."/module.php");
        } else {
            die("Module '".$module."' reports as invalid.\n");
        }
    }
    $validModule = "";
    $moduleConfig = "";
}
echo "Loaded modules:\n";
print_r($modules);

//Connection - Open a socket connection to the IRC server, and pass our settings.
$socket = fsockopen($config['server'], $config['port']);
stream_set_blocking($socket, false);
fputs($socket,"USER ".$config['nickname']." ".$config['nickname']." ".$config['nickname']." ".$config['nickname']." :".$config['nickname']."\n");
if($config['password'] != "") {
    fputs($socket,"PASS ".$config['password']."\n");
}
fputs($socket,"NICK ".$config['nickname']."\n");


//Ignored Message Types - Ignore these messages types for logging/output purposes
$ignore = array('001','002','003','004','005','250','251','252','253',
                '254','255','265','266','372','375','376','353','366',
);


//Variable Initialization - Default initial variable values
$ignoredUsers = array();
$timestamp = date("Y-m-d H:i:s T");
$isActivityActive = false;
$timerArray = array();


//Finalize Connection - Sleep briefly to allow server time to respond to login and nickname,
//and then join the channel!
sleep(1);
fputs($socket,"JOIN ".$config['channel']."\n");


//Main Loop - This is the infinite loop where all the magic happens.
while(1) {

    //Timers
    checkTimersForExpiry();

    //Continuously pull new data from the socket
    $data = fgets($socket);
    echo "[DEBUG] Data is: '".$data."'\n";

    if(!strlen($data)>1||is_null($data)||empty($data)) {
        echo "[DEBUG] $data not greater than 1: ".$data."\n";
        continue;
    }

    //Set timestamp to current time and process the line of data
    $timestamp = date("Y-m-d H:i:s T");
    $ircdata = processIRCdata($data);

    // //DEBUG
    // echo "[DEBUG] Timer Array:\n";
    // print_r($timerArray);

    //If the user is ignored, ignore their messages and go to the next line of data
    if(in_array($ircdata['userhostname'],$ignoredUsers)) {
        logEntry("User '".$ircdata['usernickname']."@".$ircdata['userhostname']."' sent data but is ignored.");
        continue;
    }

    //If the message type of the data is one that should be ignored for output purposes, ignore it, otherwise log it
    if(!in_array($ircdata['messagetype'], $ignore)) {
        logEntry($data);
    }

    //Ping Pong - when the IRC sends a PING, respond in kind with a PONG
    if($ircdata['command'] == "PING") {
        logEntry("PONG ".$ircdata['messagetype']."");
        fputs($socket, "PONG ".$ircdata['messagetype']."\n");
    }

    //Bridge Support - If bridge support is enabled, we must alter the data stream and modify
    //the username and hostname the bot sees, as well as do a few other things.
    //NOTE: THIS IS EXPERIMENTAL
    if($config['bridge_enabled'] == true) {
        if($ircdata['usernickname'] == $config['bridge_username'] && $ircdata['userhostname'] == $config['bridge_hostname']) {
            $bridgeMessage = trim($ircdata['fullmessage']);
            $bridgeMessagePieces = explode($config['bridge_right_delimeter'],$bridgeMessage);
            $bridgeUser = trim(str_replace($config['bridge_left_delimeter'],"",$bridgeMessagePieces[0]));
            $bridgeUser = substr($bridgeUser,3,32);
            $ircdata['usernickname'] = trim("".$config['bridge_user_prefix']."".$bridgeUser."");
            switch($config['bridge_user_hostname_middle']) {
                case "user":
                    $bridgeUserHostnameMiddle = str_replace(" ","",$bridgeUser);
                    $bridgeUserHostnameMiddle = trim(substr($bridgeUser,0,12));
                    $bridgeUserHostnameMiddle = "". trim(substr($config['bridge_username'],0,2)) ."-".$bridgeUserHostnameMiddle."";
                    break;
                case "hash":
                    $bridgeUserHostnameMiddle = trim(substr(md5($bridgeUser),0,12));
                    $bridgeUserHostnameMiddle = "". trim(substr($config['bridge_username'],0,2)) ."-".$bridgeUserHostnameMiddle."";
                    break;
            }
            $ircdata['userhostname'] = trim("".$config['bridge_user_hostname_prefix']."".$bridgeUserHostnameMiddle."".$config['bridge_user_hostname_suffix']."");
            logEntry("Remapped relayed message to user '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
            $bridgeMessage = trim(str_replace("".$config['bridge_left_delimeter']."".$bridgeUser."".$config['bridge_right_delimeter']."","",$bridgeMessage));
            $bridgeMessagePieces = explode(" ",$bridgeMessage);
            $firstword = trim(strval($bridgeMessagePieces[1]));
            $firstword = preg_replace('[^\w\d\!]', '', $firstword);
            $ircdata['commandargs'] = trim(str_replace($firstword,"",$bridgeMessage));
            $ircdata['commandargs'] = trim(str_replace($bridgeMessagePieces[0],"",$ircdata['commandargs']));
            $ircdata['fullmessage'] = trim(str_replace($bridgeMessagePieces[0],"",$bridgeMessage));
            $ircdata['isbridgemessage'] = "true";
        }
    }

    //Stats and Known Users - Keep track of all the users the bot sees. If we've seen the user before, just update their entry, and
    //if it is a user the bot has not seen, make a new entry in the database to begin keep stats for that user.
    switch($ircdata['messagetype']) {
        case "JOIN":
        case "PART":
        case "QUIT":
        case "PRIVMSG":
            //If user is joining, check for any modes that we should set on join
            if($ircdata['messagetype'] == "JOIN") {
                doJoinModes($ircdata['userhostname'],$ircdata['usernickname']);
            }

            //Query for existing record
            $query = "SELECT id,nick_aliases,total_words,total_lines FROM known_users WHERE hostname = '".$ircdata['userhostname']."'";
            $result = mysqli_query($dbconnection,$query);

            //Escape the message so we can safely insert it into the database
            $lastmessage = mysqli_real_escape_string($dbconnection, $ircdata['fullmessage']);

            //If $query found a result, we've seen this user so just update their record
            if(mysqli_num_rows($result) > 0) {
                $aliases = array();
                while($row = mysqli_fetch_assoc($result)) {
                    $aliases = $row['nick_aliases'];
                    $rowid = $row['id'];
                    $rowtotalwords = $row['total_words'];
                    $rowtotallines = $row['total_lines'];
                }

                $aliases = unserialize($aliases);
                if(!is_array($aliases)) {
                    $aliases = array();
                }

                //If the current nickname is not part of this user record's nickaliases, add it
                if(!in_array($ircdata['usernickname'], $aliases)) {
                    array_push($aliases,$ircdata['usernickname']);
                }

                //Increment total words and lines
                $totalwords = $rowtotalwords + str_word_count($ircdata['fullmessage']);
                $totallines = $rowtotallines + 1;

                //Serialize nickaliases array for storage
                $nickaliases = serialize($aliases);

                //Compose update query
                $query = "UPDATE known_users SET nick_aliases = '".$nickaliases."', last_datatype = '".$ircdata['messagetype']."', last_message = '".$lastmessage."', last_location = '".$ircdata['location']."', total_words = ".$totalwords.", total_lines = ".$totallines.", timestamp = '".$timestamp."' WHERE id = ".$rowid."";
                
                //Run the query
                if(mysqli_query($dbconnection,$query)) {
                    //logEntry("Updated user record for '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
                    continue;
                } else {
                    logEntry("Unable to update user record for '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
                }
            } else {
                //logEntry("Creating new user record for '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");

                //Count the words and set total lines to 1
                $wordcount = str_word_count($ircdata['fullmessage']);
                $totallines = 1;

                //Initialize the nickaliases array with the nickname and serialize it for storage
                $nickaliases = array("".$ircdata['usernickname']."");
                $nickaliases = serialize($nickaliases);

                //Compose the insert query
                $query = "INSERT INTO known_users (hostname,nick_aliases,last_datatype,last_message,last_location,total_words,total_lines,bot_flags,timestamp) VALUES ('".$ircdata['userhostname']."',',".$nickaliases.",','".$ircdata['messagetype']."','".$lastmessage."','".$ircdata['location']."',".$wordcount.",".$totallines.",'U','".$timestamp."')";
                
                //Run the query
                if(mysqli_query($dbconnection,$query)) {
                    logEntry("Successfully created new user record for '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
                    $nickaliases = "";
                    $aliases = "";
                    $query = "";
                    $result = "";
                } else {
                    logEntry("Failed creating new user record for '".$ircdata['usernickname']."@".$ircdata['userhostname']."'");
                }
            }
    break;
    }

    //Read Chat Data - Here, we read all messages so that we can act accordingly, either by
    //watching for a command, or passive functionality that is triggered solely on content of the message
    if($ircdata['messagetype'] == "PRIVMSG" && $ircdata['location'] == $config['channel']) {
        //First Word - Commands are always the first word of a message, so let's isolate that word so we can check if it is a command
        //Note: If bridge support is enabled, $firstword would already be populated by that codeblock; check for that first!
        if($firstword == "") {
            $messagearray = $ircdata['messagearray'];
            $firstword = trim($messagearray[1]);
        }


        //Passive Triggers - These are items that get triggered passively, meaning no command is required for them to trigger.
        //This allows the bot to detect things, like URL's, within a message and perform an action, rather than explicitly
        //being given a command to run. Not tested with tons of triggers, but either way seems pretty inefficient in its current
        //implementation.
        foreach($triggers as $triggerWord=>$triggerFunc) {
            if(stristr($ircdata['fullmessage'],$triggerWord)) {
                call_user_func($triggerFunc,$ircdata);
            }
        }


        //Channel Command Parsing - This block parses commands that are seen in the main channel, either from modules or built-in commands
        if($firstword[0] == $config['command_flag']) {
            $command = trim(str_replace($config['command_flag'],"",$firstword));
            if(array_key_exists($command,$modules)) {
                call_user_func($modules[$command],$ircdata);
            }
        }

        //EXPERIMENTAL - This is part of bridge support, so if you have that disabled this shouldnt matter at all. Might get a warning about checking
        //a value in $ircdata that doesn't exist. Not all bridge bots may handle things the same, and so this might not work for all. This was tested on
        //a bridge bot using 'matterbridge' and connected to a discord server. It works for that, though!
        if($ircdata['isbridgemessage'] == "true" && $firstword[1] == $config['command_flag']) {
            $firstwordpieces = explode($config['command_flag'],$firstword);
            $command = trim($firstwordpieces[1]);
            if(array_key_exists($command,$modules)) {
                call_user_func($modules[$command],$ircdata);
            }
        }

        //Built-in commands are defined here, as they are not loaded from a module but are part of the core bot
        switch($firstword) {
            case "".$config['command_flag']."help":
                sendHelp();
                break;
            case "".$config['command_flag']."ignore":
            case "".$config['command_flag']."i":
                tempIgnoreUnignore($ircdata['commandargs'],"ignore");
                break;
            case "".$config['command_flag']."unignore":
            case "".$config['command_flag']."ui":
                tempIgnoreUnignore($ircdata['commandargs'],"unignore");
                break;
        }
    }

    //PM Command Parsing - This block parses commands that are seen in the private messages, either from modules or built-in commands
    if($ircdata['messagetype'] == "PRIVMSG" && $ircdata['location'] == $config['nickname']) {
        if($firstword[0] == $config['command_flag']) {
            $command = trim(str_replace($config['command_flag'],"",$firstword));
            if(array_key_exists($command,$modules)) {
                call_user_func($modules[$command],$ircdata['fullmessage']);
            }
            // switch($firstword) {
            //     case "".$config['command_flag']."die":
            //         logEntry("Dying on command.");
            //         die("Dying on command.");
            //         break;
            // }
        }
    }

    //Zero-out variables
    $firstword = "";
    $ircdata = "";
}
?>