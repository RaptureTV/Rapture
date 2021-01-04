<?php

if(empty($isStandAlone)){
    require_once dirname(__FILE__) . '/../../../videos/configuration.php';
    require_once dirname(__FILE__) . '/../../../objects/user.php';
}
class ChatMessageLog extends ObjectYPT {

    protected $id, $chat_messages_id, $users_id, $status;
    
    static $STATUS_SENT = 's';
    static $STATUS_DELIVERED = 'd';
    static $STATUS_ERROR = 'e';
    

    static function getSearchFieldsNames() {
        return array();
    }

    static function getTableName() {
        return 'chat_message_log';
    }

    function getChat_messages_id() {
        return $this->chat_messages_id;
    }

    function getUsers_id() {
        return $this->users_id;
    }

    function getStatus() {
        return $this->status;
    }

    function setChat_messages_id($chat_messages_id) {
        $this->chat_messages_id = $chat_messages_id;
    }

    function setUsers_id($users_id) {
        $this->users_id = $users_id;
    }

    function setStatus($status) {
        $this->status = $status;
    }
    
    static function getFromChatMessagesId($chat_messages_id, $users_id) {
        global $global;
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE chat_messages_id = ? AND users_id = ? LIMIT 1";
        
        $res = sqlDAL::readSql($sql, "ii", array($chat_messages_id,$users_id));
        $data = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            $row = $data;
        } else {
            $row = false;
        }
        return $row;
    }



}
