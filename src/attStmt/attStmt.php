<?php

abstract class AttStmt
{
   abstract public function verify($stmt, $authData, $hashedClientData, $rpId = null);

   protected function convert2pemCert($bin)
   {
      return "-----BEGIN CERTIFICATE-----\n" .
         wordwrap(base64_encode($bin), 64, "\n", true) .
         "\n-----END CERTIFICATE-----";
   }

   protected function checkX5c(array $x5c)
   {
      if ($x5c === null || empty($x5c)) {
         return "packedAttStmt x5c is empty";
      }
      if (!is_array($x5c)) {
         return "packedAttStmt x5c is not array";
      }

      if (version_compare(phpversion(), "7.4", '>=')) { //TODO : Implemented independently because it is not supported version.
         // check certificate chain
         for ($i = 0; $i < count($x5c) - 1; $i++) {
            $verifyChainResult = openssl_x509_verify(self::convert2pemCert($x5c[$i]), self::convert2pemCert($x5c[$i + 1]));

            if ($verifyChainResult === 1) {
               //certificate cain verify OK !!
               Log::debugWrite("--- certificate chain verify ok! ---");
            } elseif ($verifyChainResult === 0) {
               return "invalid certificate chain";
            } else {
               return "certificate chain varification error";
            }
         }

         // check certificate expiration
         foreach ($x5c as $cer) {
            $parsedCert = openssl_x509_parse(self::convert2pemCert($cer));
            if (time() < $parsedCert["validFrom_time_t"] || $parsedCert["validTo_time_t"] < time()) {
               Log::write("Error : Certificate expiration is out of range\r\n" .
                  "validFrom_time_t : " . $parsedCert["validFrom_time_t"] . "\r\n" .
                  "validTo_time_t : " . $parsedCert["validTo_time_t"] . "\r\n" .
                  "now : " . time() . "\r\n" .
                  var_export($parsedCert, true) . "\r\n" .
                  bin2hex($cer) . "\r\n");
               return "Certificate expiration is out of range";
            }
         }
      }
      return null;
   }
}
