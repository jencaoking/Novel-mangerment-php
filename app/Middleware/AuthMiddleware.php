<?php
namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface {
    public function handle() {
        // 使用你 includes/auth.php 里的原生函数
        if (!isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
            exit(); // 关键：拦截不合格请求，终止框架继续向下执行
        }
    }
}
