<?php
// core/Middleware/MiddlewareInterface.php
namespace Core\Middleware;

interface MiddlewareInterface {
    /**
     * 处理请求
     * @return bool true 表示验证通过放行，false 表示验证失败（由实现类处理响应）
     */
    public function handle(): bool;
}
