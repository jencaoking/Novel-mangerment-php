# BookMusic Mall - 小说与音乐数字内容销售平台

## 项目简介

BookMusic Mall 是一个基于 PHP 开发的小说与音乐数字内容销售平台，采用 MVC 架构设计，提供优质的小说和音乐资源下载服务。

## 功能特性

### 用户端
- ✅ 用户注册与登录系统
- ✅ 小说商城浏览与搜索
- ✅ 音乐商城浏览与搜索
- ✅ 商品详情展示
- ✅ **在线购买功能（支付宝支付集成）**
- ✅ **微信支付集成（Native 扫码支付）**
- ✅ **购物车 + 批量合并支付**
- ✅ **商品收藏/心愿单**
- ✅ 文件下载权限管理（每日限频）
- ✅ **商品评价系统（1-5星评分 + 文字评价）**
- ✅ 用户中心（个人资料、订单管理、收藏、下载记录）
- ✅ **邮件通知服务（Resend API 集成）**
- ✅ **错误监控与性能追踪（Sentry 集成）**

### 管理功能
- ✅ 管理员登录
- ✅ 用户管理（启用/禁用）
- ✅ 商品管理（增删改查、上下架）
- ✅ **分类管理（小说/音乐分类 CRUD）**
- ✅ 订单管理（状态更新）
- ✅ **评价管理（显示/隐藏）**
- ✅ 数据统计（月度报表、热销排行）

### 技术特性
- ✅ 安全的密码加密（bcrypt）
- ✅ CSRF 防护
- ✅ SQL 注入防护（PDO 预处理）
- ✅ XSS 防护
- ✅ 文件上传安全验证（MIME 类型检测）
- ✅ **响应式设计 + 暗色模式**
- ✅ **无障碍访问（WCAG AA 焦点状态、prefers-reduced-motion）**
- ✅ MVC 架构设计
- ✅ 自动加载机制（PSR-4）
- ✅ **实时错误监控（Sentry）**

## 技术栈

### 后端
- PHP 8.2+
- MySQL 8.0+
- PDO 数据库连接
- Composer 依赖管理

### 前端
- HTML5 + CSS3（CSS Custom Properties + 暗色模式）
- JavaScript (ES6+)
- Bootstrap 5.3
- Bootstrap Icons
- Playfair Display + Inter + Noto Sans SC 字体
- 响应式设计

## 安装说明

### 1. 环境要求
- PHP >= 8.2
- MySQL >= 8.0
- Apache/Nginx Web 服务器
- Composer（可选，用于自动加载）

### 2. 安装步骤

1. **克隆或下载项目**
   ```bash
   git clone https://github.com/jencaoking/Novel-mangerment-php.git
   cd Novel-mangerment-php
   ```

2. **安装依赖（可选）**
   ```bash
   composer install
   ```

3. **配置数据库**
   - 创建 MySQL 数据库
   - 导入数据库脚本
   ```bash
   mysql -u root -p bookmusic < database/database.sql
   mysql -u root -p bookmusic < database/database_update_alipay.sql
   mysql -u root -p bookmusic < database/database_update_wechat_pay.sql
   mysql -u root -p bookmusic < database/database_update_cart_batch.sql
   mysql -u root -p bookmusic < database/database_update_favorites.sql
   ```

4. **配置环境变量**
   - 复制 `.env.example` 为 `.env`（或直接编辑 `.env`）
   - 修改数据库连接信息和支付配置

5. **配置 Web 服务器**

   **Apache 配置示例：**
   ```apache
   <VirtualHost *:80>
       ServerName bookmusic.local
       DocumentRoot /path/to/bookmusic/public
       
       <Directory /path/to/bookmusic/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   **Nginx 配置示例：**
   ```nginx
   server {
       listen 80;
       server_name bookmusic.local;
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
   }
   ```

6. **设置目录权限**
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 public/assets/
   ```

7. **访问网站**
   - 打开浏览器访问配置的域名
   - 默认管理员账户：
     - 用户名：`admin`
     - 密码：`admin123`
   - **请登录后立即修改默认密码**

## 目录结构

```
bookmusic/
├── app/                        # 应用核心
│   ├── Controllers/            # 控制器层
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── CartController.php
│   │   ├── HomeController.php
│   │   ├── PaymentController.php
│   │   ├── ProductController.php
│   │   └── UserController.php
│   ├── Middleware/             # 中间件
│   │   ├── AdminMiddleware.php
│   │   └── AuthMiddleware.php
│   └── Models/                # 模型层
│       ├── BaseModel.php
│       ├── CartModel.php
│       ├── CategoryModel.php
│       ├── DownloadModel.php
│       ├── FavoriteModel.php
│       ├── OrderModel.php
│       ├── ProductModel.php
│       ├── ReviewModel.php
│       └── UserModel.php
├── core/                       # 核心框架
│   ├── Autoloader.php
│   ├── Container.php
│   ├── Middleware/
│   └── Router.php
├── database/                   # 数据库脚本
│   ├── database.sql
│   ├── database_update_alipay.sql
│   ├── database_update_cart_batch.sql
│   ├── database_update_favorites.sql
│   └── database_update_wechat_pay.sql
├── includes/                   # 公共模块
│   ├── AlipaySDK.php
│   ├── WechatPaySDK.php
│   ├── ResendSDK.php
│   ├── auth.php
│   ├── config.php
│   ├── db.php
│   └── functions.php
├── public/                     # 公共访问目录
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css       # 前台样式（含暗色模式）
│   │   │   └── admin.css       # 后台样式
│   │   └── js/
│   │       └── main.js
│   ├── index.php               # 入口文件
│   └── .htaccess
├── views/                      # 视图层
│   ├── _header.phtml
│   ├── _footer.phtml
│   ├── index.phtml
│   ├── novels.phtml
│   ├── music.phtml
│   ├── product.phtml
│   ├── admin/                  # 后台管理视图
│   │   ├── _header.phtml
│   │   ├── _footer.phtml
│   │   ├── dashboard.phtml
│   │   ├── products.phtml
│   │   ├── product_edit.phtml
│   │   ├── categories.phtml
│   │   ├── users.phtml
│   │   ├── orders.phtml
│   │   ├── reviews.phtml
│   │   └── stats.phtml
│   ├── auth/                   # 认证视图
│   │   ├── login.phtml
│   │   └── register.phtml
│   └── user/                   # 用户中心视图
│       ├── index.phtml
│       ├── profile.phtml
│       ├── orders.phtml
│       ├── favorites.phtml
│       ├── downloads.phtml
│       └── cart.phtml
├── uploads/                    # 文件存储
├── logs/                       # 日志目录
├── cert/                       # 微信支付证书
├── .env                        # 环境变量配置
├── composer.json
└── README.md
```

## 使用说明

### 用户功能
1. **注册账户**：访问注册页面创建账户
2. **浏览商品**：在小说或音乐商城浏览商品
3. **收藏商品**：点击心形图标收藏感兴趣的商品
4. **加入购物车**：将商品加入购物车
5. **购买商品**：选择支付宝或微信支付完成购买
6. **下载文件**：购买后可在用户中心下载文件
7. **评价商品**：购买后可对商品进行评分和评价

### 管理员功能
1. **登录后台**：使用管理员账户登录
2. **仪表盘**：查看销售概览、热销排行、最近订单
3. **商品管理**：添加、编辑、删除商品，支持上下架
4. **分类管理**：管理小说和音乐的商品分类
5. **用户管理**：查看和管理用户账户
6. **订单管理**：查看和处理订单状态
7. **评价管理**：审核用户评价，支持隐藏/显示
8. **数据统计**：查看月度销售报表

## 安全建议

1. **修改默认密码**：立即修改管理员默认密码
2. **配置 HTTPS**：生产环境必须使用 HTTPS
3. **配置支付密钥**：填写真实的支付宝/微信支付密钥
4. **配置 Resend API Key**：设置邮件服务的 API 密钥
5. **定期备份**：定期备份数据库和上传文件
6. **文件权限**：确保上传目录不可执行 PHP
7. **更新维护**：定期更新 PHP 和 MySQL 版本

## 开发说明

### MVC 架构
项目采用 MVC（Model-View-Controller）架构模式：
- **Model**：处理数据逻辑和数据库操作（`app/Models/`）
- **View**：展示数据和用户界面（`views/`）
- **Controller**：处理请求和协调 Model 与 View（`app/Controllers/`）

### 添加新功能
1. 在 `app/Controllers/` 创建控制器类
2. 在 `app/Models/` 创建模型类（如需要）
3. 在 `views/` 创建视图模板
4. 在 `public/index.php` 配置路由

### 自定义样式
- 编辑 `public/assets/css/style.css` 修改全局样式
- 使用 CSS Custom Properties 统一管理颜色和间距
- 支持 `prefers-color-scheme: dark` 自动暗色模式

### 数据库操作
- 使用 PDO 预处理语句防止 SQL 注入
- 参考 `includes/db.php` 获取数据库连接
- 模型类继承 `BaseModel` 实现数据库操作

## 许可证

本项目仅供学习和研究使用。

## 联系方式

如有问题或建议，请联系：support@bookmusic.com

---

**📚 相关文档**

| 文档 | 说明 |
|------|------|
| [支付宝支付集成说明](PAYMENT_README.md) | 支付宝支付完整配置指南 |
| [Resend 邮件服务集成指南](RESEND_README.md) | 邮件通知服务配置 |
| [Sentry 错误监控集成指南](SENTRY_README.md) | 错误监控配置 |
| [项目计划与功能清单](Plan.md) | 项目架构设计和功能规划 |
| [购物车支付测试清单](CART_PAYMENT_TEST.md) | 购物车合并支付测试用例 |
