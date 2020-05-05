<?php
require_once dirname(__FILE__) . "/attStmt.php";

class None extends attStmt
{

  public function verify($stmt, $authData, $hashedClientData, $rpId = null)
  {
      if (!is_array($stmt) || empty($stmt)) {
          return null ;
      }

      //alg
      if (array_key_exists("alg", $stmt)) {
          $alg = $stmt["alg"];
      }

      //sig
      if (array_key_exists("sig", $stmt)) {
        $sig = $stmt["sig"];
      }

      //x5c
      if (array_key_exists("alg", $stmt) && array_key_exists("sig", $stmt)
        &&  (array_key_exists("x5c", $stmt) || array_key_exists("ecdaaKeyId", $stmt)))
       {
         return "invalid none attStmt format";
       }
       
      //surrogate packed
      $crePub =  new CredentialPublicKey($authData->credentialPublicKey);

      $verifyResult = openssl_verify($authData->rawData . $hashedClientData, $sig,  $crePub->getPubKey(), Algorithm::getHashAlgName($alg));
      if ($verifyResult === 1) {
          //verify OK !!
          Log::debugWrite("--- None attStmt verify ok! ---");
      } elseif ($verifyResult === 0) {
          return "invalid signature";
      } else {
          return "varification error";
      }

      return null;
  }

}