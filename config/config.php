<?php

class Config
{

    private static function getConfig($keyName)
    {
        $config =  parse_ini_file("config.ini");
        return $config[$keyName];
    }

    public static function isTestMode()
    {
        return self::getConfig("test_mode") == true;
    }

    public static function getRelyingParty()
    {
        return self::getConfig("relying_party");
    }

    public static function getDataSourceName()
    {
        return self::getConfig("db_data_source_name");
    }

    public static function getDbUserName()
    {
        return self::getConfig("db_user_name");
    }

    public static function getDbPassword()
    {
        return self::getConfig("db_password");
    }
}
