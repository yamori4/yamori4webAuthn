<?php

Class Base64{

    function websafeEncode($string) {
        $str = base64_encode($string);
        $str = str_replace(array('+','/','='),array('-','_',''),$str);
        return $str;
    }
    
    function websafeDecode($string) {
        $str = str_replace(array('-','_'),array('+','/'),$string);
        $mod = strlen($str) % 4;
        if ($mod !== 0) {
            $str .= substr('====', $mod);
        }
        return base64_decode($str, true);
    }
}


