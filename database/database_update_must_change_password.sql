-- ==========================================
-- 数据库迁移：强制首次登录修改密码
-- ==========================================
-- 版本: 2026_06_08_001
-- 说明:
--   默认管理员 (admin/admin123) 是公开文档给出的初始密码，
--   一旦线上部署必须强制管理员在首次登录后修改密码，
--   否则会留下可被利用的默认凭证。
--
--   通过在 users 表加 must_change_password 字段实现：
--     - 1: 必须修改密码
--     - 0: 正常
--   修改成功后置为 0。
--
-- 使用方法:
--   mysql -u root -p bookmusic_mall < database/database_update_must_change_password.sql
-- ==========================================

USE bookmusic_mall;

-- 1. 加字段 (用 IF NOT EXISTS 兼容 MySQL 8.0.29+ / MariaDB 10.0.2+)
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'must_change_password'
);

SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-必须修改密码(强制), 0-正常' AFTER status",
    "SELECT 'must_change_password 已存在, 跳过' AS info"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 把 admin 账号置为强制改密状态
UPDATE users
SET must_change_password = 1
WHERE username = 'admin';

-- 3. 验证
SELECT id, username, role, status, must_change_password
FROM users
WHERE must_change_password = 1;
