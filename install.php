<?php
/**
 * 数据库初始化安装脚本 - 独立版本
 */

// 检查配置文件是否存在
$configPath = __DIR__ . '/includes/config.php';
if (!file_exists($configPath)) {
    die("错误：配置文件 includes/config.php 不存在！请先创建该文件。");
}

// 引入配置文件
require_once $configPath;

// 检查是否已提交表单
$isInstalled = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = trim($_POST['admin_password_confirm'] ?? '');
    
    // 验证密码
    if (empty($adminPassword)) {
        $error = '请输入管理员密码';
    } elseif (strlen($adminPassword) < 6) {
        $error = '管理员密码至少需要6个字符';
    } elseif ($adminPassword !== $adminPasswordConfirm) {
        $error = '两次输入的密码不一致';
    } else {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
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
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role) VALUES 
                ('admin', 'admin@bookmusic.com', ?, 'admin')");
            $stmt->execute([$hashedPassword]);
            
            $isInstalled = true;
            
        } catch (PDOException $e) {
            $error = "数据库安装失败: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - BookMusic Mall</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .success {
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .success p {
            color: #666;
            margin: 10px 0;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        a {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            text-decoration: underline;
        }
        .hint {
            font-size: 14px;
            color: #888;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($isInstalled): ?>
            <div class="success">
                <div class="success-icon">✅</div>
                <h1>数据库安装成功！</h1>
                <p>所有表已创建完成。</p>
                <p>管理员账号: <strong>admin</strong></p>
                <p><a href="index.php">点击返回首页</a></p>
            </div>
        <?php else: ?>
            <h1>📦 安装 BookMusic Mall</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="admin_password">管理员密码</label>
                    <input type="password" id="admin_password" name="admin_password" required placeholder="请输入管理员密码">
                    <p class="hint">密码至少需要6个字符</p>
                </div>
                <div class="form-group">
                    <label for="admin_password_confirm">确认密码</label>
                    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required placeholder="请再次输入密码">
                </div>
                <button type="submit">开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
