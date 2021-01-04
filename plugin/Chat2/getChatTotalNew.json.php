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
$obj->total = array();
$obj->error = false;
$obj->to_users_id = 0;
$obj->errorMsg = "";
$obj->myId = 0;

$plugin = AVideoPlugin::loadPluginIfEnabled("Chat2");

if (empty($plugin)) {
    $obj->error = true;
    $obj->errorMsg = "#1 Chat not enabled ".date("m-d-Y")." Update in progress we apologize for the inconvenience. ";
    die(json_encode($obj));
}

ChatOnlineStatus::updateStatus(User::getId());

$obj->myId = User::getId();

$objData = AVideoPlugin::getObjectDataIfEnabled("Chat2");

if(empty($isStandAlone) && $objData->disableLocalChatServer){
    $obj->error = true;
    $obj->errorMsg = "Local Chat Server is disabled, please use an external chat server";
    die(json_encode($obj));
}

$room_users_id = User::getId();
$to_users_id = 0;
if (!empty($_GET['room_users_id'])) {
    $room_users_id = intval($_GET['room_users_id']);
}

if (!empty($_GET['to_users_id'])) {
    $to_users_id = intval($_GET['to_users_id']);
}

if (ChatBan::isUserBanned($room_users_id, User::getId())) {
    $obj->error = true;
    $obj->errorMsg = "You Are Banned";
    die(json_encode($obj));
}

$subscribers = Subscribe::getAllSubscribes($room_users_id);

$_POST['sort']['id'] = 'DESC';
$_REQUEST['rowCount'] = 11;
$_POST['current'] = 1;

if (!empty($to_users_id)) {
    $messages = ChatMessage::newMessages($to_users_id, User::getId());
    $obj->total[$to_users_id] = count($messages);
    $obj->status[$to_users_id] = Chat2::getOnlineStatus($to_users_id);
} else {
    $messages = ChatMessage::newMessagesFromRoom($room_users_id, User::getId());
    $obj->total[0] = count($messages);

    if (User::isLogged()) {
        foreach ($subscribers as $value) {
            if ($value['subscriber_id'] === User::getId()) {
                continue;
            }
            $messages = ChatMessage::newMessages($value['subscriber_id'], User::getId());
            $obj->total[$value['subscriber_id']] = count($messages);
            $obj->status[$value['subscriber_id']] = Chat2::getOnlineStatus($value['subscriber_id']);
        }
    }
}
die(json_encode($obj));

