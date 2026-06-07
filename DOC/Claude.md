# Claude.md - BookMusic Mall 项目指南

> 本文档为 AI 助手（如 Claude）提供项目上下文和开发指南

## 📋 项目概述

**BookMusic Mall** 是一个基于 PHP 开发的小说与音乐数字内容销售平台，采用 MVC 架构设计。

### 核心功能
- 📚 小说商城：在线销售 TXT 小说文件
- 🎵 音乐商城：在线销售 MP3 音乐文件
- 💳 支付系统：支付宝支付集成（已完成）
- 🛒 购物车：批量购买功能
- 👤 用户系统：注册、登录、个人中心
- 📊 管理后台：商品、订单、用户管理
- 📧 邮件服务：Resend API 集成
- 🔍 错误监控：Sentry 集成

### 技术栈
- **后端**: PHP 8.2+, MySQL 8.0+, PDO
- **前端**: HTML5, CSS3, JavaScript (ES6+)
- **架构**: MVC 模式
- **依赖管理**: Composer
- **安全**: bcrypt, CSRF, PDO 预处理

---

## 🏗️ 项目架构

### 目录结构
```
bookmusic/
├── app/                    # 应用核心
│   ├── Controllers/        # 控制器层
│   │   ├── AdminController.php      # 管理后台
│   │   ├── AuthController.php       # 认证
│   │   ├── CartController.php       # 购物车
│   │   ├── HomeController.php       # 首页
│   │   ├── PaymentController.php    # 支付
│   │   ├── ProductController.php    # 商品
│   │   └── UserController.php       # 用户中心
│   ├── Middleware/         # 中间件
│   │   ├── AdminMiddleware.php      # 管理员权限
│   │   └── AuthMiddleware.php       # 用户认证
│   └── Models/             # 模型层
│       ├── BaseModel.php            # 基础模型
│       ├── CartModel.php            # 购物车
│       ├── CategoryModel.php        # 分类
│       ├── DownloadModel.php        # 下载记录
│       ├── OrderModel.php           # 订单
│       ├── ProductModel.php         # 商品
│       └── UserModel.php            # 用户
├── core/                   # 核心框架
│   ├── Middleware/
│   │   └── MiddlewareInterface.php  # 中间件接口
│   ├── Autoloader.php               # 自动加载
│   └── Router.php                   # 路由
├── includes/               # 公共模块
│   ├── AlipaySDK.php                # 支付宝SDK
│   ├── ResendSDK.php                # 邮件SDK
│   ├── auth.php                     # 认证函数
│   ├── config.php.example           # 配置模板
│   ├── db.php                       # 数据库连接
│   └── functions.php                # 工具函数
├── public/                 # 公共访问目录
│   ├── assets/             # 静态资源
│   │   ├── css/
│   │   └── js/
│   ├── .htaccess                    # Apache 配置
│   └── index.php                    # 入口文件
├── views/                  # 视图层
│   ├── admin/             # 管理员视图
│   ├── auth/              # 认证视图
│   └── user/              # 用户视图
└── uploads/                # 文件存储
    ├── novels/             # 小说文件
    ├── music/              # 音乐文件
    ├── cover/              # 封面图片
    └── avatar/             # 用户头像
```

### MVC 流程
```
用户请求 → public/index.php → Router → Controller → Model → View → 响应
```

---

## 🔐 安全注意事项

### ⚠️ 已知安全问题（需修复）

#### 1. SQL 注入风险 - BaseModel
**位置**: `app/Models/BaseModel.php:41-48`

**问题**: 动态列名和 ORDER BY 参数未验证

**修复方案**:
```php
// 在 findAll() 方法中添加白名单验证
$allowedColumns = ['id', 'status', 'user_id', 'product_id', 'create_time'];
foreach ($conditions as $column => $value) {
    if (!in_array($column, $allowedColumns)) {
        throw new InvalidArgumentException("Invalid column: {$column}");
    }
    $conditionParts[] = "{$column} = ?";
    $params[] = $value;
}

// ORDER BY 验证
if ($orderBy) {
    if (!preg_match('/^[a-zA-Z_]+(\s+(ASC|DESC))?$/i', $orderBy)) {
        throw new InvalidArgumentException("Invalid orderBy format");
    }
    $sql .= " ORDER BY {$orderBy}";
}
```

#### 2. 开放重定向风险
**位置**: `app/Middleware/AuthMiddleware.php:10`

**修复方案**:
```php
$redirectUrl = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($redirectUrl);
if ($parsedUrl && isset($parsedUrl['path'])) {
    $redirectUrl = $parsedUrl['path'];
    if (isset($parsedUrl['query'])) {
        $redirectUrl .= '?' . $parsedUrl['query'];
    }
}
$_SESSION['redirect_url'] = $redirectUrl;
```

#### 3. 验证码安全
**位置**: `app/Controllers/AuthController.php:129-132`

**修复方案**:
```php
function generateCaptchaCode($length = 4) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}
```

#### 4. 订单号重复风险
**位置**: `app/Models/OrderModel.php:10`

**修复方案**:
```php
$orderNo = date('YmdHis') . bin2hex(random_bytes(4));
```

### ✅ 已实现的安全措施

1. **SQL 注入防护**: 使用 PDO 预处理语句
2. **XSS 防护**: 输出使用 `htmlspecialchars()`
3. **CSRF 防护**: 表单 Token 验证
4. **密码安全**: bcrypt 加密（cost=12）
5. **会话安全**: `session_regenerate_id(true)`
6. **文件上传**: MIME 类型白名单验证
7. **错误处理**: 生产环境隐藏详细错误

---

## 💻 开发约定

### 命名规范

| 类型 | 规范 | 示例 |
|------|------|------|
| 类名 | PascalCase | `UserController` |
| 方法名 | camelCase | `getUserById()` |
| 变量名 | camelCase | `$userName` |
| 常量 | UPPER_SNAKE_CASE | `MAX_FILE_SIZE` |
| 数据库表 | snake_case (复数) | `users`, `orders` |
| 数据库字段 | snake_case | `create_time` |

### 代码风格

```php
// ✅ 推荐：添加类型声明
public function find(int $id): ?array {
    // ...
}

// ✅ 推荐：使用 null 合并运算符
$name = $user['name'] ?? 'Guest';

// ✅ 推荐：严格类型比较
if ($status === 1) {
    // ...
}

// ❌ 避免：松散比较
if ($status == 1) {
    // ...
}

// ❌ 避免：硬编码魔法数字
$maxDownloads = 5; // 应提取为常量

// ✅ 推荐：使用常量
define('MAX_DAILY_DOWNLOADS', 5);
$maxDownloads = MAX_DAILY_DOWNLOADS;
```

### 数据库操作

```php
// ✅ 推荐：使用 PDO 预处理
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// ❌ 避免：SQL 拼接
$sql = "SELECT * FROM users WHERE id = " . $userId; // 危险！

// ✅ 推荐：使用事务处理关键操作
try {
    $db->beginTransaction();
    // ... 多个操作
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### 错误处理

```php
// ✅ 推荐：记录详细日志，返回友好错误
try {
    // ...
} catch (\Exception $e) {
    error_log(sprintf(
        '[Context] 操作失败 - 用户ID: %d, 错误: %s',
        $userId,
        $e->getMessage()
    ));
    $_SESSION['error'] = '操作失败，请稍后再试';
    redirect('/path');
}

// ❌ 避免：使用 @ 抑制错误
@unlink($file); // 不利于调试

// ❌ 避免：直接暴露异常信息
$_SESSION['error'] = $e->getMessage(); // 可能泄露敏感信息
```

---

## 🔧 常见开发任务

### 添加新控制器

1. 创建控制器文件 `app/Controllers/XxxController.php`
2. 继承基础结构（可选）
3. 在 `core/Router.php` 中注册路由
4. 创建对应视图文件

```php
<?php
namespace App\Controllers;

class XxxController {
    public function index() {
        // 业务逻辑
        view('xxx/index', $data);
    }
}
```

### 添加新模型

1. 创建模型文件 `app/Models/XxxModel.php`
2. 继承 `BaseModel`
3. 实现业务方法

```php
<?php
namespace App\Models;

class XxxModel extends BaseModel {
    protected $table = 'xxx';
    
    public function getByStatus(int $status): array {
        return $this->findAll(['status' => $status]);
    }
}
```

### 添加数据库迁移

1. 创建 SQL 文件 `database_update_xxx.sql`
2. 记录变更说明
3. 执行迁移

```sql
-- 示例：添加新字段
ALTER TABLE products ADD COLUMN stock INT DEFAULT 0;
CREATE INDEX idx_products_status ON products(status);
```

---

## 🧪 测试指南

### 测试清单

#### 功能测试
- [ ] 用户注册/登录流程
- [ ] 商品浏览和搜索
- [ ] 购物车添加/删除
- [ ] 支付流程（支付宝沙箱）
- [ ] 文件下载权限验证
- [ ] 管理后台功能

#### 安全测试
- [ ] SQL 注入测试（使用 sqlmap）
- [ ] XSS 漏洞测试
- [ ] CSRF Token 验证
- [ ] 文件上传安全测试
- [ ] 会话安全测试

#### 性能测试
- [ ] 页面加载速度（< 3秒）
- [ ] 数据库查询优化
- [ ] 并发访问测试

### 测试命令

```bash
# 检查 PHP 语法错误
php -l app/Controllers/*.php

# 检查代码风格（如安装了 PHP_CodeSniffer）
phpcs --standard=PSR12 app/

# 数据库备份
mysqldump -u root -p bookmusic_mall > backup.sql
```

---

## 🚀 部署指南

### 生产环境配置

1. **配置文件**
   ```bash
   cp includes/config.php.example includes/config.php
   # 编辑 config.php，填写真实配置
   ```

2. **环境变量**
   ```bash
   # .env 文件
   DB_HOST=localhost
   DB_NAME=bookmusic_mall
   DB_USER=your_user
   DB_PASS=your_password
   
   ALIPAY_APP_ID=your_app_id
   ALIPAY_PRIVATE_KEY=your_private_key
   RESEND_API_KEY=your_api_key
   SENTRY_DSN=your_dsn
   ```

3. **目录权限**
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 public/assets/
   chmod 600 includes/config.php
   chmod 600 .env
   ```

4. **Web 服务器配置**

   **Nginx**:
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;
       root /path/to/bookmusic/public;
       index index.php;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }
       
       # 禁止访问敏感目录
       location ~ /(includes|app|core|views) {
           deny all;
       }
   }
   ```

5. **HTTPS 配置**
   ```bash
   # 使用 Let's Encrypt
   certbot --nginx -d yourdomain.com
   ```

### 部署检查清单

- [ ] 修改默认管理员密码
- [ ] 配置 HTTPS
- [ ] 设置正确的文件权限
- [ ] 配置支付宝真实密钥
- [ ] 配置 Resend API Key
- [ ] 配置 Sentry DSN
- [ ] 设置数据库备份计划
- [ ] 配置日志轮转
- [ ] 禁用详细错误信息
- [ ] 设置 PHP opcache

---

## 🐛 常见问题

### 1. 支付回调失败

**症状**: 支付宝回调返回 fail

**排查**:
1. 检查支付宝密钥配置
2. 查看错误日志 `error_log()`
3. 验证签名算法（RSA2）
4. 检查订单状态是否已支付

### 2. 文件上传失败

**症状**: 上传文件时返回错误

**排查**:
1. 检查 `uploads/` 目录权限
2. 验证文件类型是否在白名单
3. 检查文件大小限制（php.ini）
4. 查看磁盘空间

### 3. 购物车数据异常

**症状**: 购物车商品数量不正确

**排查**:
1. 检查 `cart` 表数据
2. 验证用户登录状态
3. 检查商品是否存在/上架

### 4. Sentry 错误监控不工作

**症状**: 错误未上报到 Sentry

**排查**:
1. 检查 `SENTRY_DSN` 配置
2. 验证 Sentry SDK 是否安装
3. 检查网络连接
4. 查看 Sentry 项目设置

---

## 📚 相关文档

| 文档 | 说明 |
|------|------|
| [README.md](README.md) | 项目说明和安装指南 |
| [Plan.md](Plan.md) | 项目架构和功能规划 |
| [PAYMENT_README.md](PAYMENT_README.md) | 支付宝支付集成指南 |
| [RESEND_README.md](RESEND_README.md) | 邮件服务配置 |
| [SENTRY_README.md](SENTRY_README.md) | 错误监控配置 |
| [CART_PAYMENT_TEST.md](CART_PAYMENT_TEST.md) | 购物车测试清单 |

---

## 🔍 性能优化建议

### 数据库优化

```sql
-- 添加索引
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_products_type ON products(type);
CREATE INDEX idx_cart_user_id ON cart(user_id);

-- 查询优化
EXPLAIN SELECT * FROM orders WHERE user_id = 1 AND status = 'paid';
```

### 缓存策略

```php
// 建议使用 Redis 缓存热点数据
// 示例：商品详情缓存
$cacheKey = "product:{$productId}";
$cached = $redis->get($cacheKey);
if ($cached) {
    return json_decode($cached, true);
}
// 查询数据库
$data = $productModel->find($productId);
$redis->setex($cacheKey, 3600, json_encode($data));
```

### N+1 查询优化

```php
// ❌ 避免：循环内查询
foreach ($orders as $order) {
    $product = $productModel->find($order['product_id']);
}

// ✅ 推荐：批量查询
$productIds = array_column($orders, 'product_id');
$products = $productModel->findByIds($productIds);
```

---

## 🎯 开发优先级

### 高优先级（立即修复）
1. BaseModel SQL 注入风险
2. 开放重定向漏洞
3. 验证码安全随机数
4. 订单号生成优化

### 中优先级（短期修复）
1. CSRF Token 刷新机制
2. 支付金额验证增强
3. 类型比较严格化
4. 添加数据库索引

### 低优先级（长期改进）
1. 添加类型声明
2. 统一命名规范
3. 完善日志上下文
4. 创建验证中间件
5. 实现缓存机制

---

## 📝 代码审查摘要

### 最近审查结果（2026-06-06）

**总体评分**: ⭐⭐⭐⭐ (4/5)

**优点**:
- ✅ MVC 架构清晰
- ✅ 安全措施完善（PDO、CSRF、bcrypt）
- ✅ 错误处理规范
- ✅ 代码组织良好

**需改进**:
- ⚠️ 4 个高优先级安全问题
- ⚠️ 3 个性能优化点
- ⚠️ 4 个代码规范问题
- ⚠️ 5 个潜在 Bug

**详细报告**: 见上方"安全注意事项"章节

---

## 🤝 贡献指南

### 提交代码前

1. 运行语法检查
   ```bash
   php -l your_file.php
   ```

2. 遵循代码风格规范

3. 添加必要的注释

4. 更新相关文档

5. 测试功能完整性

### Git 提交规范

```
feat: 添加新功能
fix: 修复 Bug
docs: 文档更新
style: 代码格式调整
refactor: 代码重构
test: 测试相关
chore: 构建/工具链相关
```

---

## 📞 支持

如有问题或建议，请：
1. 查看相关文档
2. 检查错误日志
3. 联系开发团队：support@bookmusic.com

---

**最后更新**: 2026-06-06  
**维护者**: 开发团队  
**版本**: 1.0.0
