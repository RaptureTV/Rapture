<?php
header('Content-Type: application/json');
require_once '../../../../videos/configuration.php';
require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/Chat_channels.php';

$obj = new stdClass();
$obj->error = true;
$obj->msg = "";

$plugin = AVideoPlugin::loadPluginIfEnabled('Chat2');
                                                
if(!User::isAdmin()){
    $obj->msg = "You cant do this";
    die(json_encode($obj));
}

$o = new Chat_channels(@$_POST['id']);
$o->setName($_POST['name']);
$o->setUrl($_POST['url']);
$o->setUsers_id($_POST['users_id']);
$o->setStatus($_POST['status']);

if($id = $o->save()){
    $obj->error = false;
}

echo json_encode($obj);
