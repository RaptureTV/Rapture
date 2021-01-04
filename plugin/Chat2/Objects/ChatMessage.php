<?php

if (empty($isStandAlone)) {
    require_once dirname(__FILE__) . '/../../../videos/configuration.php';
    require_once dirname(__FILE__) . '/../../../objects/user.php';
    require_once $global['systemRootPath'] . 'plugin/Chat2/Objects/ChatMessageLog.php';
}

class ChatMessage extends ObjectYPT {

    protected $id, $message, $reply_chats_id, $from_users_id, $to_users_id, $room_users_id, $file;

    static function getSearchFieldsNames() {
        return array('message');
    }

    static function getTableName() {
        return 'chat_messages';
    }

    function getMessage() {
        return $this->message;
    }

    function getReply_chats_id() {
        return $this->reply_chats_id;
    }

    function getFrom_users_id() {
        return $this->from_users_id;
    }

    function getTo_users_id() {
        return $this->to_users_id;
    }

    function getRoom_users_id() {
        return $this->room_users_id;
    }

    function setMessage($message, $allowHTML = false) {
        if ($allowHTML) {
            $this->message = $message;
        } else {
            $this->message = xss_esc($message);
            $obj = AVideoPlugin::getObjectData('Chat2');
            $this->message = substr($this->message, 0, $obj->charLimit);
        }
    }

    function setReply_chats_id($reply_chats_id) {
        $this->reply_chats_id = $reply_chats_id;
    }

    function setFrom_users_id($from_users_id) {
        $this->from_users_id = $from_users_id;
    }

    function setTo_users_id($to_users_id) {
        if (empty($to_users_id)) {
            $to_users_id = "NULL";
        }
        $this->to_users_id = $to_users_id;
    }

    function setRoom_users_id($room_users_id) {
        if (empty($room_users_id)) {
            $room_users_id = "NULL";
        }
        $this->room_users_id = $room_users_id;
    }

    static function getAll($room_users_id = 0, $greater_then_id = 0, $lower_then_id = 0, $markAsDelivered = false) {
        global $global, $isStandAlone;
        if (!static::isTableInstalled()) {
            return false;
        }
        $room_users_id = intval($room_users_id);
        $greater_then_id = intval($greater_then_id);
        $lower_then_id = intval($lower_then_id);
        $me = 0;
        if (User::isLogged()) {
            $me = User::getId();
        }
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1=1 ";

        if (!empty($greater_then_id)) {
            $sql .= " AND id > '$greater_then_id' ";
        }
        if (!empty($lower_then_id)) {
            $sql .= " AND id < '$lower_then_id' ";
        }
        if (!empty($room_users_id)) {
            $sql .= " AND room_users_id = '$room_users_id' ";
        }

        $sql .= self::getSqlFromPost();
        //echo $sql;exit;
        $res = sqlDAL::readSql($sql);
        $fullData = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $rows = array();
        if ($res != false) {
            foreach ($fullData as $row) {
                $row['message'] = preg_replace('/[\x00-\x1F\x7F]/u', '', $row['message']);
                if (!empty($row['file'])) {
                    if (empty($global['webSiteRootURLStandAlone'])) {
                        $webSiteRootURL = $global['webSiteRootURL'];
                    } else {
                        $webSiteRootURL = $global['webSiteRootURLStandAlone'];
                    }
                    $row['message'] = "<a href='{$global['webSiteRootURL']}{$row['file']}' target='_blank'><img src='{$webSiteRootURL}{$row['file']}' class='img img-responsive img-rounded' style='margin:0;'></a>";
                }else{
                    $row['message'] = textToLink($row['message'], true);
                }
                $row['photo'] = User::getPhoto($row['from_users_id']);
                $row['name'] = User::getNameIdentificationById($row['from_users_id']);
                $row['humanTiming'] = humanTiming($row['created']);
                $row['isMe'] = $me == $row['from_users_id'];
                $row['isBanned'] = self::isUserBanned($room_users_id, $row['from_users_id']);
                if (!$row['isMe'] && $markAsDelivered) {
                    self::getMessageLog($row['id'], $me);
                }
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }

    static function isUserBanned($users_id, $banned_users_id) {
        $u = new User($banned_users_id);

        if ($u->getStatus() === "i") {
            return true;
        }

        return ChatBan::isUserBanned($users_id, $banned_users_id);
    }

    static function getAllFromUsers($from_users_id, $to_users_id, $greater_then_id = 0, $lower_then_id = 0, $markAsDelivered = false) {
        global $global;
        $from_users_id = intval($from_users_id);
        $to_users_id = intval($to_users_id);
        $greater_then_id = intval($greater_then_id);
        $lower_then_id = intval($lower_then_id);
        $me = 0;
        if (User::isLogged()) {
            $me = User::getId();
        }
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1 = 1 ";

        if (!empty($greater_then_id)) {
            $sql .= " AND id > '$greater_then_id' ";
        }
        if (!empty($lower_then_id)) {
            $sql .= " AND id < '$lower_then_id' ";
        }
        $sql .= " AND ((to_users_id = '$to_users_id' AND from_users_id= " . $from_users_id . ") OR (to_users_id = '$from_users_id' AND from_users_id= " . $to_users_id . ")) ";

        $sql .= self::getSqlFromPost();
        //echo $sql;
        $res = sqlDAL::readSql($sql);
        $fullData = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $rows = array();
        if ($res != false) {
            foreach ($fullData as $row) {
                if (!empty($row['file'])) {
                    if (empty($global['webSiteRootURLStandAlone'])) {
                        $webSiteRootURL = $global['webSiteRootURL'];
                    } else {
                        $webSiteRootURL = $global['webSiteRootURLStandAlone'];
                    }
                    $row['message'] = "<a href='{$global['webSiteRootURL']}{$row['file']}' target='_blank'><img src='{$webSiteRootURL}{$row['file']}' class='img img-responsive img-rounded' style='margin:0;'></a>";
                }else{
                    $row['message'] = textToLink($row['message'], true);
                }
                $row['photo'] = User::getPhoto($row['from_users_id']);
                $row['name'] = User::getNameIdentificationById($row['from_users_id']);
                $row['humanTiming'] = humanTiming($row['created']);
                $row['isMe'] = $me == $row['from_users_id'];
                $row['isBanned'] = self::isUserBanned($to_users_id, $row['from_users_id']);
                if (!$row['isMe'] && $markAsDelivered) {
                    self::getMessageLog($row['id'], $from_users_id);
                }
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }

    static function getTotalFromUsers($to_users_id, $room_users_id) {
        global $global;
        $room_users_id = intval($room_users_id);
        $to_users_id = intval($to_users_id);

        $sql = "SELECT count(id) as total FROM  " . static::getTableName() . " WHERE 1 = 1 ";
        if (!empty($to_users_id)) {
            $sql .= " AND (to_users_id = '$to_users_id') ";
        }
        if (!empty($room_users_id)) {
            $sql .= " AND (room_users_id = '$room_users_id') ";
        }
        $res = sqlDAL::readSql($sql);
        $data = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            $row = intval($data['total']);
        } else {
            $row = 0;
        }
        return $row;
    }

    static function getMessageLog($chat_messages_id, $users_id) {
        $log = ChatMessageLog::getFromChatMessagesId($chat_messages_id, $users_id);

        if (!empty($log)) {
            return $log['status'];
        }

        $l = new ChatMessageLog(0);
        $l->setChat_messages_id($chat_messages_id);
        $l->setUsers_id($users_id);
        $l->setStatus(ChatMessageLog::$STATUS_DELIVERED);
        $l->save();


        return false;
    }

    static function newMessages($from_users_id, $to_users_id) {

        $from_users_id = intval($from_users_id);
        $to_users_id = intval($to_users_id);
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1 = 1 ";

        $sql .= " AND (to_users_id = '$to_users_id' AND from_users_id= " . $from_users_id . ") ";

        $sql .= self::getSqlFromPost();
        //echo $sql;
        $res = sqlDAL::readSql($sql);
        $fullData = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $rows = array();
        if ($res != false) {
            foreach ($fullData as $row) {
                $log = ChatMessageLog::getFromChatMessagesId($row['id'], $to_users_id);
                //var_dump($log);
                if (empty($log) || $log['status'] !== ChatMessageLog::$STATUS_DELIVERED) {
                    $rows[] = $row;
                }
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }

    static function newMessagesFromRoom($room_users_id, $from_users_id) {
        $room_users_id = intval($room_users_id);
        $cacheName = "newMessagesFromRoom_{$room_users_id}_{$from_users_id}";
        $rows = ObjectYPT::getCache($cacheName, 30); // 30 seconds
        if (empty($rows)) {
            $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1 = 1 ";

            $sql .= " AND (room_users_id = '$room_users_id' and from_users_id != '$from_users_id') ";

            $sql .= self::getSqlFromPost();
            //echo $sql;
            $res = sqlDAL::readSql($sql);
            $fullData = sqlDAL::fetchAllAssoc($res);
            sqlDAL::close($res);
            $rows = array();
            if ($res != false) {
                foreach ($fullData as $row) {
                    $log = ChatMessageLog::getFromChatMessagesId($row['id'], User::getId());
                    if (empty($log) || $log['status'] !== ChatMessageLog::$STATUS_DELIVERED) {
                        $rows[] = $row;
                    }
                }
            } else {
                die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
            }
            ObjectYPT::setCache($cacheName, $rows);
        }
        return $rows;
    }

    static function deleteAllFromRoom($room_users_id) {
        global $global;
        if (!empty($room_users_id)) {
            $sql = "DELETE FROM " . static::getTableName() . " ";
            $sql .= " WHERE room_users_id = ?";
            $global['lastQuery'] = $sql;
            $dir = "{$global['systemRootPath']}videos/Chat2/images/Room/us_{$room_users_id}/";
            rrmdir($dir);
            //_error_log("Delete Query: ".$sql);
            return sqlDAL::writeSql($sql, "i", array($room_users_id));
        }
        _error_log("room_users_id for table " . static::getTableName() . " not defined for deletion");
        return false;
    }

    static function deleteMessage($chat_messages_id) {
        global $global;
        if (!empty($chat_messages_id)) {
            $sql = "DELETE FROM " . static::getTableName() . " ";
            $sql .= " WHERE id = ?";
            $global['lastQuery'] = $sql;
            self::deleteFileFromMessage($chat_messages_id);
            return sqlDAL::writeSql($sql, "i", array($chat_messages_id));
        }
        _error_log("chat_messages_id for table " . static::getTableName() . " not defined for deletion");
        return false;
    }

    static function deleteFileFromMessage($chat_messages_id) {
        $obj = self::getFilePathFromMessage($chat_messages_id);
        if(file_exists($obj->absolutePath)){
            return unlink($obj->absolutePath);
        }
        return false;
    }

    static function getFilePathFromMessage($chat_messages_id) {
        global $global;
        $message = new ChatMessage($chat_messages_id);
        $from_users_id = $message->getFrom_users_id();
        $obj = new stdClass();
        $obj->filename = "file_{$chat_messages_id}.jpg";
        $obj->relativePath = "videos/Chat2/images/user_{$from_users_id}/{$obj->filename}";
        $obj->absolutePath = "{$global['systemRootPath']}{$obj->relativePath}";
        return $obj;
    }

    static function getLatestActiveRoom($limit = 40) {
        global $global;
        $limit = intval($limit);
        $cacheName = "getLatestActiveRoom_{$limit}";
        $rows = ObjectYPT::getCache($cacheName, 30); // 30 seconds
        if (empty($rows)) {
            $sql = "SELECT distinct(room_users_id) as users_id FROM "
                    . " (SELECT room_users_id FROM " . static::getTableName() . " ORDER BY id DESC) tmp"
                    . "  LIMIT {$limit} ";

            $res = sqlDAL::readSql($sql);
            $fullData = sqlDAL::fetchAllAssoc($res);
            sqlDAL::close($res);
            $rows = array();
            if ($res != false) {
                foreach ($fullData as $row) {
                    $rows[] = $row;
                }
            } else {
                die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
            }
            ObjectYPT::setCache($cacheName, $rows);
        } else {
            $rows = object_to_array($rows);
        }
        return $rows;
    }

    function getFile() {
        return $this->file;
    }

    function setFile($file) {
        $this->file = $file;
    }

    public function save() {
        global $global;
        if (empty($this->reply_chats_id)) {
            $this->reply_chats_id = 'NULL';
        }
        $this->message = $global['mysqli']->real_escape_string($this->message);
        return parent::save();
    }

}
