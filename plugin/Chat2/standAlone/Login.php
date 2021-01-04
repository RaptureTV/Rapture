<?php

if (empty($global['systemRootPath'])) {
    require_once '../standAlone/standAloneConfigurations.php';
}

class Login {

    static function run($user, $pass, $encodedPass = false) {
        global $global;
        $webSiteRootURL = $global['webSiteRootURL'];
        if (substr($webSiteRootURL, -1) !== '/') {
            $webSiteRootURL .= "/";
        }

        $postdata = http_build_query(
                array(
                    'user' => $user,
                    'pass' => $pass,
                    'encodedPass' => $encodedPass
                )
        );

        $opts = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);

        $result = file_get_contents($webSiteRootURL . 'login', false, $context);
        $result = remove_utf8_bom($result);
        if (empty($result)) {
            $object = new stdClass();
            $object->isLogged = false;
            $object->isAdmin = false;
            $object->canUpload = false;
            $object->canComment = false;
        } else {
            $object = json_decode($result);
            if (!empty($object->isLogged)) {
                User::createUser($object);
            }
        }
        _session_start();
        $_SESSION['login'] = $object;
        return $object;
    }

    static function logoff() {
        _session_start();
        unset($_SESSION['login']);
    }

    static function isLogged() { 
        return !empty($_SESSION['login']->isLogged);
    }

    static function isAdmin() {
        return !empty($_SESSION['login']->isAdmin);
    }

    static function canUpload() {
        return !empty($_SESSION['login']->canUpload);
    }

    static function canComment() {
        return !empty($_SESSION['login']->canComment);
    }

}
