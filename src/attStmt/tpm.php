<?php
require_once dirname(__FILE__) . "/../../util/log.php";
require_once dirname(__FILE__) . "/../../util/cbor.php";
require_once dirname(__FILE__) . "/../../src/obj/algorithm.php";
require_once dirname(__FILE__) . "/attStmt.php";

//TPM is an abyss.
class Tpm extends attStmt
{
    public function verify($stmt, $authData, $hashedClientData, $rpId = null)
    {
        if (!array_key_exists("ver", $stmt)) {
            return "TPM ver alg is missing";
        }
        $ver = $stmt["ver"];
        if ($ver != 2) {
            return "TPM ver is unsupported";
        }

        if (!array_key_exists("alg", $stmt)) {
            return "TPM attStmt alg is missing";
        }
        $alg = $stmt["alg"];
        if (!is_numeric($alg)) {
            return "TPM attStmt alg format error";
        }

        if (array_key_exists("sig", $stmt)) {
            //TODO : TPMT_SIGNATURE format.   <- I can't understand it ....
            //cf. https://trustedcomputinggroup.org/wp-content/uploads/TPM-Rev-2.0-Part-2-Structures-01.38.pdf   section 11.3.4.
            $sig = $stmt["sig"];
        }

        if (!array_key_exists("x5c", $stmt)) {
            return "jws_x5c is missing";
        }
        $x5c = $stmt["x5c"];
        $x5cResult = self::checkX5c($x5c);
        if ($x5cResult !== null) {
            return $x5cResult;
        }
        $pemStr = self::convert2pemCert($x5c[0]);

        if (array_key_exists("ecdaaKeyId", $stmt)) {
            Log::write("*TPM attStmt ecdaaKeyId: " . $stmt["ecdaaKeyId"]);
            $ecdaaKeyId = $stmt["ecdaaKeyId"];
            //TODO : Unimplemented
        }

        if (!array_key_exists("certInfo", $stmt)) {
            return "tmp is missing certInfo";
        }
        $certInfo = self::parseCertInfo($stmt["certInfo"]);
        if (!is_array($certInfo)) {
            return $certInfo;
        }

        if (!array_key_exists("pubArea", $stmt)) {
            return "tmp is missing certInfo";
        }
        $pubArea = self::parsePubArea($stmt["pubArea"]);
        if (!is_array($pubArea)) {
            return $pubArea;
        }

        //check extraData
        $attToBeSigned = $authData->rawData . $hashedClientData;
        if ($certInfo["extraData"] !== hash(Algorithm::getHashAlgName($alg), $attToBeSigned, true)) {
            return "extraData does not match";
        }

        //check cert (TODO : these checks are defective.)
        $parsedCert = openssl_x509_parse($pemStr);
        Log::debugwrite("parsed TPM cert : " .  var_export($parsedCert, true)); 
        if (!empty($parsedCert["subject"] )){
            return "subject is not empty";
        }
        $certExtensions = $parsedCert["extensions"];
        if (strpos($certExtensions["extendedKeyUsage"], "2.23.133.8.3" ) === false){
            return "invalid extension key";
        }
        if (strpos($certExtensions["subjectAltName"], "2.23.133.2.1" ) === false){
            return "invalid extension key";
        }

        // verify $sig
        // TODO : Unimplemented

        //check unique
        $key = Cbor::decode($authData->credentialPublicKey);
        if ($key["1"] === 2) { //ec
            $keyFeature =  $key["-2"] . $key["-3"];
        } else if ($key["1"] === 3) { //rsa
            $keyFeature =  $key["-1"];
        }
        if ($pubArea["unique"] !== $keyFeature) {
            return "mismatch TPM unique value";
        }

        return null;
    }


    private  function parsePubArea($pubAreaByte)
    {
        $ret = array();
        $pt = 0;

        //type
        $typeLen = 2;
        $ret["type"] = substr($pubAreaByte, $pt, $typeLen);
        $pt += $typeLen;

        //nameAlg
        $nameAlgLen = 2;
        $ret["nameAlg"] = substr($pubAreaByte, $pt, $nameAlgLen);
        $pt += $nameAlgLen;

        //objectAttributes
        $objectAttributesLen = 4;
        $ret["objectAttributes"] = substr($pubAreaByte, $pt, $typeLen);
        $pt += $objectAttributesLen;

        //authPolicy
        $authPolicyLengthLen = 2;
        $authPolicyLength = substr($pubAreaByte, $pt, $authPolicyLengthLen);
        $array = array_values(unpack("n", $authPolicyLength));
        $authPolicyLength = (int) $array[0];
        $pt += $authPolicyLengthLen;

        $ret["authPolicy"] = substr($pubAreaByte, $pt, $authPolicyLength);
        $pt += $authPolicyLength;

        //parameters
        switch (strtolower(bin2hex($ret["type"]))) {
            case "0001": //TPM_ALG_RSA
                $parameterLen = 10;
                break;
            case "0023": //TPM_ALG_ECC
                $parameterLen = 8;
                break;
            default:
                return "invalid key type"; //error
        }

        $ret["parameters"] = substr($pubAreaByte, $pt, $parameterLen);
        $pt += $parameterLen;

        //unique
        $uniqueLengthLen = 2;
        $uniqueLength = substr($pubAreaByte, $pt, $uniqueLengthLen);
        $array = array_values(unpack("n", $uniqueLength));
        $uniqueLength = (int) $array[0];
        $pt += $uniqueLengthLen;

        $ret["unique"] = substr($pubAreaByte, $pt, $uniqueLength);
        $pt += $uniqueLength;

        return $ret;
    }

    private  function parseCertInfo($certInfoByte)
    {
        $ret = array();
        $pt = 0;

        //magic
        $magicLen = 4;
        if (strtolower(bin2hex(substr($certInfoByte, $pt, $magicLen))) !== "ff544347") { // ff544347 : TPM_GENERATED
            return "invalid certInfo magic";
        }
        $pt += $magicLen;

        //type
        $typeLen = 2;
        if (strtolower(bin2hex(substr($certInfoByte, $pt, $typeLen))) !== "8017") { // 8017 : TPM_ST_ATTEST_CERTIFY
            return "invalid certInfo type";
        }
        $pt += $typeLen;

        //qualifiedSigner
        $qualifiedSignerLengthLen = 2;
        $qualifiedSignerLengt = substr($certInfoByte, $pt, $qualifiedSignerLengthLen);
        $array = array_values(unpack("n", $qualifiedSignerLengt));
        $qualifiedSignerLengt = (int) $array[0];
        $pt += $qualifiedSignerLengthLen;

        $ret["qualifiedSigner"] = substr($certInfoByte, $pt, $qualifiedSignerLengt);
        $pt += $qualifiedSignerLengt;

        //extraData
        $extraDataLengthLen = 2;
        $extraDataLength = substr($certInfoByte, $pt, $extraDataLengthLen);
        $array = array_values(unpack("n", $extraDataLength));
        $extraDataLength = (int) $array[0];
        $pt += $extraDataLengthLen;

        $ret["extraData"] = substr($certInfoByte, $pt, $extraDataLength);
        $pt += $extraDataLength;

        //clockInfo
        $clockInfoLen = 17;
        $ret["clockInfo"] = substr($certInfoByte, $pt, $clockInfoLen);
        $pt += $clockInfoLen;

        //firmwareVersion
        $firmwareVersionLen = 8;
        $ret["firmwareVersion"] = substr($certInfoByte, $pt, $firmwareVersionLen);
        $pt += $firmwareVersionLen;

        //attestedName
        $attestedNameLengthLen = 2;
        $attestedNameLength = substr($certInfoByte, $pt, $attestedNameLengthLen);
        $array = array_values(unpack("n", $attestedNameLength));
        $attestedNameLength = (int) $array[0];
        $pt += $attestedNameLengthLen;

        $ret["certInfo_attestedName"] = substr($certInfoByte, $pt, $attestedNameLength);
        $pt += $attestedNameLength;

        //attestedQualifiedName
        $attestedQualifiedNameLengthLen = 2;
        $attestedQualifiedNameLength = substr($certInfoByte, $pt, $attestedQualifiedNameLengthLen);
        $array = array_values(unpack("n", $attestedQualifiedNameLength));
        $attestedQualifiedNameLength = (int) $array[0];
        $pt += $attestedQualifiedNameLengthLen;

        $ret["certInfo_attestedQualifiedName"]  = substr($certInfoByte, $pt, $attestedQualifiedNameLength);
        $pt += $attestedQualifiedNameLength;

        return $ret;
    }
}
