<?php
if(empty($isStandAlone)){
    require_once $global['systemRootPath'] . 'plugin/Plugin.abstract.php';
    require_once $global['systemRootPath'] . 'objects/subscribe.php';
    require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/ChatMessage.php';
    require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/ChatBan.php';
    require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/ChatOnlineStatus.php';
    require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/Chat_channels.php';
}
//require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/ChatOnlineStatus.php';

class Chat2 extends PluginAbstract {

    public function getTags() {
        return array(
            PluginTags::$LIVE,
            PluginTags::$RECOMMENDED,
        );
    }
    
    public function getDescription() {
        global $global;
        $desc = "A multiple proposes Chat, With moderation and Images share support <br>";
        $desc .= "<a href='https://demo.avideo.com/plugin/Chat2/standAlone/instructions.php'> Configure Stand Alone Chat</a>";
        return $desc;
    }

    public function getName() {
        return "Chat2";
    }

    public function getUUID() {
        return "52chata2-3f14-49db-958e-15ccb1a07f0e";
    }

    public function getPluginVersion() {
        return "4.0";
    }
    
    public function updateScript() {
        global $global;
        //update version 2.0
        if(AVideoPlugin::compareVersion($this->getName(), "2.0")<0){
            sqlDal::executeFile($global['systemRootPath'] . 'plugin/Chat2/install/updateV2.0.sql');
        }    
        if(AVideoPlugin::compareVersion($this->getName(), "3.0")<0){
            sqlDal::executeFile($global['systemRootPath'] . 'plugin/Chat2/install/updateV3.0.sql');
        }        
        return true;
    }

    public function getEmptyDataObject() {
        global $global;
        $obj = new stdClass();
        $obj->backgroundImage = "{$global['webSiteRootURL']}plugin/Chat2/bg.png"; //
        $obj->backgroundColor = "#e5ddd5";
        $obj->charLimit = 255;
        $obj->showOnChannel = true;
        $obj->showOnLive = true;
        $obj->showOnUserVideos = true;
        $obj->showOnEmbedVideos = false;
        $obj->showOnMobile = true;
        $obj->startMinimized = false;
        $obj->startMinimizedOnMobile = true;
        $obj->positionOnLeft = false;
        $obj->minWidth = '380px';
        $obj->maxWidth = '2000px';
        $obj->height = '70vh';
        $obj->opacityDefault = 90;
        $obj->opacityHover = 100;
        $obj->noFadeoutBallons = false;
        $obj->onlineSecondsTolerance = 30;
        $obj->requestsTimeout = 3000;
        $obj->showChatOnlyForLoggedUsers = false;
        $obj->disableHoverEffect = false;
        $obj->disableAttachments = false;
        $obj->disableMoreChatRooms = false;
        $obj->useStaticLayout = true;
        $obj->inPlayerLayout = false;
        $obj->disableLocalChatServer = false;

        $obj->openChatButtonStyle = "";
        return $obj;
    }

    public function getFooterCode() {
        global $global;
        $room_users_id = $this->showChat();
        if (empty($room_users_id)) {
            return "";
        }

        if (ChatBan::isUserBanned($room_users_id, User::getId())) {
            return "";
        }
        
        if(method_exists('User', 'hasBlockedUser') && User::hasBlockedUser($room_users_id)){
            return "";
        }
        
        $obj = $this->getDataObject();
        $content = '';
        if (empty($obj->positionOnLeft)) {
            $content .= '<style>#yptchat2{right:0;}</style>';
        }
        $content .= '<style>#yptchat2{opacity: ' . ($obj->opacityDefault / 100) . ';filter: alpha(opacity=' . $obj->opacityDefault . ');height:calc(100% + 22px);min-width:' . $obj->minWidth . ';max-width:' . $obj->maxWidth . ';}</style>';

        $hover = ":hover";
        if ((!empty($obj->disableHoverEffect) || !empty($obj->useStaticLayout)) && empty($obj->inPlayerLayout)) {
            $hover = "";
        }

        $content .= '<style>#yptchat2' . $hover . '{opacity: ' . ($obj->opacityHover / 100) . ';filter: alpha(opacity=' . $obj->opacityHover . ');height:' . $obj->height . ';max-height:calc(100% - 60px);}</style>';
        $content .= '<style>#yptchat2' . $hover . ' .yptchat2Fade{opacity: 1;filter: alpha(opacity=100);}</style>';
        $content .= file_get_contents("{$global['systemRootPath']}plugin/Chat2/chatIframe.html");

        if ($this->startMinimized()) {
            $content .= '<script>$(function () {startIframeChat2(true);});</script>';
        } else {
            $content .= '<script>$(function () {startIframeChat2(false);});</script>';
        }
        $noFade = (($obj->noFadeoutBallons || !empty($obj->useStaticLayout) && empty($obj->inPlayerLayout)) ? 1 : 0);
        
        $iframeURL = self::getChatRoomFromUsersID($room_users_id)."&iframe=1&noFade={$noFade}&showCollapseButtons=1";
        
        return str_replace(array("{iframeURL}"), array($iframeURL), $content);
    }
    
    static function getChatRoomFromUsersID($room_users_id, $getLogin = true){
        global $global;
        $iframeURL = "{$global['webSiteRootURL']}plugin/Chat2/?room_users_id={$room_users_id}"; 
        if(!empty($room_users_id)){
            $url = self::roomHasServer($room_users_id);
            if(!empty($url)){
                $iframeURL = "{$url}?room_users_id={$room_users_id}";
                if($getLogin && User::isLogged()){
                    $iframeURL .= "&user=".User::getUserName()."&pass=". User::getUserPass();
                }
            }
        }
        return $iframeURL;
    }
    
    static function roomHasServer($room_users_id){
        $chat = Chat_channels::getFromUsersId($room_users_id);
        if(!empty($chat) && $chat['status'] == 'a'){
            return $chat['url'];
        }
        
        // check for a default room
        $chat = Chat_channels::getFromUsersId(0); 
        if(!empty($chat) && $chat['status'] == 'a'){
            return $chat['url'];
        }
        
        return false;
    }

    private function startMinimized() {
        $obj = $this->getDataObject();
        if ($obj->useStaticLayout || $obj->inPlayerLayout) {
            return false;
        }
        if ($obj->startMinimizedOnMobile && isMobile()) {
            return true;
        }

        $users_id = User::getId();
        if (!empty($_COOKIE['yptChat2Minimized' . $users_id])) {
            if ($_COOKIE['yptChat2Minimized' . $users_id] == 'true') {
                return true;
            }
            return false;
        }

        $obj = $this->getDataObject();
        return !empty($obj->startMinimized);
    }

    function showChat() {
        $obj = $this->getDataObject();
        if ($obj->showChatOnlyForLoggedUsers && !User::isLogged()) {
            return false;
        }
        if (!empty($_GET['noChat'])) {
            return false;
        }

        $baseName = basename($_SERVER["SCRIPT_FILENAME"]);
        //var_dump($_SERVER);
        //echo $baseName;exit;
        if ($obj->showOnEmbedVideos && stripos($baseName, 'embed') !== false) {
            return $this->getRoomId();
        } else if (empty($obj->showOnEmbedVideos) && (stripos($baseName, 'embed') !== false || !empty($_GET['embed']))) {
            return false;
        }
        if ($obj->showOnChannel && strpos($baseName, 'channel.') !== false) {
            return $this->getRoomId();
        }
        if ($obj->showOnLive && (strpos($baseName, 'live') !== false || strpos($_SERVER["SCRIPT_NAME"], 'plugin/Live/index.php') !== false || strpos($_SERVER["SCRIPT_NAME"], 'plugin/LiveLinks/view/Live.php') !== false )) {
            return $this->getRoomId();
        }
        if ($obj->showOnUserVideos && (!empty($_GET['videoName']) || strpos($baseName, 'modeYoutube') !== false)) {
            return $this->getRoomId();
        }
        return false;
    }

    private function getRoomId() {
        if (!empty($_GET['v'])) {
            $video = Video::getVideoLight($_GET['v']);
            if (!empty($video)) {
                return $video['users_id'];
            }
        } else if (!empty($_GET['videoName'])) {
            $video = Video::getVideoFromCleanTitle($_GET['videoName']);
            if (!empty($video)) {
                return $video['users_id'];
            }
        } else if (!empty($_GET['c'])) {
            $_GET['c'] = xss_esc($_GET['c']);
            $user = User::getChannelOwner($_GET['c']);
            if (!empty($user)) {
                return $user['id'];
            }
        } else if (!empty($_GET['u'])) {
            $user = User::getFromUsername($_GET['u']);
            if (!empty($user)) {
                return $user['id'];
            }
        } else if (!empty($_GET['channelName'])) {
            $_GET['channelName'] = xss_esc($_GET['channelName']);
            $user = User::getChannelOwner($_GET['channelName']);
            if (!empty($user)) {
                return $user['id'];
            }
        } else if (!empty($_GET['link'])) {
            $_GET['link'] = intval($_GET['link']);
            $liveLink = new LiveLinksTable($_GET['link']);
            if (!empty($liveLink)) {
                return $liveLink->getUsers_id();
                ;
            }
        }
        return User::getId();
    }

    static function canAdminChat($room_users_id) {
        if (User::isAdmin()) {
            return true;
        }
        if ($room_users_id == User::getId()) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param type $users_id
     * @return string
     *  o = online
      a = away
      i = invisible
      f = offline
     */
    static function getOnlineStatus($users_id) {
        $onlineStatus = new ChatOnlineStatus(0);
        $onlineStatus->loadFromUsersID($users_id);
        $status = $onlineStatus->getStatus();
        if ($status == 'o') {
            $obj = AVideoPlugin::getObjectData('Chat2');
            $time = strtotime($onlineStatus->getModified());
            $now = strtotime($onlineStatus->getNow()); 
            if ($time + $obj->onlineSecondsTolerance < $now) {
                return 'f';
            }
        }

        return $status;
    }

    static function isUserOnline($users_id) {
        $status = self::getOnlineStatus($users_id);
        if ($status == 'o') {
            return true;
        }
        return false;
    }

    public function getMobileInfo() {
        $obj = $this->getDataObject();
        $return = new stdClass();
        $return->showOnMobile = $obj->showOnMobile;
        return $return;
    }

    public function getHeadCode() {
        global $global, $modeYouTubeTime;
        $obj = $this->getDataObject();
        if (!$this->showChat() || (empty($obj->useStaticLayout) && empty($obj->inPlayerLayout))) {
            return "";
        }
        $js = $css = "";
        $isLive = !empty($_GET['u']) || !empty($_GET['link']) || strpos($_SERVER["SCRIPT_FILENAME"], 'plugin/Live/index.php') !== false;
        if (!empty($obj->inPlayerLayout) && (!empty($modeYouTubeTime) || $isLive)) {
            $file = "plugin/Chat2/staticInPlayer.css";
            $filejs = "plugin/Chat2/staticInPlayer.js";
            $css .= "<link href=\"{$global['webSiteRootURL']}{$file}?" . filemtime("{$global['systemRootPath']}{$file}") . "\" rel=\"stylesheet\" type=\"text/css\"/>";
            $js .= "<script src=\"{$global['webSiteRootURL']}{$filejs}?" . filemtime("{$global['systemRootPath']}{$filejs}") . "\" type=\"text/javascript\"></script>";
        } else if (!empty($modeYouTubeTime)) {
            $file = "plugin/Chat2/static.css";
            $filejs = "plugin/Chat2/static.js";
            $css .= "<link href=\"{$global['webSiteRootURL']}{$file}?" . filemtime("{$global['systemRootPath']}{$file}") . "\" rel=\"stylesheet\" type=\"text/css\"/>";
            $js .= "<script src=\"{$global['webSiteRootURL']}{$filejs}?" . filemtime("{$global['systemRootPath']}{$filejs}") . "\" type=\"text/javascript\"></script>";
        } else if ($isLive) {
            $file = "plugin/Chat2/static.css";
            $filejs = "plugin/Chat2/static.js";
            $css .= "<link href=\"{$global['webSiteRootURL']}{$file}?" . filemtime("{$global['systemRootPath']}{$file}") . "\" rel=\"stylesheet\" type=\"text/css\"/>";
            $js .= "<script src=\"{$global['webSiteRootURL']}{$filejs}?" . filemtime("{$global['systemRootPath']}{$filejs}") . "\" type=\"text/javascript\"></script>";
        }
        if (!$this->allowMoveAndClose()) {
            $css .= "<style>#yptchat2Close, #yptchat2Move{display: none !important;}</style>";
            $js .= "<script>Chat2resize=false;</script>";
        } else {
            $js .= "<script>Chat2resize=true;</script>";
        }

        return $css . $js;
    }

    function allowMoveAndClose() {
        if (strpos($_SERVER["SCRIPT_NAME"], 'view/channel.php') !== false) {
            return true;
        }
        $obj = $this->getDataObject();
        if (!empty($obj->useStaticLayout) || !empty($obj->inPlayerLayout)) {
            return false;
        }
        return true;
    }
    
    public function getPluginMenu() {
        global $global;
        $menu = '<a href="plugin/Chat2/api.html" class="btn btn-primary btn-sm btn-xs btn-block"><i class="fa fa-cog"></i> API</a>';
        $menu .= '<a href="plugin/Chat2/View/editor.php" class="btn btn-primary btn-sm btn-xs btn-block"><i class="fa fa-edit"></i> External Chats</a>';
        $menu .= '<a href="plugin/Chat2/listRooms.php" class="btn btn-primary btn-sm btn-xs btn-block"><i class="far fa-comment-dots"></i> Chat Rooms</a>';
        return $menu;
    }
    
    public function afterDonation($from_users_id, $how_much, $videos_id, $users_id) {
        if(!empty($videos_id)){
            $video = new Video("", "", $videos_id);
            if(!empty($video->getUsers_id())){
                $users_id = $video->getUsers_id();
            }
        }
        if(empty($users_id)){
            _error_log("Chat2:afterDonation Users_id is empty");
            return false;
        }else{
            $chatMessage = new ChatMessage(0);
            $chatMessage->setMessage(User::getNameIdentificationById($from_users_id). " thanks for your donation of ".YPTWallet::formatCurrency($how_much));
            $chatMessage->setFrom_users_id($users_id);
            $chatMessage->setTo_users_id($from_users_id);
            $chatMessage->setRoom_users_id($users_id);
            $id = $chatMessage->save();
            if (empty($id)) {
                _error_log("Chat2:afterDonation Error on save chat");
                return false;
            }
        }
        return true;
    }

}
