<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../util/cbor.php";
require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../db/webAuthnDao.php";
require_once dirname(__FILE__) . "/../src/obj/credentialPublicKey.php";
require_once dirname(__FILE__) . "/../src/obj/authenticatorData.php";
require_once dirname(__FILE__) . "/../src/obj/algorithm.php";
require_once dirname(__FILE__) . "/../src/base.php";

class AssertionResponse extends Base
{
    public function authenticate($authn)
    {
        //check credentialId 
        if (!array_key_exists("id", $authn) || Base64::websafeDecode($authn['id']) === false) {
            return self::retErr("invalid id");
        }

        //check public key credential type
        if ($authn["type"] !== "public-key") {
            return self::retErr("invalid publicKeyCredential ");
        }

        $response = (array) $authn["response"];
        $clientDataJSON = (array) json_decode(Base64::websafeDecode($response["clientDataJSON"]));

        //check clientDataType
        if ($clientDataJSON["type"] !== "webauthn.get") {
            return self::retErr("invalid clientDataType");
        }

        //check clientDataOrigin
        $url = parse_url($clientDataJSON["origin"]);
        if (!array_key_exists("host", $url) || $url["host"] !== self::getRpId()) {
            return self::retErr("mismatch clientDataOrigin");
        }

        //check clientDataTokenBinding
        if (array_key_exists("tokenBinding", $clientDataJSON)) {
            $tokenBinding = (array) $clientDataJSON["tokenBinding"];
            if (!array_key_exists("status", $tokenBinding)) {
                return self::retErr("invalid tokenBinding");
            }
            switch ($tokenBinding["status"]) {
                case "present":
                    //Unimplemented
                    break;
                case "supported":
                    //Unimplemented
                    break;
                case "not-supported":
                    //Unimplemented
                    break;
                default:
                    return self::retErr("invalid tokenBinding");
            }
        }

        //check challenge
        if (!self::verifyChallenge($clientDataJSON["challenge"])) {
            return self::retErr("mismatch challenge");
        }

        $authData = new AuthenticatorData();
        $authData->parse(Base64::websafeDecode($response['authenticatorData']));

        //check userVerification
        switch ($_SESSION["userVerification"]) {
            case "required":
                //user verification is required but not verified.
                if (!$authData->isUV) {
                    return self::retErr("user verification is required");
                }
                break;
            case "preferred":
            case "discouraged":
                //Do Nothing
                break;
            default:
                if (!$authData->isUP) {
                    return self::retErr("user present is false");
                }
        }

        //check rpIdHash
        if (bin2hex($authData->rpIdHash) !== hash("sha256", self::getRpId())) {
            return self::retErr("mismatch rpIdHash");
        }

        $webAuthnDao = new WebAuthnDao();
        $webAuthn = $webAuthnDao->getByCredentialId($authn["id"]);
        $webAuthn = $webAuthnDao->getByCredentialId($authn["rawId"]);

        //check counter
        if ($authData->counter !== 0 && $webAuthn["counter"] !== 0) {
            if ($authData->counter <= $webAuthn["counter"]) {
                return self::retErr("invalid signCount");
            }
        }

        // check userHandle
        if (is_bool($response["userHandle"]) || is_numeric($response["userHandle"]) || is_array($response["userHandle"])) {
            return self::retErr("invalid userHandle");
        }

        //check signature format
        if (!array_key_exists("signature", $response) || $response['signature'] === "" || Base64::websafeDecode($response['signature']) === false) {
            return self::retErr("invalid signature format");
        }

        //verify signature ( cf. https://tools.ietf.org/html/rfc7517 )
        $signed = Base64::websafeDecode($response["authenticatorData"]) . hash('sha256', Base64::websafeDecode($response["clientDataJSON"]), true);
        $crePub =  new CredentialPublicKey(hex2bin($webAuthn["credential_public_key"]));

        $verifyResult = openssl_verify($signed, Base64::websafeDecode($response["signature"]), $crePub->getPubKey(), Algorithm::getHashAlgName($crePub->getAlg()));
        if ($verifyResult === 1) {
            //verify OK !!
            Log::debugWrite("--- Verify OK! ---");
        } elseif ($verifyResult === 0) {
            return self::retErr("invalid signature");
        } else {
            return self::retErr("varification error");
        }

        // update key counter
        if (!$webAuthnDao->updateCounter($webAuthn["id"], $authData->counter)) {
            return self::retErr("db update error");
        }
        return array("status" => "ok", "errorMessage" => "");
    }
}
