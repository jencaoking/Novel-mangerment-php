<?php
namespace App\Middleware;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        // 防止点击劫持
        header('X-Frame-Options: SAMEORIGIN');
        
        // 防止 MIME 嗅探
        header('X-Content-Type-Options: nosniff');
        
        // XSS 防护
        header('X-XSS-Protection: 1; mode=block');
        
        // 引用策略
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // 权限策略
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // HSTS（仅在 HTTPS 下启用）
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        return true;
    }
}
