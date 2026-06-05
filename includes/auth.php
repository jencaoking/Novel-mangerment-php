<?php
/**
 * BookMusic Mall - 认证授权模块
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// 启动Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 用户登录
 */
function login($username, $password, $remember = false) {
    $db = getDB();
    
    // 查找用户（支持用户名或邮箱登录）
    $stmt = $db->prepare("
        SELECT id, username, email, password, avatar, role, status 
        FROM users 
        WHERE (username = ? OR email = ?) AND status = 1
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => '用户不存在或已被禁用'];
    }
    
    // 验证密码
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => '密码错误'];
    }
    
    // 更新最后登录时间
    $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    // 设置Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['role'] = $user['role'];
    
    // 记住登录
    if ($remember) {
        $token = generateRandomString(32);
        setcookie('remember_token', $token, time() + 7 * 86400, '/', '', false, true);
        // 这里可以存储token到数据库用于验证
    }
    
    return ['success' => true, 'user' => $user];
}

/**
 * 用户注册
 */
function register($username, $email, $password) {
    $db = getDB();
    
    // 验证用户名格式
    if (!isValidUsername($username)) {
        return ['success' => false, 'message' => '用户名格式不正确'];
    }
    
    // 验证邮箱格式
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => '邮箱格式不正确'];
    }
    
    // 检查用户名是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => '用户名已被使用'];
    }
    
    // 检查邮箱是否已存在
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => '邮箱已被使用'];
    }
    
    // 加密密码
    $hashedPassword = hashPassword($password);
    
    // 插入用户数据
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, role, status) 
        VALUES (?, ?, ?, 'user', 1)
    ");
    
    try {
        $stmt->execute([$username, $email, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '注册失败，请稍后再试'];
    }
}

/**
 * 检查用户是否登录
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 检查用户是否是管理员
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * 获取当前用户ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * 获取当前用户信息
 */
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

/**
 * 用户登出
 */
function logout() {
    // 清除Session
    $_SESSION = [];
    
    // 删除Session Cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // 删除记住登录的Cookie
    setcookie('remember_token', '', time() - 3600, '/');
    
    // 销毁Session
    session_destroy();
}

/**
 * 要求用户登录
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

/**
 * 要求管理员权限
 */
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        redirect('/admin/login.php');
    }
}

/**
 * 检查用户是否购买了商品
 */
function hasPurchased($userId, $productId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT id FROM orders 
        WHERE user_id = ? AND product_id = ? AND status = 'paid'
    ");
    $stmt->execute([$userId, $productId]);
    
    return $stmt->fetch() !== false;
}

/**
 * 获取用户购买的商品列表
 */
function getUserPurchasedProducts($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT p.*, o.order_no, o.pay_time
        FROM products p
        INNER JOIN orders o ON p.id = o.product_id
        WHERE o.user_id = ? AND o.status = 'paid'
        ORDER BY o.pay_time DESC
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll();
}
