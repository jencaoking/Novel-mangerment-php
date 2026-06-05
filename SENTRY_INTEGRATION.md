# Sentry 错误监控集成指南

## 概述

本项目已成功集成 Sentry 错误监控服务，可以实时捕获和跟踪应用程序中的错误、异常和性能问题。

## 已完成的配置

### 1. Composer 依赖
已在 `composer.json` 中添加 Sentry SDK：
```json
"sentry/sentry": "^4.0"
```

### 2. 配置文件
在 `includes/config.php` 中添加了 Sentry 配置常量：
```php
define('SENTRY_DSN', getenv('SENTRY_DSN') ?: '');
define('SENTRY_ENVIRONMENT', getenv('SENTRY_ENVIRONMENT') ?: 'production');
```

### 3. 环境变量
在 `.env` 文件中配置了 Sentry DSN：
```env
SENTRY_DSN=https://94facd0313d0f9f555ae2752efd8560f@o4511513556287488.ingest.us.sentry.io/4511513564938240
SENTRY_ENVIRONMENT=production
```

### 4. 初始化代码
在 `public/index.php` 中添加了 Sentry 初始化代码，确保在应用启动时尽早初始化。

### 5. 异常处理
更新了全局异常处理器，自动将未捕获的异常发送到 Sentry。

## 使用方法

### 基本使用

Sentry 已在应用入口点自动初始化，所有未捕获的异常都会自动发送到 Sentry。

### 手动捕获异常

```php
try {
    // 可能抛出异常的代码
    $this->functionFailsForSure();
} catch (\Throwable $exception) {
    \Sentry\captureException($exception);
}
```

### 捕获最后一个错误

```php
// 触发一个错误
$result = @file_get_contents('non_existent_file.txt');
\Sentry\captureLastError();
```

### 添加上下文信息

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setTag('page_locale', 'zh-cn');
    $scope->setUser(['email' => 'user@example.com']);
    $scope->setExtra('database_queries', $queries);
});
```

### 创建事务（性能监控）

```php
$transaction = \Sentry\startTransaction([
    'op' => 'http.server',
    'name' => 'GET /api/users',
]);

try {
    // 业务逻辑
    $users = $userRepository->findAll();
    
    $transaction->setStatus('ok');
} catch (\Throwable $e) {
    $transaction->setStatus('internal_error');
    throw $e;
} finally {
    $transaction->finish();
}
```

## 测试集成

运行测试文件验证 Sentry 是否正常工作：

```bash
php test_sentry.php
```

或在浏览器中访问：
```
http://localhost:8000/test_sentry.php
```

## 配置选项

### 采样率配置

- `traces_sample_rate`: 性能追踪采样率（0.0 - 1.0）
- `profiles_sample_rate`: 性能分析采样率（相对于 traces_sample_rate）

### 环境配置

- `SENTRY_ENVIRONMENT`: 设置环境名称（development, staging, production）

### 其他配置选项

```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => SENTRY_ENVIRONMENT,
    'traces_sample_rate' => 1.0,
    'profiles_sample_rate' => 1.0,
    'enable_logs' => true,
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // 在发送前修改或过滤事件
        return $event;
    },
]);
```

## 注意事项

### PHP 配置

为了在错误堆栈中显示参数信息，请在 `php.ini` 中设置：
```ini
zend.exception_ignore_args = Off
```

### Excimer 扩展（可选）

要启用性能分析功能，需要安装 Excimer 扩展：
```bash
pecl install excimer
```

注意：Excimer 仅支持 Linux 和 macOS，不支持 Windows。

### 生产环境建议

1. **调整采样率**：在高流量生产环境中，建议降低采样率以减少数据量
   ```php
   'traces_sample_rate' => 0.1,  // 10% 的请求
   ```

2. **敏感数据过滤**：使用 `before_send` 回调过滤敏感信息
   ```php
   'before_send' => function ($event) {
       // 移除密码等敏感字段
       if (isset($event->request)) {
           unset($event->request->data['password']);
       }
       return $event;
   },
   ```

3. **错误去重**：Sentry 会自动进行错误去重，无需额外配置

## 故障排除

### Sentry 未发送事件

1. 检查 DSN 是否正确配置
2. 确认网络连接正常
3. 检查 Sentry 项目设置
4. 查看日志文件 `logs/error.log`

### 性能影响

如果担心性能影响，可以：
1. 降低采样率
2. 禁用性能分析（设置 `profiles_sample_rate` 为 0）
3. 异步发送事件（Sentry 默认使用异步发送）

## 相关链接

- [Sentry PHP SDK 文档](https://docs.sentry.io/platforms/php/)
- [Sentry 仪表板](https://sentry.io/)
- [Sentry PHP GitHub](https://github.com/getsentry/sentry-php)

## 支持的 PHP 版本

Sentry PHP SDK 4.x 支持 PHP 7.2+

## 更新日志

- 2026-06-05: 初始集成 Sentry SDK 4.27.0
- 配置自动异常捕获
- 添加测试文件和环境变量配置
