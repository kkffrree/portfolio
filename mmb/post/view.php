<?php
require(__DIR__ . '/../dbconnect.php');
ini_set('session.cookie_samesite', 'None'); // POSTでも送信可能に
ini_set('session.cookie_secure', 'Off');    // HTTPSじゃない場合はOff
ob_start();
session_start();

$error = [];

if (empty($_REQUEST['id'])) {
    header('Location: index.php'); exit();
}

//投稿を取得する
$stmt = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id = p.member_id AND p.id = ?');
$stmt->execute([$_REQUEST['id']]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// htmlspecialcharsのショートカット
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES);
}

// 本文内のURLにリンクを設定します
function makeLink($value) {
    return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2">\1\2</a>', $value);
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
    <div class="head">
    </div>
    <div class="content">
        <p>&laquo;<a href="index.php">一覧に戻る</a></p>
        <?php if ($post): ?>
        <div class="msg">
            <img src="member_picture/<?php echo htmlspecialchars($post['picture'], ENT_QUOTES); ?>" width="48" height="48" alt="<?php echo htmlspecialchars($post['name'], ENT_QUOTES);?>" />
            <p><?php echo htmlspecialchars($post['message'], ENT_QUOTES); ?><span class="name">(<?php echo htmlspecialchars($post['name'], ENT_QUOTES); ?>)</span></p>
            [<a href="index.php?res=<?php echo htmlspecialchars($post['id'], ENT_QUOTES); ?>">Re</a>]
            <p class="day"><?php echo htmlspecialchars($post['created'], ENT_QUOTES); ?></p>
                <?php
                if ($post['reply_post_id'] > 0):
                ?>
                <a href="view.php?id=<?php echo htmlspecialchars($post['reply_post_id'], ENT_QUOTES); ?>">返信元のメッセージ</a>
                <?php endif; ?>
                <?php if($_SESSION['id'] == $post['member_id']): ?>
                [<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#F33;">削除</a>]
                <?php endif; ?>
        </div>
<?php else: ?>
    <p>その投稿は削除されたか、URLが間違っています</p>
<?php endif; ?>
</div>
</main>
</body>
</html>