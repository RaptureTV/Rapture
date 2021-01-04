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
$obj->to_users_id = 0;
$obj->errorMsg = "";
$obj->myId = 0;


if (!User::isLogged()) {
    $obj->error = true;
    $obj->errorMsg = "User not logged";
    die(json_encode($obj));
}

$to_users_id = intval(@$_GET['to_users_id']);
if (empty($to_users_id)) {
    $obj->error = true;
    $obj->errorMsg = "No room specified";
    die(json_encode($obj));
}

$lower_then_id = intval(@$_GET['lower_then_id']);

$obj->myId = User::getId();

$plugin = AVideoPlugin::loadPluginIfEnabled("Chat2");
$objData = AVideoPlugin::getObjectDataIfEnabled("Chat2");

$_POST['sort']['id'] = 'DESC';
$_REQUEST['rowCount'] = 10;
$_POST['current'] = 1;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['chatLog']);
if(empty($_SESSION['chatLog'][$to_users_id])){
    $_SESSION['chatLog'][$to_users_id] = array();
    $greater_then_id = 0;
}else{
    $greater_then_id = $_SESSION['chatLog'][$to_users_id][0]['id'];
}

$obj->messages = ChatMessage::getAllFromUsers(User::getId(), $to_users_id, $greater_then_id, $lower_then_id, true);

$_SESSION['chatLog'][$to_users_id] = array_merge($obj->messages, $_SESSION['chatLog'][$to_users_id]);

$obj->messages = $_SESSION['chatLog'][$to_users_id];

die(json_encode($obj));

