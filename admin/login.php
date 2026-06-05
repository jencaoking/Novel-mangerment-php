<?php
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($username, $password);
    
    if ($result['success']) {
        if (isAdmin()) {
            redirect('dashboard.php');
        } else {
            logout();
            $error = '您没有管理员权限';
        }
    } else {
        $error = $result['message'];
    }
}

if (isLoggedIn() && isAdmin()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - BookMusic Mall</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="bi bi-music-note-beamed"></i>
                    <h1>BookMusic Admin</h1>
                </div>
                <p class="login-subtitle">小说与音乐数字内容销售平台</p>
            </div>
            
            <form method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="用户名" required autofocus>
                    <label for="username">
                        <i class="bi bi-person"></i> 用户名
                    </label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="密码" required>
                    <label for="password">
                        <i class="bi bi-lock"></i> 密码
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> 登录
                </button>
            </form>
            
            <div class="login-footer">
                <a href="/" class="btn btn-link">
                    <i class="bi bi-arrow-left"></i> 返回前台
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
