<?php

function object_to_array($obj) {
//only process if it's an object or array being passed to the function
    if (is_object($obj) || is_array($obj)) {
        $ret = (array) $obj;
        foreach ($ret as &$item) {
//recursively process EACH element regardless of type
            $item = object_to_array($item);
        }
        return $ret;
    }
//otherwise (i.e. for scalar values) return without modification
    else {
        return $obj;
    }
}

function TimeLogStart($name) {
    global $global;
    if (!empty($global['noDebug'])) {
        return false;
    }
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    if (empty($global['start']) || !is_array($global['start'])) {
        $global['start'] = array();
    }
    $global['start'][$name] = $time;
}

function TimeLogEnd($name, $line, $limit = 0.7) {
    global $global;
    if (!empty($global['noDebug'])) {
        return false;
    }
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    $finish = $time;
    $total_time = round(($finish - $global['start'][$name]), 4);
    if ($total_time > $limit) {
        _error_log("Warning: Slow process detected [{$name}] On  Line {$line} takes {$total_time} seconds to complete. {$_SERVER["SCRIPT_FILENAME"]}");
    }
    TimeLogStart($name);
}

function _session_start(Array $options = array()) {
    try {
        if (session_status() == PHP_SESSION_NONE) {
            return session_start($options);
        }
    } catch (Exception $exc) {
        _error_log("_session_start: " . $exc->getTraceAsString());
        return false;
    }
}

function _error_log($message, $type = 0) {
    global $global;
    if (!empty($global['noDebug']) && $type == 0) {
        return false;
    }
    $prefix = "AVideoLog::";
    switch ($type) {
        case 0:
            $prefix .= "DEBUG: ";
            break;
        case 1:
            $prefix .= "WARNING: ";
            break;
        case 2:
            $prefix .= "ERROR: ";
            break;
        case 3:
            $prefix .= "SECURITY: ";
            break;
    }
    error_log($prefix . $message);
}

class AVideoLog {

    static $DEBUG = 0;
    static $WARNING = 1;
    static $ERROR = 2;
    static $SECURITY = 3;

}

function __($str, $allowHTML = false) {
    return $str;
}

function url_get_contents($url, $ctx = "", $timeout = 0, $debug = false) {
    global $global, $mysqlHost, $mysqlUser, $mysqlPass, $mysqlDatabase, $mysqlPort;
    if ($debug) {
        _error_log("url_get_contents: Start $url, $ctx, $timeout");
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {

        $session = $_SESSION;
        session_write_close();
        if (!empty($timeout)) {
            if ($debug) {
                _error_log("url_get_contents: no timout {$url}");
            }
            ini_set('default_socket_timeout', $timeout);
        }
        @$global['mysqli']->close();
    }

    if (empty($ctx)) {
        $opts = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true,
            ),
        );
        if (!empty($timeout)) {
            ini_set('default_socket_timeout', $timeout);
            $opts['http'] = array('timeout' => $timeout);
        }
        $context = stream_context_create($opts);
    } else {
        $context = $ctx;
    }
    if (ini_get('allow_url_fopen')) {
        if ($debug) {
            _error_log("url_get_contents: allow_url_fopen {$url}");
        }
        try {
            $tmp = @file_get_contents($url, false, $context);
            if ($tmp != false) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    _session_start();
                    $_SESSION = $session;
                    _mysql_connect();
                }
                if ($debug) {
                    _error_log("url_get_contents: SUCCESS file_get_contents($url) ");
                }
                return remove_utf8_bom($tmp);
            }
            if ($debug) {
                _error_log("url_get_contents: ERROR file_get_contents($url) ");
            }
        } catch (ErrorException $e) {
            if ($debug) {
                _error_log("url_get_contents: allow_url_fopen ERROR " . $e->getMessage() . "  {$url}");
            }
            return "url_get_contents: " . $e->getMessage();
        }
    } else if (function_exists('curl_init')) {
        if ($debug) {
            _error_log("url_get_contents: CURL  {$url} ");
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if (!empty($timeout)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout + 10);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            _session_start();
            $_SESSION = $session;
            _mysql_connect();
        }
        if ($debug) {
            _error_log("url_get_contents: CURL SUCCESS {$url}");
        }
        return remove_utf8_bom($output);
    }
    if ($debug) {
        _error_log("url_get_contents: Nothing yet  {$url}");
    }

    // try wget
    $filename = getTmpDir("YPTurl_get_contents") . md5($url);
    if ($debug) {
        _error_log("url_get_contents: try wget $filename {$url}");
    }
    if (wget($url, $filename, $debug)) {
        if ($debug) {
            _error_log("url_get_contents: wget success {$url} ");
        }
        $result = file_get_contents($filename);
        unlink($filename);
        if (!empty($result)) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                _session_start();
                $_SESSION = $session;
                _mysql_connect();
            }
            return remove_utf8_bom($result);
        }
    } else if ($debug) {
        _error_log("url_get_contents: try wget fail {$url}");
    }

    $result = @file_get_contents($url, false, $context);
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        _session_start();
        $_SESSION = $session;
        _mysql_connect();
    }
    if ($debug) {
        _error_log("url_get_contents: Last try  {$url}");
    }
    return remove_utf8_bom($result);
}

function remove_utf8_bom($text) {
    if (strlen($text) > 1000000) {
        return $text;
    }

    $bom = pack('H*', 'EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

function _isWritable($dir) {
    if (!isWritable($dir)) {
        return false;
    }
    $tmpFile = "{$dir}" . uniqid();
    $bytes = @file_put_contents($tmpFile, time());
    @unlink($tmpFile);
    return !empty($bytes);
}

// due the some OS gives a fake is_writable response
function isWritable($dir) {
    $dir = rtrim($dir, '/') . '/';
    $file = $dir . uniqid();
    $result = false;
    $time = time();
    if (@file_put_contents($file, $time)) {
        if ($fileTime = @file_get_contents($file)) {
            if ($fileTime == $time) {
                $result = true;
            }
        }
    }
    @unlink($file);
    return $result;
}

function wget($url, $filename, $debug = false) {
    if (empty($url) || $url == "php://input" || !preg_match("/^http/", $url)) {
        return false;
    }
    if (wgetIsLocked($url)) {
        if ($debug) {
            _error_log("wget: ERROR the url is already downloading $url, $filename");
        }
        return false;
    }
    wgetLock($url);
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $content = file_get_contents($url);
        if (!empty($content) && file_put_contents($filename, $content) > 100) {
            wgetRemoveLock($url);
            return true;
        }
        wgetRemoveLock($url);
        return false;
    }
    $cmd = "wget --tries=1 {$url} -O {$filename} --no-check-certificate";
    if ($debug) {
        _error_log("wget Start ({$cmd}) ");
    }
    //echo $cmd;
    exec($cmd);
    wgetRemoveLock($url);
    if (!file_exists($filename)) {
        _error_log("wget: ERROR the url does not download $url, $filename");
        return false;
    }
    if (empty(filesize($filename))) {
        _error_log("wget: ERROR the url download but is empty $url, $filename");
        return true;
    }
    return false;
}

function wgetLockFile($url) {
    return getTmpDir("YPTWget") . md5($url) . ".lock";
}

function wgetLock($url) {
    $file = wgetLockFile($url);
    return file_put_contents($file, time() . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function wgetRemoveLock($url) {
    $filename = wgetLockFile($url);
    if (!file_exists($filename)) {
        return false;
    }
    return unlink($filename);
}

function wgetIsLocked($url) {
    $filename = wgetLockFile($url);
    if (!file_exists($filename)) {
        return false;
    }
    $time = intval(file_get_contents($filename));
    if (time() - $time > 36000) { // more then 10 hours
        unlink($filename);
        return false;
    }
    return true;
}

function _mysql_connect() {
    global $global, $mysqlHost, $mysqlUser, $mysqlPass, $mysqlDatabase, $mysqlPort;
    if (is_object($global['mysqli']) && empty(@$global['mysqli']->ping())) {
        try {
            $global['mysqli'] = new mysqli($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDatabase, @$mysqlPort);
            if (!empty($global['mysqli_charset'])) {
                $global['mysqli']->set_charset($global['mysqli_charset']);
            }
        } catch (Exception $exc) {
            _error_log($exc->getTraceAsString());
            return false;
        }
    }
}

function _mysql_close() {
    global $global;
    if (is_object($global['mysqli']) && !empty(@$global['mysqli']->ping())) {
        @$global['mysqli']->close();
    }
}

function getAdvancedCustom() {
    global $global;
    $name = "getAdvancedCustom";
    $getAdvancedCustom = ObjectYPT::getCache($name, 3600);
    if (empty($getAdvancedCustom)) {
        _error_log("getAdvancedCustom: request a new one");
        $getAdvancedCustom = json_decode(url_get_contents("{$global['webSiteRootURL']}plugin/CustomizeAdvanced/advancedCustom.json.php"));
        ObjectYPT::setCache($name, $getAdvancedCustom);
    }
    return $getAdvancedCustom;
}

function getCustomizeUser() {
    global $global;
    $name = "getCustomizeUser";
    $getCustomizeUser = ObjectYPT::getCache($name, 3600);
    if (empty($getCustomizeUser)) {
        _error_log("getCustomizeUser: request a new one");
        $getCustomizeUser = json_decode(url_get_contents("{$global['webSiteRootURL']}plugin/CustomizeUser/customizeUser.json.php"));
        ObjectYPT::setCache($name, $getCustomizeUser);
    }
    return $getCustomizeUser;
}

function xss_esc($text) {
    if (empty($text)) {
        return "";
    }
    $result = @htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    if (empty($result)) {
        $result = str_replace(array('"', "'", "\\"), array("", "", ""), strip_tags($text));
    }
    return $result;
}

function xss_esc_back($text) {
    $text = htmlspecialchars_decode($text, ENT_QUOTES);
    $text = str_replace(array('&amp;', '&#039;', "#039;"), array(" ", "`", "`"), $text);
    return $text;
}

function getCurrentPage() {
    if (!empty($_REQUEST['current'])) {
        return intval($_REQUEST['current']);
    } else if (!empty($_POST['current'])) {
        return intval($_POST['current']);
    } else if (!empty($_GET['current'])) {
        return intval($_GET['current']);
    }
    return 1;
}

function getRowCount($default = 1000) {
    if (!empty($_REQUEST['rowCount'])) {
        return intval($_REQUEST['rowCount']);
    } else if (!empty($_POST['rowCount'])) {
        return intval($_POST['rowCount']);
    } else if (!empty($_GET['rowCount'])) {
        return intval($_GET['rowCount']);
    } else if (!empty($_REQUEST['length'])) {
        return intval($_REQUEST['length']);
    } else if (!empty($_POST['length'])) {
        return intval($_POST['length']);
    } else if (!empty($_GET['length'])) {
        return intval($_GET['length']);
    }
    return $default;
}

function make_path($path) {
    if (substr($path, -1) !== '/') {
        $path = pathinfo($path, PATHINFO_DIRNAME);
    }
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

function humanTiming($time, $precision = 0) {
    if (!is_int($time)) {
        $time = strtotime($time);
    }
    $time = time() - $time; // to get the time since that moment
    return secondsToHumanTiming($time, $precision);
}

function secondsToHumanTiming($time, $precision = 0) {
    $time = ($time < 0) ? $time * -1 : $time;
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second',
    );

    /**
     * For detection propouse only
     */
    __('year');
    __('month');
    __('week');
    __('day');
    __('hour');
    __('minute');
    __('second');
    __('years');
    __('months');
    __('weeks');
    __('days');
    __('hours');
    __('minutes');
    __('seconds');

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }

        $numberOfUnits = floor($time / $unit);
        if ($numberOfUnits > 1) {
            $text = __($text . "s");
        } else {
            $text = __($text);
        }

        if ($precision) {
            $rest = $time % $unit;
            if ($rest) {
                $text .= ' ' . secondsToHumanTiming($rest, $precision - 1);
            }
        }

        return $numberOfUnits . ' ' . $text;
    }
}

function getAPI($APIName, $parameters) {
    $name = "getAPI_" . md5($APIName . $parameters);
    $api = ObjectYPT::getCache($name, 60);
    if (empty($api)) {
        if (isLock($name)) {
            sleep(1);
            return getAPI($APIName, $parameters);
        }
        setLock($name);
        global $global, $APISecret;
        $json = url_get_contents("{$global['webSiteRootURL']}plugin/API/get.json.php?APIName={$APIName}&APISecret={$APISecret}&{$parameters}");

        removeLock($name);
        if (!empty($json)) {
            $json = json_decode($json);
            if (empty($json) || $json->error) {
                _error_log("Error on getAPI($APIName, $parameters)({$global['webSiteRootURL']}plugin/API/get.json.php?APIName={$APIName}&APISecret={$APISecret}&{$parameters}) " . json_encode($json));
                return array();
            }
            if (empty($json->response)) {
                return array();
            }
            ObjectYPT::setCache($name, $json->response);
            return $json->response;
        } else {
            _error_log("Error on getAPI($APIName, $parameters)({$global['webSiteRootURL']}plugin/API/get.json.php?APIName={$APIName}&APISecret={$APISecret}&{$parameters})");
            return false;
        }
    }
    return $api;
}

function getSelfURI() {
    if (empty($_SERVER['PHP_SELF'])) {
        return "";
    }
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]?$_SERVER[QUERY_STRING]";
}

function im_resize_max_size($file_src, $file_dest, $max_width, $max_height) {
    $fn = $file_src;
    $tmpFile = getTmpFile() . ".jpg";
    if (empty($fn)) {
        _error_log("im_resize_max_size: file name is empty, Destination: {$file_dest}", AVideoLog::$ERROR);
        return false;
    }
    if (function_exists("exif_read_data")) {
        error_log($fn);
        convertImage($fn, $tmpFile, 100);
        $exif = exif_read_data($tmpFile);
        if ($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
            if ($orientation != 1) {
                $img = imagecreatefromjpeg($tmpFile);
                $deg = 0;
                switch ($orientation) {
                    case 3:
                        $deg = 180;
                        break;
                    case 6:
                        $deg = 270;
                        break;
                    case 8:
                        $deg = 90;
                        break;
                }
                if ($deg) {
                    $img = imagerotate($img, $deg, 0);
                }
                imagejpeg($img, $fn, 100);
            }
        }
    } else {
        _error_log("Make sure you install the php_mbstring and php_exif to be able to rotate images");
    }

    $size = getimagesize($fn);
    $ratio = $size[0] / $size[1]; // width/height
    if ($size[0] <= $max_width && $size[1] <= $max_height) {
        $width = $size[0];
        $height = $size[1];
    } else
    if ($ratio > 1) {
        $width = $max_width;
        $height = $max_height / $ratio;
    } else {
        $width = $max_width * $ratio;
        $height = $max_height;
    }

    $src = imagecreatefromstring(file_get_contents($fn));
    $dst = imagecreatetruecolor($width, $height);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
    imagedestroy($src);
    imagejpeg($dst, $file_dest); // adjust format as needed
    imagedestroy($dst);
    @unlink($file_src);
    @unlink($tmpFile);
}

function convertImage($originalImage, $outputImage, $quality) {
    $imagetype = 0;
    if (function_exists('exif_imagetype')) {
        $imagetype = exif_imagetype($originalImage);
    }

    // jpg, png, gif or bmp?
    $exploded = explode('.', $originalImage);
    $ext = $exploded[count($exploded) - 1];

    if ($imagetype == IMAGETYPE_JPEG || preg_match('/jpg|jpeg/i', $ext))
        $imageTmp = imagecreatefromjpeg($originalImage);
    else if ($imagetype == IMAGETYPE_PNG || preg_match('/png/i', $ext))
        $imageTmp = imagecreatefrompng($originalImage);
    else if ($imagetype == IMAGETYPE_GIF || preg_match('/gif/i', $ext))
        $imageTmp = imagecreatefromgif($originalImage);
    else if ($imagetype == IMAGETYPE_BMP || preg_match('/bmp/i', $ext))
        $imageTmp = imagecreatefrombmp($originalImage);
    else if ($imagetype == IMAGETYPE_WEBP || preg_match('/webp/i', $ext))
        $imageTmp = imagecreatefromwebp($originalImage);
    else {
        _error_log("convertImage: File Extension not found ($originalImage, $outputImage, $quality) " . exif_imagetype($originalImage));
        return 0;
    }
    // quality is a value from 0 (worst) to 100 (best)
    imagejpeg($imageTmp, $outputImage, $quality);
    imagedestroy($imageTmp);

    return 1;
}

function getTmpFile() {
    return getTmpDir("tmpFiles") . uniqid();
}

function getTmpDir($subdir = "") {
    global $global;
    if (empty($_SESSION['getTmpDir'])) {
        $_SESSION['getTmpDir'] = array();
    }
    if (empty($_SESSION['getTmpDir'][$subdir . "_"])) {
        $tmpDir = sys_get_temp_dir();
        if (empty($tmpDir) || !_isWritable($tmpDir)) {
            $tmpDir = "{$global['systemRootPath']}videos/cache/";
        }
        $tmpDir = rtrim($tmpDir, '/') . '/';
        $tmpDir = "{$tmpDir}{$subdir}";
        $tmpDir = rtrim($tmpDir, '/') . '/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        _session_start();
        $_SESSION['getTmpDir'][$subdir . "_"] = $tmpDir;
    } else {
        $tmpDir = $_SESSION['getTmpDir'][$subdir . "_"];
    }
    return $tmpDir;
}

function getLockFile($name) {
    return getTmpDir("YPTLockFile") . md5($name) . ".lock";
}

function setLock($name) {
    $file = getLockFile($name);
    return file_put_contents($file, time());
}

function isLock($name, $timeout = 60) {
    $file = getLockFile($name);
    if (file_exists($file)) {
        $time = intval(file_get_contents($file));
        if ($time + $timeout < time()) {
            return false;
        }
    }
}

function removeLock($name) {
    $filename = getLockFile($name);
    if (!file_exists($filename)) {
        return false;
    }
    return unlink($filename);
}

function textToLink($string, $targetBlank = false) {
    $target = "";
    if ($targetBlank) {
        $target = "target=\"_blank\"";
    }

    return preg_replace(
            "~[[:alpha:]]+://[^<>[:space:]'\"]+[[:alnum:]/]~", "<a href=\"\\0\" {$target} >\\0</a>", $string
    );
}
