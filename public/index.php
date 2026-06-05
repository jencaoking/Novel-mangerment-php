<?php
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once '../core/Autoloader.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

try {
    $router = new \Core\Router();
    
    // ==========================================
    // 1. 前台公开路由（商品模块）
    // ==========================================
    $router->get('/', 'HomeController@index');
    $router->get('/music', 'ProductController@music');
    $router->get('/novels', 'ProductController@novels');
    $router->get('/product/{id}', 'ProductController@show');
    $router->post('/product/{id}', 'ProductController@buy');
    
    // ==========================================
    // 2. 认证模块路由（登录/注册/登出）
    // ==========================================
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@processLogin');
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@processRegister');
    $router->get('/logout', 'AuthController@logout');
    
    // ==========================================
    // 3. 用户中心路由（需要普通登录权限）
    // ==========================================
    $router->get('/user', 'UserController@index');
    $router->get('/user/profile', 'UserController@profile');
    $router->post('/user/profile', 'UserController@updateProfile');
    $router->get('/user/orders', 'UserController@orders');
    $router->get('/user/downloads', 'UserController@downloads');
    
    // ==========================================
    // 4. 后台管理路由（需要管理员权限）
    // ==========================================
    $router->get('/admin/dashboard', 'AdminController@dashboard');
    $router->get('/admin/products', 'AdminController@products');
    $router->get('/admin/users', 'AdminController@users');
    $router->post('/admin/users', 'AdminController@toggleUserStatus');
    $router->get('/admin/orders', 'AdminController@orders');
    $router->post('/admin/orders/update', 'AdminController@updateOrderStatus');
    $router->get('/admin/stats', 'AdminController@stats');
    $router->post('/admin/upload', 'AdminController@upload');
    
    // 启动路由
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $router->dispatch($uri, $method);
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo "<h1>服务器内部错误</h1>";
    echo "<p>抱歉，服务器遇到了一个内部错误，请稍后重试。</p>";
}
