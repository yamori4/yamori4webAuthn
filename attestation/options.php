<?php
require_once dirname(__FILE__) . "/../config/config.php";
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../util/base64.php";
require_once dirname(__FILE__) . "/../src/credentialRequest.php";

session_start();
session_regenerate_id();

if (array_key_exists("loginId", $_POST)) {
    $loginId = Base64::websafeEncode($_POST['loginId']);
    $userName = $_POST['loginId'];
    $displayName = $_POST['loginId'];

    $authenticatorSelection = array(
        // "authenticatorAttachment" => "cross-platform",     /* <- Uncomment when using */
        "requireResidentKey" => false,
        "userVerification" => "preferred"
    );
    $attestation = "direct";
    $extensions = array();
} else {
    //Json input
    $input = (array) json_decode(file_get_contents('php://input'));
    Log::debugWrite("(1) AttestationOptionsJsonInput\r\n" . var_export($input, true));
    if (!Config::isTestMode()) {
        header("Content-type: application/json; charset=utf-8");
        echo json_encode(array("status" => "ng", "message" => "Forbidden request."));
        return;
    }
    // Unimplemented
    $loginId = null;
    $userName = null;
    $displayName = null;
    $authenticatorSelection = null;
    $attestation = null;
    $extensions = null;
}

$request = CredentialRequest::compose($loginId, $userName, $displayName, $authenticatorSelection, $attestation, $extensions);
Log::debugWrite("[ 1 ] AttestationRequest\r\n" . json_encode($request, JSON_PRETTY_PRINT));

header("Content-type: application/json; charset=utf-8");
echo json_encode($request);
