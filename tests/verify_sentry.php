<?php
/**
 * Sentry 集成验证脚本
 * 
 * 此脚本用于验证 Sentry 是否正确集成到项目中
 */

echo "========================================\n";
echo "  Sentry 集成验证\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. 检查 composer.json
echo "[1/7] 检查 composer.json...\n";
$composerJson = file_get_contents(__DIR__ . '/composer.json');
if (strpos($composerJson, 'sentry/sentry') !== false) {
    $success[] = "✓ composer.json 包含 sentry/sentry 依赖";
    echo "   ✓ 通过\n";
} else {
    $errors[] = "✗ composer.json 缺少 sentry/sentry 依赖";
    echo "   ✗ 失败\n";
}

// 2. 检查 vendor 目录
echo "[2/7] 检查 Sentry SDK 安装...\n";
if (file_exists(__DIR__ . '/vendor/sentry/sentry/src/functions.php')) {
    $success[] = "✓ Sentry SDK 已安装";
    echo "   ✓ 通过\n";
} else {
    $errors[] = "✗ Sentry SDK 未安装";
    echo "   ✗ 失败\n";
}

// 3. 检查配置文件
echo "[3/7] 检查配置文件...\n";
$configContent = file_get_contents(__DIR__ . '/includes/config.php');
if (strpos($configContent, 'SENTRY_DSN') !== false && strpos($configContent, 'SENTRY_ENVIRONMENT') !== false) {
    $success[] = "✓ includes/config.php 包含 Sentry 配置";
    echo "   ✓ 通过\n";
} else {
    $errors[] = "✗ includes/config.php 缺少 Sentry 配置";
    echo "   ✗ 失败\n";
}

// 4. 检查环境变量
echo "[4/7] 检查环境变量配置...\n";
$envContent = file_get_contents(__DIR__ . '/.env');
if (strpos($envContent, 'SENTRY_DSN=') !== false) {
    $success[] = "✓ .env 文件包含 SENTRY_DSN";
    echo "   ✓ 通过\n";
} else {
    $warnings[] = "⚠ .env 文件缺少 SENTRY_DSN 配置";
    echo "   ⚠ 警告\n";
}

// 5. 检查入口文件
echo "[5/7] 检查入口文件初始化...\n";
$indexContent = file_get_contents(__DIR__ . '/public/index.php');
if (strpos($indexContent, '\\Sentry\\init') !== false) {
    $success[] = "✓ public/index.php 包含 Sentry 初始化代码";
    echo "   ✓ 通过\n";
} else {
    $errors[] = "✗ public/index.php 缺少 Sentry 初始化代码";
    echo "   ✗ 失败\n";
}

// 6. 检查异常处理
echo "[6/7] 检查异常处理集成...\n";
if (strpos($indexContent, '\\Sentry\\captureException') !== false) {
    $success[] = "✓ 全局异常处理器已集成 Sentry";
    echo "   ✓ 通过\n";
} else {
    $warnings[] = "⚠ 全局异常处理器未集成 Sentry";
    echo "   ⚠ 警告\n";
}

// 7. 测试类加载
echo "[7/7] 测试 Sentry 类加载...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('\\Sentry\\State\\Hub')) {
        $success[] = "✓ Sentry 类可以正常加载";
        echo "   ✓ 通过\n";
    } else {
        $errors[] = "✗ Sentry 类无法加载";
        echo "   ✗ 失败\n";
    }
} catch (\Exception $e) {
    $errors[] = "✗ 自动加载失败: " . $e->getMessage();
    echo "   ✗ 失败\n";
}

// 输出结果摘要
echo "\n========================================\n";
echo "  验证结果摘要\n";
echo "========================================\n\n";

if (!empty($success)) {
    echo "✅ 成功 (" . count($success) . "):\n";
    foreach ($success as $item) {
        echo "   $item\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  警告 (" . count($warnings) . "):\n";
    foreach ($warnings as $item) {
        echo "   $item\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ 错误 (" . count($errors) . "):\n";
    foreach ($errors as $item) {
        echo "   $item\n";
    }
    echo "\n";
}

// 最终判断
echo "========================================\n";
if (empty($errors)) {
    echo "🎉 验证通过！Sentry 已成功集成。\n";
    echo "========================================\n\n";
    
    if (!empty($warnings)) {
        echo "提示：存在一些警告，建议修复以获得最佳体验。\n\n";
    }
    
    echo "下一步：\n";
    echo "1. 运行 php test_sentry.php 测试功能\n";
    echo "2. 查看 SENTRY_INTEGRATION.md 了解详细用法\n";
    echo "3. 访问网站，Sentry 会自动捕获错误\n";
    
    exit(0);
} else {
    echo "❌ 验证失败！请修复上述错误后重试。\n";
    echo "========================================\n\n";
    
    echo "常见问题解决：\n";
    echo "- 如果 Sentry SDK 未安装，运行: php composer.phar update sentry/sentry --prefer-source\n";
    echo "- 检查 .env 文件中的 SENTRY_DSN 配置\n";
    echo "- 确保 composer.json 包含 sentry/sentry 依赖\n";
    
    exit(1);
}
