<?php
// セッションの開始
session_start();

// セッション変数をすべて解除（ログイン情報をクリア）
$_SESSION = array();

// ブラウザのクッキーに保存されたセッションIDも削除
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// セッションを完全に破壊
session_destroy();

// ログアウト後に「ログイン画面」へ移動させる
header('Location: login.php');
exit;
