<?php
require 'config.php';
$isStandAlone = true;
$global['mysqli'] = new mysqli($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDatabase, @$mysqlPort);
require_once $global['systemRootPath'].'standAlone/functions.php';
_session_start();
session_write_close();
require_once $global['systemRootPath'].'standAlone/Object.php';
require_once $global['systemRootPath'].'standAlone/mysql_dal.php';
require_once $global['systemRootPath'].'standAlone/Login.php';
require_once $global['systemRootPath'].'standAlone/User.php';
require_once $global['systemRootPath'].'standAlone/Subscribe.php';
require_once $global['systemRootPath'].'standAlone/AVideoPlugin.php';
require_once $global['systemRootPath'].'standAlone/Plugin.abstract.php';
require_once $global['systemRootPath'].'Chat2.php';
require_once $global['systemRootPath'].'functions.php';
require_once $global['systemRootPath'].'Objects/ChatBan.php';
require_once $global['systemRootPath'].'Objects/ChatOnlineStatus.php';
require_once $global['systemRootPath'].'Objects/ChatMessage.php';
require_once $global['systemRootPath'].'Objects/ChatMessageLog.php';
$advancedCustom = getAdvancedCustom();
$advancedCustomUser = getCustomizeUser();
ob_start();

if (!Login::isLogged() && !empty($_GET['user']) && !empty($_GET['pass'])) {
    Login::run($_GET['user'], $_GET['pass'], true);
}