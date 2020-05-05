<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../src/base.php";
require_once dirname(__FILE__) . "/../db/userDao.php";
require_once dirname(__FILE__) . "/../db/webAuthnDao.php";

class CredentialRequest extends Base
{
    public function compose($userId, $userName, $displayName, $authenticatorSelection, $attestation, $extensions)
    {
        $userDao = new UserDao();
        $user = $userDao->getById($userId);
        if (!$user) {
            //Create user if user does not exist
            if (!$userDao->add($userId, $userName, $displayName)) {
                return array("status" => "ng", "message" => "create user error");
            }
            $user = $userDao->getById($userId);
        }

        $_SESSION["user_table_id"] = $user["id"];

        $webAuthnDao = new WebAuthnDao();
        $excludeCredentials = array();
        foreach ($webAuthnDao->getAllowCredentialsByUserTableId($user["id"]) as $webAuthn) {
            array_push($excludeCredentials, array("type" => $webAuthn["type"], "id" => $webAuthn["credential_id"]));
        }

        $requestArr =  array(
            "status" => "ok",
            "errorMessage" => "",
            "challenge" => self::getChallenge(),
            "rp" => array(
                "name" => self::getRpName(),
                "id" => self::getRpId()
            ),
            "user" => array(
                "name" => $user["name"],
                "displayName" => $user["display_name"],
                "id" => $user["user_id"], //TODO
            ),
            "pubKeyCredParams" => array(
                array("type" => "public-key", "alg" => -7),
                array("type" => "public-key", "alg" => -8),
                array("type" => "public-key", "alg" => -35),
                array("type" => "public-key", "alg" => -36),
                array("type" => "public-key", "alg" => -38),
                array("type" => "public-key", "alg" => -37),
                array("type" => "public-key", "alg" => -39),
                array("type" => "public-key", "alg" => -257),
                array("type" => "public-key", "alg" => -258),
                array("type" => "public-key", "alg" => -259)
            ),
            "authenticatorSelection" => $authenticatorSelection,
            "excludeCredentials" => $excludeCredentials,
            "attestation" => $attestation,
            "timeout" => 60000,
            "extensions" => $extensions
        );

        return $requestArr;
    }
}
