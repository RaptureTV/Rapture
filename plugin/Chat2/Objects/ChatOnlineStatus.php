<?php

if(empty($isStandAlone)){
    require_once dirname(__FILE__) . '/../../../videos/configuration.php';
    require_once dirname(__FILE__) . '/../../../objects/user.php';
}
class ChatOnlineStatus extends ObjectYPT {

    protected $id, $status, $users_id, $modified, $now;

    static function getSearchFieldsNames() {
        return array('status');
    }

    static function getTableName() {
        return 'chat_online_status';
    }
    
    
    function loadFromUsersID($users_id) {
        $row = self::getFromDbFromUsersID($users_id);
        if (empty($row))
            return false;
        foreach ($row as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    static protected function getFromDbFromUsersID($users_id) {
        global $global;
        $sql = "SELECT *, now() as `now` FROM " . static::getTableName() . " WHERE  users_id = ? LIMIT 1";
        // I had to add this because the about from customize plugin was not loading on the about page http://127.0.0.1/AVideo/about
        $res = sqlDAL::readSql($sql, "i", array($users_id));
        $data = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            $row = $data;
        } else {
            $row = false;
        }
        return $row;
    }
    
    function getNow() {
        return $this->now;
    }
        
    function getStatus() {
        return $this->status;
    }

    function getUsers_id() {
        return $this->users_id;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setUsers_id($users_id) {
        $this->users_id = $users_id;
    }
    
    function getModified() {
        return $this->modified;
    }

    function setModified($modified) {
        $this->modified = $modified;
    }
        
    static function updateStatus($users_id, $status='o'){
        if(empty($users_id)){
            return false;
        }
        $s = new ChatOnlineStatus(0);
        $s->loadFromUsersID($users_id);
        $s->setUsers_id($users_id);
        $s->setStatus($status);
        return $s->save();
    }
    
    static function getLatestActiveUsers($limit=40){
        global $global;
        $limit = intval($limit);
        $sql = "SELECT distinct(users_id) as users_id FROM "
                . " (SELECT users_id FROM " . static::getTableName() . " ORDER BY modified DESC) tmp "
                . " LIMIT {$limit} ";
        
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
        return $rows;
    }



}
