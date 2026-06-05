<?php
/**
 * BookMusic Mall - 配置文件
 * 小说与音乐数字内容销售平台
 */

// 数据库配置
define('DB_HOST', 'mysql6.sqlpub.com');
define('DB_PORT', '3311');
define('DB_NAME', 'novel00000');
define('DB_USER', 'jencao0');
define('DB_PASS', 'De3IIdSHLcwZMRHk');
define('DB_CHARSET', 'utf8mb4');

// 网站配置
define('SITE_NAME', 'BookMusic Mall');
define('SITE_URL', 'http://localhost/bookmusic/');
define('SITE_DESCRIPTION', '小说与音乐数字内容销售平台');

// 文件上传配置
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_NOVEL_SIZE', 50 * 1024 * 1024); // 50MB
define('MAX_MUSIC_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);  // 5MB

// 允许的文件类型
define('ALLOWED_NOVEL_TYPES', ['txt']);
define('ALLOWED_MUSIC_TYPES', ['mp3', 'wav', 'ogg']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Session配置
define('SESSION_NAME', 'bookmusic_session');
define('SESSION_LIFETIME', 7200); // 2小时

// 安全配置
define('HASH_COST', 12); // bcrypt加密强度
define('CSRF_TOKEN_NAME', 'csrf_token');

// 分页配置
define('ITEMS_PER_PAGE', 12);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session配置
ini_set('session.name', SESSION_NAME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
