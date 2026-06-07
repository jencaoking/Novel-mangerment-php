
# BookMusic Mall - 小说与音乐数字内容销售平台

## 一、项目定位

### 1.1 项目名称
**BookMusic Mall** - 小说与音乐数字内容销售平台

### 1.2 项目目标
- 在线销售 TXT 小说
- 在线销售 MP3 音乐
- 用户注册登录系统
- 在线支付集成（微信/支付宝）
- 购买后下载权限管理
- 后台管理系统
- 用户中心

### 1.3 项目愿景
打造一个简洁、安全、高效的数字内容交易平台，为用户提供优质的小说和音乐资源下载服务。

---

## 二、功能模块规划

### 2.1 用户端

#### 2.1.1 首页 (`/index.php`)
| 功能 | 描述 |
|------|------|
| 轮播图 | 展示热门商品和促销活动 |
| 热门小说 | 按销量/评分排序展示 |
| 热门音乐 | 按销量/评分排序展示 |
| 最新上架 | 近期上架的商品 |
| 搜索框 | 支持关键词搜索 |
| 分类导航 | 小说/音乐分类入口 |

#### 2.1.2 用户注册 (`/register.php`)
| 字段 | 类型 | 验证规则 |
|------|------|----------|
| 用户名 | 字符串 | 3-20字符，唯一 |
| 邮箱 | 字符串 | 有效邮箱格式，唯一 |
| 密码 | 字符串 | 6-20字符 |
| 确认密码 | 字符串 | 与密码一致 |
| 验证码 | 字符串 | 图形验证码校验 |

#### 2.1.3 用户登录 (`/login.php`)
| 字段 | 类型 | 验证规则 |
|------|------|----------|
| 用户名/邮箱 | 字符串 | 必填 |
| 密码 | 字符串 | 必填 |
| 记住登录 | 布尔 | 可选，有效期7天 |

#### 2.1.4 小说商城 (`/novels.php`)
- **分类筛选**：玄幻、都市、仙侠等
- **搜索功能**：支持书名、作者搜索
- **排序方式**：最新、最热、价格（升/降）
- **分页展示**：每页12/24条

#### 2.1.5 音乐商城 (`/music.php`)
- **分类筛选**：流行、摇滚、电子等
- **在线试听**：30秒预览
- **搜索功能**：支持歌名、歌手搜索
- **排序方式**：最新、最热、价格

#### 2.1.6 商品详情 (`/product.php?id={id}`)
| 展示内容 | 说明 |
|----------|------|
| 封面图片 | 商品封面展示 |
| 商品名称 | 书名/歌曲名 |
| 作者/歌手 | 创作者信息 |
| 简介描述 | 商品介绍 |
| 价格 | 售价金额 |
| 销量 | 累计购买数量 |
| 购买按钮 | 立即购买入口 |

#### 2.1.7 用户中心 (`/user/`)
| 功能模块 | 说明 |
|----------|------|
| 个人资料 | 头像、昵称、邮箱等信息管理 |
| 我的订单 | 订单列表、订单状态查看 |
| 我的下载 | 已购买商品下载记录 |
| 修改密码 | 安全设置 |
| 退出登录 | 安全退出 |

---

### 2.2 管理员后台

#### 2.2.1 管理员登录 (`/admin/login.php`)
- 用户名/密码验证
- 仅管理员角色可访问

#### 2.2.2 仪表盘 (`/admin/dashboard.php`)
| 统计指标 | 说明 |
|----------|------|
| 总用户数 | 平台注册用户总量 |
| 总订单数 | 累计订单数量 |
| 总销售额 | 累计交易金额 |
| 今日收入 | 当日交易金额 |
| 热门商品 | Top10 销量排行 |

#### 2.2.3 用户管理 (`/admin/users.php`)
- 查看用户列表
- 用户状态管理（封禁/解封）
- 删除用户
- 用户搜索

#### 2.2.4 商品管理 (`/admin/products.php`)
**小说管理**：
- 上传TXT文件
- 编辑商品信息
- 删除商品
- 状态管理（上架/下架）

**音乐管理**：
- 上传MP3文件
- 上传封面图片
- 上传试听片段（30秒）
- 编辑商品信息
- 删除商品

#### 2.2.5 订单管理 (`/admin/orders.php`)
- 订单列表查看
- 订单状态更新
- 退款处理
- 订单详情

#### 2.2.6 数据统计 (`/admin/stats.php`)
| 统计维度 | 说明 |
|----------|------|
| 收入统计 | 按日/周/月统计 |
| 下载统计 | 下载量趋势分析 |
| 销量排行 | 商品销量TOP榜 |
| 用户增长 | 注册用户趋势 |

---

## 三、数据库设计

### 3.1 数据库表结构

#### 3.1.1 users（用户表）
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status TINYINT DEFAULT 1 COMMENT '1-正常 0-封禁',
    last_login DATETIME DEFAULT NULL,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 3.1.2 products（商品表）
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    type ENUM('novel', 'music') NOT NULL COMMENT '小说/音乐',
    category_id INT NOT NULL,
    author VARCHAR(100) DEFAULT NULL COMMENT '作者/歌手',
    description TEXT DEFAULT NULL,
    cover VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    preview_path VARCHAR(255) DEFAULT NULL COMMENT '音乐试听路径',
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    downloads INT DEFAULT 0,
    sales INT DEFAULT 0,
    status TINYINT DEFAULT 1 COMMENT '1-上架 0-下架',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

#### 3.1.3 categories（分类表）
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('novel', 'music') NOT NULL COMMENT '小说分类/音乐分类',
    sort_order INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 小说分类示例
INSERT INTO categories (name, type) VALUES 
('玄幻', 'novel'), ('都市', 'novel'), ('仙侠', 'novel'), 
('悬疑', 'novel'), ('科幻', 'novel'), ('言情', 'novel');

-- 音乐分类示例
INSERT INTO categories (name, type) VALUES 
('流行', 'music'), ('摇滚', 'music'), ('电子', 'music'), 
('古典', 'music'), ('民谣', 'music'), ('嘻哈', 'music');
```

#### 3.1.4 orders（订单表）
```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_no VARCHAR(64) UNIQUE NOT NULL COMMENT '订单编号',
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid', 'paid', 'cancelled', 'refunded') DEFAULT 'unpaid',
    pay_time DATETIME DEFAULT NULL,
    cancel_time DATETIME DEFAULT NULL,
    refund_time DATETIME DEFAULT NULL,
    refund_reason VARCHAR(255) DEFAULT NULL,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### 3.1.5 downloads（下载记录表）
```sql
CREATE TABLE downloads (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id BIGINT NOT NULL COMMENT '关联订单',
    ip VARCHAR(50) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

#### 3.1.6 cart（购物车表）
```sql
CREATE TABLE cart (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY (user_id, product_id)
);
```

---

## 四、文件存储规划

### 4.1 目录结构
```
uploads/
├── novels/          # 小说文件存储
│   ├── 001.txt
│   └── 002.txt
├── music/           # 音乐文件存储
│   ├── 001.mp3
│   └── 002.mp3
├── preview/         # 音乐试听片段
│   ├── 001.mp3
│   └── 002.mp3
├── cover/           # 封面图片
│   ├── 001.jpg
│   └── 002.png
└── avatar/          # 用户头像
    ├── 001.jpg
    └── 002.png
```

### 4.2 文件命名规范
- **小说文件**: `{product_id}.txt`
- **音乐文件**: `{product_id}.mp3`
- **试听文件**: `{product_id}_preview.mp3`
- **封面图片**: `{product_id}.{ext}`
- **用户头像**: `{user_id}.{ext}`

---

## 五、项目目录结构

```
bookmusic/
├── index.php          # 首页
├── login.php          # 用户登录
├── register.php       # 用户注册
├── logout.php         # 用户退出
├── novels.php         # 小说商城
├── music.php          # 音乐商城
├── product.php        # 商品详情
├── buy.php            # 购买处理
├── download.php       # 下载处理
├── user/              # 用户中心
│   ├── index.php      # 用户中心首页
│   ├── profile.php    # 个人资料
│   ├── orders.php     # 我的订单
│   └── downloads.php  # 我的下载
├── admin/             # 管理后台
│   ├── login.php      # 管理员登录
│   ├── dashboard.php  # 仪表盘
│   ├── users.php      # 用户管理
│   ├── products.php   # 商品管理
│   ├── orders.php     # 订单管理
│   ├── stats.php      # 数据统计
│   └── upload.php     # 文件上传
├── includes/          # 公共模块
│   ├── db.php         # 数据库连接
│   ├── auth.php       # 认证授权
│   ├── functions.php  # 工具函数
│   └── config.php     # 配置文件
├── uploads/           # 文件存储
└── assets/            # 静态资源
    ├── css/           # 样式文件
    ├── js/            # 脚本文件
    └── images/        # 公共图片
```

---

## 六、支付系统设计

### 6.1 支付方式
- **微信支付**：微信公众号/H5支付 (待实现)
- **支付宝支付**：PC网站支付 + H5支付 ✅ **已实现**

### 6.2 支付流程

```
用户点击购买
    ↓
创建订单 (orders表, 状态:pending)
    ↓
生成支付宝支付链接
    ↓
跳转到支付宝支付页面
    ↓
用户完成支付
    ↓
支付宝异步通知服务器 (/payment/notify)
    ↓
验证签名、校验金额
    ↓
更新订单状态为paid, 记录trade_no和pay_time
    ↓
增加商品销量
    ↓
用户浏览器同步返回 (/payment/return)
    ↓
显示支付成功
```

### 6.3 回调处理
- **异步回调** (`POST /payment/notify`)：
  - ✅ 验证RSA2签名
  - ✅ 检查订单是否存在
  - ✅ 幂等性处理(避免重复回调)
  - ✅ 验证订单金额
  - ✅ 更新订单状态为paid
  - ✅ 记录支付宝交易号(trade_no)
  - ✅ 更新支付时间(pay_time)
  - ✅ 增加商品销量
  
- **同步回调** (`GET /payment/return`)：
  - ✅ 验证签名
  - ✅ 跳转至订单列表页
  - ✅ 显示支付结果

### 6.4 技术实现
- **SDK**: 自研轻量级AlipaySDK类 (`includes/AlipaySDK.php`)
- **控制器**: `app/Controllers/PaymentController.php`
- **配置**: `.env` 和 `includes/config.php`
- **安全**: RSA2签名验证、金额校验、幂等性处理
- **数据库**: orders表新增 `trade_no` 字段

---

## 七、下载权限设计

### 7.1 下载流程

```
用户点击下载
    ↓
检查用户登录状态
    ↓ (未登录)
跳转登录页
    ↓ (已登录)
检查订单记录
    ↓ (无订单)
提示未购买
    ↓ (有订单)
检查订单状态是否为paid
    ↓ (未支付)
提示未支付
    ↓ (已支付)
生成临时下载链接(有效期5分钟)
    ↓
开始下载
    ↓
记录下载日志(downloads表)
```

### 7.2 安全措施
- 下载链接带签名验证
- 限制同一订单每日下载次数
- 记录下载IP和UA

---

## 八、安全设计

### 8.1 密码安全
```php
// 密码加密
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// 密码验证
if (password_verify($password, $hashedPassword)) {
    // 验证通过
}
```

### 8.2 登录安全
- Session管理：设置合理过期时间
- 登录失败次数限制（5次/15分钟）
- 验证码：登录失败后显示图形验证码

### 8.3 CSRF防护
```php
// 生成Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 表单中隐藏字段
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// 验证Token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF验证失败');
}
```

### 8.4 SQL注入防护
- 使用PDO预处理语句
- 禁止SQL拼接
- 参数化查询

### 8.5 文件上传安全
| 允许类型 | 禁止类型 |
|----------|----------|
| .txt | .php |
| .mp3 | .exe |
| .jpg/.jpeg | .js |
| .png | .bat |
| .gif | .html/.htm |

- 文件类型白名单校验
- 文件大小限制（小说<50MB，音乐<10MB）
- 文件重命名防止路径遍历
- 存储目录禁止执行权限

### 8.6 XSS防护
- 输出内容HTML转义
- 使用htmlspecialchars()
- 富文本内容过滤

---

## 九、API接口设计

### 9.1 用户接口

| 接口 | 方法 | 描述 |
|------|------|------|
| `/api/user/login` | POST | 用户登录 |
| `/api/user/register` | POST | 用户注册 |
| `/api/user/profile` | GET/POST | 获取/更新用户信息 |
| `/api/user/logout` | POST | 用户退出 |

### 9.2 商品接口

| 接口 | 方法 | 描述 |
|------|------|------|
| `/api/products` | GET | 获取商品列表 |
| `/api/products/{id}` | GET | 获取商品详情 |
| `/api/products/search` | GET | 商品搜索 |

### 9.3 订单接口

| 接口 | 方法 | 描述 |
|------|------|------|
| `/api/orders` | POST/GET | 创建订单/获取订单列表 |
| `/api/orders/{id}` | GET | 获取订单详情 |
| `/api/orders/{id}/pay` | POST | 订单支付 |

---

## 十、UI设计

### 10.1 设计风格
- **风格**: 简洁现代、暗色模式支持
- **响应式**: 支持桌面/平板/手机
- **框架**: Bootstrap 5

### 10.2 配色方案
| 颜色类型 | 颜色值 | 用途 |
|----------|--------|------|
| 主色 | #3B82F6 | 按钮、链接、强调元素 |
| 辅助色 | #10B981 | 成功状态、进度条 |
| 警告色 | #F59E0B | 警告提示 |
| 危险色 | #EF4444 | 删除、错误提示 |
| 背景色 | #F5F7FA | 页面背景 |
| 卡片色 | #FFFFFF | 卡片背景 |
| 文字色 | #1F2937 | 正文文字 |

---

## 十一、开发阶段规划

### 阶段一：基础系统（第1周）
| 任务 | 描述 | 责任人 |
|------|------|--------|
| 环境搭建 | PHP+MySQL环境配置 | 后端开发 |
| 数据库设计 | 表结构创建 | 后端开发 |
| 用户注册 | 注册页面+逻辑 | 全栈开发 |
| 用户登录 | 登录页面+逻辑 | 全栈开发 |
| 用户中心 | 基础页面框架 | 全栈开发 |

### 阶段二：商品系统（第2周）
| 任务 | 描述 | 责任人 |
|------|------|--------|
| 小说商城 | 列表页、筛选、排序 | 全栈开发 |
| 音乐商城 | 列表页、试听功能 | 全栈开发 |
| 商品详情 | 详情页展示 | 全栈开发 |
| 搜索功能 | 全文搜索 | 后端开发 |

### 阶段三：后台管理（第3周）
| 任务 | 描述 | 责任人 |
|------|------|--------|
| 管理员登录 | 后台登录页 | 全栈开发 |
| 仪表盘 | 数据统计展示 | 全栈开发 |
| 用户管理 | 用户列表、状态管理 | 全栈开发 |
| 商品管理 | 上传、编辑、删除 | 全栈开发 |

### 阶段四：订单系统（第4周）
| 任务 | 描述 | 责任人 |
|------|------|--------|
| 订单创建 | 购买流程 | 后端开发 |
| 支付集成 | 微信/支付宝支付 | 后端开发 |
| 下载权限 | 下载验证逻辑 | 后端开发 |
| 订单管理 | 后台订单处理 | 全栈开发 |

### 阶段五：优化上线（第5周）
| 任务 | 描述 | 责任人 |
|------|------|--------|
| 安全加固 | XSS/CSRF/SQL注入防护 | 后端开发 |
| 性能优化 | 缓存、图片压缩 | 后端开发 |
| Nginx部署 | 服务器配置 | 运维 |
| HTTPS配置 | SSL证书配置 | 运维 |
| 数据备份 | 备份策略配置 | 运维 |

---

## 十二、技术栈

### 前端
| 技术 | 版本 | 说明 |
|------|------|------|
| HTML5 | - | 页面结构 |
| CSS3 | - | 样式设计 |
| Bootstrap | 5.x | UI框架 |
| JavaScript | ES6+ | 交互逻辑 |
| jQuery | 3.x | DOM操作 |

### 后端
| 技术 | 版本 | 说明 |
|------|------|------|
| PHP | 8.2+ | 服务端语言 |
| PDO | - | 数据库连接 |
| MySQL | 8.0+ | 数据库 |

### 服务器
| 技术 | 版本 | 说明 |
|------|------|------|
| Nginx | 1.20+ | Web服务器 |
| PHP-FPM | 8.2+ | PHP进程管理 |
| Linux | Ubuntu 22.04 | 操作系统 |

### 缓存（可选）
| 技术 | 版本 | 说明 |
|------|------|------|
| Redis | 7.x | 缓存服务 |

### 文件存储
| 方案 | 说明 |
|------|------|
| 本地存储 | 初期使用 |
| MinIO | 后期扩展 |

---

## 十三、关键技术点

### 13.1 文件上传处理
- 使用PHP原生文件上传函数
- 验证文件类型和大小
- 文件重命名防止覆盖
- 存储路径安全处理

### 13.2 支付集成
- 微信支付API对接
- 支付宝API对接
- 异步回调处理
- 订单状态同步

### 13.3 权限控制
- 用户角色区分（普通用户/管理员）
- 页面访问权限校验
- 操作权限校验

### 13.4 数据统计
- 使用GROUP BY统计
- 日期范围筛选
- 图表展示（使用Chart.js）

---

## 十四、风险评估

| 风险点 | 风险等级 | 应对措施 |
|--------|----------|----------|
| 文件存储容量 | 中 | 定期清理过期文件，后期迁移至对象存储 |
| 支付安全 | 高 | 使用官方SDK，HTTPS加密传输 |
| 并发访问 | 中 | 使用Redis缓存热点数据 |
| 数据备份 | 高 | 每日自动备份，异地存储 |
| 恶意攻击 | 高 | WAF防护，日志监控 |

---

## 十五、上线准备清单

### 15.1 功能测试
- [ ] 用户注册登录
- [ ] 商品浏览搜索
- [ ] 订单创建支付
- [ ] 文件下载
- [ ] 后台管理

### 15.2 安全测试
- [ ] SQL注入测试
- [ ] XSS漏洞测试
- [ ] CSRF防护测试
- [ ] 文件上传安全

### 15.3 性能测试
- [ ] 页面加载速度
- [ ] 数据库查询优化
- [ ] 并发访问测试

### 15.4 环境配置
- [ ] Nginx配置
- [ ] HTTPS证书
- [ ] 数据库配置
- [ ] 日志配置
