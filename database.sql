-- BookMusic Mall 数据库初始化脚本
-- 小说与音乐数字内容销售平台

-- 创建数据库
CREATE DATABASE IF NOT EXISTS bookmusic_mall DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE bookmusic_mall;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 分类表
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('novel', 'music') NOT NULL COMMENT '小说分类/音乐分类',
    sort_order INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 商品表
CREATE TABLE IF NOT EXISTS products (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 订单表
CREATE TABLE IF NOT EXISTS orders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 下载记录表
CREATE TABLE IF NOT EXISTS downloads (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 购物车表
CREATE TABLE IF NOT EXISTS cart (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_user_product (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默认分类数据
INSERT INTO categories (name, type, sort_order) VALUES 
('玄幻', 'novel', 1),
('都市', 'novel', 2),
('仙侠', 'novel', 3),
('悬疑', 'novel', 4),
('科幻', 'novel', 5),
('言情', 'novel', 6),
('流行', 'music', 1),
('摇滚', 'music', 2),
('电子', 'music', 3),
('古典', 'music', 4),
('民谣', 'music', 5),
('嘻哈', 'music', 6);

-- 插入默认管理员账户
-- 用户名: admin, 密码: admin123 (请登录后立即修改)
INSERT INTO users (username, email, password, role, status) VALUES 
('admin', 'admin@bookmusic.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEaGbRo.eW/ug1c4.5MvNlKlO9iK', 'admin', 1);

-- 插入示例商品数据
INSERT INTO products (title, type, category_id, author, description, cover, file_path, price, status) VALUES 
('斗破苍穹', 'novel', 1, '天蚕土豆', '这里是属于斗气的世界，没有花俏艳丽的魔法，有的，仅仅是繁衍到巅峰的斗气！', 'default.jpg', '1.txt', 9.99, 1),
('完美世界', 'novel', 1, '辰东', '一粒尘可填海，一根草斩日月星辰，弹指间诸天星辰寂灭。', 'default.jpg', '2.txt', 12.99, 1),
('夜的钢琴曲', 'music', 7, '石进', '夜的钢琴曲系列，治愈系钢琴音乐。', 'default.jpg', '1.mp3', 5.99, 1);

-- 创建索引
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_products_type ON products(type);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_product ON orders(product_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_downloads_user ON downloads(user_id);
CREATE INDEX idx_downloads_product ON downloads(product_id);
