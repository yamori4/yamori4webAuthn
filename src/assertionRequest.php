<?php

require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../src/base.php";
require_once dirname(__FILE__) . "/../db/userDao.php";
require_once dirname(__FILE__) . "/../db/webAuthnDao.php";

class AssertionRequest extends Base
{

    public function get($loginId, $userVerification, $extensions)
    {

        $userDao = new UserDao();
        $user = $userDao->getById($loginId);
        if (!$user) {
           return  array("status" => "ng","errorMessage" => "User is not registered.");
        }

        $webAuthnDao = new WebAuthnDao();
        $webAuthnArr = $webAuthnDao->getAllowCredentialsByUserTableId($user["id"]);

        $allowCredentails = array();
        foreach ($webAuthnArr as $webAuthn) {
            array_push($allowCredentails, array("type" =>  $webAuthn["type"], "id" =>  $webAuthn["credential_id"]));
        }

        $_SESSION["userVerification"] = $userVerification;

        $requestArr =  array(
            "status" => "ok",
            "errorMessage" => "",
            "challenge" => self::getChallenge(),
            "rpId" => self::getRpId(),
            "allowCredentials" => $allowCredentails,
            "userVerification" => $userVerification,
            "timeout" => 60000,
            "extensions" => $extensions
        );
        return $requestArr;
    }
}
