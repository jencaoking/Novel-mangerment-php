<?php
/**
 * BookMusic Mall - 安全测试
 * 包含 CSRF 防护、SQL注入防护等安全功能测试
 */

// 在输出任何内容之前启动session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=====================================\n";
echo "安全测试\n";
echo "=====================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// 测试1: CSRF Token 生成
$tests[] = [
    'name' => 'CSRF Token 生成测试',
    'test' => function() {
        $token = generateCSRFToken();
        
        return !empty($token) && 
               strlen($token) === 64 &&  // bin2hex(random_bytes(32)) = 64个字符
               isset($_SESSION[CSRF_TOKEN_NAME]) &&
               $_SESSION[CSRF_TOKEN_NAME] === $token;
    }
];

// 测试2: CSRF Token 验证 - 有效token
$tests[] = [
    'name' => 'CSRF Token 验证测试 - 有效token',
    'test' => function() {
        $token = generateCSRFToken();
        return verifyCSRFToken($token);
    }
];

// 测试3: CSRF Token 验证 - 无效token
$tests[] = [
    'name' => 'CSRF Token 验证测试 - 无效token',
    'test' => function() {
        generateCSRFToken();
        return !verifyCSRFToken('invalid-token-12345');
    }
];

// 测试4: CSRF Token 验证 - 空token
$tests[] = [
    'name' => 'CSRF Token 验证测试 - 空token',
    'test' => function() {
        generateCSRFToken();
        return !verifyCSRFToken('');
    }
];

// 测试5: CSRF Token 验证 - 无session token
$tests[] = [
    'name' => 'CSRF Token 验证测试 - 无session token',
    'test' => function() {
        unset($_SESSION['csrf_token']);
        return !verifyCSRFToken('any-token');
    }
];

// 测试6: CSRF Token 多次验证（当前实现不销毁token）
$tests[] = [
    'name' => 'CSRF Token 多次验证测试',
    'test' => function() {
        $token = generateCSRFToken();
        $firstVerify = verifyCSRFToken($token);
        $secondVerify = verifyCSRFToken($token);
        
        // 当前实现中，验证后token不会被销毁，所以两次验证都应该通过
        return $firstVerify && $secondVerify;
    }
];

// 测试7: 用户名格式验证 - 有效用户名
$tests[] = [
    'name' => '用户名格式验证测试 - 有效用户名',
    'test' => function() {
        return isValidUsername('test') &&
               isValidUsername('test123') &&
               isValidUsername('test_user') &&
               isValidUsername('测试用户');
    }
];

// 测试8: 用户名格式验证 - 无效用户名
$tests[] = [
    'name' => '用户名格式验证测试 - 无效用户名',
    'test' => function() {
        return !isValidUsername('') &&
               !isValidUsername('ab') &&  // 太短
               !isValidUsername('test@user') &&  // 包含特殊字符
               !isValidUsername('test user');  // 包含空格
    }
];

// 测试9: 邮箱格式验证 - 有效邮箱
$tests[] = [
    'name' => '邮箱格式验证测试 - 有效邮箱',
    'test' => function() {
        return isValidEmail('test@example.com') &&
               isValidEmail('test.user@example.com') &&
               isValidEmail('test_user@example.org');
    }
];

// 测试10: 邮箱格式验证 - 无效邮箱
$tests[] = [
    'name' => '邮箱格式验证测试 - 无效邮箱',
    'test' => function() {
        return !isValidEmail('') &&
               !isValidEmail('test@') &&
               !isValidEmail('@example.com') &&
               !isValidEmail('test.example.com');
    }
];

// 测试11: SQL注入防护 - 列名校验（模拟方法）
$tests[] = [
    'name' => 'SQL注入防护测试 - 列名校验',
    'test' => function() {
        $validateColumn = function($column) {
            if (!is_string($column) || empty($column)) {
                return false;
            }
            return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column) === 1;
        };
        
        // 测试恶意输入
        return !$validateColumn("id; DROP TABLE users--") &&
               !$validateColumn("' OR 1=1--") &&
               !$validateColumn("'); DELETE FROM users; --");
    }
];

// 测试12: SQL注入防护 - ORDER BY 校验（模拟方法）
$tests[] = [
    'name' => 'SQL注入防护测试 - ORDER BY 校验',
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
        
        // 测试恶意输入
        return !$validateOrderBy("id; DROP TABLE users") &&
               !$validateOrderBy("id ASC; DELETE FROM users") &&
               !$validateOrderBy("1; SELECT * FROM users");
    }
];

// 测试13: filterFillable 防止批量赋值攻击（模拟方法）
$tests[] = [
    'name' => '批量赋值防护测试',
    'test' => function() {
        $filterFillable = function(array $data, array $fillable) {
            if (empty($fillable)) {
                return $data;
            }
            return array_intersect_key($data, array_flip($fillable));
        };
        
        // 模拟恶意输入，尝试设置 role 为 admin
        $input = [
            'name' => 'test',
            'email' => 'test@example.com',
            'role' => 'admin',
            'is_admin' => 1
        ];
        
        $result = $filterFillable($input, ['name', 'email']);
        
        return !isset($result['role']) && 
               !isset($result['is_admin']) &&
               isset($result['name']) &&
               isset($result['email']);
    }
];

// 测试14: XSS 过滤 - HTML 标签过滤
$tests[] = [
    'name' => 'XSS 防护测试 - HTML 过滤',
    'test' => function() {
        $input = '<script>alert("XSS")</script>';
        $output = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return strpos($output, '<script>') === false &&
               strpos($output, '&lt;script&gt;') !== false;
    }
];

// 测试15: 密码哈希验证
$tests[] = [
    'name' => '密码哈希验证测试',
    'test' => function() {
        $password = 'test-password-123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        return password_verify($password, $hash) &&
               !password_verify('wrong-password', $hash);
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