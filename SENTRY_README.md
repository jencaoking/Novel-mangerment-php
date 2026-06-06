# Sentry 错误监控集成指南

## 📋 概述

本项目已成功集成 Sentry 错误监控服务，可以实时捕获和跟踪应用程序中的错误、异常和性能问题。

**核心功能：**
- ✅ 自动异常捕获和报告
- ✅ 性能监控和事务追踪
- ✅ 实时错误日志记录
- ✅ 上下文信息追踪
- ✅ 多环境支持

---

## ⚡ 快速开始

### 1. 配置 DSN

编辑 `.env` 文件：

```env
# Sentry 配置
SENTRY_DSN=https://your-dsn@sentry.io/project-id
SENTRY_ENVIRONMENT=production
```

### 2. 验证安装

运行测试脚本：

```bash
php test_sentry.php
```

### 3. 查看示例

```bash
php examples/sentry_usage_example.php
```

---

## 🔧 已完成的配置

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

### 3. 初始化代码
在 `public/index.php` 中添加了 Sentry 初始化代码，确保在应用启动时尽早初始化。

### 4. 异常处理
更新了全局异常处理器，自动将未捕获的异常发送到 Sentry。

---

## 🎯 配置选项详解

### 当前配置

```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => SENTRY_ENVIRONMENT,
    'traces_sample_rate' => 1.0,
    'profiles_sample_rate' => 1.0,
    'enable_logs' => true,
]);
```

### 配置项说明

#### 必需配置

**`dsn` (Data Source Name)**
- **类型**: string
- **说明**: Sentry 项目的唯一标识符
- **示例**: `'https://xxx@sentry.io/project-id'`

#### 推荐配置

**`environment`**
- **类型**: string
- **可选值**: `'development'`, `'staging'`, `'production'`
- **用途**: 在 Sentry 仪表板中按环境过滤错误

**`traces_sample_rate`**
- **类型**: float (0.0 - 1.0)
- **说明**: 性能追踪的采样率
- **推荐值**: 生产环境 0.1-0.2，开发环境 1.0

**`profiles_sample_rate`**
- **类型**: float (0.0 - 1.0)
- **说明**: 性能分析的采样率
- **注意**: 需要安装 Excimer 扩展（仅 Linux/macOS）

**`enable_logs`**
- **类型**: boolean
- **说明**: 是否启用日志发送到 Sentry

### 可选高级配置

**`release`**
- **类型**: string
- **说明**: 应用发布版本标识
- **示例**: `'bookmusic@1.0.0'`

**`before_send`**
- **类型**: callable
- **说明**: 在发送事件到 Sentry 之前的回调函数
- **用途**: 过滤敏感信息、修改事件数据

**`attach_stacktrace`**
- **类型**: boolean
- **说明**: 是否为所有事件附加堆栈跟踪

---

## 📊 采样率选择指南

| 环境 | traces_sample_rate | profiles_sample_rate | 说明 |
|------|-------------------|---------------------|------|
| **开发** | 1.0 (100%) | 1.0 (100%) | 完整监控，便于调试 |
| **测试** | 0.5 (50%) | 0.5 (50%) | 平衡数据量和覆盖率 |
| **预发布** | 0.2 (20%) | 0.2 (20%) | 接近生产环境的配置 |
| **生产（低流量）** | 0.2 (20%) | 0.1 (10%) | < 1000 req/min |
| **生产（中流量）** | 0.1 (10%) | 0.05 (5%) | 1000-10000 req/min |
| **生产（高流量）** | 0.05 (5%) | 0.01 (1%) | > 10000 req/min |

---

## 🚀 使用方法

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
    $users = $userRepository->findAll();
    $transaction->setStatus('ok');
} catch (\Throwable $e) {
    $transaction->setStatus('internal_error');
    throw $e;
} finally {
    $transaction->finish();
}
```

---

## 🔧 常用配置场景

### 场景 1: 最小化配置（仅错误捕获）
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'traces_sample_rate' => 0,  // 禁用性能追踪
]);
```

### 场景 2: 标准生产配置
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'production',
    'traces_sample_rate' => 0.1,
    'enable_logs' => true,
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // 移除敏感数据
        if (isset($event->request)) {
            unset($event->request->data['password']);
        }
        return $event;
    },
]);
```

### 场景 3: 隐私保护配置
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'production',
    'traces_sample_rate' => 0.1,
    'send_default_pii' => false,
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        $sensitiveFields = ['password', 'token', 'credit_card', 'ssn'];
        foreach ($sensitiveFields as $field) {
            unset($event->request->data[$field]);
        }
        return $event;
    },
]);
```

---

## ⚠️ 注意事项

### PHP 配置

确保 `php.ini` 中设置：
```ini
zend.exception_ignore_args = Off
```

### Excimer 扩展（可选）

如需性能分析功能：
```bash
pecl install excimer
```
注意：仅支持 Linux/macOS

### 生产环境建议

1. **调整采样率**：在高流量生产环境中，建议降低采样率
2. **敏感数据过滤**：使用 `before_send` 回调过滤敏感信息
3. **错误去重**：Sentry 会自动进行错误去重

---

## ⚡ 性能影响评估

| 配置 | CPU 影响 | 内存影响 | 网络影响 | 推荐场景 |
|------|---------|---------|---------|---------|
| 仅错误捕获 | 极低 | 极低 | 低 | 所有环境 |
| + 10% 追踪 | 低 | 低 | 中 | 生产环境 |
| + 50% 追踪 | 中 | 中 | 高 | 测试环境 |
| + 100% 追踪 | 高 | 高 | 很高 | 开发环境 |
| + 性能分析 | 很高 | 很高 | 很高 | 仅调试时 |

---

## ❓ 常见问题

### 错误 1: "DSN is not valid"
**原因**: DSN 格式错误或为空  
**解决**: 检查 `.env` 文件中的 `SENTRY_DSN` 是否正确

### 错误 2: 错误没有发送到 Sentry
**原因**: 网络连接问题或配置错误  
**解决**: 
1. 检查网络连接
2. 验证 DSN 是否正确
3. 查看 `logs/error.log`

### 错误 3: 性能开销过大
**原因**: 采样率设置过高  
**解决**: 降低 `traces_sample_rate` 和 `profiles_sample_rate`

### 错误 4: 敏感信息泄露
**原因**: 未配置 `before_send` 过滤  
**解决**: 添加 `before_send` 回调移除敏感字段

---

## 📊 监控数据

登录 [Sentry 仪表板](https://sentry.io/) 可以查看：
- ❌ 错误和异常
- ⚡ 性能数据
- 📈 事务追踪
- 📝 日志信息
- 👥 用户影响范围

---

## 🔗 相关资源

- [Sentry PHP SDK 文档](https://docs.sentry.io/platforms/php/)
- [Sentry 仪表板](https://sentry.io/)
- [使用示例](examples/sentry_usage_example.php)

---

**最后更新**: 2026-06-05  
**Sentry SDK 版本**: 4.27.0  
**PHP 版本要求**: >= 7.2