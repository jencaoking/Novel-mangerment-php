<?php
/**
 * BookMusic Mall - 综合测试运行器
 * 运行所有测试并生成测试报告
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=====================================\n";
echo "BookMusic Mall 综合测试运行器\n";
echo "=====================================\n\n";

// 测试文件列表
$testFiles = [
    'test_container.php' => 'Container 依赖注入容器测试',
    'test_router.php' => 'Router 路由测试',
    'test_models.php' => '模型层测试',
    'test_controllers.php' => '控制器测试',
    'test_middleware.php' => '中间件测试',
    'test_security.php' => '安全测试'
];

$totalPassed = 0;
$totalFailed = 0;
$testResults = [];

// 运行每个测试文件
foreach ($testFiles as $file => $description) {
    echo "正在运行: {$description}\n";
    echo "-------------------------------------\n";
    
    $testPath = __DIR__ . '/' . $file;
    
    if (!file_exists($testPath)) {
        echo "✗ 测试文件不存在: {$file}\n\n";
        continue;
    }
    
    // 使用 proc_open 运行测试并捕获输出
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    
    $escapedPath = escapeshellarg($testPath);
    $process = proc_open("php {$escapedPath}", $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        echo $output;
        
        // 解析测试结果
        if (preg_match('/测试结果:\s*(\d+)\s*通过,\s*(\d+)\s*失败/', $output, $matches)) {
            $passed = (int)$matches[1];
            $failed = (int)$matches[2];
            
            $totalPassed += $passed;
            $totalFailed += $failed;
            
            $testResults[] = [
                'file' => $file,
                'description' => $description,
                'passed' => $passed,
                'failed' => $failed,
                'success' => $failed === 0
            ];
        }
        
        if ($errors) {
            echo "错误输出:\n{$errors}\n";
        }
        
        echo "\n";
    } else {
        echo "✗ 无法启动测试进程\n\n";
    }
}

// 生成综合报告
echo "=====================================\n";
echo "综合测试报告\n";
echo "=====================================\n\n";

echo "测试摘要:\n";
echo "-------------------------------------\n";

foreach ($testResults as $result) {
    $status = $result['success'] ? '✓ 通过' : '✗ 失败';
    echo "{$result['description']}: {$status} ({$result['passed']}/" . ($result['passed'] + $result['failed']) . ")\n";
}

echo "\n";
echo "总计: {$totalPassed} 通过, {$totalFailed} 失败\n";

if ($totalFailed === 0) {
    echo "\n🎉 所有测试通过！\n";
} else {
    echo "\n⚠️ 有 {$totalFailed} 个测试失败，请检查相关测试文件。\n";
}

echo "\n=====================================\n";
echo "测试完成时间: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n";

exit($totalFailed > 0 ? 1 : 0);
?>