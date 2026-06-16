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

// SOSが出ているタスクを最優先で取得
$sql_sos = "SELECT tasks.*, users.username 
            FROM tasks 
            JOIN users ON tasks.user_id = users.id 
            WHERE tasks.is_sos = 1 
            ORDER BY tasks.updated_at DESC";
$stmt_sos = $pdo->query($sql_sos);
$sos_tasks = $stmt_sos->fetchAll();

// 本物のスケジュールデータを「日付順」で取得する
$sql_sch = "SELECT schedules.*, users.username 
            FROM schedules 
            JOIN users ON schedules.user_id = users.id 
            WHERE schedules.schedule_date >= CURDATE() -- 今日以降の予定のみ表示
            ORDER BY schedules.schedule_date ASC";
$stmt_sch = $pdo->query($sql_sch);
$schedules = $stmt_sch->fetchAll();

// 全員のタスクを「締め切りが近い順」で取得
$sql_all = "SELECT tasks.*, users.username 
            FROM tasks 
            JOIN users ON tasks.user_id = users.id 
            ORDER BY tasks.deadline ASC";
$stmt_all = $pdo->query($sql_all);
$all_tasks = $stmt_all->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ダッシュボード | トモテク</title>
    <style>
        body {
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f3f7fa;
            color: #333;
        }

        /* ヘッダーナビゲーション */
        header {
            background: linear-gradient(135deg, #2b5cff, #4e2bff);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        header .user-info { font-size: 14px; }
        header .user-info a { color: #ffeb3b; text-decoration: none; margin-left: 15px; font-weight: bold; }

        /* メインコンテンツエリア */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* 白色の角丸カード */
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
        .card h2 {
            margin-top: 0;
            font-size: 18px;
            border-left: 4px solid #2b5cff;
            padding-left: 10px;
            color: #2b5cff;
        }

        /* SOS緊急アラート */
        .sos-card {
            border: 2px solid #e53e3e;
            background-color: #fff5f5;
        }
        .sos-card h2 { border-left-color: #e53e3e; color: #e53e3e; }
        .sos-item {
            background: white;
            border: 1px solid #fed7d7;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sos-badge { background-color: #e53e3e; color: white; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }

        /* テーブルスタイル */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        th { background-color: #f8fafc; color: #4a5568; font-size: 14px; }
        td { font-size: 15px; }

        /* 進捗バー */
        progress { width: 100%; height: 12px; border-radius: 6px; overflow: hidden; }
        progress::-webkit-progress-bar { background-color: #e2e8f0; }
        progress::-webkit-progress-value { background-color: #2b5cff; }

        /* ボタン関係 */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            background-color: #2b5cff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }
        .btn:hover { background-color: #1a46d6; }
        .btn-action { background-color: #4a5568; }
        .btn-action:hover { background-color: #2d3748; }
    </style>
</head>
<body>

<header>
    <h1>トモテク (TomoTech)</h1>
    <div class="user-info">
        ようこそ、<strong><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></strong> さん
        <a href="logout.php">ログアウト</a>
    </div>
</header>

<div class="main-container">

    <?php if (!empty($sos_tasks)): ?>
        <div class="card sos-card">
            <h2>🚨 チームのSOS（苦戦中のメンバーがいます！）</h2>
            <?php foreach ($sos_tasks as $task): ?>
                <div class="sos-item">
                    <div>
                        <span class="sos-badge">SOS</span>
                        <strong><?php echo htmlspecialchars($task['username'], ENT_QUOTES, 'UTF-8'); ?></strong> さんのタスク: 
                        「<?php echo htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8'); ?>」
                    </div>
                    <a href="detail.php?id=<?php echo $task['id']; ?>" class="btn" style="background-color: #e53e3e;">助けに行く（詳細）</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>📅 チームスケジュール</h2>
            <a href="create_schedule.php" class="btn btn-action">＋ 予定を追加</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">日付</th>
                    <th style="width: 60%;">内容</th>
                    <th style="width: 20%;">登録者</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #999; padding: 20px;">
                            現在登録されている予定はありません。右上のボタンから追加してみましょう！
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($schedules as $sch): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sch['schedule_date'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo nl2br(htmlspecialchars($sch['content'], ENT_QUOTES, 'UTF-8')); ?></td>
                            <td><span style="color: #666; font-size: 13px;"><?php echo htmlspecialchars($sch['username'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>📊 メンバーの進捗状況（期限が近い順）</h2>
            <a href="create_task.php" class="btn btn-action">＋ 自分の進捗タスクを追加</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">担当者</th>
                    <th style="width: 30%;">ミッション・タスク名</th>
                    <th style="width: 25%;">進捗率 (メーター)</th>
                    <th style="width: 15%;">締め切り日</th>
                    <th style="width: 15%;">アクション</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_tasks)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999; padding: 20px;">登録されているタスクがありません。右上のボタンから追加しましょう！</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_tasks as $task): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($task['username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div style="font-size: 12px; margin-bottom: 3px; font-weight: bold; color: #2b5cff;">
                                    <?php echo $task['progress']; ?>% 完了
                                </div>
                                <progress value="<?php echo $task['progress']; ?>" max="100"></progress>
                            </td>
                            <?php 
                                $is_urgent = (strtotime($task['deadline']) - time()) / (60 * 60 * 24) <= 3;
                            ?>
                            <td style="<?php echo $is_urgent ? 'color: red; font-weight: bold;' : ''; ?>">
                                <?php echo htmlspecialchars($task['deadline'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($is_urgent): ?> ⚠️<?php endif; ?>
                            </td>
                            <td>
                                <a href="detail.php?id=<?php echo $task['id']; ?>" class="btn">詳細・応援</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
