<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../db/baseDao.php";

class UserDao extends BaseDao
{

    function __construct()
    {
        parent::__construct();
    }

    function getById($userId)
    {
        $sql = "select * from user where user_id = :userId";
        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    function add($userId, $userName, $displayName)
    {
        $sql = "insert into user (user_id, name, display_name, created_at) values (:userId, :userName, :displayName, :created_at)";
        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(":userId", $userId, PDO::PARAM_STR);
            $stmt->bindValue(":userName", $userName, PDO::PARAM_STR);
            $stmt->bindValue(":displayName", $displayName, PDO::PARAM_STR);
            $stmt->bindValue(":created_at", gmdate('Y-m-d H:i:s'), PDO::PARAM_STR);
            return  $stmt->execute();
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    public function deleteAll()
    {
        return parent::deleteAllData("user");
    }
}
