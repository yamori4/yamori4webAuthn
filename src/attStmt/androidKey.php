<?php
require_once dirname(__FILE__) . "/../../util/log.php";
require_once dirname(__FILE__) . "/attStmt.php";

class AndroidKey extends attStmt
{
    //TODO : incomplete method
    public function verify($stmt, $authData, $hashedClientData, $rpId = null)
    {
        //alg
        if (!array_key_exists("alg", $stmt)) {
            return "androidKeyStmt alg is missing";
        }
        if (!is_numeric($stmt["alg"])) {
            return "androidKeyStmt alg format error";
        }
        $alg = $stmt["alg"]; //TODO : verify sig

        //sig
        if ($stmt["sig"] === null || empty($stmt["sig"])) {
            return ("androidKeyStmt sig is empty");
        }
        $sig = $stmt["sig"];

        //x5c
        if (array_key_exists("x5c", $stmt)) {
            $x5c = $stmt["x5c"];
            $x5cResult = self::checkX5c($x5c);
            if ($x5cResult !== null) {
                return $x5cResult;
            }
        }
        $pemStr = self::convert2pemCert($x5c[0]);

        // verify signature
        $verifyResult = openssl_verify($authData->rawData . $hashedClientData, $sig, $pemStr, Algorithm::getHashAlgName($alg));
        if ($verifyResult === 1) {
            //verify OK !!
            Log::debugWrite("--- androidKey attStmt verify ok! ---");
        } elseif ($verifyResult === 0) {
            return "invalid androidKey signature";
        } else {
            return "androidKey varification error";
        }

        return null;
    }
}
