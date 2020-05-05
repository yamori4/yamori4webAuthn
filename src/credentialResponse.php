<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../util/cbor.php";
require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../db/webAuthnDao.php";
require_once dirname(__FILE__) . "/../src/base.php";
require_once dirname(__FILE__) . "/../src/obj/authenticatorData.php";
require_once dirname(__FILE__) . "/../src/attStmt/attStmt.php";
require_once dirname(__FILE__) . "/../src/attStmt/packed.php";
require_once dirname(__FILE__) . "/../src/attStmt/tpm.php";
require_once dirname(__FILE__) . "/../src/attStmt/u2f.php";
require_once dirname(__FILE__) . "/../src/attStmt/androidSafetynet.php";
require_once dirname(__FILE__) . "/../src/attStmt/androidKey.php";
require_once dirname(__FILE__) . "/../src/attStmt/none.php";

class CredentialResponse extends Base
{

    public function register($register)
    {
        if (!array_key_exists("id", $register) || Base64::websafeDecode($register['id']) === false || is_bool($register['id'])) {
            return self::retErr("invalid id");
        }

        if (!array_key_exists("response", $register)) {
            return self::retErr("missing response");
        }
        $response = (array) $register['response'];
        if (!array_key_exists("attestationObject", $response) || $response['attestationObject'] === "") {
            return array("status" => "ng", "message" => "invalid attestationObject");
            return self::retErr("invalid response");
        }

        //check public key credential type
        if ($register["type"] !== "public-key") {
            return self::retErr("invalid publicKeyCredential");
        }

        //check clientDataType
        $clientDataJSON = (array) json_decode(Base64::websafeDecode($response['clientDataJSON']));
        if ($clientDataJSON["type"] !== "webauthn.create") {
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
                    //TODO Unimplemented
                    break;
                case "supported":
                    //TODO Unimplemented
                    break;
                case "not-supported":
                    //TODO Unimplemented
                    break;
                default:
                    return self::retErr("invalid tokenBinding");
            }
        }

        //check challenge
        if (!self::verifyChallenge($clientDataJSON["challenge"])) {
            return self::retErr("mismatch challenge");
        }

        $attestationObjectArray = Cbor::decode(Base64::websafeDecode($response['attestationObject']));

        //check autData
        $authData = new AuthenticatorData();
        $authDataResult = $authData->parse($attestationObjectArray['authData']);
        if ($authDataResult !== null) {
            return self::retErr($authDataResult);
        }

        //check credentialId
        if (Base64::websafeDecode($register['rawId']) !== $authData->credentialId) {
            return self::retErr("mismatch credentialId");
        }

        //check rpIdHash
        if ($authData->rpIdHash !== hash("sha256", utf8_encode(self::getRpId()), true)) {
            return "mismatch rpIdHash";
        }

        //do different processing for each fmt
        if (!array_key_exists("fmt", $attestationObjectArray)) {
            return self::retErr("invalid fmt");
        }
        switch ($attestationObjectArray["fmt"]) {
            case "packed":
                $attStmt = new Packed();
                break;
            case "fido-u2f":
                $attStmt = new U2f();
                break;
            case "tpm":
                $attStmt = new Tpm();
                break;
            case "android-safetynet":
                $attStmt = new AndroidSafetynet();
                break;
            case "android-key":
                $attStmt = new AndroidKey();
                break;
            case "none":
                $attStmt = new None();
                break;
            default:
                return self::retErr("invalid fmt");
        }
        if (!array_key_exists("attStmt", $attestationObjectArray)) {
            return self::retErr("invalid attStmt");
        }
        $hashedClientData = hash('sha256', utf8_encode(Base64::websafeDecode($response['clientDataJSON'])), true);
        $attStmtResult = $attStmt->verify(
            $attestationObjectArray['attStmt'],
            $authData,
            $hashedClientData,
            self::getRpId()
        );
        if ($attStmtResult !== null) {
            return self::retErr($attStmtResult);
        }

        //check duplicate registration
        $webAuthnDao = new WebAuthnDao();
        $encodedCredentialId = Base64::websafeEncode($authData->credentialId);
        foreach ($webAuthnDao->getCredentialIdsByUserTableId($_SESSION["user_table_id"]) as $credential) {
            if ($credential === $encodedCredentialId) {
                return self::retErr("This key is already registered.");
            }
        }

        //register webAuthn key
        $webAuthnDao->add(
            $_SESSION["user_table_id"],
            $register['type'],
            $attestationObjectArray['fmt'],
            $authData->counter,
            $authData->aaguId,
            $encodedCredentialId,
            $authData->credentialPublicKey,
            $response['attestationObject'] //Save it in case you need the information of the authenticator in the future.
        );

        return array("status" => "ok", "errorMessage" => "");
    }
}
