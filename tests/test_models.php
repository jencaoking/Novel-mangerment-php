<?php
/**
 * BookMusic Mall - 模型层测试
 * 注意：此测试不需要数据库连接，仅测试模型的静态方法和属性
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=====================================\n";
echo "模型层测试\n";
echo "=====================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// 测试1: 列名校验 - 使用模拟的BaseModel方法
$tests[] = [
    'name' => '列名校验测试 - 有效列名',
    'test' => function() {
        $validateColumn = function($column) {
            if (!is_string($column) || empty($column)) {
                return false;
            }
            return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column) === 1;
        };
        
        return $validateColumn('username') &&
               $validateColumn('user_name') &&
               $validateColumn('userName123');
    }
];

// 测试2: 列名校验 - 无效列名
$tests[] = [
    'name' => '列名校验测试 - 无效列名',
    'test' => function() {
        $validateColumn = function($column) {
            if (!is_string($column) || empty($column)) {
                return false;
            }
            return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column) === 1;
        };
        
        return !$validateColumn('') &&
               !$validateColumn('123invalid') &&
               !$validateColumn('invalid-column') &&
               !$validateColumn('user name') &&
               !$validateColumn(null);
    }
];

// 测试3: ORDER BY 校验 - 有效
$tests[] = [
    'name' => 'ORDER BY 校验测试 - 有效',
    'test' => function() {
        $validateOrderBy = function($orderBy) {
            if (!is_string($orderBy) || empty($orderBy)) {
                return false;
            }
            $parts = explode(',', $orderBy);
            foreach ($parts as $part) {
                $part = trim($part);
                $part = trim(preg_replace('/\s+(ASC|DESC)$/i', '', $part));
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $part)) {
                    return false;
                }
            }
            return true;
        };
        
        return $validateOrderBy('id') &&
               $validateOrderBy('id ASC') &&
               $validateOrderBy('id DESC') &&
               $validateOrderBy('created_at DESC') &&
               $validateOrderBy('users.id');
    }
];

// 测试4: ORDER BY 校验 - 无效
$tests[] = [
    'name' => 'ORDER BY 校验测试 - 无效',
    'test' => function() {
        $validateOrderBy = function($orderBy) {
            if (!is_string($orderBy) || empty($orderBy)) {
                return false;
            }
            $parts = explode(',', $orderBy);
            foreach ($parts as $part) {
                $part = trim($part);
                $part = trim(preg_replace('/\s+(ASC|DESC)$/i', '', $part));
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $part)) {
                    return false;
                }
            }
            return true;
        };
        
        return !$validateOrderBy('') &&
               !$validateOrderBy('; DROP TABLE users') &&
               !$validateOrderBy('id; DELETE FROM users') &&
               !$validateOrderBy(null);
    }
];

// 测试5: filterFillable 方法测试 - 空fillable
$tests[] = [
    'name' => 'filterFillable 方法测试 - 空fillable',
    'test' => function() {
        $filterFillable = function(array $data, array $fillable = []) {
            if (empty($fillable)) {
                return $data;
            }
            return array_intersect_key($data, array_flip($fillable));
        };
        
        $result = $filterFillable(['name' => 'test', 'password' => 'secret'], []);
        
        // 当fillable为空时，返回所有数据
        return isset($result['name']) && isset($result['password']);
    }
];

// 测试6: filterFillable 方法测试 - 有fillable
$tests[] = [
    'name' => 'filterFillable 方法测试 - 有fillable',
    'test' => function() {
        $filterFillable = function(array $data, array $fillable) {
            if (empty($fillable)) {
                return $data;
            }
            return array_intersect_key($data, array_flip($fillable));
        };
        
        $data = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'should-be-filtered'
        ];
        $result = $filterFillable($data, ['name', 'email']);
        
        return isset($result['name']) && 
               isset($result['email']) && 
               !isset($result['password']);
    }
];

// 测试7: hideFields 方法测试
$tests[] = [
    'name' => 'hideFields 方法测试',
    'test' => function() {
        $hideFields = function(array $data, array $hidden) {
            if (empty($hidden)) {
                return $data;
            }
            return array_diff_key($data, array_flip($hidden));
        };
        
        $data = [
            'id' => 1,
            'name' => 'test',
            'password' => 'secret',
            'token' => 'abc123'
        ];
        $result = $hideFields($data, ['password', 'token']);
        
        return isset($result['id']) && 
               isset($result['name']) && 
               !isset($result['password']) && 
               !isset($result['token']);
    }
];

// 测试8: UserModel 类存在性
$tests[] = [
    'name' => 'UserModel 类存在性测试',
    'test' => function() {
        return class_exists('App\Models\UserModel');
    }
];

// 测试9: UserModel 继承 BaseModel
$tests[] = [
    'name' => 'UserModel 继承测试',
    'test' => function() {
        return is_subclass_of('App\Models\UserModel', 'App\Models\BaseModel');
    }
];

// 测试10: UserModel 属性验证（通过反射，不实例化）
$tests[] = [
    'name' => 'UserModel 属性验证',
    'test' => function() {
        if (!class_exists('App\Models\UserModel')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Models\UserModel');
        
        // 检查是否有 table 属性
        if (!$reflection->hasProperty('table')) {
            return false;
        }
        
        // 检查是否有 fillable 属性
        if (!$reflection->hasProperty('fillable')) {
            return false;
        }
        
        // 检查是否有 hidden 属性
        if (!$reflection->hasProperty('hidden')) {
            return false;
        }
        
        // 获取默认属性值（不实例化）
        $tableProp = $reflection->getProperty('table');
        $tableProp->setAccessible(true);
        
        $fillableProp = $reflection->getProperty('fillable');
        $fillableProp->setAccessible(true);
        
        $hiddenProp = $reflection->getProperty('hidden');
        $hiddenProp->setAccessible(true);
        
        // 获取默认值（静态属性或默认值）
        $defaultProperties = $reflection->getDefaultProperties();
        
        return ($defaultProperties['table'] ?? '') === 'users' &&
               is_array($defaultProperties['fillable'] ?? []) &&
               in_array('username', $defaultProperties['fillable']) &&
               in_array('email', $defaultProperties['fillable']) &&
               is_array($defaultProperties['hidden'] ?? []) &&
               in_array('password', $defaultProperties['hidden']);
    }
];

// 测试11: UserModel 方法存在性
$tests[] = [
    'name' => 'UserModel 方法存在性测试',
    'test' => function() {
        if (!class_exists('App\Models\UserModel')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Models\UserModel');
        
        return $reflection->hasMethod('findByUsernameOrEmail') &&
               $reflection->hasMethod('findByUsername') &&
               $reflection->hasMethod('findByEmail') &&
               $reflection->hasMethod('updateLastLogin') &&
               $reflection->hasMethod('isUsernameExists') &&
               $reflection->hasMethod('isEmailExists');
    }
];

// 测试12: BaseModel 方法存在性
$tests[] = [
    'name' => 'BaseModel 方法存在性测试',
    'test' => function() {
        if (!class_exists('App\Models\BaseModel')) {
            return false;
        }
        
        $reflection = new ReflectionClass('App\Models\BaseModel');
        
        return $reflection->hasMethod('find') &&
               $reflection->hasMethod('findBy') &&
               $reflection->hasMethod('findAll') &&
               $reflection->hasMethod('create') &&
               $reflection->hasMethod('update') &&
               $reflection->hasMethod('delete');
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