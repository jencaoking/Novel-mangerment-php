# BookMusic Mall - 小说与音乐数字内容销售平台

## 项目简介

BookMusic Mall 是一个基于 PHP 开发的小说与音乐数字内容销售平台，提供优质的小说和音乐资源下载服务。

## 功能特性

### 用户端
- ✅ 用户注册与登录系统
- ✅ 小说商城浏览与搜索
- ✅ 音乐商城浏览与搜索
- ✅ 商品详情展示
- ✅ 在线购买功能
- ✅ 文件下载权限管理
- ✅ 用户中心（个人资料、订单管理、下载记录）

### 管理功能
- ✅ 管理员登录
- ✅ 用户管理
- ✅ 商品管理
- ✅ 订单管理
- ✅ 数据统计

### 技术特性
- ✅ 安全的密码加密（bcrypt）
- ✅ CSRF 防护
- ✅ SQL 注入防护（PDO 预处理）
- ✅ XSS 防护
- ✅ 文件上传安全验证
- ✅ 高质量前端设计（现代极简主义风格）

## 技术栈

### 后端
- PHP 8.2+
- MySQL 8.0+
- PDO 数据库连接

### 前端
- HTML5 + CSS3
- JavaScript (ES6+)
- 独特的字体设计（Cormorant Garamond + Source Sans Pro）
- 响应式设计

## 安装说明

### 1. 环境要求
- PHP >= 8.2
- MySQL >= 8.0
- Apache/Nginx Web 服务器

### 2. 安装步骤

1. **克隆或下载项目**
   ```bash
   cd /your/web/directory
   ```

2. **配置数据库**
   - 创建 MySQL 数据库
   - 导入 `database.sql` 文件
   ```bash
   mysql -u root -p < database.sql
   ```

3. **配置数据库连接**
   - 编辑 `includes/config.php`
   - 修改数据库连接信息：
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'bookmusic_mall');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   ```

4. **配置 Web 服务器**

   **Apache 配置示例：**
   ```apache
   <VirtualHost *:80>
       ServerName bookmusic.local
       DocumentRoot /path/to/bookmusic
       
       <Directory /path/to/bookmusic>
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
       root /path/to/bookmusic;
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

5. **设置目录权限**
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 assets/
   ```

6. **访问网站**
   - 打开浏览器访问配置的域名
   - 默认管理员账户：
     - 用户名：`admin`
     - 密码：`admin123`
   - **请登录后立即修改默认密码**

## 目录结构

```
bookmusic/
├── index.php              # 首页
├── login.php              # 用户登录
├── register.php           # 用户注册
├── logout.php             # 用户退出
├── novels.php             # 小说商城
├── music.php              # 音乐商城
├── product.php            # 商品详情
├── buy.php                # 购买处理
├── download.php           # 下载处理
├── user/                  # 用户中心
│   ├── index.php          # 用户中心首页
│   ├── profile.php        # 个人资料
│   └── orders.php         # 我的订单
├── admin/                 # 管理后台
├── includes/              # 公共模块
│   ├── db.php             # 数据库连接
│   ├── auth.php           # 认证授权
│   ├── functions.php      # 工具函数
│   └── config.php         # 配置文件
├── uploads/               # 文件存储
│   ├── novels/            # 小说文件
│   ├── music/             # 音乐文件
│   ├── preview/           # 音乐试听
│   ├── cover/             # 封面图片
│   └── avatar/            # 用户头像
├── assets/                # 静态资源
│   ├── css/               # 样式文件
│   ├── js/                # 脚本文件
│   └── images/            # 公共图片
└── database.sql           # 数据库初始化脚本
```

## 使用说明

### 用户功能
1. **注册账户**：访问注册页面创建账户
2. **浏览商品**：在小说或音乐商城浏览商品
3. **购买商品**：点击商品查看详情并购买
4. **下载文件**：购买后可在用户中心下载文件

### 管理员功能
1. **登录后台**：使用管理员账户登录
2. **管理用户**：查看和管理用户账户
3. **管理商品**：添加、编辑、删除商品
4. **管理订单**：查看和处理订单
5. **数据统计**：查看销售和访问统计

## 安全建议

1. **修改默认密码**：立即修改管理员默认密码
2. **配置 HTTPS**：生产环境必须使用 HTTPS
3. **定期备份**：定期备份数据库和上传文件
4. **文件权限**：确保上传目录不可执行 PHP
5. **更新维护**：定期更新 PHP 和 MySQL 版本

## 开发说明

### 添加新功能
1. 在相应目录创建 PHP 文件
2. 引入必要的公共模块（`auth.php`, `db.php`）
3. 实现功能逻辑
4. 创建对应的前端页面

### 自定义样式
- 编辑 `assets/css/style.css` 修改全局样式
- 使用 CSS 变量统一管理颜色和间距

### 数据库操作
- 使用 PDO 预处理语句防止 SQL 注入
- 参考 `includes/db.php` 获取数据库连接

## 许可证

本项目仅供学习和研究使用。

## 联系方式

如有问题或建议，请联系：support@bookmusic.com

---

**注意：** 这是一个基础版本，实际生产环境需要：
- 完善的支付系统集成（微信/支付宝）
- 更完善的后台管理功能
- 性能优化和缓存机制
- 日志记录和监控
- 更多的安全防护措施
