<?php
if(empty($isStandAlone)){
    require_once dirname(__FILE__) . '/../../../videos/configuration.php';
    require_once dirname(__FILE__) . '/../../../objects/user.php';
}
class ChatBan extends ObjectYPT {

    protected $id,$users_id,$banned_users_id;

    static function getSearchFieldsNames() {
        return array();
    }

    static function getTableName() {
        return 'chat_ban';
    }
    
    function getUsers_id() {
        return $this->users_id;
    }

    function getBanned_users_id() {
        return $this->banned_users_id;
    }

    function setUsers_id($users_id) {
        $this->users_id = $users_id;
    }

    function setBanned_users_id($banned_users_id) {
        $this->banned_users_id = $banned_users_id;
    }
    
    static function removeBan($users_id, $banned_users_id) {
        global $global;
        $users_id = intval($users_id);
        $banned_users_id = intval($banned_users_id);
        $sql = "DELETE FROM " . static::getTableName() . " ";
        $sql .= " WHERE users_id = ? AND banned_users_id = ?";
        $global['lastQuery'] = $sql;
        $name = "isUserBanned_{$users_id}_{$banned_users_id}";
        $result = ObjectYPT::deleteAllSessionCache($name);
        //_error_log("Delete Query: ".$sql);
        return sqlDAL::writeSql($sql, "ii", array($users_id, $banned_users_id));
    }
    
    static function getBans($users_id) {
        $users_id = intval($users_id);
        $sql = "SELECT banned_users_id FROM  " . static::getTableName() . " WHERE 1 = 1 ";
        $sql .= " AND (users_id = '$users_id') ";
        
        $res = sqlDAL::readSql($sql);
        $fullData = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $rows = array();
        if ($res != false) {
            foreach ($fullData as $row) {
                $rows[] = $row['banned_users_id'];
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }
    
    static function isUserBanned($users_id, $banned_users_id){
        global $global;
        $name = "isUserBanned_{$users_id}_{$banned_users_id}";
        $result = ObjectYPT::getSessionCache($name, 60);// 1 minute
        if(empty($result) || !isset($result[1])){
            $sql = "SELECT * FROM " . static::getTableName() . " WHERE  users_id = ? AND banned_users_id = ? LIMIT 1";        
            $res = sqlDAL::readSql($sql, "ii", array($users_id, $banned_users_id));
            $data = sqlDAL::fetchAssoc($res);
            sqlDAL::close($res);
            if ($res) {
                $row = $data;
            } else {
                $row = false;
            }
            $result = array(time(), $row);
            ObjectYPT::setSessionCache($name, $result);
        }
        return $result[1];
    }

}
