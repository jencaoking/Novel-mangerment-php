<?php
namespace App\Middleware;

use App\Models\UserModel;
use Core\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface {
    /**
     * 不需要强制改密即可访问的白名单路径
     * (这些是"让用户能改密"的路径, 必须放行否则会死循环)
     */
    private const PASSWORD_CHANGE_WHITELIST = [
        '/user/profile',
        '/user/change-password',
        '/logout',
    ];

    public function handle(): bool {
        if (!isLoggedIn()) {
            $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
            $safePath = $parsedUrl['path'] ?? '/';
            $safeUri = $safePath . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
            $_SESSION['redirect_url'] = $safeUri;
            redirect('/login');
        }

        // 强制改密拦截: 标记的用户只能访问白名单
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if ($this->isPasswordChangeRequired()) {
            if (!$this->isWhitelisted($currentPath)) {
                $_SESSION['info'] = '为了您的账户安全, 登录后必须先修改密码才能使用其他功能。';
                redirect('/user/profile#change-password');
            }
        }

        return true;
    }

    /**
     * 当前登录用户是否被标记为必须改密
     */
    private function isPasswordChangeRequired(): bool {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return false;
        }
        $userModel = new UserModel();
        return $userModel->mustChangePassword((int)$userId);
    }

    /**
     * 当前路径是否在白名单中
     */
    private function isWhitelisted(string $path): bool {
        foreach (self::PASSWORD_CHANGE_WHITELIST as $allow) {
            if ($path === $allow || strpos($path, $allow . '?') === 0) {
                return true;
            }
        }
        return false;
    }
}
