<?php

header('Content-Type: application/json');
require_once './Login.php';

$result = new stdClass();
$result->isLogged = false;

if (!empty($_REQUEST['inputUser']) && !empty($_REQUEST['inputPassword'])) {
    $result = Login::run($_REQUEST['inputUser'], $_REQUEST['inputPassword']);
}
echo json_encode($result);