<?php
/**
 * BookMusic Mall - Resend 邮件发送示例
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// 示例1: 简单文本邮件
echo "=== 示例1: 发送简单欢迎邮件 ===\n";
$result = sendEmailWithResend(
    'binccccc000@gmail.com',
    'Hello World',
    '<p>Congrats on sending your <strong>first email</strong>!</p>'
);

if ($result['success']) {
    echo "✓ 邮件发送成功！\n";
    echo "响应数据: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "✗ 邮件发送失败: " . $result['message'] . "\n";
}

echo "\n";

// 示例2: 带自定义发件人的邮件
echo "=== 示例2: 发送自定义发件人邮件 ===\n";
$result = sendEmailWithResend(
    'binccccc000@gmail.com',
    '订单确认通知',
    '<h1>感谢您的购买</h1><p>您的订单已确认，我们将尽快处理。</p>',
    'noreply@bookmusic.com' // 自定义发件人
);

if ($result['success']) {
    echo "✓ 邮件发送成功！\n";
} else {
    echo "✗ 邮件发送失败: " . $result['message'] . "\n";
}

echo "\n";

// 示例3: 在业务逻辑中使用（例如注册欢迎邮件）
echo "=== 示例3: 注册欢迎邮件示例 ===\n";
$username = '新用户';
$welcomeHtml = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #333;'>欢迎加入 BookMusic Mall!</h2>
        <p>亲爱的 {$username}，</p>
        <p>感谢您注册我们的平台。现在您可以：</p>
        <ul>
            <li>浏览精选小说和音乐</li>
            <li>购买您喜欢的数字内容</li>
            <li>享受优质的阅读和听觉体验</li>
        </ul>
        <p>祝您使用愉快！</p>
        <hr>
        <p style='color: #999; font-size: 12px;'>BookMusic Mall 团队</p>
    </div>
";

$result = sendEmailWithResend(
    'binccccc000@gmail.com',
    '欢迎加入 BookMusic Mall',
    $welcomeHtml
);

if ($result['success']) {
    echo "✓ 欢迎邮件发送成功！\n";
} else {
    echo "✗ 欢迎邮件发送失败: " . $result['message'] . "\n";
}

echo "\n完成！\n";
