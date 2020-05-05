<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../config/config.php";

abstract class BaseDao
{
    protected $dbh;

    function __construct()
    {
        try {
            $this->dbh = new PDO(Config::getDataSourceName(), Config::getDbUserName(), Config::getDbPassword());
        } catch (Exception $ex) {
            Log::write("DB Error : " . $ex->getMessage());
        }
    }

    protected function deleteAllData($tableName)
    {
        $sql = "delete from " . $tableName . " where 1";
        try {
            $stmt = $this->dbh->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }
}
