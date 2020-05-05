<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../db/baseDao.php";

class WebAuthnDao extends BaseDao
{
    function __construct()
    {
        parent::__construct();
    }

    public function add($userTableId, $type, $fmt, $counter, $aaguId, $credentailId, $credentialPublicKey ,$attestationObject)
    {
        $sql = <<<EOM
insert into web_authn
 (user_table_id, type, fmt, counter, aagu_id, credential_id, credential_public_key, attestation_object, created_at, updated_at)
 values
 (:userTableId, :type, :fmt, :counter, :aaguId, :credentailId, :credentialPublicKey, :attestationObject, :createdAt, :upatedAt)
EOM;

        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(":userTableId", $userTableId, PDO::PARAM_INT);
            $stmt->bindValue(":type", $type, PDO::PARAM_STR);
            $stmt->bindValue(":fmt", $fmt, PDO::PARAM_STR);
            $stmt->bindValue(":counter", $counter, PDO::PARAM_INT);
            $stmt->bindValue(":aaguId", $aaguId, PDO::PARAM_STR);
            $stmt->bindValue(":credentailId", $credentailId, PDO::PARAM_STR);
            $stmt->bindValue(":credentialPublicKey", bin2hex($credentialPublicKey), PDO::PARAM_STR);
            $stmt->bindValue(":attestationObject", $attestationObject, PDO::PARAM_STR);
            $stmt->bindValue(":createdAt", gmdate('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(":upatedAt", gmdate('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    public function getAllowCredentialsByUserTableId($userTableId)
    {
        $sql = "select type, credential_id from web_authn where user_table_id = :userTableId";

        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(":userTableId", $userTableId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    public function getCredentialIdsByUserTableId($userTableId)
    {
        $sql = "select credential_id from web_authn where user_table_id = :userTableId";

        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(":userTableId", $userTableId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    public function getByCredentialId($credentialId)
    {
        $sql = "select * from web_authn where credential_id = :credentialId";

        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(":credentialId", $credentialId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    public function updateCounter($id, $counter)
    {
        $sql = "update web_authn set counter = :counter, updated_at = :updatedAt where id = :id";

        try {
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $this->dbh->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->bindValue(":counter", $counter, PDO::PARAM_INT);
            $stmt->bindValue(":updatedAt", gmdate('Y-m-d H:i:s'), PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            Log::write('DB Error : ' . $e->getMessage());
            die();
        }
    }

    public function deleteAll()
    {
        return parent::deleteAllData("web_authn");
    }
}
