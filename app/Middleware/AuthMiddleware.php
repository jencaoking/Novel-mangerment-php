<?php
namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface {
    public function handle() {
        if (!isLoggedIn()) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/') === 0 && strpos($uri, '//') !== 0) {
                $_SESSION['redirect_url'] = $uri;
            }
            redirect('/login');
            exit();
        }
    }
}
