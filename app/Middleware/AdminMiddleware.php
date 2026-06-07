<?php
namespace App\Middleware;

class AdminMiddleware extends AuthMiddleware {
    public function handle(): bool {
        if (!parent::handle()) {
            return false;
        }

        if (!isAdmin()) {
            $_SESSION['error'] = '您没有权限访问此页面';
            redirect('/');
        }
        
        return true;
    }
}
