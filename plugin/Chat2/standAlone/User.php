<?php

class User {

    private $id, $user, $email, $name, $identification, $photoURL, $channelName, $status, $donationLink;

    function __construct($id, $user = "", $password = "") {
        if (empty($id)) {
            // get the user data from user and pass
            $this->user = $user;
            if ($password !== false) {
                $this->password = $password;
            } else {
                $this->loadFromUser($user);
            }
        } else {
            // get data from id
            $this->load($id);
        }
    }

    function getChannelName() {
        return $this->channelName;
    }

    private function load($id) {
        $user = self::getUserDb($id);
        if (empty($user))
            return false;
        foreach ($user as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    static private function getUserDb($id) {
        global $global, $APISecret;
        $id = intval($id);
        if (empty($id)) {
            return false;
        }
        if (empty($_SESSION['getUserDb'][$id])) {
            // always update
            $json = getAPI("user", "users_id={$id}");
            self::createUser($json->user);
            $user = self::_getUserDB($id);
            _session_start();
            $_SESSION['getUserDb'][$id] = $user;
        }
        return $_SESSION['getUserDb'][$id];
    }

    static private function _getUserDB($id) {
        $sql = "SELECT * FROM chat_users WHERE  id = ? LIMIT 1;";
        $res = sqlDAL::readSql($sql, "i", array($id), true);
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        return $user;
    }

    /**
     * return an name to identify the user from database
     * @return String
     */
    function getNameIdentificationBd() {
        return $this->identification;
    }

    static private function findById($id) {
        return self::getUserDb($id);
    }

    static function getPhoto($id = "") {
        if (empty($id)) {
            if (self::isLogged()) {
                return $_SESSION['login']->photo;
            }
        } else {
            $user = self::getUserDb($id);
            if ($user) {
                return $user['photoURL'];
            }
        }
        return false;
    }

    static function getNameIdentificationById($id = "") {
        if (empty($id)) {
            if (self::isLogged()) {
                return $_SESSION['login']->nameIdentification;
            }
        } else {
            $user = self::getUserDb($id);
            if ($user) {
                return $user['identification'];
            }
        }
        return false;
    }

    function getStatus() {
        return $this->status;
    }

    static function getChannelLink($users_id = 0) {
        global $global;
        $channelName = "";
        if (empty($id)) {
            if (!self::isLogged()) {
                return false;
            }
            $channelName = $_SESSION['login']->channelName;
        } else {
            $user = self::getUserDb($id);
            if ($user) {
                $channelName = $user['channelName'];
            }
        }
        $link = "{$global['webSiteRootURL']}channel/" . urlencode($channelName);
        return $link;
    }

    function login($noPass = false, $encodedPass = false) {
        
    }

    function getPhotoDB() {
        return self::getPhoto($this->id);
    }

    function getDonationLinkIfEnabled() {
        global $advancedCustomUser;
        if ($advancedCustomUser->allowDonationLink) {
            return $this->donationLink;
        }
        return false;
    }

    static function isLogged() {
        return Login::isLogged();
    }

    static function isAdmin() {
        return Login::isAdmin();
    }

    static function getId() {
        if (self::isLogged()) {
            return $_SESSION['login']->id;
        } else {
            return false;
        }
    }

    static function createUser($object) {
        if (empty($object->id)) {
            return false;
        }
        global $global;
        // check if the user exists already
        $user = self::_getUserDB($object->id);

        if (empty($object->nameIdentification) && !empty($object->identification)) {
            $object->nameIdentification = $object->identification;
        }

        if (!empty($user)) {// update
            $sql = "UPDATE chat_users SET "
                    . "`user` = '{$object->user}', "
                    . "`email` = '{$object->email}', "
                    . "`name` = '{$object->name}', "
                    . "`identification` = '{$object->nameIdentification}', "
                    . "`photoURL` = '{$object->photo}', "
                    . "`channelName` = '{$object->channelName}', "
                    . "`donationLink` = '{$object->donationLink}', "
                    . "`status` = 'a', "
                    . "`modified` = now() "
                    . " WHERE id = '{$object->id}'";
        } else {//insert
            $sql = "INSERT INTO chat_users (`id`, `user`, `email`, `name`, `identification`, `photoURL`, "
                    . "`channelName`,`status`, `created`, `modified`, donationLink) "
                    . " VALUES ('{$object->id}', '{$object->user}', '{$object->email}', '{$object->name}', '{$object->nameIdentification}', '{$object->photo}', "
                    . "'{$object->channelName}','a', now(), now(), '{$object->donationLink}');";
        }
        $insert_row = sqlDAL::writeSql($sql);

        return $object->id;
    }

    static function getNameIdentification() {
        if (!self::isLogged()) {
            return false;
        }
        return $_SESSION['login']->nameIdentification;
    }

    static function getUserName() {
        if (self::isLogged()) {
            return $_SESSION['login']->user;
        } else {
            return false;
        }
    }

    static function getUserPass() {
        if (self::isLogged()) {
            return $_SESSION['login']->pass;
        } else {
            return false;
        }
    }

}
