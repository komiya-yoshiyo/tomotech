<?php
// セッションの開始
session_start();
// データベース接続ファイルを読み込む
require_once 'db_connect.php';
// ログインしていない場合はログイン画面に強制移動
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// 登録ボタンが押された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_date = $_POST['schedule_date'];
    $content       = trim($_POST['content']);
    $user_id       = $_SESSION['user_id']; // 登録した人のID

    if (!empty($schedule_date) && !empty($content)) {
        try {
            // スケジュールをデータベースに登録する
            $sql = "INSERT INTO schedules (schedule_date, content, user_id) VALUES (:schedule_date, :content, :user_id)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':schedule_date' => $schedule_date,
                ':content'       => $content,
                ':user_id'       => $user_id
            ]);

            // 登録が成功したらメイン画面に戻る
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $message = 'スケジュールの登録に失敗しました。';
        }
    } else {
        $message = '日付と予定内容は必須入力です。';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スケジュール追加 | トモテク</title>
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

        .form-content { padding: 30px; }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-size: 14px;
            font-weight: bold;
            color: #4a5568;
        }
        .input-group input, .input-group textarea { 
            width: 100%; 
            padding: 12px; 
            box-sizing: border-box; 
            border: 1px solid #e2e8f0; 
            border-radius: 6px; 
            font-size: 15px;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .input-group input:focus, .input-group textarea:focus {
            outline: none;
            border-color: #2b5cff;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.1);
        }

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
        
        .back-link {
            display: block;
            text-align: center; 
            margin-top: 20px;
            font-size: 14px;
            color: #2b5cff; 
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover { text-decoration: underline; }

        .msg { padding: 12px; border-radius: 6px; text-align: center; font-size: 14px; margin-bottom: 20px; background-color: #fff5f5; color: #e53e3e; border: 1px solid #fed7d7; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-banner">
        <h2>チームスケジュールの追加</h2>
        <p>トモテク (TomoTech)</p>
    </div>

    <div class="form-content">
        <?php if (!empty($message)): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form action="create_schedule.php" method="POST">
            <div class="input-group">
                <label for="schedule_date">予定の日付</label>
                <input type="date" id="schedule_date" name="schedule_date" required>
            </div>

            <div class="input-group">
                <label for="content">予定の内容</label>
                <textarea id="content" name="content" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn-submit">スケジュールを登録する</button>
        </form>
        
        <a href="index.php" class="back-link">ダッシュボードに戻る</a>
    </div>
</div>

</body>
</html>
