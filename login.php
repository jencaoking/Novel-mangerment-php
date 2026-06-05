<?php
/**
 * BookMusic Mall - 用户登录页面
 */

require_once 'includes/auth.php';

// 如果已登录，重定向到首页
if (isLoggedIn()) {
    redirect('/');
}

$error = '';

// 处理登录表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // 验证输入
        if (empty($username) || empty($password)) {
            $error = '请填写用户名和密码';
        } else {
            // 执行登录
            $result = login($username, $password, $remember);
            
            if ($result['success']) {
                // 登录成功，重定向到首页或之前的页面
                $redirectUrl = $_SESSION['redirect_url'] ?? '/';
                unset($_SESSION['redirect_url']);
                redirect($redirectUrl);
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
    <title>登录 - BookMusic Mall</title>
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

    <!-- 登录表单 -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>欢迎回来</h1>
                    <p>登录您的 BookMusic Mall 账户</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form" data-validate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="username">用户名 / 邮箱</label>
                        <input type="text" id="username" name="username" required 
                               placeholder="输入用户名或邮箱"
                               value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="输入密码">
                    </div>
                    
                    <div class="form-group form-checkbox">
                        <label>
                            <input type="checkbox" name="remember" id="remember" 
                                   <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                            <span>记住我（7天内自动登录）</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        登录
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>还没有账户？ <a href="/register.php">立即注册</a></p>
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
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
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
        
        .form-checkbox label {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            cursor: pointer;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
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
