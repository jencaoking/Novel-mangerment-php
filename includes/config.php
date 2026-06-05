<?php
/**
 * BookMusic Mall - 配置文件
 * 小说与音乐数字内容销售平台
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'bookmusic');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'BookMusic Mall');
define('SITE_URL', 'http://localhost:8000/');
define('SITE_DESCRIPTION', '小说与音乐数字内容销售平台');

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_NOVEL_SIZE', 50 * 1024 * 1024);
define('MAX_MUSIC_SIZE', 10 * 1024 * 1024);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);

define('ALLOWED_NOVEL_TYPES', ['txt']);
define('ALLOWED_MUSIC_TYPES', ['mp3', 'wav', 'ogg']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

define('SESSION_NAME', 'bookmusic_session');
define('SESSION_LIFETIME', 7200);

define('HASH_COST', 12);
define('CSRF_TOKEN_NAME', 'csrf_token');

define('ITEMS_PER_PAGE', 12);

date_default_timezone_set('Asia/Shanghai');

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.name', SESSION_NAME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
