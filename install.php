<?php
/**
 * 数据库初始化安装脚本
 */

require_once 'includes/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    // 先连接不指定数据库
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // 创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 切换到目标数据库
    $pdo->exec("USE " . DB_NAME);
    
    // 读取 SQL 文件
    $sql = file_get_contents('database.sql');
    
    // 按分号分割 SQL 语句
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            // 跳过 USE 语句，因为我们已经切换了数据库
            if (stripos($statement, 'USE ') === 0 || stripos($statement, 'CREATE DATABASE') === 0) {
                continue;
            }