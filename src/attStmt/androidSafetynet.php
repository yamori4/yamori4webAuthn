<?php
require_once dirname(__FILE__) . "/../../util/log.php";
require_once dirname(__FILE__) . "/../../util/base64.php";
require_once dirname(__FILE__) . "/attStmt.php";

class AndroidSafetynet extends attStmt
{

    public function verify($stmt, $authData, $hashedClientData, $rpId = null)
    {
        if (!is_array($stmt) || empty($stmt)) {
            return "androidSafetynetAttStmt format error";
        }

        //var
        if (!array_key_exists("ver", $stmt)) {
            return ("androidSafetynetAttStmt ver is missing");
        }
        if ($stmt["ver"] === null || $stmt["ver"] === "") {
            return "androidSafetynetAttStmt ver is empty";
        }
        $var = $stmt["ver"];

        //response
        if (!array_key_exists("response", $stmt)) {
            return ("androidSafetynetAttStmt response is missing");
        }
        if ($stmt["response"] === null || $stmt["response"] === "") {
            return ("androidSafetynetAttStmt response is empty");
        }

        $jws =  $stmt["response"];
        $jws = explode(".", $jws);
        $headers = (array) json_decode(Base64::websafeDecode($jws[0]));
        $payload = (array) json_decode(Base64::websafeDecode($jws[1]));
        $signature =  Base64::websafeDecode($jws[2]);

        if (!array_key_exists("alg", $headers)) {
            return "alg is missing";
        }
        switch ($headers["alg"]) {
            case "SH256":
            case "RS256":
            default:
                $alg = "sha256";
        }

        if (!array_key_exists("x5c", $headers)) {
            return "jws_x5c is missing";
        }
        $x5c = array();
        foreach ($headers["x5c"] as $certStr) {
            array_push($x5c, Base64::websafeDecode($certStr));
        }
        $x5cResult = self::checkX5c($x5c);
        if ($x5cResult !== null) {
            return $x5cResult;
        }
        $pemStr = self::convert2pemCert($x5c[0]);

        //check nonce
        if (base64_decode($payload["nonce"]) !==  hash('sha256', $authData->rawData . $hashedClientData, true)) {
            return "nonce does not match";
        }

        //check ctsProfileMatch
        if (!$payload["ctsProfileMatch"]) {
            return "ctsProfileMatch is false";
        }
        //check timestampMs
        if (($payload["timestampMs"] < (time() - 60) * 1000) || (time() + 3) * 1000 < $payload["timestampMs"]) {
            return "timestampMs is out of range";
        }

        // check signatrue
        $verifyResult = openssl_verify($jws[0] . "." . $jws[1], $signature, $pemStr, $alg);
        if ($verifyResult === 1) {
            Log::debugWrite("--- Packed attStmt verify ok! ---");
        } elseif ($verifyResult === 0) {
            return "invalid androidSafetyNet signature";
        } else {
            return "androidSafetyNet varification error";
        }

        return null;
    }
}
