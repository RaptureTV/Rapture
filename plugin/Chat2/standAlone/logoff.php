<?php

header('Content-Type: application/json');
require_once './Login.php';

$result = new stdClass();
$result->isLogged = false;

Login::logoff();

if(!empty($_GET['redirect'])){
    header("Location: {$_GET['redirect']}");
    exit;
}
echo json_encode($result);