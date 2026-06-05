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
        setcookie('remember_token', $token, time() + 7 * 86400, '/', '', false, true);
        
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
        
        return ['success' => true, 'user_id' => $userId];
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => '注册失败，请稍后再试'];
    }
}

function isLoggedIn() {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '') {
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
    
    setcookie('remember_token', '', time() - 3600, '/');
    
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
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard';
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