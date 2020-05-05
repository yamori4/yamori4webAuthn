<?php
require_once dirname(__FILE__) . "/../util/log.php";
require_once dirname(__FILE__) . "/../config/config.php";
require_once dirname(__FILE__) . "/../db/userDao.php";
require_once dirname(__FILE__) . "/../src/assertionResponse.php";

session_start();

if (array_key_exists("authn", $_POST)) {
    $authn = (array) json_decode($_POST["authn"]);
} else {
    //Json input
    $authn = (array) json_decode(file_get_contents('php://input'));
    Log::debugWrite("( 4 ) AttestationOptionsJsonInput\r\n" . var_export($authn, true));
    if (!Config::isTestMode()) {
        header("Content-type: application/json; charset=utf-8");
        echo json_encode(array("status" => "ng", "message" => "Forbidden request."));
        return;
    }
}
Log::debugWrite("[ 4 ] AssertionResponse\r\n" . json_encode($authn, JSON_PRETTY_PRINT));
$result = AssertionResponse::authenticate($authn);

//login process (sample)
$userDao = new UserDao();
$user = $userDao->getById($_SESSION["loginId"]);
unset($_SESSION["loginId"]);
if ($user) {
    session_regenerate_id();
    $_SESSION['loginUser'] = $user;
} else {
    $result = array("status" => "ng", "message" => "User is not registered.");
}

if ($result["status"] !== "ok" && !Config::isTestMode()) { //Do not notify users detail error information
    Log::debugWrite("[ 4' ] AssertionResponse_Result
    \r\n" . json_encode($result, JSON_PRETTY_PRINT));
    $result = array("status" => "ng", "errorMessage" => "An error has occurred.");
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($result);
