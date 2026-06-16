<?php
// データベース接続ファイルを読み込む
require_once 'db_connect.php';

$message = '';
$is_success = false;

// 登録ボタンが押された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashed_password
            ]);

            $message = 'ユーザー登録が完了しました！';
            $is_success = true;
        } catch (PDOException $e) {
            $message = 'このユーザー名は既に使われています。';
        }
    } else {
        $message = 'ユーザー名とパスワードを入力してください。';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規ユーザー登録 | トモテク</title>
    <style>
        body { 
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif; 
            margin: 0; 
            padding: 50px 20px;
            background-color: #f3f7fa;
            color: #333333;
        }
        
        /* 中央配置のコンテナ */
        .container { 
            max-width: 480px;
            margin: 0 auto; 
            background: #ffffff; 
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* ヘッダー */
        .header-banner {
            background: linear-gradient(135deg, #2b5cff, #4e2bff);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header-banner h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .header-banner p {
            margin: 8px 0 0 0;
            font-size: 15px;
            font-weight: bold;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        /* フォームのコンテンツエリア */
        .form-content {
            padding: 30px;
        }
        
        /* 入力フォームのスタイル */
        .input-group { margin-bottom: 20px; }
        .input-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-size: 14px;
            font-weight: bold;
            color: #4a5568;
        }
        .input-group input { 
            width: 100%; 
            padding: 12px; 
            box-sizing: border-box; 
            border: 1px solid #e2e8f0; 
            border-radius: 6px; 
            font-size: 15px;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        /* 入力中の青い枠線 */
        .input-group input:focus {
            outline: none;
            border-color: #2b5cff;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.1);
        }

        /* 送信ボタン */
        button { 
            width: 100%; 
            padding: 12px; 
            background-color: #2b5cff; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px;
            font-weight: bold;
            cursor: pointer; 
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        button:hover { background-color: #1a46d6; }
        
        /* メッセージ表示の装飾 */
        .msg { 
            padding: 12px;
            border-radius: 6px;
            text-align: center; 
            font-size: 14px;
            margin-bottom: 20px; 
            font-weight: bold;
        }
        .msg.error {
            background-color: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
        }
        .msg.success {
            background-color: #f0fff4;
            color: #38a169;
            border: 1px solid #c6f6d5;
        }

        .link-area {
            text-align: center; 
            margin-top: 20px;
            font-size: 14px;
        }
        .link-area a {
            color: #2b5cff;
            text-decoration: none;
            font-weight: 500;
        }
        .link-area a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-banner">
        <h2>チーム開発支援・進捗同期システム</h2>
        <p>トモテク (TomoTech)</p>
    </div>

    <div class="form-content">
        <?php if (!empty($message)): ?>
            <div class="msg <?php echo $is_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
                <?php if ($is_success): ?>
                    <br><a href="login.php" style="color: #38a169; text-decoration: underline;">ログインはこちら</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="username">ユーザー名（ログインID）</label>
                <input type="text" id="username" name="username" placeholder="ユーザー名を入力" required>
            </div>
            <div class="input-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" placeholder="パスワードを入力" required>
            </div>
            <button type="submit">新規アカウント作成</button>
        </form>
        
        <div class="link-area">
            <a href="login.php">すでにアカウントをお持ちの方</a>
        </div>
    </div>
</div>

</body>
</html>
