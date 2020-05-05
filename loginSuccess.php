<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebAuthn Sample<</title>
</head>
<body>
<?php

session_start();

if (isset($_GET["action"])) {
    unset($_SESSION);
    session_destroy();
    setCookie('PHPSESSID');

    header('Location: index.html');
    exit;
}

if (array_key_exists("loginUser", $_SESSION)) {
    echo "<h2>Authentication Successful !</h2>";
    $user = $_SESSION["loginUser"];

    echo "--- user info ---<br />";
    echo var_export($user, true);
    echo "<br /><br />";
    echo "<a href= \"loginSuccess.php?action=logout\" >Logout</a>";
    
} else {
    echo "<h2>Not Authenticated</h2>";
}

?>
</body>
</html>