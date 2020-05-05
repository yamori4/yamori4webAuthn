<?php
require_once dirname(__FILE__) . "/../config/config.php";
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../src/assertionRequest.php";

session_start();
session_regenerate_id();

if (array_key_exists("authnUsername", $_POST)) {
    $loginId =  Base64::websafeEncode($_POST["authnUsername"]);
    $userVerification = "preferred";
    $extensions  = array();
} else {
    //Json input
    $input = (array) json_decode(file_get_contents('php://input'));
    Log::debugWrite("(3) AssertionOptionsJsonInput\r\n" . var_export($input, true));
    if (!Config::isTestMode()) {
        header("Content-type: application/json; charset=utf-8");
        echo json_encode(array("status" => "ng","message" => "Forbidden request."));
        return;
    }
    // Unimplemented
    $loginId = null;
    $userVerification = null;
    $extensions = null;
}

$_SESSION["loginId"] = $loginId;
$request = AssertionRequest::get($loginId, $userVerification, $extensions);
Log::debugWrite("[ 3 ] AssertionRequest\r\n" . json_encode($request, JSON_PRETTY_PRINT));

header("Content-type: application/json; charset=utf-8");
echo json_encode($request);
