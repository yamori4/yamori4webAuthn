<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP WebAuthn Sample<</title> </head> <body>
            <?php
            require_once dirname(__FILE__) . "/db/WebAuthnDao.php";
            require_once dirname(__FILE__) . "/db/userDao.php";
            require_once dirname(__FILE__) . "/config/config.php";

            if (!Config::isTestMode()) {
                header('Location: ./index.html');
                exit;
            }

            $webAuthnDao = new WebAuthnDao();
            echo  $webAuthnDao->deleteAll() ? "WebAuthn data were deleted." : "WebAuthn data Data deletion was failed";
            echo "<br />";
            $userDao = new UserDao();
            echo $userDao->deleteAll() ? "User data were deleted." : "User data Data deletion was failed";
            echo "<br />";

            ?>
            </body>

</html>