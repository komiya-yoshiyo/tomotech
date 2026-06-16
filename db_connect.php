<?php
$host     = 'localhost';
$dbname   = '******';
$user     = '******';
$password = '******';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, 
        ]
    );
    
} catch (PDOException $e) {
    // 接続に失敗した場合
    exit('データベース接続失敗：' . $e->getMessage());
}
?>
