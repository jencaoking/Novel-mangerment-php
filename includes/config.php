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

// 支付宝支付配置
define('ALIPAY_APP_ID', getenv('ALIPAY_APP_ID') ?: 'YOUR_ALIPAY_APP_ID');
define('ALIPAY_PRIVATE_KEY', getenv('ALIPAY_PRIVATE_KEY') ?: 'YOUR_ALIPAY_PRIVATE_KEY');
define('ALIPAY_PUBLIC_KEY', getenv('ALIPAY_PUBLIC_KEY') ?: 'YOUR_ALIPAY_PUBLIC_KEY');
define('ALIPAY_GATEWAY_URL', getenv('ALIPAY_GATEWAY_URL') ?: 'https://openapi.alipay.com/gateway.do');
define('ALIPAY_RETURN_URL', getenv('ALIPAY_RETURN_URL') ?: SITE_URL . 'payment/return');
define('ALIPAY_NOTIFY_URL', getenv('ALIPAY_NOTIFY_URL') ?: SITE_URL . 'payment/notify');
define('ALIPAY_SIGN_TYPE', 'RSA2');
define('ALIPAY_CHARSET', 'UTF-8');
define('ALIPAY_FORMAT', 'json');
define('ALIPAY_VERSION', '1.0');

// Resend 邮件服务配置
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: 're_cg1AZ5pn_HU6FgqKeGb348jzUufc6ck9i');

date_default_timezone_set('Asia/Shanghai');

// 开发环境显示错误，生产环境记录日志
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Session 配置使用函数而非 ini_set，确保在 session_start() 前生效
session_name(SESSION_NAME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
