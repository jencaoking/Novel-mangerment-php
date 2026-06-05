# Resend API 快速开始指南

## 🚀 5分钟快速配置

### 步骤 1: 获取 API Key（2分钟）

1. 访问 [Resend.com](https://resend.com/)
2. 注册账号并登录
3. 点击 "API Keys" → "Create API Key"
4. 复制你的 API Key（格式：`re_xxxxxxxxxxxxx`）

### 步骤 2: 配置 API Key（1分钟）

编辑项目根目录的 `.env` 文件：

```bash
# 找到这一行
RESEND_API_KEY=re_xxxxxxxxx

# 替换为你的真实 API Key
RESEND_API_KEY=re_YourRealApiKeyHere
```

**⚠️ 重要**: 将 `re_xxxxxxxxx` 替换为你从 Resend 获取的真实 API Key！

### 步骤 3: 测试配置（2分钟）

在终端运行：

```bash
php test_resend.php
```

如果看到以下输出，说明配置成功：

```
=====================================
Resend API 配置测试
=====================================

1. 检查 API Key 配置...
   ✓ API Key 已配置

2. 检查 CURL 扩展...
   ✓ CURL 扩展已启用

3. 测试邮件发送...
   收件人: binccccc000@gmail.com
   主题: Resend API 配置测试
   内容: 这是一封测试邮件

   ✓ 邮件发送成功！
   HTTP 状态码: 200
   邮件 ID: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx

✓ 所有测试通过！Resend API 配置正确。
```

## ✅ 完成！

现在你的项目已经可以使用 Resend API 发送邮件了。

## 📧 使用示例

### 简单发送

```php
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$result = sendEmailWithResend(
    'user@example.com',
    'Hello World',
    '<p>Congrats on sending your <strong>first email</strong>!</p>'
);

if ($result['success']) {
    echo "发送成功！";
} else {
    echo "发送失败: " . $result['message'];
}
```

### 自动发送欢迎邮件

当新用户注册时，系统会自动发送欢迎邮件到用户邮箱。无需额外代码！

## 📚 更多资源

- [完整集成指南](RESEND_README.md)
- [使用示例](examples/resend_example.php)
- [Resend 官方文档](https://resend.com/docs)

## ❓ 常见问题

**Q: 邮件没有收到？**
A: 检查垃圾邮件文件夹，或确认 API Key 是否正确配置。

**Q: 如何自定义发件人？**
A: 
```php
sendEmailWithResend(
    'user@example.com',
    '主题',
    '<p>内容</p>',
    'noreply@yourdomain.com'  // 自定义发件人
);
```

**Q: 可以在哪里使用？**
A: 任何需要发送邮件的地方：
- 用户注册欢迎邮件（已集成）
- 订单确认通知
- 密码重置链接
- 下载完成通知
- 等等...

---

**需要帮助？** 查看 [RESEND_README.md](RESEND_README.md) 获取详细说明。
