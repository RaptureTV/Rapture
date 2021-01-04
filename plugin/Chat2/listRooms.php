<?php
global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../../videos/configuration.php';
}

$obj = AVideoPlugin::getObjectDataIfEnabled('Chat2');

if (empty($obj)) {
    echo "Chat Not enabled";
    exit;
}

if ($obj->showChatOnlyForLoggedUsers && !User::isLogged()) {
    echo "User not logged";
    exit;
}

require_once $global['systemRootPath'] . 'plugin/Chat2/functions.php';
// chat owner
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title>Chat</title>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
        <style>
            .twPc-div {
                background: #fff none repeat scroll 0 0;
                border: 1px solid #e1e8ed;
                border-radius: 6px;
                height: 200px;
                margin-bottom: 10px;
            }
            .twPc-bg {
                background-size: cover;
                border-bottom: 1px solid #e1e8ed;
                border-radius: 4px 4px 0 0;
                height: 95px;
                width: 100%;
            }
            .twPc-block {
                display: block !important;
            }
            .twPc-button {
                margin: -35px -10px 0;
                text-align: right;
                width: 100%;
            }
            .twPc-avatarLink {
                background-color: #fff;
                border-radius: 6px;
                display: inline-block !important;
                float: left;
                margin: -30px 5px 0 8px;
                max-width: 100%;
                padding: 1px;
                vertical-align: bottom;
            }
            .twPc-avatarImg {
                border: 2px solid #fff;
                border-radius: 7px;
                box-sizing: border-box;
                color: #fff;
                height: 72px;
                width: 72px;
            }
            .twPc-divUser {
                margin: 12px 0 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .twPc-divName {
                font-size: 18px;
                font-weight: 700;
                line-height: 21px;
            }
            .twPc-divName a {
                color: black;
            }
            .twPc-divStats {
                margin-left: 11px;
                padding: 10px 0;
            }
            .twPc-Arrange {
                box-sizing: border-box;
                display: table;
                margin: 0;
                min-width: 100%;
                padding: 0;
                table-layout: auto;
            }
            ul.twPc-Arrange {
                list-style: outside none none;
                margin: 0;
                padding: 0;
            }
            .twPc-ArrangeSizeFit {
                display: table-cell;
                padding: 0;
                vertical-align: top;
            }
            .twPc-ArrangeSizeFit a:hover {
                text-decoration: none;
            }
            .twPc-StatValue {
                display: block;
                font-size: 18px;
                font-weight: 500;
                transition: color 0.15s ease-in-out 0s;
            }
            .twPc-StatLabel {
                color: #8899a6;
                font-size: 10px;
                letter-spacing: 0.02em;
                overflow: hidden;
                text-transform: uppercase;
                transition: color 0.15s ease-in-out 0s;
            }
        </style>
    </head>
    <body class="<?php echo $global['bodyClass']; ?>">
        <?php
        include $global['systemRootPath'] . 'view/include/navbar.php';
        ?>
        <div class="container">
            <h1>Channels</h1>
            <div class="row">
                <?php
                $rooms = ChatMessage::getLatestActiveRoom();
                foreach ($rooms as $value) {
                    if (empty($value['users_id'])) {
                        continue;
                    }
                    $user = new User($value['users_id']);
                    if (empty($user->getId())) {
                        continue;
                    }
                    if ($user->getStatus() == 'i') {
                        continue;
                    }
                    ?>
                    <div class=" col-lg-4 col-md-4 col-sm-6 col-xs-12">

                        <div class="twPc-div">
                            <a class="twPc-bg twPc-block" style="background-image: url(<?php echo $user->getBackground($value['users_id']); ?>)"></a>

                            <div>
                                <div class="twPc-button">
                                    <!-- Twitter Button | you can get from: https://about.twitter.com/tr/resources/buttons#follow -->
                                    <?php
                                    echo Subscribe::getButton($value['users_id']);
                                    ?>
                                </div>

                                <a title="<?php echo $user->getNameIdentificationBd(); ?>" href="<?php echo $user->getChannelLink($value['users_id']); ?>" class="twPc-avatarLink">
                                    <img alt="<?php echo $user->getNameIdentificationBd(); ?>" src="<?php echo $user->getPhotoDB(); ?>" class="twPc-avatarImg">
                                </a>

                                <div class="twPc-divUser">
                                    <div class="twPc-divName">
                                        <a href="<?php echo $user->getChannelLink($value['users_id']); ?>">
                                            <?php echo $user->getNameIdentificationBd(); ?>
                                        </a>
                                    </div>
                                    <span>
                                        <a href="<?php echo $user->getChannelLink($value['users_id']); ?>">
                                            Channel: <?php echo $user->getChannelName(); ?>
                                        </a>
                                    </span>
                                </div>

                                <div class="twPc-divStats">
                                    <ul class="twPc-Arrange">
                                        <li class="twPc-ArrangeSizeFit">
                                            <a href="<?php echo Chat2::getChatRoomFromUsersID($value['users_id'], false); ?>" title="Chat">
                                                <span class="twPc-StatLabel twPc-block">Chat</span>
                                                <span class="twPc-StatValue"><?php echo number_format(ChatMessage::getTotalFromUsers(0, $value['users_id']),0); ?></span>
                                            </a>
                                        </li>
                                        <li class="twPc-ArrangeSizeFit">
                                            <a href="<?php echo $user->getChannelLink($value['users_id']); ?>" title="885 Following">
                                                <span class="twPc-StatLabel twPc-block">Videos</span>
                                                <span class="twPc-StatValue">
                                                    <?php
                                                    echo number_format(Video::getTotalVideos("", $value['users_id'], true), 0);
                                                    ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="twPc-ArrangeSizeFit">
                                            <a href="<?php echo Chat2::getChatRoomFromUsersID($value['users_id'], false); ?>" title="Status">
                                                <span class="twPc-StatLabel twPc-block">Status</span>
                                                <?php
                                                if (Chat2::isUserOnline($value['users_id'])) {
                                                    ?>
                                                    <span class="label label-success">Online</span>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <span class="label label-danger">Offline</span>
                                                    <?php
                                                }
                                                ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>    
                    <?php
                }
                ?>
            </div>

            <h1>Users</h1>
            <div class="row">
                <?php
                $rooms = ChatOnlineStatus::getLatestActiveUsers();
                foreach ($rooms as $value) {
                    $user = new User($value['users_id']);
                    if ($user->getStatus() == 'i') {
                        continue;
                    }
                    ?>
                    <div class=" col-lg-3 col-md-3 col-sm-4 col-xs-6">

                        <div class="twPc-div">
                            <a class="twPc-bg twPc-block" style="background-image: url(<?php echo $user->getBackground($value['users_id']); ?>)"></a>

                            <div>
                                <div class="twPc-button">
                                    <!-- Twitter Button | you can get from: https://about.twitter.com/tr/resources/buttons#follow -->
                                    <?php
                                    echo Subscribe::getButton($value['users_id']);
                                    ?>
                                </div>

                                <a title="<?php echo $user->getNameIdentificationBd(); ?>" href="<?php echo $user->getChannelLink($value['users_id']); ?>" class="twPc-avatarLink">
                                    <img alt="<?php echo $user->getNameIdentificationBd(); ?>" src="<?php echo $user->getPhotoDB(); ?>" class="twPc-avatarImg">
                                </a>

                                <div class="twPc-divUser">
                                    <div class="twPc-divName">
                                        <a href="<?php echo $user->getChannelLink($value['users_id']); ?>">
                                            <?php echo $user->getNameIdentificationBd(); ?>
                                        </a>
                                    </div>
                                    <span>
                                        <a href="<?php echo $user->getChannelLink($value['users_id']); ?>">
                                            Channel: <?php echo $user->getChannelName(); ?>
                                        </a>
                                    </span>
                                </div>

                                <div class="twPc-divStats">
                                    <ul class="twPc-Arrange">
                                        <li class="twPc-ArrangeSizeFit">
                                            <a href="<?php echo Chat2::getChatRoomFromUsersID($value['users_id'], false); ?>" title="Chat">
                                                <span class="twPc-StatLabel twPc-block">Chat</span>
                                                <span class="twPc-StatValue"><?php echo number_format(ChatMessage::getTotalFromUsers($value['users_id'], 0),0); ?></span>
                                            </a>
                                        </li>
                                        <li class="twPc-ArrangeSizeFit">
                                            <a href="<?php echo $user->getChannelLink($value['users_id']); ?>" title="885 Following">
                                                <span class="twPc-StatLabel twPc-block">Videos</span>
                                                <span class="twPc-StatValue">
                                                    <?php
                                                    echo number_format(Video::getTotalVideos("", $value['users_id'], true), 0);
                                                    ?>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="twPc-ArrangeSizeFit">
                                            <a href="<?php echo Chat2::getChatRoomFromUsersID($value['users_id'], false); ?>" title="Status">
                                                <span class="twPc-StatLabel twPc-block">Status</span>
                                                <?php
                                                if (Chat2::isUserOnline($value['users_id'])) {
                                                    ?>
                                                    <span class="label label-success">Online</span>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <span class="label label-danger">Offline</span>
                                                    <?php
                                                }
                                                ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> 
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        ?>
    </body>
</html>