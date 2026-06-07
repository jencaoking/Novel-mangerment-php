<?php
/**
 * BookMusic Mall - Resend API 配置测试
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=====================================\n";
echo "Resend API 配置测试\n";
echo "=====================================\n\n";

// 检查配置
echo "1. 检查 API Key 配置...\n";
if (RESEND_API_KEY === 're_xxxxxxxxx') {
    echo "   ✗ 错误: 请先在 .env 文件中配置 RESEND_API_KEY\n";
    echo "   提示: 将 're_xxxxxxxxx' 替换为你的真实 API Key\n";
    exit(1);
} else {
    echo "   ✓ API Key 已配置\n";
}

// 检查 CURL 扩展
echo "\n2. 检查 CURL 扩展...\n";
if (!function_exists('curl_init')) {
    echo "   ✗ 错误: CURL 扩展未启用\n";
    echo "   提示: 请在 php.ini 中启用 curl 扩展\n";
    exit(1);
} else {
    echo "   ✓ CURL 扩展已启用\n";
}

// 测试邮件发送
echo "\n3. 测试邮件发送...\n";
echo "   收件人: binccccc000@gmail.com\n";
echo "   主题: Resend API 配置测试\n";
echo "   内容: 这是一封测试邮件\n\n";

$result = sendEmailWithResend(
    'binccccc000@gmail.com',
    'Resend API 配置测试',
    '<h1>测试成功！</h1><p>Resend API 配置正确，邮件服务正常工作。</p>'
);

if ($result['success']) {
    echo "   ✓ 邮件发送成功！\n";
    echo "   HTTP 状态码: " . ($result['http_code'] ?? 'N/A') . "\n";
    if (isset($result['data']['id'])) {
        echo "   邮件 ID: " . $result['data']['id'] . "\n";
    }
    echo "\n✓ 所有测试通过！Resend API 配置正确。\n";
    exit(0);
} else {
    echo "   ✗ 邮件发送失败\n";
    echo "   错误信息: " . $result['message'] . "\n";
    if (isset($result['http_code'])) {
        echo "   HTTP 状态码: " . $result['http_code'] . "\n";
    }
    if (isset($result['data'])) {
        echo "   响应数据: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "\n✗ 测试失败。请检查 API Key 和网络连接。\n";
    exit(1);
}
