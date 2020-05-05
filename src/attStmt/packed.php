<?php
require_once dirname(__FILE__) . "/../../util/log.php";
require_once dirname(__FILE__) . "/../../src/obj/algorithm.php";
require_once dirname(__FILE__) . "/../../src/obj/credentialPublicKey.php";
require_once dirname(__FILE__) . "/attStmt.php";

class Packed extends attStmt
{

    public function verify($stmt, $authData, $hashedClientData, $rpId = null)
    {
        if (!is_array($stmt) || empty($stmt)) {
            return "packedAttStmt format error";
        }

        //alg
        if (!array_key_exists("alg", $stmt)) {
            return "packedAttStmt alg is missing";
        }
        if (!is_numeric($stmt["alg"])) {
            return "packedAttStmt alg format error";
        }
        $alg = $stmt["alg"];

        //sig
        if (!array_key_exists("sig", $stmt)) {
            return "packedAttStmt sig is missing";
        }

        if ($stmt["sig"] === null || empty($stmt["sig"])) {
            return "packedAttStmt sig is empty";
        }
        $sig = $stmt["sig"];

        //ecdaaKeyId
        if (array_key_exists("ecdaaKeyId", $stmt)) {
            Log::write("*Packed attStmt ecdaaKeyId: " . $stmt["ecdaaKeyId"]);
            $ecdaaKeyId = $stmt["ecdaaKeyId"];
            //TODO : Unimplemented
        }

        /*  ------- TODO  MetadataService is Unimplemented -------  */

        //x5c
        if (array_key_exists("x5c", $stmt)) {
            /* full packed */
            $x5c = $stmt["x5c"];
            $x5cResult = self::checkX5c($x5c);
            if ($x5cResult !== null) {
                return $x5cResult;
            }
            $pemStr =  self::convert2pemCert($x5c[0]);
        } else {
            /* surrogate packed */
            $crePub =  new CredentialPublicKey($authData->credentialPublicKey);
            $pemStr = $crePub->getPubKey();
        }

        $verifyResult = openssl_verify($authData->rawData . $hashedClientData, $sig, $pemStr, Algorithm::getHashAlgName($alg));
        if ($verifyResult === 1) {
            //verify OK !!
            Log::debugWrite("--- Packed attStmt verify ok! ---");
        } elseif ($verifyResult === 0) {
            return "invalid packed signature";
        } else {
            return "packed varification error";
        }

        return null;
    }
}
