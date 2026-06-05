<?php
/**
 * BookMusic Mall - 个人资料
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

$userId = getCurrentUserId();
$error = '';

// 获取用户信息
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = '安全验证失败';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($username) || empty($email)) {
            $error = '请填写所有必填字段';
        } elseif (!isValidUsername($username)) {
            $error = '用户名格式不正确';
        } elseif (!isValidEmail($email)) {
            $error = '邮箱格式不正确';
        } else {
            // 检查用户名是否已被使用
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                $error = '用户名已被使用';
            } else {
                // 检查邮箱是否已被使用
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $error = '邮箱已被使用';
                } else {
                    // 更新用户信息
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    if ($stmt->execute([$username, $email, $userId])) {
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        redirect('/user/profile.php?success=1');
                    } else {
                        $error = '更新失败，请稍后再试';
                    }
                }
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
    <title>个人资料 - BookMusic Mall</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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
                    <a href="/user/" class="btn btn-secondary">用户中心</a>
                    <a href="/logout.php" class="btn btn-outline">退出</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="user-center">
        <div class="container">
            <div class="user-layout">
                <div class="user-sidebar">
                    <div class="user-info">
                        <img src="/uploads/avatar/<?= e($user['avatar'] ?: 'default.jpg') ?>" 
                             alt="<?= e($user['username']) ?>" class="user-avatar">
                        <h3><?= e($user['username']) ?></h3>
                        <p><?= e($user['email']) ?></p>
                    </div>
                    <nav class="user-nav">
                        <a href="/user/">概览</a>
                        <a href="/user/profile.php" class="active">个人资料</a>
                        <a href="/user/orders.php">我的订单</a>
                        <a href="/user/downloads.php">我的下载</a>
                        <a href="/user/password.php">修改密码</a>
                    </nav>
                </div>
                
                <div class="user-content">
                    <h1>个人资料</h1>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?= e($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">资料更新成功</div>
                    <?php endif; ?>
                    
                    <form method="POST" class="profile-form">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" name="username" value="<?= e($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>邮箱</label>
                            <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="/assets/js/main.js"></script>
    <style>
        .user-center { padding-top: 100px; min-height: calc(100vh - 80px); }
        .user-layout { display: grid; grid-template-columns: 250px 1fr; gap: var(--spacing-2xl); }
        .user-sidebar { position: sticky; top: 100px; height: fit-content; }
        .user-info { text-align: center; padding: var(--spacing-xl); background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: var(--spacing-lg); }
        .user-avatar { width: 100px; height: 100px; border-radius: 50%; margin-bottom: var(--spacing-md); }
        .user-nav { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: var(--spacing-md); }
        .user-nav a { display: block; padding: var(--spacing-md); color: var(--gray-700); border-radius: var(--radius-md); transition: all var(--transition-fast); }
        .user-nav a:hover, .user-nav a.active { background: var(--accent-light); color: var(--accent-dark); }
        .user-content { background: var(--white); padding: var(--spacing-2xl); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .profile-form { max-width: 500px; }
        .form-group { margin-bottom: var(--spacing-lg); }
        .form-group label { display: block; margin-bottom: var(--spacing-sm); font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; border: 2px solid var(--gray-200); border-radius: var(--radius-md); }
        .alert { padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); }
        .alert-error { background: #fee; color: var(--danger-color); }
        .alert-success { background: #efe; color: var(--success-color); }
        @media (max-width: 768px) { .user-layout { grid-template-columns: 1fr; } }
    </style>
</body>
</html>
