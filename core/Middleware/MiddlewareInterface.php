<?php
// core/Middleware/MiddlewareInterface.php
namespace Core\Middleware;

interface MiddlewareInterface {
    /**
     * 处理请求
     * 如果验证失败，直接在内部 redirect() 并 exit()
     * 如果验证成功，什么都不用做，直接放行
     */
    public function handle();
}
