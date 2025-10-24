<?php
require(__DIR__ . '../../dbconnect.php');
ini_set('session.cookie_samesite', 'Lax'); // POSTでも送信可能に
ini_set('session.cookie_secure', 'Off');    // HTTPSじゃない場合はOff
ob_start();
session_start();

$error = [];

if (!empty($_POST)) {
    // ログインの処理
    if ($_POST['email'] != '' && $_POST['password'] != '') {
        $login = $db->prepare('SELECT * FROM members WHERE email=? AND password=?');
        $login->execute(array(
            $_POST['email'],
            sha1($_POST['password'])
        ));
        $member = $login->fetch();

        if ($member) {
            // ログイン成功
            $_SESSION['id'] = $member['id'];
            $_SESSION['time'] = time();

            header('Location: index.php'); exit();
        } else {
            $error['login'] = 'failed';
        }
    } else {
        $error['login'] = 'blank';
    }
        }
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
    <div class="contents">
        <div id="lead">
            <p>メールアドレスとパスワードを記入してログインしてください。</p>
            <p>入会手続きがまだの方はこちらからどうぞ。</p>
            <p>&raquo;<a href="join/">入会手続きをする</a></p>
        </div>
            <form action="" method="post">
            <dl>
                <dt>メールアドレス</dt>
                <dd>
                <input type="text" name="email" size="35" maxlength="255"
                value="<?php echo isset($_POST['email'])? htmlspecialchars($_POST['email'], ENT_QUOTES) : ''; ?>" />
                <?php if (isset($error['login']) && $error['login'] == 'blank'): ?>
                <p class="error">* メールアドレスとパスワードをご記入ください</p>
                <?php endif; ?>
                <?php if (isset($error['login']) && $error['login'] == 'failed'): ?>
                <p class="error">* ログインに失敗しました。正しくご記入ください。</p>
                <?php endif; ?>
                </dd>  
                <dt>パスワード</dt>
                <dd>
                <input type="password" name="password" size="35" maxlength="255" 
                value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password'], ENT_QUOTES) : ''; ?>"/>
                </dd>
                <dt>ログイン情報の記録</dt>
                <dd>
                <input id="save" type="checkbox" name="save" value="on"><label for="save">次回からは自動的にログインする</label>
                </dd>
            </dl>
            <div>
              <button type="submit" name="login" value="login" class="btn-gradient-radius">
                ログインする
            </button>
           </div>
            </form>
    </div>
</main>
</body>
</html>