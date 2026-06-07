<?php
namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface {
    public function handle(): bool {
        if (!isLoggedIn()) {
            $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
            $safePath = $parsedUrl['path'] ?? '/';
            $safeUri = $safePath . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
            $_SESSION['redirect_url'] = $safeUri;
            redirect('/login');
        }
        return true;
    }
}
