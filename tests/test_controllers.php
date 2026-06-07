<?php
/**
 * BookMusic Mall - 控制器测试
 * 包含 AuthController 和 ProductController 测试
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

echo "=====================================\n";
echo "控制器测试\n";
echo "=====================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// 测试1: AuthController 类存在性
$tests[] = [
    'name' => 'AuthController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\AuthController');
    }
];

// 测试2: AuthController 方法存在性
$tests[] = [
    'name' => 'AuthController 方法存在性测试',
    'test' => function() {
        if (!class_exists('App\Controllers\AuthController')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Controllers\AuthController');
        
        return $reflection->hasMethod('showLogin') &&
               $reflection->hasMethod('processLogin') &&
               $reflection->hasMethod('showRegister') &&
               $reflection->hasMethod('processRegister') &&
               $reflection->hasMethod('logout') &&
               $reflection->hasMethod('captcha');
    }
];

// 测试3: AuthController 构造函数（无参数）
$tests[] = [
    'name' => 'AuthController 构造函数测试',
    'test' => function() {
        if (!class_exists('App\Controllers\AuthController')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Controllers\AuthController');
        $constructor = $reflection->getConstructor();
        
        // AuthController 不需要构造函数参数
        return $constructor === null || $constructor->getNumberOfRequiredParameters() === 0;
    }
];

// 测试4: ProductController 类存在性
$tests[] = [
    'name' => 'ProductController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\ProductController');
    }
];

// 测试5: ProductController 方法存在性
$tests[] = [
    'name' => 'ProductController 方法存在性测试',
    'test' => function() {
        if (!class_exists('App\Controllers\ProductController')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Controllers\ProductController');
        
        return $reflection->hasMethod('__construct') &&
               $reflection->hasMethod('novels') &&
               $reflection->hasMethod('music') &&
               $reflection->hasMethod('show') &&
               $reflection->hasMethod('submitReview') &&
               $reflection->hasMethod('buy') &&
               $reflection->hasMethod('download');
    }
];

// 测试6: ProductController 构造函数依赖注入
$tests[] = [
    'name' => 'ProductController 构造函数依赖注入测试',
    'test' => function() {
        if (!class_exists('App\Controllers\ProductController')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Controllers\ProductController');
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return false;
        }
        
        $parameters = $constructor->getParameters();
        
        // 检查是否有5个参数
        if (count($parameters) !== 5) {
            return false;
        }
        
        // 检查参数类型
        $paramTypes = [];
        foreach ($parameters as $param) {
            $type = $param->getType();
            if ($type) {
                // 兼容不同PHP版本的类型获取方式
                $typeName = method_exists($type, 'getName') ? $type->getName() : (string)$type;
                $paramTypes[] = $typeName;
            }
        }
        
        // 检查是否有正确的依赖类型
        $expectedTypes = [
            'App\Models\ProductModel',
            'App\Models\CategoryModel',
            'App\Models\OrderModel',
            'App\Models\DownloadModel',
            'App\Models\ReviewModel'
        ];
        
        foreach ($expectedTypes as $expectedType) {
            if (!in_array($expectedType, $paramTypes)) {
                return false;
            }
        }
        
        return true;
    }
];

// 测试7: UserController 类存在性
$tests[] = [
    'name' => 'UserController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\UserController');
    }
];

// 测试8: HomeController 类存在性
$tests[] = [
    'name' => 'HomeController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\HomeController');
    }
];

// 测试9: AdminController 类存在性
$tests[] = [
    'name' => 'AdminController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\AdminController');
    }
];

// 测试10: CartController 类存在性
$tests[] = [
    'name' => 'CartController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\CartController');
    }
];

// 测试11: PaymentController 类存在性
$tests[] = [
    'name' => 'PaymentController 类存在性测试',
    'test' => function() {
        return class_exists('App\Controllers\PaymentController');
    }
];

// 测试12: 所有控制器命名空间正确
$tests[] = [
    'name' => '控制器命名空间测试',
    'test' => function() {
        $controllers = [
            'App\Controllers\AuthController',
            'App\Controllers\ProductController',
            'App\Controllers\UserController',
            'App\Controllers\HomeController',
            'App\Controllers\AdminController',
            'App\Controllers\CartController',
            'App\Controllers\PaymentController'
        ];
        
        foreach ($controllers as $controller) {
            if (!class_exists($controller)) {
                continue;
            }
            
            $reflection = new ReflectionClass($controller);
            if ($reflection->getNamespaceName() !== 'App\Controllers') {
                return false;
            }
        }
        
        return true;
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