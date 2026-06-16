<?php
// セッションの開始
session_start();
// データベース接続ファイルを読み込む
require_once 'db_connect.php';
// ログインしていない場合はログイン画面に移動
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$is_success = false;

// 登録ボタンが押された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = trim($_POST['task_name']);
    $progress  = intval($_POST['progress']);
    $deadline  = $_POST['deadline'];
    $user_id   = $_SESSION['user_id'];

    if (!empty($task_name) && !empty($deadline)) {
        try {
            // データベースにタスクを挿入する
            $sql = "INSERT INTO tasks (user_id, task_name, progress, deadline, is_sos) 
                    VALUES (:user_id, :task_name, :progress, :deadline, 0)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':user_id'   => $user_id,
                ':task_name' => $task_name,
                ':progress'  => $progress,
                ':deadline'  => $deadline
            ]);

            // 登録が成功したらメイン画面に戻す
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $message = '登録に失敗しました：' . $e->getMessage();
        }
    } else {
        $message = 'タスク名と締め切り日は必須入力です。';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>タスク追加 | トモテク</title>
    <style>
        body { 
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif; 
            margin: 0; 
            padding: 50px 20px;
            background-color: #f3f7fa; 
            color: #333333;
        }
        
        .container { 
            max-width: 480px; 
            margin: 0 auto; 
            background: #ffffff; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); 
            overflow: hidden;
        }

        .header-banner {
            background: linear-gradient(135deg, #2b5cff, #4e2bff); 
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header-banner h2 { margin: 0; font-size: 20px; font-weight: 600; }
        .header-banner p { margin: 5px 0 0 0; font-size: 15px; opacity: 0.9; }

        .form-content { padding: 30px; }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-size: 14px;
            font-weight: bold;
            color: #4a5568;
        }
        .input-group input, .input-group select { 
            width: 100%; 
            padding: 12px; 
            box-sizing: border-box; 
            border: 1px solid #e2e8f0; 
            border-radius: 6px; 
            font-size: 15px;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: #2b5cff;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.1);
        }

        /* 登録ボタン */
        .btn-submit { 
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
        }
        .btn-submit:hover { background-color: #1a46d6; }
        
        /* 戻るリンク */
        .back-link {
            display: block;
            text-align: center; 
            margin-top: 20px;
            font-size: 14px;
            color: #4a5568;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }

        .msg { padding: 12px; border-radius: 6px; text-align: center; font-size: 14px; margin-bottom: 20px; background-color: #fff5f5; color: #e53e3e; border: 1px solid #fed7d7; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-banner">
        <h2>新しい進捗タスクの追加</h2>
        <p>トモテク (TomoTech)</p>
    </div>

    <div class="form-content">
        <?php if (!empty($message)): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form action="create_task.php" method="POST">
            <div class="input-group">
                <label for="task_name">ミッション・タスク名</label>
                <input type="text" id="task_name" name="task_name" required>
            </div>

            <div class="input-group">
                <label for="progress">現在の進捗状況</label>
                <select id="progress" name="progress">
                    <option value="0">0% (未着手)</option>
                    <option value="25">25% (設計・準備中)</option>
                    <option value="50">50% (実装中)</option>
                    <option value="75">75% (テスト・微調整中)</option>
                    <option value="100">100% (完了！)</option>
                </select>
            </div>

            <div class="input-group">
                <label for="deadline">締め切り日</label>
                <input type="date" id="deadline" name="deadline" required>
            </div>

            <button type="submit" class="btn-submit">タスクをタイムラインに追加する</button>
        </form>
        
        <a href="index.php" class="back-link">ダッシュボードに戻る</a>
    </div>
</div>

</body>
</html>
