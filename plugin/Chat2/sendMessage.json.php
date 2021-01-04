<?php
if (!isset($global['systemRootPath'])) {
    $configFile = '../../videos/configuration.php';
    if(file_exists($configFile)){
        require_once $configFile;
    }else{
        require_once './standAlone/standAloneConfigurations.php';
    }
}
session_write_close();
header('Content-Type: application/json');
$obj = new stdClass();
$obj->id = 0;
$obj->name = User::getNameIdentification();
$obj->message = $_REQUEST['message'];
$obj->messageFooter = date("h:i:s d/m/Y");
$obj->error = true;

if (!User::isLogged()) {
    $obj->errorMsg = "User not logged";
    die(json_encode($obj));
}

$to_users_id = intval(@$_GET['users_id']);
$room_users_id = intval(@$_GET['room_users_id']);

if (empty($to_users_id) && empty($room_users_id)) {
    $obj->errorMsg = "No user specified";
    die(json_encode($obj));
}

$plugin = AVideoPlugin::loadPluginIfEnabled("Chat2");
$objData = AVideoPlugin::getObjectDataIfEnabled("Chat2");

// check if the user is banned
$users_id = !empty($to_users_id)?$to_users_id:$room_users_id;
if(ChatBan::isUserBanned($users_id, User::getId())){
    $obj->errorMsg = "You Are Banned";
    die(json_encode($obj));
}

if (!empty($_REQUEST['message'])) {
    $obj->users_id = User::getId();
    $chatMessage = new ChatMessage(0);
    $chatMessage->setMessage($_REQUEST['message']);
    $chatMessage->setFrom_users_id($obj->users_id);
    $chatMessage->setTo_users_id($to_users_id);
    $chatMessage->setRoom_users_id($room_users_id);
    $obj->id = $chatMessage->save();
    $obj->to_users_id = $to_users_id;
    $obj->room_users_id = $room_users_id;
    if (empty($obj->id)) {
        $obj->errorMsg = "Error on save chat";
    }else{
        $obj->message = textToLink($obj->message, true);
        $obj->error = false;        
    }
}
die(json_encode($obj));