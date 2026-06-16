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

// URLの「?id=〇〇」からタスクIDを取得
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($task_id === 0) {
    header('Location: index.php');
    exit;
}

$message = '';
$is_success = false;

// 更新ボタンやコメント投稿がPOSTされた時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 進捗・SOSの更新（タスクの持ち主だけが実行可能）
    if (isset($_POST['action']) && $_POST['action'] === 'update_task') {
        $progress = intval($_POST['progress']);
        $is_sos   = isset($_POST['is_sos']) ? 1 : 0;

        try {
            $sql = "UPDATE tasks SET progress = :progress, is_sos = :is_sos, updated_at = NOW() 
                    WHERE id = :task_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':progress' => $progress,
                ':is_sos'   => $is_sos,
                ':task_id'  => $task_id,
                ':user_id'  => $_SESSION['user_id']
            ]);
            $message = '進捗状況を更新しました！';
            $is_success = true;
        } catch (PDOException $e) {
            $message = '更新に失敗しました。';
        }
    }
    
    // 応援コメントの追加
    if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        $comment_text = trim($_POST['comment_text']);
        
        if (!empty($comment_text)) {
            try {
                $sql = "INSERT INTO comments (task_id, user_id, comment_text) VALUES (:task_id, :user_id, :comment_text)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':task_id'      => $task_id,
                    ':user_id'      => $_SESSION['user_id'],
                    ':comment_text' => $comment_text
                ]);
                $message = '応援コメントを届けました！';
                $is_success = true;
            } catch (PDOException $e) {
                $message = 'コメントの送信に失敗しました。';
            }
        }
    }
}

// 画面を表示するために現在のタスク詳細を取得
$sql_task = "SELECT tasks.*, users.username 
             FROM tasks 
             JOIN users ON tasks.user_id = users.id 
             WHERE tasks.id = :task_id";
$stmt_task = $pdo->prepare($sql_task);
$stmt_task->execute([':task_id' => $task_id]);
$task = $stmt_task->fetch();

// タスクが存在しない場合はメインに戻す
if (!$task) {
    header('Location: index.php');
    exit;
}

// タスクの作成者かどうかを判定
$is_owner = ($task['user_id'] == $_SESSION['user_id']);

// このタスクについた応援コメント一覧を取得
$sql_comments = "SELECT comments.*, users.username 
                 FROM comments 
                 JOIN users ON comments.user_id = users.id 
                 WHERE comments.task_id = :task_id 
                 ORDER BY comments.created_at ASC";
$stmt_comments = $pdo->prepare($sql_comments);
$stmt_comments->execute([':task_id' => $task_id]);
$comments = $stmt_comments->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>タスク詳細・応援 | トモテク</title>
    <style>
        body { 
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif; 
            margin: 0; 
            padding: 40px 20px;
            background-color: #f3f7fa; 
            color: #333333;
        }
        
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #ffffff; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); 
            overflow: hidden;
        }

        .header-banner {
            background: linear-gradient(135deg, #2b5cff, #4e2bff); 
            color: #ffffff;
            padding: 25px 20px;
            text-align: center;
        }
        .header-banner h2 { margin: 0; font-size: 20px; font-weight: 600; }
        .header-banner p { margin: 5px 0 0 0; font-size: 14px; opacity: 0.9; }

        /* SOS状態の時のヘッダー */
        .header-banner.sos-active {
            background: linear-gradient(135deg, #e53e3e, #f56565);
        }

        .content { padding: 30px; }
        
        /* 基本情報の枠 */
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .info-row { display: flex; margin-bottom: 12px; font-size: 15px; }
        .info-label { width: 30%; font-weight: bold; color: #4a5568; }
        .info-value { width: 70%; }

        /* メーター */
        progress { width: 100%; height: 16px; border-radius: 8px; overflow: hidden; margin-top: 5px; }
        progress::-webkit-progress-bar { background-color: #e2e8f0; }
        progress::-webkit-progress-value { background-color: #2b5cff; }
        .sos-active progress::-webkit-progress-value { background-color: #e53e3e; }

        /* フォームの共通カードスタイル */
        .inner-card {
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            padding: 20px; 
            border-radius: 8px;
            margin-bottom: 25px;
        }

        /* コメント用吹き出しのスタイル */
        .comment-bubble {
            background-color: #f0f4ff;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .comment-meta { font-size: 12px; color: #718096; margin-bottom: 4px; font-weight: bold; }

        /* フォーム要素 */
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: bold; }
        select, textarea { 
            width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; background-color: #f8fafc; font-size: 14px;
        }
        
        .btn-submit { 
            width: 100%; padding: 12px; background-color: #2b5cff; color: white; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: background-color 0.2s;
        }
        .btn-submit:hover { background-color: #1a46d6; }
        
        /* SOS用トグルボタン */
        .sos-checkbox {
            display: flex; align-items: center; background-color: #fff5f5; border: 1px solid #fed7d7; padding: 10px; border-radius: 6px; color: #e53e3e; font-weight: bold; margin-bottom: 15px;
        }
        .sos-checkbox input { margin-right: 10px; transform: scale(1.3); }

        .back-link { display: block; text-align: center; margin-top: 25px; font-size: 14px; color: #2b5cff; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        .msg { padding: 12px; border-radius: 6px; text-align: center; font-size: 14px; margin-bottom: 20px; font-weight: bold; }
        .msg.success { background-color: #f0fff4; color: #38a169; border: 1px solid #c6f6d5; }
    </style>
</head>
<body>

<div class="container <?php echo $task['is_sos'] ? 'sos-active' : ''; ?>">
    
    <div class="header-banner <?php echo $task['is_sos'] ? 'sos-active' : ''; ?>">
        <h2><?php echo $task['is_sos'] ? '🚨 助け合いモード稼働中' : '📋 タスク詳細・応援'; ?></h2>
        <p>トモテク (TomoTech)</p>
    </div>

    <div class="content">
        <?php if (!empty($message)): ?>
            <div class="msg success"><?php echo $message; ?></div>
        <?php endif; ?>

        <!--基本情報カード-->
        <div class="info-box">
            <div class="info-row">
                <div class="info-label">担当メンバー</div>
                <div class="info-value"><strong><?php echo htmlspecialchars($task['username'], ENT_QUOTES, 'UTF-8'); ?></strong> さん</div>
            </div>
            <div class="info-row">
                <div class="info-label">タスク名</div>
                <div class="info-value"><?php echo htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">締め切り日</div>
                <div class="info-value" style="<?php echo (strtotime($task['deadline']) - time()) / (60*60*24) <= 3 ? 'color:red; font-weight:bold;' : ''; ?>">
                    <?php echo htmlspecialchars($task['deadline'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
            <div class="info-row" style="display:block;">
                <div class="info-label" style="width:100%; margin-bottom: 5px;">現在の進捗：<?php echo $task['progress']; ?>%</div>
                <progress value="<?php echo $task['progress']; ?>" max="100"></progress>
            </div>
        </div>

        <!--【自分のタスクの場合】進捗の更新カード-->
        <?php if ($is_owner): ?>
            <form action="detail.php?id=<?php echo $task_id; ?>" method="POST" class="inner-card">
                <input type="hidden" name="action" value="update_task">
                <h3 style="margin-top:0; font-size:16px; color:#2b5cff;">自身の進捗状況を更新</h3>
                
                <div class="input-group">
                    <label for="progress">進捗率を変更</label>
                    <select id="progress" name="progress">
                        <option value="0" <?php if($task['progress'] == 0) echo 'selected'; ?>>0% (未着手)</option>
                        <option value="25" <?php if($task['progress'] == 25) echo 'selected'; ?>>25% (設計・準備中)</option>
                        <option value="50" <?php if($task['progress'] == 50) echo 'selected'; ?>>50% (実装中)</option>
                        <option value="75" <?php if($task['progress'] == 75) echo 'selected'; ?>>75% (テスト・微調整中)</option>
                        <option value="100" <?php if($task['progress'] == 100) echo 'selected'; ?>>100% (完了！)</option>
                    </select>
                </div>

                <div class="sos-checkbox">
                    <input type="checkbox" id="is_sos" name="is_sos" value="1" <?php if($task['is_sos'] == 1) echo 'checked'; ?>>
                    <label for="is_sos" style="cursor:pointer; margin:0;">🚨 難航中・SOSを出してみんなに知らせる</label>
                </div>

                <button type="submit" class="btn-submit">上記の内容で更新する</button>
            </form>
        <?php endif; ?>

        <!--応援コメント投稿カード-->
        <div class="inner-card">
            <h3 style="margin-top:0; font-size:16px; color:#4a5568;">💬 仲間への応援メッセージ</h3>
            
            <!--コメント一覧部分-->
            <div style="margin-bottom: 20px; max-height: 250px; overflow-y: auto;">
                <?php if (empty($comments)): ?>
                    <p style="color: #999; font-size: 14px; text-align: center; margin: 20px 0;">まだメッセージはありません。最初の声をかけよう！</p>
                <?php else: ?>
                    <?php foreach ($comments as $com): ?>
                        <div class="comment-bubble">
                            <div class="comment-meta">
                                <?php echo htmlspecialchars($com['username'], ENT_QUOTES, 'UTF-8'); ?> さん 
                                <span style="font-weight:normal; color:#a0aec0; margin-left:8px;"><?php echo $com['created_at']; ?></span>
                            </div>
                            <div style="color: #2d3748; line-height: 1.4;">
                                <?php echo nl2br(htmlspecialchars($com['comment_text'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!--コメントフォーム-->
            <form action="detail.php?id=<?php echo $task_id; ?>" method="POST" style="border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                <input type="hidden" name="action" value="add_comment">
                <div class="input-group">
                    <textarea name="comment_text" rows="2" placeholder="メッセージを入力" required></textarea>
                </div>
                <button type="submit" class="btn-submit" style="background-color: #4a5568;">エールを送る</button>
            </form>
        </div>

        <a href="index.php" class="back-link">ダッシュボードに戻る</a>
    </div>
</div>

</body>
</html>
