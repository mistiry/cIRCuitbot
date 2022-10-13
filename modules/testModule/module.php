<?php
function testModule() {
    global $ircdata;
    logEntry("Successfully called test module.");
    sendPRIVMSG($ircdata['location'],"Successful trigger of testModule.");
}