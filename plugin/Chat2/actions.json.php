<?php
global $global, $config;
if (!isset($global['systemRootPath'])) {
    $configFile = '../../videos/configuration.php';
    if(file_exists($configFile)){
        require_once $configFile;
    }else{
        require_once './standAlone/standAloneConfigurations.php';
    }
}

if (!User::isLogged()) {
    return false;
}

$obj = AVideoPlugin::getObjectDataIfEnabled('Chat2');

$json = new stdClass();
$json->error = true;
$json->msg = "";
        
if (empty($obj)) {
    $json->msg = "Chat Not enabled";
    die(json_encode($json));
}
$room_users_id = intval(@$_GET['room_users_id']);
if (empty($room_users_id)) {
    $json->msg = "Chat Room Error";
    die(json_encode($json));
}

$channelOwner = new User($room_users_id);

if(empty($channelOwner)){
    $json->msg = "Invalid room ID";
    die(json_encode($json));
}

if(Chat2::canAdminChat($room_users_id)){
    if(!empty($_GET['chat_messages_id'])){
        if(ChatMessage::deleteMessage($_GET['chat_messages_id'])){
            $json->error = false;
        }else{
            $json->msg = "Error on delete message";
        }
    }
    if(!empty($_GET['clear'])){
        if(ChatMessage::deleteAllFromRoom($room_users_id)){
            $json->error = false;
        }else{
            $json->msg = "Error clear messages";
        }
    }
    if(!empty($_GET['ban'])){
        $ban = new ChatBan(0);
        $ban->setUsers_id($room_users_id);
        $ban->setBanned_users_id($_GET['ban']);
        if($ban->save()){
            $json->error = false;
        }else{
            $json->msg = "Error on ban user";
        }
    }
    if(!empty($_GET['removeBan'])){
        if(ChatBan::removeBan($room_users_id, $_GET['removeBan'])){
            $json->error = false;
        }else{
            $json->msg = "Error on ban user";
        }
    }
}
die(json_encode($json));
?>