<?php
/**
 * BookMusic Mall - 中间件测试
 * 包含 AuthMiddleware 和 AdminMiddleware 测试
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

echo "=====================================\n";
echo "中间件测试\n";
echo "=====================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// 测试1: MiddlewareInterface 接口存在性
$tests[] = [
    'name' => 'MiddlewareInterface 接口存在性测试',
    'test' => function() {
        return interface_exists('Core\Middleware\MiddlewareInterface');
    }
];

// 测试2: MiddlewareInterface 有 handle 方法声明
$tests[] = [
    'name' => 'MiddlewareInterface handle 方法声明测试',
    'test' => function() {
        if (!interface_exists('Core\Middleware\MiddlewareInterface')) {
            return false;
        }
        
        // 使用 ReflectionClass 来检查接口（兼容所有PHP版本）
        $reflection = new ReflectionClass('Core\Middleware\MiddlewareInterface');
        return $reflection->hasMethod('handle');
    }
];

// 测试3: AuthMiddleware 类存在性
$tests[] = [
    'name' => 'AuthMiddleware 类存在性测试',
    'test' => function() {
        return class_exists('App\Middleware\AuthMiddleware');
    }
];

// 测试4: AuthMiddleware 实现 MiddlewareInterface
$tests[] = [
    'name' => 'AuthMiddleware 实现 MiddlewareInterface',
    'test' => function() {
        if (!class_exists('App\Middleware\AuthMiddleware')) {
            return false;
        }
        
        return is_subclass_of('App\Middleware\AuthMiddleware', 'Core\Middleware\MiddlewareInterface');
    }
];

// 测试5: AuthMiddleware 有 handle 方法
$tests[] = [
    'name' => 'AuthMiddleware handle 方法存在性测试',
    'test' => function() {
        if (!class_exists('App\Middleware\AuthMiddleware')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Middleware\AuthMiddleware');
        return $reflection->hasMethod('handle');
    }
];

// 测试6: AdminMiddleware 类存在性
$tests[] = [
    'name' => 'AdminMiddleware 类存在性测试',
    'test' => function() {
        return class_exists('App\Middleware\AdminMiddleware');
    }
];

// 测试7: AdminMiddleware 实现 MiddlewareInterface
$tests[] = [
    'name' => 'AdminMiddleware 实现 MiddlewareInterface',
    'test' => function() {
        if (!class_exists('App\Middleware\AdminMiddleware')) {
            return false;
        }
        
        return is_subclass_of('App\Middleware\AdminMiddleware', 'Core\Middleware\MiddlewareInterface');
    }
];

// 测试8: AdminMiddleware 有 handle 方法
$tests[] = [
    'name' => 'AdminMiddleware handle 方法存在性测试',
    'test' => function() {
        if (!class_exists('App\Middleware\AdminMiddleware')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Middleware\AdminMiddleware');
        return $reflection->hasMethod('handle');
    }
];

// 测试9: AuthMiddleware 命名空间正确
$tests[] = [
    'name' => 'AuthMiddleware 命名空间测试',
    'test' => function() {
        if (!class_exists('App\Middleware\AuthMiddleware')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Middleware\AuthMiddleware');
        return $reflection->getNamespaceName() === 'App\Middleware';
    }
];

// 测试10: AdminMiddleware 命名空间正确
$tests[] = [
    'name' => 'AdminMiddleware 命名空间测试',
    'test' => function() {
        if (!class_exists('App\Middleware\AdminMiddleware')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Middleware\AdminMiddleware');
        return $reflection->getNamespaceName() === 'App\Middleware';
    }
];

// 测试11: MiddlewareInterface 命名空间正确
$tests[] = [
    'name' => 'MiddlewareInterface 命名空间测试',
    'test' => function() {
        if (!interface_exists('Core\Middleware\MiddlewareInterface')) {
            return false;
        }
        
        $reflection = new ReflectionClass('Core\Middleware\MiddlewareInterface');
        return $reflection->getNamespaceName() === 'Core\Middleware';
    }
];

// 测试12: AuthMiddleware 构造函数（无参数）
$tests[] = [
    'name' => 'AuthMiddleware 构造函数测试',
    'test' => function() {
        if (!class_exists('App\Middleware\AuthMiddleware')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Middleware\AuthMiddleware');
        $constructor = $reflection->getConstructor();
        
        return $constructor === null || $constructor->getNumberOfRequiredParameters() === 0;
    }
];

// 测试13: AdminMiddleware 构造函数（无参数）
$tests[] = [
    'name' => 'AdminMiddleware 构造函数测试',
    'test' => function() {
        if (!class_exists('App\Middleware\AdminMiddleware')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Middleware\AdminMiddleware');
        $constructor = $reflection->getConstructor();
        
        return $constructor === null || $constructor->getNumberOfRequiredParameters() === 0;
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

// 输出结果码
exit($failed > 0 ? 1 : 0);
?>