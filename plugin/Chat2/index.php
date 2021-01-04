<?php
global $global, $config;
if (!isset($global['systemRootPath'])) {
    $configFile = '../../videos/configuration.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        require_once './standAlone/standAloneConfigurations.php';
    }
}
if (empty($isStandAlone)) {
    $isStandAlone = 0;
}
if (empty($isStandAlone)) {
    require_once $global['systemRootPath'] . 'plugin/Chat2/functions.php';
    $chatRoomURL = "{$global['webSiteRootURL']}plugin/Chat2/";
    $chatRoomPath = "{$global['systemRootPath']}plugin/Chat2/";
} else {
    $chatRoomURL = "{$global['webSiteRootURLStandAlone']}";
    $chatRoomPath = "{$global['systemRootPath']}";
}

$obj = AVideoPlugin::getObjectDataIfEnabled('Chat2');

if (empty($obj)) {
    echo "#3 Chat not enabled " . date("m-d-Y") . " Update in progress we apologize for the inconvenience. ";
    exit;
}

$room_users_id = intval(@$_GET['room_users_id']);
$to_users_id = intval(@$_GET['to_users_id']);
if (empty($room_users_id)) {
    if (empty($to_users_id)) {
        echo "Chat Room Error";
        exit;
    }
    $channelOwner = new User($to_users_id);
} else {
    $channelOwner = new User($room_users_id);
}


if (empty($channelOwner)) {
    echo "Invalid room ID";
    exit;
}

if ($obj->showChatOnlyForLoggedUsers && !User::isLogged()) {
    echo "User not logged";
    exit;
}

if (empty($isStandAlone)) {
    // check if there is a stand alone page setup
    $server = Chat2::roomHasServer($channelOwner->getBdId());
    if (!empty($server)) {
        $oldURL = getSelfURI();
        $newURL = str_replace($global['webSiteRootURL'] . "plugin/Chat2/index.php", $server, $oldURL);
        $newURL = str_replace($global['webSiteRootURL'] . "plugin/Chat2", $server, $oldURL);
        if ($newURL !== $oldURL) {
            header("Location: $newURL");
            exit;
        }
    }
    if ($obj->disableLocalChatServer) {
        echo "Local Chat Server is disabled, please use an external chat server";
        exit;
    }
}

// for mobile login
if (!empty($_GET['user']) && !empty($_GET['pass'])) {
    $user = $_GET['user'];
    $password = $_GET['pass'];

    $userObj = new User(0, $user, $password);
    $userObj->login(false, true);
}

$canAdminChat = Chat2::canAdminChat($room_users_id);

if ($canAdminChat) {
    if (!empty($_GET['clear'])) {
        ChatMessage::deleteAllFromRoom($room_users_id);
        header("Location: {$chatRoomURL}?room_users_id={$room_users_id}");
    }
} else {
    if (ChatBan::isUserBanned($room_users_id, User::getId())) {
        echo "You Are Banned";
        exit;
    }
}

if (method_exists('User', 'hasBlockedUser') && User::hasBlockedUser($room_users_id, User::getId())) {
    echo "This user is blocked";
    exit;
}
$hover = ":hover";
if (!empty($obj->disableHoverEffect) || !empty($obj->useStaticLayout)) {
    $hover = "";
}

$chatClassCol1 = "col-lg-2 col-md-3 col-sm-4 col-xs-2";
$chatClassCol2 = "col-lg-10 col-md-9 col-sm-8 col-xs-10";

if (!User::isLogged()) {
    $chatClassCol1 = "";
    $chatClassCol2 = "col-lg-12 col-md-12 col-sm-12 col-xs-12";
}
// chat owner
?>
<!DOCTYPE html>
<html lang="">
    <head>
        <?php echo!empty($isStandAlone) ? "<!-- stand alone -->" : ""; ?>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-3.5.1.min.js" type="text/javascript"></script>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/seetalert/sweetalert.min.js" type="text/javascript"></script>
        <?php
        echo AVideoPlugin::getHeadCode();
        ?>
        <script>
            var webSiteRootURL = '<?php echo $global['webSiteRootURL']; ?>';
            var webSiteRootURLChat2 = '<?php echo empty($isStandAlone) ? $global['webSiteRootURL'] . "plugin/Chat2/" : $global['webSiteRootURLStandAlone']; ?>';
            var room_users_id = '<?php echo $room_users_id; ?>';
            var to_users_id = '<?php echo $to_users_id; ?>';
            var users_id = '<?php echo User::getId(); ?>';
            var requestsTimeout = '<?php echo $obj->requestsTimeout; ?>';
<?php
if (User::isLogged()) {
    $credentials = "user=" . urlencode(User::getUserName()) . "&pass=" . urlencode(User::getUserPass());
} else {
    $credentials = "";
}
echo "var credentials = '{$credentials}';";
echo "var credentialsE = '&{$credentials}';";
?>
        </script>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="view/img/favicon.ico">
        <title>Chat</title>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-toast/jquery.toast.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/css/fontawesome-free-5.5.0-web/css/all.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/emojionearea/emojionearea.min.css" rel="stylesheet">
        <link href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/style.css?<?php echo @filemtime("{$global['systemRootPath']}plugin/Chat2/style.css"); ?>" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/mini-upload-form/assets/css/style.css" rel="stylesheet" />
        <style>
            /*
            *{
                transition: all 0.3s;
            }
            */
            .chat{
                background-color: <?php echo $obj->backgroundColor; ?>;
                background-image: url(<?php echo $obj->backgroundImage; ?>);
            }
            <?php
            if (!empty($_GET['iframe'])) {
                ?>
                .chat, #chatPanel{
                    background-image: none;
                    background-color: transparent;
                    <?php
                    if (empty($_GET['noFade'])) {
                        ?>
                        -webkit-mask-image: -webkit-gradient(linear, left top, 
                            left bottom, from(rgba(0,0,0,0)), to(rgba(0,0,0,1)));

                        <?php
                    }
                    ?>
                }

                .panel-default{
                    border-color: transparent;
                }

                .panel, .panel-heading, #roomButton{
                    opacity: 0;
                    filter: alpha(opacity=0); /* For IE8 and earlier */
                }
                .panel-footer{
                    background-image: none;
                    background-color: transparent;
                    border-color: transparent;
                }

                .bubble{
                    box-shadow: 2px 2px 10px black;   
                }
                <?php
            }
            ?><?php
            if (empty($_GET['bubblesOnly']) || !empty($obj->inPlayerLayout)) {
                ?>
                body<?php echo $hover; ?> .chat{
                    background-color: <?php echo $obj->backgroundColor; ?>;
                    background-image: url(<?php echo $obj->backgroundImage; ?>);
                    -webkit-mask-image: none;
                }

                #chatPanel, 
                body<?php echo $hover; ?> .panel, 
                body<?php echo $hover; ?> .panel-heading, 
                body<?php echo $hover; ?> #roomButton{
                    opacity: 1;
                    filter: alpha(opacity=100); /* For IE8 and earlier */
                    -webkit-mask-image: none;
                }
                body<?php echo $hover; ?> .panel-default,
                body<?php echo $hover; ?> .panel-footer{
                    border-color: #ddd;
                }

                body<?php echo $hover; ?> .panel-footer{
                    background-color: #f5f5f5;
                }

                body<?php echo $hover; ?> .bubble{
                    box-shadow: 0 0 0;
                }
                <?php
            } else {
                ?>
                .panel-footer, #divRooms, .panel-heading{
                    display: none;
                }
                #chatPanel{
                    opacity: 1;
                    filter: alpha(opacity=100); /* For IE8 and earlier */
                    -webkit-mask-image: none;
                }
                #divChat{
                    width: 100%;
                }
                .panel-body {
                    top: 0;
                    bottom: 0;
                }
                <?php
            }
            ?>
            ul.list-group{
                margin: 0;  
            }
            .minimized{
                width: 100%;
            }
        </style>
    </head>

    <body style="background-color: transparent;">
        <div class="container-fluid">
            <div class="row" id="divChatRow">
                <div class="<?php echo $chatClassCol1; ?>" style="padding: 0;" id="divRooms">
                    <?php
                    if (User::isLogged()) {
                        ?>
                        <div class="panel panel-default top0Radius">
                            <div class="panel-heading top0Radius">
                                <img src="<?php echo User::getPhoto(); ?>" class="img img-circle img-responsive pull-left">                            
                                <div class="hidden-xs"><?php echo User::getNameIdentification(); ?></div>
                            </div>
                            <div class="panel-body" style="bottom: 0; padding: 0;">
                                <ul class="list-group" id="onlineList">
                                    <?php
                                    if (!empty($to_users_id)) {
                                        listItem($to_users_id, false);
                                    } else
                                    if (User::isLogged()) {
                                        listItem($room_users_id, true);
                                        $subscribers = Subscribe::getAllSubscribes($room_users_id);
                                        foreach ($subscribers as $value) {
                                            if ($value['subscriber_id'] === User::getId()) {
                                                continue;
                                            }
                                            if (ChatBan::isUserBanned($room_users_id, $value['subscriber_id'])) {
                                                listItem($value['subscriber_id'], false, true);
                                            } else {
                                                listItem($value['subscriber_id'], false);
                                            }
                                        }
                                    }
                                    ?>
                                </ul> 
                                <ul class="list-group" id="offlineList">
                                </ul> 
                            </div>
                        </div>
                        <button id="roomButton" class="btn btn-default btn-xs"><i class="fas fa-users"></i></button>
                        <?php
                    }
                    ?>
                </div>
                <div class="<?php echo $chatClassCol2; ?>" style="padding: 0;" id="divChat">
                    <div class="panel panel-default top0Radius" id="chatPanel">
                        <div class="panel-heading top0Radius" style="display: none;">
                            <div class="pull-left" style="min-width: fit-content;">
                                <img src="<?php echo $channelOwner->getPhotoDB(); ?>" class="img img-circle img-responsive pull-left" id="talkToImage"> 

                            </div>
                            <div id="talkToNameId" class="pull-left"><?php echo empty($to_users_id) ? $channelOwner->getChannelName() : $channelOwner->getNameIdentificationBd(); ?></div>
                            <?php
                            if (empty($_GET['mobileMode'])) {
                                ?>
                                <div class="btn-group" style="position: absolute; right: 15px; top: 8px; ">
                                    <div class="dropdown" style="float: left;">                                        
                                        <button class="btn  btn-default dropdown-toggle" type="button" data-toggle="dropdown" style="
                                        <?php
                                        if (empty($obj->disableAttachments)) {
                                            ?>border-top-right-radius: 0;
                                                    border-bottom-right-radius: 0; border-right-width: 0;<?php
                                                }
                                                ?>"   data-placement="bottom"  
                                                title="<?php echo __('Actions'); ?>">
                                            <i class="fas fa-ellipsis-v" ></i>
                                        </button>
                                        <ul class="dropdown-menu" style="right: 0; left: auto;">
                                            <li class="dropdown-header"><i class="fas fa-user-cog"></i> <?php echo __('Actions'); ?></li>
                                            <li><a href="#" onclick="goToChannel(to_users_id);"><i class="fas fa-play-circle"></i>  <?php echo __('Channel'); ?></a></li>
                                            <li><a href="<?php echo $chatRoomURL; ?>?room_users_id=<?php echo $room_users_id; ?>" target="_blank"><i class="fas fa-external-link-square-alt"></i>  <?php echo __('Open in a new Tab'); ?></a></li>

                                            <?php
                                            if (empty($obj->disableMoreChatRooms)) {
                                                ?>
                                                <li>
                                                    <a href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/listRooms.php" target="_parent">
                                                        <i class="far fa-comment-dots"></i>
                                                        <?php echo __('More Chat Rooms'); ?>
                                                    </a>
                                                </li>

                                                <?php
                                            }
                                            ?>
                                            <li>
                                                <a>
                                                    <i class="fas fa-scroll"></i> 
                                                    <input type="checkbox" id="autoscroll" checked="checked"/>
                                                    <label for="autoscroll"><?php echo __('Auto Scroll'); ?></label>
                                                </a>
                                            </li>
                                            <?php
                                            if ($canAdminChat) {
                                                ?>
                                                <li class="divider"></li>
                                                <li class="dropdown-header"><i class="fas fa-user-tie"></i>  <?php echo __('Channel Admin'); ?></li>
                                                <li class="hiddenOnRoom" id="banFromChat"><a href="#" onclick="banFromChat(to_users_id);"><i class="fas fa-ban"></i>  <?php echo __('Ban From Chat'); ?></a></li>
                                                <li class="hiddenOnRoom" id="removeBanFromChat"><a href="#" onclick="removeBanFromChat(to_users_id);"><i class="fas fa-ban"></i>  <?php echo __('Remove Ban From Chat'); ?></a></li>
                                                <li class="hiddenOnChat"><a href="<?php echo $chatRoomURL; ?>?room_users_id=<?php echo $room_users_id; ?>&clear=1"><i class="fas fa-trash"></i>  <?php echo __('Clear Chat'); ?></a></li>
                                                <li class="divider"></li>
                                                <li class="dropdown-header"><i class="fas fa-code"></i>  <?php echo __('Embed Codes'); ?></li>
                                                <li class="hiddenOnChat">
                                                    <a href="#" onclick="copyChatToClipboard($('#roomEmbedCode').val());" ><span class="fa fa-copy"></span> <span id="btnChatEmbedText"><?php echo __("Chat"); ?></span></a>
                                                    <input type="hidden" id="roomEmbedCode" name="roomEmbedCode" value='<?php
                                                    $code = str_replace("{embedURL}", "{$chatRoomURL}?room_users_id={$room_users_id}&iframe=1&noFade=1", $advancedCustom->embedCodeTemplate);
                                                    echo ($code);
                                                    ?>'/>
                                                </li>
                                                <li class="hiddenOnChat">
                                                    <a href="#" onclick="copyChatToClipboard($('#roomEmbedCodeBubblesOnly').val());" ><span class="fa fa-copy"></span> <span id="btnChatEmbedText"><?php echo __("Bubbles Only"); ?></span></a>
                                                    <input type="hidden" id="roomEmbedCodeBubblesOnly" name="roomEmbedCodeBubblesOnly" value='<?php
                                                    $code = str_replace("{embedURL}", "{$chatRoomURL}?room_users_id={$room_users_id}&iframe=1&noFade=1&bubblesOnly=1", $advancedCustom->embedCodeTemplate);
                                                    echo ($code);
                                                    ?>'/>
                                                </li>
                                                <li class="divider"></li>
                                                <li class="dropdown-header"><i class="fas fa-link"></i>  <?php echo __('Links'); ?></li>
                                                <li class="hiddenOnChat">
                                                    <a href="#" onclick="copyChatToClipboard($('#roomEmbedCodeURLOnly').val());" ><span class="fa fa-copy"></span> <span id="btnChatEmbedText"><?php echo __("Chat"); ?></span></a>
                                                    <input type="hidden" id="roomEmbedCodeURLOnly" name="roomEmbedCode" value='<?php
                                                    echo ("{$chatRoomURL}?room_users_id={$room_users_id}&iframe=1&noFade=1");
                                                    ?>'/>
                                                </li>
                                                <li class="hiddenOnChat">
                                                    <a href="#" onclick="copyChatToClipboard($('#roomEmbedCodeBubblesOnlyURLOnly').val());" ><span class="fa fa-copy"></span> <span id="btnChatEmbedText"><?php echo __("Bubbles Only"); ?></span></a>
                                                    <input type="hidden" id="roomEmbedCodeBubblesOnlyURLOnly" name="roomEmbedCode" value='<?php
                                                    echo ("{$chatRoomURL}?room_users_id={$room_users_id}&iframe=1&noFade=1&bubblesOnly=1");
                                                    ?>'/>
                                                </li>

                                                <?php
                                            }
                                            if (User::isAdmin()) {
                                                ?>
                                                <li class="divider"></li>
                                                <li class="divider hiddenOnRoom"></li>
                                                <li class="dropdown-header hiddenOnRoom"><i class="fas fa-user-tie"></i>  <?php echo __('Site Admin'); ?></li>
                                                <li class="hiddenOnRoom"><a href="#" onclick="banUser(to_users_id);"><i class="fas fa-ban"></i>  <?php echo __('Ban From Site'); ?></a></li>
                                                <li class=""><a href="<?php echo $global['webSiteRootURL']; ?>plugin/User_Controll/page/editor.php" target="_top"><i class="fas fa-globe-americas"></i>  <?php echo __('Manage Bans'); ?></a></li>
                                                <?php
                                            }
                                            if (!empty($isStandAlone)) {
                                                if (User::isLogged()) {
                                                    ?>
                                                    <li class="divider"></li>
                                                    <li class="logoff">
                                                        <a href="<?php echo $global['webSiteRootURLStandAlone']; ?>standAlone/logoff.php?redirect=<?php echo urlencode(getSelfURI()) ?>">
                                                            <i class="fas fa-sign-out-alt"></i> <?php echo __('Logoff'); ?>
                                                        </a>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                                <li class="server" style="color: rgba(0,0,0,0.2); padding: 3px 20px; text-align: right; font-size: 0.5em;">
                                                    Stand Alone (<?php echo $global['serverName']; ?>) <i class="fas fa-circle-notch fa-spin"></i>
                                                </li>
                                                <?php
                                            }
                                            ?>

                                        </ul>
                                    </div>
                                    <?php
                                    if (empty($obj->disableAttachments)) {
                                        ?>
                                        <button class="btn btn-default" type="button" onclick="$('#uploadFile').modal('show');" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __('Attach'); ?>"
                                                style="" id="attachButton">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <?php
                                    }
                                    ?>
                                    <?php
                                    $donationLink = $channelOwner->getDonationLinkIfEnabled();
                                    if (!empty($donationLink)) {
                                        ?>
                                        <a class="btn btn-success" href="<?php echo $donationLink; ?>" target="_blank" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __('Donate'); ?>"
                                           style="" id="donateButton">
                                            <i class="fas fa-donate"></i>
                                        </a>    
                                        <?php
                                    }
                                    if (!empty($_GET['showCollapseButtons'])) {
                                        ?>
                                        <button id="chat2CollapseBtn" 
                                                style="
                                                border-top-right-radius: 4px;
                                                border-bottom-right-radius: 4px;" 
                                                class="btn btn-danger last" type="button" onclick="collapseChat2();" data-toggle="tooltip" data-placement="bottom"  title="<?php echo __('Close'); ?>"                                            >
                                            <i class="fa fa-times"></i>
                                        </button>
                                        <button id="chat2ExpandBtn" 
                                                style="
                                                border-radius: 4px;
                                                display: none;" class="btn btn-primary" type="button" onclick="expandChat2();" data-toggle="tooltip" data-placement="left"  title="<?php echo __('Open Chat'); ?>">
                                            <i class="far fa-comment-dots"></i>
                                        </button>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>

                        </div>
                        <div class="panel-body chat" id="divChatPanel" >
                            <div class="text-center">
                                <a href="#" class="btn" onclick="loadMore()">Load more</a>
                            </div>
                            <div id="chatScreen">

                            </div>
                            <button id="scrollDownBtn" type="button" class="btn btn-default btn-circle" onclick="_chatAutoscroll(true)" style="position: fixed; bottom: 60px; right: 20px;">
                                <i class="fas fa-angle-down"></i>
                            </button>
                        </div>
                        <div class="panel-footer" style="height: 55px;" >
                            <?php
                            if (!User::isLogged()) {
                                if (empty($_GET['mobileMode'])) {
                                    if (!empty($isStandAlone)) {
                                        include $global['systemRootPath'] . 'standAlone/login_form.php';
                                    } else {
                                        ?>
                                        <div class="row">
                                            <div class="col-xs-12" id="divChatInput">
                                                <a href="<?php echo $global['webSiteRootURL']; ?>/user?redirectUri=<?php echo urlencode("{$chatRoomURL}?room_users_id={$room_users_id}"); ?>" class="btn btn-block btn-default btn-xs"><?php echo __("Login"); ?></a>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                            } else {
                                ?>

                                <div class="row">
                                    <div class="col-lg-11 col-md-10 col-sm-10 col-xs-10" id="divChatInput">
                                        <input id="chatInput" maxlength="<?php echo $obj->charLimit; ?>" style="display: none;">
                                    </div>
                                    <div class=" col-lg-1 col-md-2 col-sm-2 col-xs-2" id="divSubmitChat">
                                        <button class="btn btn-block " id="submitChat">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>

                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <hgroup class="them-bubble bubble" id="them-bubble" style="display: none;">
                    <div class="messageNameId">Fulano da silva</div>
                    <?php
                    if ($canAdminChat) {
                        ?>
                        <a href="#" class="btn btn-sm btn-xs btn-default pull-right ban" title="<?php echo __('Ban From Chat'); ?>" onclick="banFromChat($(this).parent('hgroup').attr('users_id'));"><i class="fas fa-ban"></i></a>
                        <a href="#" class="btn btn-sm btn-xs btn-default pull-right removeBan" title="<?php echo __('Remove Ban From Chat'); ?>" onclick="removeBanFromChat($(this).parent('hgroup').attr('users_id'));"><i class="fas fa-check"></i></a>
                        <a href="#" class="btn btn-sm btn-xs btn-default pull-right removeMessage" title="<?php echo __('Delete Message'); ?>" onclick="removeMessage($(this).parent('hgroup').attr('message_id'));"><i class="fas fa-trash-alt"></i></a>
                        <?php
                    }
                    if (User::isAdmin()) {
                        ?>
                        <a href="#" class="btn btn-sm btn-xs btn-danger pull-right banFromSite"  onclick="banUser($(this).parent('hgroup').attr('users_id'));" title="<?php echo __('Ban From Site'); ?>"><i class="fas fa-globe-americas"></i>  </a>
                        <?php
                    }
                    ?>
                    <div class="message"></div>
                    <div class="messageFooter">1 hour ago</div>
                </hgroup>
                <hgroup class="me-bubble bubble" id="me-bubble" style="display: none;">
                    <div class="messageNameId">Fulano da silva</div>
                    <?php
                    if ($canAdminChat) {
                        ?>
                        <a href="#" class="btn btn-sm btn-xs btn-success pull-right removeMessage" title="<?php echo __('Delete Message'); ?>" onclick="removeMessage($(this).parent('hgroup').attr('message_id'));"><i class="fas fa-trash-alt"></i></a>
                        <?php
                    }
                    ?>
                    <div class="message"></div>
                    <div class="messageFooter">1 hour ago</div>
                </hgroup>
            </div>
        </div>
        <div id="divOpenChat" style="display: none;">
            <button type="button" class="btn btn-default btn-circle" onclick="maximize()" style="<?php echo $obj->openChatButtonStyle; ?>">
                <i class="far fa-comment-dots"></i>
            </button>
        </div>

        <div class="modal fade" id="pleaseWaitDialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        Processing...
                    </div>
                    <div class="modal-body">
                        <div class="progress progress-striped active">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if (empty($obj->disableAttachments)) {
            ?>
            <div id="uploadFile" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo __("Upload a File"); ?></h4>
                        </div>
                        <div class="modal-body">
                            <form id="upload" method="post" action="<?php echo $chatRoomURL; ?>uploadFile.php?<?php echo $credentials; ?>" enctype="multipart/form-data">
                                <div id="drop">
                                    <?php echo __("Drop Here"); ?>
                                    <a><?php echo __("Browse"); ?></a>
                                    <input type="file" name="upl" multiple  accept="image/*" />
                                </div>
                                <ul>
                                    <!-- The file uploads will be shown here -->
                                </ul>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
            <?php
        }
        ?>
        <textarea id="elementChatToCopy" style="
                  filter: alpha(opacity=0);
                  -moz-opacity: 0;
                  -khtml-opacity: 0;
                  opacity: 0;
                  position: absolute;
                  z-index: -9999;
                  top: 0;
                  left: 0;
                  pointer-events: none;"></textarea>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/script.js"></script>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/js-cookie/js.cookie.js"></script>
        <script src="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/emojionearea/emojionearea.min.js"></script>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo $global['webSiteRootURL']; ?>view/js/jquery-toast/jquery.toast.min.js" type="text/javascript"></script><!-- JavaScript Includes -->
        <?php
        if (empty($obj->disableAttachments)) {
            ?>
            <script src="<?php echo $global['webSiteRootURL']; ?>view/mini-upload-form/assets/js/jquery.knob.js"></script>

            <!-- jQuery File Upload Dependencies -->
            <script src="<?php echo $global['webSiteRootURL']; ?>view/mini-upload-form/assets/js/jquery.ui.widget.js"></script>
            <script src="<?php echo $global['webSiteRootURL']; ?>view/mini-upload-form/assets/js/jquery.iframe-transport.js"></script>
            <script src="<?php echo $global['webSiteRootURL']; ?>view/mini-upload-form/assets/js/jquery.fileupload.js"></script>

            <script>
                    $(function () {
                        var ul = $('#upload ul');
                        // Initialize the jQuery File Upload plugin
                        $('#upload').fileupload({
                            dataType: 'json',
                            formData: [{name: 'room_users_id', value: room_users_id}, {name: 'to_users_id', value: to_users_id}],
                            // This element will accept file drag/drop uploading
                            dropZone: $('#drop'),
                            // This function is called when a file is added to the queue;
                            // either via the browse button, or via drag/drop:
                            add: function (e, data) {
                                $('#upload').fileupload('option', 'formData')[0] = {name: 'room_users_id', value: room_users_id};
                                $('#upload').fileupload('option', 'formData')[1] = {name: 'to_users_id', value: to_users_id};
                                var tpl = $('<li class="working"><input type="text" value="0" data-width="48" data-height="48"' +
                                        ' data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043" /><p style="color:#AAA;" class="action">Uploading...</p><p class="filename"></p><span></span></li>');

                                // Append the file name and file size
                                tpl.find('p.filename').text(data.files[0].name)
                                        .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

                                // Add the HTML to the UL element
                                data.context = tpl.appendTo(ul);

                                // Initialize the knob plugin
                                tpl.find('input').knob();

                                // Listen for clicks on the cancel icon
                                tpl.find('span').click(function () {

                                    if (tpl.hasClass('working')) {
                                        jqXHR.abort();
                                    }

                                    tpl.fadeOut(function () {
                                        tpl.remove();
                                    });

                                });

                                // Automatically upload the file once it is added to the queue
                                var jqXHR = data.submit();
                            },
                            progress: function (e, data) {

                                // Calculate the completion percentage of the upload
                                var progress = parseInt(data.loaded / data.total * 100, 10);

                                // Update the hidden input field and trigger a change
                                // so that the jQuery knob plugin knows to update the dial
                                data.context.find('input').val(progress).change();

                                if (progress == 100) {
                                    data.context.removeClass('working');
                                }
                            },
                            fail: function (e, data) {
                                // Something has gone wrong!
                                data.context.addClass('error');
                            },
                            done: function (e, data) {
                                console.log(data.result);
                                if (data.result.error) {
                                    $.toast({
                                        heading: 'Error',
                                        text: data.result.errorMsg,
                                        icon: 'error'
                                    });
                                    data.context.addClass('error');
                                    data.context.find('p.action').text("Error");
                                } else {
                                    if (data.result.room_users_id) {
                                        getRoom(data.result.room_users_id, 0, false);
                                    } else {
                                        $("#chatItem" + data.result.to_users_id).addClass('active');
                                        getChat(data.result.to_users_id, 0, false);
                                    }
                                    $('#uploadFile').modal('hide');
                                    data.context.find('p.action').html("Upload done");
                                    data.context.addClass('working');
                                }
                            }

                        });


                    });
            </script>

            <?php
        }
        ?>
        <script src="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/script.js?<?php echo @filemtime("{$chatRoomPath}script.js"); ?>" type="text/javascript"></script>

        <script>
                    <?php
                    if (empty($obj->useStaticLayout)) {
                        ?>
                    function collapseChat2() {
                        window.parent.closeChat2();
                        Cookies.set('yptChat2Minimized', true, {
                            path: '/',
                            expires: 365
                        });
                    }
                    function expandChat2() {
                        window.parent.maximize();
                        Cookies.set('yptChat2Minimized', false, {
                            path: '/',
                            expires: 365
                        });
                    }

                        <?php
                    } else {
                        ?>
                    function collapseChat2() {
                        $('#divRooms, .panel-footer').hide();
                        $('#divChatPanel').slideUp();
                        $('#divChat').addClass('minimized');
                        $('#chatPanel .btn').hide();
                        $('#chat2CollapseBtn').hide();
                        $('#chat2ExpandBtn').show();
                        window.parent.collapseChat2();
                        Cookies.set('yptChat2Minimized', true, {
                            path: '/',
                            expires: 365
                        });
                    }
                    function expandChat2() {
                        $('#divRooms, .panel-footer').fadeIn();
                        $('#divChatPanel').show();
                        $('#divChat').removeClass('minimized');
                        $('#chatPanel .btn').show();
                        $('#chat2CollapseBtn').show();
                        $('#chat2ExpandBtn').hide();
                        window.parent.expandChat2();
                        Cookies.set('yptChat2Minimized', false, {
                            path: '/',
                            expires: 365
                        });
                    }

                    <?php
                }
                ?>
                $(function () {                    
                    if (typeof window.parent.isYptChat2Minimized === 'function' && window.parent.isYptChat2Minimized()) {
                        collapseChat2();
                    }
                });

                function insertImageURI(value) {
                    // parse the uri to strip out "base64"
                    var sourceSplit = value.split("base64,");
                    var sourceString = sourceSplit[1];
                    // Write base64-encoded string into input field
                    $("#sourceString").val(sourceString);
                    $("#sourceString").parent('form').submit();
                }
        </script>
        <?php
        if ($isStandAlone) {
            ?>
            <script>
                function banUser(users_id) {
                    $.toast({
                        heading: 'Warning',
                        text: 'Not available for standalone',
                        icon: 'warning'
                    });
                }
            </script>
            <?php
        } else if (AVideoPlugin::isEnabledByName("User_Controll")) {
            include $global['systemRootPath'] . 'plugin/User_Controll/footer.php';
        } else {
            ?>
            <script>
                function banUser(users_id) {
                    $.toast({
                        heading: 'Warning',
                        text: 'You Need to Purchase/Enable the User_Controll Plugin',
                        icon: 'warning'
                    });
                }
            </script>
            <?php
        }
        ?>
</html>