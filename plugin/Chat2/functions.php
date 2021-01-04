<?php

function listItem($usersId, $channel = false, $banned = false) {
    $us = new User($usersId);
    $channelLink = $us->getChannelLink();
    if ($channel) {
        $id = "channelItem";
        $class = "list-group-item-info";
        $NameIdentification = $us->getChannelName();
        $to_users_id = 0;
    } else {
        $id = "chatItem$usersId";
        $class = "";
        $NameIdentification = $us->getNameIdentificationBd();
        $to_users_id = $usersId;
    }
    if($banned){
        $class .= " list-group-item-danger";
    }
    ?>
    <li  class="list-group-item <?php echo $class; ?>" to_users_id="<?php echo $to_users_id; ?>" id="<?php echo $id; ?>" channelLink="<?php echo $channelLink; ?>">
        <img src="<?php echo User::getPhoto($usersId); ?>" class="img img-circle img-responsive pull-left">
        <span class="NameIdentification hidden-xs"><?php echo $NameIdentification; ?></span>
        <span class="badge" style="display: none;">0</span>
    </li> 
    <?php
}
