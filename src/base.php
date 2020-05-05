<?php

require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../config/config.php";

abstract class Base
{
    protected function getRpId()
    {
        return self::getRpName(); 
    }

    protected function getRpName()
    {
        if (Config::isTestMode()) {
            return $_SERVER["HTTP_HOST"]; 
        }
        return Config::getRelyingParty();
    }

    protected function getChallenge()
    {
        return Base64::websafeEncode(hash('sha256', session_id(), true));
    }

    protected function verifyChallenge($token)
    {
        return $token === self::getChallenge();
    }

    protected function retErr($msg){
        return array("status" => "ng", "errorMessage" => $msg);
    }
}
