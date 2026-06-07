<?php
/**
 * BookMusic Mall - Container 依赖注入容器测试
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Container.php';

echo "=====================================\n";
echo "Container 依赖注入容器测试\n";
echo "=====================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// 测试1: 容器实例化
$tests[] = [
    'name' => '容器实例化测试',
    'test' => function() {
        $container = new Core\Container();
        return $container instanceof Core\Container;
    }
];

// 测试2: 解析简单类
$tests[] = [
    'name' => '解析简单类测试',
    'test' => function() {
        $container = new Core\Container();
        $result = $container->get('stdClass');
        return $result instanceof stdClass;
    }
];

// 测试3: 单例模式测试
$tests[] = [
    'name' => '单例模式测试',
    'test' => function() {
        $container = new Core\Container();
        $instance1 = $container->get('stdClass');
        $instance2 = $container->get('stdClass');
        return $instance1 === $instance2;
    }
];

// 测试4: 解析带依赖的类
class DependencyClass {
    public $value = 'dependency';
}

class DependentClass {
    public $dependency;
    public function __construct(DependencyClass $dep) {
        $this->dependency = $dep;
    }
}

$tests[] = [
    'name' => '解析带依赖的类测试',
    'test' => function() {
        $container = new Core\Container();
        $instance = $container->get('DependentClass');
        return $instance instanceof DependentClass && $instance->dependency instanceof DependencyClass;
    }
];

// 测试5: 递归依赖解析
class GrandParent {
    public $name = 'grandparent';
}

class ParentClass {
    public $grandparent;
    public function __construct(GrandParent $gp) {
        $this->grandparent = $gp;
    }
}

class ChildClass {
    public $parent;
    public function __construct(ParentClass $p) {
        $this->parent = $p;
    }
}

$tests[] = [
    'name' => '递归依赖解析测试',
    'test' => function() {
        $container = new Core\Container();
        $instance = $container->get('ChildClass');
        return $instance instanceof ChildClass && 
               $instance->parent instanceof ParentClass &&
               $instance->parent->grandparent instanceof GrandParent;
    }
];

// 测试6: 不存在的类抛出异常
$tests[] = [
    'name' => '不存在的类抛出异常测试',
    'test' => function() {
        $container = new Core\Container();
        try {
            $container->get('NonExistentClass12345');
            return false;
        } catch (\Exception $e) {
            return strpos($e->getMessage(), '类不存在') !== false;
        }
    }
];

// 测试7: 带默认参数的构造函数
class DefaultParamClass {
    public $value;
    public function __construct($value = 'default') {
        $this->value = $value;
    }
}

$tests[] = [
    'name' => '带默认参数的构造函数测试',
    'test' => function() {
        $container = new Core\Container();
        $instance = $container->get('DefaultParamClass');
        return $instance instanceof DefaultParamClass && $instance->value === 'default';
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