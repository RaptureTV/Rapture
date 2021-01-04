<?php

if (!isset($global['systemRootPath'])) {
    $configFile = '../../videos/configuration.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        require_once './standAlone/standAloneConfigurations.php';
    }
}
session_write_close();
header('Content-Type: application/json');
$obj = new stdClass();
$obj->messages = array();
$obj->error = false;
$obj->users = array();

if (!User::isAdmin()) {
    $obj->error = true;
    $obj->errorMsg = "User not admin";
    die(json_encode($obj));
}

$u = User::getAllUsers();

foreach ($u as $value) {
    $obj->users[] = array('id' => $value['id'], 'identification' => mb_convert_encoding($value['identification'], 'UTF-8', 'UTF-8'));
}
die(json_encode($obj));

