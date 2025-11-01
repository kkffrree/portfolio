<?php
try {
  $db = new PDO(
    'mysql:dbname=LAA1686811-alftte;host=mysql80-3.lolipop.lan;charset=utf8',
    'LAA1686811',
    'YdV0kMCESrFVyL0C'
  );
} catch (PDOException $e) {
  echo 'DB接続エラー: ' . $e->getMessage();
    exit;
}
?>