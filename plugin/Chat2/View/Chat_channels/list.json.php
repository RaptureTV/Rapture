<?php
require_once '../../../../videos/configuration.php';
require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/Chat_channels.php';
header('Content-Type: application/json');

$rows = Chat_channels::getAll();
?>
{"data": <?php echo json_encode($rows); ?>}