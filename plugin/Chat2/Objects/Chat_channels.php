<?php

require_once dirname(__FILE__) . '/../../../videos/configuration.php';

class Chat_channels extends ObjectYPT {

    protected $id,$name,$url,$users_id,$status;
    
    static function getSearchFieldsNames() {
        return array('name','url');
    }

    static function getTableName() {
        return 'chat_channels';
    }
    
    static function getAllUsers() {
        global $global;
        $table = "users";
        $sql = "SELECT * FROM {$table} WHERE status = 'a' AND (isAdmin=1 OR canStream = 1 OR canUpload = 1) ";
        
        $res = sqlDAL::readSql($sql);
        $fullData = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $rows = array();
        if ($res != false) {
            foreach ($fullData as $row) {
                $rows[] = $row;
            }
        } else {
            _error_log($sql . ' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }
    
     
    function setId($id) {
        $this->id = intval($id);
    } 
 
    function setName($name) {
        $this->name = $name;
    } 
 
    function setUrl($url) {
        $this->url = $url;
    } 
 
    function setUsers_id($users_id) {
        $this->users_id = intval($users_id);
    } 
 
    function setStatus($status) {
        $this->status = $status;
    } 
    
     
    function getId() {
        return intval($this->id);
    }  
 
    function getName() {
        return $this->name;
    }  
 
    function getUrl() {
        return $this->url;
    }  
 
    function getUsers_id() {
        return intval($this->users_id);
    }  
 
    function getStatus() {
        return $this->status;
    }  

    
    static function getAll() {
        global $global;
        if (!static::isTableInstalled()) {
            return false;
        }
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1=1 ";

        $sql .= self::getSqlFromPost();
        $res = sqlDAL::readSql($sql);
        $fullData = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        $rows = array();
        if ($res != false) {
            foreach ($fullData as $row) {
                if(empty($row['users_id'])){
                    $row['channelName'] = __("All Channels");
                    $row['user'] = false;
                }else{
                    $row['user'] = User::getUserFromID($row['users_id']);
                    $row['channelName'] = $row['user']['channelName'];
                }
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }
    
    static function getFromUsersId($users_id) {
        global $global;
        $users_id = intval($users_id);
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE  users_id = ? LIMIT 1";
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
        
}
