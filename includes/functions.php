<?php
/**
 * BookMusic Mall - 公共函数库
 */

require_once __DIR__ . '/config.php';

/**
 * 安全输出HTML内容
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 生成CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * 验证CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * 密码加密
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

/**
 * 密码验证
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * 生成唯一订单号
 */
function generateOrderNo() {
    return date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 格式化价格
 */
function formatPrice($price) {
    return '¥' . number_format($price, 2, '.', ',');
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * 验证邮箱格式
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证用户名格式
 */
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{3,20}$/u', $username);
}

/**
 * 获取客户端IP
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * 获取User Agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * 重定向
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * JSON响应
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 上传文件
 */
function uploadFile($file, $targetDir, $allowedTypes, $maxSize) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => '没有上传文件'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传出错'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超出限制'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    $fileName = uniqid() . '.' . $fileExt;
    $targetPath = $targetDir . $fileName;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => '文件保存失败'];
}

/**
 * 分页函数
 */
function paginate($total, $page, $perPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 时间格式化
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d', $timestamp);
    }
}

/**
 * 截取字符串
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
}
