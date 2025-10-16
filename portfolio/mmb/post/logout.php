<?php
require('../dbconnect.php');
ini_set('session.cookie_samesite', 'None'); // POSTでも送信可能に
ini_set('session.cookie_secure', 'Off');    // HTTPSじゃない場合はOff
ob_start();
session_start();

//セッション情報を削除
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"],$params["httponly"]
);
}

session_destroy();

//cookie情報も削除
setcookie('email', '', time()-3600);
setcookie('password', '', time()-3600);

header('Location: login.php');
exit();
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="style.css">
</head>
<body>
<main>

</div>
</main>
</body>
</html>