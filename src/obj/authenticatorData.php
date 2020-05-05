<?php
require_once dirname(__FILE__) . "/../../util/log.php";
require_once dirname(__FILE__) . "/../../util/cbor.php";

class AuthenticatorData
{
    public $rawData;
    public $rpIdHash;

    public $flags;
    public $isED;
    public $isAT;
    public $isUV;
    public $isUP;

    public $counter;
    public $aaguId;
    public $credentialId;
    public $credentialPublicKey;

    public $extensionData;

    function parse($authData)
    {
        $this->rawData = $authData;

        //RP ID hash (32bytes)
        $this->rpIdHash = substr($authData, 0, 32);

        //FLAGS (1byte)
        $this->flags = substr($authData, 32, 1);

        $array = array_values(unpack("C", $this->flags));
        $flagsNum  = (int) $array[0];
        //bit flag -> ED,AT,0,0,0,UV,0,UP
        $this->isED = ($flagsNum & 0x80) === 0x80 ? true : false; //Extension data 
        $this->isAT = ($flagsNum & 0x40) === 0x40 ? true : false; //Attested credential data
        $this->isUV = ($flagsNum & 0x04) === 0x04 ? true : false; //User presence
        $this->isUP = ($flagsNum & 0x01) === 0x01 ? true : false; //User verification

        //COUNTER (4bytes)
        $counter = substr($authData, 33, 4);
        $array = array_values(unpack("N", $counter));
        $this->counter = (int) $array[0];

        if (strlen($authData) <= 37) {
            return;
        }
        $addition = substr($authData, 37, strlen($authData) - 37);
        $pt = 0;

        //ATTESTED CRED. DATA
        if ($this->isAT) {
            $this->aaguId = bin2hex(substr($addition, 0, 16));
            $credentialIdLength = substr($addition, 16, 2);
            $array = array_values(unpack("n", $credentialIdLength));
            $credentialIdLength = (int) $array[0];
            $this->credentialId = substr($addition, 18, $credentialIdLength);
            $this->credentialPublicKey = substr($addition, 18 + $credentialIdLength, strlen($addition) - (18 + $credentialIdLength));

            $rest = Cbor::getRest($this->credentialPublicKey); //TODO : This coding is not beautiful....  Umm....
            if ($rest !== false && $rest !== "") {
                if (!$this->isED) {
                    Log::write("credentialPublicKey contains extra data" . bin2hex($rest));
                    return "credentialPublicKey contains extra data";
                }
            }
        }

        //EXTENSIONS
        if ($this->isED) {
            if ($this->isAT) {
                $addition = $rest;
            }
            $this->extensionData = Cbor::decode(substr($addition, $pt, strlen($addition) - $pt));
        }
    }
}
