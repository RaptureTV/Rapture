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
if (!file_exists($configFile)) {
    list($scriptPath) = get_included_files();
    $path = pathinfo($scriptPath);
    $configFile = $path['dirname'] . "/" . $configFile;
}
$objChat = AVideoPlugin::getObjectDataIfEnabled('Chat2');

header('Content-Type: application/json');
$obj = new stdClass();
$obj->filename = "";
$obj->relativePath = "";
$obj->error = true;
$obj->message_id = 0;

if (empty($objChat)) {
    $obj->errorMsg = "Plugin disabled";
    die(json_encode($obj));
}

if (!empty($obj->disableAttachments)) {
    $obj->errorMsg = "Attachment disabled";
    die(json_encode($obj));
}

if (!User::isLogged()) {
    $obj->errorMsg = "User not logged";
    die(json_encode($obj));
}

$to_users_id = intval(@$_POST['to_users_id']);
$room_users_id = intval(@$_POST['room_users_id']);

if (empty($to_users_id) && empty($room_users_id)) {
    $obj->errorMsg = "No user specified";
    die(json_encode($obj));
}

// check if the user is banned
$users_id = !empty($to_users_id) ? $to_users_id : $room_users_id;
if (ChatBan::isUserBanned($users_id, User::getId())) {
    $obj->errorMsg = "You Are Banned";
    die(json_encode($obj));
}

$obj->room_users_id = $room_users_id;
$obj->to_users_id = $to_users_id;

$plugin = AVideoPlugin::loadPluginIfEnabled("Chat2");
$objData = AVideoPlugin::getObjectDataIfEnabled("Chat2");


$allowed = array('jpg', 'jpeg', 'gif', 'png');

if (isset($_FILES['upl']) && $_FILES['upl']['error'] == 0) {
    $extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), $allowed)) {
        $obj->errorMsg = "File extension error [{$_FILES['upl']['name']}], we allow only (" . implode(",", $allowed) . ")";
        die(json_encode($obj));
    }
    $chatMessage = new ChatMessage(0);
    $chatMessage->setMessage("");
    $chatMessage->setFrom_users_id(User::getId());
    $obj->id = $chatMessage->save();
    if (empty($obj->id)) {
        $obj->errorMsg = "Error on save chat";
    } else {
        $paths = ChatMessage::getFilePathFromMessage($obj->id);
        $obj->filename = $paths->filename;
        $obj->relativePath = $paths->relativePath;
        $obj->absolutePath = $paths->absolutePath;
        if (!empty($to_users_id)) {
            $room_users_id = NULL;
        } else if (!empty($room_users_id)) {
            $to_users_id = NULL;
        }
        make_path($obj->absolutePath);
        im_resize_max_size($_FILES['upl']['tmp_name'], $obj->absolutePath, 1024, 1024);
        $chatMessage = new ChatMessage($obj->id);
        $chatMessage->setTo_users_id($to_users_id);
        $chatMessage->setRoom_users_id($room_users_id);
        $chatMessage->setFile($obj->relativePath);
        $obj->message_id = $chatMessage->save();
        $obj->error = false;
    }
}
die(json_encode($obj));
