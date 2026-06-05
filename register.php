<?php
/**
 * BookMusic Mall - 用户注册页面
 */

require_once 'includes/auth.php';

// 如果已登录，重定向到首页
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

// 处理注册表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // 验证输入
        if (empty($username) || empty($email) || empty($password)) {
            $error = '请填写所有必填字段';
        } elseif (!isValidUsername($username)) {
            $error = '用户名格式不正确（3-20个字符，支持中文、英文、数字和下划线）';
        } elseif (!isValidEmail($email)) {
            $error = '请输入有效的邮箱地址';
        } elseif (strlen($password) < 6) {
            $error = '密码至少需要6个字符';
        } elseif ($password !== $confirmPassword) {
            $error = '两次输入的密码不一致';
        } else {
            // 执行注册
            $result = register($username, $email, $password);
            
            if ($result['success']) {
                $success = '注册成功！即将跳转到登录页面...';
                // 3秒后跳转到登录页
                header('Refresh: 3; url=/login.php');
            } else {
                $error = $result['message'];
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
    <title>注册 - BookMusic Mall</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="/" class="navbar-brand">
                    <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 2C10.06 2 2 10.06 2 20s8.06 18 18 18 18-8.06 18-18S29.94 2 20 2zm0 4c7.73 0 14 6.27 14 14s-6.27 14-14 14S6 25.73 6 20 12.27 6 20 6zm-4 6v16l12-8-12-8z"/>
                    </svg>
                    <span>BookMusic Mall</span>
                </a>
                
                <ul class="navbar-nav">
                    <li><a href="/">首页</a></li>
                    <li><a href="/novels.php">小说</a></li>
                    <li><a href="/music.php">音乐</a></li>
                </ul>
                
                <div class="navbar-actions">
                    <a href="/login.php" class="btn btn-secondary">登录</a>
                    <a href="/register.php" class="btn btn-primary">注册</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 注册表单 -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>创建账户</h1>
                    <p>加入 BookMusic Mall，开启您的数字内容之旅</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" required 
                               placeholder="3-20个字符，支持中文、英文、数字"
                               value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">邮箱地址</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="your@email.com"
                               value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="至少6个字符" data-min-length="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="再次输入密码">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        创建账户
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>已有账户？ <a href="/login.php">立即登录</a></p>
                </div>
            </div>
        </div>
    </section>

    <script src="/assets/js/main.js"></script>
    
    <style>
        /* 认证页面样式 */
        .auth-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            padding-top: 80px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 480px;
            padding: var(--spacing-lg);
        }
        
        .auth-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            box-shadow: var(--shadow-xl);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }
        
        .auth-header h1 {
            font-size: 2rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .auth-header p {
            color: var(--gray-600);
            margin-bottom: 0;
        }
        
        .auth-form {
            margin-bottom: var(--spacing-xl);
        }
        
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all var(--transition-fast);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }
        
        .form-group input.error {
            border-color: var(--danger-color);
        }
        
        .btn-block {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
        }
        
        .auth-footer {
            text-align: center;
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--gray-200);
        }
        
        .alert {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .alert-error {
            background: #fee;
            color: var(--danger-color);
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: var(--success-color);
            border: 1px solid #cfc;
        }
    </style>
</body>
</html>
