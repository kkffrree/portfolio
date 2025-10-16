<?php
require('../../dbconnect.php');
ini_set('session.cookie_samesite', 'None'); // POSTでも送信可能に
ini_set('session.cookie_secure', 'Off');    // HTTPSじゃない場合はOff
ob_start();
session_start();

$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // エラー項目の確認
    if (empty($_POST['name'])) {
        $error['name'] = 'blank';
    }
    if (empty($_POST['email'])) {
        $error['email'] = 'blank';
    }
    if (empty($_POST['password'])) {
        $error['password'] = 'blank';
    } elseif (strlen($_POST['password']) < 4) {
        $error['password'] = 'length';
    }

    $fileName = $_FILES['image']['name'] ?? '';
    if(!empty($fileName)) {
        $ext = strtolower(substr($fileName, -3));
        if (!in_array($ext, ['jpg', 'gif', 'png'])) {
            $error['image'] = 'type';
        }
    }
    // 重複アカウントのチェック
    if(empty($error)) {
        $member = $db->prepare('SELECT COUNT(*) AS cnt FROM members WHERE email=?');
        $member ->execute([$_POST['email']]);
        $record = $member->fetch();
        if ($record['cnt'] > 0) {
        $error['email'] = 'duplicate';

    }}

    // エラーがなければセッションに保存
    if (empty($error)) {
        $_SESSION['join'] = $_POST;

        if (!empty($fileName)) {
            $image = date('YmdHis') . $fileName;
            move_uploaded_file($_FILES['image']['tmp_name'], '../member_picture/' . $image);
            $_SESSION['join']['image'] = $image;
        }

        header('Location: check.php');
        exit();
    }
}

// 書き直し処理
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'rewrite' && !empty($_SESSION['join'])) {
    $_POST = $_SESSION['join'];
    $error['rewrite'] = true;
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="../style.css">
</head>
<body>

<main>
<div class="contents">
<h2>会員登録</h2>
<p>次のフォームに必要事項をご記入ください。</p>
<form action="" method="post" enctype="multipart/form-data">
<dl>
    <dt>ニックネーム<span class="required">必須</span></dt>
    <dd><input type="text" name="name" size="35" maxlength="255" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES); ?>">
    <?php if (!empty($error['name']) && $error['name'] === 'blank'): ?><p class="error">*ニックネームを入力してください</p><?php endif; ?></dd>

    <dt>メールアドレス<span class="required">必須</span></dt>
    <dd><input type="text" name="email" size="35" maxlength="255" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">
    <?php if (!empty($error['email']) && $error['email'] === 'blank'): ?>
    <p class="error">*メールアドレスを入力してください</p>
    <?php endif; ?></dd>
    <?php if (!empty($error['email']) && $error['email'] === 'duplicate'): ?>
    <p class="error">*指定されたメールアドレスはすでに登録されています</p>
    <?php endif; ?>
    <dt>パスワード<span class="required">必須</span></dt>
    <dd><input type="password" name="password" size="10" maxlength="20" value="<?php echo htmlspecialchars($_POST['password'] ?? '', ENT_QUOTES); ?>">
    <?php if (!empty($error['password']) && $error['password'] === 'length'): ?><p class="error">*パスワードは4文字以上で入力してください</p><?php endif; ?></dd>

    <dt>写真など</dt>
    <dd><input type="file" name="image" size="35">
    <?php if (!empty($error['image']) && $error['image'] === 'type'): ?><p class="error">*写真などは「.gif」または「.jpg」の画像を指定してください</p><?php endif; ?>
    <?php if (!empty($error) && isset($error['image'])): ?><p class="error">*恐れ入りますが、画像を改めて指定してください</p><?php endif; ?></dd>
</dl>
<div>
  <button type="submit" name="join" value="join" class="btn-gradient-radius">
    入力内容を確認する
  </button>
</div>
</form>
</div>
</main>
</body>
</html>