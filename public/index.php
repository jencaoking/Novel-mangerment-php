<?php
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once '../core/Autoloader.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ==========================================
// 🛡️ 全局异常与错误拦截网
// ==========================================
// 1. 关闭直接在页面显示错误（防止信息泄露）
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. 捕获所有未被 catch 的异常
set_exception_handler(function ($exception) {
    // 将详细错误写入日志文件 (logs/error.log)
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logMessage = sprintf(
        "[%s] %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    error_log($logMessage, 3, $logDir . '/error.log');

    // 清空可能已经输出的半截 HTML
    if (ob_get_length()) ob_clean();
    
    // 给用户展示友好的 500 页面
    http_response_code(500);
    echo "<div style='text-align:center; margin-top:100px; font-family:sans-serif;'>";
    echo "<h1>服务器开小差了 (500)</h1>";
    echo "<p>系统遇到了一点小问题，工程师正在紧急抢修，请稍后再试。</p>";
    echo "</div>";
    exit;
});

// 3. 将普通的 PHP Error 转换为 Exception，统一交给上面的函数处理
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) { return; }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
// ==========================================

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
