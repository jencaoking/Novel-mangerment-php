# Resend 邮件服务集成指南

## 概述

本项目已集成 Resend API，用于发送邮件通知。Resend 是一个现代化的电子邮件 API 服务。

## 配置步骤

### 1. 获取 API Key

1. 访问 [Resend 官网](https://resend.com/)
2. 注册账号并登录
3. 在 Dashboard 中创建 API Key
4. 复制你的 API Key（格式：`re_xxxxxxxxx`）

### 2. 配置 API Key

**重要：请将 `re_xxxxxxxxx` 替换为你的真实 API Key**

有两种方式配置：

#### 方式一：修改 .env 文件（推荐）

编辑 `.env` 文件，找到以下行：

```env
RESEND_API_KEY=re_xxxxxxxxx
```

将 `re_xxxxxxxxx` 替换为你的真实 API Key：

```env
RESEND_API_KEY=re_YourRealApiKeyHere
```

#### 方式二：设置环境变量

在服务器环境中设置 `RESEND_API_KEY` 环境变量。

### 3. 验证配置

运行示例文件测试配置：

```bash
php examples/resend_example.php
```

## 使用方法

### 基本用法

```php
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 发送简单邮件
$result = sendEmailWithResend(
    'recipient@example.com',           // 收件人
    '邮件主题',                         // 主题
    '<p>邮件内容</p>'                   // HTML 内容
);

if ($result['success']) {
    echo "发送成功！";
} else {
    echo "发送失败: " . $result['message'];
}
```

### 自定义发件人

```php
$result = sendEmailWithResend(
    'recipient@example.com',
    '订单确认',
    '<h1>感谢购买</h1>',
    'noreply@yourdomain.com'  // 自定义发件人
);
```

### 在业务场景中使用

#### 用户注册欢迎邮件

```php
// 在 AuthController.php 的注册方法中
$username = $userData['username'];
$email = $userData['email'];

$welcomeHtml = "
    <div style='font-family: Arial, sans-serif;'>
        <h2>欢迎加入 BookMusic Mall!</h2>
        <p>亲爱的 {$username}，</p>
        <p>感谢您注册我们的平台。</p>
    </div>
";

sendEmailWithResend($email, '欢迎加入', $welcomeHtml);
```

#### 订单确认邮件

```php
// 在 PaymentController.php 中
$orderNo = $order['order_no'];
$totalAmount = $order['total_amount'];

$orderHtml = "
    <div>
        <h2>订单确认</h2>
        <p>订单号: {$orderNo}</p>
        <p>金额: ¥{$totalAmount}</p>
        <p>感谢您的购买！</p>
    </div>
";

sendEmailWithResend($userEmail, '订单确认', $orderHtml);
```

#### 密码重置邮件

```php
// 在 AuthController.php 中
$resetToken = generateRandomString(32);
$resetLink = SITE_URL . "reset-password?token={$resetToken}";

$resetHtml = "
    <div>
        <h2>密码重置</h2>
        <p>点击以下链接重置密码：</p>
        <a href='{$resetLink}'>重置密码</a>
        <p>链接有效期为 24 小时。</p>
    </div>
";

sendEmailWithResend($userEmail, '密码重置', $resetHtml);
```

## API 参数说明

### sendEmailWithResend() 函数

```php
function sendEmailWithResend($to, $subject, $htmlContent, $from = 'onboarding@resend.dev')
```

**参数：**
- `$to` (string): 收件人邮箱地址
- `$subject` (string): 邮件主题
- `$htmlContent` (string): HTML 格式的邮件内容
- `$from` (string, 可选): 发件人邮箱，默认为 `onboarding@resend.dev`

**返回值：**
```php
[
    'success' => true/false,
    'message' => '错误信息（如果失败）',
    'data' => [...],  // Resend API 响应数据
    'http_code' => 200  // HTTP 状态码
]
```

## 注意事项

1. **API Key 安全**：永远不要将真实的 API Key 提交到版本控制系统
2. **发件人域名**：使用自定义域名需要在 Resend Dashboard 中验证
3. **速率限制**：免费套餐有发送限制，请查看 Resend 官方文档
4. **HTML 内容**：确保 HTML 内容格式正确，建议使用内联样式
5. **错误处理**：始终检查返回结果的 `success` 字段

## 常见问题

### Q: 邮件发送失败怎么办？

A: 检查以下几点：
1. API Key 是否正确配置
2. 网络连接是否正常
3. 收件人邮箱格式是否正确
4. 查看 `$result['message']` 获取具体错误信息

### Q: 如何自定义发件人名称？

A: 可以使用以下格式：
```php
$from = 'Your Name <noreply@yourdomain.com>';
sendEmailWithResend($to, $subject, $html, $from);
```

### Q: 支持抄送和密送吗？

A: 当前简化版本不支持。如需支持，可以修改 `ResendSDK.php` 添加 `cc` 和 `bcc` 参数。

## 更多信息

- [Resend 官方文档](https://resend.com/docs)
- [Resend PHP SDK](https://github.com/resend/resend-php)
- [Resend API 参考](https://resend.com/docs/api-reference/emails/send-email)

## 示例代码

查看 `examples/resend_example.php` 文件获取更多使用示例。
