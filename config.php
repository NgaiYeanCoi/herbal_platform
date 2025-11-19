<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 数据库配置
$host = 'localhost';
$dbname = 'herbal_platform';
$username = 'root';
$password = '10086'; // 替换为你的数据库密码

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>