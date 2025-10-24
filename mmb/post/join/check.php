<?php
// Start session with proper cookie parameters for localhost/MAMP
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require(__DIR__ . '/../dbconnect.php');

// Redirect to input page if session data is missing
if (empty($_SESSION['join'])) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    // Insert member data into database
    $statement = $db->prepare('INSERT INTO members SET name=?, email=?, password=?, picture=?, created=NOW(), modified=NOW()');
    $statement->execute([
        $_SESSION['join']['name'],
        $_SESSION['join']['email'],
        sha1($_SESSION['join']['password']),
        $_SESSION['join']['image'] ?? ''
    ]);

    // Clear session data and redirect to thanks page
    unset($_SESSION['join']);
    header('Location: thanks.php');
    exit();
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="../style.css"> 
<title>登録内容確認</title>
</head>
<body>
<main>
<div class="contents">
<form action="check.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="submit" />
    <dl>
        <dt>ニックネーム</dt>
        <dd><?php echo htmlspecialchars($_SESSION['join']['name'], ENT_QUOTES, 'UTF-8'); ?></dd>
        <dt>メールアドレス</dt>
        <dd><?php echo htmlspecialchars($_SESSION['join']['email'], ENT_QUOTES, 'UTF-8'); ?></dd>
        <dt>パスワード</dt>
        <dd>【表示されません】</dd>
        <dt>写真など</dt>
        <dd>
            <?php 
            if (!empty($_SESSION['join']['image'])): 
                $imagePath = '../member_picture/' . $_SESSION['join']['image'];
                if (file_exists($imagePath)):
            ?>
                <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" width="100" height="100" alt="プロフィール画像" />
            <?php else: ?>
                <p>画像が存在しません。</p>
            <?php 
                endif;
            else: 
            ?>
                <p>画像は登録されていません。</p>
            <?php endif; ?>
        </dd>
    </dl>
    <div>
        <a href="index.php?action=rewrite">&laquo;&nbsp;書き直す</a> | 
        <button type="submit" class="btn-gradient-radius" value="check">登録する</button>
    </div>
</form>
</div>
</main>
</body>
</html>