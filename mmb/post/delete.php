<?php
require(__DIR__ . '/../dbconnect.php');
ini_set('session.cookie_samesite', 'None'); // POSTでも送信可能に
ini_set('session.cookie_secure', 'Off');    // HTTPSじゃない場合はOff
ob_start();
session_start();

$error = [];

if(isset($_SESSION['id'])) {
    $id = $_REQUEST['id'];

    // 投稿を検査する
    $messages = $db->prepare('SELECT * FROM posts WHERE id=?');
    $messages->execute(array($id));
    $message = $messages->fetch();

    if ($message['member_id'] == $_SESSION['id']) {
        // 削除する
        $del = $db->prepare('DELETE FROM posts WHERE id=?');
        $del->execute(array($id));
    }
}
header('Location: index.php'); exit();
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