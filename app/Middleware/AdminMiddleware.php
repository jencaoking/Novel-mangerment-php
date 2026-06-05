<?php
namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;

class AdminMiddleware implements MiddlewareInterface {
    public function handle() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('/login');
            exit();
        }
    }
}
