<?php
/**
 * Sentry 集成测试文件
 * 
 * 此文件用于测试 Sentry 错误监控是否正常工作
 */

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once 'includes/config.php';

echo "<h1>Sentry 集成测试</h1>";

// 检查 Sentry 是否已配置
if (defined('SENTRY_DSN') && !empty(SENTRY_DSN)) {
    echo "<p style='color: green;'>✓ Sentry DSN 已配置</p>";
    echo "<p>DSN: " . substr(SENTRY_DSN, 0, 20) . "...</p>";
} else {
    echo "<p style='color: red;'>✗ Sentry DSN 未配置</p>";
    echo "<p>请在 .env 文件中设置 SENTRY_DSN</p>";
    exit;
}

// 初始化 Sentry
try {
    \Sentry\init([
        'dsn' => SENTRY_DSN,
        'environment' => SENTRY_ENVIRONMENT,
        'traces_sample_rate' => 1.0,
        'profiles_sample_rate' => 1.0,
        'enable_logs' => true,
    ]);
    echo "<p style='color: green;'>✓ Sentry 初始化成功</p>";
} catch (\Exception $e) {
    echo "<p style='color: red;'>✗ Sentry 初始化失败: " . $e->getMessage() . "</p>";
    exit;
}

// 测试捕获异常
echo "<hr><h2>测试异常捕获</h2>";

try {
    // 故意抛出一个异常
    throw new \Exception('这是一个测试异常 - ' . date('Y-m-d H:i:s'));
} catch (\Throwable $exception) {
    \Sentry\captureException($exception);
    echo "<p style='color: blue;'>✓ 测试异常已捕获并发送到 Sentry</p>";
    echo "<p>异常信息: " . $exception->getMessage() . "</p>";
}

// 测试捕获最后一个错误
echo "<hr><h2>测试错误捕获</h2>";

// 触发一个警告
$result = @file_get_contents('non_existent_file.txt');
\Sentry\captureLastError();
echo "<p style='color: blue;'>✓ 最后错误已捕获并发送到 Sentry</p>";

echo "<hr><p><strong>测试完成！</strong></p>";
echo "<p>请检查您的 Sentry 仪表板以确认事件是否已接收。</p>";
echo "<p><a href='index.php'>返回首页</a></p>";
