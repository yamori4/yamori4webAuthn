<?php

require_once dirname(__FILE__) . "/../../util/log.php";
require_once dirname(__FILE__) . "/../../util/cbor.php";
require_once dirname(__FILE__) . "/../../util/base64.php";
require_once dirname(__FILE__) . "/attStmt.php";

class U2f extends attStmt
{
    public function verify($stmt, $authData, $hashedClientData, $rpId = null)
    {
        //x5c
        if (array_key_exists("x5c", $stmt)) {
            $x5c = $stmt["x5c"];
            $x5cResult = self::checkX5c($x5c);
            if ($x5cResult !== null) {
                return $x5cResult;
            }
        }

        if ($stmt["sig"] === null || empty($stmt["sig"])) {
            return ("u2f sig is empty");
        }
        $sig = $stmt["sig"];

        //check aaguid
        if ($authData->aaguId !== "00000000000000000000000000000000") { //always 0
            return "invalid u2f aaguid";
        }

        $crePub = new CredentialPublicKey($authData->credentialPublicKey);
        $signTarget = hex2bin("00") . $authData->rpIdHash . $hashedClientData . $authData->credentialId . $crePub->getEccXY();

        $verifyResult = openssl_verify($signTarget, $sig, self::convert2pemCert($x5c[0]), "sha256");

        if ($verifyResult === 1) {
            //verify OK !!
            Log::debugWrite("--- U2F attStmt verify ok! ---");
        } elseif ($verifyResult === 0) {
            return "invalid u2f signature";
        } else {
            return "u2f varification error";
        }

        return null;
    }
}
