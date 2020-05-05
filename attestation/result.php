<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../config/config.php";
require_once dirname(__FILE__) . "/../src/credentialResponse.php";

session_start();

if (array_key_exists("register", $_POST)) {
    $register =  (array) json_decode($_POST["register"]);
} else {
    //Json input
    $register = (array) json_decode(file_get_contents('php://input'));
    Log::debugWrite("( 2 ) AttestationResultJsonInput\r\n" . var_export($register, true));
    if (!Config::isTestMode()) {
        header("Content-type: application/json; charset=utf-8");
        echo json_encode(array("status" => "ng", "message" => "Forbidden request."));
        return;
    }
}

Log::debugWrite("[ 2 ] AttestationResponse\r\n" . json_encode($register, JSON_PRETTY_PRINT));
$result = CredentialResponse::register($register);

if ($result["status"] !== "ok" && !Config::isTestMode()) {//Do not notify users detail error information
    Log::debugWrite("[ 2' ] AttestationResponse_Result\r\n" . json_encode($result, JSON_PRETTY_PRINT));
    $result = array("status" => "ng", "errorMessage" => "An error has occurred.");
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($result);
