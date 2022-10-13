<?php
function testTrigger() {
    global $ircdata;
    logEntry("Successfully called test trigger.");
    sendPRIVMSG($ircdata['location'],"Successful trigger of testTrigger.");
}