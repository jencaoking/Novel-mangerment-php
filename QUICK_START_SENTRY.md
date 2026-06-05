# Sentry 快速开始指南

## 5分钟快速集成 Sentry

### 1. 安装依赖

```bash
php composer.phar update sentry/sentry --prefer-source
```

### 2. 配置环境变量

在 `.env` 文件中添加（已自动配置）：

```env
SENTRY_DSN=https://94facd0313d0f9f555ae2752efd8560f@o4511513556287488.ingest.us.sentry.io/4511513564938240
SENTRY_ENVIRONMENT=production
```

### 3. 验证安装

运行测试脚本：

```bash
php test_sentry.php
```

或在浏览器访问：`http://localhost:8000/test_sentry.php`

### 4. 查看结果

登录 [Sentry 仪表板](https://sentry.io/) 查看捕获的错误和性能数据。

---

## 常用代码示例

### 捕获异常

```php
try {
    // 你的代码
} catch (\Throwable $e) {
    \Sentry\captureException($e);
}
```

### 添加用户上下文

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope) {
    $scope->setUser(['id' => $userId, 'email' => $email]);
    $scope->setTag('page', 'checkout');
});
```

### 性能监控

```php
$transactionContext = new \Sentry\Tracing\TransactionContext();
$transactionContext->setOp('http.server');
$transactionContext->setName('GET /api/users');
$transaction = \Sentry\startTransaction($transactionContext);

try {
    // 业务逻辑
    $transaction->setStatus(\Sentry\Tracing\SpanStatus::ok());
} catch (\Throwable $e) {
    $transaction->setStatus(\Sentry\Tracing\SpanStatus::internalError());
    throw $e;
} finally {
    $transaction->finish();
}
```

---

## 下一步

- 阅读完整文档：[SENTRY_INTEGRATION.md](SENTRY_INTEGRATION.md)
- 查看使用示例：[examples/sentry_usage_example.php](examples/sentry_usage_example.php)
- 访问 [Sentry 官方文档](https://docs.sentry.io/platforms/php/)

---

**提示**：Sentry 已在应用入口点自动初始化，所有未捕获的异常都会自动发送！
