# Sentry 配置快速参考

## 📋 当前配置状态

| 配置项 | 当前值 | 说明 |
|--------|--------|------|
| DSN | ✅ 已配置 | 从 .env 读取 |
| Environment | ✅ production | 生产环境 |
| traces_sample_rate | ✅ 1.0 | 100% 性能追踪采样 |
| profiles_sample_rate | ✅ 1.0 | 100% 性能分析采样 |
| enable_logs | ✅ true | 启用日志发送 |

## 🎯 配置选项速查表

### 基础配置（必需）

```php
\Sentry\init([
    'dsn' => 'YOUR_DSN_HERE',  // ← 必须配置
]);
```

### 推荐配置（生产环境）

```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'production',
    'traces_sample_rate' => 0.1,    // 10% 采样
    'enable_logs' => true,
]);
```

### 完整配置（开发/测试环境）

```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'development',
    'traces_sample_rate' => 1.0,     // 100% 采样
    'profiles_sample_rate' => 1.0,   // 100% 性能分析
    'enable_logs' => true,
    'attach_stacktrace' => true,
    'release' => 'app@1.0.0',
]);
```

## 📊 采样率选择指南

| 环境 | traces_sample_rate | profiles_sample_rate | 说明 |
|------|-------------------|---------------------|------|
| **开发** | 1.0 (100%) | 1.0 (100%) | 完整监控，便于调试 |
| **测试** | 0.5 (50%) | 0.5 (50%) | 平衡数据量和覆盖率 |
| **预发布** | 0.2 (20%) | 0.2 (20%) | 接近生产环境的配置 |
| **生产（低流量）** | 0.2 (20%) | 0.1 (10%) | < 1000 req/min |
| **生产（中流量）** | 0.1 (10%) | 0.05 (5%) | 1000-10000 req/min |
| **生产（高流量）** | 0.05 (5%) | 0.01 (1%) | > 10000 req/min |

## 🔧 常用配置场景

### 场景 1: 最小化配置（仅错误捕获）
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'traces_sample_rate' => 0,  // 禁用性能追踪
]);
```
**适用**: 只需要错误报告，不需要性能监控

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
**适用**: 大多数生产环境

### 场景 3: 完整监控配置
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'staging',
    'traces_sample_rate' => 0.5,
    'profiles_sample_rate' => 0.5,
    'enable_logs' => true,
    'attach_stacktrace' => true,
    'max_breadcrumbs' => 100,
    'release' => git('rev-parse --short HEAD'),
]);
```
**适用**: 测试/预发布环境，需要详细诊断信息

### 场景 4: 隐私保护配置
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'production',
    'traces_sample_rate' => 0.1,
    'send_default_pii' => false,  // 不发送个人信息
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // 彻底清理敏感信息
        if (isset($event->request)) {
            $sensitiveFields = ['password', 'token', 'credit_card', 'ssn'];
            foreach ($sensitiveFields as $field) {
                unset($event->request->data[$field]);
            }
        }
        return $event;
    },
]);
```
**适用**: 处理敏感数据的應用（金融、医疗等）

## ⚡ 性能影响评估

| 配置 | CPU 影响 | 内存影响 | 网络影响 | 推荐场景 |
|------|---------|---------|---------|---------|
| 仅错误捕获 | 极低 | 极低 | 低 | 所有环境 |
| + 10% 追踪 | 低 | 低 | 中 | 生产环境 |
| + 50% 追踪 | 中 | 中 | 高 | 测试环境 |
| + 100% 追踪 | 高 | 高 | 很高 | 开发环境 |
| + 性能分析 | 很高 | 很高 | 很高 | 仅调试时 |

## 🚀 快速调整指南

### 如果 Sentry 数据量太大
```php
// 降低采样率
'traces_sample_rate' => 0.05,  // 从 1.0 降到 0.05

// 或者完全禁用性能追踪
'traces_sample_rate' => 0,
```

### 如果需要更详细的诊断信息
```php
// 提高采样率
'traces_sample_rate' => 1.0,

// 启用堆栈跟踪
'attach_stacktrace' => true,

// 增加面包屑数量
'max_breadcrumbs' => 100,
```

### 如果遇到性能问题
```php
// 禁用性能分析
'profiles_sample_rate' => 0,

// 降低追踪采样率
'traces_sample_rate' => 0.05,

// 禁用日志
'enable_logs' => false,
```

## 📝 环境变量配置示例

### .env 文件
```env
# Sentry 基础配置
SENTRY_DSN=https://your-dsn@sentry.io/project-id
SENTRY_ENVIRONMENT=production

# 可选：覆盖默认配置
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.05
SENTRY_RELEASE=1.0.0
```

### 在代码中使用
```php
\Sentry\init([
    'dsn' => getenv('SENTRY_DSN'),
    'environment' => getenv('SENTRY_ENVIRONMENT') ?: 'production',
    'traces_sample_rate' => (float)(getenv('SENTRY_TRACES_SAMPLE_RATE') ?: 0.1),
    'profiles_sample_rate' => (float)(getenv('SENTRY_PROFILES_SAMPLE_RATE') ?: 0.05),
    'release' => getenv('SENTRY_RELEASE') ?: null,
]);
```

## ⚠️ 常见错误和解决方案

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

## 🔗 快速链接

- [完整配置文档](SENTRY_CONFIGURATION_GUIDE.md)
- [集成指南](SENTRY_INTEGRATION.md)
- [快速开始](QUICK_START_SENTRY.md)
- [使用示例](examples/sentry_usage_example.php)
- [官方文档](https://docs.sentry.io/platforms/php/configuration/)

---

**提示**: 根据实际使用情况定期调整配置，找到性能和监控的最佳平衡点。
