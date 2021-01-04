<?php

class Subscribe{
    static function getAllSubscribes($users_id){
        global $global, $APISecret;
        $json = getAPI("subscribers", "users_id={$users_id}");
        return object_to_array($json);        
    }
}