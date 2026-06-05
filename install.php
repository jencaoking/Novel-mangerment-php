<?php
/**
 * 数据库初始化安装脚本 - 独立版本
 */

define('INSTALL_SCRIPT', true);

// 检查是否已安装
if (file_exists(__DIR__ . '/install.lock')) {
    die('系统已安装！如需重新安装，请先删除 install.lock 文件。');
}

// 检查配置文件是否存在
$configPath = __DIR__ . '/includes/config.php';
if (!file_exists($configPath)) {
    die("错误：配置文件 includes/config.php 不存在！请先创建该文件。");
}

// 引入配置文件
require_once $configPath;

// 引入认证函数（用于CSRF保护）
require_once __DIR__ . '/includes/auth.php';

// 检查是否已提交表单
$isInstalled = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF验证
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
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
            
            // 读取 database.sql 文件
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("数据库初始化脚本 database.sql 不存在");
            }
            
            $sqlContent = file_get_contents($sqlFile);
            
            // 解析并执行 SQL 语句（按分号分割，排除注释和空行）
            $sqlStatements = explode(';', $sqlContent);
            
            foreach ($sqlStatements as $statement) {
                $statement = trim($statement);
                // 跳过空行和注释
                if (empty($statement) || str_starts_with($statement, '--')) {
                    continue;
                }
                // 跳过创建数据库的语句（已在配置中指定）
                if (str_starts_with(strtolower($statement), 'create database')) {
                    continue;
                }
                // 跳过 use 语句（已在配置中指定）
                if (str_starts_with(strtolower($statement), 'use ')) {
                    continue;
                }
                $pdo->exec($statement);
            }
            
            // 更新管理员密码（database.sql 已创建 admin 账号）
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $stmt->execute([$hashedPassword]);
            
            $isInstalled = true;
            file_put_contents(__DIR__ . '/install.lock', 'locked');
            
        } catch (PDOException $e) {
            $error = "数据库安装失败: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "安装失败: " . $e->getMessage();
        }
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
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit">开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
