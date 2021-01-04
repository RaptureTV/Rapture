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
$obj->messages = array();
$obj->error = false;
$obj->room_users_id = 0;
$obj->errorMsg = "";
$obj->myId = 0;


$room_users_id = intval(@$_GET['room_users_id']);
if (empty($room_users_id)) {
    $obj->error = true;
    $obj->errorMsg = "No room specified";
    die(json_encode($obj));
}

$lower_then_id = intval(@$_GET['lower_then_id']);

$obj->myId = User::getId();

$plugin = AVideoPlugin::loadPluginIfEnabled("Chat2");

if (empty($plugin)) {
    $obj->error = true;
    $obj->errorMsg = "#2 Chat not enabled ".date("m-d-Y")." Update in progress we apologize for the inconvenience. ";
    die(json_encode($obj));
}

$objData = AVideoPlugin::getObjectDataIfEnabled("Chat2");

// check if the user is banned
if(ChatBan::isUserBanned($room_users_id, User::getId())){
    $obj->error = true;
    $obj->errorMsg = "You Are Banned";
    die(json_encode($obj));
}

$_POST['sort']['id'] = 'DESC';
$_REQUEST['rowCount'] = 10;
$_POST['current'] = 1;

_session_start();
unset($_SESSION['roomLog']);
if(empty($_SESSION['roomLog'][$room_users_id])){
    $_SESSION['roomLog'][$room_users_id] = array();
    $greater_then_id = 0;
}else{
    $greater_then_id = $_SESSION['roomLog'][$room_users_id][0]['id'];
}

$obj->messages = ChatMessage::getAll($room_users_id, $greater_then_id, $lower_then_id, User::isLogged());

$_SESSION['roomLog'][$room_users_id] = array_merge($obj->messages, $_SESSION['roomLog'][$room_users_id]);

$obj->messages = $_SESSION['roomLog'][$room_users_id];

if(!empty($_GET['debug'])){
    var_dump($obj);
}

die(json_encode($obj));

