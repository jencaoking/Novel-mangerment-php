<?php
/**
 * BookMusic Mall - Router 路由测试
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Container.php';
require_once __DIR__ . '/../core/Router.php';

echo "=====================================\n";
echo "Router 路由测试\n";
echo "=====================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// 测试1: 路由实例化
$tests[] = [
    'name' => '路由实例化测试',
    'test' => function() {
        $router = new Core\Router();
        return $router instanceof Core\Router;
    }
];

// 测试2: 注册和匹配 GET 路由
$tests[] = [
    'name' => '注册和匹配 GET 路由测试',
    'test' => function() {
        $router = new Core\Router();
        $matched = false;
        
        $router->get('/test', function() use (&$matched) {
            $matched = true;
        });
        
        ob_start();
        $router->dispatch('/test', 'GET');
        ob_end_clean();
        
        return $matched;
    }
];

// 测试3: 注册和匹配 POST 路由
$tests[] = [
    'name' => '注册和匹配 POST 路由测试',
    'test' => function() {
        $router = new Core\Router();
        $matched = false;
        
        $router->post('/api/test', function() use (&$matched) {
            $matched = true;
        });
        
        ob_start();
        $router->dispatch('/api/test', 'POST');
        ob_end_clean();
        
        return $matched;
    }
];

// 测试4: 路由参数匹配
$tests[] = [
    'name' => '路由参数匹配测试',
    'test' => function() {
        $router = new Core\Router();
        $capturedId = null;
        
        $router->get('/user/{id}', function($id) use (&$capturedId) {
            $capturedId = $id;
        });
        
        ob_start();
        $router->dispatch('/user/123', 'GET');
        ob_end_clean();
        
        return $capturedId === '123';
    }
];

// 测试5: 多个路由参数
$tests[] = [
    'name' => '多个路由参数测试',
    'test' => function() {
        $router = new Core\Router();
        $captured = [];
        
        $router->get('/user/{userId}/order/{orderId}', function($userId, $orderId) use (&$captured) {
            $captured = ['userId' => $userId, 'orderId' => $orderId];
        });
        
        ob_start();
        $router->dispatch('/user/456/order/789', 'GET');
        ob_end_clean();
        
        return $captured['userId'] === '456' && $captured['orderId'] === '789';
    }
];

// 测试6: 未定义路由返回404
$tests[] = [
    'name' => '未定义路由返回404测试',
    'test' => function() {
        $router = new Core\Router();
        
        ob_start();
        $router->dispatch('/undefined-route', 'GET');
        $output = ob_end_clean();
        
        return strpos($output, '404') !== false;
    }
];

// 测试7: 错误请求方法
$tests[] = [
    'name' => '错误请求方法测试',
    'test' => function() {
        $router = new Core\Router();
        $matched = false;
        
        $router->get('/only-get', function() use (&$matched) {
            $matched = true;
        });
        
        ob_start();
        $router->dispatch('/only-get', 'POST');
        $output = ob_end_clean();
        
        return !$matched && strpos($output, '404') !== false;
    }
];

// 测试8: 回调函数返回值
$tests[] = [
    'name' => '回调函数返回值测试',
    'test' => function() {
        $router = new Core\Router();
        
        $router->get('/return-test', function() {
            return 'test-response';
        });
        
        ob_start();
        $result = $router->dispatch('/return-test', 'GET');
        ob_end_clean();
        
        return $result === 'test-response';
    }
];

// 测试9: 路由带中间件（模拟中间件）
$tests[] = [
    'name' => '路由带中间件测试',
    'test' => function() {
        $router = new Core\Router();
        $middlewareCalled = false;
        $handlerCalled = false;
        
        // 创建测试中间件
        class TestMiddleware {
            public function handle() {
                global $middlewareCalled;
                $middlewareCalled = true;
                return true; // 通过
            }
        }
        
        $router->get('/middleware-test', function() use (&$handlerCalled) {
            $handlerCalled = true;
        }, ['TestMiddleware']);
        
        ob_start();
        $router->dispatch('/middleware-test', 'GET');
        ob_end_clean();
        
        return $middlewareCalled && $handlerCalled;
    }
];

// 测试10: 中间件拒绝访问
$tests[] = [
    'name' => '中间件拒绝访问测试',
    'test' => function() {
        $router = new Core\Router();
        $handlerCalled = false;
        
        // 创建拒绝访问的中间件
        class DenyMiddleware {
            public function handle() {
                return false; // 拒绝
            }
        }
        
        $router->get('/deny-test', function() use (&$handlerCalled) {
            $handlerCalled = true;
        }, ['DenyMiddleware']);
        
        ob_start();
        $router->dispatch('/deny-test', 'GET');
        ob_end_clean();
        
        return !$handlerCalled;
    }
];

// 执行测试
foreach ($tests as $index => $test) {
    echo "测试 " . ($index + 1) . ": " . $test['name'] . "... ";
    try {
        $result = $test['test']();
        if ($result) {
            echo "✓ 通过\n";
            $passed++;
        } else {
            echo "✗ 失败\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "✗ 异常: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n=====================================\n";
echo "测试结果: {$passed} 通过, {$failed} 失败\n";
echo "=====================================\n";

exit($failed > 0 ? 1 : 0);
?>