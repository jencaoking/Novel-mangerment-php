<?php
/**
 * 数据库初始化安装脚本 - 独立版本
 */

// 直接使用正确的数据库配置
$host = 'mysql6.sqlpub.com';
$port = '3311';
$dbname = 'novel00000';
$username = 'jencao0';
$password = 'De3IIdSHLcwZMRHk';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // 用户表
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        status TINYINT DEFAULT 1 COMMENT '1-正常 0-封禁',
        last_login DATETIME DEFAULT NULL,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 分类表
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('novel', 'music') NOT NULL COMMENT '小说分类/音乐分类',
        sort_order INT DEFAULT 0,
        status TINYINT DEFAULT 1,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 商品表
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        type ENUM('novel', 'music') NOT NULL COMMENT '小说/音乐',
        category_id INT NOT NULL,
        author VARCHAR(100) DEFAULT NULL COMMENT '作者/歌手',
        description TEXT DEFAULT NULL,
        cover VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        preview_path VARCHAR(255) DEFAULT NULL COMMENT '音乐试听路径',
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        downloads INT DEFAULT 0,
        sales INT DEFAULT 0,
        status TINYINT DEFAULT 1 COMMENT '1-上架 0-下架',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 订单表
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_no VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending',
        payment_method VARCHAR(50) DEFAULT NULL,
        payment_no VARCHAR(100) DEFAULT NULL,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 下载记录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS downloads (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        order_id INT NOT NULL,
        download_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (order_id) REFERENCES orders(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 插入初始分类数据
    $pdo->exec("INSERT IGNORE INTO categories (name, type, sort_order) VALUES 
        ('玄幻', 'novel', 1),
        ('都市', 'novel', 2),
        ('仙侠', 'novel', 3),
        ('历史', 'novel', 4),
        ('科幻', 'novel', 5),
        ('流行', 'music', 1),
        ('摇滚', 'music', 2),
        ('电子', 'music', 3),
        ('古典', 'music', 4),
        ('民谣', 'music', 5)");
    
    // 创建管理员账号
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, email, password, role) VALUES 
        ('admin', 'admin@bookmusic.com', '$adminPassword', 'admin')");
    
    echo "<h1>数据库安装成功！</h1>";
    echo "<p>所有表已创建完成。</p>";
    echo "<p>管理员账号: admin / admin123</p>";
    echo "<p><a href='index.php'>点击返回首页</a></p>";
    
} catch (PDOException $e) {
    die("数据库安装失败: " . $e->getMessage());
}
?>