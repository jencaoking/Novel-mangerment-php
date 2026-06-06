# Sentry 配置详解

## 当前配置

```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => SENTRY_ENVIRONMENT,
    'traces_sample_rate' => 1.0,
    'profiles_sample_rate' => 1.0,
    'enable_logs' => true,
]);
```

## 配置项说明

### 必需配置

#### `dsn` (Data Source Name)
- **类型**: string
- **说明**: Sentry 项目的唯一标识符，用于将错误发送到正确的 Sentry 项目
- **示例**: `'https://94facd0313d0f9f555ae2752efd8560f@o4511513556287488.ingest.us.sentry.io/4511513564938240'`
- **来源**: 从 `.env` 文件的 `SENTRY_DSN` 读取

### 推荐配置

#### `environment`
- **类型**: string
- **说明**: 环境标识，用于区分不同环境的错误
- **可选值**: `'development'`, `'staging'`, `'production'`
- **默认值**: `'production'`
- **用途**: 
  - 在 Sentry 仪表板中按环境过滤错误
  - 为不同环境设置不同的告警规则
- **示例**: 
  ```php
  'environment' => 'production',  // 生产环境
  'environment' => 'development', // 开发环境
  ```

#### `traces_sample_rate`
- **类型**: float (0.0 - 1.0)
- **说明**: 性能追踪的采样率
- **默认值**: `0` (禁用性能追踪)
- **推荐值**:
  - 开发环境: `1.0` (100% 采样)
  - 测试环境: `0.5` (50% 采样)
  - 生产环境: `0.1` - `0.2` (10%-20% 采样)
- **用途**: 
  - 追踪请求性能和瓶颈
  - 分析慢查询和慢接口
- **示例**:
  ```php
  'traces_sample_rate' => 0.1,  // 生产环境：10% 的请求
  ```

#### `profiles_sample_rate`
- **类型**: float (0.0 - 1.0)
- **说明**: 性能分析的采样率（相对于 traces_sample_rate）
- **默认值**: `0` (禁用性能分析)
- **注意**: 需要安装 Excimer 扩展（仅 Linux/macOS）
- **用途**: 
  - CPU 性能分析
  - 函数调用栈分析
- **示例**:
  ```php
  'profiles_sample_rate' => 0.5,  // 对 50% 的追踪进行性能分析
  ```

#### `enable_logs`
- **类型**: boolean
- **说明**: 是否启用日志发送到 Sentry
- **默认值**: `false`
- **推荐值**: `true`
- **用途**: 
  - 捕获应用日志
  - 与错误关联查看
- **示例**:
  ```php
  'enable_logs' => true,
  ```

### 可选高级配置

#### `release`
- **类型**: string
- **说明**: 应用发布版本标识
- **用途**: 
  - 追踪哪个版本引入了错误
  - 关联 commit 信息
  - 自动解析 sourcemaps
- **示例**:
  ```php
  'release' => 'bookmusic@1.0.0',
  'release' => git('rev-parse HEAD'),  // Git commit hash
  ```

#### `before_send`
- **类型**: callable
- **说明**: 在发送事件到 Sentry 之前的回调函数
- **用途**: 
  - 过滤敏感信息
  - 修改事件数据
  - 丢弃特定事件
- **示例**:
  ```php
  'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
      // 移除密码字段
      if (isset($event->request)) {
          unset($event->request->data['password']);
          unset($event->request->data['credit_card']);
      }
      
      // 忽略特定类型的错误
      if ($event->getLevel() === \Sentry\Severity::warning()) {
          return null;  // 丢弃警告
      }
      
      return $event;
  },
  ```

#### `before_breadcrumb`
- **类型**: callable
- **说明**: 在添加面包屑之前的回调函数
- **用途**: 过滤或修改面包屑数据
- **示例**:
  ```php
  'before_breadcrumb' => function (\Sentry\Breadcrumb $breadcrumb): ?\Sentry\Breadcrumb {
      // 忽略 SQL 查询面包屑
      if ($breadcrumb->getType() === 'sql') {
          return null;
      }
      return $breadcrumb;
  },
  ```

#### `max_breadcrumbs`
- **类型**: int
- **说明**: 每个事件最多保留的面包屑数量
- **默认值**: `100`
- **推荐值**: `50` - `100`
- **示例**:
  ```php
  'max_breadcrumbs' => 50,
  ```

#### `attach_stacktrace`
- **类型**: boolean
- **说明**: 是否为所有事件附加堆栈跟踪
- **默认值**: `false`
- **推荐值**: `true`
- **示例**:
  ```php
  'attach_stacktrace' => true,
  ```

#### `send_default_pii`
- **类型**: boolean
- **说明**: 是否发送默认的个人身份信息（PII）
- **默认值**: `false`
- **注意**: 开启后会发送用户 IP、cookie 等信息
- **示例**:
  ```php
  'send_default_pii' => false,  // 保护用户隐私
  ```

#### `integrations`
- **类型**: array
- **说明**: 自定义集成列表
- **用途**: 启用或禁用特定集成
- **示例**:
  ```php
  'integrations' => [
      new \Sentry\Integration\RequestIntegration(),
      new \Sentry\Integration\TransactionIntegration(),
  ],
  ```

## 不同环境的配置建议

### 开发环境
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'development',
    'traces_sample_rate' => 1.0,      // 100% 采样
    'profiles_sample_rate' => 1.0,     // 100% 性能分析
    'enable_logs' => true,
    'attach_stacktrace' => true,
]);
```

### 测试环境
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'staging',
    'traces_sample_rate' => 0.5,       // 50% 采样
    'profiles_sample_rate' => 0.5,     // 50% 性能分析
    'enable_logs' => true,
]);
```

### 生产环境
```php
\Sentry\init([
    'dsn' => SENTRY_DSN,
    'environment' => 'production',
    'traces_sample_rate' => 0.1,       // 10% 采样
    'profiles_sample_rate' => 0.1,     // 10% 性能分析
    'enable_logs' => true,
    'release' => 'bookmusic@1.0.0',
    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // 过滤敏感信息
        if (isset($event->request)) {
            unset($event->request->data['password']);
        }
        return $event;
    },
]);
```

## 性能优化建议

### 1. 调整采样率
高流量网站应降低采样率以减少数据量和成本：
```php
'traces_sample_rate' => 0.05,  // 5% 采样
```

### 2. 禁用不必要的功能
如果不需要性能分析，可以禁用：
```php
'traces_sample_rate' => 0,      // 禁用性能追踪
'profiles_sample_rate' => 0,    // 禁用性能分析
```

### 3. 使用异步发送
Sentry 默认使用异步发送，不会阻塞请求。

### 4. 过滤高频错误
使用 `before_send` 丢弃已知且无需关注的错误：
```php
'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
    $exception = $event->getExceptions()[0] ?? null;
    if ($exception && strpos($exception->getValue(), 'Expected error') !== false) {
        return null;  // 丢弃预期内的错误
    }
    return $event;
},
```

## 常见问题

### Q: 为什么我的错误没有发送到 Sentry？
A: 检查以下几点：
1. DSN 是否正确配置
2. 网络连接是否正常
3. Sentry 项目是否存在
4. 查看本地日志文件 `logs/error.log`

### Q: 如何减少 Sentry 的数据量？
A: 
1. 降低 `traces_sample_rate`
2. 使用 `before_send` 过滤事件
3. 禁用不需要的集成

### Q: 性能分析需要什么条件？
A: 
1. 安装 Excimer 扩展 (`pecl install excimer`)
2. 仅支持 Linux/macOS
3. 设置 `profiles_sample_rate > 0`

### Q: 如何保护用户隐私？
A: 
1. 设置 `send_default_pii = false`
2. 使用 `before_send` 移除敏感字段
3. 避免在日志中记录个人信息

## 相关资源

- [Sentry PHP SDK 文档](https://docs.sentry.io/platforms/php/configuration/options/)
- [Sentry 配置选项完整列表](https://docs.sentry.io/platforms/php/configuration/options/)
- [性能监控指南](https://docs.sentry.io/platforms/php/tracing/)

---

**最后更新**: 2026-06-05  
**Sentry SDK 版本**: 4.27.0
