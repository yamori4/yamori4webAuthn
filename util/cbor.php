<?php

interface iCbor
{
    public function encode($arr);
    public function decode($str);
}

require_once dirname(__FILE__) . "/../oss/CBOREncode/src/CBOR/CBOREncoder.php";
require_once dirname(__FILE__) . "/../oss/CBOREncode/src/CBOR/CBORExceptions.php";
require_once dirname(__FILE__) . "/../oss/CBOREncode/src/CBOR/Types/CBORByteString.php";
require_once dirname(__FILE__) . "/base64.php";

class Cbor
{
    public function encode($arr)
    {
        $encodedData = \CBOR\CBOREncoder::encode($arr);
        $byteArr = unpack("C*", $encodedData);
        $hexStr = implode(array_map(function ($byte) {
            strtoupper(dechex($byte));
        }, $byteArr));
        return Base64::websafeEncode($hexStr);
    }

    public function decode($bin)
    {
        $arr = (array) \CBOR\CBOREncoder::decode($bin);
        self::castByte($arr);
        return $arr;
    }


    public function getRest($bin)
    {
        $arr = (array) \CBOR\CBOREncoder::decode($bin);
        $encodedData = \CBOR\CBOREncoder::encode($arr);
        $byteArr = unpack("C*", $encodedData);
        $hexStr = implode(array_map(function ($byte) {
            strtoupper(dechex($byte));
        }, $byteArr));
        $len = strlen($hexStr);
        $rest =  substr($bin, $len, strlen($bin) - $len);
        return $rest;
    }


    private function castByte(&$arr)
    {
        foreach ($arr as &$elm) {
            if (is_object($elm)) {
                if (method_exists($elm, "get_byte_string")) {
                    $elm = (string) $elm->get_byte_string();
                }
            } else if (is_array($elm)) {
                self::castByte($elm);
            } else {
                //Do Nothing
            }
        }
    }
}
