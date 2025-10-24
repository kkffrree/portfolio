<?php
require(__DIR__ . '/../dbconnect.php');
ini_set('session.cookie_samesite', 'Lax'); // POSTでも送信可能に
ini_set('session.cookie_secure', 'Off');    // HTTPSじゃない場合はOff
ob_start();
session_start();
$error = [];

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();

    $members = $db->prepare('SELECT* FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
} else {
    // ログインしていない
    header('Location: login.php'); exit();
}
 // 投稿を記録する
 if (isset($_SESSION['id'])) {
    $member_id = $_SESSION['id'];
    $error = [];

    if (!empty($_POST)) {
    if (trim($_POST['message']) === '') {
        $error['message'] = 'blank';
    } else {
        $reply_post_id = !empty($_POST['reply_post_id']) ? $_POST['reply_post_id'] : 0;
        $postInsert = $db->prepare('INSERT INTO posts (member_id, message, reply_post_id, created, modified) VALUES (?, ?, ?, NOW(), NOW())');
        $postInsert->execute([$member_id, $_POST['message'], $_POST['reply_post_id'] ?? 0 ]);

        header('Location: index.php');
        exit();
    }
}
} else {
    header('Location: login.php');
    exit();
}

// 投稿を取得する
$posts = $db->query('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC')
            ->fetchAll(PDO::FETCH_ASSOC);


// 返信の場合（$message を常に定義しておく）
$message = '';
if (isset($_REQUEST['res'])) {
    $response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id = p.member_id AND p.id = ? ORDER BY p.created DESC');
    $response->execute([$_REQUEST['res']]);
    $table = $response->fetch();

    if ($table) {
        $message = '@' . $table['name'] . ' ' . $table['message'];
    }
}
// htmlspecialcharsのショートカット
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES);
}

// 本文内のURLにリンクを設定します
function makeLink($value) {
    return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2">\1\2</a>', $value);
}

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
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="full_page">
    <main>
        
    <div class="contents">
        <div class="form">
        <div class="logout"><a href="logout.php">ログアウト</a></div><br>
        <form action="" method="post">
        <dl>
            <dt><?php echo h($member['name'], ENT_QUOTES); ?>さん、 メッセージをどうぞ(255文字まで)</dt>
            <dd>
                <textarea name="message" cols="50" rows="5"><?php echo h($message, ENT_QUOTES); ?></textarea>
                <input type="hidden" name="reply_post_id" value="<?php echo isset($_REQUEST['res']) ? h($_REQUEST['res'], ENT_QUOTES) : 0; ?>"/>
            </dd>
        </dl>
        <div><button type="submit" class="btn-gradient-radius">投稿する</button></div>
        
        </form>
        </div>

        <div class="msg_container">
        <?php foreach ($posts as $post):?>
        <?php if ($post === false) continue; ?>
        <div class="msg">

            <img src="member_picture/<?php echo h($post['picture'],ENT_QUOTES); ?>" width="48" height="48" alt="<?php echo h($post['name'], ENT_QUOTES);?>" />
            <p><?php echo makeLink(h($post['message'], ENT_QUOTES)); ?><span class="name">(<?php echo h ($post['name'], ENT_QUOTES); ?>)</span></p>
            [<a href="index.php?res=<?php echo h($post['id'], ENT_QUOTES); ?>">Re</a>]
            <p class="day"><a href="view.php?id=<?php echo h($post['id'], ENT_QUOTES); ?>"><?php echo h($post['created'], ENT_QUOTES); ?></a>
            <?php
            if ($post['reply_post_id'] > 0):
            ?>
            <a href="view.php?id=<?php echo h($post['reply_post_id'], ENT_QUOTES); ?>">返信元のメッセージ</a>
            <?php endif; ?>
            <?php if($_SESSION['id'] == $post['member_id']): ?>
            [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#ccc;">削除</a>]
            <?php endif; ?>
        </p>
        </div>
        <?php
        endforeach;
        ?>
    </div>
    </div>
</div>
</main>
</body>
</html>