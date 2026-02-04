<?php
//This file is reponsible for initializing the circular message buffer.

function initMessageBuffer(){
    global $messageBuffer;
    global $config;

    // the message buffer is an array of arrays
    // the arrays contained in the buffer are structure as such:
    // $messageBufferArray = [$data['usernickname'], $data['fullmessage']]

    //Init the messageBuffer with the max number of messages
    //This way we don't have to keep track of the number of messages in the buffer, the main loop can just unshift + pop values
    for ($i = 0; $i < (int) $config['message_buffer_size']; $i++){
        array_unshift($messageBuffer, array([NULL, NULL])); 
    }
}