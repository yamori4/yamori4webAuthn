<?php

class Config
{

    private function getConfig($keyName)
    {
        $config =  parse_ini_file("config.ini");
        return $config[$keyName];
    }

    public function isTestMode()
    {
        return (self::getConfig("test_mode") == true) ? true : false;
    }

    public function getRelyingParty()
    {
        return self::getConfig("relying_party");
    }

    public function getDataSourceName()
    {
        return self::getConfig("db_data_source_name");
    }

    public function getDbUserName()
    {
        return self::getConfig("db_user_name");
    }

    public function getDbPassword()
    {
        return self::getConfig("db_password");
    }
}
