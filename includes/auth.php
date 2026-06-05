<?php
/**
 * BookMusic Mall - 认证授权模块
 */

require_once __DIR__ . '/functions.php';

use App\Models\UserModel;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login($username, $password, $remember = false) {
    $userModel = new UserModel();
    $user = $userModel->findByUsernameOrEmail($username);
    
    if (!$user) {
        return ['success' => false, 'message' => '用户不存在或已被禁用'];
    }
    
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => '密码错误'];
    }
    
    $userModel->updateLastLogin($user['id']);
    
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['role'] = $user['role'];
    
    if ($remember) {
        $token = generateRandomString(32);
        $hashedToken = hash('sha256', $token);
        // 根据是否 HTTPS 动态设置 Secure 标志
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie('remember_token', $token, time() + 7 * 86400, '/', '', $secure, true);
        
        $expire = date('Y-m-d H:i:s', time() + 7 * 86400);
        $userModel->updateRememberToken($user['id'], $hashedToken, $expire);
    }
    
    return ['success' => true, 'user' => $user];
}

function register($username, $email, $password) {
    $userModel = new UserModel();
    
    if (!isValidUsername($username)) {
        return ['success' => false, 'message' => '用户名格式不正确'];
    }
    
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => '邮箱格式不正确'];
    }
    
    if ($userModel->isUsernameExists($username)) {
        return ['success' => false, 'message' => '用户名已被使用'];
    }
    
    if ($userModel->isEmailExists($email)) {
        return ['success' => false, 'message' => '邮箱已被使用'];
    }
    
    $hashedPassword = hashPassword($password);
    
    try {
        $userId = $userModel->create([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'user',
            'status' => 1
        ]);
        
        // 发送欢迎邮件
        sendWelcomeEmail($username, $email);
        
        return ['success' => true, 'user_id' => $userId];
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => '注册失败，请稍后再试'];
    }
}

/**
 * 发送欢迎邮件
 */
function sendWelcomeEmail($username, $email) {
    $welcomeHtml = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #333;'>欢迎加入 BookMusic Mall!</h2>
            <p>亲爱的 {$username}，</p>
            <p>感谢您注册我们的平台。现在您可以：</p>
            <ul>
                <li>浏览精选小说和音乐</li>
                <li>购买您喜欢的数字内容</li>
                <li>享受优质的阅读和听觉体验</li>
            </ul>
            <p>祝您使用愉快！</p>
            <hr>
            <p style='color: #999; font-size: 12px;'>BookMusic Mall 团队</p>
        </div>
    ";
    
    // 异步发送邮件，避免阻塞注册流程
    try {
        sendEmailWithResend($email, '欢迎加入 BookMusic Mall', $welcomeHtml);
    } catch (\Exception $e) {
        // 记录错误但不影响注册流程
        error_log('欢迎邮件发送失败: ' . $e->getMessage());
    }
}

function isLoggedIn() {
    if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return true;
    }
    return checkAutoLogin();
}

function checkAutoLogin() {
    if (!isset($_COOKIE['remember_token']) || empty($_COOKIE['remember_token'])) {
        return false;
    }
    
    $userModel = new UserModel();
    $token = $_COOKIE['remember_token'];
    $hashedToken = hash('sha256', $token);
    
    $user = $userModel->findByRememberToken($hashedToken);
    
    if (!$user) {
        return false;
    }
    
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['role'] = $user['role'];
    
    return true;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'avatar' => $_SESSION['avatar'],
        'role' => $_SESSION['role']
    ];
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // 登出时清除 remember_token，保持与设置时参数一致
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie('remember_token', '', time() - 3600, '/', '', $secure, true);
    
    if ($userId) {
        $userModel = new UserModel();
        $userModel->clearRememberToken($userId);
    }
    
    session_destroy();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        // 过滤 REQUEST_URI，只允许站内路径
        $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard';
        // 移除可能的协议和域名部分，防止开放重定向
        $parsedUrl = parse_url($redirectUrl);
        if ($parsedUrl && isset($parsedUrl['path'])) {
            $redirectUrl = $parsedUrl['path'];
            if (isset($parsedUrl['query'])) {
                $redirectUrl .= '?' . $parsedUrl['query'];
            }
        }
        $_SESSION['redirect_url'] = $redirectUrl;
        redirect('/login');
    }
}

function hasPurchased($userId, $productId) {
    $orderModel = new \App\Models\OrderModel();
    return $orderModel->hasPurchased($userId, $productId);
}

function getUserPurchasedProducts($userId) {
    $orderModel = new \App\Models\OrderModel();
    return $orderModel->getUserPurchasedProducts($userId);
}