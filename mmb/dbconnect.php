<?php
try {
  $db = new PDO(
    'mysql:dbname=LAA1686811-alftte;host=mysql80-1.lolipop.lan;charset=utf8',
    'LAA1686811',
    'kokiri27'
  );
} catch (PDOException $e) {
  echo 'DB接続エラー: ' . $e->getMessage();
    exit;
}
?>