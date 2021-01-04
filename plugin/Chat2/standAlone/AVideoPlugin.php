<?php


class AVideoPlugin {

    static function getObjectData($name) {
        global $global;
        $nameCache = "plugin_parameters_plugin_name={$name}";
        $obj = ObjectYPT::getCache($nameCache, 3600);
        if(empty($obj) || !is_object($obj)){
            $json = getAPI("plugin_parameters", "plugin_name={$name}");     
            if(!is_object($json)){
                _error_log("getObjectData($name) is not an object: ".json_encode($json));
            }else{
                $obj = $json->response;
                ObjectYPT::setCache($nameCache, $obj);
            }
        }
        if(empty($obj->onlineSecondsTolerance)){
            _error_log("getObjectData($name) onlineSecondsTolerance: ".json_encode($obj));
        }
        return $obj;
    }

    static function getObjectDataIfEnabled($name) {
        return self::getObjectData($name);
    }

    static function loadPluginIfEnabled($name) {
        return true;
    }

    public static function getHeadCode() {
        return "";
    }

    static function isEnabledByName($name) {
        return true;
    }

}