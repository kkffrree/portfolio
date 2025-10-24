<?php
try {
    // dbconnect.php と database.sqlite が同じ mmb フォルダ内にある場合
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー：' . $e->getMessage();
    exit;
}
?>