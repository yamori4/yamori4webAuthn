<?php
require_once dirname(__FILE__) . "/../../util/log.php";

// cf. https://tools.ietf.org/html/rfc7517
// cf. https://www.iana.org/assignments/cose/cose.xhtml 
class CredentialPublicKey
{
    private $arr = null;
    private $pubKey = null;
    private $alg = null;
    private $eccFlag = false;
    private $rsaFlag = false;

    public function __construct($bin)
    {
        $this->arr = (array) Cbor::decode($bin);         

        switch ($this->arr["1"]) { //KTY (cf. https://www.iana.org/assignments/cose/cose.xhtml#key-type)
            case 2: //EC2
                if (count($this->arr) !==  5) {
                    Log::write("ECC key param error.");
                    return;
                }
                $this->eccFlag = true;
                $this->pubKey = self::getEccPubKey($this->arr);
                $this->alg = $this->arr["3"];
                break;
            case 3: //RSA
                if (count($this->arr) !==  4) {
                    Log::write("ESA key param error.");
                    return;
                }
                $this->rsaFlag = true;
                $this->pubKey = self::getRsaPubKey($this->arr);
                $this->alg = $this->arr["3"];
                break;
            case 0: //Reserved
            case 1: //OKP
            case 4: //Symmetric
            case 5: //HSS-LMS
            default:
                Log::write("unsupported KTY type : " & $this->arr["1"]);
        }
        return null;
    }


    public function isEcc()
    {
        return $this->eccFlag;
    }

    public function isRsa()
    {
        return $this->rsaFlag;
    }

    public function getAlg()
    {
        return $this->alg;
    }

    public function getPubKey()
    {
        return $this->pubKey;
    }


    // cf. https://www.rfc-editor.org/rfc/rfc4492.txt
    private function getEccPubKey(array $pubKey)
    {
        $bitString = "0004" . bin2hex($pubKey["-2"]) . bin2hex($pubKey["-3"]); // $pubKey["-2"] -> X , $pubKey["-3"] -> Y
        if (intdiv(strlen($bitString), 2) > 0xFF) {
            $bitString = "82" . sprintf('%04x', intdiv(strlen($bitString), 2)) . $bitString;
        } else if (intdiv(strlen($bitString), 2) > 0x7F) {
            $bitString = "81" . sprintf('%02x', intdiv(strlen($bitString), 2)) . $bitString;
        } else {
            $bitString = sprintf('%02x', intdiv(strlen($bitString), 2)) . $bitString;
        }
        $bitString  = "03" . $bitString;

        $oid = "06" . "07" . "2a8648ce3d0201";
        switch ((int) $pubKey["3"]) {
            case Algorithm::ES256:
                $oid .= "06" . "08" . "2a8648ce3d030107"; //P-256
                break;
            case Algorithm::ES384:
                $oid .= "06" . "05" . "2b81040022"; //P-384
                break;
            case Algorithm::ES512:
                $oid .= "06" . "05" . "2b81040023"; //P-512
                break;
            default:
                return null;
        }
        $oid = "30" . sprintf('%02x', intdiv(strlen($oid), 2)) . $oid;

        $pemStr = $oid . $bitString;
        if (intdiv(strlen($pemStr), 2) > 0xFF) {
            $pemStr = "82" . sprintf('%04x', intdiv(strlen($pemStr), 2)) . $pemStr;
        } else if (intdiv(strlen($pemStr), 2) > 0x7F) {
            $pemStr = "81" . sprintf('%02x', intdiv(strlen($pemStr), 2)) . $pemStr;
        } else {
            $pemStr = sprintf('%02x', intdiv(strlen($pemStr), 2)) . $pemStr;
        }
        $pemStr = "30" . $pemStr;

        $keyStr = "-----BEGIN PUBLIC KEY-----\n";
        $keyStr .=  wordwrap(base64_encode(hex2bin($pemStr)), 64, "\n", true);
        $keyStr .=  "\n-----END PUBLIC KEY-----";

        return $keyStr;
    }

    public function getEccXY()
    {
        if (count($this->arr) !== 5) {
            Log::write("ECC key param error.");
            return null;
        }
        return  hex2bin("04") . $this->arr["-2"] . $this->arr["-3"]; //$this->aarr["-2"] -> X , $this->aarr["-3"] -> Y
    }

    private function getRsaPubKey(array $pubKey)
    {
        //n
        $n = bin2hex($pubKey["-1"]);
        if (intdiv(strlen($n), 2) > 0xFF) {
            $n = "82" . sprintf('%04x', intdiv(strlen($n), 2)) . $n;
        } else if (intdiv(strlen($n), 2) > 0x7F) {
            $n = "81" . sprintf('%02x', intdiv(strlen($n), 2)) . $n;
        } else {
            $n = sprintf('%02x', intdiv(strlen($n), 2)) . $n;
        }
        $n = "02" . $n;

        //e
        $e = bin2hex($pubKey["-2"]);
        $e = "02" . sprintf('%02x', intdiv(strlen($e), 2)) . $e;


        $pemStr = $n . $e;
        if (strlen($pemStr) > 0xFF) {
            $pemStr = "82" . sprintf('%04x', intdiv(strlen($pemStr), 2)) . $pemStr;
        } else {
            $pemStr = sprintf('%02x', intdiv(strlen($pemStr), 2)) . $pemStr;
        }
        $pemStr = "30" . $pemStr;
        $pemStr = "00" . $pemStr;

        if (strlen($pemStr) > 0xFF) {
            $pemStr = "82" . sprintf('%04x', intdiv(strlen($pemStr), 2)) . $pemStr;
        } else {
            $pemStr = sprintf('%02x', intdiv(strlen($pemStr), 2)) . $pemStr;
        }
        $pemStr =  "03" . $pemStr;
        $pemStr = "30" . "0d" . "06092a864886f70d0101010500" . $pemStr;

        if (strlen($pemStr) > 0xFF) {
            $pemStr = "82" . sprintf('%04x', intdiv(strlen($pemStr), 2)) . $pemStr;
        } else {
            $pemStr = sprintf('%02x', intdiv(strlen($pemStr), 2)) . $pemStr;
        }
        $pemStr =  "30" . $pemStr;

        $keyStr = "-----BEGIN PUBLIC KEY-----\n";
        $keyStr .=  wordwrap(base64_encode(hex2bin($pemStr)), 64, "\n", true);
        $keyStr .=  "\n-----END PUBLIC KEY-----";

        return  $keyStr;
    }
}
