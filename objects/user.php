<?php

if (empty($global['systemRootPath'])) {
    $global['systemRootPath'] = '../';
}
require_once $global['systemRootPath'] . 'videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/bootGrid.php';

class User {

    private $id;
    private $user;
    private $name;
    private $email;
    private $password;
    private $isAdmin;
    private $canStream;
    private $canUpload;
    private $canCreateMeet;
    private $canViewChart;
    private $status;
    private $photoURL;
    private $backgroundURL;
    private $recoverPass;
    private $about;
    private $channelName;
    private $emailVerified;
    private $analyticsCode;
    private $externalOptions;
    private $userGroups = array();
    private $first_name;
    private $last_name;
    private $address;
    private $zip_code;
    private $country;
    private $region;
    private $city;
    private $donationLink;
    private $modified;
    static $DOCUMENT_IMAGE_TYPE = "Document Image";

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

    function getEmail() {
        return $this->email;
    }

    function getUser() {
        return $this->user;
    }

    function getAbout() {
        return str_replace(array('\\\\\\\n'), array("\n"), $this->about);
    }

    function setAbout($about) {
        $this->about = xss_esc($about);
    }

    function getPassword() {
        return $this->password;
    }

    function getCanStream() {
        return $this->canStream;
    }

    function setCanStream($canStream) {
        $this->canStream = (empty($canStream) || strtolower($canStream) === 'false') ? 0 : 1;
    }

    function getCanViewChart() {
        return $this->canViewChart;
    }

    function setCanViewChart($canViewChart) {
        $this->canViewChart = (empty($canViewChart) || strtolower($canViewChart) === 'false') ? 0 : 1;
    }

    function getCanCreateMeet() {
        return $this->canCreateMeet;
    }

    function setCanCreateMeet($canCreateMeet) {
        $this->canCreateMeet = (empty($canCreateMeet) || strtolower($canCreateMeet) === 'false') ? 0 : 1;
        ;
    }

    function getCanUpload() {
        return $this->canUpload;
    }

    function setCanUpload($canUpload) {
        $this->canUpload = (empty($canUpload) || strtolower($canUpload) === 'false') ? 0 : 1;
    }

    function getAnalyticsCode() {
        return $this->analyticsCode;
    }

    function setAnalyticsCode($analyticsCode) {
        preg_match("/(ua-\d{4,9}-\d{1,4})/i", $analyticsCode, $matches);
        if (!empty($matches[1])) {
            $this->analyticsCode = $matches[1];
        } else {
            $this->analyticsCode = "";
        }
    }

    function getAnalytics() {
        $id = $this->getId();
        $aCode = $this->getAnalyticsCode();
        if (!empty($id) && !empty($aCode)) {
            $code = "<!-- Global site tag (gtag.js) - Google Analytics From user {$id} -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id={$aCode}\"></script>
<script>
if (typeof gtag !== \"function\") {
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
}

  gtag('config', '{$aCode}');
</script>
";
        } else {
            $code = "<!-- No Analytics for this user {$id} -->";
        }
        return $code;
    }

    function addExternalOptions($id, $value) {
        $eo = unserialize(base64_decode($this->externalOptions));
        if (!is_array($eo)) {
            $eo = array();
        }
        $eo[$id] = $value;
        $this->setExternalOptions($eo);
        return $this->save();
    }

    function removeExternalOptions($id) {
        $eo = unserialize(base64_decode($this->externalOptions));
        unset($eo[$id]);
        $this->setExternalOptions($eo);
        return $this->save();
    }

    function setExternalOptions($options) {
        //we convert it to base64 to sanitize the input since we do not validate input from externalOptions
        $this->externalOptions = base64_encode(serialize($options));
    }

    function getExternalOption($id) {
        $eo = unserialize(base64_decode($this->externalOptions));
        if (empty($eo[$id])) {
            return NULL;
        }
        return $eo[$id];
    }

    private function load($id) {
        $id = intval($id);
        if (empty($id)) {
            return false;
        }
        $user = self::getUserDb($id);
        if (empty($user))
            return false;
        foreach ($user as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    private function loadFromUser($user) {
        $userLoaded = self::getUserDbFromUser($user);
        if (empty($userLoaded)){
            return false;
        }
        _error_log("User::loadFromUser($user) ");
        _error_log("User::loadFromUser json ". json_encode(debug_backtrace()));
        foreach ($userLoaded as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    function loadSelfUser() {
        $this->load($this->getId());
    }

    static function getId() {
        if (self::isLogged()) {
            return $_SESSION['user']['id'];
        } else {
            return false;
        }
    }

    static function getEmail_() {
        if (self::isLogged()) {
            return $_SESSION['user']['email'];
        } else {
            return false;
        }
    }

    function getBdId() {
        return $this->id;
    }

    static function updateSessionInfo() {
        if (self::isLogged()) {
            $user = self::getUserDb($_SESSION['user']['id']);
            $_SESSION['user'] = $user;
        }
    }

    static function getName() {
        if (self::isLogged()) {
            return $_SESSION['user']['name'];
        } else {
            return false;
        }
    }

    static function getUserName() {
        if (self::isLogged()) {
            return $_SESSION['user']['user'];
        } else {
            return false;
        }
    }

    static function getUserChannelName() {
        if (self::isLogged()) {

            if (empty($_SESSION['user']['channelName'])) {
                $_SESSION['user']['channelName'] = self::_recommendChannelName();
                $user = new User(User::getId());
                $user->setChannelName($_SESSION['user']['channelName']);
                $user->save();
            }

            return $_SESSION['user']['channelName'];
        } else {
            return false;
        }
    }

    static function _recommendChannelName($name = "", $try = 0, $unknown = "", $users_id=0) {
        if(empty($users_id)){
            if(!empty(User::getId())){
                $users_id = User::getId();
            }
        }
        if(empty($users_id)){
            $newChannelName = $name . "_" . uniqid();
            if(strlen($newChannelName)>40){
                $newChannelName = uniqid();
            }
            return $newChannelName;
        }
        if ($try > 10) {
            _error_log("User:_recommendChannelName too many tries ({$name}) (" . User::getId() . ") ", AVideoLog::$ERROR);
            return uniqid();
        }
        if (empty($name)) {
            $name = self::getNameIdentification();
            if ($name == __("Unknown User") && !empty($unknown)) {
                $name = $unknown;
            }
            $name = cleanString($name);
        }
        // in case is a email get only the username
        $parts = explode("@", $name);
        $name = $parts[0];
        // do not exceed 36 chars to leave some room for the unique id;
        $name = substr($name, 0, 36);
        if (!Permissions::canAdminUsers()) {
            $user = self::getUserFromChannelName($name);
            if ($user && $user['id'] !== $users_id) {
                return self::_recommendChannelName($name . "_" . uniqid(), $try + 1);
            }
        }
        return $name;
    }

    static function getUserFromChannelName($channelName) {
        $channelName = cleanString($channelName);
        global $global;
        $channelName = $global['mysqli']->real_escape_string($channelName);
        $sql = "SELECT * FROM users WHERE channelName = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($channelName));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);

        if ($user != false) {
            return $user;
        } else {
            return false;
        }
    }

    /**
     * return an name to identify the user
     * @return String
     */
    static function getNameIdentification() {
        global $advancedCustomUser;
        if (self::isLogged()) {
            if (!empty(self::getName()) && empty($advancedCustomUser->doNotIndentifyByName)) {
                return self::getName();
            }
            if (!empty(self::getMail()) && empty($advancedCustomUser->doNotIndentifyByEmail)) {
                return self::getMail();
            }
            if (!empty(self::getUserName()) && empty($advancedCustomUser->doNotIndentifyByUserName)) {
                return self::getUserName();
            }
            if (!empty(self::getUserChannelName())) {
                return self::getUserChannelName();
            }
        }
        return __("Unknown User");
    }

    /**
     * return an name to identify the user from database
     * @return String
     */
    function getNameIdentificationBd() {
        global $advancedCustomUser;
        if (!empty($this->name) && empty($advancedCustomUser->doNotIndentifyByName)) {
            return $this->name;
        }
        if (!empty($this->email) && empty($advancedCustomUser->doNotIndentifyByEmail)) {
            return $this->email;
        }
        if (!empty($this->user) && empty($advancedCustomUser->doNotIndentifyByUserName)) {
            return $this->user;
        }
        if (!empty($this->channelName)) {
            return $this->channelName;
        }
        return __("Unknown User");
    }

    static function getNameIdentificationById($id = "") {
        if (!empty($id)) {
            $user = new User($id);
            return $user->getNameIdentificationBd();
        }
        return __("Unknown User");
    }

    static function getUserPass() {
        if (self::isLogged()) {
            return $_SESSION['user']['password'];
        } else {
            return false;
        }
    }

    function _getName() {
        return $this->name;
    }

    static function _getPhoto($id = "") {
        global $global;
        if (!empty($id)) {
            $user = self::findById($id);
            if (!empty($user)) {
                $photo = $user['photoURL'];
            }
        } elseif (self::isLogged()) {
            $photo = $_SESSION['user']['photoURL'];
        }
        if (!empty($photo)) {
            if (preg_match("/videos\/userPhoto\/.*/", $photo) && file_exists($global['systemRootPath'] . $photo)) {
                return $photo;
            } else {
                $photoPath = "/videos/userPhoto/photo{$id}.png";
                $content = url_get_contents($photo);
                file_put_contents($global['systemRootPath'] . $photoPath, $content);
                $photo = $photoPath;
            }
        }
        if (empty($photo)) {
            $photo = "view/img/userSilhouette.jpg";
        }
        return $photo;
    }

    static function getPhoto($id = "") {
        global $global;
        if (!empty($id)) {
            $user = self::findById($id);
            if (!empty($user)) {
                $photo = $user['photoURL'];
            }
        } elseif (self::isLogged()) {
            $photo = $_SESSION['user']['photoURL'];
        }
        if (!empty($photo) && preg_match("/videos\/userPhoto\/.*/", $photo)) {
            if (file_exists($global['systemRootPath'] . $photo)) {
                $photo = $global['webSiteRootURL'] . $photo . "?" . filemtime($global['systemRootPath'] . $photo);
            } else {
                $photo = "";
            }
        }
        if (empty($photo)) {
            $photo = $global['webSiteRootURL'] . "view/img/userSilhouette.jpg";
        }
        return $photo;
    }

    static function _getOGImage($users_id) {
        return "/videos/userPhoto/photo{$users_id}_og_200X200.jpg";
    }

    static function deleteOGImage($users_id) {
        global $global;
        $photo = $global['systemRootPath'] . self::_getOGImage($users_id);
        @unlink($photo);
    }

    static function getOGImage($users_id = "") {
        global $global;
        $photo = self::_getPhoto($users_id);
        if ($photo == "view/img/userSilhouette.jpg") {
            return "{$global['webSiteRootURL']}view/img/userSilhouette.jpg";
        }
        if (empty($photo)) {
            return false;
        }
        $source = $global['systemRootPath'] . $photo;
        $destination = $global['systemRootPath'] . self::_getOGImage($users_id);

        convertImageToOG($source, $destination);

        return $global['webSiteRootURL'] . self::_getOGImage($users_id);
        ;
    }

    static function getEmailVerifiedIcon($id = "") {
        global $advancedCustomUser;
        $mark = "";
        if (!empty($advancedCustomUser->showEmailVerifiedMark)) {
            if (!empty($id)) {
                $user = self::findById($id);
                if (!empty($user)) {
                    $verified = $user['emailVerified'];
                }
            } elseif (self::isLogged()) {
                $verified = $_SESSION['user']['emailVerified'];
            }
            if (!empty($verified)) {
                
                $icon = "{$global['systemRootPath']}view/img/verifyicon.png";
                $mark .= ' <img src="http://157.230.2.203/VOD/view/img/verifyicon.png" style="height:30px;width:30px;" title="' . __("E-mail Verified") . '">';
            } else {
                //return '<i class="fas fa-times-circle text-muted"></i>';
                $mark .= '';
            }
        }
        if ($advancedCustomUser->Checkmark1Enabled) {
            if (User::externalOptionsFromUserID($id, "checkmark1")) {
                $mark .= " " . $advancedCustomUser->Checkmark1HTML;
            }
        }
        if ($advancedCustomUser->Checkmark2Enabled) {
            if (User::externalOptionsFromUserID($id, "checkmark2")) {
                $mark .= " " . $advancedCustomUser->Checkmark2HTML;
            }
        }
        if ($advancedCustomUser->Checkmark3Enabled) {
            if (User::externalOptionsFromUserID($id, "checkmark3")) {
                $mark .= " " . $advancedCustomUser->Checkmark3HTML;
            }
        }
        return $mark;
    }

    function getPhotoDB() {
        global $global;
        $photo = self::getPhoto($this->id);
        return $photo;
    }

    static function getBackground($id = "") {
        global $global;
        if (!empty($id)) {
            $user = self::findById($id);
            if (!empty($user)) {
                $photo = $user['backgroundURL'];
            }
        } elseif (self::isLogged()) {
            $photo = $_SESSION['user']['backgroundURL'];
        }
        if (!empty($photo) && preg_match("/videos\/userPhoto\/.*/", $photo)) {
            if (file_exists($global['systemRootPath'] . $photo)) {
                $photo = $global['webSiteRootURL'] . $photo;
            } else {
                $photo = "";
            }
        }
        if (empty($photo)) {
            $photo = $global['webSiteRootURL'] . "view/img/background.png";
        }
        return $photo;
    }

    static function getMail() {
        if (self::isLogged()) {
            return $_SESSION['user']['email'];
        } else {
            return false;
        }
    }

    function save($updateUserGroups = false) {
        global $global, $config, $advancedCustom, $advancedCustomUser;
        if (is_object($config) && $config->currentVersionLowerThen('5.6')) {
            // they dont have analytics code
            return false;
        }
        if (empty($this->user) || empty($this->password)) {
            //echo "u:" . $this->user . "|p:" . strlen($this->password);
            _error_log('Error : ' . __("You need a user and passsword to register"));
            return false;
        }
        if (empty($this->isAdmin)) {
            $this->isAdmin = "false";
        }
        if (empty($this->canStream)) {
            if (empty($this->id)) { // it is a new user
                if (empty($advancedCustomUser->newUsersCanStream)) {
                    $this->canStream = "0";
                } else {
                    $this->canStream = "1";
                }
            } else {
                $this->canStream = "0";
            }
        }
        if (empty($this->canUpload)) {
            $this->canUpload = "0";
        }
        if (empty($this->status)) {
            $this->status = 'a';
        }
        if (empty($this->emailVerified))
            $this->emailVerified = "false";

        $this->user = $global['mysqli']->real_escape_string($this->user);
        $this->password = $global['mysqli']->real_escape_string($this->password);
        $this->name = $global['mysqli']->real_escape_string($this->name);
        $this->status = $global['mysqli']->real_escape_string($this->status);
        $this->about = $global['mysqli']->real_escape_string($this->about);
        $this->about = preg_replace("/(\\\)+n/", "\n", $this->about);
        $this->channelName = self::_recommendChannelName($this->channelName, 0, $this->user, $this->id);
        $this->channelName = $global['mysqli']->real_escape_string($this->channelName);
        if (filter_var($this->donationLink, FILTER_VALIDATE_URL) === FALSE) {
            $this->donationLink = "";
        }
        if (!empty($this->id)) {
            $formats = "ssssiiii";
            $values = array($this->user, $this->password, $this->email, $this->name, $this->isAdmin, $this->canStream, $this->canUpload, $this->canCreateMeet);
            $sql = "UPDATE users SET user = ?, password = ?, "
                    . "email = ?, name = ?, isAdmin = ?,"
                    . "canStream = ?,canUpload = ?,canCreateMeet = ?,";
            if (isset($this->canViewChart)) {
                $formats .= "i";
                $values[] = $this->canViewChart;
                $sql .= "canViewChart = ?, ";
            }
            $formats .= "ssssssissssssssssi";
            $values[] = $this->status;
            $values[] = $this->photoURL;
            $values[] = $this->backgroundURL;
            $values[] = $this->recoverPass;
            $values[] = $this->about;
            $values[] = $this->channelName;
            $values[] = $this->emailVerified;
            $values[] = $this->analyticsCode;
            $values[] = $this->externalOptions;
            $values[] = $this->first_name;
            $values[] = $this->last_name;
            $values[] = $this->address;
            $values[] = $this->zip_code;
            $values[] = $this->country;
            $values[] = $this->region;
            $values[] = $this->city;
            $values[] = $this->donationLink;
            $values[] = $this->id;

            $sql .= "status = ?, "
                    . "photoURL = ?, backgroundURL = ?, "
                    . "recoverPass = ?, about = ?, "
                    . " channelName = ?, emailVerified = ? , analyticsCode = ?, externalOptions = ? , "
                    . " first_name = ? , last_name = ? , address = ? , zip_code = ? , country = ? , region = ? , city = ? , donationLink = ? , "
                    . " modified = now() WHERE id = ?";
        } else {
            $formats = "ssssiiiissssss";
            $values = array($this->user, $this->password, $this->email, $this->name, $this->isAdmin, $this->canStream, $this->canUpload, $this->canCreateMeet,
                $this->status, $this->photoURL, $this->recoverPass, $this->channelName, $this->analyticsCode, $this->externalOptions);
            $sql = "INSERT INTO users (user, password, email, name, isAdmin, canStream, canUpload, canCreateMeet, canViewChart, status,photoURL,recoverPass, created, modified, channelName, analyticsCode, externalOptions) "
                    . " VALUES (?,?,?,?,?,?,?,?, false, "
                    . "?,?,?, now(), now(),?,?,?)";
        }
        $insert_row = sqlDAL::writeSql($sql, $formats, $values);
        if ($insert_row) {
            if (empty($this->id)) {
                $id = $global['mysqli']->insert_id;
                if (!empty($advancedCustomUser->unverifiedEmailsCanNOTLogin)) {
                    self::sendVerificationLink($id);
                }
            } else {
                $id = $this->id;
            }
            if ($updateUserGroups) {
                require_once $global['systemRootPath'] . 'objects/userGroups.php';
                // update the user groups
                UserGroups::updateUserGroups($id, $this->userGroups);
            }
            return $id;
        } else {
            _error_log(' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error . " $sql");
            return false;
        }
    }

    static function getChannelOwner($channelName) {
        global $global;
        $channelName = $global['mysqli']->real_escape_string($channelName);
        $sql = "SELECT * FROM users WHERE channelName = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($channelName));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            $user = $result;
        } else {
            $user = false;
        }
        return $user;
    }

    static function getFromUsername($user) {
        global $global;
        $user = $global['mysqli']->real_escape_string($user);
        $sql = "SELECT * FROM users WHERE user = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($user));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            $user = $result;
        } else {
            $user = false;
        }
        return $user;
    }

    static function canWatchVideo($videos_id) {
        if (empty($videos_id)) {
            _error_log("User::canWatchVideo Video is empty ({$videos_id})");
            return false;
        }

        if (User::isAdmin()) {
            return true;
        }

        $video = new Video("", "", $videos_id);
        if ($video->getStatus() === 'i') {
            _error_log("User::canWatchVideo Video is inactive ({$videos_id})");
            return false;
        }
        $user = new User($video->getUsers_id());
        if ($user->getStatus() === 'i') {
            _error_log("User::canWatchVideo User is inactive ({$videos_id})");
            return false;
        }

        if (AVideoPlugin::userCanWatchVideo(User::getId(), $videos_id)) {
            return true;
        }

        // check if the video is not public 
        $rows = UserGroups::getVideoGroups($videos_id);

        if (empty($rows)) {
            // check if any plugin restrict access to this video
            if (!AVideoPlugin::userCanWatchVideo(User::getId(), $videos_id)) {
                if(User::isLogged()){
                    _error_log("User::canWatchVideo there is no usergorup set for this video but A plugin said user [" . User::getId() . "] can not see ({$videos_id})");
                }else{
                    _error_log("User::canWatchVideo there is no usergorup set for this video but A plugin said user [not logged] can not see ({$videos_id})");
                }
                return false;
            } else {
                return true; // the video is public
            }
        }

        if (!User::isLogged()) {
            _error_log("User::canWatchVideo You are not logged so can not see ({$videos_id}) session_id=" . session_id() . " SCRIPT_NAME=" . $_SERVER["SCRIPT_NAME"] . " IP = " . getRealIpAddr());
            return false;
        }
        // if is not public check if the user is on one of its groups
        $rowsUser = UserGroups::getUserGroups(User::getId());

        foreach ($rows as $value) {
            foreach ($rowsUser as $value2) {
                if ($value['id'] === $value2['id']) {
                    return true;
                }
            }
        }

        _error_log("User::canWatchVideo The user " . User::getId() . " is not on any of the user groups ({$videos_id}) " . json_encode($rows));
        return false;
    }

    static function canWatchVideoWithAds($videos_id) {
        if (empty($videos_id)) {
            _error_log("User::canWatchVideo (videos_id is empty) " . $videos_id);
            return false;
        }
        if (User::isAdmin()) {
            return true;
        }

        if (AVideoPlugin::userCanWatchVideoWithAds(User::getId(), $videos_id)) {
            return true;
        }
        _error_log("User::userCanWatchVideoWithAds (No can not) " . User::getId() . " " . $videos_id);

        if (self::canWatchVideo($videos_id)) {
            return true;
        }
        _error_log("User::canWatchVideo (No can not) " . $videos_id);

        return false;
    }

    function delete() {
        if (!self::isAdmin()) {
            return false;
        }
        // cannot delete yourself
        if (self::getId() === $this->id) {
            return false;
        }

        global $global;
        if (!empty($this->id)) {
            $sql = "DELETE FROM users WHERE id = ?";
        } else {
            return false;
        }
        return sqlDAL::writeSql($sql, "i", array($this->id));
    }

    const USER_LOGGED = 0;
    const USER_NOT_VERIFIED = 1;
    const USER_NOT_FOUND = 2;
    const CAPTCHA_ERROR = 3;
    const REQUIRE2FA = 4;

    function login($noPass = false, $encodedPass = false) {
        if(User::isLogged()){
            return false;
        }
        global $global, $advancedCustom, $advancedCustomUser, $config;
        if (strtolower($encodedPass) === 'false') {
            $encodedPass = false;
        }
        //_error_log("user::login: noPass = $noPass, encodedPass = $encodedPass, this->user, $this->user " . getRealIpAddr());
        if ($noPass) {
            $user = $this->find($this->user, false, true);
        } else {
            $user = $this->find($this->user, $this->password, true, $encodedPass);
        }

        if (!self::checkLoginAttempts()) {
            return self::CAPTCHA_ERROR;
        }
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // check for multiple logins attempts to prevent hacking end
        // if user is not verified
        if (!empty($user) && empty($user['isAdmin']) && empty($user['emailVerified']) && !empty($advancedCustomUser->unverifiedEmailsCanNOTLogin)) {
            unset($_SESSION['user']);
            self::sendVerificationLink($user['id']);
            return self::USER_NOT_VERIFIED;
        } else if ($user) {
            $_SESSION['user'] = $user;
            $this->setLastLogin($_SESSION['user']['id']);
            $rememberme = 0;
            if ((!empty($_POST['rememberme']) && $_POST['rememberme'] == "true") || !empty($_COOKIE['rememberme'])) {
                $expires = strtotime("+ 1 year");
                $rememberme = 1;
            } else {
                $expires = 0;
            }
            _setcookie("rememberme", $rememberme, $expires);
            _setcookie("user", $user['user'], $expires);
            _setcookie("pass", $user['password'], $expires);

            AVideoPlugin::onUserSignIn($_SESSION['user']['id']);
            $_SESSION['loginAttempts'] = 0;
            session_write_close();
            return self::USER_LOGGED;
        } else {
            unset($_SESSION['user']);
            return self::USER_NOT_FOUND;
        }
    }

    static function isCaptchaNeed() {
        global $advancedCustomUser;
        // check for multiple logins attempts to prevent hacking
        if (!empty($_SESSION['loginAttempts']) && !empty($advancedCustomUser->requestCaptchaAfterLoginsAttempts)) {
            if (isMobile()) {
                $advancedCustomUser->requestCaptchaAfterLoginsAttempts += 10;
            }
            if ($_SESSION['loginAttempts'] > $advancedCustomUser->requestCaptchaAfterLoginsAttempts) {
                return true;
            }
        }
        return false;
    }

    static function checkLoginAttempts() {
        global $advancedCustomUser, $global;
        // check for multiple logins attempts to prevent hacking
        if (empty($_SESSION['loginAttempts'])) {
            _session_start();
            $_SESSION['loginAttempts'] = 0;
        }
        if (!empty($advancedCustomUser->requestCaptchaAfterLoginsAttempts)) {
            _session_start();
            $_SESSION['loginAttempts']++;
            if ($_SESSION['loginAttempts'] > $advancedCustomUser->requestCaptchaAfterLoginsAttempts) {
                if (empty($_POST['captcha'])) {
                    return false;
                }
                require_once $global['systemRootPath'] . 'objects/captcha.php';
                if (!Captcha::validation($_POST['captcha'])) {
                    return false;
                }
            }
        }
        return true;
    }

    static function getCaptchaFormIfNeed() {
        // check for multiple logins attempts to prevent hacking
        if (self::isCaptchaNeed()) {
            return self::getCaptchaForm();
        }
        return "";
    }

    static function getCaptchaForm($uid = "") {
        global $global;
        return '<div class="input-group">'
                . '<span class="input-group-addon"><img src="' . $global['webSiteRootURL'] . 'captcha" id="captcha' . $uid . '"></span>
                    <span class="input-group-addon"><span class="btn btn-xs btn-success" id="btnReloadCapcha' . $uid . '"><span class="glyphicon glyphicon-refresh"></span></span></span>
                    <input name="captcha" placeholder="' . __("Type the code") . '" class="form-control" type="text" style="height: 60px;" maxlength="5" id="captchaText' . $uid . '">
                </div>
                <script>
                $(document).ready(function () {
                    $("#btnReloadCapcha' . $uid . '").click(function () {
                        $("#captcha' . $uid . '").attr("src", "' . $global['webSiteRootURL'] . 'captcha?" + Math.random());
                        $("#captchaText' . $uid . '").val("");
                    });
                });
                </script>';
    }

    private function setLastLogin($user_id) {
        global $global;
        if (empty($user_id)) {
            die('Error : setLastLogin ');
        }
        $sql = "UPDATE users SET lastLogin = now(), modified = now() WHERE id = ?";
        return sqlDAL::writeSql($sql, "i", array($user_id));
    }

    static function logoff() {
        global $global, $justLogoff;
        $justLogoff = true;
        _session_start();
        ObjectYPT::deleteAllSessionCache();
        _unsetcookie('rememberme');
        _unsetcookie('user');
        _unsetcookie('pass');
        unset($_SESSION['user']);
    }

    static private function recreateLoginFromCookie() {
        global $justLogoff, $justTryToRecreateLoginFromCookie;
        if (empty($justTryToRecreateLoginFromCookie) && empty($justLogoff) && empty($_SESSION['user']['id'])) {
            $justTryToRecreateLoginFromCookie = 1;
            if ((!empty($_COOKIE['user'])) && (!empty($_COOKIE['pass'])) && (!empty($_COOKIE['rememberme']))) {

                $user = new User(0, $_COOKIE['user'], false);
                $user->setPassword($_COOKIE['pass'], true);
                //  $dbuser = self::getUserDbFromUser($_COOKIE['user']);
                $resp = $user->login(false, true);

                _error_log("user::recreateLoginFromCookie: do cookie-login: " . $_COOKIE['user'] . "   result: " . $resp);
                if (0 == $resp) {
                    _error_log("success " . $_SESSION['user']['id']);
                } else {
                    _error_log("user::recreateLoginFromCookie: do logoff: " . $_COOKIE['user'] . "   result: " . $resp);
                    self::logoff();
                }
            }
        }
    }

    static function isLogged() {
        self::recreateLoginFromCookie();
        return !empty($_SESSION['user']['id']);
    }

    static function isVerified() {
        self::recreateLoginFromCookie();
        return !empty($_SESSION['user']['emailVerified']);
    }

    static function isAdmin() {
        self::recreateLoginFromCookie();
        return !empty($_SESSION['user']['isAdmin']);
    }

    static function canStream() {
        self::recreateLoginFromCookie();
        return !empty($_SESSION['user']['isAdmin']) || !empty($_SESSION['user']['canStream']);
    }

    static function externalOptions($id) {
        if (!empty($_SESSION['user']['externalOptions'])) {
            $externalOptions = unserialize(base64_decode($_SESSION['user']['externalOptions']));
            if (isset($externalOptions[$id])) {
                if ($externalOptions[$id] == "true")
                    $externalOptions[$id] = true;
                else
                if ($externalOptions[$id] == "false")
                    $externalOptions[$id] = false;

                return $externalOptions[$id];
            }
        }
        return false;
    }

    static function externalOptionsFromUserID($users_id, $id) {
        $user = self::findById($users_id);
        if ($user) {
            if (!is_null($user['externalOptions'])) {
                $externalOptions = unserialize(base64_decode($user['externalOptions']));
                if (is_array($externalOptions) && sizeof($externalOptions) > 0) {
                    foreach ($externalOptions as $k => $v) {
                        if ($id != $k) {
                            continue;
                        }
                        if ($v == "true")
                            $v = 1;
                        else
                        if ($v == "false")
                            $v = 0;
                        return $v;
                    }
                }
            }
        }
        return false;
    }

    function thisUserCanStream() {
        if ($this->status === 'i') {
            return false;
        }
        return !empty($this->isAdmin) || !empty($this->canStream);
    }

    private function find($user, $pass, $mustBeactive = false, $encodedPass = false) {
        global $global, $advancedCustom;
        $formats = "";
        $values = array();
        $user = $global['mysqli']->real_escape_string($user);
        $sql = "SELECT * FROM users WHERE user = ? ";

        $formats .= "s";
        $values[] = $user;

        if ($mustBeactive) {
            $sql .= " AND status = 'a' ";
        }

        $sql .= " LIMIT 1";
        $res = sqlDAL::readSql($sql, $formats, $values, true);
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if (!empty($result)) {
            if ($pass !== false) {
                if (!encryptPasswordVerify($pass, $result['password'], $encodedPass)) {
                    if (!empty($advancedCustom) && $advancedCustom->enableOldPassHashCheck) {
                        _error_log("Password check new hash pass does not match, trying MD5");
                        return $this->find_Old($user, $pass, $mustBeactive, $encodedPass);
                    } else {
                        return false;
                    }
                }
            }
            $user = $result;
        } else {
            _error_log("Password check new hash user not found");
            //check if is the old password style
            $user = false;
            //$user = false;
        }
        return $user;
    }

    /**
     * this is the deprecated function, with week password
     * @global type $global
     * @param type $user
     * @param type $pass
     * @param type $mustBeactive
     * @param type $encodedPass
     * @return boolean
     */
    private function find_Old($user, $pass, $mustBeactive = false, $encodedPass = false) {
        global $global;
        $formats = "";
        $values = array();
        $user = $global['mysqli']->real_escape_string($user);
        $sql = "SELECT * FROM users WHERE user = ? ";

        $formats .= "s";
        $values[] = $user;

        if ($mustBeactive) {
            $sql .= " AND status = 'a' ";
        }
        if ($pass !== false) {
            if (!$encodedPass || $encodedPass === 'false') {
                _error_log("Password check Old not encoded pass");
                $passEncoded = md5($pass);
            } else {
                _error_log("Password check Old encoded pass");
                $passEncoded = $pass;
            }
            $sql .= " AND password = ? ";
            $formats .= "s";
            $values[] = $passEncoded;
        }
        $sql .= " LIMIT 1";
        $res = sqlDAL::readSql($sql, $formats, $values, true);
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if (!empty($result)) {
            if (!$encodedPass || $encodedPass === 'false') {
                //update the password
                $u = new User($result['id']);
                $u->setPassword($pass);
                $u->save();
                $result['password'] = $u->getPassword();
            }
            $user = $result;
        } else {
            $user = false;
        }
        if (empty($user)) {
            _error_log("Password check Old not found");
        } else {
            _error_log("Password check Old found");
        }
        return $user;
    }

    static private function findById($id) {
        global $global;
        $id = intval($id);
        if (empty($id)) {
            return false;
        }
        $sql = "SELECT * FROM users WHERE id = ?  LIMIT 1";
        $res = sqlDAL::readSql($sql, "i", array($id));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res) {
            $user = $result;
        } else {
            $user = false;
        }
        return $user;
    }

    static function findByEmail($email) {
        global $global;
        $email = trim($email);
        if (empty($email)) {
            return false;
        }
        $sql = "SELECT * FROM users WHERE email = ?  LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($email));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($res != false) {
            $user = $result;
        } else {
            $user = false;
        }
        return $user;
    }

    static private function getUserDb($id) {
        global $global;
        $id = intval($id);
        if (empty($id)) {
            return false;
        }
        $sql = "SELECT * FROM users WHERE  id = ? LIMIT 1;";
        $res = sqlDAL::readSql($sql, "i", array($id));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($user != false) {
            return $user;
        }
        return false;
    }

    static private function getUserDbFromUser($user) {
        global $global;
        if (empty($user)) {
            return false;
        }
        $sql = "SELECT * FROM users WHERE user = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($user));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($user != false) {
            return $user;
        }
        return false;
    }

    static function getUserFromID($users_id) {
        global $global;
        if (empty($users_id)) {
            return false;
        }
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($users_id));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($user != false) {
            $user['groups'] = UserGroups::getUserGroups($user['id']);
            $user['identification'] = self::getNameIdentificationById($user['id']);
            $user['photo'] = self::getPhoto($user['id']);
            $user['background'] = self::getBackground($user['id']);
            $user['tags'] = self::getTags($user['id']);
            $user['name'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $user['name']);
            $user['isEmailVerified'] = $user['emailVerified'];
            if (!is_null($user['externalOptions'])) {
                $externalOptions = unserialize(base64_decode($user['externalOptions']));
                if (is_array($externalOptions) && sizeof($externalOptions) > 0) {
                    foreach ($externalOptions as $k => $v) {
                        if ($v == "true")
                            $v = 1;
                        else
                        if ($v == "false")
                            $v = 0;
                        $user[$k] = $v;
                    }
                }
            }
            unset($user['password']);
            unset($user['recoverPass']);
            if (!Permissions::canAdminUsers() && $user['id'] !== User::getId()) {
                unset($user['first_name']);
                unset($user['last_name']);
                unset($user['address']);
                unset($user['zip_code']);
                unset($user['country']);
                unset($user['region']);
                unset($user['city']);
            }
            $user = $user;
            return $user;
        }
        return false;
    }

    static function getUserFromEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($email));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($user != false) {
            return $user;
        }
        return false;
    }

    function setUser($user) {
        global $advancedCustomUser;
        if (empty($advancedCustomUser->userCanChangeUsername)) {
            if (!empty($this->user)) {
                return false;
            }
        }
        $this->user = strip_tags($user);
    }

    function setName($name) {
        $this->name = strip_tags($name);
    }

    function setEmail($email) {
        global $advancedCustomUser;
        $email = strip_tags($email);
        if (!empty($advancedCustomUser->emailMustBeUnique)) {
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $userFromEmail = User::getUserFromEmail($email);
            if (!empty($userFromEmail)) {
                return false;
            }
        }
        $this->email = $email;
        return true;
    }

    function setPassword($password, $doNotEncrypt = false) {
        if (!empty($password)) {
            if ($doNotEncrypt) {
                $this->password = ($password);
            } else {
                $this->password = encryptPassword($password);
            }
        }
    }

    function setIsAdmin($isAdmin) {
        if (empty($isAdmin) || $isAdmin === "false" || !User::isAdmin()) {
            $isAdmin = "0";
        } else {
            $isAdmin = "1";
        }
        $this->isAdmin = $isAdmin;
    }

    function setStatus($status) {
        $this->status = strip_tags($status);
    }

    function getPhotoURL() {
        return $this->photoURL;
    }

    function setPhotoURL($photoURL) {
        $this->photoURL = strip_tags($photoURL);
    }

    static function getAllUsers($ignoreAdmin = false, $searchFields = array('name', 'email', 'user', 'channelName', 'about'), $status = "") {
        if (!Permissions::canAdminUsers() && !$ignoreAdmin) {
            return false;
        }
        //will receive
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT * FROM users WHERE 1=1 ";
        if (!empty($status)) {
            if (strtolower($status) === 'i') {
                $sql .= " AND status = 'i' ";
            } else {
                $sql .= " AND status = 'a' ";
            }
        }
        $sql .= BootGrid::getSqlFromPost($searchFields);

        $user = array();
        require_once $global['systemRootPath'] . 'objects/userGroups.php';
        $res = sqlDAL::readSql($sql . ";");
        $downloadedArray = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        if ($res != false) {
            foreach ($downloadedArray as $row) {
                $row['groups'] = UserGroups::getUserGroups($row['id']);
                $row['identification'] = self::getNameIdentificationById($row['id']);
                $row['photo'] = self::getPhoto($row['id']);
                $row['background'] = self::getBackground($row['id']);
                $row['tags'] = self::getTags($row['id']);
                $row['name'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $row['name']);
                $row['isEmailVerified'] = $row['emailVerified'];
                if (!is_null($row['externalOptions'])) {
                    $externalOptions = unserialize(base64_decode($row['externalOptions']));
                    if (is_array($externalOptions) && sizeof($externalOptions) > 0) {
                        foreach ($externalOptions as $k => $v) {
                            if ($v == "true")
                                $v = 1;
                            else
                            if ($v == "false")
                                $v = 0;
                            $row[$k] = $v;
                        }
                    }
                }
                unset($row['password']);
                unset($row['recoverPass']);
                if (!Permissions::canAdminUsers() && $row['id'] !== User::getId()) {
                    unset($row['first_name']);
                    unset($row['last_name']);
                    unset($row['address']);
                    unset($row['zip_code']);
                    unset($row['country']);
                    unset($row['region']);
                    unset($row['city']);
                }
                $user[] = $row;
            }
        } else {
            $user = false;
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }

        return $user;
    }

    static function getAllUsersThatHasVideos($ignoreAdmin = false) {
        if (!self::isAdmin() && !$ignoreAdmin) {
            return false;
        }
        global $global;
        $sql = "SELECT * FROM users u WHERE status = 'a' AND (canUpload = 1 || isAdmin = 1) AND "
                . " (SELECT count(id) FROM videos where users_id = u.id )>0 ";

        $user = array();
        $res = sqlDAL::readSql($sql . ";");
        $downloadedArray = sqlDAL::fetchAllAssoc($res);
        sqlDAL::close($res);
        if ($res != false) {
            foreach ($downloadedArray as $row) {
                $user[] = $row;
            }
        } else {
            $user = false;
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }

        return $user;
    }

    static function getTotalUsers($ignoreAdmin = false, $status = "") {
        if (!Permissions::canAdminUsers() && !$ignoreAdmin) {
            return false;
        }
        //will receive
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT id FROM users WHERE 1=1  ";

        if (!empty($status)) {
            if (strtolower($status) === 'i') {
                $sql .= " AND status = 'i' ";
            } else {
                $sql .= " AND status = 'a' ";
            }
        }
        $sql .= BootGrid::getSqlSearchFromPost(array('name', 'email', 'user'));

        $res = sqlDAL::readSql($sql);
        $result = sqlDal::num_rows($res);
        sqlDAL::close($res);


        return $result;
    }

    static function userExists($user) {
        global $global;
        $user = $global['mysqli']->real_escape_string($user);
        $sql = "SELECT * FROM users WHERE user = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "s", array($user));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);

        if ($user != false) {
            return $user['id'];
        } else {
            return false;
        }
    }

    static function idExists($users_id) {
        global $global;
        $users_id = intval($users_id);
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "i", array($users_id));
        $user = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        if ($user != false) {
            return $user['id'];
        } else {
            return false;
        }
    }

    static function createUserIfNotExists($user, $pass, $name, $email, $photoURL, $isAdmin = false, $emailVerified = false) {
        global $global, $advancedCustomUser;
        $user = $global['mysqli']->real_escape_string($user);
        $userId = 0;
        if (!$userId = self::userExists($user)) {
            if (empty($pass)) {
                $pass = uniqid();
            }
            $pass = encryptPassword($pass);
            $userObject = new User(0, $user, $pass);
            $userObject->setEmail($email);
            $userObject->setName($name);
            $userObject->setIsAdmin($isAdmin);
            $userObject->setPhotoURL($photoURL);
            $userObject->setEmailVerified($emailVerified);
            $userId = $userObject->save();
            if (!empty($userId)) {
                if (!empty($advancedCustomUser->userDefaultUserGroup->value)) { // for new users use the default usergroup
                    UserGroups::updateUserGroups($userId, array($advancedCustomUser->userDefaultUserGroup->value), true);
                }
            }
            return $userId;
        } else {
            if ($emailVerified) {
                $userObj = new User($userId);
                if (!$userObj->getEmailVerified()) {
                    $userObj->setEmailVerified(1);
                    $userObj->save();
                }
            }
        }
        return $userId;
    }

    function getRecoverPass() {
        return $this->recoverPass;
    }

    function setRecoverPass($recoverPass, $forceChange = false) {
        // let the same recover pass if it was 10 minutes ago
        if (empty($forceChange) && !empty($this->recoverPass) && !empty($recoverPass) && !empty($this->modified) && strtotime($this->modified) > strtotime("-10 minutes")) {
            return $this->recoverPass;
        }
        $this->recoverPass = $recoverPass;
        return $this->recoverPass;
    }

    static function canUpload($doNotCheckPlugins = false) {
        global $global, $config, $advancedCustomUser;
        if(Permissions::canModerateVideos()){
            return true;
        }
        if (User::isAdmin()) {
            return true;
        }
        if (empty($doNotCheckPlugins) && !AVideoPlugin::userCanUpload(User::getId())) {
            return false;
        }

        if (isset($advancedCustomUser->onlyVerifiedEmailCanUpload) && $advancedCustomUser->onlyVerifiedEmailCanUpload && !User::isVerified()) {
            return false;
        }

        if ($config->getAuthCanUploadVideos()) {
            return self::isLogged();
        }
        if (self::isLogged() && !empty($_SESSION['user']['canUpload'])) {
            return true;
        }
        return self::isAdmin();
    }

    static function canViewChart() {
        global $global, $config;
        if (self::isLogged() && !empty($_SESSION['user']['canViewChart'])) {
            return true;
        }
        return self::isAdmin();
    }

    static function canCreateMeet() {
        global $global, $config;
        if (self::isLogged() && !empty($_SESSION['user']['canCreateMeet'])) {
            return true;
        }
        return self::isAdmin();
    }

    static function canComment() {
        global $global, $config, $advancedCustomUser;
        if (self::isAdmin()) {
            return true;
        }
        
        if(Permissions::canAdminComment()){
            return true;
        }
        
        if ($config->getAuthCanComment()) {
            if (empty($advancedCustomUser->unverifiedEmailsCanNOTComment)) {
                return self::isLogged();
            } else {
                return self::isVerified();
            }
        }
        return false;
    }

    static function canSeeCommentTextarea() {
        global $global, $config;
        if (!$config->getAuthCanComment()) {
            if (!self::isAdmin()) {
                return false;
            }
        }
        return true;
    }

    function getUserGroups() {
        return $this->userGroups;
    }

    function setUserGroups($userGroups) {
        if (is_array($userGroups)) {
            $this->userGroups = $userGroups;
        }
    }

    function getIsAdmin() {
        return $this->isAdmin;
    }

    function getStatus() {
        return $this->status;
    }

    /**
     *
     * @param type $user_id
     * text
     * label Default Primary Success Info Warning Danger
     */
    static function getTags($user_id) {
        $user = new User($user_id);
        $tags = array();
        if ($user->getIsAdmin()) {
            $obj = new stdClass();
            $obj->type = "info";
            $obj->text = __("Admin");
            $tags[] = $obj;
        } else {
            $obj = new stdClass();
            $obj->type = "default";
            $obj->text = __("Regular User");
            $tags[] = $obj;
        }

        if ($user->getStatus() == "a") {
            $obj = new stdClass();
            $obj->type = "success";
            $obj->text = __("Active");
            $tags[] = $obj;
        } else {
            $obj = new stdClass();
            $obj->type = "danger";
            $obj->text = __("Inactive");
            $tags[] = $obj;
        }
        if ($user->getEmailVerified()) {
            $obj = new stdClass();
            $obj->type = "success";
            $obj->text = __("E-mail Verified");
            $tags[] = $obj;
        } else {
            $obj = new stdClass();
            $obj->type = "warning";
            $obj->text = __("E-mail Not Verified");
            $tags[] = $obj;
        }
        global $global;
        if (!empty($global['systemRootPath'])) {
            require_once $global['systemRootPath'] . 'objects/userGroups.php';
        } else {
            require_once 'userGroups.php';
        }
        $groups = UserGroups::getUserGroups($user_id);
        foreach ($groups as $value) {
            $obj = new stdClass();
            $obj->type = "warning";
            $obj->text = $value['group_name'];
            $tags[] = $obj;
        }

        return $tags;
    }

    function getBackgroundURL() {
        global $global;
        $this->backgroundURL = self::getBackgroundURLFromUserID($this->id);
        return $this->backgroundURL;
    }

    static function getBackgroundURLFromUserID($users_id = 0) {
        if (empty($users_id)) {
            $users_id = User::getId();
        }
        global $global;
        $backgroundURL = "videos/userPhoto/background{$users_id}.jpg";
        if (!file_exists($global['systemRootPath'] . $backgroundURL)) {
            $backgroundURL = "videos/userPhoto/background{$users_id}.png";
        }
        if (!file_exists($global['systemRootPath'] . $backgroundURL)) {
            $backgroundURL = "view/img/background.jpg";
        }
        return $backgroundURL;
    }

    function setBackgroundURL($backgroundURL) {
        $this->backgroundURL = strip_tags($backgroundURL);
    }

    function getChannelName() {
        if (empty($this->channelName)) {
            $this->channelName = self::_recommendChannelName($this->channelName);
            $this->save();
        }
        return $this->channelName;
    }

    static function _getUserChannelName($users_id = 0) {
        global $global, $config;
        if (empty($users_id)) {
            $users_id = self::getId();
        }
        $user = new User($users_id);
        if (empty($user)) {
            return false;
        }

        return $user->getChannelName();
    }

    function getEmailVerified() {
        return intval($this->emailVerified);
    }

    static function validateChannelName($channelName) {
        return trim(preg_replace("/[^0-9A-Z_]/i", "", ucwords($channelName)));
    }

    /**
     *
     * @param type $channelName
     * @return boolean return true is is unique
     */
    function setChannelName($channelName) {
        $channelName = self::validateChannelName($channelName);
        $user = static::getChannelOwner($channelName);
        if (!empty($user)) { // if the channel name exists and it is not from this user, rename the channel name
            if (empty($this->id) || $user['id'] != $this->id) {
                return false;
            }
        }
        $this->channelName = xss_esc($channelName);
        return true;
    }

    function setEmailVerified($emailVerified) {
        $this->emailVerified = (empty($emailVerified) || strtolower($emailVerified) === 'false') ? 0 : 1;
    }

    static function getChannelLink($users_id = 0) {
        global $global;
        $name = self::_getChannelName($users_id);
        if (empty($name)) {
            return false;
        }
        $link = "{$global['webSiteRootURL']}channel/" . urlencode($name);
        return $link;
    }

    static function getChannelLinkFromChannelName($channelName) {
        global $global;
        $link = "{$global['webSiteRootURL']}channel/" . urlencode($channelName);
        return $link;
    }

    static function _getChannelName($users_id = 0) {
        global $global, $config;
        if (empty($users_id)) {
            $users_id = self::getId();
        }
        $user = new User($users_id);
        if (empty($user)) {
            return false;
        }
        if (empty($user->getChannelName())) {
            $name = $user->getBdId();
        } else {
            $name = $user->getChannelName();
        }
        return $name;
    }

    static function sendVerificationLink($users_id) {
        global $global, $advancedCustomUser;
        //Only send the verification email each 30 minutes
        if (!empty($_SESSION["sendVerificationLink"][$users_id]) && time() - $_SESSION["sendVerificationLink"][$users_id] > 1800) {
            _error_log("sendVerificationLink: Email already sent, we will wait 30 min  {$users_id}");
            return true;
        }
        $config = new Configuration();
        $user = new User($users_id);
        $code = urlencode(static::createVerificationCode($users_id));
        require_once $global['systemRootPath'] . 'objects/PHPMailer/src/PHPMailer.php';
        require_once $global['systemRootPath'] . 'objects/PHPMailer/src/SMTP.php';
        require_once $global['systemRootPath'] . 'objects/PHPMailer/src/Exception.php';
        //Create a new PHPMailer instance
        if (!is_object($config)) {
            _error_log("sendVerificationLink: config is not a object " . json_encode($config));
            return false;
        }
        $contactEmail = $config->getContactEmail();
        $webSiteTitle = $config->getWebSiteTitle();
        $email = $user->getEmail();
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer;
            setSiteSendMessage($mail);
            //$mail->SMTPDebug = 4;
            //Set who the message is to be sent from
            $mail->setFrom($contactEmail, $webSiteTitle);
            //Set who the message is to be sent to
            $mail->addAddress($email);
            //Set the subject line
            $mail->Subject = __('Please Verify Your E-mail ') . $webSiteTitle;

            $msg = sprintf(__("Hi %s"), $user->getName());
            $msg .= "<br><br>" . __($advancedCustomUser->verificationMailTextLine1);
            $msg .= "<br><br>" . sprintf(__($advancedCustomUser->verificationMailTextLine2), $webSiteTitle);
            $msg .= "<br><br>" . sprintf(__($advancedCustomUser->verificationMailTextLine3), $webSiteTitle);
            $msg .= "<br><br>" . sprintf(__($advancedCustomUser->verificationMailTextLine4));
            $msg .= "<br><br>" . " <a href='{$global['webSiteRootURL']}objects/userVerifyEmail.php?code={$code}'>" . __("Verify") . "</a>";

            $mail->msgHTML($msg);
            $resp = $mail->send();
            if (!$resp) {
                _error_log("sendVerificationLink Error Info: {$mail->ErrorInfo}");
            } else {
                _session_start();
                $_SESSION["sendVerificationLink"][$users_id] = time();
            }
            return $resp;
        } catch (phpmailerException $e) {
            _error_log($e->errorMessage()); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
            _error_log($e->getMessage()); //Boring error messages from anything else!
        }
        return false;
    }

    static function verifyCode($code) {
        global $global;
        $obj = static::decodeVerificationCode($code);
        $salt = hash('sha256', $global['salt']);
        if ($salt !== $obj->salt) {
            return false;
        }
        $user = new User($obj->users_id);
        $recoverPass = $user->getRecoverPass();
        if ($recoverPass == $obj->recoverPass) {
            $user->setEmailVerified(1);
            return $user->save();
        }
        return false;
    }

    static function createVerificationCode($users_id) {
        global $global;
        $obj = new stdClass();
        $obj->users_id = $users_id;
        $obj->recoverPass = uniqid();
        $obj->salt = hash('sha256', $global['salt']);

        $user = new User($users_id);
        $obj->recoverPass = $user->setRecoverPass($obj->recoverPass);
        $user->save();

        return base64_encode(json_encode($obj));
    }

    static function decodeVerificationCode($code) {
        $obj = json_decode(base64_decode($code));
        return $obj;
    }

    function getFirst_name() {
        return $this->first_name;
    }

    function getLast_name() {
        return $this->last_name;
    }

    function getAddress() {
        return $this->address;
    }

    function getZip_code() {
        return $this->zip_code;
    }

    function getCountry() {
        return $this->country;
    }

    function getRegion() {
        return $this->region;
    }

    function getCity() {
        return $this->city;
    }

    function setFirst_name($first_name) {
        $this->first_name = $first_name;
    }

    function setLast_name($last_name) {
        $this->last_name = $last_name;
    }

    function setAddress($address) {
        $this->address = $address;
    }

    function setZip_code($zip_code) {
        $this->zip_code = $zip_code;
    }

    function setCountry($country) {
        $this->country = $country;
    }

    function setRegion($region) {
        $this->region = $region;
    }

    function setCity($city) {
        $this->city = $city;
    }

    static function getDocumentImage($users_id) {
        $row = static::getBlob($users_id, User::$DOCUMENT_IMAGE_TYPE);
        if (!empty($row['blob'])) {
            return $row['blob'];
        }
        return false;
    }

    static function saveDocumentImage($image, $users_id) {
        $row = static::saveBlob($image, $users_id, User::$DOCUMENT_IMAGE_TYPE);
        if (!empty($row['blob'])) {
            return $row['blob'];
        }
        return false;
    }

    static function getBlob($users_id, $type) {
        global $global;
        $sql = "SELECT * FROM users_blob WHERE users_id = ? AND `type` = ? LIMIT 1";
        $res = sqlDAL::readSql($sql, "is", array($users_id, $type));
        $result = sqlDAL::fetchAssoc($res);
        sqlDAL::close($res);
        return $result;
    }

    static function saveBlob($blob, $users_id, $type) {
        global $global;
        $row = self::getBlob($users_id, $type);
        $null = NULL;
        if (!empty($row['id'])) {
            $sql = "UPDATE users_blob SET `blob` = ? , modified = now() WHERE id = ?";
            $stmt = $global['mysqli']->prepare($sql);
            $stmt->bind_param('bi', $null, $row['id']);
        } else {
            $sql = "INSERT INTO users_blob (`blob`, users_id, `type`, modified, created) VALUES (?,?,?, now(), now())";
            $stmt = $global['mysqli']->prepare($sql);
            $stmt->bind_param('bis', $null, $users_id, $type);
        }

        $stmt->send_long_data(0, $blob);


        return $stmt->execute();
    }

    static function deleteBlob($users_id, $type) {
        global $global;
        $row = self::getBlob($users_id, $type);
        if (!empty($row['id'])) {
            $sql = "DELETE FROM users_blob ";
            $sql .= " WHERE id = ?";
            $global['lastQuery'] = $sql;
            //_error_log("Delete Query: ".$sql);
            return sqlDAL::writeSql($sql, "i", array($row['id']));
        }
        _error_log("Id for table users_blob not defined for deletion");
        return false;
    }

    function getDonationLink() {
        return $this->donationLink;
    }

    function getDonationLinkIfEnabled() {
        global $advancedCustomUser;
        if ($advancedCustomUser->allowDonationLink) {
            return $this->donationLink;
        }
        return false;
    }

    function setDonationLink($donationLink) {
        $this->donationLink = $donationLink;
    }

    static function donationLink() {
        if (self::isLogged()) {
            return $_SESSION['user']['donationLink'];
        } else {
            return false;
        }
    }

    static function loginFromRequest() {
        $inputJSON = url_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); //convert JSON into array
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                if (empty($_REQUEST[$key])) {
                    $_REQUEST[$key] = $value;
                }
            }
        }
        if (!empty($_REQUEST['do_not_login'])) {
            return false;
        }
        if (!empty($_REQUEST['user']) && !empty($_REQUEST['pass'])) {
            $user = new User(0, $_REQUEST['user'], $_REQUEST['pass']);
            $user->login(false, !empty($_REQUEST['encodedPass']));
        }
    }

    static function loginFromRequestToGet() {
        if (!empty($_REQUEST['user']) && !empty($_REQUEST['pass'])) {
            $return = "user={$_REQUEST['user']}&pass={$_REQUEST['pass']}";
            if (!empty($_REQUEST['encodedPass'])) {
                $return .= "&encodedPass=" . intval($_REQUEST['encodedPass']);
            }
            return $return;
        }
        return "";
    }

    static function getBlockUserButton($users_id) {
        $canBlock = self::userCanBlockUserWithReason($users_id);
        if (!$canBlock->result) {
            return "<!-- {$canBlock->msg} -->";
        }
        return ReportVideo::buttonBlockUser($users_id);
    }

    static function getActionBlockUserButton($users_id) {
        $canBlock = self::userCanBlockUserWithReason($users_id);
        if (!$canBlock->result) {
            return "<!-- {$canBlock->msg} -->";
        }
        return ReportVideo::actionButtonBlockUser($users_id);
    }

    static function userCanBlockUser($users_id, $ignoreIfIsAlreadyBLocked = false) {
        if (empty($users_id)) {
            return false;
        }
        if (!User::isLogged()) {
            return false;
        }
        if ($users_id == User::getId()) {
            return false;
        }
        if (empty($ignoreIfIsAlreadyBLocked)) {
            $report = AVideoPlugin::getDataObjectIfEnabled("ReportVideo");
            if (empty($report)) {
                return false;
            }
        }
        return true;
    }

    static function userCanBlockUserWithReason($users_id, $ignoreIfIsAlreadyBLocked = false) {
        $obj = new stdClass();
        $obj->result = false;
        $obj->msg = "Unkonw";

        if (empty($users_id)) {
            $obj->msg = "Empty User ID";
            return $obj;
        }
        if (!User::isLogged()) {
            $obj->msg = "You are not logged";
            return $obj;
        }
        if ($users_id == User::getId()) {
            $obj->msg = "You cannot block your own video";
            return $obj;
        }
        if (empty($ignoreIfIsAlreadyBLocked)) {
            $report = AVideoPlugin::getDataObjectIfEnabled("ReportVideo");
            if (empty($report)) {
                $obj->msg = "this user is already blocked";
                return $obj;
            }
        }

        $obj->result = true;
        $obj->msg = "You can block";
        return $obj;
    }

    static function hasBlockedUser($reported_users_id, $users_id = 0) {
        if (empty($users_id)) {
            $users_id = User::getId();
        }
        if (!self::userCanBlockUser($reported_users_id, true)) {
            return false;
        }
        $report = AVideoPlugin::getDataObjectIfEnabled("ReportVideo");
        if (!empty($report)) {
            return ReportVideo::isBlocked($reported_users_id, $users_id);
        } else {
            return false;
        }
    }

    function updateUserImages($params = array()) {

        $id = $this->id;
        $obj = new stdClass();

        // Update Background Image
        if (isset($params['backgroundImg']) && $params['backgroundImg'] != '') {

            $background = url_get_contents($params['backgroundImg']);
            $ext = pathinfo(parse_url($params['backgroundImg'], PHP_URL_PATH), PATHINFO_EXTENSION);
            $allowed = array('jpg', 'jpeg', 'gif', 'png');
            if (!in_array(strtolower($ext), $allowed)) {
                return "File extension error background Image, We allow only (" . implode(",", $allowed) . ")";
            }

            $backgroundPath = "videos/userPhoto/tmp_background{$id}.".$ext;
            $oldfile = "videos/userPhoto/background{$id}.png";
            $file = "videos/userPhoto/background{$id}.jpg";

            if (!isset($global['systemRootPath'])) {
                $global['systemRootPath'] = '../../';
            }

            $filePath = $global['systemRootPath'] . $backgroundPath;

            $updateBackground = file_put_contents($filePath, $background);

            convertImage($filePath, $global['systemRootPath'].$file, 70);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            if (file_exists($oldfile)) {
                unlink($oldfile);
            }

            if ($updateBackground) {
                $obj->background = 'Background has been updated!';
            } else {
                $obj->background = 'Error updating background.';
            }

            $this->setBackgroundURL($file);
        }

        // Update Profile Image
        if (isset($params['profileImg']) && $params['profileImg'] != '') {
            
            $photo = url_get_contents($params['profileImg']);
            $photoPath = "videos/userPhoto/photo{$id}.png";

            if (!isset($global['systemRootPath'])) {
                $global['systemRootPath'] = '../../';
            }

            $filePath = $global['systemRootPath'] . $photoPath;

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $updateProfile = file_put_contents($filePath, $photo);
            if ($updateProfile) {
                $obj->profile = 'Profile has been updated!';
            } else {
                $obj->profile = 'Error updating profile.';
            }
            
            $this->setPhotoURL($photoPath);
        }

        $formats = "ssi";
        $values[] = $this->photoURL;
        $values[] = $this->backgroundURL;
        $values[] = $this->id;

        $sql .= "UPDATE users SET "
                . "photoURL = ?, backgroundURL = ?, "
                . " modified = now() WHERE id = ?";
      
        $insert_row = sqlDAL::writeSql($sql, $formats, $values);
        $obj->save = $insert_row; // create/update data for photoURL / backgroundURL

        return $obj;
        
    }
}
